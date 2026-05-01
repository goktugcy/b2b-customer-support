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
    :class="cn('block h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20', props.class)"
  >
    <slot />
  </select>
</template>
