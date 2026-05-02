<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import {
  Building2,
  CheckSquare,
  Clock3,
  Filter,
  Plus,
  RefreshCw,
  Save,
  Search,
  SlidersHorizontal,
  Star,
  Tag,
  Trash2,
  UserRound,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Select from '@/Components/ui/select/Select.vue'
import Input from '@/Components/ui/input/Input.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import type { CategoryOption, Paginated, ProjectOption, SelectOption, TagOption, TrackerOption } from '@/types'

type TicketRow = {
  id: string
  ticket_number: number
  display_id: string
  url: string
  subject: string
  status: string
  priority: string
  company: string
  project?: string
  tracker?: string
  tags: TagOption[]
  assignee?: string
  created_at: string
  sla?: string | null
}
type SavedView = { id: string; name: string; filters: Record<string, string>; is_shared: boolean; is_default: boolean; owner?: string }

const props = defineProps<{
  tickets: Paginated<TicketRow>
  filters: { search?: string; queue?: string; status?: string; priority?: string; company?: string; project?: string; tracker?: string; category?: string; tag?: string }
  companies: { public_id: string; name: string }[]
  projects: ProjectOption[]
  trackers: TrackerOption[]
  categories: CategoryOption[]
  tags: TagOption[]
  statuses: SelectOption[]
  priorities: SelectOption[]
  agents: { id: string; name: string }[]
  savedViews: SavedView[]
}>()

const filterKeys = ['search', 'queue', 'status', 'priority', 'company', 'project', 'tracker', 'category', 'tag'] as const
const advancedFilterKeys = ['status', 'priority', 'company', 'project', 'tracker', 'category', 'tag'] as const
const queueTabs = [
  { label: 'All', value: '' },
  { label: 'Mine', value: 'mine' },
  { label: 'Unassigned', value: 'unassigned' },
  { label: 'Overdue', value: 'overdue' },
  { label: 'Due soon', value: 'due_soon' },
]

const filter = useForm({
  search: props.filters.search ?? '',
  queue: props.filters.queue ?? '',
  status: props.filters.status ?? '',
  priority: props.filters.priority ?? '',
  company: props.filters.company ?? '',
  project: props.filters.project ?? '',
  tracker: props.filters.tracker ?? '',
  category: props.filters.category ?? '',
  tag: props.filters.tag ?? '',
})

const selectedIds = ref<string[]>([])
const bulkTags = ref('')
const bulkForm = useForm({
  ticket_ids: [] as string[],
  status: '',
  priority: '',
  assigned_to_user_id: '',
  tag_names: [] as string[],
})
const viewForm = useForm({ name: '', filters: {}, is_shared: false, is_default: false })
const selectedView = ref('')
const advancedOpen = ref(advancedFilterKeys.some((key) => Boolean(props.filters[key])))

const allVisibleSelected = computed(() => props.tickets.data.length > 0 && props.tickets.data.every((ticket) => selectedIds.value.includes(ticket.id)))
const selectedSavedView = computed(() => props.savedViews.find((item) => item.id === selectedView.value))
const hasActiveFilters = computed(() => filterKeys.some((key) => Boolean(props.filters[key])))
const activeAdvancedCount = computed(() => advancedFilterKeys.filter((key) => Boolean(filter[key])).length)

const applyFilters = () => {
  router.get(route('admin.tickets.index'), filter.data(), { preserveState: true, replace: true })
}

const setQueue = (queue: string) => {
  filter.queue = queue
  applyFilters()
}

const resetFilters = () => {
  filterKeys.forEach((key) => {
    filter[key] = ''
  })
  applyFilters()
}

const applySavedView = () => {
  const view = props.savedViews.find((item) => item.id === selectedView.value)
  if (!view) return

  filterKeys.forEach((key) => {
    filter[key] = view.filters?.[key] ?? ''
  })
  advancedOpen.value = advancedFilterKeys.some((key) => Boolean(filter[key]))
  applyFilters()
}
const createSavedView = () => {
  viewForm.filters = filter.data()
  viewForm.post(route('admin.ticket-views.store'), { preserveScroll: true, onSuccess: () => viewForm.reset() })
}
const updateSavedView = () => {
  if (!selectedSavedView.value) return
  router.patch(route('admin.ticket-views.update', selectedSavedView.value.id), {
    filters: filter.data(),
    is_shared: selectedSavedView.value.is_shared,
    is_default: selectedSavedView.value.is_default,
  }, { preserveScroll: true })
}
const setDefaultView = () => {
  if (!selectedSavedView.value) return
  router.patch(route('admin.ticket-views.update', selectedSavedView.value.id), { is_default: true }, { preserveScroll: true })
}
const deleteSavedView = () => {
  if (!selectedSavedView.value) return
  router.delete(route('admin.ticket-views.destroy', selectedSavedView.value.id), {
    preserveScroll: true,
    onSuccess: () => { selectedView.value = '' },
  })
}
const toggleAllVisible = () => {
  selectedIds.value = allVisibleSelected.value ? [] : props.tickets.data.map((ticket) => ticket.id)
}
const runBulk = () => {
  bulkForm.ticket_ids = [...selectedIds.value]
  bulkForm.tag_names = bulkTags.value.split(',').map((tag) => tag.trim()).filter(Boolean)
  bulkForm.patch(route('admin.tickets.bulk'), {
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = []
      bulkTags.value = ''
    },
  })
}

const statusTone = (status: string) => status === 'closed' || status === 'resolved' ? 'green' : status === 'waiting_on_customer' || status === 'pending' ? 'amber' : 'blue'
const priorityTone = (priority: string) => priority === 'urgent' ? 'red' : priority === 'high' ? 'amber' : priority === 'normal' ? 'blue' : 'neutral'
const formatDate = (value: string) => {
  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}
const taxonomy = (ticket: TicketRow) => [ticket.project, ticket.tracker].filter(Boolean).join(' / ') || 'No project'

onMounted(() => {
  const defaultView = props.savedViews.find((view) => view.is_default)
  if (defaultView && !hasActiveFilters.value) {
    selectedView.value = defaultView.id
    applySavedView()
  }
})
</script>

<template>
  <AdminLayout title="Tickets">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold tracking-normal">Ticket operations</h2>
        <p class="mt-1 text-sm text-muted-foreground">Triage customer requests by queue, ownership, SLA risk, and taxonomy.</p>
      </div>
      <Link :href="route('admin.tickets.create')">
        <Button><Plus class="h-4 w-4" /> Create ticket</Button>
      </Link>
    </div>

    <div class="mt-4 flex gap-2 overflow-x-auto rounded-lg border bg-card p-1">
      <button
        v-for="tab in queueTabs"
        :key="tab.value || 'all'"
        type="button"
        class="inline-flex h-9 shrink-0 items-center rounded-md px-3 text-sm font-medium transition-colors"
        :class="filter.queue === tab.value ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:bg-secondary hover:text-foreground'"
        @click="setQueue(tab.value)"
      >
        {{ tab.label }}
      </button>
    </div>

    <Card class="mt-4">
      <CardContent class="space-y-4 p-4">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
          <div class="flex min-w-0 flex-1 flex-col gap-3 md:flex-row">
            <div class="relative min-w-0 flex-1">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input v-model="filter.search" class="pl-9" placeholder="Search #100001, subject, company" @keydown.enter.prevent="applyFilters" />
            </div>
            <div class="flex flex-wrap gap-2">
              <Button type="button" variant="secondary" @click="applyFilters">
                <Filter class="h-4 w-4" />
                Apply
              </Button>
              <Button type="button" variant="ghost" @click="advancedOpen = !advancedOpen">
                <SlidersHorizontal class="h-4 w-4" />
                Filters
                <Badge v-if="activeAdvancedCount" tone="blue">{{ activeAdvancedCount }}</Badge>
              </Button>
              <Button v-if="hasActiveFilters" type="button" variant="ghost" @click="resetFilters">Reset</Button>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <Select v-model="selectedView" class="w-48" @change="applySavedView">
              <option value="">Saved views</option>
              <option v-for="view in savedViews" :key="view.id" :value="view.id">{{ view.name }}{{ view.is_shared ? ' shared' : '' }}</option>
            </Select>
            <Input v-model="viewForm.name" class="w-44" placeholder="New view name" />
            <label class="flex items-center gap-2 text-xs text-muted-foreground"><Checkbox v-model="viewForm.is_shared" /> Share</label>
            <label class="flex items-center gap-2 text-xs text-muted-foreground"><Checkbox v-model="viewForm.is_default" /> Default</label>
            <Button size="sm" variant="secondary" :disabled="!viewForm.name" @click="createSavedView"><Save class="h-4 w-4" /> Save</Button>
            <Button size="sm" variant="ghost" :disabled="!selectedSavedView" @click="updateSavedView"><RefreshCw class="h-4 w-4" /></Button>
            <Button size="sm" variant="ghost" :disabled="!selectedSavedView || selectedSavedView.is_default" @click="setDefaultView"><Star class="h-4 w-4" /></Button>
            <Button size="sm" variant="ghost" :disabled="!selectedSavedView" @click="deleteSavedView"><Trash2 class="h-4 w-4" /></Button>
          </div>
        </div>

        <div v-if="advancedOpen" class="grid gap-3 border-t pt-4 md:grid-cols-2 xl:grid-cols-7">
          <Select v-model="filter.status" @change="applyFilters">
            <option value="">All statuses</option>
            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
          </Select>
          <Select v-model="filter.priority" @change="applyFilters">
            <option value="">All priorities</option>
            <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
          </Select>
          <Select v-model="filter.company" @change="applyFilters">
            <option value="">All companies</option>
            <option v-for="company in companies" :key="company.public_id" :value="company.public_id">{{ company.name }}</option>
          </Select>
          <Select v-model="filter.project" @change="applyFilters">
            <option value="">All projects</option>
            <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
          </Select>
          <Select v-model="filter.tracker" @change="applyFilters">
            <option value="">All trackers</option>
            <option v-for="tracker in trackers" :key="tracker.id" :value="tracker.id">{{ tracker.name }}</option>
          </Select>
          <Select v-model="filter.category" @change="applyFilters">
            <option value="">All categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
          </Select>
          <Select v-model="filter.tag" @change="applyFilters">
            <option value="">All tags</option>
            <option v-for="tag in tags" :key="tag.id" :value="tag.id">{{ tag.name }}</option>
          </Select>
        </div>
      </CardContent>
    </Card>

    <div v-if="selectedIds.length" class="sticky top-20 z-20 mt-4 rounded-lg border border-primary/30 bg-card/95 p-3 shadow-panel backdrop-blur">
      <div class="flex flex-wrap items-center gap-3">
        <Badge tone="blue"><CheckSquare class="mr-1 h-3.5 w-3.5" />{{ selectedIds.length }} selected</Badge>
        <Select v-model="bulkForm.status" class="w-44">
          <option value="">Status</option>
          <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
        </Select>
        <Select v-model="bulkForm.priority" class="w-44">
          <option value="">Priority</option>
          <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
        </Select>
        <Select v-model="bulkForm.assigned_to_user_id" class="w-52">
          <option value="">Assignment</option>
          <option value="">Unassigned</option>
          <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
        </Select>
        <Input v-model="bulkTags" class="w-64" placeholder="Tags, comma separated" />
        <Button :disabled="bulkForm.processing" @click="runBulk">Apply bulk action</Button>
      </div>
    </div>

    <Card class="mt-4 overflow-hidden">
      <div class="flex items-center justify-between border-b bg-muted/30 px-4 py-3">
        <label class="flex items-center gap-2 text-sm font-medium">
          <Checkbox :model-value="allVisibleSelected" @update:model-value="toggleAllVisible" />
          Select visible
        </label>
        <p class="text-sm text-muted-foreground">{{ tickets.data.length }} visible</p>
      </div>

      <div v-if="tickets.data.length" class="divide-y">
        <div
          v-for="ticket in tickets.data"
          :key="ticket.id"
          class="group grid gap-3 p-4 transition-colors hover:bg-secondary/40 lg:grid-cols-[32px_minmax(0,1.6fr)_minmax(180px,0.7fr)_minmax(180px,0.7fr)_auto]"
        >
          <div class="pt-1">
            <Checkbox v-model="selectedIds" :value="ticket.id" />
          </div>

          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <Link :href="ticket.url" class="text-xs font-semibold text-primary transition-colors hover:text-accent">{{ ticket.display_id }}</Link>
              <Badge v-if="ticket.sla === 'breached'" tone="red">SLA breached</Badge>
              <Badge :tone="statusTone(ticket.status)">{{ ticket.status }}</Badge>
              <Badge :tone="priorityTone(ticket.priority)">{{ ticket.priority }}</Badge>
              <Badge v-for="tagItem in ticket.tags" :key="tagItem.name" tone="neutral">
                <Tag class="mr-1 h-3 w-3" />
                {{ tagItem.name }}
              </Badge>
            </div>
            <Link :href="ticket.url" class="mt-1 block truncate text-base font-semibold text-foreground transition-colors group-hover:text-primary">
              {{ ticket.subject }}
            </Link>
            <div class="mt-2 flex flex-wrap gap-1.5">
              <Badge v-if="ticket.tracker" tone="neutral">{{ ticket.tracker }}</Badge>
            </div>
          </div>

          <div class="min-w-0 text-sm">
            <div class="flex items-center gap-2 text-muted-foreground">
              <Building2 class="h-4 w-4 shrink-0" />
              <span class="truncate">{{ ticket.company }}</span>
            </div>
            <p class="mt-1 truncate text-xs text-muted-foreground">{{ taxonomy(ticket) }}</p>
          </div>

          <div class="min-w-0 text-sm">
            <div class="flex items-center gap-2 text-muted-foreground">
              <UserRound class="h-4 w-4 shrink-0" />
              <span class="truncate">{{ ticket.assignee || 'Unassigned' }}</span>
            </div>
            <div class="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
              <Clock3 class="h-3.5 w-3.5" />
              {{ formatDate(ticket.created_at) }}
            </div>
          </div>

          <div class="flex items-center justify-start lg:justify-end">
            <Link :href="ticket.url">
              <Button variant="secondary" size="sm">Open</Button>
            </Link>
          </div>
        </div>
      </div>

      <div v-else class="p-6">
        <EmptyState title="No tickets found" />
      </div>
    </Card>

    <div class="mt-4">
      <Pagination :links="tickets.links" />
    </div>
  </AdminLayout>
</template>
