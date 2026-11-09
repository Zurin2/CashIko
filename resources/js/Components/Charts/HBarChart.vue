<script setup>
/** Horizontal category bars (§8.9 Expenses by Category). */
import { computed } from 'vue'

const props = defineProps({
  data: { type: Object, required: true },
})

const rows = computed(() => {
  const total = Object.values(props.data).reduce((a, b) => a + b, 0) || 1
  const max = Math.max(...Object.values(props.data), 1)
  return Object.entries(props.data)
    .sort((a, b) => b[1] - a[1])
    .map(([k, v]) => ({ label: k, value: v, pct: (v / max) * 100, share: Math.round((v / total) * 100) }))
})

const fmt = (n) => Number(n).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
</script>

<template>
  <div class="space-y-2.5">
    <div v-for="r in rows" :key="r.label" class="space-y-1">
      <div class="flex items-baseline justify-between gap-2 text-[13px]">
        <span class="font-semibold text-forest-dark">{{ r.label }}</span>
        <span class="tabular font-bold text-forest-dark">{{ fmt(r.value) }} <span class="font-medium text-stone-400">· {{ r.share }}%</span></span>
      </div>
      <div class="h-2 overflow-hidden rounded-full bg-cream">
        <div class="h-full rounded-full bg-mango" :style="{ width: r.pct + '%' }" />
      </div>
    </div>
    <p v-if="!rows.length" class="text-sm text-forest-muted">Walang gastos sa panahong ito.</p>
  </div>
</template>
