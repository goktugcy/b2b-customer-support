<script setup lang="ts">
import { computed } from 'vue'
import { X } from 'lucide-vue-next'
import Badge from '@/Components/ui/badge/Badge.vue'
import type { MultiSelectOption } from '@/types'

const model = defineModel<string[]>({ default: () => [] })

const props = defineProps<{
  options: MultiSelectOption[]
  placeholder?: string
}>()

const availableOptions = computed(() => props.options.filter((option) => !model.value.includes(option.id)))
const selectedOptions = computed(() => props.options.filter((option) => model.value.includes(option.id)))

const add = (value: string) => {
  if (!value || model.value.includes(value)) {
    return
  }

  model.value = [...model.value, value]
}

const remove = (value: string) => {
  model.value = model.value.filter((item) => item !== value)
}

const onSelect = (event: Event) => {
  const target = event.target as HTMLSelectElement

  add(target.value)
  target.value = ''
}
</script>

<template>
  <div class="space-y-2">
    <select
      class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20"
      @change="onSelect"
    >
      <option value="">{{ placeholder ?? 'Select' }}</option>
      <option v-for="option in availableOptions" :key="option.id" :value="option.id">
        {{ option.name }}
      </option>
    </select>

    <div v-if="selectedOptions.length" class="flex flex-wrap gap-2">
      <Badge v-for="option in selectedOptions" :key="option.id" tone="neutral" class="inline-flex items-center gap-1">
        {{ option.name }}
        <button type="button" class="rounded-sm text-slate-500 hover:text-slate-950" @click="remove(option.id)">
          <X class="h-3 w-3" />
        </button>
      </Badge>
    </div>
  </div>
</template>
