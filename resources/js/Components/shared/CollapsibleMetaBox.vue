<script setup lang="ts">
import { ref, watch } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  title: string
  defaultOpen?: boolean
  class?: string
  contentClass?: string
}>(), {
  defaultOpen: true,
})

const open = ref(props.defaultOpen)

watch(() => props.defaultOpen, (value) => {
  open.value = value
})
</script>

<template>
  <Card :class="cn('overflow-hidden rounded-md border bg-card shadow-sm', props.class)">
    <button
      type="button"
      class="flex h-11 w-full items-center justify-between border-b bg-muted/35 px-4 text-left text-sm font-semibold transition-colors hover:bg-muted/55"
      :aria-expanded="open"
      @click="open = !open"
    >
      <span class="truncate">{{ title }}</span>
      <ChevronDown
        class="h-4 w-4 shrink-0 text-muted-foreground transition-transform"
        :class="{ 'rotate-180': !open }"
      />
    </button>
    <CardContent v-show="open" :class="cn('p-4', contentClass)">
      <slot />
    </CardContent>
  </Card>
</template>
