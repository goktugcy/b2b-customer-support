<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps<{
  href: string
  active?: boolean
}>()

const classes = computed(() =>
  props.active
    ? 'block w-full border-l-4 border-primary bg-secondary py-2 pe-4 ps-3 text-start text-base font-medium text-foreground transition-colors'
    : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-muted-foreground transition-colors hover:border-border hover:bg-secondary hover:text-foreground',
)
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
