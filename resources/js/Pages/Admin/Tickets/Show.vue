<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Select from '@/Components/ui/select/Select.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import AttachmentList from '@/Components/shared/AttachmentList.vue'
import FilePicker from '@/Components/shared/FilePicker.vue'
import MultiSelectChips from '@/Components/shared/MultiSelectChips.vue'
import type { MultiSelectOption, SelectOption } from '@/types'

type Attachment = { id: string; filename: string; size: number; visibility?: string; url: string }
type Person = { id: string; name: string; side?: string }

type TicketDetail = {
  id: string
  subject: string
  description: string
  status: string
  priority: string
  company: string
  assignee?: string
  assignee_id?: string
  requester?: string
  targets: { departments: Person[]; users: Person[] }
  watchers: Person[]
  attachments: Attachment[]
  comments: { id: string; body: string; visibility: string; author?: string; created_at: string; attachments: Attachment[] }[]
  events: { id: number; type: string; actor?: string; old_values?: unknown; new_values?: unknown; occurred_at: string }[]
}

const props = defineProps<{
  ticket: TicketDetail
  statuses: SelectOption[]
  priorities: SelectOption[]
  transitions: SelectOption[]
  agents: MultiSelectOption[]
  departments: MultiSelectOption[]
  providerUsers: MultiSelectOption[]
}>()

const editForm = useForm({
  subject: props.ticket.subject,
  priority: props.ticket.priority,
})

const statusForm = useForm({ status: props.transitions[0]?.value ?? props.ticket.status })
const assignForm = useForm({ assigned_to_user_id: props.ticket.assignee_id ?? '' })
const commentForm = useForm({ body: '', visibility: 'public', attachments: [] as File[] })
const targetForm = useForm({
  target_department_ids: props.ticket.targets.departments.map((department) => department.id),
  target_user_ids: props.ticket.targets.users.map((user) => user.id),
})
const watcherForm = useForm({ user_id: '' })
const attachmentForm = useForm({ visibility: 'public', attachments: [] as File[] })

const watcherOptions = computed(() => props.providerUsers.filter((user) => !props.ticket.watchers.some((watcher) => watcher.id === user.id)))
const targetErrors = computed(() => targetForm.errors.target_department_ids || targetForm.errors.target_user_ids || (targetForm.errors as Record<string, string | undefined>).targets)

const updateTicket = () => editForm.patch(route('admin.tickets.update', props.ticket.id), { preserveScroll: true })
const changeStatus = () => statusForm.patch(route('admin.tickets.status', props.ticket.id), { preserveScroll: true })
const assignTicket = () => assignForm.patch(route('admin.tickets.assignment', props.ticket.id), { preserveScroll: true })
const updateTargets = () => targetForm.patch(route('admin.tickets.targets', props.ticket.id), { preserveScroll: true })

const addComment = () => commentForm.post(route('admin.tickets.comments.store', props.ticket.id), {
  preserveScroll: true,
  forceFormData: true,
  onSuccess: () => {
    commentForm.reset('body')
    commentForm.attachments = []
  },
})

const addWatcher = () => watcherForm.post(route('admin.tickets.watchers.store', props.ticket.id), {
  preserveScroll: true,
  onSuccess: () => watcherForm.reset(),
})

const removeWatcher = (userId: string) => {
  router.delete(route('admin.tickets.watchers.destroy', [props.ticket.id, userId]), { preserveScroll: true })
}

const uploadAttachments = () => {
  attachmentForm.attachments.forEach((file) => {
    router.post(route('admin.tickets.attachments.store', props.ticket.id), {
      file,
      visibility: attachmentForm.visibility,
    }, {
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
  <AdminLayout :title="ticket.subject">
    <div class="mb-2">
      <Link :href="route('admin.tickets.index')" class="text-sm font-medium text-teal-800">Back to tickets</Link>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1fr_380px]">
      <div class="space-y-6">
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 class="text-xl font-semibold">{{ ticket.subject }}</h2>
              <p class="mt-1 text-sm text-slate-500">{{ ticket.company }} · {{ ticket.requester || 'No requester' }}</p>
            </div>
            <div class="flex gap-2">
              <Badge tone="blue">{{ ticket.status }}</Badge>
              <Badge tone="neutral">{{ ticket.priority }}</Badge>
            </div>
          </div>
          <p class="mt-5 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ ticket.description }}</p>
          <AttachmentList :attachments="ticket.attachments" />
        </div>

        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <h3 class="text-sm font-semibold">Conversation</h3>
          <div class="mt-4 space-y-3">
            <div v-for="comment in ticket.comments" :key="comment.id" class="rounded-md border border-slate-200 p-3">
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium">{{ comment.author || 'System' }}</p>
                <Badge :tone="comment.visibility === 'internal' ? 'amber' : 'green'">{{ comment.visibility }}</Badge>
              </div>
              <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ comment.body }}</p>
              <AttachmentList :attachments="comment.attachments" />
            </div>
          </div>

          <form class="mt-5 space-y-3" @submit.prevent="addComment">
            <Label>Reply</Label>
            <Textarea v-model="commentForm.body" required />
            <FieldError :message="commentForm.errors.body" />
            <div class="grid gap-3 md:grid-cols-[180px_1fr]">
              <Select v-model="commentForm.visibility">
                <option value="public">Public reply</option>
                <option value="internal">Internal note</option>
              </Select>
              <FilePicker v-model="commentForm.attachments" />
            </div>
            <div class="flex justify-end">
              <Button type="submit" :disabled="commentForm.processing">Add comment</Button>
            </div>
          </form>
        </div>

        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <h3 class="text-sm font-semibold">Timeline</h3>
          <ol class="mt-4 space-y-3">
            <li v-for="event in ticket.events" :key="event.id" class="border-l-2 border-slate-200 pl-3">
              <p class="text-sm font-medium">{{ event.type }}</p>
              <p class="text-xs text-slate-500">{{ event.actor || 'System' }} · {{ event.occurred_at }}</p>
            </li>
          </ol>
        </div>
      </div>

      <div class="space-y-4">
        <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="updateTicket">
          <h3 class="text-sm font-semibold">Details</h3>
          <div class="mt-4 space-y-3">
            <div>
              <Label>Subject</Label>
              <Input v-model="editForm.subject" class="mt-1" />
            </div>
            <div>
              <Label>Priority</Label>
              <Select v-model="editForm.priority" class="mt-1">
                <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
              </Select>
            </div>
            <Button type="submit" variant="secondary" class="w-full">Save details</Button>
          </div>
        </form>

        <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="changeStatus">
          <h3 class="text-sm font-semibold">Status</h3>
          <Select v-model="statusForm.status" class="mt-4">
            <option v-for="status in transitions" :key="status.value" :value="status.value">{{ status.label }}</option>
          </Select>
          <Button type="submit" class="mt-3 w-full" :disabled="!transitions.length">Change status</Button>
        </form>

        <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="assignTicket">
          <h3 class="text-sm font-semibold">Assignment</h3>
          <Select v-model="assignForm.assigned_to_user_id" class="mt-4">
            <option value="">Unassigned</option>
            <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
          </Select>
          <Button type="submit" class="mt-3 w-full" variant="secondary">Update assignment</Button>
        </form>

        <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="updateTargets">
          <h3 class="text-sm font-semibold">Targets</h3>
          <div class="mt-4 space-y-3">
            <div>
              <Label>Departments</Label>
              <MultiSelectChips v-model="targetForm.target_department_ids" class="mt-1" :options="departments" placeholder="Add department" />
            </div>
            <div>
              <Label>Provider users</Label>
              <MultiSelectChips v-model="targetForm.target_user_ids" class="mt-1" :options="providerUsers" placeholder="Add provider user" />
            </div>
            <FieldError :message="targetErrors" />
            <Button type="submit" variant="secondary" class="w-full">Save targets</Button>
          </div>
        </form>

        <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="addWatcher">
          <h3 class="text-sm font-semibold">Watchers</h3>
          <div class="mt-4 flex flex-wrap gap-2">
            <Badge v-for="watcher in ticket.watchers" :key="watcher.id" tone="neutral" class="gap-2">
              {{ watcher.name }}
              <button type="button" class="text-slate-500 hover:text-slate-950" @click="removeWatcher(watcher.id)">x</button>
            </Badge>
          </div>
          <Select v-model="watcherForm.user_id" class="mt-4">
            <option value="">Add provider watcher</option>
            <option v-for="user in watcherOptions" :key="user.id" :value="user.id">{{ user.name }}</option>
          </Select>
          <FieldError :message="watcherForm.errors.user_id" />
          <Button type="submit" class="mt-3 w-full" variant="secondary" :disabled="!watcherForm.user_id">Add watcher</Button>
        </form>

        <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="uploadAttachments">
          <h3 class="text-sm font-semibold">Attachments</h3>
          <div class="mt-4 space-y-3">
            <Select v-model="attachmentForm.visibility">
              <option value="public">Public</option>
              <option value="internal">Internal</option>
            </Select>
            <FilePicker v-model="attachmentForm.attachments" />
            <Button type="submit" class="w-full" variant="secondary" :disabled="!attachmentForm.attachments.length">Upload</Button>
          </div>
        </form>
      </div>
    </section>
  </AdminLayout>
</template>
