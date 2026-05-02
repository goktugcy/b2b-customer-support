<script setup lang="ts">
import { cn } from '@/lib/utils'

defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('rounded-lg border bg-card/95 text-card-foreground shadow-panel backdrop-blur-sm dark:shadow-none', $props.class)">
    <slot />
  </div>
</template>
