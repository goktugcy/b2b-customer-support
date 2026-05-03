<script setup lang="ts">
import { TabsRoot } from 'reka-ui'

defineProps<{
  modelValue?: string
  defaultValue?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <TabsRoot :model-value="modelValue" :default-value="defaultValue" @update:model-value="emit('update:modelValue', $event)">
    <slot />
  </TabsRoot>
</template>
