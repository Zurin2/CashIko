<script setup>
/** Sales (§8.4); grouped list + detail bottom sheet + void. */
import { computed, ref, onMounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Plus, X, Trash2 } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Toaster from '@/Components/Toaster.vue'
import SaleSheet from '@/components/sales/SaleSheet.vue'
import { peso, friendlyDate, paymentLabel, paymentColor, friendlyDateTime } from '@/lib/format'

const props = defineProps({ sales: Array, products: Array, customers: Array, filters: Object })
const branchParam = ref(new URLSearchParams(window.location.search).get('branch') || 'all')

const ranges = [
  { key: 'today', label: 'Today' },
  { key: 'week', label: 'This week' },
  { key: 'month', label: 'This month' },
  { key: 'all', label: 'All' },
]
const payments = ['all', 'cash', 'gcash', 'maya', 'utang']

const reload = (params) =>
  router.get(route('sales.index'), { ...params, branch: branchParam.value }, { preserveState: true, only: ['sales', 'products', 'customers', 'filters'] })

const openPos = ref(new URLSearchParams(window.location.search).has('new'))

const total = computed(() => props.sales.reduce((a, s) => a + Number(s.total), 0))

const grouped = computed(() => {
  const map = {}
  for (const s of props.sales) {
    const k = friendlyDate(s.date_time)
    ;(map[k] ||= []).push(s)
  }
  return Object.entries(map)
})

const detail = ref(null)
const voiding = ref(false)

const voidSale = async () => {
  if (!confirm('Void this sale? Stock is returned.')) return
  voiding.value = true
  router.post(route('sales.void', detail.value.id), {}, {
    preserveState: true,
    onSuccess: () => { detail.value = null; voiding.value = false },
    onError: () => { voiding.value = false },
    only: ['sales', 'filters'],
  })
}

onMounted(() => {})
</script>

<template>
  <Head title="Benta (Sales)" />
  <AppLayout>
    <Toaster />
    <div class="md:max-w-3xl mx-auto">
      <header class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-forest-dark">Benta (Sales)</h1>
        <button
          class="flex h-10 items-center gap-1 rounded-xl bg-forest px-4 text-sm font-semibold text-white active:scale-[0.98]"
          @click="openPos = true"
        >
          <Plus class="h-4 w-4" /> Benta
        </button>
      </header>

      <div class="no-scrollbar mb-3 flex items-center gap-2 overflow-x-auto">
        <button
          v-for="r in ranges"
          :key="r.key"
          class="chip"
          :class="props.filters.range === r.key ? 'chip-active' : 'chip-idle'"
          @click="reload({ range: r.key, payment: undefined })"
        >{{ r.label }}</button>
        <select
          class="input ml-auto !h-9 w-auto shrink-0 py-0 text-xs"
          :value="props.filters.payment"
          @change="reload({ payment: $event.target.value === 'all' ? undefined : $event.target.value })"
        >
          <option v-for="p in payments" :key="p" :value="p">{{ p === 'all' ? 'All payments' : paymentLabel(p) }}</option>
        </select>
      </div>

      <div class="mb-3 rounded-2xl border border-forest/15 bg-forest/5 px-4 py-3">
        <div class="text-[11px] font-semibold uppercase tracking-wide text-forest-muted">Total benta</div>
        <div class="text-peso text-2xl text-forest-dark">{{ peso(total) }}</div>
      </div>

      <div v-if="grouped.length" class="space-y-5">
        <section v-for="[date, list] in grouped" :key="date">
          <h3 class="mb-1.5 text-xs font-bold uppercase tracking-wide text-forest-muted">{{ date }}</h3>
          <div class="divide-y divide-warm overflow-hidden rounded-2xl border border-warm bg-white shadow-card">
            <button v-for="s in list" :key="s.id" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition-colors hover:bg-cream" @click="detail = s">
              <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-semibold text-forest-dark">
                  {{ s.items.map((i) => `${i.quantity}× ${i.name}`).join(', ') }}
                </div>
                <div class="text-[11px] text-forest-muted">
                  {{ friendlyDateTime(s.date_time).split(', ')[1] || friendlyDateTime(s.date_time) }} ·
                  <span :class="paymentColor(s.payment_type)">{{ paymentLabel(s.payment_type) }}</span>
                  <span v-if="s.customer_name"> · {{ s.customer_name }}</span>
                </div>
              </div>
              <div class="text-peso text-sm text-forest-dark">{{ peso(s.total) }}</div>
            </button>
          </div>
        </section>
      </div>
      <EmptyState v-else :icon="X" title="Wala pang benta" subtitle="Tap ang + Benta upang simulan ang POS." class="mt-6">
        <template #cta>
          <button class="rounded-xl bg-forest px-4 py-2 text-sm font-semibold text-white" @click="openPos = true">Magdagdag ng Benta</button>
        </template>
      </EmptyState>
    </div>

    <!-- Detail bottom sheet -->
    <Teleport to="body">
      <div v-if="detail" class="fixed inset-0 z-50 flex items-end print-hide" @click.self="detail = null">
        <div class="absolute inset-0 bg-black/50" />
        <div class="relative z-10 max-h-[85vh] w-full overflow-y-auto rounded-t-2xl bg-surface p-4 sm:mx-auto sm:max-w-md sm:rounded-2xl">
          <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold text-forest-dark">Sale detail</h2>
            <button class="tap-target grid place-items-center" @click="detail = null"><X class="h-5 w-5" /></button>
          </div>
          <div class="rounded-xl bg-white p-4 shadow-card text-sm">
            <div class="mb-2 text-xs text-forest-muted">{{ friendlyDateTime(detail.date_time) }} · <span :class="paymentColor(detail.payment_type)">{{ paymentLabel(detail.payment_type) }}</span><template v-if="detail.branch_name"> · {{ detail.branch_name }}</template></div>
            <div class="divide-y divide-warm">
              <div v-for="(line, i) in detail.items" :key="i" class="flex items-baseline justify-between gap-2 py-1.5">
                <span>{{ line.quantity }}× {{ line.name }}</span>
                <span class="tabular font-semibold">{{ peso(line.quantity * line.unit_price) }}</span>
              </div>
            </div>
            <div class="mt-3 flex items-center justify-between border-t border-warm pt-3">
              <span class="font-bold">Total</span>
              <span class="text-peso text-lg">{{ peso(detail.total) }}</span>
            </div>
            <p v-if="detail.customer_name" class="mt-1 text-xs text-forest-muted">Customer: {{ detail.customer_name }}</p>
            <button
              class="mt-4 flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-red-50 font-semibold text-red-600 ring-1 ring-red-200 transition hover:bg-red-100 active:scale-[0.98]"
              :disabled="voiding"
              @click="voidSale"
            >
              <Trash2 class="h-4 w-4" /> Void sale
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <SaleSheet v-model:open="openPos" :products="props.products" :customers="props.customers" />
  </AppLayout>
</template>
