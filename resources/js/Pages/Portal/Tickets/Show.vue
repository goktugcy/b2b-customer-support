<script setup lang="ts">
import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, X } from 'lucide-vue-next'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Select from '@/Components/ui/select/Select.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import AttachmentList from '@/Components/shared/AttachmentList.vue'
import FilePicker from '@/Components/shared/FilePicker.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import RichContent from '@/Components/shared/RichContent.vue'
import RichTextEditor from '@/Components/shared/RichTextEditor.vue'
import type { SelectOption } from '@/types'

type Attachment = { id: string; filename: string; size: number; url: string }
type Person = { id: string; name: string; side?: string }

type Ticket = {
  id: string
  subject: string
  description: string
  status: string
  priority: string
  project?: string
  tracker?: string
  category?: string
  tags: { id: string; name: string; color?: string }[]
  custom_fields: { id: string; name: string; type: string; value: unknown }[]
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
    <Link :href="route('portal.tickets.index')" class="link inline-flex items-center gap-2 text-sm">
      <ArrowLeft class="h-4 w-4" />
      Back to tickets
    </Link>
    <section class="mt-4 grid gap-6 lg:grid-cols-[1fr_340px]">
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-3">
              <CardTitle class="text-xl">{{ ticket.subject }}</CardTitle>
              <div class="flex gap-2"><Badge tone="blue">{{ ticket.status }}</Badge><Badge>{{ ticket.priority }}</Badge></div>
            </div>
          </CardHeader>
          <CardContent>
            <div class="mb-3 flex flex-wrap gap-2">
              <Badge v-if="ticket.tracker" tone="neutral">{{ ticket.tracker }}</Badge>
              <Badge v-if="ticket.category" tone="neutral">{{ ticket.category }}</Badge>
              <Badge v-for="tag in ticket.tags" :key="tag.id" tone="neutral">{{ tag.name }}</Badge>
            </div>
            <RichContent :html="ticket.description" />
            <AttachmentList :attachments="ticket.attachments" />
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">Conversation</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="comment in ticket.comments" :key="comment.id" class="rounded-md border bg-background p-3">
                <p class="text-sm font-medium">{{ comment.author || 'Support' }}</p>
                <RichContent class="mt-2" :html="comment.body" />
                <AttachmentList :attachments="comment.attachments" />
              </div>
            </div>
            <form class="mt-5 space-y-3" @submit.prevent="submit">
              <Label>Reply</Label>
              <RichTextEditor v-model="form.body" placeholder="Write a reply" />
              <FieldError :message="form.errors.body" />
              <FilePicker v-model="form.attachments" />
              <Button type="submit" :disabled="form.processing">Add reply</Button>
            </form>
          </CardContent>
        </Card>
      </div>

      <div class="space-y-4">
        <Card>
          <CardHeader><CardTitle class="text-sm">Ticket details</CardTitle></CardHeader>
          <CardContent>
            <dl class="space-y-3 text-sm">
              <div><dt class="text-muted-foreground">Assignee</dt><dd class="font-medium">{{ ticket.assignee || 'Unassigned' }}</dd></div>
              <div><dt class="text-muted-foreground">Project</dt><dd class="font-medium">{{ ticket.project || '-' }}</dd></div>
              <div><dt class="text-muted-foreground">Tracker</dt><dd class="font-medium">{{ ticket.tracker || '-' }}</dd></div>
              <div v-if="ticket.category"><dt class="text-muted-foreground">Category</dt><dd class="font-medium">{{ ticket.category }}</dd></div>
              <div><dt class="text-muted-foreground">Status</dt><dd class="font-medium">{{ ticket.status }}</dd></div>
              <div><dt class="text-muted-foreground">Priority</dt><dd class="font-medium">{{ ticket.priority }}</dd></div>
              <div v-for="field in ticket.custom_fields" :key="field.id">
                <dt class="text-muted-foreground">{{ field.name }}</dt>
                <dd class="font-medium">{{ Array.isArray(field.value) ? field.value.join(', ') : field.value || '-' }}</dd>
              </div>
            </dl>
          </CardContent>
        </Card>

        <Card v-if="transitions.length">
          <CardHeader><CardTitle class="text-sm">Status</CardTitle></CardHeader>
          <CardContent>
            <form @submit.prevent="changeStatus">
              <Select v-model="statusForm.status">
                <option v-for="status in transitions" :key="status.value" :value="status.value">{{ status.label }}</option>
              </Select>
              <Button type="submit" class="mt-3 w-full">Update status</Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">Targets</CardTitle></CardHeader>
          <CardContent>
            <div class="flex flex-wrap gap-2">
              <Badge v-for="department in ticket.targets.departments" :key="department.id" tone="blue">{{ department.name }}</Badge>
              <Badge v-for="user in ticket.targets.users" :key="user.id" tone="neutral">{{ user.name }}</Badge>
            </div>
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
                <option value="">Add watcher</option>
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
              <FilePicker v-model="attachmentForm.attachments" />
              <Button type="submit" class="w-full" variant="secondary" :disabled="!attachmentForm.attachments.length">Upload</Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </section>
  </PortalLayout>
</template>
