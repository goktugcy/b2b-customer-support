<script setup lang="ts">
import Input from '@/Components/ui/input/Input.vue'

const model = defineModel<string | number | null>()
</script>

<template>
  <Input v-model="model" />
</template>
