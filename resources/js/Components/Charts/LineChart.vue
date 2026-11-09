<script setup>
/** Minimal SVG line chart (Reports §8.9 sales trend). */
import { computed } from 'vue'

const props = defineProps({
  data: { type: Array, required: true }, // [{label, sales}]
})

const W = 280
const H = 90

const pts = computed(() => {
  const max = Math.max(...props.data.map((d) => d.sales), 1)
  return props.data.map((d, i) => ({
    ...d,
    x: (W * i) / Math.max(props.data.length - 1, 1),
    y: H - (d.sales / max) * (H - 10) - 4,
  }))
})

const path = computed(() =>
  pts.value.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ')
)

const area = computed(() =>
  `${path.value} L${W},${H} L0,${H} Z`
)

const fmt = (n) => n.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
</script>

<template>
  <div class="space-y-1">
    <svg :viewBox="`0 0 ${W} ${H}`" class="h-24 w-full" preserveAspectRatio="none" aria-hidden="true">
      <path :d="area" fill="hsl(145 48% 26% / 0.10)" />
      <path :d="path" fill="none" stroke="hsl(145 48% 26%)" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round" />
      <circle
        v-for="(p, i) in pts"
        :key="i"
        :cx="p.x" :cy="p.y" r="2.6"
        fill="hsl(145 48% 26%)"
      />
    </svg>
    <div class="flex justify-between text-[9px] text-forest-muted">
      <span>{{ data[0]?.label }}</span>
      <span>{{ fmt(Math.max(...data.map((d) => d.sales))) }} peak</span>
      <span>{{ data[data.length - 1]?.label }}</span>
    </div>
  </div>
</template>
