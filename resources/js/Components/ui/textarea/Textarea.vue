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
    :class="cn('flex min-h-[96px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground hover:border-ring/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/70 focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50', props.class)"
  />
</template>
