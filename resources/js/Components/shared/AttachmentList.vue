<script setup lang="ts">
import { Download, Paperclip } from 'lucide-vue-next'

type Attachment = {
  id: string
  filename: string
  mime_type?: string | null
  size: number
  visibility?: string
  url: string
}

defineProps<{ attachments?: Attachment[] }>()

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
  <div v-if="attachments?.length" class="mt-3 flex flex-wrap gap-2">
    <a
      v-for="attachment in attachments"
      :key="attachment.id"
      :href="attachment.url"
      class="inline-flex max-w-full items-center gap-2 rounded-md border bg-card px-2.5 py-1.5 text-xs font-medium text-muted-foreground shadow-sm transition-colors hover:border-primary/30 hover:text-primary"
    >
      <Paperclip class="h-3.5 w-3.5 shrink-0" />
      <span class="truncate">{{ attachment.filename }}</span>
      <span class="shrink-0 text-muted-foreground/70">{{ formatSize(attachment.size) }}</span>
      <Download class="h-3.5 w-3.5 shrink-0" />
    </a>
  </div>
</template>
