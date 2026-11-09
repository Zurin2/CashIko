<script setup>
/** Branches (§8.11): premium leaderboard + branch CRUD (Main undeletable). */
import { computed, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Plus, Crown, Pencil, Building2, Trash2 } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Sheet from '@/Components/Sheet.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import Toaster from '@/Components/Toaster.vue'
import { peso } from '@/lib/format'
import { PH_LOCATIONS } from '@/lib/locations'

const props = defineProps({ branches: Array })

const isPremium = computed(() => usePage().props.business?.plan === 'premium')

const active = computed(() => props.branches.filter((b) => b.is_active) || [])
const sorting = computed(() => [...active.value].sort((a, b) => b.stats.sales - a.stats.sales))
const top = computed(() => sorting.value[0])

const chartData = computed(() => sorting.value.map((b) => ({ label: b.name, sales: b.stats.sales })))

/* form */
const showForm = ref(false)
const editing = ref(null)
const form = ref({ name: '', house_number: '', street: '', barangay: '', city: '', is_active: true })
const cityOpen = ref(false)
const saving = ref(false)

const openForm = (b = null) => {
  editing.value = b
  form.value = b
    ? { name: b.name, house_number: b.house_number || '', street: b.street || '', barangay: b.barangay || '', city: b.city || '', is_active: b.is_active }
    : { name: '', house_number: '', street: '', barangay: '', city: '', is_active: true }
  showForm.value = true
}

const save = () => {
  if (!editing.value && !form.value.name.trim()) return
  saving.value = true
  const url = editing.value ? route('branches.update', editing.value.id) : route('branches.store')
  router.post(url, { ...form.value, is_active: form.value.is_active ? '1' : '0' }, {
    onSuccess: () => { showForm.value = false; saving.value = false },
    onError: () => (saving.value = false),
  })
}

const remove = (b) => {
  if (!confirm(`Deactivate ${b.name}? Its historical records are never touched.`)) return
  router.delete(route('branches.destroy', b.id), { only: ['branches'] })
}

const toggleActive = (b) =>
  router.post(route('branches.update', b.id), { name: b.name, house_number: b.house_number || null, street: b.street || null, barangay: b.barangay || null, city: b.city || null, is_active: !b.is_active }, { only: ['branches'] })
</script>

<template>
  <Head title="Branches" />
  <AppLayout>
    <Toaster />
    <div class="md:max-w-3xl mx-auto">
      <header class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-forest-dark">Branches</h1>
        <button v-if="isPremium" class="flex h-10 items-center gap-1 rounded-xl bg-forest px-4 text-sm font-semibold text-white active:scale-[0.98]" @click="openForm()">
          <Plus class="h-4 w-4" /> Branch
        </button>
      </header>

      <div v-if="!isPremium" class="mt-6">
        <div class="rounded-2xl border border-warm bg-white p-6 text-center shadow-card">
          <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-mango/15 text-mango"><Crown class="h-6 w-6" /></span>
          <h2 class="mt-3 font-bold text-forest-dark">Premium feature</h2>
          <p class="mt-1 text-sm text-forest-muted">Upgrade to Premium to track multiple branches in real time.</p>
          <button class="mt-4 h-12 rounded-xl bg-forest px-6 font-semibold text-white" @click="router.visit(route('pricing.index'))">See plans</button>
        </div>
      </div>

      <template v-else>
        <section v-if="top" class="mb-3 rounded-2xl bg-gradient-to-br from-forest to-[hsl(150_30%_18%)] px-5 py-6 text-white shadow-cardlg">
          <div class="text-xs font-semibold opacity-80">Top branch today</div>
          <div class="text-2xl font-extrabold">{{ top.name }}</div>
          <div class="mt-1 text-sm opacity-90">{{ peso(top.stats.sales) }} · {{ top.stats.txns }} transactions</div>
        </section>

        <section class="mb-3 rounded-2xl border border-warm bg-white p-4 shadow-card">
          <h2 class="mb-2 text-sm font-bold text-forest-dark">Today's Sales by Branch</h2>
          <BarChart :data="chartData" />
        </section>

        <div class="space-y-3">
          <article v-for="b in props.branches" :key="b.id" class="rounded-2xl border border-warm bg-white p-4 shadow-card" :class="!b.is_active ? 'opacity-60' : ''">
            <div class="flex items-center justify-between gap-2">
              <div>
                <div class="flex items-center gap-2">
                  <h2 class="min-w-0 text-base font-bold text-forest-dark">{{ b.name }}</h2>
                  <span v-if="!b.is_active" class="rounded-full bg-stone-200 px-2 py-0.5 text-[9px] font-bold uppercase text-stone-500">Inactive</span>
                </div>
                <p class="text-[11px] text-forest-muted">{{ b.location || 'Walang address details' }}</p>
              </div>
              <div class="flex items-center gap-1.5">
                <button class="tap-target grid place-items-center text-forest-muted hover:text-forest" @click="openForm(b)" aria-label="Edit"><Pencil class="h-4 w-4" /></button>
                <button v-if="b.name !== 'Main'" class="tap-target grid place-items-center text-stone-300 hover:text-red-600" @click="remove(b)" aria-label="Deactivate"><Trash2 class="h-4 w-4" /></button>
              </div>
            </div>
            <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
              <div class="rounded-xl bg-cream py-2"><div class="font-bold text-forest-dark">{{ peso(b.stats.sales) }}</div><div class="text-stone-500">Benta</div></div>
              <div class="rounded-xl bg-cream py-2"><div class="font-bold text-forest-dark">{{ b.stats.txns }}</div><div class="text-stone-500">Txns</div></div>
              <div class="rounded-xl bg-cream py-2"><div class="font-bold text-forest-dark">{{ peso(b.stats.profit) }}</div><div class="text-stone-500">Kita</div></div>
              <div class="rounded-xl bg-cream py-2"><div class="font-bold" :class="b.stats.low ? 'text-red-600' : 'text-forest-dark'">{{ b.stats.low }}</div><div class="text-stone-500">Low</div></div>
            </div>
            <button class="mt-2 text-[11px] font-semibold text-forest underline-offset-2 hover:underline" @click="toggleActive(b)">
              {{ b.is_active ? 'Deactivate' : 'Reactivate' }}
            </button>
          </article>
        </div>
      </template>
    </div>

    <Sheet :open="showForm" :title="editing ? 'Edit Branch' : 'Bagong Branch'" :action-label="saving ? 'Saving…' : 'Save'" :action-disabled="saving" @close="showForm = false" @submit="save">
      <label class="mb-1 block text-xs font-semibold text-forest-muted">Branch name</label>
      <input class="input mb-3" v-model="form.name" :disabled="editing?.name === 'Main'" placeholder="Hal. Stall 2 - Palengke" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">House / Building no.</label>
      <input class="input mb-3" v-model="form.house_number" placeholder="Hal. Blk 12 Lot 7" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">Street</label>
      <input class="input mb-3" v-model="form.street" placeholder="Hal. Rizal Avenue" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">Barangay</label>
      <input class="input mb-3" v-model="form.barangay" placeholder="Hal. Brgy. San Isidro" />
      <label class="mb-1 block text-xs font-semibold text-forest-muted">City / Municipality</label>
      <select class="input mb-3" v-model="form.city">
        <option :value="''" disabled>Piliin ang lungsod</option>
        <option v-for="c in PH_LOCATIONS" :key="c" :value="c" :selected="c === editing?.city || c === form.city">{{ c }}</option>
      </select>
      <label class="mb-1 block text-xs font-semibold text-forest-muted">Status</label>
      <div class="mb-3 grid grid-cols-2 gap-2">
        <button type="button" class="chip justify-center py-2" :class="form.is_active ? 'chip-active' : 'chip-idle'" @click="form.is_active = true">Active</button>
        <button type="button" class="chip justify-center py-2" :class="!form.is_active ? 'chip-active' : 'chip-idle'" @click="form.is_active = false">Inactive</button>
      </div>
      <p class="text-[11px] text-forest-muted">Historical records stay intact even if this branch goes inactive.</p>
    </Sheet>
  </AppLayout>
</template>
