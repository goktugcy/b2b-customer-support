<script setup lang="ts">
defineProps<{
  title: string
  description?: string
  eyebrow?: string
}>()
</script>

<template>
  <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
    <div class="min-w-0">
      <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ eyebrow }}</p>
      <h2 class="truncate text-2xl font-semibold tracking-normal text-foreground">{{ title }}</h2>
      <p v-if="description" class="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground">{{ description }}</p>
      <div v-if="$slots.meta" class="mt-3">
        <slot name="meta" />
      </div>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
