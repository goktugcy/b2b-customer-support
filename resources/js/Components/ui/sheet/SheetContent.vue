<script setup lang="ts">
import { DialogContent, DialogOverlay, DialogPortal } from 'reka-ui'
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  side?: 'left' | 'right'
  class?: string
}>(), {
  side: 'right',
})

const sideClass = computed(() => props.side === 'left'
  ? 'inset-y-0 left-0 border-r'
  : 'inset-y-0 right-0 border-l')
</script>

<template>
  <DialogPortal>
    <DialogOverlay class="fixed inset-0 z-50 bg-foreground/45 backdrop-blur-sm" />
    <DialogContent
      :class="cn('fixed z-50 flex h-full w-[min(100vw,420px)] flex-col bg-card text-card-foreground shadow-2xl outline-none', sideClass, props.class)"
    >
      <slot />
    </DialogContent>
  </DialogPortal>
</template>
