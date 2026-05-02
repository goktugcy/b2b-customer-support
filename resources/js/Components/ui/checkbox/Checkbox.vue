<script setup lang="ts">
import { cn } from '@/lib/utils'

const model = defineModel<boolean | Array<string | number | boolean>>({ default: false })
const props = defineProps<{ class?: string; value?: string | number | boolean }>()
</script>

<template>
  <input
    v-model="model"
    type="checkbox"
    :value="value"
    :class="cn('h-4 w-4 rounded border-input bg-card text-primary shadow-sm focus:ring-ring/70 focus:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50', props.class)"
  />
</template>
