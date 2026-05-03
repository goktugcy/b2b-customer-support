<script setup lang="ts">
import { DialogTitle } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <DialogTitle :class="cn('text-base font-semibold tracking-normal', props.class)">
    <slot />
  </DialogTitle>
</template>
