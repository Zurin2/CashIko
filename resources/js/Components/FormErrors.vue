<script setup>
/** Shared validation-error banner for sheets/forms; errors passed from Inertia onError. */
defineProps({ errors: { type: Object, default: () => ({}) } })
</script>

<template>
  <div v-if="Object.keys(errors).length" class="mb-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2" role="alert">
    <p v-for="(msg, key) in errors" :key="key" class="text-xs font-semibold text-red-700">{{ msg }}</p>
  </div>
</template>
