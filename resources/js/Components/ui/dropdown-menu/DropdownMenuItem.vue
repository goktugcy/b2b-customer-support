<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: string
  inset?: boolean
}>()
</script>

<template>
  <div :class="cn('relative flex min-h-9 cursor-default select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-secondary hover:text-foreground', inset && 'pl-8', props.class)">
    <slot />
  </div>
</template>
