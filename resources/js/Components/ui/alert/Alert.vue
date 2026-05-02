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
  success: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-200',
  destructive: 'border-red-200 bg-red-50 text-red-900 dark:border-red-900/70 dark:bg-red-950/40 dark:text-red-200',
  warning: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-200',
  info: 'border-blue-200 bg-blue-50 text-blue-900 dark:border-sky-900/70 dark:bg-sky-950/40 dark:text-sky-200',
}[props.variant]))
</script>

<template>
  <div :class="cn('relative w-full rounded-lg border px-4 py-3 text-sm shadow-sm dark:shadow-none', variants, props.class)" role="alert">
    <slot />
  </div>
</template>
