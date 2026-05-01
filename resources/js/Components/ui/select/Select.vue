<script setup lang="ts">
import { cn } from '@/lib/utils'

const model = defineModel<string | number | null>()
const props = defineProps<{
  class?: string
  required?: boolean
}>()
</script>

<template>
  <select
    v-model="model"
    :required="required"
    :class="cn('flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50', props.class)"
  >
    <slot />
  </select>
</template>
