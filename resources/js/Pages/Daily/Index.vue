<script setup>
/** Daily Summary (§8.10): today hero, 14-day chart, expandable day list. */
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { ChevronDown, CalendarDays, Wallet, ReceiptText, HandCoins, ShoppingBag as ShoppingIconOpen } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { peso, paymentLabel } from '@/lib/format'

const props = defineProps({ today: Object, chart: Array, days: Array })

const openKey = ref(null)
const toggle = (key) => (openKey.value = openKey.value === key ? null : key)

const tint = (d) => (d.profit >= 0 ? 'text-emerald-600' : 'text-red-600')
</script>

<template>
  <Head title="Daily Summary" />
  <AppLayout>
    <div class="md:max-w-3xl mx-auto">
      <header class="mb-4">
        <h1 class="text-xl font-bold text-forest-dark">Daily Summary</h1>
        <p class="text-sm text-forest-muted">Last 30 days · branch-aware</p>
      </header>

      <section class="mb-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
        <div class="text-[11px] font-bold uppercase tracking-wide text-forest-muted">Today</div>
        <div class="text-peso text-3xl text-forest-dark">{{ peso(props.today.sales) }}</div>
        <div class="mt-2 grid grid-cols-3 gap-2 text-center text-xs">
          <div class="rounded-xl bg-cream py-2"><div class="font-bold text-forest-dark">{{ peso(props.today.profit) }}</div><div class="text-stone-500">Kita</div></div>
          <div class="rounded-xl bg-cream py-2"><div class="font-bold text-forest-dark">{{ props.today.txns }}</div><div class="text-stone-500">Txns</div></div>
          <div class="rounded-xl bg-cream py-2"><div class="font-bold text-forest-dark">{{ peso(props.today.expenses) }}</div><div class="text-stone-500">Gastos</div></div>
        </div>
      </section>

      <section class="mb-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
        <h2 class="mb-2 text-sm font-bold text-forest-dark">14-day sales</h2>
        <BarChart :data="props.chart" />
      </section>

      <div v-if="props.days.length" class="mb-8 space-y-2">
        <article v-for="d in props.days" :key="d.key" class="overflow-hidden rounded-2xl border border-warm bg-white shadow-card">
          <button class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-cream" @click="toggle(d.key)">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-forest/10 text-forest"><CalendarDays class="h-4 w-4" /></span>
            <span class="min-w-0 flex-1">
              <span class="block text-sm font-bold text-forest-dark">{{ d.label }}</span>
              <span class="block text-[11px] text-forest-muted">{{ d.txns }} transactions</span>
            </span>
            <span class="text-right">
              <span class="block text-peso text-sm text-forest-dark">{{ peso(d.sales) }}</span>
              <span class="block text-[11px] font-bold" :class="tint(d)">Kita {{ peso(d.profit) }}</span>
            </span>
            <ChevronDown class="h-4 w-4 shrink-0 text-stone-400 transition-transform" :class="openKey === d.key ? 'rotate-180' : ''" />
          </button>

          <div v-if="openKey === d.key" class="border-t border-warm bg-surface px-4 py-3 text-sm">
            <div class="grid grid-cols-2 gap-2 pb-2">
              <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-2"><ShoppingIconOpen class="h-4 w-4 text-forest" /><div><div class="font-bold text-forest-dark">{{ peso(d.sales) }}</div><div class="text-[10px] text-stone-500">Benta</div></div></div>
              <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-2"><Wallet class="h-4 w-4 text-emerald-600" /><div><div class="font-bold" :class="tint(d)">{{ peso(d.profit) }}</div><div class="text-[10px] text-stone-500">Kita</div></div></div>
              <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-2"><ReceiptText class="h-4 w-4 text-red-600" /><div><div class="font-bold text-forest-dark">{{ peso(d.expenses) }}</div><div class="text-[10px] text-stone-500">Gastos</div></div></div>
              <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-2"><HandCoins class="h-4 w-4 text-orange-600" /><div><div class="font-bold text-forest-dark">{{ peso(d.payments + d.newUtang) }}</div><div class="text-[10px] text-stone-500">Utang activity</div></div></div>
            </div>

            <div class="mb-2 flex flex-wrap gap-1.5">
              <span v-for="(v, k) in d.paymentBreakdown" :key="k" class="chip chip-idle">{{ paymentLabel(k) }} {{ peso(v) }}</span>
              <span v-if="d.newUtang > 0" class="chip border-orange-200 bg-orange-50 text-orange-600">+{{ peso(d.newUtang) }} bagong utang</span>
            </div>

            <div v-if="d.topItems.length" class="rounded-xl bg-white px-3 py-2">
              <div class="mb-1 text-[10px] font-bold uppercase text-stone-500">Top items</div>
              <div v-for="t in d.topItems" :key="t.name" class="flex justify-between text-[13px]">
                <span class="font-semibold text-forest-dark">{{ t.name }}</span>
                <span class="text-forest-muted">{{ t.qty }} sold</span>
              </div>
            </div>
          </div>
        </article>
      </div>
      <EmptyState v-else :icon="CalendarDays" title="Wala pang activity" subtitle="The last 30 days are quiet — log a sale to populate.">
      </EmptyState>
    </div>
  </AppLayout>
</template>
