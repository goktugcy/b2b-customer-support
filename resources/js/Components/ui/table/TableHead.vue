<script setup lang="ts">
import { cn } from '@/lib/utils'

defineProps<{ class?: string }>()
</script>

<template>
  <th :class="cn('h-11 px-4 text-left align-middle text-xs font-semibold uppercase text-muted-foreground [&:has([role=checkbox])]:pr-0', $props.class)">
    <slot />
  </th>
</template>
