<script setup>
/** Gastos (§8.8). */
import { computed, onMounted, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Plus, ReceiptText, Trash2 } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Sheet from '@/Components/Sheet.vue'
import Toaster from '@/Components/Toaster.vue'
import { peso, friendlyDate, todayISO } from '@/lib/format'

const props = defineProps({ expenses: Array, total: Number, categories: Array, filters: Object })

const branchParam = ref(new URLSearchParams(window.location.search).get('branch') || 'all')
const ranges = [{ key: 'today', label: 'Today' }, { key: 'week', label: 'This week' }, { key: 'month', label: 'This month' }, { key: 'all', label: 'All' }]
const chips = computed(() => ['all', ...(props.categories || [])])

const reload = (range) =>
  router.get(route('expenses.index'), {
    range: range === 'month' ? undefined : range,
    category: props.filters.category !== 'all' ? props.filters.category : undefined,
    branch: branchParam.value,
  }, { preserveState: true, only: ['expenses', 'total', 'filters'] })

const setCategory = (c) =>
  router.get(route('expenses.index'), {
    range: props.filters.range !== 'month' ? props.filters.range : undefined,
    category: c === 'all' ? undefined : c,
    branch: branchParam.value,
  }, { preserveState: true, only: ['expenses', 'total', 'filters'] })

/* Add sheet */
const open = ref(false)
const form = ref({ amount: '', category: 'Restock/Supplies', custom: '', date: todayISO(), note: '' })
const saving = ref(false)

const save = () => {
  if (!Number(form.value.amount)) return
  saving.value = true
  router.post(route('expenses.store'), {
    amount: Number(form.value.amount),
    category: form.value.category,
    custom_category: form.value.category === 'Other' && form.value.custom.trim() ? form.value.custom.trim() : null,
    date: form.value.date,
    note: form.value.note || null,
    branch: branchParam.value !== 'all' ? branchParam.value : null,
  }, {
    onSuccess: () => { open.value = false; saving.value = false },
    onError: () => (saving.value = false),
  })
}

const remove = (e) => {
  if (confirm('Delete this expense?')) router.delete(route('expenses.destroy', e.id), { only: ['expenses', 'total'] })
}

const label = (e) => e.custom_category || e.category

onMounted(() => {
  if (new URLSearchParams(window.location.search).has('new')) open.value = true
})
</script>

<template>
  <Head title="Gastos (Expenses)" />
  <AppLayout>
    <Toaster />
    <div class="md:max-w-3xl mx-auto">
      <header class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-forest-dark">Gastos (Expenses)</h1>
        <button class="flex h-10 items-center gap-1 rounded-xl bg-forest px-4 text-sm font-semibold text-white active:scale-[0.98]" @click="open = true">
          <Plus class="h-4 w-4" /> Gastos
        </button>
      </header>

      <div class="mb-3 rounded-2xl border border-warm bg-white px-4 py-3 shadow-card">
        <div class="text-[11px] font-semibold uppercase tracking-wide text-forest-muted">Total (current filter)</div>
        <div class="text-peso text-2xl text-forest-dark">{{ peso(props.total) }}</div>
      </div>

      <div class="no-scrollbar mb-3 flex gap-2 overflow-x-auto">
        <button v-for="r in ranges" :key="r.key" class="chip" :class="props.filters.range === r.key ? 'chip-active' : 'chip-idle'" @click="reload(r.key)">{{ r.label }}</button>
      </div>
      <div class="no-scrollbar mb-3 flex gap-2 overflow-x-auto">
        <button v-for="c in chips" :key="c" class="chip" :class="props.filters.category === c ? 'chip-active' : 'chip-idle'" @click="setCategory(c)">{{ c === 'all' ? 'All' : c }}</button>
      </div>

      <div v-if="props.expenses.length" class="divide-y divide-warm overflow-hidden rounded-2xl border border-warm bg-white shadow-card">
        <div v-for="e in props.expenses" :key="e.id" class="flex items-center justify-between gap-3 px-4 py-3">
          <div class="min-w-0">
            <div class="truncate text-sm font-bold text-forest-dark">{{ label(e) }}</div>
            <div class="text-[11px] text-forest-muted">{{ friendlyDate(e.date) }} · {{ e.category }}</div>
            <div v-if="e.note" class="truncate text-[11px] italic text-stone-500">{{ e.note }}</div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-peso text-sm text-red-600">−{{ peso(e.amount) }}</span>
            <button class="tap-target grid place-items-center text-stone-300 hover:text-red-600" @click="remove(e)" aria-label="Delete"><Trash2 class="h-4 w-4" /></button>
          </div>
        </div>
      </div>
      <EmptyState v-else :icon="ReceiptText" title="Wala pang gastos" subtitle="I-log ang gastos para tama ang kita."> </EmptyState>
    </div>

    <Sheet :open="open" title="Bagong Gastos" :action-label="saving ? 'Saving…' : 'I-save ang gastos'" :action-disabled="saving" @close="open = false" @submit="save">
      <label class="mb-1 block text-xs font-semibold text-forest-muted">Halaga</label>
      <input class="input mb-3" inputmode="decimal" v-model="form.amount" placeholder="₱0.00" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">Category</label>
      <select class="input mb-3" v-model="form.category">
        <option v-for="c in props.categories" :key="c" :value="c">{{ c }}</option>
      </select>
      <input v-if="form.category === 'Other'" class="input mb-3" v-model="form.custom" placeholder="I-type ang uri ng gastos" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">Date</label>
      <input class="input mb-3" type="date" v-model="form.date" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">Note (optional)</label>
      <input class="input" v-model="form.note" placeholder="Hal. half-zone electricity bill" />
    </Sheet>
  </AppLayout>
</template>
