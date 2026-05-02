<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import { Plus, Save, Search, Star, Trash2, RefreshCw } from 'lucide-vue-next'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Select from '@/Components/ui/select/Select.vue'
import Input from '@/Components/ui/input/Input.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import Table from '@/Components/ui/table/Table.vue'
import TableBody from '@/Components/ui/table/TableBody.vue'
import TableCell from '@/Components/ui/table/TableCell.vue'
import TableHead from '@/Components/ui/table/TableHead.vue'
import TableHeader from '@/Components/ui/table/TableHeader.vue'
import TableRow from '@/Components/ui/table/TableRow.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import type { Paginated, ProjectOption, SelectOption, TagOption, TrackerOption } from '@/types'

type TicketRow = {
  id: string
  ticket_number: number
  display_id: string
  url: string
  subject: string
  status: string
  priority: string
  project?: string
  tracker?: string
  tags: string[]
  assignee?: string
  created_at: string
  sla?: string | null
}
type SavedView = { id: string; name: string; filters: Record<string, string>; is_shared: boolean; is_default: boolean; owner?: string }

const props = defineProps<{
  tickets: Paginated<TicketRow>
  filters: { search?: string; queue?: string; status?: string; project?: string; tracker?: string; tag?: string }
  statuses: SelectOption[]
  projects: ProjectOption[]
  trackers: TrackerOption[]
  tags: TagOption[]
  savedViews: SavedView[]
}>()

const filter = useForm({
  search: props.filters.search ?? '',
  queue: props.filters.queue ?? '',
  status: props.filters.status ?? '',
  project: props.filters.project ?? '',
  tracker: props.filters.tracker ?? '',
  tag: props.filters.tag ?? '',
})
const applyFilters = () => router.get(route('portal.tickets.index'), filter.data(), { preserveState: true, replace: true })

const selectedView = ref('')
const viewForm = useForm({ name: '', filters: {}, is_shared: false, is_default: false })
const filterKeys = ['search', 'queue', 'status', 'project', 'tracker', 'tag'] as const
const selectedSavedView = computed(() => props.savedViews.find((item) => item.id === selectedView.value))
const hasActiveFilters = computed(() => filterKeys.some((key) => Boolean(props.filters[key])))
const applySavedView = () => {
  const view = props.savedViews.find((item) => item.id === selectedView.value)
  if (!view) return

  filterKeys.forEach((key) => {
    filter[key] = view.filters?.[key] ?? ''
  })
  applyFilters()
}
const createSavedView = () => {
  viewForm.filters = filter.data()
  viewForm.post(route('portal.ticket-views.store'), { preserveScroll: true, onSuccess: () => viewForm.reset() })
}
const updateSavedView = () => {
  if (!selectedSavedView.value) return
  router.patch(route('portal.ticket-views.update', selectedSavedView.value.id), {
    filters: filter.data(),
    is_shared: selectedSavedView.value.is_shared,
    is_default: selectedSavedView.value.is_default,
  }, { preserveScroll: true })
}
const setDefaultView = () => {
  if (!selectedSavedView.value) return
  router.patch(route('portal.ticket-views.update', selectedSavedView.value.id), { is_default: true }, { preserveScroll: true })
}
const deleteSavedView = () => {
  if (!selectedSavedView.value) return
  router.delete(route('portal.ticket-views.destroy', selectedSavedView.value.id), {
    preserveScroll: true,
    onSuccess: () => { selectedView.value = '' },
  })
}

onMounted(() => {
  const defaultView = props.savedViews.find((view) => view.is_default)
  if (defaultView && !hasActiveFilters.value) {
    selectedView.value = defaultView.id
    applySavedView()
  }
})
</script>

<template>
  <PortalLayout title="Tickets">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold tracking-normal">Company tickets</h2>
        <p class="mt-1 text-sm text-muted-foreground">Search and follow requests across your workspace.</p>
      </div>
      <Link :href="route('portal.tickets.create')">
        <Button><Plus class="h-4 w-4" /> Create ticket</Button>
      </Link>
    </div>

    <Card class="mt-4">
      <CardContent class="p-4">
        <div class="mb-3 flex flex-wrap items-center justify-end gap-2">
          <Select v-model="selectedView" class="w-48" @change="applySavedView">
            <option value="">Saved views</option>
            <option v-for="view in savedViews" :key="view.id" :value="view.id">{{ view.name }}{{ view.is_shared ? ' shared' : '' }}</option>
          </Select>
          <Input v-model="viewForm.name" class="w-48" placeholder="New view name" />
          <label class="flex items-center gap-2 text-xs text-muted-foreground"><Checkbox v-model="viewForm.is_shared" /> Share</label>
          <label class="flex items-center gap-2 text-xs text-muted-foreground"><Checkbox v-model="viewForm.is_default" /> Default</label>
          <Button size="sm" variant="secondary" :disabled="!viewForm.name" @click="createSavedView"><Save class="h-4 w-4" /> Save view</Button>
          <Button size="sm" variant="secondary" :disabled="!selectedSavedView" @click="updateSavedView"><RefreshCw class="h-4 w-4" /> Update</Button>
          <Button size="sm" variant="secondary" :disabled="!selectedSavedView || selectedSavedView.is_default" @click="setDefaultView"><Star class="h-4 w-4" /> Default</Button>
          <Button size="sm" variant="ghost" :disabled="!selectedSavedView" @click="deleteSavedView"><Trash2 class="h-4 w-4" /></Button>
        </div>
        <div class="grid gap-3 md:grid-cols-6">
          <div class="relative md:col-span-2">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="filter.search" class="pl-9" placeholder="Search #100001 or subject" @keydown.enter.prevent="applyFilters" />
          </div>
          <Select v-model="filter.queue" @change="applyFilters">
            <option value="">All queues</option>
            <option value="mine">My tickets</option>
            <option value="unassigned">Unassigned</option>
            <option value="overdue">Overdue</option>
            <option value="due_soon">Due soon</option>
          </Select>
          <Select v-model="filter.status" @change="applyFilters">
            <option value="">All statuses</option>
            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
          </Select>
          <Select v-model="filter.project" @change="applyFilters">
            <option value="">All projects</option>
            <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
          </Select>
          <Select v-model="filter.tracker" @change="applyFilters">
            <option value="">All trackers</option>
            <option v-for="tracker in trackers" :key="tracker.id" :value="tracker.id">{{ tracker.name }}</option>
          </Select>
          <Select v-model="filter.tag" @change="applyFilters">
            <option value="">All tags</option>
            <option v-for="tag in tags" :key="tag.id" :value="tag.id">{{ tag.name }}</option>
          </Select>
        </div>
      </CardContent>
    </Card>

    <Card class="mt-4 overflow-hidden">
      <CardContent class="p-0">
        <Table class="table-fixed">
          <TableHeader class="bg-muted/50">
            <TableRow>
              <TableHead class="w-[44%]">Ticket</TableHead>
              <TableHead>Project</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Priority</TableHead>
              <TableHead>Assignee</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody v-if="tickets.data.length">
            <TableRow v-for="ticket in tickets.data" :key="ticket.id">
              <TableCell>
                <Link :href="ticket.url" class="group inline-flex min-w-0 flex-col transition-colors hover:text-primary">
                  <span class="text-xs font-semibold text-primary">{{ ticket.display_id }}</span>
                  <span class="truncate font-medium text-foreground group-hover:text-primary">{{ ticket.subject }}</span>
                </Link>
                <div class="mt-2 flex flex-wrap gap-1">
                  <Badge v-if="ticket.tracker" tone="neutral">{{ ticket.tracker }}</Badge>
                  <Badge v-if="ticket.sla === 'breached'" tone="red">SLA breached</Badge>
                </div>
              </TableCell>
              <TableCell class="text-muted-foreground">{{ ticket.project || '-' }}</TableCell>
              <TableCell>
                <div class="flex flex-wrap items-center gap-1.5">
                  <Badge tone="blue">{{ ticket.status }}</Badge>
                </div>
              </TableCell>
              <TableCell>
                <div class="flex flex-wrap items-center gap-1.5">
                  <span class="text-muted-foreground">{{ ticket.priority }}</span>
                  <Badge v-for="tag in ticket.tags" :key="tag" tone="neutral">{{ tag }}</Badge>
                </div>
              </TableCell>
              <TableCell class="text-muted-foreground">{{ ticket.assignee || 'Unassigned' }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <div v-if="!tickets.data.length" class="p-4"><EmptyState title="No tickets yet" /></div>
      </CardContent>
    </Card>
    <div class="mt-4"><Pagination :links="tickets.links" /></div>
  </PortalLayout>
</template>
