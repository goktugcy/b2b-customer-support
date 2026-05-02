<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { ArrowLeft, X } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Select from '@/Components/ui/select/Select.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import AttachmentList from '@/Components/shared/AttachmentList.vue'
import FilePicker from '@/Components/shared/FilePicker.vue'
import CollapsibleMetaBox from '@/Components/shared/CollapsibleMetaBox.vue'
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
  ticket_number: number
  display_id: string
  route_params: { company: string; ticket: number }
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
  csat: { latest_rating?: number | null; average_rating?: number | null; responses_count: number }
}
type CannedResponse = { id: string; title: string; shortcut?: string | null; body: string }

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
  cannedResponses: CannedResponse[]
  mentionableUsers: { public: MultiSelectOption[]; internal: MultiSelectOption[] }
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
const commentForm = useForm({ body: '', visibility: 'public', mentioned_user_ids: [] as string[], attachments: [] as File[] })
const targetForm = useForm({
  target_department_ids: props.ticket.targets.departments.map((department) => department.id),
  target_user_ids: props.ticket.targets.users.map((user) => user.id),
})
const watcherForm = useForm({ user_id: '' })
const attachmentForm = useForm({ visibility: 'public', attachments: [] as File[] })
const mergeForm = useForm({ target_ticket_id: '' })
const splitForm = useForm({ subject: `Follow-up: ${props.ticket.subject}`, comment_ids: [] as string[] })
const cannedResponseId = ref('')

const watcherOptions = computed(() => props.providerUsers.filter((user) => !props.ticket.watchers.some((watcher) => watcher.id === user.id)))
const targetErrors = computed(() => targetForm.errors.target_department_ids || targetForm.errors.target_user_ids || (targetForm.errors as Record<string, string | undefined>).targets)
const filteredCategories = computed(() => props.categories.filter((category) => category.project_id === editForm.project_id && category.status !== 'disabled'))
const selectedCustomFields = computed(() => props.customFields.filter((field) => field.tracker_id === editForm.tracker_id && field.status !== 'disabled'))
const commentAttachmentErrors = computed(() => commentForm.errors.attachments || Object.entries(commentForm.errors).find(([key]) => key.startsWith('attachments.'))?.[1])
const attachmentUploadError = ref('')
const currentMentionOptions = computed(() => commentForm.visibility === 'internal' ? props.mentionableUsers.internal : props.mentionableUsers.public)
const ticketRoute = computed(() => props.ticket.route_params)
const pageTitle = computed(() => `${props.ticket.display_id} · ${props.ticket.subject}`)

watch(() => editForm.project_id, () => {
  if (!filteredCategories.value.some((category) => category.id === editForm.category_id)) {
    editForm.category_id = ''
  }
})

const updateTicket = () => editForm.patch(route('admin.tickets.update', ticketRoute.value), { preserveScroll: true })
const changeStatus = () => statusForm.patch(route('admin.tickets.status', ticketRoute.value), { preserveScroll: true })
const assignTicket = () => assignForm.patch(route('admin.tickets.assignment', ticketRoute.value), { preserveScroll: true })
const updateTargets = () => targetForm.patch(route('admin.tickets.targets', ticketRoute.value), { preserveScroll: true })

const addComment = () => commentForm.post(route('admin.tickets.comments.store', ticketRoute.value), {
  preserveScroll: true,
  forceFormData: true,
  onSuccess: () => {
    commentForm.reset('body')
    commentForm.mentioned_user_ids = []
    commentForm.attachments = []
  },
})

const addWatcher = () => watcherForm.post(route('admin.tickets.watchers.store', ticketRoute.value), {
  preserveScroll: true,
  onSuccess: () => watcherForm.reset(),
})

const removeWatcher = (userId: string) => {
  router.delete(route('admin.tickets.watchers.destroy', { ...ticketRoute.value, user: userId }), { preserveScroll: true })
}

const applyCannedResponse = () => {
  const response = props.cannedResponses.find((item) => item.id === cannedResponseId.value)
  if (response) {
    commentForm.body = response.body
  }
}

const mergeTicket = () => mergeForm.post(route('admin.tickets.merge', ticketRoute.value), { preserveScroll: true })
const splitTicket = () => splitForm.post(route('admin.tickets.split', ticketRoute.value), { preserveScroll: true })

const uploadAttachments = () => {
  attachmentUploadError.value = ''

  attachmentForm.attachments.forEach((file) => {
    router.post(route('admin.tickets.attachments.store', ticketRoute.value), {
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
  <AdminLayout :title="pageTitle">
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
                <CardTitle class="text-xl">{{ ticket.display_id }} · {{ ticket.subject }}</CardTitle>
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
                  <div class="flex items-center gap-2">
                    <label class="flex items-center gap-1 text-xs text-muted-foreground"><Checkbox v-model="splitForm.comment_ids" :value="comment.id" /> Split</label>
                    <Badge :tone="comment.visibility === 'internal' ? 'amber' : 'green'">{{ comment.visibility }}</Badge>
                  </div>
                </div>
                <RichContent class="mt-2" :html="comment.body" />
                <AttachmentList :attachments="comment.attachments" />
              </div>
            </div>

            <form class="mt-5 space-y-3" @submit.prevent="addComment">
              <Label>Reply</Label>
              <Select v-model="cannedResponseId" @change="applyCannedResponse">
                <option value="">Canned response</option>
                <option v-for="response in cannedResponses" :key="response.id" :value="response.id">{{ response.shortcut ? `${response.shortcut} · ${response.title}` : response.title }}</option>
              </Select>
              <RichTextEditor v-model="commentForm.body" placeholder="Write a reply" />
              <FieldError :message="commentForm.errors.body" />
              <div class="grid gap-3 md:grid-cols-[180px_1fr]">
                <Select v-model="commentForm.visibility">
                  <option value="public">Public reply</option>
                  <option value="internal">Internal note</option>
                </Select>
                <MultiSelectCombobox v-model="commentForm.mentioned_user_ids" :options="currentMentionOptions" placeholder="Mention users" />
              </div>
              <div>
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
        <CollapsibleMetaBox title="Details">
            <form class="space-y-3" @submit.prevent="updateTicket">
              <div class="rounded-md border bg-muted/40 px-3 py-2">
                <p class="text-xs font-medium uppercase text-muted-foreground">Ticket ID</p>
                <p class="mt-1 text-sm font-semibold">{{ ticket.display_id }}</p>
              </div>
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
        </CollapsibleMetaBox>

        <CollapsibleMetaBox title="Status">
            <form @submit.prevent="changeStatus">
              <Select v-model="statusForm.status">
                <option v-for="status in transitions" :key="status.value" :value="status.value">{{ status.label }}</option>
              </Select>
              <Button type="submit" class="mt-3 w-full" :disabled="!transitions.length">Change status</Button>
            </form>
        </CollapsibleMetaBox>

        <CollapsibleMetaBox title="CSAT" :default-open="false">
            <p class="text-2xl font-semibold">{{ ticket.csat.average_rating ? Number(ticket.csat.average_rating).toFixed(1) : '-' }}</p>
            <p class="text-sm text-muted-foreground">{{ ticket.csat.responses_count }} response(s)</p>
            <p v-if="ticket.csat.latest_rating" class="mt-2 text-sm">Latest rating: {{ ticket.csat.latest_rating }}/5</p>
        </CollapsibleMetaBox>

        <CollapsibleMetaBox title="Merge / split" :default-open="false" content-class="space-y-4">
            <form class="space-y-2" @submit.prevent="mergeTicket">
              <Label>Merge into ticket ID</Label>
              <Input v-model="mergeForm.target_ticket_id" placeholder="#100001 or 100001" />
              <Button type="submit" class="w-full" variant="secondary" :disabled="!mergeForm.target_ticket_id">Merge ticket</Button>
            </form>
            <form class="space-y-2 border-t pt-4" @submit.prevent="splitTicket">
              <Label>Split selected comments</Label>
              <Input v-model="splitForm.subject" />
              <Button type="submit" class="w-full" variant="secondary" :disabled="!splitForm.comment_ids.length">Create split ticket</Button>
            </form>
        </CollapsibleMetaBox>

        <CollapsibleMetaBox title="Assignment">
            <form @submit.prevent="assignTicket">
              <Select v-model="assignForm.assigned_to_user_id">
                <option value="">Unassigned</option>
                <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
              </Select>
              <Button type="submit" class="mt-3 w-full" variant="secondary">Update assignment</Button>
            </form>
        </CollapsibleMetaBox>

        <CollapsibleMetaBox title="Targets" :default-open="false">
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
        </CollapsibleMetaBox>

        <CollapsibleMetaBox title="Watchers" :default-open="false">
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
        </CollapsibleMetaBox>

        <CollapsibleMetaBox title="Attachments" :default-open="false">
            <form class="space-y-3" @submit.prevent="uploadAttachments">
              <Select v-model="attachmentForm.visibility">
                <option value="public">Public</option>
                <option value="internal">Internal</option>
              </Select>
              <FilePicker v-model="attachmentForm.attachments" :error="attachmentUploadError" />
              <Button type="submit" class="w-full" variant="secondary" :disabled="!attachmentForm.attachments.length">Upload</Button>
            </form>
        </CollapsibleMetaBox>
      </div>
    </section>
  </AdminLayout>
</template>
