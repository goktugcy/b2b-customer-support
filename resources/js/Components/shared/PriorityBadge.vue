<script setup lang="ts">
import Badge from '@/Components/ui/badge/Badge.vue'

const props = defineProps<{ priority?: string | null }>()

const toneFor = (priority?: string | null) => {
  if (priority === 'urgent') return 'red'
  if (priority === 'high') return 'amber'
  if (priority === 'normal') return 'blue'

  return 'neutral'
}
</script>

<template>
  <Badge :tone="toneFor(props.priority)">{{ props.priority || 'priority' }}</Badge>
</template>
