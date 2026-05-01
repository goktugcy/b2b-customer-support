<script setup lang="ts">
import { cn } from '@/lib/utils'

const model = defineModel<string | null>()
const props = defineProps<{
  placeholder?: string
  rows?: number
  class?: string
  required?: boolean
}>()
</script>

<template>
  <textarea
    v-model="model"
    :rows="rows ?? 4"
    :placeholder="placeholder"
    :required="required"
    :class="cn('flex min-h-[96px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50', props.class)"
  />
</template>
