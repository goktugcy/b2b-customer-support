<script setup lang="ts">
import { TabsList } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <TabsList :class="cn('inline-flex h-10 items-center gap-1 rounded-md border bg-card p-1 text-muted-foreground shadow-sm', props.class)">
    <slot />
  </TabsList>
</template>
