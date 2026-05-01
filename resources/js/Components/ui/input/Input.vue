<script setup lang="ts">
import { cn } from '@/lib/utils'

const model = defineModel<string | number | null>()
const props = withDefaults(defineProps<{
  type?: string
  placeholder?: string
  class?: string
  required?: boolean
  autofocus?: boolean
  autocomplete?: string
}>(), {
  type: 'text',
})
</script>

<template>
  <input
    v-model="model"
    :type="type"
    :placeholder="placeholder"
    :required="required"
    :autofocus="autofocus"
    :autocomplete="autocomplete"
    :class="cn('block h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20', props.class)"
  />
</template>
