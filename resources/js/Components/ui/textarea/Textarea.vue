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
    :class="cn('block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20', props.class)"
  />
</template>
