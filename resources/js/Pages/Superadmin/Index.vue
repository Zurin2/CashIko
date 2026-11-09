<script setup>
/** Superadmin (§8.16): standalone, outside AppLayout; forbidden state handled. */
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { ArrowLeft, Crown, ShieldCheck, Users as UsersIcon, Store, ShoppingBag, HandCoins } from 'lucide-vue-next'
import { peso, friendlyDate } from '@/lib/format'

const props = defineProps({
  forbidden: { type: Boolean, default: false },
  users: { type: Array, default: () => [] },
  businesses: { type: Array, default: () => [] },
  totals: { type: Object, default: () => ({}) },
})

const tab = ref('users')
const back = () => router.visit(route('settings.index'))
</script>

<template>
  <Head title="Superadmin" />
  <div class="min-h-screen bg-surface">
    <header class="border-b border-warm bg-white">
      <div class="mx-auto flex max-w-4xl items-center gap-3 px-4 py-3">
        <button class="tap-target grid place-items-center" @click="back" aria-label="Back"><ArrowLeft class="h-5 w-5 text-forest-dark" /></button>
        <h1 class="text-xl font-bold text-forest-dark">Superadmin</h1>
      </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-5">
      <div v-if="forbidden" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-8 text-center text-sm font-semibold text-red-600">
        Hindi ka superadmin — walang access sa panel na ito.
      </div>

      <template v-else>
        <section class="grid grid-cols-2 gap-3 md:grid-cols-4">
          <div class="rounded-2xl border border-warm bg-white p-4 shadow-card"><div class="text-[10px] font-bold uppercase text-stone-500">Total users</div><div class="text-peso text-2xl text-forest-dark">{{ props.totals.users }}</div></div>
          <div class="rounded-2xl border border-warm bg-white p-4 shadow-card"><div class="text-[10px] font-bold uppercase text-stone-500">Businesses</div><div class="text-peso text-2xl text-forest-dark">{{ props.totals.businesses }}</div></div>
          <div class="rounded-2xl border border-warm bg-white p-4 shadow-card"><div class="text-[10px] font-bold uppercase text-stone-500">Total sales</div><div class="text-peso text-2xl text-forest-dark">{{ peso(props.totals.sales_total) }}</div></div>
          <div class="rounded-2xl border border-warm bg-white p-4 shadow-card"><div class="text-[10px] font-bold uppercase text-stone-500">Utang (all)</div><div class="text-peso text-2xl text-orange-600">{{ peso(props.totals.utang_outstanding) }}</div></div>
        </section>

        <div class="my-4 flex gap-2 rounded-2xl border border-warm bg-white p-1.5 shadow-card">
          <button class="tap-target flex flex-1 items-center justify-center gap-1.5 rounded-xl text-sm font-bold" :class="tab === 'users' ? 'bg-forest text-white' : 'text-forest-muted'" @click="tab = 'users'">
            <UsersIcon class="h-4 w-4" /> Users ({{ props.users.length }})
          </button>
          <button class="tap-target flex flex-1 items-center justify-center gap-1.5 rounded-xl text-sm font-bold" :class="tab === 'businesses' ? 'bg-forest text-white' : 'text-forest-muted'" @click="tab = 'businesses'">
            <Store class="h-4 w-4" /> Businesses ({{ props.businesses.length }})
          </button>
        </div>

        <div v-if="tab === 'users'" class="divide-y divide-warm overflow-hidden rounded-2xl border border-warm bg-white shadow-card">
          <div v-for="u in props.users" :key="u.id" class="flex items-center gap-3 px-4 py-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-forest/10 font-bold text-forest">{{ (u.full_name || u.email).slice(0, 1).toUpperCase() }}</span>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-1.5">
                <span class="truncate text-sm font-bold text-forest-dark">{{ u.full_name }}</span>
                <ShieldCheck v-if="u.is_superadmin" class="h-3.5 w-3.5 text-mango" />
              </div>
              <div class="truncate text-[11px] text-forest-muted">{{ u.email }}</div>
              <div class="text-[11px] text-stone-500">{{ u.business_name || 'No business yet' }}</div>
            </div>
            <span class="rounded-full bg-stone-100 px-2 py-0.5 text-[9px] font-bold uppercase text-stone-500">user</span>
          </div>
        </div>

        <div v-else class="space-y-3">
          <article v-for="b in props.businesses" :key="b.id" class="rounded-2xl border border-warm bg-white p-4 shadow-card">
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                  <h2 class="truncate font-bold text-forest-dark">{{ b.name }}</h2>
                  <Crown v-if="b.plan === 'premium'" class="h-3.5 w-3.5 shrink-0 text-mango" />
                </div>
                <div class="text-[11px] text-forest-muted">{{ b.type }} · {{ b.location }} · {{ b.owner_email }}</div>
              </div>
              <span class="rounded-full px-2.5 py-1 text-[9px] font-extrabold uppercase" :class="b.plan === 'premium' ? 'bg-mango text-white' : 'bg-stone-200 text-stone-500'">{{ b.plan }}</span>
            </div>
            <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
              <div class="rounded-xl bg-cream py-2"><div class="font-bold text-forest-dark">{{ peso(b.sales_total) }}</div><div class="text-stone-500">Benta</div></div>
              <div class="rounded-xl bg-cream py-2"><div class="font-bold" :class="b.profit >= 0 ? 'text-emerald-600' : 'text-red-600'">{{ peso(b.profit) }}</div><div class="text-stone-500">Kita</div></div>
              <div class="rounded-xl bg-cream py-2"><div class="font-bold text-orange-600">{{ peso(b.utang_outstanding) }}</div><div class="text-stone-500">Utang</div></div>
              <div class="rounded-xl bg-cream py-2"><div class="font-bold text-forest-dark">{{ b.products_count }}</div><div class="text-stone-500">Products</div></div>
            </div>
            <p class="mt-2 text-[10px] text-stone-500">Last sale {{ b.last_sale ? friendlyDate(b.last_sale) : '—' }} · joined {{ friendlyDate(b.created_date) }}</p>
          </article>
        </div>
      </template>
    </main>
  </div>
</template>
