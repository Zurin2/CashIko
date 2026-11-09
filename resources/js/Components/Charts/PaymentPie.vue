<script setup>
/** Minimal SVG donut/pie for payment split (§8.9 Sales by Payment). */
import { computed } from 'vue'

const props = defineProps({
  data: { type: Object, required: true }, // {cash: 123, gcash: ...}
  colors: { type: Object, default: () => ({}) },
})

const PALETTE = { cash: 'hsl(145 48% 26%)', gcash: 'hsl(199 89% 48%)', maya: 'hsl(258 90% 66%)', utang: 'hsl(26 88% 56%)' }

const entries = computed(() => {
  const total = Object.values(props.data).reduce((a, b) => a + b, 0) || 1
  let angle = 0
  const cx = 50, cy = 50, r = 40

  const arcs = Object.entries(props.data)
    .filter(([, v]) => v > 0)
    .map(([k, v]) => {
      const slice = (v / total) * Math.PI * 2
      const a0 = angle
      angle += slice
      const x0 = cx + r * Math.cos(a0)
      const y0 = cy + r * Math.sin(a0)
      const x1 = cx + r * Math.cos(angle - 1e-4)
      const y1 = cy + r * Math.sin(angle - 1e-4)
      const large = slice > Math.PI ? 1 : 0
      return {
        key: k,
        value: v,
        pct: Math.round((v / total) * 100),
        d: `M${cx},${cy} L${x0},${y0} A${r},${r} 0 ${large} 1 ${x1},${y1} Z`,
        fill: props.colors[k] || PALETTE[k] || 'hsl(145 20% 60%)',
      }
    })

  return arcs
})

const total = computed(() => Object.values(props.data).reduce((a, b) => a + b, 0))

const fmt = (n) => Number(n).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })

const labelOf = (k) =>
  ({ cash: 'Cash', gcash: 'GCash', maya: 'Maya', utang: 'Utang' }[k] || k)
</script>

<template>
  <div class="flex items-center gap-4">
    <svg viewBox="0 0 100 100" class="h-24 w-24 shrink-0" aria-hidden="true">
      <path v-if="!entries.length" d="M50,50 L50,10 A40,40 0 1 1 49.99,10 Z" fill="hsl(44 22% 78%)" />
      <path v-for="a in entries" :key="a.key" :d="a.d" :fill="a.fill" opacity="0.92" />
      <circle cx="50" cy="50" r="17" fill="hsl(44 43% 96.5%)" />
    </svg>
    <div class="min-w-0 flex-1 space-y-1.5 text-sm">
      <div v-for="a in entries" :key="a.key" class="flex items-center justify-between gap-2">
        <span class="flex items-center gap-1.5 font-medium text-forest-dark">
          <span class="h-2.5 w-2.5 rounded-full" :style="{ background: a.fill }" />
          {{ labelOf(a.key) }}
        </span>
        <span class="tabular text-forest-muted">{{ fmt(a.value) }}</span>
      </div>
      <div v-if="!entries.length" class="text-forest-muted">Wala pang benta sa panahong ito.</div>
      <div v-else class="border-t border-warm pt-1.5 font-bold text-forest-dark">{{ fmt(total) }} total</div>
    </div>
  </div>
</template>
