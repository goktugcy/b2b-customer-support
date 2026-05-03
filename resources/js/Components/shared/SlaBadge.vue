<script setup lang="ts">
import Badge from '@/Components/ui/badge/Badge.vue'

defineProps<{ value?: string | null }>()
</script>

<template>
  <Badge v-if="value === 'breached'" tone="red">SLA breached</Badge>
  <Badge v-else-if="value === 'due_soon'" tone="amber">Due soon</Badge>
  <Badge v-else-if="value" tone="blue">{{ value.replaceAll('_', ' ') }}</Badge>
</template>
