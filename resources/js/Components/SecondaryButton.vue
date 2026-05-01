<script setup lang="ts">
import Button from '@/Components/ui/button/Button.vue'

withDefaults(defineProps<{
  type?: 'button' | 'submit' | 'reset'
}>(), {
  type: 'button',
})
</script>

<template>
  <Button :type="type" variant="secondary">
    <slot />
  </Button>
</template>
