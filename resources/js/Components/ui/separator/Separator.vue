<script setup lang="ts">
import { cn } from '@/lib/utils'

defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('h-px w-full bg-slate-200', $props.class)" />
</template>
