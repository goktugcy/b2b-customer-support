<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  name?: string | null
  src?: string | null
  class?: string
}>()

const initials = computed(() => (props.name || 'SD')
  .split(' ')
  .filter(Boolean)
  .map((part) => part[0])
  .join('')
  .slice(0, 2)
  .toUpperCase())
</script>

<template>
  <span :class="cn('relative inline-flex h-9 w-9 shrink-0 overflow-hidden rounded-md border bg-secondary text-sm font-semibold text-primary', props.class)">
    <img v-if="src" :src="src" :alt="name || ''" class="h-full w-full object-cover">
    <span v-else class="flex h-full w-full items-center justify-center">{{ initials }}</span>
  </span>
</template>
