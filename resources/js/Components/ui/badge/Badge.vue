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
  neutral: 'bg-slate-100 text-slate-700',
  green: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  amber: 'bg-amber-50 text-amber-800 ring-amber-200',
  red: 'bg-rose-50 text-rose-700 ring-rose-200',
  blue: 'bg-sky-50 text-sky-700 ring-sky-200',
}[props.tone]))
</script>

<template>
  <span :class="cn('inline-flex items-center rounded px-2 py-0.5 text-xs font-medium ring-1 ring-inset ring-slate-200', toneClass, props.class)">
    <slot />
  </span>
</template>
