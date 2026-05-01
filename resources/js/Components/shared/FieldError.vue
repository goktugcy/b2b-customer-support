<script setup lang="ts">
defineProps<{ message?: string }>()
</script>

<template>
  <p v-if="message" class="mt-1.5 text-sm text-destructive">{{ message }}</p>
</template>
