<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

defineProps<{ href: string }>()
</script>

<template>
  <Link
    :href="href"
    class="block w-full px-4 py-2 text-start text-sm leading-5 text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground focus:bg-secondary focus:outline-none"
  >
    <slot />
  </Link>
</template>
