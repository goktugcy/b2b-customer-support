<script setup lang="ts">
defineProps<{ open?: boolean }>()
</script>

<template>
  <details class="relative inline-block text-left">
    <slot />
  </details>
</template>
