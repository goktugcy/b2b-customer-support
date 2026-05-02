<script setup lang="ts">
import { computed, ref } from 'vue'
import { Check, ChevronsUpDown, X } from 'lucide-vue-next'
import { PopoverContent, PopoverRoot, PopoverTrigger } from 'reka-ui'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import { cn } from '@/lib/utils'
import type { MultiSelectOption } from '@/types'

const model = defineModel<string[]>({ default: [] })

const props = withDefaults(defineProps<{
  options: MultiSelectOption[]
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  class?: string
}>(), {
  placeholder: 'Select',
  searchPlaceholder: 'Search...',
  emptyText: 'No options found',
})

const open = ref(false)
const search = ref('')

const selectedIds = computed(() => Array.isArray(model.value) ? model.value : [])
const selectedOptions = computed(() => props.options.filter((option) => selectedIds.value.includes(option.id)))
const filteredOptions = computed(() => {
  const needle = search.value.trim().toLocaleLowerCase()

  if (!needle) {
    return props.options
  }

  return props.options.filter((option) => option.name.toLocaleLowerCase().includes(needle))
})

const toggle = (id: string) => {
  model.value = selectedIds.value.includes(id)
    ? selectedIds.value.filter((selected) => selected !== id)
    : [...selectedIds.value, id]
}

const remove = (id: string) => {
  model.value = selectedIds.value.filter((selected) => selected !== id)
}
</script>

<template>
  <PopoverRoot v-model:open="open">
    <PopoverTrigger as-child>
      <button
        type="button"
        :class="cn('flex min-h-10 w-full items-center gap-2 rounded-md border border-input bg-card px-3 py-2 text-left text-sm shadow-sm transition-colors hover:border-ring/40 hover:bg-secondary/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/70 focus-visible:ring-offset-2 focus-visible:ring-offset-background', props.class)"
      >
        <div class="flex min-w-0 flex-1 flex-wrap gap-1">
          <Badge v-for="option in selectedOptions" :key="option.id" tone="neutral" class="max-w-full gap-1">
            <span class="truncate">{{ option.name }}</span>
            <span role="button" tabindex="0" class="rounded-sm text-muted-foreground hover:text-foreground" @click.stop="remove(option.id)" @keydown.enter.stop.prevent="remove(option.id)">
              <X class="h-3 w-3" />
            </span>
          </Badge>
          <span v-if="!selectedOptions.length" class="text-muted-foreground">{{ placeholder }}</span>
        </div>
        <ChevronsUpDown class="h-4 w-4 shrink-0 text-muted-foreground" />
      </button>
    </PopoverTrigger>
    <PopoverContent side="bottom" align="start" :side-offset="6" class="z-50 w-[min(520px,calc(100vw-2rem))] rounded-md border bg-popover p-2 text-popover-foreground shadow-lg">
      <Input v-model="search" :placeholder="searchPlaceholder" class="mb-2" />
      <div class="max-h-64 overflow-y-auto">
        <button
          v-for="option in filteredOptions"
          :key="option.id"
          type="button"
          class="flex w-full items-center justify-between gap-3 rounded-md px-2 py-2 text-left text-sm hover:bg-secondary"
          @click="toggle(option.id)"
        >
          <span class="truncate">{{ option.name }}</span>
          <Check v-if="selectedIds.includes(option.id)" class="h-4 w-4 text-primary" />
        </button>
        <p v-if="!filteredOptions.length" class="px-2 py-6 text-center text-sm text-muted-foreground">{{ emptyText }}</p>
      </div>
      <div v-if="selectedIds.length" class="mt-2 border-t pt-2">
        <Button type="button" variant="ghost" size="sm" class="w-full" @click="model = []">Clear</Button>
      </div>
    </PopoverContent>
  </PopoverRoot>
</template>
