<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { ArrowLeft, X } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Select from '@/Components/ui/select/Select.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import AttachmentList from '@/Components/shared/AttachmentList.vue'
import FilePicker from '@/Components/shared/FilePicker.vue'
import MultiSelectCombobox from '@/Components/shared/MultiSelectCombobox.vue'
import RichContent from '@/Components/shared/RichContent.vue'
import RichTextEditor from '@/Components/shared/RichTextEditor.vue'
import TagPicker from '@/Components/shared/TagPicker.vue'
import CustomFieldForm from '@/Components/shared/CustomFieldForm.vue'
import type { CategoryOption, CustomFieldDefinition, CustomFieldValues, MultiSelectOption, ProjectOption, SelectOption, TagOption, TrackerOption } from '@/types'

type Attachment = { id: string; filename: string; size: number; visibility?: string; url: string }
type Person = { id: string; name: string; side?: string }

type TicketDetail = {
  id: string
  subject: string
  description: string
  status: string
  priority: string
  company: string
  project?: string
  project_id?: string
  tracker?: string
  tracker_id?: string
  category?: string
  category_id?: string
  tags: TagOption[]
  custom_fields: { id: string; name: string; type: string; value: unknown }[]
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
  projects: ProjectOption[]
  trackers: TrackerOption[]
  categories: CategoryOption[]
  tags: TagOption[]
  customFields: CustomFieldDefinition[]
}>()

const editForm = useForm({
  subject: props.ticket.subject,
  priority: props.ticket.priority,
  project_id: props.ticket.project_id ?? '',
  tracker_id: props.ticket.tracker_id ?? '',
  category_id: props.ticket.category_id ?? '',
  tag_names: props.ticket.tags.map((tag) => tag.name),
  custom_fields: Object.fromEntries(props.ticket.custom_fields.map((field) => [field.id, field.value])) as CustomFieldValues,
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
const filteredCategories = computed(() => props.categories.filter((category) => category.project_id === editForm.project_id && category.status !== 'disabled'))
const selectedCustomFields = computed(() => props.customFields.filter((field) => field.tracker_id === editForm.tracker_id && field.status !== 'disabled'))
const commentAttachmentErrors = computed(() => commentForm.errors.attachments || Object.entries(commentForm.errors).find(([key]) => key.startsWith('attachments.'))?.[1])
const attachmentUploadError = ref('')

watch(() => editForm.project_id, () => {
  if (!filteredCategories.value.some((category) => category.id === editForm.category_id)) {
    editForm.category_id = ''
  }
})

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
  attachmentUploadError.value = ''

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
      onError: (errors) => {
        attachmentUploadError.value = errors.file ?? Object.values(errors)[0] ?? 'Upload failed.'
      },
    })
  })
}
</script>

<template>
  <AdminLayout :title="ticket.subject">
    <div class="mb-2">
      <Link :href="route('admin.tickets.index')" class="link inline-flex items-center gap-2 text-sm">
        <ArrowLeft class="h-4 w-4" />
        Back to tickets
      </Link>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1fr_380px]">
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <CardTitle class="text-xl">{{ ticket.subject }}</CardTitle>
                <p class="mt-1 text-sm text-muted-foreground">{{ ticket.company }} · {{ ticket.project || 'General' }} · {{ ticket.requester || 'No requester' }}</p>
              </div>
              <div class="flex gap-2">
                <Badge tone="blue">{{ ticket.status }}</Badge>
                <Badge tone="neutral">{{ ticket.priority }}</Badge>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div class="mb-3 flex flex-wrap gap-2">
              <Badge v-if="ticket.tracker" tone="neutral">{{ ticket.tracker }}</Badge>
              <Badge v-if="ticket.category" tone="neutral">{{ ticket.category }}</Badge>
              <Badge v-for="tag in ticket.tags" :key="tag.name" tone="neutral">{{ tag.name }}</Badge>
            </div>
            <RichContent :html="ticket.description" />
            <AttachmentList :attachments="ticket.attachments" />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm">Conversation</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="comment in ticket.comments" :key="comment.id" class="rounded-md border bg-background/70 p-3">
                <div class="flex items-center justify-between gap-2">
                  <p class="text-sm font-medium">{{ comment.author || 'System' }}</p>
                  <Badge :tone="comment.visibility === 'internal' ? 'amber' : 'green'">{{ comment.visibility }}</Badge>
                </div>
                <RichContent class="mt-2" :html="comment.body" />
                <AttachmentList :attachments="comment.attachments" />
              </div>
            </div>

            <form class="mt-5 space-y-3" @submit.prevent="addComment">
              <Label>Reply</Label>
              <RichTextEditor v-model="commentForm.body" placeholder="Write a reply" />
              <FieldError :message="commentForm.errors.body" />
              <div class="grid gap-3 md:grid-cols-[180px_1fr]">
                <Select v-model="commentForm.visibility">
                  <option value="public">Public reply</option>
                  <option value="internal">Internal note</option>
                </Select>
                <FilePicker v-model="commentForm.attachments" :error="commentAttachmentErrors" />
              </div>
              <div class="flex justify-end">
                <Button type="submit" :disabled="commentForm.processing">Add comment</Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm">Timeline</CardTitle>
          </CardHeader>
          <CardContent>
            <ol class="space-y-3">
              <li v-for="event in ticket.events" :key="event.id" class="border-l-2 border-border pl-3">
                <p class="text-sm font-medium">{{ event.type }}</p>
                <p class="text-xs text-muted-foreground">{{ event.actor || 'System' }} · {{ event.occurred_at }}</p>
              </li>
            </ol>
          </CardContent>
        </Card>
      </div>

      <div class="space-y-4 xl:sticky xl:top-20 xl:self-start">
        <Card>
          <CardHeader><CardTitle class="text-sm">Details</CardTitle></CardHeader>
          <CardContent>
            <form class="space-y-3" @submit.prevent="updateTicket">
              <div>
                <Label>Subject</Label>
                <Input v-model="editForm.subject" class="mt-1" />
              </div>
              <div>
                <Label>Project</Label>
                <Select v-model="editForm.project_id" class="mt-1">
                  <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                </Select>
                <FieldError :message="editForm.errors.project_id" />
              </div>
              <div>
                <Label>Tracker</Label>
                <Select v-model="editForm.tracker_id" class="mt-1">
                  <option v-for="tracker in trackers" :key="tracker.id" :value="tracker.id">{{ tracker.name }}</option>
                </Select>
                <FieldError :message="editForm.errors.tracker_id" />
              </div>
              <div>
                <Label>Category</Label>
                <Select v-model="editForm.category_id" class="mt-1">
                  <option value="">No category</option>
                  <option v-for="category in filteredCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </Select>
              </div>
              <div>
                <Label>Priority</Label>
                <Select v-model="editForm.priority" class="mt-1">
                  <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
                </Select>
              </div>
              <div>
                <Label>Tags</Label>
                <TagPicker v-model="editForm.tag_names" class="mt-1" :options="tags" />
              </div>
              <CustomFieldForm
                v-model="editForm.custom_fields"
                :fields="selectedCustomFields"
                :projects="projects"
                :categories="filteredCategories"
                :users="providerUsers"
                :errors="editForm.errors"
              />
              <Button type="submit" variant="secondary" class="w-full">Save details</Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">Status</CardTitle></CardHeader>
          <CardContent>
            <form @submit.prevent="changeStatus">
              <Select v-model="statusForm.status">
                <option v-for="status in transitions" :key="status.value" :value="status.value">{{ status.label }}</option>
              </Select>
              <Button type="submit" class="mt-3 w-full" :disabled="!transitions.length">Change status</Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">Assignment</CardTitle></CardHeader>
          <CardContent>
            <form @submit.prevent="assignTicket">
              <Select v-model="assignForm.assigned_to_user_id">
                <option value="">Unassigned</option>
                <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
              </Select>
              <Button type="submit" class="mt-3 w-full" variant="secondary">Update assignment</Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">Targets</CardTitle></CardHeader>
          <CardContent>
            <form class="space-y-3" @submit.prevent="updateTargets">
              <div>
                <Label>Departments</Label>
                <MultiSelectCombobox v-model="targetForm.target_department_ids" class="mt-1" :options="departments" placeholder="Select departments" />
              </div>
              <div>
                <Label>Provider users</Label>
                <MultiSelectCombobox v-model="targetForm.target_user_ids" class="mt-1" :options="providerUsers" placeholder="Select provider users" />
              </div>
              <FieldError :message="targetErrors" />
              <Button type="submit" variant="secondary" class="w-full">Save targets</Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">Watchers</CardTitle></CardHeader>
          <CardContent>
            <form @submit.prevent="addWatcher">
              <div class="flex flex-wrap gap-2">
                <Badge v-for="watcher in ticket.watchers" :key="watcher.id" tone="neutral" class="gap-2">
                  {{ watcher.name }}
                  <button type="button" class="text-muted-foreground transition-colors hover:text-foreground" @click="removeWatcher(watcher.id)">
                    <X class="h-3 w-3" />
                  </button>
                </Badge>
              </div>
              <Select v-model="watcherForm.user_id" class="mt-4">
                <option value="">Add provider watcher</option>
                <option v-for="user in watcherOptions" :key="user.id" :value="user.id">{{ user.name }}</option>
              </Select>
              <FieldError :message="watcherForm.errors.user_id" />
              <Button type="submit" class="mt-3 w-full" variant="secondary" :disabled="!watcherForm.user_id">Add watcher</Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">Attachments</CardTitle></CardHeader>
          <CardContent>
            <form class="space-y-3" @submit.prevent="uploadAttachments">
              <Select v-model="attachmentForm.visibility">
                <option value="public">Public</option>
                <option value="internal">Internal</option>
              </Select>
              <FilePicker v-model="attachmentForm.attachments" :error="attachmentUploadError" />
              <Button type="submit" class="w-full" variant="secondary" :disabled="!attachmentForm.attachments.length">Upload</Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </section>
  </AdminLayout>
</template>
