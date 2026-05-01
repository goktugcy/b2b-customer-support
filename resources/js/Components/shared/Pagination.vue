<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
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
      :class="[
        'rounded-md border px-3 py-1.5 text-sm',
        link.active ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-200 bg-white text-slate-700',
        !link.url && 'pointer-events-none opacity-40',
      ]"
      v-html="link.label"
    />
  </nav>
</template>
