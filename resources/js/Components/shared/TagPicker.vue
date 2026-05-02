<script setup lang="ts">
import { computed, ref } from 'vue'
import { Check, ChevronsUpDown, Plus, X } from 'lucide-vue-next'
import { PopoverContent, PopoverRoot, PopoverTrigger } from 'reka-ui'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import { cn } from '@/lib/utils'
import type { TagOption } from '@/types'

const model = defineModel<string[]>({ default: [] })

const props = withDefaults(defineProps<{
  options: TagOption[]
  placeholder?: string
  class?: string
}>(), {
  placeholder: 'Select or create tags',
})

const open = ref(false)
const search = ref('')

const normalize = (value: string) => value.trim()
const selectedKeys = computed(() => model.value.map((tag) => tag.toLocaleLowerCase()))
const filteredOptions = computed(() => {
  const needle = search.value.trim().toLocaleLowerCase()
  const options = needle
    ? props.options.filter((option) => option.name.toLocaleLowerCase().includes(needle))
    : props.options

  return options
})
const canCreate = computed(() => {
  const name = normalize(search.value)

  return name.length > 0 && !selectedKeys.value.includes(name.toLocaleLowerCase()) && !props.options.some((option) => option.name.toLocaleLowerCase() === name.toLocaleLowerCase())
})

const toggle = (name: string) => {
  const normalized = normalize(name)
  const exists = model.value.some((tag) => tag.toLocaleLowerCase() === normalized.toLocaleLowerCase())

  model.value = exists
    ? model.value.filter((tag) => tag.toLocaleLowerCase() !== normalized.toLocaleLowerCase())
    : [...model.value, normalized]
}

const createTag = () => {
  const name = normalize(search.value)

  if (!name) {
    return
  }

  if (!selectedKeys.value.includes(name.toLocaleLowerCase())) {
    model.value = [...model.value, name]
  }

  search.value = ''
}

const remove = (name: string) => {
  model.value = model.value.filter((tag) => tag !== name)
}

const tagColor = (name: string) => props.options.find((option) => option.name.toLocaleLowerCase() === name.toLocaleLowerCase())?.color ?? '#64748b'
</script>

<template>
  <PopoverRoot v-model:open="open">
    <PopoverTrigger as-child>
      <button
        type="button"
        :class="cn('flex min-h-10 w-full items-center gap-2 rounded-md border border-input bg-card px-3 py-2 text-left text-sm shadow-sm transition-colors hover:border-ring/40 hover:bg-secondary/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/70 focus-visible:ring-offset-2 focus-visible:ring-offset-background', props.class)"
      >
        <div class="flex min-w-0 flex-1 flex-wrap gap-1">
          <span
            v-for="tag in model"
            :key="tag"
            class="inline-flex max-w-full items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium"
            :style="{ borderColor: `${tagColor(tag)}33`, backgroundColor: `${tagColor(tag)}14`, color: tagColor(tag) }"
          >
            <span class="truncate">{{ tag }}</span>
            <span role="button" tabindex="0" class="rounded-sm opacity-70 hover:opacity-100" @click.stop="remove(tag)" @keydown.enter.stop.prevent="remove(tag)">
              <X class="h-3 w-3" />
            </span>
          </span>
          <span v-if="!model.length" class="text-muted-foreground">{{ placeholder }}</span>
        </div>
        <ChevronsUpDown class="h-4 w-4 shrink-0 text-muted-foreground" />
      </button>
    </PopoverTrigger>
    <PopoverContent side="bottom" align="start" :side-offset="6" class="z-50 w-[min(520px,calc(100vw-2rem))] rounded-md border bg-popover p-2 text-popover-foreground shadow-lg">
      <Input v-model="search" placeholder="Search or type a new tag" class="mb-2" @keydown.enter.prevent="createTag" />
      <div class="max-h-64 overflow-y-auto">
        <button
          v-for="option in filteredOptions"
          :key="option.name"
          type="button"
          class="flex w-full items-center justify-between gap-3 rounded-md px-2 py-2 text-left text-sm hover:bg-secondary"
          @click="toggle(option.name)"
        >
          <span class="inline-flex min-w-0 items-center gap-2">
            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: option.color ?? '#64748b' }" />
            <span class="truncate">{{ option.name }}</span>
          </span>
          <Check v-if="selectedKeys.includes(option.name.toLocaleLowerCase())" class="h-4 w-4 text-primary" />
        </button>
        <button v-if="canCreate" type="button" class="mt-1 flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm text-primary hover:bg-secondary" @click="createTag">
          <Plus class="h-4 w-4" />
          <span>{{ search.trim() }}</span>
        </button>
        <p v-if="!filteredOptions.length && !canCreate" class="px-2 py-6 text-center text-sm text-muted-foreground">No tags found</p>
      </div>
      <div v-if="model.length" class="mt-2 border-t pt-2">
        <Button type="button" variant="ghost" size="sm" class="w-full" @click="model = []">Clear</Button>
      </div>
    </PopoverContent>
  </PopoverRoot>
</template>
