<script setup>
/** Reports (§8.9): Kita summary, breakdown, trend, best sellers, payment pie, hbars, utang, dead stock, CSV/print. */
import { computed, ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Download, Printer, TrendingUp } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import LineChart from '@/Components/Charts/LineChart.vue'
import PaymentPie from '@/Components/Charts/PaymentPie.vue'
import HBarChart from '@/Components/Charts/HBarChart.vue'

const props = defineProps({
  range: String,
  profit: Object,
  trend: Array,
  topProducts: Array,
  payments: Object,
  expensesByCategory: Object,
  utang: Object,
  deadStock: Array,
})

const ranges = [
  { key: 'today', label: 'Today' },
  { key: 'week', label: 'This week' },
  { key: 'month', label: 'This month' },
]

const setRange = (key) =>
  router.get(route('reports.index'), { range: key }, { preserveState: true, only: ['range', 'profit', 'trend', 'topProducts', 'payments', 'expensesByCategory', 'utang', 'deadStock'] })

const sentence = computed(() =>
  props.profit.realProfit >= 0
    ? `Kumita ka ng ₱${props.profit.realProfit.toLocaleString('en-PH', { minimumFractionDigits: 2 })} this ${props.range === 'today' ? 'day' : props.range}, ${props.profit.margin}% margin.`
    : `Debit ka ng ₱${Math.abs(props.profit.realProfit).toLocaleString('en-PH', { minimumFractionDigits: 2 })} this ${props.range} — tingnan ang gastos.`)

const print = () => window.print()
</script>

<template>
  <Head title="Reports" />
  <AppLayout>
    <div class="md:max-w-3xl mx-auto">
      <header class="mb-4 print-hide">
        <h1 class="text-xl font-bold text-forest-dark">Reports</h1>
      </header>

      <div class="no-scrollbar mb-4 flex gap-2 overflow-x-auto print-hide">
        <button v-for="r in ranges" :key="r.key" class="chip" :class="props.range === r.key ? 'chip-active' : 'chip-idle'" @click="setRange(r.key)">{{ r.label }}</button>
      </div>

      <section class="mb-3 rounded-2xl bg-gradient-to-br from-forest to-[hsl(150_30%_18%)] px-5 py-6 text-white shadow-cardlg">
        <div class="text-xs font-semibold opacity-80">Kita Summary</div>
        <p class="mt-1 text-lg font-bold leading-snug">{{ sentence }}</p>
      </section>

      <section class="mb-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Profit breakdown</h2>
        <div class="space-y-1.5 text-sm">
          <div class="flex justify-between"><span class="text-forest-muted">Total Sales</span><span class="font-semibold">₱{{ props.profit.sales.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span></div>
          <div class="flex justify-between"><span class="text-forest-muted">Cost of Goods</span><span class="font-semibold text-red-600">− ₱{{ props.profit.cost.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span></div>
          <div class="flex justify-between"><span class="text-forest-muted">Expenses</span><span class="font-semibold text-red-600">− ₱{{ props.profit.expenses.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span></div>
          <div class="flex items-baseline justify-between border-t border-warm pt-2">
            <span class="font-bold">Real Profit</span>
            <span class="text-peso text-2xl" :class="props.profit.realProfit >= 0 ? 'text-emerald-600' : 'text-red-600'">₱{{ props.profit.realProfit.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
          </div>
        </div>
        <p class="mt-2 text-[11px] text-forest-muted">Profit = Sales − Cost of goods − Expenses · margin {{ props.profit.margin }}%</p>
      </section>

      <section class="mb-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Sales trend (7 days)</h2>
        <LineChart :data="props.trend" />
      </section>

      <section class="mb-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Best Sellers</h2>
        <div v-if="props.topProducts.length" class="space-y-2">
          <div v-for="(p, i) in props.topProducts" :key="p.name" class="flex items-center gap-3">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-forest/10 text-xs font-extrabold text-forest">{{ i + 1 }}</span>
            <span class="min-w-0 flex-1 truncate text-sm font-semibold text-forest-dark">{{ p.name }}</span>
            <span class="text-xs text-forest-muted">{{ p.qty }} sold</span>
            <span class="tabular text-sm font-bold text-forest-dark">₱{{ p.rev.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-forest-muted">Wala pang benta sa panahong ito.</p>
      </section>

      <section class="mb-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Sales by Payment</h2>
        <PaymentPie :data="props.payments" />
      </section>

      <section class="mb-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Expenses by Category</h2>
        <HBarChart :data="props.expensesByCategory" />
      </section>

      <section class="mb-3 grid grid-cols-2 gap-3">
        <div class="rounded-2xl border border-orange-200 bg-orange-50 p-4">
          <div class="text-[11px] font-bold uppercase text-orange-700">Utang outstanding</div>
          <div class="text-peso text-xl text-orange-600">₱{{ props.utang.outstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
          <div class="text-[11px] font-bold uppercase text-emerald-700">Utang in window</div>
          <div class="text-peso text-xl text-emerald-600">₱{{ props.utang.inWindow.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</div>
        </div>
      </section>

      <section class="mb-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-3 text-sm font-bold text-forest-dark">Slow Movers (Dead Stock)</h2>
        <div v-if="props.deadStock.length" class="space-y-1.5">
          <div v-for="p in props.deadStock" :key="p.id" class="flex items-center justify-between gap-2 text-sm">
            <span class="min-w-0 flex-1"><b class="text-forest-dark">{{ p.name }}</b> <span class="text-[11px] text-forest-muted">{{ p.category }} · {{ p.quantity }} {{ p.unit }}</span></span>
            <span class="tabular font-bold text-orange-600">₱{{ p.value.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }} stock</span>
          </div>
        </div>
        <p v-else class="text-sm text-forest-muted">Walang dead stock — maliksi ang lahat ng items. 👍</p>
      </section>

      <div class="mb-8 grid grid-cols-2 gap-3 print-hide">
        <a :href="route('reports.csv')" class="flex h-12 items-center justify-center gap-2 rounded-xl bg-forest font-semibold text-white active:scale-[0.98]">
          <Download class="h-4 w-4" /> Download CSV
        </a>
        <button class="flex h-12 items-center justify-center gap-2 rounded-xl bg-cream font-semibold text-forest-dark hover:bg-warm active:scale-[0.98]" @click="print">
          <Printer class="h-4 w-4" /> Print / PDF
        </button>
      </div>
    </div>
  </AppLayout>
</template>
