<script setup lang="ts">
import { SlidersHorizontal, X } from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Sheet from '@/Components/ui/sheet/Sheet.vue'
import SheetContent from '@/Components/ui/sheet/SheetContent.vue'
import SheetHeader from '@/Components/ui/sheet/SheetHeader.vue'
import SheetTitle from '@/Components/ui/sheet/SheetTitle.vue'
import SheetDescription from '@/Components/ui/sheet/SheetDescription.vue'
import SheetFooter from '@/Components/ui/sheet/SheetFooter.vue'

withDefaults(defineProps<{
  open?: boolean
  count?: number
  title?: string
  description?: string
}>(), {
  count: 0,
  title: 'Filters',
  description: 'Refine the current list without losing context.',
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  apply: []
  reset: []
}>()
</script>

<template>
  <Button type="button" variant="secondary" @click="emit('update:open', true)">
    <SlidersHorizontal class="h-4 w-4" />
    Filters
    <Badge v-if="count" tone="blue">{{ count }}</Badge>
  </Button>

  <Sheet :open="open" @update:open="emit('update:open', $event)">
    <SheetContent>
      <SheetHeader class="flex-row items-start justify-between gap-3">
        <div>
          <SheetTitle>{{ title }}</SheetTitle>
          <SheetDescription>{{ description }}</SheetDescription>
        </div>
        <Button type="button" variant="ghost" size="icon" class="h-8 w-8" @click="emit('update:open', false)">
          <X class="h-4 w-4" />
        </Button>
      </SheetHeader>
      <div class="flex-1 overflow-y-auto px-5 py-5">
        <slot />
      </div>
      <SheetFooter>
        <Button type="button" variant="ghost" @click="emit('reset')">Reset</Button>
        <Button type="button" @click="emit('apply')">Apply filters</Button>
      </SheetFooter>
    </SheetContent>
  </Sheet>
</template>
