<script setup>
/** Utang (§8.7): customers + reminders tabs, detail w/ ledger, add-utang / payment sheets. */
import { computed, onMounted, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Plus, Search, Send, Copy, MessageSquareText, HandCoins, Wallet, Users as UsersIcon, ChevronDown } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Sheet from '@/Components/Sheet.vue'
import FormErrors from '@/Components/FormErrors.vue'
import BranchSelect from '@/Components/BranchSelect.vue'
import Toaster from '@/Components/Toaster.vue'
import { peso, friendlyDate, todayISO, daysFromNow } from '@/lib/format'
import { t } from '@/lib/i18n'

const props = defineProps({ customers: Array, entries: Array, totals: Object, branches: Array })

const branchParam = ref(new URLSearchParams(window.location.search).get('branch') || 'all')
const tab = ref('customers')
const search = ref('')

const STATUS = {
  'Up to date': 'text-emerald-600 bg-emerald-100',
  Overdue: 'text-red-600 bg-red-100',
  'Due soon': 'text-orange-600 bg-orange-100',
  Outstanding: 'text-sky-600 bg-sky-100',
}

const TAGS = { trusted: 'text-emerald-700 bg-emerald-100', watch: 'text-amber-600 bg-amber-100', none: 'text-stone-500 bg-stone-200' }

const filtered = computed(() =>
  props.customers.filter((c) => !search.value || c.name.toLowerCase().includes(search.value.toLowerCase()))
)

const detail = ref(null)
const history = computed(() => {
  if (!detail.value) return { rows: [], balance: 0 }
  const mine = [...props.entries].filter((e) => String(e.customer_id) === String(detail.value.id))
    .map((e) => e)
  /* chronological, newest first with running balance */
  let balance = 0
  const rows = mine
    .slice()
    .reverse()
    .map((e) => {
      balance += e.type === 'utang' ? Number(e.amount) : -Number(e.amount)
      return { ...e, running: balance }
    })
    .reverse()
  return { rows, balance: mine.reduce((a, e) => a + (e.type === 'utang' ? +e.amount : -e.amount), 0) }
})

const openDetail = (c) => {
  detail.value = c
}

const setTag = (tag) => {
  router.post(route('customers.tag', detail.value.id), { tag }, { preserveState: true, only: ['customers', 'entries'] })
}

/* Add utang sheet */
const showAdd = ref(false)
const addForm = ref({ mode: 'existing', customer_id: null, new_name: '', new_phone: '', amount: '', due_date: '', branch_id: null, note: '' })
const savingU = ref(false)
const addErrors = ref({})

const openAdd = () => {
  addForm.value = { mode: 'existing', customer_id: null, new_name: '', new_phone: '', amount: '', due_date: daysFromNow(7), branch_id: null, note: '' }
  addErrors.value = {}
  showAdd.value = true
}

const saveUtang = () => {
  const f = addForm.value
  const errs = {}
  if (!Number(f.amount)) errs.amount = t('Enter the utang amount.')
  if (f.mode === 'existing' && !f.customer_id) errs.customer_id = t('Choose a customer.')
  if (f.mode === 'new' && !f.new_name.trim()) errs.new_name = t('Enter the customer name.')
  if (Object.keys(errs).length) {
    addErrors.value = errs
    return
  }
  addErrors.value = {}
  savingU.value = true
  router.post(route('utang.store'), {
    customer_id: f.mode === 'existing' ? f.customer_id : null,
    new_customer: f.mode === 'new' ? { name: f.new_name, phone: f.new_phone || null } : null,
    amount: Number(f.amount),
    due_date: f.due_date || null,
    branch_id: f.branch_id || null,
    note: f.note || null,
    branch: branchParam.value !== 'all' ? branchParam.value : null,
  }, {
    onSuccess: () => { showAdd.value = false; savingU.value = false },
    onError: (e) => { savingU.value = false; addErrors.value = e },
  })
}

/* Payment sheet (§7.9 branch attribution is server-side) */
const showPay = ref(false)
const payForm = ref({ customer_id: null, amount: '', date: todayISO(), note: '' })
const savingP = ref(false)
const payErrors = ref({})

const openPay = (c) => {
  payForm.value = { customer_id: c?.id ?? null, amount: '', date: todayISO(), note: '' }
  payErrors.value = {}
  showPay.value = true
}

const savePayment = () => {
  const errs = {}
  if (!Number(payForm.value.amount)) errs.amount = t('Enter the payment amount.')
  if (!payForm.value.customer_id) errs.customer_id = t('Choose a customer.')
  if (Object.keys(errs).length) {
    payErrors.value = errs
    return
  }
  payErrors.value = {}
  savingP.value = true
  router.post(route('utang.payment'), {
    ...payForm.value,
    amount: Number(payForm.value.amount),
    branch: branchParam.value !== 'all' ? branchParam.value : null,
  }, {
    onSuccess: () => { showPay.value = false; savingP.value = false },
    onError: (e) => { savingP.value = false; payErrors.value = e },
  })
}

/* Reminders (§7.10) */
const reminderBody = (c) =>
  t('Hi {name}, a friendly reminder that you have an outstanding balance of {amount} at {business}, due {date}. Thank you!', {
    name: c.name,
    amount: peso(c.balance),
    business: usePage().props.business.name,
    date: c.oldest_due ? friendlyDate(c.oldest_due) : t('no due date'),
  })

const candidates = computed(() =>
  props.customers.filter(
    (c) => c.balance > 0 && (!c.oldest_due || c.oldest_due <= daysFromNow(3))
  )
)

const sendReminder = (c) =>
  router.post(route('reminders.store'), { customer_id: c.id, message: reminderBody(c), tone: 'gentle' }, { preserveState: true, only: [] })

const copyReminder = async (c) => {
  await navigator.clipboard.writeText(reminderBody(c))
}

const sms = (c) => {
  window.location.href = `sms:${c.phone || ''}?body=${encodeURIComponent(reminderBody(c))}`
}

onMounted(() => {
  const q = new URLSearchParams(window.location.search)
  if (q.has('new')) openAdd()
})
</script>

<template>
  <Head :title="t('Utang ledger')" />
  <AppLayout>
    <Toaster />
    <div class="md:max-w-3xl mx-auto">
      <header class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-forest-dark">{{ t('Utang ledger') }}</h1>
        <button class="flex h-10 items-center gap-1 rounded-xl bg-forest px-4 text-sm font-semibold text-white active:scale-[0.98]" @click="openAdd">
          <Plus class="h-4 w-4" /> {{ t('Add utang') }}
        </button>
      </header>

      <section class="mb-4 grid grid-cols-2 gap-3">
        <div class="rounded-2xl border border-orange-200 bg-orange-50 p-4">
          <div class="text-[11px] font-bold uppercase tracking-wide text-orange-700">{{ t('Total outstanding') }}</div>
          <div class="text-peso text-xl text-orange-600">{{ peso(props.totals.outstanding) }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
          <div class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">{{ t('Collected this month') }}</div>
          <div class="text-peso text-xl text-emerald-600">{{ peso(props.totals.collected_this_month) }}</div>
        </div>
      </section>

      <div class="mb-3 flex gap-2 rounded-2xl border border-warm bg-white p-1.5 shadow-card">
        <button class="tap-target flex flex-1 items-center justify-center gap-1.5 rounded-xl text-sm font-bold transition-colors" :class="tab === 'customers' ? 'bg-forest text-white' : 'text-forest-muted'" @click="tab = 'customers'">
          <UsersIcon class="h-4 w-4" /> {{ t('Customers') }}
        </button>
        <button class="tap-target relative flex flex-1 items-center justify-center gap-1.5 rounded-xl text-sm font-bold transition-colors" :class="tab === 'reminders' ? 'bg-forest text-white' : 'text-forest-muted'" @click="tab = 'reminders'">
          <Send class="h-4 w-4" /> {{ t('Reminders') }}
          <span v-if="candidates.length" class="ml-1 rounded-full bg-mango px-1.5 text-[10px] font-extrabold text-white">{{ candidates.length }}</span>
        </button>
      </div>

      <template v-if="tab === 'customers'">
        <div class="relative mb-3">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
          <input class="input pl-9" :placeholder="t('Search customers…')" v-model="search" />
        </div>

        <div v-if="filtered.length" class="divide-y divide-warm overflow-hidden rounded-2xl border border-warm bg-white shadow-card">
          <button v-for="c in filtered" :key="c.id" class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-cream" @click="openDetail(c)">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-cream font-bold text-forest">{{ c.name.slice(0, 1) }}</span>
            <span class="min-w-0 flex-1">
              <span class="flex items-center gap-1.5">
                <span class="truncate text-sm font-bold text-forest-dark">{{ c.name }}</span>
                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase" :class="STATUS[c.status.label]">{{ t(c.status.label) }}</span>
                <span v-if="c.tag !== 'none'" class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase" :class="TAGS[c.tag]">{{ t(c.tag) }}</span>
              </span>
              <span class="block text-[11px] text-forest-muted">{{ c.phone || t('no phone') }} · {{ t('due') }} {{ c.oldest_due ? friendlyDate(c.oldest_due) : '—' }}</span>
            </span>
            <span class="text-peso text-sm" :class="c.balance > 0 ? 'text-orange-600' : 'text-emerald-600'">{{ peso(c.balance) }}</span>
          </button>
        </div>
        <EmptyState v-else :icon="UsersIcon" :title="t('No customers')" :subtitle="t('Add a customer when someone takes utang on credit.')"> </EmptyState>
      </template>

      <template v-else>
        <div v-if="candidates.length" class="space-y-3">
          <article v-for="c in candidates" :key="c.id" class="rounded-2xl border border-warm bg-white p-4 shadow-card">
            <div class="flex items-center justify-between gap-2">
              <h2 class="font-bold text-forest-dark">{{ c.name }} <span class="text-xs font-medium text-forest-muted">({{ peso(c.balance) }})</span></h2>
              <span class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase" :class="STATUS[c.status.label]">{{ t(c.status.label) }}</span>
            </div>
            <p class="mt-2 rounded-xl bg-cream p-2.5 text-[13px] leading-relaxed text-forest-dark">{{ reminderBody(c) }}</p>
            <div class="mt-2.5 flex gap-2">
              <button class="h-9 flex-1 rounded-xl bg-forest text-xs font-bold text-white hover:bg-forest/90 active:scale-[0.98]" @click="sendReminder(c)">{{ t('Send') }}</button>
              <button class="h-9 flex-1 rounded-xl bg-cream text-xs font-bold text-forest-dark hover:bg-warm active:scale-[0.98]" @click="copyReminder(c)"><Copy class="mr-1 inline h-3.5 w-3.5" />{{ t('Copy') }}</button>
              <button v-if="c.phone" class="h-9 flex-1 rounded-xl bg-orange-100 text-xs font-bold text-orange-600 hover:bg-orange-200 active:scale-[0.98]" @click="sms(c)"><MessageSquareText class="mr-1 inline h-3.5 w-3.5" />SMS</button>
            </div>
          </article>
        </div>
        <EmptyState v-else :icon="Send" :title="t('No reminders needed')" :subtitle="t('All customers are up to date, or nothing is overdue.')" />
      </template>
    </div>

    <!-- Customer detail (full screen) -->
    <Teleport to="body">
      <div v-if="detail" class="fixed inset-0 z-50 flex flex-col bg-surface print-hide">
        <header class="flex items-center justify-between border-b border-warm bg-white px-4 py-3">
          <h2 class="font-bold text-forest-dark">{{ detail.name }}</h2>
          <button class="tap-target grid place-items-center" @click="detail = null" :aria-label="t('Back')"><ChevronDown class="h-5 w-5 rotate-90" /></button>
        </header>
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <div class="rounded-2xl bg-forest px-4 py-5 text-white">
            <div class="text-xs font-semibold opacity-80">{{ t('Current balance') }}</div>
            <div class="text-peso text-3xl">{{ peso(history.balance) }}</div>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <button class="h-12 rounded-xl bg-emerald-600 font-semibold text-white hover:bg-emerald-700 active:scale-[0.98]" @click="openPay(detail)">{{ t('Record Payment') }}</button>
            <div class="flex rounded-xl border border-warm bg-white text-xs font-bold">
              <button
                v-for="tag in ['none', 'trusted', 'watch']"
                :key="tag"
                class="flex-1 first:rounded-l-xl last:rounded-r-xl py-2 transition-colors"
                :class="detail.tag === tag ? (tag === 'trusted' ? 'bg-emerald-100 text-emerald-700' : tag === 'watch' ? 'bg-amber-100 text-amber-600' : 'bg-cream text-forest-dark') : 'text-stone-400'"
                @click="setTag(tag)"
              >{{ t(tag) }}</button>
            </div>
          </div>

          <h3 class="pt-1 text-sm font-bold text-forest-dark">{{ t('History') }} <span class="text-xs font-medium text-forest-muted">({{ t('newest first') }})</span></h3>
          <div class="overflow-hidden rounded-2xl border border-warm bg-white shadow-card">
            <div v-for="e in history.rows" :key="e.id" class="flex items-start justify-between gap-3 border-b border-warm/60 px-4 py-3 last:border-0">
              <div class="flex gap-3">
                <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full text-[11px]" :class="e.type === 'utang' ? 'bg-orange-100 text-orange-600' : 'bg-emerald-100 text-emerald-600'">
                  {{ e.type === 'utang' ? '↑' : '↓' }}
                </span>
                <div>
                  <div class="text-sm font-semibold text-forest-dark">{{ e.type === 'utang' ? t('Utang') : t('Payment') }}</div>
                  <div class="text-[11px] text-forest-muted">
                    {{friendlyDate(e.date)}}
                    <template v-if="e.due_date"> · {{ t('due') }} {{ friendlyDate(e.due_date) }}</template>
                    <template v-if="e.branch_name"> · {{ e.branch_name }}</template>
                  </div>
                  <div v-if="e.note" class="text-[11px] italic text-stone-500">{{ e.note }}</div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-peso text-sm" :class="e.type === 'utang' ? 'text-orange-600' : 'text-emerald-600'">
                  {{ e.type === 'utang' ? '+' : '−' }}{{ peso(e.amount) }}
                </div>
                <div class="text-[10px] text-stone-400">{{ t('bal') }} {{ peso(e.running) }}</div>
              </div>
            </div>
            <p v-if="!history.rows.length" class="px-4 py-6 text-center text-sm text-forest-muted">{{ t('No history yet.') }}</p>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Add utang sheet -->
    <Sheet :open="showAdd" :title="t('New utang')" :action-label="t('Save utang')" :action-disabled="savingU" @close="showAdd = false" @submit="saveUtang">
      <FormErrors :errors="addErrors" />
      <div class="mb-3 grid grid-cols-2 gap-2 text-xs font-bold">
        <button :class="addForm.mode === 'existing' ? 'chip-active' : 'chip-idle'" class="chip justify-center py-2" @click="addForm.mode = 'existing'">{{ t('Existing customer') }}</button>
        <button :class="addForm.mode === 'new' ? 'chip-active' : 'chip-idle'" class="chip justify-center py-2" @click="addForm.mode = 'new'">{{ t('New customer') }}</button>
      </div>
      <select v-if="addForm.mode === 'existing'" class="input mb-2" v-model="addForm.customer_id" :class="addErrors.customer_id ? 'border-red-400' : ''">
        <option :value="null" disabled>{{ t('Choose a customer') }}</option>
        <option v-for="c in props.customers" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <p v-if="addErrors.customer_id" class="-mt-1 mb-2 text-[11px] font-semibold text-red-600">{{ addErrors.customer_id }}</p>
      <template v-else>
        <input class="input mb-2" v-model="addForm.new_name" :placeholder="t('Customer name')" />
        <p v-if="addErrors.new_name" class="-mt-1 mb-2 text-[11px] font-semibold text-red-600">{{ addErrors.new_name }}</p>
        <input class="input mb-2" v-model="addForm.new_phone" inputmode="tel" :placeholder="t('Phone (optional)')" />
      </template>
      <BranchSelect v-model="addForm.branch_id" class="mb-2" :label="t('Branch (optional)')" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Amount') }}</label>
      <input class="input mb-2" inputmode="decimal" v-model="addForm.amount" placeholder="₱0.00" required />
      <p v-if="addErrors.amount" class="-mt-1 mb-2 text-[11px] font-semibold text-red-600">{{ addErrors.amount }}</p>
      <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Due date') }}</label>
      <input class="input mb-2" type="date" v-model="addForm.due_date" :min="todayISO()" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Note (optional)') }}</label>
      <input class="input" v-model="addForm.note" :placeholder="t('e.g. ice + rice + 2 Coke')" />
    </Sheet>

    <!-- Payment sheet -->
    <Sheet :open="showPay" :title="t('Payment: {name}', { name: props.customers.find((c) => String(c.id) === String(payForm.customer_id))?.name || '' })" :action-label="t('Save payment')" :action-disabled="savingP" @close="showPay = false" @submit="savePayment">
      <FormErrors :errors="payErrors" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Amount') }}</label>
      <input class="input mb-2" inputmode="decimal" v-model="payForm.amount" placeholder="₱0.00" @focus="(e) => (payForm.amount = '')" />
      <p v-if="payErrors.amount" class="-mt-1 mb-2 text-[11px] font-semibold text-red-600">{{ payErrors.amount }}</p>
      <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Date') }}</label>
      <input class="input mb-2" type="date" v-model="payForm.date" :max="todayISO()" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Note (optional)') }}</label>
      <input class="input" v-model="payForm.note" :placeholder="t('e.g. partial payment')" />
    </Sheet>
  </AppLayout>
</template>
