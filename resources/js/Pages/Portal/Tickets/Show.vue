<script setup lang="ts">
import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Select from '@/Components/ui/select/Select.vue'
import AttachmentList from '@/Components/shared/AttachmentList.vue'
import FilePicker from '@/Components/shared/FilePicker.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import type { SelectOption } from '@/types'

type Attachment = { id: string; filename: string; size: number; url: string }
type Person = { id: string; name: string; side?: string }

type Ticket = {
  id: string
  subject: string
  description: string
  status: string
  priority: string
  assignee?: string
  targets: { departments: Person[]; users: Person[] }
  watchers: Person[]
  attachments: Attachment[]
  comments: { id: string; body: string; author?: string; created_at: string; attachments: Attachment[] }[]
}

const props = defineProps<{
  ticket: Ticket
  transitions: SelectOption[]
  watcherUsers: Person[]
}>()

const form = useForm({ body: '', attachments: [] as File[] })
const statusForm = useForm({ status: props.transitions[0]?.value ?? 'resolved' })
const watcherForm = useForm({ user_id: '' })
const attachmentForm = useForm({ attachments: [] as File[] })

const watcherOptions = computed(() => props.watcherUsers.filter((user) => !props.ticket.watchers.some((watcher) => watcher.id === user.id)))

const submit = () => form.post(route('portal.tickets.comments.store', props.ticket.id), {
  preserveScroll: true,
  forceFormData: true,
  onSuccess: () => {
    form.reset('body')
    form.attachments = []
  },
})

const changeStatus = () => statusForm.patch(route('portal.tickets.status', props.ticket.id), { preserveScroll: true })

const addWatcher = () => watcherForm.post(route('portal.tickets.watchers.store', props.ticket.id), {
  preserveScroll: true,
  onSuccess: () => watcherForm.reset(),
})

const removeWatcher = (userId: string) => {
  router.delete(route('portal.tickets.watchers.destroy', [props.ticket.id, userId]), { preserveScroll: true })
}

const uploadAttachments = () => {
  attachmentForm.attachments.forEach((file) => {
    router.post(route('portal.tickets.attachments.store', props.ticket.id), { file }, {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        attachmentForm.attachments = []
      },
    })
  })
}
</script>

<template>
  <PortalLayout :title="ticket.subject">
    <Link :href="route('portal.tickets.index')" class="text-sm font-medium text-teal-800">Back to tickets</Link>
    <section class="mt-4 grid gap-6 lg:grid-cols-[1fr_340px]">
      <div class="space-y-6">
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold">{{ ticket.subject }}</h2>
            <div class="flex gap-2"><Badge tone="blue">{{ ticket.status }}</Badge><Badge>{{ ticket.priority }}</Badge></div>
          </div>
          <p class="mt-5 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ ticket.description }}</p>
          <AttachmentList :attachments="ticket.attachments" />
        </div>

        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <h3 class="text-sm font-semibold">Conversation</h3>
          <div class="mt-4 space-y-3">
            <div v-for="comment in ticket.comments" :key="comment.id" class="rounded-md border border-slate-200 p-3">
              <p class="text-sm font-medium">{{ comment.author || 'Support' }}</p>
              <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ comment.body }}</p>
              <AttachmentList :attachments="comment.attachments" />
            </div>
          </div>
          <form class="mt-5 space-y-3" @submit.prevent="submit">
            <Label>Reply</Label>
            <Textarea v-model="form.body" required />
            <FieldError :message="form.errors.body" />
            <FilePicker v-model="form.attachments" />
            <Button type="submit" :disabled="form.processing">Add reply</Button>
          </form>
        </div>
      </div>

      <div class="space-y-4">
        <aside class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <h3 class="text-sm font-semibold">Ticket details</h3>
          <dl class="mt-4 space-y-3 text-sm">
            <div><dt class="text-slate-500">Assignee</dt><dd class="font-medium">{{ ticket.assignee || 'Unassigned' }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ ticket.status }}</dd></div>
            <div><dt class="text-slate-500">Priority</dt><dd class="font-medium">{{ ticket.priority }}</dd></div>
          </dl>
        </aside>

        <form v-if="transitions.length" class="rounded-md border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="changeStatus">
          <h3 class="text-sm font-semibold">Status</h3>
          <Select v-model="statusForm.status" class="mt-4">
            <option v-for="status in transitions" :key="status.value" :value="status.value">{{ status.label }}</option>
          </Select>
          <Button type="submit" class="mt-3 w-full">Update status</Button>
        </form>

        <aside class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <h3 class="text-sm font-semibold">Targets</h3>
          <div class="mt-4 flex flex-wrap gap-2">
            <Badge v-for="department in ticket.targets.departments" :key="department.id" tone="blue">{{ department.name }}</Badge>
            <Badge v-for="user in ticket.targets.users" :key="user.id" tone="neutral">{{ user.name }}</Badge>
          </div>
        </aside>

        <form class="rounded-md border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="addWatcher">
          <h3 class="text-sm font-semibold">Watchers</h3>
          <div class="mt-4 flex flex-wrap gap-2">
            <Badge v-for="watcher in ticket.watchers" :key="watcher.id" tone="neutral" class="gap-2">
              {{ watcher.name }}
              <button type="button" class="text-slate-500 hover:text-slate-950" @click="removeWatcher(watcher.id)">x</button>
            </Badge>
          </div>
          <Select v-model="watcherForm.user_id" class="mt-4">
            <option value="">Add watcher</option>
            <option v-for="user in watcherOptions" :key="user.id" :value="user.id">{{ user.name }}</option>
          </Select>
          <FieldError :message="watcherForm.errors.user_id" />
          <Button type="submit" class="mt-3 w-full" variant="secondary" :disabled="!watcherForm.user_id">Add watcher</Button>
        </form>

        <form class="rounded-md border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="uploadAttachments">
          <h3 class="text-sm font-semibold">Attachments</h3>
          <div class="mt-4 space-y-3">
            <FilePicker v-model="attachmentForm.attachments" />
            <Button type="submit" class="w-full" variant="secondary" :disabled="!attachmentForm.attachments.length">Upload</Button>
          </div>
        </form>
      </div>
    </section>
  </PortalLayout>
</template>
