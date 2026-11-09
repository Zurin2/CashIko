<script setup>
import { computed, ref, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage()
const show = ref(false)
const msg = ref(null)
const kind = ref('success')
let timer = null

watch(
  () => [page.props.flash.success, page.props.flash.error],
  ([success, error]) => {
    if (success) { kind.value = 'success'; msg.value = success }
    if (error) { kind.value = 'error'; msg.value = error }
    if (success || error) {
      show.value = true
      clearTimeout(timer)
      timer = setTimeout(() => (show.value = false), 3800)
    }
  }
)
</script>

<template>
  <Teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-2 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="translate-y-2 opacity-0"
    >
      <div
        v-if="show"
        class="fixed bottom-20 left-1/2 z-[60] w-[min(90vw,420px)] -translate-x-1/2 rounded-2xl px-4 py-3 shadow-cardlg md:bottom-6 print-hide"
        :class="kind === 'success' ? 'bg-forest text-white' : 'bg-red-600 text-white'"
        role="status"
      >
        {{ msg }}
      </div>
    </transition>
  </Teleport>
</template>
