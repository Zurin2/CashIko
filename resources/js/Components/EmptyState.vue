<script setup>
defineProps({
  icon: { type: [Object, Function], required: true },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})

defineEmits(['cta'])
</script>

<template>
  <div class="grid place-items-center gap-3 rounded-2xl border border-warm bg-white/70 px-6 py-10 text-center">
    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-forest/10 text-forest">
      <component :is="icon" class="h-6 w-6" />
    </div>
    <div class="text-base font-bold text-forest-dark">{{ title }}</div>
    <p v-if="subtitle" class="max-w-xs text-sm text-forest-muted">{{ subtitle }}</p>
    <slot name="cta">
      <slot />
    </slot>
  </div>
</template>
