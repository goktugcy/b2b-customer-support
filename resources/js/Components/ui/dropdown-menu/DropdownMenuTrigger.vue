<script setup lang="ts">
withDefaults(defineProps<{ asChild?: boolean }>(), {
  asChild: true,
})
</script>

<template>
  <summary class="inline-flex list-none [&::-webkit-details-marker]:hidden">
    <slot />
  </summary>
</template>
