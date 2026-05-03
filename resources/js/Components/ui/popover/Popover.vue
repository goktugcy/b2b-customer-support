<script setup lang="ts">
import { PopoverRoot } from 'reka-ui'

const props = defineProps<{ open?: boolean }>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()
</script>

<template>
  <PopoverRoot v-if="props.open !== undefined" :open="props.open" @update:open="emit('update:open', $event)">
    <slot />
  </PopoverRoot>
  <PopoverRoot v-else @update:open="emit('update:open', $event)">
    <slot />
  </PopoverRoot>
</template>
