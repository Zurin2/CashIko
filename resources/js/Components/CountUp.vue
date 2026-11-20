<script setup>
/**
 * Animated number that counts up when scrolled into view (rAF, zero deps).
 * Usage: <CountUp :to="2415.5" :decimals="2" prefix="₱" />
 */
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  to: { type: Number, required: true },
  duration: { type: Number, default: 1400 },
  decimals: { type: Number, default: 0 },
  delay: { type: Number, default: 0 },
  prefix: { type: String, default: '' },
  suffix: { type: String, default: '' },
})

const el = ref(null)
const value = ref(0)
let observer = null
let played = false

const play = () => {
  if (played) return
  played = true
  const t0 = performance.now() + props.delay
  const tick = (t) => {
    const p = Math.min(Math.max((t - t0) / props.duration, 0), 1)
    const eased = 1 - Math.pow(1 - p, 3) // easeOutCubic
    value.value = Number((props.to * eased).toFixed(props.decimals))
    if (p < 1) requestAnimationFrame(tick)
  }
  requestAnimationFrame(tick)
}

onMounted(() => {
  observer = new IntersectionObserver((entries) => {
    if (entries.some((e) => e.isIntersecting)) {
      play()
      observer.disconnect()
    }
  }, { threshold: 0.4 })
  if (el.value) observer.observe(el.value)
})

onBeforeUnmount(() => observer?.disconnect())

const fmt = (n) =>
  n.toLocaleString('en-PH', {
    minimumFractionDigits: props.decimals,
    maximumFractionDigits: props.decimals,
  })
</script>

<template>
  <span ref="el" class="tabular-nums">{{ prefix }}{{ fmt(value) }}{{ suffix }}</span>
</template>
