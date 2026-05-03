<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  align?: 'start' | 'center' | 'end'
  side?: 'top' | 'right' | 'bottom' | 'left'
  sideOffset?: number
  class?: string
}>(), {
  align: 'end',
  side: 'bottom',
  sideOffset: 8,
})

const alignClass = computed(() => ({
  start: 'left-0',
  center: 'left-1/2 -translate-x-1/2',
  end: 'right-0',
}[props.align]))
const sideClass = computed(() => props.side === 'top' ? 'bottom-full mb-2' : 'top-full mt-2')
</script>

<template>
  <div :class="cn('absolute z-50 min-w-48 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-xl outline-none', alignClass, sideClass, props.class)">
    <slot />
  </div>
</template>
