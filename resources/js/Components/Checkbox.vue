<script setup lang="ts">
import { computed } from 'vue'

const emit = defineEmits<{
  'update:checked': [value: boolean | unknown[]]
}>()

const props = defineProps<{
  checked: boolean | unknown[]
  value?: unknown
}>()

const proxyChecked = computed({
  get() {
    return props.checked
  },

  set(val) {
    emit('update:checked', val)
  },
})
</script>

<template>
  <input
    v-model="proxyChecked"
    type="checkbox"
    :value="value"
    class="h-4 w-4 rounded border-input text-primary shadow-sm focus:ring-ring focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50"
  />
</template>
