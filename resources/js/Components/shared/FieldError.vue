<script setup lang="ts">
defineProps<{ message?: string }>()
</script>

<template>
  <p v-if="message" class="mt-1 text-sm text-rose-700">{{ message }}</p>
</template>
