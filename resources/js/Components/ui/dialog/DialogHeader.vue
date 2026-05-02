<script setup lang="ts">
import { cn } from '@/lib/utils'

defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('space-y-1.5 text-left', $props.class)">
    <slot />
  </div>
</template>
