<script setup lang="ts">
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
</script>

<template>
  <Card>
    <CardContent class="flex flex-col gap-3 p-3 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex min-w-0 flex-1 flex-col gap-3 md:flex-row md:items-center">
        <slot />
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </CardContent>
  </Card>
</template>
