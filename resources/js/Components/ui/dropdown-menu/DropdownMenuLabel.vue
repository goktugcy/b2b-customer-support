<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('px-2 py-1.5 text-xs font-semibold uppercase text-muted-foreground', props.class)">
    <slot />
  </div>
</template>
