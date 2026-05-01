<script setup lang="ts">
import { cn } from '@/lib/utils'

defineProps<{ class?: string }>()
</script>

<template>
  <h5 :class="cn('mb-1 font-medium leading-none tracking-normal', $props.class)">
    <slot />
  </h5>
</template>
