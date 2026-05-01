<script setup lang="ts">
import { Inbox } from 'lucide-vue-next'

defineProps<{ title: string; description?: string }>()
</script>

<template>
  <div class="rounded-lg border border-dashed bg-muted/30 px-6 py-10 text-center">
    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-md border bg-background text-muted-foreground">
      <Inbox class="h-5 w-5" />
    </div>
    <p class="mt-3 text-sm font-medium text-foreground">{{ title }}</p>
    <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
  </div>
</template>
