<script setup lang="ts">
import { PopoverTrigger } from 'reka-ui'

withDefaults(defineProps<{ asChild?: boolean }>(), {
  asChild: true,
})
</script>

<template>
  <PopoverTrigger :as-child="asChild">
    <slot />
  </PopoverTrigger>
</template>
