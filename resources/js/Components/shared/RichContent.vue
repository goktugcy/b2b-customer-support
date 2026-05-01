<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  html?: string | null
  class?: string
}>(), {
  html: '',
})
</script>

<template>
  <div :class="cn('rich-content text-sm leading-6 text-muted-foreground', props.class)" v-html="html || ''" />
</template>
