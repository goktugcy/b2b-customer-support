<script setup lang="ts">
import Badge from '@/Components/ui/badge/Badge.vue'

const props = defineProps<{
  status?: string | null
  label?: string | null
}>()

const toneFor = (status?: string | null) => {
  if (!status) return 'neutral'
  if (['closed', 'resolved', 'active', 'published', 'completed', 'accepted', 'clean', 'enabled', 'success'].includes(status)) return 'green'
  if (['failed', 'disabled', 'revoked', 'blocked', 'rejected'].includes(status)) return 'red'
  if (['waiting_on_customer', 'pending', 'draft', 'processing', 'queued'].includes(status)) return 'amber'

  return 'blue'
}

const labelFor = (value?: string | null) => (value || 'unknown').replaceAll('_', ' ')
</script>

<template>
  <Badge :tone="toneFor(status)">{{ label || labelFor(status) }}</Badge>
</template>
