<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('mt-auto flex flex-col-reverse gap-2 border-t px-5 py-4 sm:flex-row sm:justify-end', props.class)">
    <slot />
  </div>
</template>
