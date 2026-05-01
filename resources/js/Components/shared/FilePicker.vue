<script setup lang="ts">
import { ref } from 'vue'
import { Paperclip, X } from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'

const model = defineModel<File[]>({ default: () => [] })
const input = ref<HTMLInputElement | null>(null)

const addFiles = (files: FileList | null) => {
  if (!files?.length) {
    return
  }

  model.value = [...model.value, ...Array.from(files)]
}

const onInput = (event: Event) => {
  const target = event.target as HTMLInputElement

  addFiles(target.files)
  target.value = ''
}

const remove = (index: number) => {
  model.value = model.value.filter((_, currentIndex) => currentIndex !== index)
}

const formatSize = (size: number) => {
  if (size < 1024) {
    return `${size} B`
  }

  if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(1)} KB`
  }

  return `${(size / 1024 / 1024).toFixed(1)} MB`
}
</script>

<template>
  <div class="space-y-2">
    <input
      ref="input"
      type="file"
      multiple
      class="hidden"
      @change="onInput"
    />
    <Button type="button" variant="secondary" @click="input?.click()">
      <Paperclip class="h-4 w-4" />
      Files
    </Button>

    <ul v-if="model.length" class="space-y-2">
      <li v-for="(file, index) in model" :key="`${file.name}-${index}`" class="flex items-center justify-between gap-3 rounded-md border border-slate-200 px-3 py-2 text-sm">
        <span class="min-w-0 truncate">{{ file.name }} · {{ formatSize(file.size) }}</span>
        <button type="button" class="rounded text-slate-500 hover:text-slate-950" @click="remove(index)">
          <X class="h-4 w-4" />
        </button>
      </li>
    </ul>
  </div>
</template>
