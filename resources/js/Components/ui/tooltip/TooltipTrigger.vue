<script setup lang="ts">
import { TooltipTrigger } from 'reka-ui'

withDefaults(defineProps<{ asChild?: boolean }>(), {
  asChild: true,
})
</script>

<template>
  <TooltipTrigger :as-child="asChild">
    <slot />
  </TooltipTrigger>
</template>
