<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  variant?: 'default' | 'success' | 'destructive' | 'warning' | 'info'
  class?: string
}>(), {
  variant: 'default',
})

const variants = computed(() => ({
  default: 'border-border bg-card text-card-foreground',
  success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
  destructive: 'border-red-200 bg-red-50 text-red-900',
  warning: 'border-amber-200 bg-amber-50 text-amber-900',
  info: 'border-blue-200 bg-blue-50 text-blue-900',
}[props.variant]))
</script>

<template>
  <div :class="cn('relative w-full rounded-lg border px-4 py-3 text-sm', variants, props.class)" role="alert">
    <slot />
  </div>
</template>
