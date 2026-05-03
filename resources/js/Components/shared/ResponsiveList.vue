<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('overflow-hidden rounded-lg border bg-card shadow-panel dark:shadow-none', props.class)">
    <div class="divide-y">
      <slot />
    </div>
  </div>
</template>
