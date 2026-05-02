<script setup lang="ts">
import { ref, watch } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import {
  Bold,
  Check,
  Code,
  Code2,
  Heading1,
  Heading2,
  Italic,
  Link2,
  List,
  ListOrdered,
  Pilcrow,
  Quote,
  Redo2,
  RemoveFormatting,
  Undo2,
  Unlink,
  X,
} from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import { cn } from '@/lib/utils'

const model = defineModel<string>({ default: '' })

const props = withDefaults(defineProps<{
  placeholder?: string
  class?: string
}>(), {
  placeholder: 'Write content...',
})

const linkEditorOpen = ref(false)
const linkUrl = ref('')

const editor = useEditor({
  content: model.value || '',
  extensions: [
    StarterKit.configure({
      link: false,
    }),
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
      class: 'rich-editor-content min-h-[240px] px-4 py-3 text-sm leading-6 outline-none',
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

const openLinkEditor = () => {
  if (!editor.value) {
    return
  }

  linkUrl.value = editor.value.getAttributes('link').href as string | undefined ?? ''
  linkEditorOpen.value = true
}

const applyLink = () => {
  if (!editor.value) {
    return
  }

  const url = linkUrl.value.trim()

  if (!url) {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
  } else {
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
  }

  linkEditorOpen.value = false
}

const closeLinkEditor = () => {
  linkEditorOpen.value = false
  linkUrl.value = ''
}

const toolbarButtonClass = (active = false) => cn(active && 'bg-secondary text-secondary-foreground')
</script>

<template>
  <div :class="cn('overflow-hidden rounded-md border border-input bg-card shadow-sm', props.class)">
    <div v-if="editor" class="space-y-2 border-b bg-muted/35 p-2">
      <div class="flex flex-wrap items-center gap-1">
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('heading', { level: 1 }))" title="Heading 1" aria-label="Heading 1" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()">
          <Heading1 />
        </Button>
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('heading', { level: 2 }))" title="Heading 2" aria-label="Heading 2" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">
          <Heading2 />
        </Button>
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('paragraph'))" title="Paragraph" aria-label="Paragraph" @click="editor.chain().focus().setParagraph().run()">
          <Pilcrow />
        </Button>
        <span class="mx-1 h-6 w-px bg-border" />
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('bold'))" title="Bold" aria-label="Bold" @click="editor.chain().focus().toggleBold().run()">
          <Bold />
        </Button>
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('italic'))" title="Italic" aria-label="Italic" @click="editor.chain().focus().toggleItalic().run()">
          <Italic />
        </Button>
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('code'))" title="Inline code" aria-label="Inline code" @click="editor.chain().focus().toggleCode().run()">
          <Code />
        </Button>
        <span class="mx-1 h-6 w-px bg-border" />
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('bulletList'))" title="Bullet list" aria-label="Bullet list" @click="editor.chain().focus().toggleBulletList().run()">
          <List />
        </Button>
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('orderedList'))" title="Ordered list" aria-label="Ordered list" @click="editor.chain().focus().toggleOrderedList().run()">
          <ListOrdered />
        </Button>
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('blockquote'))" title="Quote" aria-label="Quote" @click="editor.chain().focus().toggleBlockquote().run()">
          <Quote />
        </Button>
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('codeBlock'))" title="Code block" aria-label="Code block" @click="editor.chain().focus().toggleCodeBlock().run()">
          <Code2 />
        </Button>
        <span class="mx-1 h-6 w-px bg-border" />
        <Button type="button" size="icon" variant="ghost" :class="toolbarButtonClass(editor.isActive('link'))" title="Link" aria-label="Link" @click="openLinkEditor">
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

      <div v-if="linkEditorOpen" class="flex flex-col gap-2 rounded-md border bg-background p-2 sm:flex-row">
        <Input
          v-model="linkUrl"
          class="h-9"
          placeholder="https://example.com"
          @keydown.enter.prevent="applyLink"
          @keydown.esc.prevent="closeLinkEditor"
        />
        <div class="flex gap-2">
          <Button type="button" size="sm" @click="applyLink">
            <Check class="h-4 w-4" />
            Apply
          </Button>
          <Button type="button" size="sm" variant="ghost" @click="closeLinkEditor">
            <X class="h-4 w-4" />
            Cancel
          </Button>
        </div>
      </div>
    </div>
    <EditorContent :editor="editor" />
  </div>
</template>
