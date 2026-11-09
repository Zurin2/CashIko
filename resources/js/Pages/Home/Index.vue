<script setup>
/** Home dashboard (§8.3). */
import { usePage, Link } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import { ShoppingBag, HandCoins, Boxes, ReceiptText, Wallet, ChevronRight, Sparkles } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Toaster from '@/Components/Toaster.vue'
import { peso, paymentLabel, paymentColor, friendlyDateTime, greeting } from '@/lib/format'

const props = defineProps({ stats: Object, chart: Array, lowStock: Array, recent: Array })

const firstName = usePage().props.auth.user?.name?.split(' ')[0] || ''

const quick = [
  { label: 'Benta', href: route('sales.index', { new: 1 }), icon: ShoppingBag },
  { label: 'Utang', href: route('utang.index', { new: 1 }), icon: HandCoins },
  { label: 'Restock', href: route('stock.index', { restock: 1 }), icon: Boxes },
  { label: 'Gastos', href: route('expenses.index', { new: 1 }), icon: ReceiptText },
]

const cards = [
  { label: 'Benta Ngayon', value: peso(props.stats.todaySales), sub: props.stats.todayTxns + ' transactions', href: route('sales.index'), icon: ShoppingBag, tint: 'bg-forest/10 text-forest' },
  { label: 'Kita Ngayon', value: peso(props.stats.todayProfit), sub: "Today's profit", href: null, icon: Wallet, tint: 'bg-emerald-100 text-emerald-700' },
  { label: 'Utang na Hindi Pa Bayad', value: peso(props.stats.utangOut), sub: props.stats.utangPeople + ' customers', href: route('utang.index'), icon: HandCoins, tint: 'bg-orange-100 text-orange-600' },
  { label: 'Low Stock Alerts', value: String(props.stats.lowStockCount), sub: 'items need restock', href: route('stock.index'), icon: Boxes, tint: 'bg-red-100 text-red-600' },
]
</script>

<template>
  <Head title="Home" />
  <AppLayout>
    <Toaster />

    <header>
      <h1 class="text-xl font-bold text-forest-dark">{{ greeting() }}, {{ firstName }}! 👋</h1>
      <p class="text-sm text-forest-muted">{{ $page.props.business.name }}</p>
    </header>

    <section class="mt-4 grid grid-cols-2 gap-3">
      <component
        :is="c.href ? Link : 'div'"
        v-for="c in cards"
        :key="c.label"
        :href="c.href ?? '#'"
        class="rounded-2xl border border-warm bg-white p-4 shadow-card transition-colors duration-150 hover:border-forest/40"
      >
        <span class="grid h-9 w-9 place-items-center rounded-xl" :class="c.tint"><component :is="c.icon" class="h-4.5 w-4.5" /></span>
        <div class="mt-3 text-[11px] font-semibold uppercase tracking-wide text-stone-500">{{ c.label }}</div>
        <div class="text-peso text-2xl text-forest-dark">{{ c.value }}</div>
        <div class="text-[11px] text-forest-muted">{{ c.sub }}</div>
      </component>
    </section>

    <section class="mt-3 grid grid-cols-4 gap-3">
      <Link v-for="q in quick" :key="q.label" :href="q.href" class="flex flex-col items-center gap-1.5 rounded-2xl border border-warm bg-white p-3 shadow-card transition-colors hover:border-mango/60 active:scale-[0.98]">
        <span class="grid h-9 w-9 place-items-center rounded-full bg-mango/15 text-mango"><component :is="q.icon" class="h-4.5 w-4.5" /></span>
        <span class="text-[11px] font-bold text-forest-dark">{{ q.label }}</span>
      </Link>
    </section>

    <section class="mt-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
      <div class="mb-2 flex items-baseline justify-between">
        <h2 class="text-sm font-bold text-forest-dark">Benta nitong 7 araw</h2>
        <span class="text-xs text-forest-muted">{{ peso(props.stats.weekSales) }} total</span>
      </div>
      <BarChart :data="props.chart" />
    </section>

    <section class="mt-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-bold text-forest-dark">Low Stock</h2>
        <Link :href="route('stock.index')" class="flex items-center gap-0.5 text-xs font-semibold text-forest">View <ChevronRight class="h-3.5 w-3.5" /></Link>
      </div>
      <div v-if="props.lowStock.length" class="space-y-2">
        <div v-for="p in props.lowStock" :key="p.id" class="flex items-center justify-between gap-2 rounded-xl bg-cream px-3 py-2">
          <span class="min-w-0 flex-1 truncate text-sm font-medium text-forest-dark">{{ p.name }}</span>
          <span class="text-xs font-bold" :class="p.out ? 'text-red-600' : 'text-orange-600'">
            {{ p.out ? 'WALA NA' : p.quantity + ' ' + p.unit }}
          </span>
        </div>
      </div>
      <p v-else class="text-sm text-forest-muted">Lahat ng stock okay ngayon. 👍</p>
    </section>

    <section class="mt-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
      <h2 class="mb-3 text-sm font-bold text-forest-dark">Recent Activity</h2>
      <div v-if="props.recent.length" class="divide-y divide-warm">
        <div v-for="s in props.recent" :key="s.id" class="flex items-center justify-between gap-3 py-2.5">
          <div class="min-w-0">
            <div class="truncate text-sm font-semibold text-forest-dark">{{ s.summary }}</div>
            <div class="text-[11px] text-forest-muted">
              {{ friendlyDateTime(s.date_time) }} · <span :class="paymentColor(s.payment_type)">{{ paymentLabel(s.payment_type) }}</span>
            </div>
          </div>
          <div class="text-peso text-sm text-forest-dark">{{ peso(s.total) }}</div>
        </div>
      </div>
      <EmptyState v-else :icon="ShoppingBag" title="Wala pang benta" subtitle="I-record ang unang benta para makita ang kita ngayon.">
        <template #cta>
          <Link :href="route('sales.index', { new: 1 })" class="rounded-xl bg-forest px-4 py-2 text-sm font-semibold text-white">Magdagdag ng Benta</Link>
        </template>
      </EmptyState>
    </section>

    <Link v-if="$page.props.business.plan === 'premium'" :href="route('assistant.index')" class="mt-3 mb-6 flex items-center gap-3 rounded-2xl bg-forest px-4 py-3.5 text-white transition active:scale-[0.99]">
      <Sparkles class="h-5 w-5 text-mango" />
      <span class="flex-1 text-sm font-semibold">Tanong kay CashIko</span>
      <ChevronRight class="h-4 w-4 opacity-80" />
    </Link>
    <Link v-else :href="route('pricing.index')" class="mt-3 mb-6 flex items-center gap-3 rounded-2xl border border-forest/20 bg-forest/5 px-4 py-3.5 transition active:scale-[0.99]">
      <Sparkles class="h-5 w-5 text-mango" />
      <span class="flex-1 text-sm font-semibold text-forest-dark">Gusto mo ba ng AI assistant? Upgrade to Premium</span>
      <ChevronRight class="h-4 w-4 text-forest" />
    </Link>
  </AppLayout>
</template>
