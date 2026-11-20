<script setup>
/** POS "Bagong Transaksyon" (§8.5) — reference POS layout: rail + product grid + detail panel. */
import { computed, ref, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  ArrowLeft, Minus, Plus, Search, ShoppingCart, Trash2,
} from 'lucide-vue-next'
import { peso, daysFromNow } from '@/lib/format'
import { t } from '@/lib/i18n'

const props = defineProps({
  open: Boolean,
  products: { type: Array, default: () => [] },
  customers: { type: Array, default: () => [] },
  methods: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open', 'saved'])

const search = ref('')
const activeCat = ref(t('All'))
const searchInput = ref(null)
const cart = ref([]) // [{product_id, name, quantity, unit_price, unit_cost, unit, left}]
const payment = ref('cash')
const customerMode = ref('existing')
const customerId = ref(null)
const newCustomer = ref({ name: '', phone: '' })
const dueDate = ref(daysFromNow(7))
const submitting = ref(false)

const FALLBACK_PAYMENTS = [
  { code: 'cash', label: 'Cash' },
  { code: 'gcash', label: 'GCash' },
  { code: 'maya', label: 'Maya' },
]
const payments = computed(() =>
  props.methods.length
    ? props.methods.map((m) => ({ code: m.code, label: m.label, ...m }))
    : FALLBACK_PAYMENTS.map((m) => ({ ...m }))
)
const activeMethod = computed(() => payments.value.find((m) => m.code === payment.value))
const isUtang = ref(false) // utang = debt record, not a payment method

const cats = computed(() => {
  const counts = new Map()
  for (const p of props.products) {
    const c = p.category?.trim() || t('Others')
    counts.set(c, (counts.get(c) || 0) + 1)
  }
  return [{ name: t('All'), count: props.products.length },
    ...[...counts.entries()].map(([name, count]) => ({ name, count }))]
})

const filtered = computed(() =>
  props.products.filter((p) => {
    const catOk = activeCat.value === t('All') || (p.category?.trim() || t('Others')) === activeCat.value
    return catOk && (!search.value || p.name.toLowerCase().includes(search.value.toLowerCase()))
  })
)

const inCart = (id) => cart.value.some((l) => l.product_id === id)
const qtyOf = (id) => cart.value.find((l) => l.product_id === id)?.quantity || 0

const add = (p) => {
  if (p.quantity <= 0 || inCart(p.id)) return
  cart.value.push({ product_id: p.id, name: p.name, quantity: 1, unit_price: Number(p.selling_price), unit_cost: Number(p.cost_price), unit: p.unit, left: Number(p.quantity) })
}

const bump = (id, delta) => {
  const line = cart.value.find((l) => l.product_id === id)
  if (!line) return
  const next = line.quantity + delta
  if (next <= 0) cart.value = cart.value.filter((l) => l.product_id !== id)
  else line.quantity = Math.min(next, line.left)
}

const removeLine = (id) => {
  cart.value = cart.value.filter((l) => l.product_id !== id)
}

const total = computed(() => cart.value.reduce((a, l) => a + l.quantity * l.unit_price, 0))

const canSave = computed(() => {
  if (!cart.value.length) return false
  if (isUtang.value) {
    if (customerMode.value === 'existing') return Boolean(customerId.value)
    return newCustomer.value.name.trim().length > 0
  }
  return true
})

const close = () => {
  emit('update:open', false)
  search.value = ''
  activeCat.value = t('All')
  cart.value = []
  payment.value = payments.value[0]?.code || 'cash'
  isUtang.value = false
  customerMode.value = 'existing'
  customerId.value = null
  newCustomer.value = { name: '', phone: '' }
  // The sheet usually overlays /sales; deep-links (?new=1) from elsewhere go back explicitly.
  if (window.route && route().current() !== 'sales.index') router.visit(route('sales.index'))
}

const focusSearch = () => nextTick(() => searchInput.value?.focus())

const save = () => {
  if (!canSave.value) return
  submitting.value = true

  const body = {
    items: cart.value.map((l) => ({ product_id: l.product_id, quantity: l.quantity })),
    payment_type: isUtang.value ? 'utang' : payment.value,
    due_date: isUtang.value ? dueDate.value : null,
    customer_id: isUtang.value && customerMode.value === 'existing' ? customerId.value : null,
    new_customer:
      isUtang.value && customerMode.value === 'new'
        ? { name: newCustomer.value.name, phone: newCustomer.value.phone || null }
        : null,
  }

  router.post(route('sales.store'), body, {
    onSuccess: () => { close(); submitting.value = false },
    onError: () => (submitting.value = false),
  })
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 flex flex-col bg-cream print-hide">
      <!-- header: back to /sales -->
      <header class="flex items-center gap-3 border-b border-warm bg-white px-4 py-3">
        <button
          class="tap-target inline-flex items-center gap-1.5 rounded-lg py-1.5 pl-1.5 pr-3 text-sm font-bold text-forest transition-colors hover:bg-cream focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-forest/40"
          @click="close"
          :aria-label="t('Back to Sales')"
        >
          <ArrowLeft class="h-5 w-5" /> {{ t('Sales') }}
        </button>
        <h2 class="flex-1 truncate text-lg font-extrabold tracking-tight text-forest-dark">{{ t('New Transaction') }}</h2>
      </header>

      <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
        <!-- product catalog -->
        <section class="flex min-h-0 flex-1 flex-col">
          <!-- search + category tabs -->
          <div class="px-4 pb-2 pt-3">
            <div class="relative">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-forest/60" aria-hidden="true" />
              <input
                ref="searchInput"
                class="w-full rounded-lg border border-warm/70 bg-white py-2.5 pl-9 pr-3 text-sm text-forest-dark shadow-sm placeholder:text-stone-400 focus:border-forest/60 focus:outline-none focus:ring-2 focus:ring-forest/25"
                v-model="search"
                :placeholder="t('Search products…')"
              />
            </div>
          </div>
          <div class="flex gap-2 overflow-x-auto px-4 pb-2">
            <button
              v-for="c in cats"
              :key="c.name"
              class="chip shrink-0"
              :class="activeCat === c.name ? 'chip-active' : 'chip-idle'"
              @click="activeCat = c.name"
            >
              {{ c.name }} <span class="font-black">{{ c.count }}</span>
            </button>
          </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-6">
              <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                <article
                  v-for="p in filtered"
                  :key="p.id"
                  class="group flex flex-col overflow-hidden rounded-2xl border bg-white transition-colors"
                  :class="inCart(p.id) ? 'border-forest ring-1 ring-forest' : 'border-warm'"
                >
                  <div class="relative aspect-[4/3] bg-cream">
                    <img v-if="p.photo_url" :src="p.photo_url" :alt="p.name" class="h-full w-full object-cover" />
                    <span v-else class="grid h-full w-full place-items-center text-2xl font-extrabold text-forest/40">{{ p.name.slice(0, 1) }}</span>
                    <span
                      class="absolute left-2 top-2 rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide"
                      :class="p.quantity > 0 ? 'bg-forest/90 text-white' : 'bg-red-500 text-white'"
                    >
                      {{ p.quantity > 0 ? `${p.quantity} ${p.unit || ''} ${t('stock')}` : t('Out') }}
                    </span>
                  </div>
                  <div class="flex flex-1 flex-col p-3">
                    <h3 class="line-clamp-2 min-h-[2.4em] text-[13px] font-semibold leading-tight text-forest-dark">{{ p.name }}</h3>
                    <div class="mt-auto flex items-center justify-between pt-2">
                      <span class="text-sm font-extrabold text-forest-dark">{{ peso(p.selling_price) }}</span>
                      <button
                        v-if="!inCart(p.id)"
                        class="tap-target inline-flex items-center gap-1 rounded-lg border border-forest/30 px-2.5 py-1.5 text-[11px] font-bold text-forest transition-colors hover:bg-forest hover:text-white active:scale-95 disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-forest"
                        :disabled="p.quantity <= 0"
                        @click="add(p)"
                      >
                        <Plus class="h-3.5 w-3.5" /> {{ t('Add to Cart') }}
                      </button>
                      <span v-else class="grid h-7 min-w-7 place-items-center rounded-lg bg-forest px-1.5 text-xs font-bold text-white">
                        ×{{ qtyOf(p.id) }}
                      </span>
                    </div>
                  </div>
                </article>
              </div>
              <p v-if="!filtered.length" class="mt-10 text-center text-sm text-forest-muted">{{ t('No matches for search/category.') }}</p>
            </div>
          </section>

          <!-- Detail Transaction panel -->
          <aside class="flex max-h-[46vh] min-h-0 w-full shrink-0 flex-col border-t border-warm bg-white shadow-cardlg lg:max-h-none lg:w-[380px] lg:border-l lg:border-t-0 lg:shadow-none">
            <div class="flex items-center justify-between border-b border-warm px-4 py-3">
              <h3 class="text-sm font-extrabold tracking-tight text-forest-dark">{{ t('Transaction details') }}</h3>
              <button
                v-if="cart.length"
                class="tap-target inline-flex items-center gap-1 text-[11px] font-bold text-red-500 hover:text-red-600"
                @click="cart = []"
              >
                <Trash2 class="h-3.5 w-3.5" /> {{ t('Clear') }}
              </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3">
              <!-- cart lines -->
              <div v-if="cart.length" class="space-y-2.5">
                <div v-for="l in cart" :key="l.product_id" class="flex items-center gap-2.5">
                  <span class="min-w-0 flex-1">
                    <span class="block truncate text-[13px] font-semibold text-forest-dark">{{ l.name }}</span>
                    <span class="block text-[10px] text-forest-muted">{{ peso(l.unit_price) }} · {{ t('{count} left', { count: l.left }) }}</span>
                  </span>
                  <div class="flex items-center gap-1.5">
                    <button class="tap-target grid h-7 w-7 place-items-center rounded-lg bg-cream text-forest-dark active:scale-95" :aria-label="t('Decrease {name}', { name: l.name })" @click="bump(l.product_id, -1)">
                      <Minus class="h-3.5 w-3.5" />
                    </button>
                    <span class="w-6 text-center text-sm font-bold tabular-nums">{{ l.quantity }}</span>
                    <button class="tap-target grid h-7 w-7 place-items-center rounded-lg bg-forest text-white active:scale-95 disabled:opacity-40" :aria-label="t('Increase {name}', { name: l.name })" :disabled="l.quantity >= l.left" @click="bump(l.product_id, +1)">
                      <Plus class="h-3.5 w-3.5" />
                    </button>
                  </div>
                  <span class="w-20 text-right text-sm font-extrabold tabular-nums text-forest-dark">{{ peso(l.quantity * l.unit_price) }}</span>
                </div>
              </div>
              <p v-else class="grid place-items-center gap-1.5 py-8 text-center text-sm text-forest-muted">
                <ShoppingCart class="h-8 w-8 text-warm" aria-hidden="true" />
                {{ t('Cart is empty — tap “Add to Cart”.') }}
              </p>

              <!-- payment method -->
              <template v-if="cart.length">
                <p class="mb-2 mt-4 text-[11px] font-bold uppercase tracking-wide text-forest-muted">{{ t('Payment method') }}</p>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    v-for="m in payments"
                    :key="m.code"
                    class="rounded-xl border p-2.5 text-left transition-colors"
                    :class="!isUtang && payment === m.code ? 'border-forest bg-forest/5 ring-1 ring-forest' : 'border-warm bg-white hover:border-forest/40'"
                    @click="payment = m.code; isUtang = false"
                  >
                    <span class="flex items-center justify-between">
                      <span class="text-[13px] font-bold text-forest-dark">{{ m.label }}</span>
                      <span class="grid h-4 w-4 place-items-center rounded-full border" :class="!isUtang && payment === m.code ? 'border-forest bg-forest' : 'border-warm'">
                        <span v-if="!isUtang && payment === m.code" class="h-1.5 w-1.5 rounded-full bg-white" />
                      </span>
                    </span>
                    <span v-if="m.qr_url" class="text-[10px] font-bold text-forest">QR ✓</span>
                  </button>
                </div>

                <!-- Utang: separate debt action, not a payment method -->
                <button
                  class="mt-2.5 w-full rounded-xl border-2 border-dashed p-2.5 text-left transition-colors"
                  :class="isUtang ? 'border-orange-400 bg-orange-50' : 'border-orange-300 bg-white hover:bg-orange-50/60'"
                  @click="isUtang = !isUtang"
                >
                  <span class="flex items-center justify-between">
                    <span class="text-[13px] font-bold text-orange-600">{{ t('Record as Utang') }}</span>
                    <span class="grid h-4 w-4 place-items-center rounded-full border" :class="isUtang ? 'border-orange-500 bg-orange-500' : 'border-orange-300'">
                      <span v-if="isUtang" class="h-1.5 w-1.5 rounded-full bg-white" />
                    </span>
                  </span>
                  <span class="text-[10px] text-orange-500">{{ t('Debt record — no payment now') }}</span>
                </button>

                <!-- QR scan panel for the selected e-wallet -->
                <div v-if="!isUtang && activeMethod?.qr_url" class="mt-3 rounded-2xl border border-forest/30 bg-cream p-3 text-center">
                  <p class="text-xs font-bold text-forest-dark">{{ t('Scan to pay {amount}', { amount: peso(total) }) }}</p>
                  <img :src="activeMethod.qr_url" :alt="t('{name} QR code', { name: activeMethod.label })" class="mx-auto mt-2 h-40 w-40 rounded-xl border border-warm bg-white object-contain p-1" />
                  <p v-if="activeMethod.account_name || activeMethod.account_number" class="mt-1.5 text-[11px] font-semibold text-forest-muted">
                    {{ [activeMethod.account_name, activeMethod.account_number].filter(Boolean).join(' · ') }}
                  </p>
                  <p class="mt-1 text-[10px] text-forest-muted">{{ t('Finish the payment before recording.') }}</p>
                </div>

                <!-- utang customer -->
                <div v-if="isUtang" class="mt-3 rounded-2xl border border-orange-200 bg-orange-50 p-3">
                  <div class="mb-2 grid grid-cols-2 gap-2 text-xs font-bold">
                    <button :class="customerMode === 'existing' ? 'text-forest' : 'text-stone-400'" @click="customerMode = 'existing'">{{ t('Existing customer') }}</button>
                    <button :class="customerMode === 'new' ? 'text-forest' : 'text-stone-400'" @click="customerMode = 'new'">{{ t('Add new') }}</button>
                  </div>
                  <select v-if="customerMode === 'existing'" class="input" v-model="customerId">
                    <option :value="null" disabled>{{ t('Select customer') }}</option>
                    <option v-for="c in props.customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                  <template v-else>
                    <input class="input mb-2" v-model="newCustomer.name" :placeholder="t('Customer name')" />
                    <input class="input mb-2" v-model="newCustomer.phone" inputmode="tel" :placeholder="t('Phone (optional)')" />
                  </template>
                  <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t('Due date') }}</label>
                  <input class="input !py-0.5" type="date" v-model="dueDate" />
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-dashed border-warm pt-3">
                  <span class="text-sm font-semibold text-forest-muted">{{ t('Total') }}</span>
                  <span class="text-peso text-xl">{{ peso(total) }}</span>
                </div>
              </template>
            </div>

            <div class="border-t border-warm p-4">
              <button
                class="h-12 w-full rounded-xl bg-forest text-[15px] font-bold text-white transition-all duration-150 ease-out hover:bg-forest-dark active:scale-[0.98] disabled:opacity-40"
                :disabled="!canSave || submitting"
                @click="save"
              >
                {{ submitting ? t('Recording…') : cart.length ? (isUtang ? t('Record Utang · {amount}', { amount: peso(total) }) : t('Confirm · {amount}', { amount: peso(total) })) : t('Confirm') }}
              </button>
            </div>
          </aside>
        </div>
    </div>
  </Teleport>
</template>
