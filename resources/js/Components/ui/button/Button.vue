<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  type?: 'button' | 'submit' | 'reset'
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  disabled?: boolean
  class?: string
}>(), {
  type: 'button',
  variant: 'primary',
  disabled: false,
})

const variants = {
  primary: 'bg-teal-700 text-white hover:bg-teal-800 focus-visible:ring-teal-700',
  secondary: 'border border-slate-300 bg-white text-slate-900 hover:bg-slate-50 focus-visible:ring-slate-500',
  ghost: 'text-slate-700 hover:bg-slate-100 focus-visible:ring-slate-500',
  danger: 'bg-rose-700 text-white hover:bg-rose-800 focus-visible:ring-rose-700',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    :class="cn('inline-flex h-9 items-center justify-center gap-2 rounded-md px-3 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50', variants[variant], props.class)"
  >
    <slot />
  </button>
</template>
