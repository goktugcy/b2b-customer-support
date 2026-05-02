<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  tone?: 'neutral' | 'green' | 'amber' | 'red' | 'blue'
  class?: string
}>(), {
  tone: 'neutral',
})

const toneClass = computed(() => ({
  neutral: 'border-border bg-secondary/80 text-secondary-foreground',
  green: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300',
  amber: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300',
  red: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/70 dark:bg-red-950/40 dark:text-red-300',
  blue: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-sky-900/70 dark:bg-sky-950/40 dark:text-sky-300',
}[props.tone]))
</script>

<template>
  <span :class="cn('inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium transition-colors', toneClass, props.class)">
    <slot />
  </span>
</template>
