<script setup>
import { X } from 'lucide-vue-next'

defineProps({
  open: { type: Boolean, required: true },
  title: { type: String, required: true },
  actionLabel: { type: String, default: '' },
  actionDisabled: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'submit'])
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 flex items-end sm:items-center sm:justify-center print-hide">
      <div class="absolute inset-0 bg-black/50" @click="emit('close')" />
      <div class="relative z-10 flex max-h-[92vh] w-full flex-col rounded-t-2xl bg-surface sm:max-w-lg sm:rounded-2xl">
        <header class="flex items-center justify-between border-b border-warm px-4 py-3">
          <h2 class="text-base font-bold text-forest-dark">{{ title }}</h2>
          <button class="tap-target grid place-items-center rounded-lg hover:bg-cream" @click="emit('close')" aria-label="Close">
            <X class="h-5 w-5 text-forest-dark" />
          </button>
        </header>
        <div class="flex-1 overflow-y-auto px-4 py-4">
          <slot />
        </div>
        <footer v-if="actionLabel" class="sticky bottom-0 border-t border-warm bg-white p-3">
          <button
            type="button"
            class="h-12 w-full rounded-xl bg-forest font-semibold text-white transition active:scale-[0.98] disabled:opacity-40"
            :disabled="actionDisabled"
            @click="emit('submit')"
          >
            {{ actionLabel }}
          </button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>
