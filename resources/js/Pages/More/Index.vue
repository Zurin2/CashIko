<script setup>
/** More (§8.15): menu of secondary destinations. */
import { Head, router, usePage } from '@inertiajs/vue3'
import { ReceiptText, BarChart3, CalendarDays, Building2, Sparkles, CreditCard, Settings as SettingsIcon, LogOut } from 'lucide-vue-next'

const business = usePage().props.business
const isPremium = business?.plan === 'premium'

const items = [
  { label: 'Gastos', href: route('expenses.index'), icon: ReceiptText },
  { label: 'Reports', href: route('reports.index'), icon: BarChart3 },
  { label: 'Daily Summary', href: route('daily.index'), icon: CalendarDays },
  { label: 'Branches', href: route('branches.index'), icon: Building2, premium: true },
  { label: 'Tanong kay CashIko', href: route('assistant.index'), icon: Sparkles, premium: true },
  { label: 'Pricing / Upgrade', href: route('pricing.index'), icon: CreditCard },
  { label: 'Settings', href: route('settings.index'), icon: SettingsIcon },
]
</script>

<template>
  <Head title="Higit pa" />
  <AppLayout>
    <div class="md:max-w-2xl mx-auto pb-8 space-y-4">
      <section class="flex items-center gap-3 rounded-2xl bg-forest px-5 py-5 text-white shadow-cardlg">
        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15 text-xl font-extrabold">{{ (usePage().props.auth.user?.name || '?').slice(0, 1) }}</span>
        <span class="min-w-0 flex-1">
          <span class="block truncate text-lg font-extrabold">{{ business?.name }}</span>
          <span class="block truncate text-xs opacity-80">{{ business?.type }} · {{ business?.location }}</span>
        </span>
        <span class="rounded-full px-3 py-1 text-[10px] font-extrabold uppercase tracking-wide" :class="isPremium ? 'bg-mango text-white' : 'bg-white/20'">
          {{ isPremium ? 'PREMIUM' : 'BASIC' }}
        </span>
      </section>

      <section class="overflow-hidden rounded-2xl border border-warm bg-white shadow-card">
        <component
          :is="'a'"
          v-for="item in items"
          :key="item.label"
          href="#"
          class="flex items-center gap-3 border-b border-warm/60 px-4 py-4 text-sm font-semibold text-forest-dark transition-colors last:border-0 hover:bg-cream"
          @click.prevent="router.visit(item.href)"
        >
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-forest/10 text-forest"><component :is="item.icon" class="h-4.5 w-4.5" /></span>
          <span class="flex-1">{{ item.label }}</span>
          <span v-if="item.premium && !isPremium" class="rounded-full bg-mango/15 px-2 py-0.5 text-[9px] font-extrabold uppercase text-mango">PREMIUM</span>
          <span class="text-stone-300">›</span>
        </component>
      </section>

      <button class="flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-red-200 font-semibold text-red-600 hover:bg-red-50" @click="router.post(route('logout'))">
        <LogOut class="h-4 w-4" /> Log out
      </button>

      <p class="pb-4 text-center text-[11px] text-forest-muted">CashIko v1 · ang business companion ng pinoy microbusiness</p>
    </div>
  </AppLayout>
</template>
