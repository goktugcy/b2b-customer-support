<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{
  title?: string
  description?: string
  class?: string
}>()
</script>

<template>
  <section :class="cn('space-y-4', props.class)">
    <div v-if="title || description || $slots.actions" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h3 v-if="title" class="text-base font-semibold tracking-normal text-foreground">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
    <slot />
  </section>
</template>
