<script setup>
/** Branch picker; hidden for basic plan (spec §9 BranchSelect). */
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { t } from '@/lib/i18n'

const props = defineProps({
  modelValue: { type: [String, Number], default: null },
  label: { type: String, default: 'Branch' },
})

const emit = defineEmits(['update:modelValue'])

const page = usePage()
const isPremium = computed(() => page.props.business?.plan === 'premium')
const branches = computed(() => page.props.branches || [])
</script>

<template>
  <div v-if="isPremium && branches.length" class="block">
    <label class="mb-1 block text-xs font-semibold text-forest-muted">{{ t(label) }}</label>
    <select class="input" :value="modelValue ?? ''" @change="emit('update:modelValue', $event.target.value)">
      <option :value="null">{{ t('Not set (Main)') }}</option>
      <option v-for="b in branches" :key="b.id" :value="b.id">
        {{ b.name }}<template v-if="!b.is_active"> {{ t('(Inactive)') }}</template>
      </option>
    </select>
  </div>
</template>
