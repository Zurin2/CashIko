<script setup>
/** Settings (§8.14) + More page entry data tools. */
import { ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Database, RefreshCcw, ShieldCheck, LogOut } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Toaster from '@/Components/Toaster.vue'
import { PH_CITIES, PH_RURAL } from '@/lib/locations'

const business = usePage().props.business
const user = usePage().props.auth.user

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
  language: business.language,
})

const saving = ref(false)

const save = () => {
  saving.value = true
  router.post(route('settings.update'), { ...form.value, auto_remind: form.value.auto_remind ? '1' : '0' }, {
    onSuccess: () => (saving.value = false),
    onError: () => (saving.value = false),
  })
}

const loadSample = () => {
  if (!confirm('Load sample data? It only fills what is missing.')) return
  router.post(route('data.sample'), {})
}

const resetAll = () => {
  if (!confirm('Reset ALL data? This deletes every record including the business. This cannot be undone.')) return
  if (!confirm('Last confirmation — delete everything?')) return
  router.post(route('data.reset'), {})
}

const logout = () => router.post(route('logout'))
</script>

<template>
  <Head title="Settings" />
  <AppLayout>
    <Toaster />
    <div class="md:max-w-2xl mx-auto pb-8 space-y-4">
      <h1 class="text-xl font-bold text-forest-dark">Settings</h1>

      <section class="rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Business Profile</h2>
        <label class="mb-1 block text-xs font-semibold text-forest-muted">Negosyo name</label>
        <input class="input mb-3" v-model="form.name" />
        <label class="mb-1 block text-xs font-semibold text-forest-muted">Business type</label>
        <select class="input mb-3" v-model="form.type">
          <option v-for="t in TYPES" :key="t" :value="t">{{ t }}</option>
        </select>
        <label class="mb-1 block text-xs font-semibold text-forest-muted">Location</label>
        <select class="input" v-model="form.location">
          <option value="" disabled>Piliin ang lungsod o munisipyo</option>
          <optgroup v-for="g in PH_CITIES" :key="g.group" :label="g.group">
            <option v-for="c in g.options" :key="c" :value="c">{{ c }}</option>
          </optgroup>
          <optgroup label="Municipalities">
            <option v-for="r in PH_RURAL" :key="r" :value="r">{{ r }}</option>
          </optgroup>
        </select>
      </section>

      <section class="rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Utang Defaults</h2>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-xs font-semibold text-forest-muted">Default due days</label>
            <input class="input" inputmode="numeric" v-model="form.default_due_days" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-forest-muted">Default reminder tone</label>
            <select class="input" v-model="form.default_reminder_tone">
              <option value="gentle">Gentle</option>
              <option value="firm">Firm</option>
            </select>
          </div>
        </div>
        <label class="mt-4 flex items-center justify-between text-sm font-medium text-forest-dark">
          Auto-remind (coming soon)
          <button type="button" class="relative h-6 w-11 rounded-full transition-colors" :class="form.auto_remind ? 'bg-forest' : 'bg-warm'" @click="form.auto_remind = !form.auto_remind">
            <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all" :class="form.auto_remind ? 'left-[1.375rem]' : 'left-0.5'" />
          </button>
        </label>
      </section>

      <section class="rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Inventory + Language</h2>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-xs font-semibold text-forest-muted">Low stock threshold default</label>
            <input class="input" inputmode="numeric" v-model="form.low_stock_threshold" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-forest-muted">Language</label>
            <select class="input" v-model="form.language">
              <option value="taglish">Taglish</option>
              <option value="english">English</option>
            </select>
          </div>
        </div>
      </section>

      <button class="h-12 w-full rounded-xl bg-forest font-semibold text-white active:scale-[0.98] disabled:opacity-40" :disabled="saving" @click="save">
        {{ saving ? 'Saving…' : 'Save settings' }}
      </button>

      <section class="rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Data</h2>
        <div class="flex flex-col gap-2">
          <button class="flex h-11 items-center justify-center gap-2 rounded-xl bg-cream font-semibold text-forest-dark hover:bg-warm" @click="loadSample"><Database class="h-4 w-4" /> Load sample data</button>
          <button class="flex h-11 items-center justify-center gap-2 rounded-xl bg-red-50 font-semibold text-red-600 hover:bg-red-100" @click="resetAll"><RefreshCcw class="h-4 w-4" /> Reset all data</button>
          <p class="text-[11px] text-forest-muted">Offline mode — coming soon.</p>
        </div>
      </section>

      <button v-if="user.is_superadmin" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-forest-dark font-semibold text-white" @click="router.visit(route('superadmin.index'))">
        <ShieldCheck class="h-4 w-4" /> Superadmin
      </button>

      <button class="flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-red-200 font-semibold text-red-600 hover:bg-red-50" @click="logout">
        <LogOut class="h-4 w-4" /> Log out
      </button>
    </div>
  </AppLayout>
</template>
