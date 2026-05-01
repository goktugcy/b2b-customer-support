<script setup lang="ts">
import { ref } from 'vue'
import { cn } from '@/lib/utils'

const model = defineModel<string | number | null>()
const input = ref<HTMLInputElement | null>(null)
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

defineExpose({
  focus: () => input.value?.focus(),
})
</script>

<template>
  <input
    ref="input"
    v-model="model"
    :type="type"
    :placeholder="placeholder"
    :required="required"
    :autofocus="autofocus"
    :autocomplete="autocomplete"
    :class="cn('flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50', props.class)"
  />
</template>
