<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch, type Component } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
  AlertCircle,
  File as FileIcon,
  FileArchive,
  FileImage,
  FileJson,
  FileSpreadsheet,
  FileText,
  UploadCloud,
  X,
} from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import type { PageProps } from '@/types'

const props = withDefaults(defineProps<{
  error?: string
  disabled?: boolean
}>(), {
  error: '',
  disabled: false,
})

const page = usePage<PageProps>()
const model = defineModel<File[]>({ default: () => [] })
const input = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const localErrors = ref<string[]>([])
const previewUrls = new Map<File, string>()

const attachmentSettings = computed(() => page.props.support.attachments)
const maxBytes = computed(() => attachmentSettings.value.max_bytes || 20 * 1024 * 1024)
const allowedExtensions = computed(() => attachmentSettings.value.allowed_extensions.map((extension) => extension.toLowerCase()))
const accept = computed(() => attachmentSettings.value.accept || allowedExtensions.value.map((extension) => `.${extension}`).join(','))
const allowedLabel = computed(() => allowedExtensions.value.map((extension) => extension.toUpperCase()).join(', '))

const addFiles = (files: FileList | null) => {
  if (!files?.length || props.disabled) {
    return
  }

  localErrors.value = []

  const existingFiles = new Set(model.value.map(fileKey))
  const acceptedFiles: File[] = []

  Array.from(files).forEach((file) => {
    const error = validate(file)
    const key = fileKey(file)

    if (error) {
      localErrors.value.push(`${file.name}: ${error}`)

      return
    }

    if (existingFiles.has(key)) {
      localErrors.value.push(`${file.name}: already selected.`)

      return
    }

    existingFiles.add(key)
    acceptedFiles.push(file)
  })

  if (acceptedFiles.length) {
    model.value = [...model.value, ...acceptedFiles]
  }
}

const onInput = (event: Event) => {
  const target = event.target as HTMLInputElement

  addFiles(target.files)
  target.value = ''
}

const onDrop = (event: DragEvent) => {
  isDragging.value = false
  addFiles(event.dataTransfer?.files ?? null)
}

const remove = (index: number) => {
  model.value = model.value.filter((_, currentIndex) => currentIndex !== index)
}

const validate = (file: File) => {
  if (file.size > maxBytes.value) {
    return `maximum size is ${formatSize(maxBytes.value)}.`
  }

  const extension = extensionFor(file)

  if (allowedExtensions.value.length && (!extension || !allowedExtensions.value.includes(extension))) {
    return 'file type is not supported.'
  }

  return null
}

const fileKey = (file: File) => `${file.name}:${file.size}:${file.lastModified}`

const extensionFor = (file: File) => {
  const segments = file.name.split('.')

  return segments.length > 1 ? segments.pop()?.toLowerCase() ?? '' : ''
}

const isImage = (file: File) => file.type.startsWith('image/')

const iconFor = (file: File): Component => {
  const extension = extensionFor(file)

  if (isImage(file)) {
    return FileImage
  }

  if (['zip'].includes(extension)) {
    return FileArchive
  }

  if (['json'].includes(extension)) {
    return FileJson
  }

  if (['csv', 'xlsx'].includes(extension)) {
    return FileSpreadsheet
  }

  if (['txt', 'pdf', 'docx'].includes(extension)) {
    return FileText
  }

  return FileIcon
}

const selectedFiles = computed(() => model.value.map((file) => ({
  file,
  extension: extensionFor(file).toUpperCase() || 'FILE',
  icon: iconFor(file),
  previewUrl: previewUrls.get(file),
})))

const formatSize = (size: number) => {
  if (size < 1024) {
    return `${size} B`
  }

  if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(1)} KB`
  }

  return `${(size / 1024 / 1024).toFixed(1)} MB`
}

watch(model, (files) => {
  Array.from(previewUrls.keys()).forEach((file) => {
    if (!files.includes(file)) {
      URL.revokeObjectURL(previewUrls.get(file)!)
      previewUrls.delete(file)
    }
  })

  files.forEach((file) => {
    if (isImage(file) && !previewUrls.has(file)) {
      previewUrls.set(file, URL.createObjectURL(file))
    }
  })
}, { immediate: true })

onBeforeUnmount(() => {
  previewUrls.forEach((url) => URL.revokeObjectURL(url))
  previewUrls.clear()
})
</script>

<template>
  <div class="space-y-3">
    <input
      ref="input"
      type="file"
      multiple
      :accept="accept"
      :disabled="disabled"
      class="hidden"
      @change="onInput"
    />

    <div
      class="rounded-md border border-dashed bg-muted/20 p-4 transition-colors"
      :class="[
        isDragging ? 'border-primary bg-primary/5' : 'border-border',
        disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:border-primary/40 hover:bg-muted/30',
      ]"
      @click="!disabled && input?.click()"
      @dragenter.prevent="isDragging = true"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
    >
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border bg-background text-muted-foreground">
          <UploadCloud class="h-5 w-5" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-foreground">Add attachments</p>
          <p class="mt-0.5 truncate text-xs text-muted-foreground">
            {{ allowedLabel }} · up to {{ formatSize(maxBytes) }}
          </p>
        </div>
        <Button type="button" variant="secondary" size="sm" :disabled="disabled">
          Browse
        </Button>
      </div>
    </div>

    <div v-if="error || localErrors.length" class="flex gap-2 rounded-md border border-destructive/30 bg-destructive/5 p-3 text-xs text-destructive">
      <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
      <div class="min-w-0 space-y-1">
        <p v-if="error">{{ error }}</p>
        <p v-for="message in localErrors" :key="message">{{ message }}</p>
      </div>
    </div>

    <ul v-if="selectedFiles.length" class="grid gap-2 sm:grid-cols-2">
      <li
        v-for="(item, index) in selectedFiles"
        :key="`${item.file.name}-${index}`"
        class="flex min-w-0 items-center gap-3 rounded-md border bg-background p-2 text-sm"
      >
        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted/30 text-muted-foreground">
          <img
            v-if="item.previewUrl"
            :src="item.previewUrl"
            :alt="item.file.name"
            class="h-full w-full object-cover"
          >
          <component :is="item.icon" v-else class="h-5 w-5" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="truncate font-medium text-foreground">{{ item.file.name }}</p>
          <p class="text-xs text-muted-foreground">{{ item.extension }} · {{ formatSize(item.file.size) }}</p>
        </div>
        <button
          type="button"
          class="rounded-md p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
          :aria-label="`Remove ${item.file.name}`"
          @click.stop="remove(index)"
        >
          <X class="h-4 w-4" />
        </button>
      </li>
    </ul>
  </div>
</template>
