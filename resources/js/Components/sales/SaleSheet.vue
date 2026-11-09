<script setup>
/** POS "Magdagdag ng Benta" (§8.5) — full-screen sheet; server owns pricing checks. */
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { X, ArrowLeft } from 'lucide-vue-next'
import { peso, daysFromNow, paymentLabel } from '@/lib/format'

const props = defineProps({
  open: Boolean,
  products: { type: Array, default: () => [] },
  customers: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open', 'saved'])

const search = ref('')
const cart = ref([]) // [{product_id, name, quantity, unit_price, unit_cost, unit, left}]
const payment = ref('cash')
const customerMode = ref('existing')
const customerId = ref(null)
const newCustomer = ref({ name: '', phone: '' })
const dueDate = ref(daysFromNow(7))
const submitting = ref(false)

const filtered = computed(() =>
  props.products.filter((p) => !search.value || p.name.toLowerCase().includes(search.value.toLowerCase()))
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

const total = computed(() => cart.value.reduce((a, l) => a + l.quantity * l.unit_price, 0))

const canSave = computed(() => {
  if (!cart.value.length) return false
  if (payment.value === 'utang') {
    if (customerMode.value === 'existing') return Boolean(customerId.value)
    return newCustomer.value.name.trim().length > 0
  }
  return true
})

const close = () => {
  emit('update:open', false)
  search.value = ''
  cart.value = []
  payment.value = 'cash'
  customerMode.value = 'existing'
  customerId.value = null
  newCustomer.value = { name: '', phone: '' }
}

const save = () => {
  if (!canSave.value) return
  submitting.value = true

  const body = {
    items: cart.value.map((l) => ({ product_id: l.product_id, quantity: l.quantity })),
    payment_type: payment.value,
    due_date: payment.value === 'utang' ? dueDate.value : null,
    customer_id: payment.value === 'utang' && customerMode.value === 'existing' ? customerId.value : null,
    new_customer:
      payment.value === 'utang' && customerMode.value === 'new'
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
    <div v-if="open" class="fixed inset-0 z-50 flex flex-col bg-surface print-hide">
      <header class="flex items-center gap-3 border-b border-warm bg-white px-4 py-3">
        <button class="tap-target grid place-items-center rounded-lg hover:bg-cream" @click="close" aria-label="Close POS">
          <ArrowLeft class="h-5 w-5 text-forest-dark" />
        </button>
        <h2 class="flex-1 font-bold text-forest-dark">Magdagdag ng Benta</h2>
      </header>

      <div class="flex-1 overflow-y-auto">
        <div class="sticky top-0 z-10 bg-surface px-4 pb-2 pt-3">
          <input class="input" v-model="search" placeholder="Maghanap ng product…" />
        </div>

        <div class="grid grid-cols-2 gap-3 px-4 pb-4">
          <button
            v-for="p in filtered"
            :key="p.id"
            class="flex items-center gap-2.5 rounded-2xl border p-3 text-left transition-colors disabled:opacity-50"
            :class="inCart(p.id) ? 'border-forest ring-1 ring-forest bg-forest/5' : 'border-warm bg-white'"
            :disabled="p.quantity <= 0"
            @click="add(p)"
          >
            <img
              v-if="p.photo_url"
              :src="p.photo_url"
              :alt="p.name"
              class="h-12 w-12 shrink-0 rounded-xl object-cover border border-warm"
            />
            <span v-else class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-forest/10 text-base font-extrabold text-forest">
              {{ p.name.slice(0, 1) }}
            </span>
            <span class="min-w-0 flex-1">
              <span class="line-clamp-2 block text-[13px] font-semibold leading-tight text-forest-dark">{{ p.name }}</span>
              <span class="block text-[10px] text-forest-muted">
                {{ p.quantity }} {{ p.unit }} left
                <span v-if="p.quantity <= 0" class="font-bold text-red-500">· WALA</span>
              </span>
              <span class="text-sm font-bold text-forest">{{ peso(p.selling_price) }}</span>
            </span>
            <span v-if="inCart(p.id)" class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-forest text-xs font-bold text-white">
              {{ qtyOf(p.id) }}
            </span>
          </button>
        </div>
      </div>

      <!-- Cart panel -->
      <div v-if="cart.length" class="max-h-[46vh] overflow-y-auto rounded-t-2xl border-t border-warm bg-white p-4 shadow-cardlg">
        <h3 class="mb-2 text-sm font-bold text-forest-dark">Cart</h3>
        <div class="space-y-1.5">
          <div v-for="l in cart" :key="l.product_id" class="flex items-center gap-2 text-sm">
            <span class="min-w-0 flex-1 truncate font-medium text-forest-dark">{{ l.name }}</span>
            <button class="tap-target grid h-8 w-8 place-items-center rounded-lg bg-cream text-forest-dark active:scale-95" @click="bump(l.product_id, -1)">−</button>
            <span class="w-7 text-center font-bold">{{ l.quantity }}</span>
            <button class="tap-target grid h-8 w-8 place-items-center rounded-lg bg-forest text-white active:scale-95 disabled:opacity-40" :disabled="l.quantity >= l.left" @click="bump(l.product_id, +1)">+</button>
            <span class="w-20 text-right tabular font-bold text-forest-dark">{{ peso(l.quantity * l.unit_price) }}</span>
          </div>
        </div>

        <div class="mt-3 grid grid-cols-4 gap-2">
          <button
            v-for="m in ['cash', 'gcash', 'maya', 'utang']"
            :key="m"
            class="chip justify-center py-2"
            :class="payment === m ? 'chip-active' : 'chip-idle'"
            @click="payment = m"
          >{{ paymentLabel(m) }}</button>
        </div>

        <div v-if="payment === 'utang'" class="mt-3 rounded-2xl border border-orange-200 bg-orange-50 p-3">
          <div class="mb-2 grid grid-cols-2 gap-2 text-xs font-bold">
            <button :class="customerMode === 'existing' ? 'text-forest' : 'text-stone-400'" @click="customerMode = 'existing'">Existing customer</button>
            <button :class="customerMode === 'new' ? 'text-forest' : 'text-stone-400'" @click="customerMode = 'new'">Magdagdag ng bago</button>
          </div>
          <select v-if="customerMode === 'existing'" class="input" v-model="customerId">
            <option :value="null" disabled>Piliin ang customer</option>
            <option v-for="c in props.customers" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <template v-else>
            <input class="input mb-2" v-model="newCustomer.name" placeholder="Pangalan ng customer" />
            <input class="input mb-2" v-model="newCustomer.phone" inputmode="tel" placeholder="Telepono (optional)" />
          </template>
          <label class="mb-1 block text-xs font-semibold text-forest-muted">Due date</label>
          <input class="input !py-0.5" type="date" v-model="dueDate" />
        </div>

        <div class="mt-3 flex items-center justify-between">
          <span class="text-sm font-semibold text-forest-muted">Total</span>
          <span class="text-peso text-xl">{{ peso(total) }}</span>
        </div>
        <button
          class="mt-3 h-12 w-full rounded-xl bg-forest font-semibold text-white transition active:scale-[0.98] disabled:opacity-40"
          :disabled="!canSave || submitting"
          @click="save"
        >
          {{ submitting ? 'I-record…' : 'I-record ang Benta' }}
        </button>
      </div>
    </div>
  </Teleport>
</template>
