<script setup lang="ts">
import { watch } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import { Bold, Code, Italic, Link2, List, ListOrdered, Quote, Redo2, RemoveFormatting, Undo2, Unlink } from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import { cn } from '@/lib/utils'

const model = defineModel<string>({ default: '' })

const props = withDefaults(defineProps<{
  placeholder?: string
  class?: string
}>(), {
  placeholder: 'Write content...',
})

const editor = useEditor({
  content: model.value || '',
  extensions: [
    StarterKit,
    Link.configure({
      openOnClick: false,
      autolink: true,
      defaultProtocol: 'https',
      HTMLAttributes: {
        rel: 'noopener noreferrer nofollow',
        target: '_blank',
      },
    }),
    Placeholder.configure({
      placeholder: props.placeholder,
    }),
  ],
  editorProps: {
    attributes: {
      class: 'rich-editor-content min-h-[180px] px-3 py-3 text-sm leading-6 outline-none',
    },
  },
  onUpdate: ({ editor }) => {
    model.value = editor.getHTML()
  },
})

watch(model, (value) => {
  if (!editor.value || editor.value.getHTML() === value) {
    return
  }

  editor.value.commands.setContent(value || '', { emitUpdate: false })
})

const toggleLink = () => {
  if (!editor.value) {
    return
  }

  const previousUrl = editor.value.getAttributes('link').href as string | undefined
  const url = window.prompt('URL', previousUrl ?? '')

  if (url === null) {
    return
  }

  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }

  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

const toolbarButtonClass = (active = false) => cn(active && 'bg-secondary text-secondary-foreground')
</script>

<template>
  <div :class="cn('overflow-hidden rounded-md border border-input bg-background shadow-sm', props.class)">
    <div v-if="editor" class="flex flex-wrap items-center gap-1 border-b bg-muted/40 p-2">
      <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('bold'))" title="Bold" aria-label="Bold" @click="editor.chain().focus().toggleBold().run()">
        <Bold />
      </Button>
      <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('italic'))" title="Italic" aria-label="Italic" @click="editor.chain().focus().toggleItalic().run()">
        <Italic />
      </Button>
      <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('code'))" title="Code" aria-label="Code" @click="editor.chain().focus().toggleCode().run()">
        <Code />
      </Button>
      <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('bulletList'))" title="Bullet list" aria-label="Bullet list" @click="editor.chain().focus().toggleBulletList().run()">
        <List />
      </Button>
      <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('orderedList'))" title="Ordered list" aria-label="Ordered list" @click="editor.chain().focus().toggleOrderedList().run()">
        <ListOrdered />
      </Button>
      <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('blockquote'))" title="Quote" aria-label="Quote" @click="editor.chain().focus().toggleBlockquote().run()">
        <Quote />
      </Button>
      <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('link'))" title="Link" aria-label="Link" @click="toggleLink">
        <Link2 />
      </Button>
      <Button type="button" size="icon" variant="ghost" title="Unlink" aria-label="Unlink" @click="editor.chain().focus().unsetLink().run()">
        <Unlink />
      </Button>
      <Button type="button" size="icon" variant="ghost" title="Clear formatting" aria-label="Clear formatting" @click="editor.chain().focus().clearNodes().unsetAllMarks().run()">
        <RemoveFormatting />
      </Button>
      <div class="ml-auto flex gap-1">
        <Button type="button" size="icon" variant="ghost" title="Undo" aria-label="Undo" @click="editor.chain().focus().undo().run()">
          <Undo2 />
        </Button>
        <Button type="button" size="icon" variant="ghost" title="Redo" aria-label="Redo" @click="editor.chain().focus().redo().run()">
          <Redo2 />
        </Button>
      </div>
    </div>
    <EditorContent :editor="editor" />
  </div>
</template>
