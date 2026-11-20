<script setup>
/** Settings (§8.14) + More page entry data tools. */
import { computed, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Plus, QrCode, RefreshCcw, ShieldCheck, LogOut, Trash2 } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import FormErrors from '@/Components/FormErrors.vue'
import Toaster from '@/Components/Toaster.vue'
import { PH_CITIES, PH_RURAL } from '@/lib/locations'
import { t } from '@/lib/i18n'

const business = usePage().props.business
const user = usePage().props.auth.user

const LANGUAGES = [
  { value: 'english', label: 'English' },
  { value: 'filipino', label: 'Filipino' },
]

const BASE_TYPES = ['Sari-sari store', 'Coffee shop', 'Carinderia/Eatery', 'Food stall', 'Clothing/Apparel']
const TYPES = BASE_TYPES.includes(business.type) ? [...BASE_TYPES] : [...BASE_TYPES, business.type]

const form = ref({
  name: business.name,
  type: business.type,
  location: business.location || '',
  default_due_days: business.default_due_days,
  default_reminder_tone: business.default_reminder_tone,
  auto_remind: business.auto_remind,
  low_stock_threshold: business.low_stock_threshold,
  language: business.language === 'filipino' ? 'filipino' : 'english',
})

const saving = ref(false)
const errors = ref({})
const methodErrors = ref({})

const save = () => {
  errors.value = {}
  saving.value = true
  router.post(route('settings.update'), { ...form.value, auto_remind: form.value.auto_remind ? '1' : '0' }, {
    onSuccess: () => (saving.value = false),
    onError: (e) => { saving.value = false; errors.value = e },
  })
}

const resetAll = () => {
  if (!confirm(t('Reset ALL data? This deletes every record including the business. This cannot be undone.'))) return
  if (!confirm(t('Last confirmation — delete everything?'))) return
  router.post(route('data.reset'), {})
}

const logout = () => router.post(route('logout'))

// --- Payment methods + QR (per-method account info & QR image upload) ---
const page = usePage()
const serverMethods = computed(() => page.props.paymentMethods || [])
const methods = ref([])
watch(serverMethods, (v) => { methods.value = v.map((m) => ({ ...m })) }, { immediate: true, deep: false })

const addLabel = ref('')
const adding = ref(false)
const busy = ref({})

const onQrPick = (m, e) => {
  const file = e.target.files?.[0] || null
  m._qrFile = file
  m._qrPreview = file ? URL.createObjectURL(file) : null
}

const saveMethod = (m) => {
  busy.value[m.id] = true
  const f = new FormData()
  f.append('label', m.label)
  f.append('account_name', m.account_name || '')
  f.append('account_number', m.account_number || '')
  f.append('is_active', m.is_active ? '1' : '0')
  if (m._qrFile) f.append('qr', m._qrFile)
  router.post(route('settings.payment-methods.save', m.id), f, {
    forceFormData: true,
    onSuccess: () => (methodErrors.value[m.id] = null),
    onError: (e) => (methodErrors.value[m.id] = e.label || e.account_name || e.account_number || e.qr || t('Could not save.')),
    onFinish: () => (busy.value[m.id] = false),
  })
}

const addMethod = () => {
  if (!addLabel.value.trim()) {
    methodErrors.value.add = t('Enter the method name.')
    return
  }
  methodErrors.value.add = null
  adding.value = true
  router.post(route('settings.payment-methods.add'), { label: addLabel.value }, {
    onError: (e) => (methodErrors.value.add = e.label || t('Could not add it.')),
    onFinish: () => { adding.value = false; addLabel.value = '' },
  })
}

const removeMethod = (m) => {
  if (!confirm(t('Delete {name}?', { name: m.label }))) return
  router.delete(route('settings.payment-methods.destroy', m.id))
}

</script>

<template>
  <Head :title="t('Settings')" />
  <AppLayout>
    <Toaster />
    <div class="md:max-w-2xl mx-auto pb-8 space-y-4">
      <h1 class="text-xl font-bold text-forest-dark">{{ t('Settings') }}</h1>

      <section class="rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">{{ t('Business Profile') }}</h2>
        <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Business name') }}</label>
        <input class="input mb-3" v-model="form.name" />
        <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Business type') }}</label>
        <select class="input mb-3" v-model="form.type">
          <option v-for="type in TYPES" :key="type" :value="type">{{ type }}</option>
        </select>
        <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Location') }}</label>
        <select class="input" v-model="form.location">
          <option value="" disabled>{{ t('Choose a city or municipality') }}</option>
          <optgroup v-for="g in PH_CITIES" :key="g.group" :label="g.group">
            <option v-for="c in g.options" :key="c" :value="c">{{ c }}</option>
          </optgroup>
          <optgroup label="Municipalities">
            <option v-for="r in PH_RURAL" :key="r" :value="r">{{ r }}</option>
          </optgroup>
        </select>
      </section>

      <section class="rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">{{ t('Utang Defaults') }}</h2>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Default due days') }}</label>
            <input class="input" inputmode="numeric" v-model="form.default_due_days" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Default reminder tone') }}</label>
            <select class="input" v-model="form.default_reminder_tone">
              <option value="gentle">{{ t('Gentle') }}</option>
              <option value="firm">{{ t('Firm') }}</option>
            </select>
          </div>
        </div>
        <label class="mt-4 flex items-center justify-between text-sm font-medium text-forest-dark">
          {{ t('Auto-remind (coming soon)') }}
          <button type="button" class="relative h-6 w-11 rounded-full transition-colors" :class="form.auto_remind ? 'bg-forest' : 'bg-warm'" @click="form.auto_remind = !form.auto_remind">
            <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all" :class="form.auto_remind ? 'left-[1.375rem]' : 'left-0.5'" />
          </button>
        </label>
      </section>

      <section class="rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">{{ t('Inventory + Language') }}</h2>
        <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Low stock threshold default') }}</label>
        <input class="input mb-4" inputmode="numeric" v-model="form.low_stock_threshold" />

        <p class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Language') }}</p>
        <div class="grid grid-cols-2 gap-2" role="group" :aria-label="t('Language')">
          <button
            v-for="opt in LANGUAGES"
            :key="opt.value"
            type="button"
            class="tap-target rounded-xl border px-3 text-sm font-semibold transition-colors"
            :class="form.language === opt.value
              ? 'border-forest bg-forest/5 text-forest-dark'
              : 'border-warm bg-white text-forest-muted hover:border-forest/40'"
            :aria-pressed="form.language === opt.value"
            @click="form.language = opt.value"
          >{{ t(opt.label) }}</button>
        </div>
        <p class="mt-2 text-[11px] text-forest-muted">{{ t('Changes the app copy. Takes effect the moment you save.') }}</p>
      </section>

      <FormErrors :errors="errors" />

      <button class="h-12 w-full rounded-xl bg-forest font-semibold text-white active:scale-[0.98] disabled:opacity-40" :disabled="saving" @click="save">
        {{ saving ? t('Saving…') : t('Save settings') }}
      </button>

      <section class="rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-1 text-sm font-bold text-forest-dark">{{ t('Payment Methods + QR') }}</h2>
        <p class="mb-3 text-[11px] text-forest-muted">{{ t('Upload a QR for GCash, Maya, or any e-wallet — it appears in the POS when that method is picked.') }}</p>

        <div class="space-y-3">
          <article v-for="m in methods" :key="m.id" class="rounded-2xl border border-warm bg-cream/40 p-3">
            <div class="flex items-center gap-3">
              <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl border border-warm bg-white">
                <img v-if="m._qrPreview || m.qr_url" :src="m._qrPreview || m.qr_url" :alt="m.label + ' QR'" class="h-full w-full object-contain" />
                <span v-else class="text-[9px] font-bold text-stone-400">NO QR</span>
              </div>
              <div class="min-w-0 flex-1">
                <input class="input !py-1.5 font-bold" v-model="m.label" :aria-label="m.label + ' label'" />
                <label class="mt-1.5 flex items-center gap-2 text-xs font-semibold text-forest-muted">
                  <input type="checkbox" v-model="m.is_active" class="accent-forest" /> {{ t('Active at POS') }}
                </label>
              </div>
              <button
                v-if="m.is_default === false"
                class="tap-target grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100"
                :aria-label="t('Delete {name}', { name: m.label })"
                @click="removeMethod(m)"
              >
                <Trash2 class="h-4 w-4" />
              </button>
            </div>

            <div class="mt-2 grid grid-cols-2 gap-2">
              <div>
                <label class="mb-1 block text-[10px] font-bold uppercase text-forest-muted">{{ t('Account name') }}</label>
                <input class="input !py-1.5 text-sm" v-model="m.account_name" :placeholder="t('e.g. Juan Dela Cruz')" />
              </div>
              <div>
                <label class="mb-1 block text-[10px] font-bold uppercase text-forest-muted">{{ t('Account number') }}</label>
                <input class="input !py-1.5 text-sm" v-model="m.account_number" inputmode="numeric" placeholder="0917…" />
              </div>
            </div>
            <p v-if="methodErrors[m.id]" class="mt-1.5 text-[11px] font-semibold text-red-600">{{ methodErrors[m.id] }}</p>

            <div class="mt-2 flex items-center justify-between gap-2">
              <label class="tap-target inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-forest/30 px-3 py-1.5 text-[11px] font-bold text-forest hover:bg-forest/5">
                <QrCode class="h-3.5 w-3.5" /> {{ m.qr_url || m._qrPreview ? t('Replace QR') : t('Upload QR') }}
                <input type="file" accept="image/*" class="hidden" @change="onQrPick(m, $event)" />
              </label>
              <button
                class="h-9 rounded-lg bg-forest px-4 text-xs font-bold text-white transition active:scale-95 disabled:opacity-40"
                :disabled="busy[m.id]"
                @click="saveMethod(m)"
              >
                {{ busy[m.id] ? t('Saving…') : t('Save') }}
              </button>
            </div>
          </article>
        </div>

        <div class="mt-3 flex items-center gap-2">
          <input class="input" v-model="addLabel" :placeholder="t('e.g. ShopeePay, PayPal, BPI…')" @keyup.enter="addMethod" />
          <button class="tap-target grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-forest text-white active:scale-95 disabled:opacity-40" :disabled="adding" :aria-label="t('Add payment method')" @click="addMethod">
            <Plus class="h-5 w-5" />
          </button>
        </div>
        <p v-if="methodErrors.add" class="mt-1.5 text-[11px] font-semibold text-red-600">{{ methodErrors.add }}</p>
      </section>

      <section class="rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">{{ t('Data') }}</h2>
        <div class="flex flex-col gap-2">
          <button class="flex h-11 items-center justify-center gap-2 rounded-xl bg-red-50 font-semibold text-red-600 hover:bg-red-100" @click="resetAll"><RefreshCcw class="h-4 w-4" /> {{ t('Reset all data') }}</button>
          <p class="text-[11px] text-forest-muted">{{ t('Offline mode — coming soon.') }}</p>
        </div>
      </section>

      <button v-if="user.is_superadmin" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-forest-dark font-semibold text-white" @click="router.visit(route('superadmin.index'))">
        <ShieldCheck class="h-4 w-4" /> {{ t('Superadmin') }}
      </button>

      <button class="flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-red-200 font-semibold text-red-600 hover:bg-red-50" @click="logout">
        <LogOut class="h-4 w-4" /> {{ t('Log out') }}
      </button>
    </div>
  </AppLayout>
</template>
