<script setup lang="ts">
defineProps<{ title?: string }>()
</script>

<template>
  <div class="min-h-screen bg-slate-100 px-4 py-10 text-slate-950">
    <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-md flex-col justify-center">
      <div class="mb-6">
        <div class="flex h-10 w-10 items-center justify-center rounded-md bg-teal-700 text-sm font-bold text-white">CS</div>
        <h1 class="mt-4 text-2xl font-semibold tracking-normal">{{ title ?? 'Support Desk' }}</h1>
      </div>
      <div class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <slot />
      </div>
    </div>
  </div>
</template>
