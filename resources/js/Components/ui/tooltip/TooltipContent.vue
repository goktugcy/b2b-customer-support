<script setup lang="ts">
import { TooltipContent, TooltipPortal } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <TooltipPortal>
    <TooltipContent :class="cn('z-50 rounded-md border bg-popover px-2.5 py-1.5 text-xs text-popover-foreground shadow-lg', props.class)">
      <slot />
    </TooltipContent>
  </TooltipPortal>
</template>
