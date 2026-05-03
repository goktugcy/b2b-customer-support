<script setup lang="ts">
import { PopoverContent, PopoverPortal } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  align?: 'start' | 'center' | 'end'
  sideOffset?: number
  class?: string
}>(), {
  align: 'center',
  sideOffset: 8,
})
</script>

<template>
  <PopoverPortal>
    <PopoverContent
      :align="align"
      :side-offset="sideOffset"
      :class="cn('z-50 w-72 rounded-md border bg-popover p-4 text-popover-foreground shadow-xl outline-none', props.class)"
    >
      <slot />
    </PopoverContent>
  </PopoverPortal>
</template>
