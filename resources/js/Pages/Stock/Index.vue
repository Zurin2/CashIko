<script setup>
/** Stock (§8.6): search, chips, product cards, product form, restock sheet. */
import { computed, onMounted, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Plus, Boxes, Search, Pencil, Trash2, PackageOpen, Camera, X as XMark } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Sheet from '@/Components/Sheet.vue'
import BranchSelect from '@/Components/BranchSelect.vue'
import Toaster from '@/Components/Toaster.vue'
import { peso, todayISO } from '@/lib/format'

const props = defineProps({
  products: Array,
  categories: Array,
  filters: Object,
})

const branchParam = ref(new URLSearchParams(window.location.search).get('branch') || 'all')

const CATEGORY_PRESETS = ['Drinks', 'Snacks', 'Noodles', 'Pantry', 'Bakery', 'Toiletries', 'Vice', 'Service', 'Other']
const UNITS = ['pcs', 'pack', 'bottle', 'kg', 'sachet', 'stick', 'box', 'loaf', 'kilo']

const statusChips = [
  { key: 'all', label: 'All' },
  { key: 'low', label: 'Low stock' },
  { key: 'out', label: 'Out of stock' },
  { key: 'dead', label: 'Dead stock' },
]

const search = ref(props.filters.search)
const status = ref(props.filters.status)
const category = ref(props.filters.category)
let searchTimer = null

const reload = (extra = {}) =>
  router.get(
    route('stock.index'),
    {
      search: search.value || undefined,
      status: status.value === 'all' ? undefined : status.value,
      category: category.value === 'all' ? undefined : category.value,
      branch: branchParam.value,
      ...extra,
    },
    { preserveState: true, only: ['products', 'categories', 'filters'] }
  )

const onSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => reload(), 250)
}

/* Product form state */
const showProductForm = ref(false)
const editing = ref(null)
const form = ref({
  name: '', category: 'Drinks', unit: 'pcs', quantity: 0, cost_price: '',
  selling_price: '', low_stock_threshold: 5, branch_id: null, photo_path: null,
  expiration_date: '', customCategory: '',
})
const savingProduct = ref(false)

const effectiveCategory = computed(() =>
  form.value.category === 'Other' && form.value.customCategory.trim()
    ? form.value.customCategory.trim()
    : form.value.category
)

const openProductForm = (p = null) => {
  editing.value = p
  form.value = p
    ? { name: p.name, category: CATEGORY_PRESETS.includes(p.category) ? p.category : 'Other', unit: p.unit, quantity: p.quantity, cost_price: p.cost_price, selling_price: p.selling_price, low_stock_threshold: p.low_stock_threshold, branch_id: p.branch_id, photo_path: null, expiration_date: p.expiration_date || '', customCategory: CATEGORY_PRESETS.includes(p.category) ? '' : p.category || '', photoPreview: p.photo_url }
    : { name: '', category: 'Drinks', unit: 'pcs', quantity: 0, cost_price: '', selling_price: '', low_stock_threshold: usePage().props.business?.low_stock_threshold ?? 5, branch_id: null, photo_path: null, expiration_date: '', customCategory: '', photoPreview: null }
  showProductForm.value = true
}

const uploadPhoto = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  const fd = new FormData()
  fd.append('photo', file)
  const res = await fetch(route('photos.store'), {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, Accept: 'application/json' },
    body: fd,
  })
  const data = await res.json()
  if (data.photo_path) {
    form.value.photo_path = data.photo_path
    form.value.photoPreview = URL.createObjectURL(file)
  }
}

const removePhoto = () => (form.value.photo_path = null)

const saveProduct = () => {
  if (!form.value.name.trim() || form.value.selling_price === '') return
  savingProduct.value = true
  const payload = {
    name: form.value.name,
    category: effectiveCategory.value || null,
    unit: form.value.unit,
    cost_price: Number(form.value.cost_price),
    selling_price: Number(form.value.selling_price),
    low_stock_threshold: form.value.low_stock_threshold,
    branch_id: form.value.branch_id || null,
    photo_path: form.value.photo_path || null,
    expiration_date: form.value.expiration_date || null,
    ...((editing.value ? {} : { quantity: Number(form.value.quantity) || 0 })),
  }
  const url = editing.value ? route('products.update', editing.value.id) : route('products.store')
  router.post(url, payload, {
    onSuccess: () => { showProductForm.value = false; savingProduct.value = false },
    onError: () => (savingProduct.value = false),
  })
}

/* Restock sheet (§7.6) */
const showRestock = ref(false)
const restockTarget = ref(null)
const restockForm = ref({ quantity: '', cost_price: '', supplier: '' })
const savingRestock = ref(false)

const restockFirst = computed(() => props.products?.[0])

const openRestock = (p) => {
  restockTarget.value = p || restockFirst.value
  restockForm.value = { quantity: '', cost_price: p?.cost_price ?? '', supplier: '' }
  showRestock.value = true
}

const newStock = computed(() =>
  Number(restockTarget.value?.quantity || 0) + Number(restockForm.value.quantity || 0)
)

const saveRestock = () => {
  if (!Number(restockForm.value.quantity) || restockForm.value.cost_price === '') return
  savingRestock.value = true
  router.post(route('products.restock', restockTarget.value.id), {
    quantity: Number(restockForm.value.quantity),
    cost_price: Number(restockForm.value.cost_price),
    supplier: restockForm.value.supplier || null,
  }, {
    preserveState: true,
    only: ['products', 'categories', 'filters'],
    onSuccess: () => { showRestock.value = false; savingRestock.value = false },
    onError: () => (savingRestock.value = false),
  })
}

const deleteProduct = (p) => {
  if (!confirm(`Delete ${p.name}?`)) return
  router.delete(route('products.destroy', p.id), { only: ['products', 'categories'] })
}

const margin = (p) => Number(p.selling_price) - Number(p.cost_price)
const badge = (p) => (p.status === 'out' ? 'text-red-600 bg-red-100' : p.status === 'low' ? 'text-orange-600 bg-orange-100' : 'text-emerald-600 bg-emerald-100')

/* ?restock=1 deep link: open the restock flow on the first product. */
onMounted(() => {
  const q = new URLSearchParams(window.location.search)
  if (q.has('restock') && restockFirst.value) openRestock(restockFirst.value)
})
</script>

<template>
  <Head title="Stock" />
  <AppLayout>
    <Toaster />
    <div class="md:max-w-3xl mx-auto">
      <header class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-forest-dark">Stock</h1>
        <button class="flex h-10 items-center gap-1 rounded-xl bg-forest px-4 text-sm font-semibold text-white active:scale-[0.98]" @click="openProductForm()">
          <Plus class="h-4 w-4" /> Product
        </button>
      </header>

      <div class="mb-3 flex gap-2">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
          <input class="input pl-9" placeholder="Maghanap ng product…" v-model="search" @input="onSearch" />
        </div>
        <select class="input !h-11 w-auto min-w-36 py-0 text-xs" v-model="category" @change="reload()">
          <option value="all">All categories</option>
          <option v-for="c in props.categories" :key="c" :value="c">{{ c }}</option>
        </select>
      </div>

      <div class="no-scrollbar mb-3 flex gap-2 overflow-x-auto">
        <button v-for="c in statusChips" :key="c.key" class="chip" :class="status === c.key ? 'chip-active' : 'chip-idle'" @click="(status = c.key, reload())">
          {{ c.label }}
        </button>
      </div>

      <div v-if="props.products?.length" class="space-y-3">
        <article v-for="p in props.products" :key="p.id" class="rounded-2xl border border-warm bg-white p-4 shadow-card">
          <div class="flex gap-3">
            <img v-if="p.photo_url" :src="p.photo_url" :alt="p.name" class="h-14 w-14 shrink-0 rounded-xl border border-warm object-cover" />
            <span v-else class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-forest/10 text-lg font-extrabold text-forest">{{ p.name.slice(0, 1) }}</span>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <h2 class="min-w-0 flex-1 truncate font-bold text-forest-dark">{{ p.name }}</h2>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="badge(p)">
                  {{ p.status === 'out' ? 'Out of stock' : p.status === 'low' ? 'Low' : 'Okay' }}
                </span>
              </div>
              <p class="text-[11px] text-forest-muted">
                {{ p.category }} · {{ p.quantity }} {{ p.unit }} · margin {{ peso(margin(p)) }}
                <template v-if="p.branch_name"> · {{ p.branch_name }}</template>
              </p>
              <p class="text-[11px] text-forest-muted">Cost {{ peso(p.cost_price) }} · Sell {{ peso(p.selling_price) }}</p>
              <p v-if="p.expiration_date" class="text-[11px] text-stone-500">Expires {{ p.expiration_date }}</p>
              <p v-if="p.is_dead_stock" class="text-[11px] font-semibold text-orange-600">⚠️ Walang sale sa loob ng 30 araw — dead stock</p>
            </div>
          </div>
          <div class="mt-3 flex gap-2">
            <button class="h-9 flex-1 rounded-xl bg-forest/10 text-xs font-bold text-forest hover:bg-forest/15 active:scale-[0.98]" @click="openRestock(p)">Restock</button>
            <button class="h-9 flex-1 rounded-xl bg-cream text-xs font-bold text-forest-dark hover:bg-warm active:scale-[0.98]" @click="openProductForm(p)">Edit</button>
            <button class="h-9 flex-1 rounded-xl bg-red-50 text-xs font-bold text-red-600 hover:bg-red-100 active:scale-[0.98]" @click="deleteProduct(p)">Delete</button>
          </div>
        </article>
      </div>
      <EmptyState v-else :icon="Boxes" title="Walang nahanap na product" subtitle="Add ng product para masimulan ang stock tracking." class="mt-6">
        <template #cta>
          <button class="rounded-xl bg-forest px-4 py-2 text-sm font-semibold text-white" @click="openProductForm()">Add Product</button>
        </template>
      </EmptyState>
    </div>

    <!-- Product form (full-screen sheet) -->
    <Teleport to="body">
      <div v-if="showProductForm" class="fixed inset-0 z-50 flex flex-col bg-surface print-hide">
        <header class="flex items-center justify-between border-b border-warm bg-white px-4 py-3">
          <h2 class="font-bold text-forest-dark">{{ editing ? 'Edit Product' : 'Bagong Product' }}</h2>
          <button class="tap-target grid place-items-center" @click="showProductForm = false" aria-label="Close"><XMark class="h-5 w-5" /></button>
        </header>
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <div class="rounded-2xl border border-warm bg-white p-4">
            <label class="mb-1 block text-xs font-semibold text-forest-muted">Photo (private, signed URL lamang)</label>
            <div v-if="form.photoPreview" class="flex items-center gap-3">
              <img :src="form.photoPreview" class="h-20 w-20 rounded-xl object-cover border border-warm" />
              <button class="text-xs font-semibold text-red-600" @click="(removePhoto(), (form.photoPreview = null))">Remove</button>
            </div>
            <label v-else class="flex h-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-warm text-xs font-semibold text-forest-muted hover:border-forest/50">
              <Camera class="h-5 w-5" /> I-upload ang photo
              <input type="file" accept="image/*" class="hidden" @change="uploadPhoto" />
            </label>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold text-forest-muted">Product name</label>
            <input class="input" v-model="form.name" placeholder="Hal. Lucky Me Pancit Canton" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-xs font-semibold text-forest-muted">Category</label>
              <select class="input" v-model="form.category">
                <option v-for="c in CATEGORY_PRESETS" :key="c" :value="c">{{ c }}</option>
              </select>
              <input v-if="form.category === 'Other'" class="input mt-2" v-model="form.customCategory" placeholder="+ Custom category" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-forest-muted">Unit</label>
              <select class="input" v-model="form.unit">
                <option v-for="u in UNITS" :key="u" :value="u">{{ u }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-xs font-semibold text-forest-muted">Cost price</label>
              <input class="input" inputmode="decimal" v-model="form.cost_price" placeholder="₱0.00" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-forest-muted">Selling price</label>
              <input class="input" inputmode="decimal" v-model="form.selling_price" placeholder="₱0.00" />
            </div>
          </div>

          <BranchSelect v-model="form.branch_id" />

          <div class="grid grid-cols-2 gap-3">
            <div v-if="!editing">
              <label class="mb-1 block text-xs font-semibold text-forest-muted">Quantity</label>
              <input class="input" inputmode="numeric" v-model="form.quantity" placeholder="0" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-forest-muted">Low stock threshold</label>
              <input class="input" inputmode="numeric" v-model="form.low_stock_threshold" />
            </div>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold text-forest-muted">Expiration date (optional)</label>
            <input class="input" type="date" v-model="form.expiration_date" />
          </div>

          <button class="h-12 w-full rounded-xl bg-forest font-semibold text-white active:scale-[0.98] disabled:opacity-40" :disabled="savingProduct" @click="saveProduct">
            {{ editing ? 'Save changes' : 'I-add ang product' }}
          </button>
        </div>
      </div>
    </Teleport>

    <!-- Restock bottom sheet -->
    <Sheet :open="showRestock" :title="'I-restock: ' + (restockTarget?.name || '')" :action-label="savingRestock ? 'Saving…' : 'I-save ang restock'" :action-disabled="savingRestock" @close="showRestock = false" @submit="saveRestock">
      <p class="mb-3 text-sm font-semibold text-forest-dark">{{ restockTarget?.name }}</p>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-xs font-semibold text-forest-muted">Quantity added</label>
          <input class="input" inputmode="numeric" v-model="restockForm.quantity" placeholder="0" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-semibold text-forest-muted">Bagong cost price</label>
          <input class="input" inputmode="decimal" v-model="restockForm.cost_price" placeholder="₱0.00" />
        </div>
      </div>
      <div class="mt-3">
        <label class="mb-1 block text-xs font-semibold text-forest-muted">Supplier (optional)</label>
        <input class="input" v-model="restockForm.supplier" placeholder="Hal. Divisoria supplier" />
      </div>
      <div class="mt-3 rounded-xl bg-forest/5 p-3 text-xs font-medium text-forest-muted">
        New stock level: <b class="text-forest-dark">{{ newStock }} {{ restockTarget?.unit }}</b>
        · Gastos sa P&L: <b class="text-forest-dark">{{ peso(Number(restockForm.quantity || 0) * Number(restockForm.cost_price || 0)) }}</b>
      </div>
    </Sheet>
  </AppLayout>
</template>
