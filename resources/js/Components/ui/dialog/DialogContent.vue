<script setup lang="ts">
import { DialogContent, DialogOverlay, DialogPortal } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <DialogPortal>
    <DialogOverlay class="fixed inset-0 z-50 bg-foreground/45 backdrop-blur-sm data-[state=closed]:animate-out data-[state=open]:animate-in" />
    <DialogContent
      :class="cn(
        'fixed left-1/2 top-1/2 z-50 grid max-h-[calc(100vh-2rem)] w-[calc(100vw-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 overflow-y-auto rounded-lg border bg-card p-6 text-card-foreground shadow-2xl outline-none',
        props.class,
      )"
    >
      <slot />
    </DialogContent>
  </DialogPortal>
</template>
