<script setup lang="ts">
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <div class="rounded-md border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
    <p class="text-sm font-medium text-slate-900">{{ title }}</p>
    <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
  </div>
</template>
