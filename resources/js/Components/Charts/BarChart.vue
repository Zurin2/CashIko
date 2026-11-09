<script setup>
/** Minimal SVG bar chart — no chart library needed. Bars are forest green. */
import { computed } from 'vue'

const props = defineProps({
  data: { type: Array, required: true }, // [{label, sales}]
})

const max = computed(() => Math.max(...props.data.map((d) => d.sales), 1))

const bars = computed(() =>
  props.data.map((d, i) => ({
    ...d,
    x: i,
    h: Math.max(4, (d.sales / max.value) * 100),
  }))
)

const fmt = (n) => n.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
</script>

<template>
  <div class="flex h-32 items-end gap-1 w-full" aria-hidden="true">
    <div v-for="(b, i) in bars" :key="i" class="group relative flex-1 flex h-full flex-col justify-end">
      <div
        class="w-full rounded-t-md bg-forest/85 transition-colors duration-150 group-hover:bg-forest group-first:rounded-tl-xl"
        :style="{ height: b.h + '%' }"
      />
      <div class="truncate pt-1 text-[9px] leading-tight text-forest-muted" style="writing-mode: horizontal-tb;">
        {{ b.label?.split(' ')[1] ?? b.label }}
      </div>
      <div class="pointer-events-none absolute -top-7 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-lg bg-forest-dark px-2 py-1 text-[10px] font-semibold text-white opacity-0 transition-opacity group-hover:opacity-100">
        {{ b.label }} · {{ fmt(b.sales) }}
      </div>
    </div>
  </div>
</template>
