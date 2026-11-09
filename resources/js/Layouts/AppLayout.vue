<script setup>
/**
 * App shell (spec §9 AppLayout + §13 navigation map): desktop sidebar,
 * mobile bottom tabs, branch selector (premium + >1 branch), FAB on
 * Home/Sales. Redirects to /onboarding when no business exists (backed by
 * the EnsureBusinessExists middleware in Laravel).
 */
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import {
  Home, ShoppingBag, Boxes, HandCoins, ReceiptText, BarChart3,
  CalendarDays, Building2, Sparkles, CreditCard, Settings as SettingsIcon,
  Menu, X, Plus, ChevronDown, LogOut, Gem,
} from 'lucide-vue-next'

const page = usePage()
const mobileMenu = ref(false)
const fabOpen = ref(false)

const branches = computed(() => page.props.branches || [])
const business = computed(() => page.props.business)
const isPremium = computed(() => business.value?.plan === 'premium')

/* selectedBranch: in-memory only, not persisted (spec §10). */
const branchParam = ref(new URLSearchParams(window.location.search).get('branch') || 'all')
const url = (path, query = {}) => {
  if (isPremium.value && branches.value.length > 1 && branchParam.value) {
    query = { branch: branchParam.value, ...query }
  }
  return route(path, query)
}

const onBranchChange = (e) => {
  branchParam.value = e.target.value
  router.reload({ data: { branch: e.target.value }, only: [], preserveState: true })
}

const showBranchSelect = computed(() => isPremium.value && branches.value.length > 1)

const tabs = [
  { label: 'Home', path: '/home', href: url('home'), icon: Home, active: computed(() => page.url === '/home') },
  { label: 'Benta', path: '/sales', href: url('sales.index'), icon: ShoppingBag, active: computed(() => page.url.startsWith('/sales')) },
  { label: 'Stock', path: '/stock', href: url('stock.index'), icon: Boxes, active: computed(() => page.url.startsWith('/stock')) },
  { label: 'Utang', path: '/utang', href: url('utang.index'), icon: HandCoins, active: computed(() => page.url.startsWith('/utang')) },
  { label: 'Higit pa', path: '/more', href: url('more'), icon: Menu, active: computed(() => page.url.startsWith('/more')) },
]

const sidebar = [
  { label: 'Home', href: url('home') },
  { label: 'Benta (Sales)', href: url('sales.index') },
  { label: 'Stock', href: url('stock.index') },
  { label: 'Utang', href: url('utang.index') },
  { label: 'Gastos', href: url('expenses.index') },
  { label: 'Reports', href: url('reports.index') },
  { label: 'Daily Summary', href: url('daily.index') },
  { label: 'Branches', href: url('branches.index'), premium: true },
  { label: 'Tanong kay CashIko', href: url('assistant.index'), premium: true },
  { label: 'Pricing', href: url('pricing.index') },
  { label: 'Settings', href: url('settings.index') },
]

const fabButtons = [
  { label: 'Magdagdag ng Benta', href: url('sales.index', { new: 1 }) },
  { label: 'Magdagdag ng Utang', href: url('utang.index', { new: 1 }) },
  { label: 'Mag-restock', href: url('stock.index', { restock: 1 }) },
  { label: 'Gastos', href: url('expenses.index', { new: 1 }) },
]

const logout = () => router.post(route('logout'))

/* Active check against current URL path without query. */
const isActive = (href) => page.url.split('?')[0] === href.split('?')[0]
const onFabNavigation = (href) => {
  fabOpen.value = false
  router.visit(href)
}
</script>

<template>
  <div class="min-h-screen bg-surface pb-24 md:pb-8">
    <!-- Desktop sidebar -->
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col border-r border-warm bg-white md:flex">
      <div class="flex items-center gap-2 px-5 py-5">
        <div class="grid h-9 w-9 place-items-center rounded-xl bg-forest text-lg font-extrabold text-white">C</div>
        <div>
          <div class="font-bold leading-none text-forest-dark">CashIko</div>
          <div class="text-[11px] font-medium text-forest-muted">Business companion</div>
        </div>
      </div>

      <nav class="flex-1 space-y-0.5 overflow-y-auto px-3">
        <Link
          v-for="item in sidebar"
          :key="item.label"
          :href="item.href"
          class="flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-150"
          :class="isActive(item.href) ? 'bg-forest text-white' : 'text-forest-dark hover:bg-cream'"
        >
          <span class="flex items-center gap-2.5">
            {{ item.label }}
            <Gem v-if="item.premium && !isPremium" class="h-3.5 w-3.5 text-mango" />
          </span>
        </Link>
      </nav>

      <div class="border-t border-warm p-4">
        <div class="flex items-center gap-3">
          <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-mango/15 text-sm font-bold text-mango">
            {{ (page.props.auth.user?.name || '?').slice(0, 1).toUpperCase() }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="truncate text-sm font-semibold text-forest-dark">{{ page.props.auth.user?.name }}</div>
            <div class="truncate text-[11px] text-forest-muted">{{ page.props.auth.user?.email }}</div>
          </div>
          <button type="button" class="tap-target grid place-items-center rounded-lg text-forest-muted hover:text-red-600" title="Log out" @click="logout">
            <LogOut class="h-4 w-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Mobile header -->
    <header class="sticky top-0 z-40 border-b border-warm bg-white/95 backdrop-blur md:hidden print-hide">
      <div class="flex items-center justify-between px-4 py-3">
        <button class="tap-target grid place-items-center md:hidden" @click="mobileMenu = true" aria-label="Menu">
          <Menu class="h-5 w-5 text-forest-dark" />
        </button>
        <div class="grid place-items-center text-left md:hidden">
          <div class="font-bold text-forest-dark">CashIko</div>
        </div>
        <select
          v-if="showBranchSelect"
          class="input max-w-[140px] !h-9 py-0 text-xs"
          :value="branchParam"
          @change="onBranchChange"
        >
          <option value="all">All Branches</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">
            {{ b.name }}<template v-if="!b.is_active"> (Inactive)</template>
          </option>
        </select>
      </div>
    </header>

    <!-- Mobile drawer -->
    <Teleport to="body">
      <div v-if="mobileMenu" class="fixed inset-0 z-50 md:hidden" @click.self="mobileMenu = false">
        <div class="absolute inset-0 bg-black/50" @click="mobileMenu = false"></div>
        <div class="absolute inset-y-0 left-0 w-72 bg-white p-5 shadow-cardlg">
          <div class="mb-5 flex items-center justify-between">
            <div class="text-lg font-extrabold text-forest">CashIko</div>
            <button @click="mobileMenu = false" aria-label="close"><X class="h-5 w-5" /></button>
          </div>
          <nav class="space-y-1">
            <Link
              v-for="item in sidebar"
              :key="item.label"
              :href="item.href"
              class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium"
              :class="isActive(item.href) ? 'bg-forest text-white' : 'text-forest-dark hover:bg-cream'"
              @click="mobileMenu = false"
            >
              <span class="flex items-center gap-2">
                {{ item.label }}
                <Gem v-if="item.premium && !isPremium" class="h-3.5 w-3.5 text-mango" />
              </span>
            </Link>
          </nav>
          <div class="mt-6 border-t border-warm pt-4 text-xs text-forest-muted">
            <div class="font-semibold text-forest-dark">{{ page.props.auth.user?.name }}</div>
            {{ page.props.auth.user?.email }}
            <button class="mt-2 block font-semibold text-red-600" @click="logout">Log out</button>
          </div>
        </div>
      </div>
    </Teleport>

    <main class="mx-auto max-w-[1100px] px-4 pt-4 md:pl-64 md:pr-8 md:pt-8">
      <slot />
    </main>

    <!-- FAB -->
    <div v-if="['/home', '/sales'].includes(page.url.split('?')[0])" class="fixed bottom-24 right-4 z-40 md:right-8 print-hide">
      <div v-if="fabOpen" class="fixed inset-0 z-20" @click="fabOpen = false" />
      <div v-if="fabOpen" class="absolute bottom-16 right-0 mb-2 w-56 overflow-hidden rounded-2xl bg-white p-1 shadow-cardlg">
        <button
          v-for="b in fabButtons"
          :key="b.label"
          class="tap-target flex w-full items-center gap-2 rounded-xl px-3 text-left text-sm font-semibold text-forest-dark hover:bg-cream"
          @click="onFabNavigation(b.href)"
        >
          <Plus class="h-4 w-4 text-mango" />{{ b.label }}
        </button>
      </div>
      <button
        class="grid h-14 w-14 place-items-center rounded-full bg-mango text-white shadow-cardlg active:scale-95"
        :aria-expanded="fabOpen"
        @click="fabOpen = !fabOpen"
        :class="fabOpen ? 'rotate-180' : ''"
      >
        <Plus class="h-6 w-6 transition-transform duration-150" />
      </button>
    </div>

    <!-- Mobile bottom tabs -->
    <nav class="fixed bottom-0 left-0 right-0 z-30 border-t border-warm bg-white md:hidden print-hide">
      <div class="mx-auto grid h-16 max-w-md grid-cols-5">
        <Link
          v-for="t in tabs"
          :key="t.label"
          :href="t.href"
          class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-semibold transition-colors duration-150"
          :class="t.active.value ? 'text-forest' : 'text-stone-400'"
        >
          <component :is="t.icon" class="h-5 w-5" :stroke-width="t.active.value ? 2.5 : 2" />
          {{ t.label }}
        </Link>
      </div>
    </nav>
  </div>
</template>
