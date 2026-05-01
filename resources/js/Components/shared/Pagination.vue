<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'
import type { PaginationLink } from '@/types'

defineProps<{ links: PaginationLink[] }>()
</script>

<template>
  <nav class="flex flex-wrap gap-1">
    <Link
      v-for="link in links"
      :key="link.label"
      :href="link.url ?? ''"
      preserve-scroll
      :class="cn(
        'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm font-medium shadow-sm transition-colors',
        link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-input bg-background text-foreground hover:bg-secondary',
        !link.url && 'pointer-events-none opacity-40',
      )"
      v-html="link.label"
    />
  </nav>
</template>
