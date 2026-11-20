<script setup>
/** Onboarding — 5-step wizard (§8.2). */
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight, Plus, X } from 'lucide-vue-next'
import { PH_CITIES, PH_RURAL } from '@/lib/locations'

const TYPES = ['Sari-sari store', 'Coffee shop', 'Carinderia/Eatery', 'Food stall', 'Clothing/Apparel', 'Other (type it in)']
const CUSTOM = 'Other (type it in)'

const step = ref(1)
const customType = ref('')
const useCustom = ref(false)
const multiBranch = ref(false)
const branchName = ref([''])

const form = useForm({
  name: '',
  type: '',
  location: '',
  multiBranch: false,
  extraBranches: [],
})

const canContinue = computed(() => {
  if (step.value === 1) return form.name.trim().length > 0
  if (step.value === 2) return form.type.trim().length > 0
  if (step.value === 3) return form.location.length > 0
  if (step.value === 4) return !multiBranch.value || branchName.value.every((n) => n.trim().length > 0)
  return true
})

const next = () => {
  if (!canContinue.value) return
  step.value++
}

const prev = () => (step.value = Math.max(1, step.value - 1))

const onChooseType = (t) => {
  if (t === CUSTOM) {
    useCustom.value = true
    form.type = customType.value
  } else {
    useCustom.value = false
    customType.value = ''
    form.type = t
  }
}

const onCustomType = () => (form.type = customType.value)

const addBranchName = () => branchName.value.push('')

const removeBranchName = (i) => branchName.value.splice(i, 1)

const finish = () => {
  form.multiBranch = multiBranch.value
  form.extraBranches = multiBranch.value ? branchName.value.filter((n) => n.trim()) : []
  form.post(route('onboarding.store'), {
    onSuccess: () => window.location.assign('/home'),
  })
}
</script>

<template>
  <Head title="Onboarding" />
  <div class="mx-auto min-h-screen flex max-w-[460px] flex-col px-5 py-8">
    <div class="mb-6">
      <div class="mb-3 flex items-center gap-2">
        <img src="/images/cashiko-icon.svg" alt="CashIko" class="h-8 w-auto" draggable="false" />
        <div class="text-lg font-extrabold text-forest">CashIko</div>
      </div>
      <div class="h-1.5 overflow-hidden rounded-full bg-cream">
        <div class="h-full rounded-full bg-forest transition-all duration-300" :style="{ width: (step / 5) * 100 + '%' }" />
      </div>
    </div>

    <div class="flex-1 space-y-5">
      <template v-if="step === 1">
        <h1 class="text-2xl font-extrabold text-forest-dark">What is your business called?</h1>
        <input class="input text-lg" autofocus placeholder="e.g. Nena's Store" v-model="form.name" @keydown.enter="next()" />
        <p v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</p>
      </template>

      <template v-else-if="step === 2">
        <h1 class="text-2xl font-extrabold text-forest-dark">What kind of business is it?</h1>
        <div class="space-y-2">
          <button
            v-for="t in TYPES"
            :key="t"
            class="tap-target w-full rounded-xl border px-4 text-left font-medium transition-colors"
            :class="(useCustom && t === CUSTOM) || (!useCustom && form.type === t) ? 'border-forest bg-forest/5 text-forest-dark' : 'border-warm bg-white text-forest-dark hover:border-forest/50'"
            @click="onChooseType(t)"
          >
            {{ t }}
            <span v-if="t === CUSTOM" class="ml-2 text-xs text-forest-muted">type it in if it is not listed</span>
          </button>
          <input v-if="useCustom" class="input" placeholder="e.g. Noodle stall, Milk tea shop, Water refilling" v-model="customType" @input="onCustomType" @keydown.enter="next()" />
        </div>
      </template>

      <template v-else-if="step === 3">
        <h1 class="text-2xl font-extrabold text-forest-dark">Where is it located?</h1>
        <select class="input" v-model="form.location">
          <option value="" disabled>Select city or municipality</option>
          <optgroup v-for="g in PH_CITIES" :key="g.group" :label="g.group">
            <option v-for="c in g.options" :key="c" :value="c">{{ c }}</option>
          </optgroup>
          <optgroup label="Municipalities">
            <option v-for="r in PH_RURAL" :key="r" :value="r">{{ r }}</option>
          </optgroup>
        </select>
      </template>

      <template v-else-if="step === 4">
        <h1 class="text-2xl font-extrabold text-forest-dark">Do you have more branches or stalls?</h1>
        <div class="grid grid-cols-2 gap-3">
          <button class="tap-target rounded-xl border border-warm bg-white font-semibold text-forest-dark hover:border-forest/60" :class="!multiBranch ? 'border-forest' : ''" @click="multiBranch = false">No</button>
          <button class="tap-target rounded-xl border border-warm bg-white font-semibold text-forest-dark hover:border-forest/60" :class="multiBranch ? 'border-forest' : ''" @click="multiBranch = true">Yes</button>
        </div>
        <div v-if="multiBranch" class="space-y-2 rounded-2xl border border-warm bg-white p-4">
          <p class="text-sm font-semibold text-forest-dark">Name each branch</p>
          <div v-for="(name, i) in branchName" :key="i" class="flex items-center gap-2">
            <input class="input" :placeholder="'e.g. Stall ' + (i + 1) + ' - Market'" v-model="branchName[i]" />
            <button v-if="branchName.length > 1" class="tap-target grid place-items-center text-stone-400 hover:text-red-600" @click="removeBranchName(i)" aria-label="remove"><X class="h-4 w-4" /></button>
          </div>
          <button v-if="branchName.length < 5" class="flex items-center gap-1 pt-1 text-sm font-semibold text-forest hover:underline" @click="addBranchName"><Plus class="h-4 w-4" />Add another</button>
        </div>
      </template>

      <template v-else>
        <h1 class="text-2xl font-extrabold text-forest-dark">You're all set!</h1>
        <div class="rounded-2xl border border-warm bg-white p-4 shadow-card">
          <div class="text-xs font-semibold uppercase tracking-widest text-forest-muted">Summary</div>
          <div class="mt-1 font-bold text-forest-dark">{{ form.name || '——' }}</div>
          <div class="text-sm text-forest-muted">{{ form.type }} · {{ form.location || '——' }}</div>
          <div class="mt-2 text-xs text-forest-muted">{{ multiBranch ? 'Multi-branch setup' : 'Single branch' }} · 14-day trial</div>
        </div>
        <button class="h-12 w-full rounded-xl bg-forest font-semibold text-white active:scale-[0.98] disabled:opacity-40" :disabled="form.processing" @click="finish">
          {{ form.processing ? 'Setting up your business…' : 'Start my business' }}
        </button>
      </template>
    </div>

    <div class="mt-8 flex justify-between print-hide">
      <button v-if="step > 1" class="tap-target flex items-center gap-1 font-semibold text-forest-muted" @click="prev"><ChevronLeft class="h-4 w-4" />Back</button>
      <span v-else />
      <button
        v-if="step < 5"
        class="tap-target flex items-center gap-1 rounded-xl bg-forest px-5 font-semibold text-white disabled:opacity-40"
        :disabled="!canContinue"
        @click="next"
      >
        Next <ChevronRight class="h-4 w-4" />
      </button>
    </div>
  </div>
</template>
