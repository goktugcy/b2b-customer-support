<script setup lang="ts">
import { cn } from '@/lib/utils'

const model = defineModel<boolean>({ default: false })
const props = defineProps<{ class?: string }>()
</script>

<template>
  <input
    v-model="model"
    type="checkbox"
    :class="cn('h-4 w-4 rounded border-slate-300 text-teal-700 shadow-sm focus:ring-teal-700', props.class)"
  />
</template>
