<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import { Clock3, Plus, RefreshCw, Save, Search, Star, Tag, Trash2, UserRound } from 'lucide-vue-next'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Select from '@/Components/ui/select/Select.vue'
import Input from '@/Components/ui/input/Input.vue'
import Tabs from '@/Components/ui/tabs/Tabs.vue'
import TabsList from '@/Components/ui/tabs/TabsList.vue'
import TabsTrigger from '@/Components/ui/tabs/TabsTrigger.vue'
import DropdownMenu from '@/Components/ui/dropdown-menu/DropdownMenu.vue'
import DropdownMenuContent from '@/Components/ui/dropdown-menu/DropdownMenuContent.vue'
import DropdownMenuItem from '@/Components/ui/dropdown-menu/DropdownMenuItem.vue'
import DropdownMenuLabel from '@/Components/ui/dropdown-menu/DropdownMenuLabel.vue'
import DropdownMenuSeparator from '@/Components/ui/dropdown-menu/DropdownMenuSeparator.vue'
import DropdownMenuTrigger from '@/Components/ui/dropdown-menu/DropdownMenuTrigger.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import DataToolbar from '@/Components/shared/DataToolbar.vue'
import FilterSheet from '@/Components/shared/FilterSheet.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import PriorityBadge from '@/Components/shared/PriorityBadge.vue'
import ResponsiveList from '@/Components/shared/ResponsiveList.vue'
import SlaBadge from '@/Components/shared/SlaBadge.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'
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
const applyFiltersAndClose = () => {
  advancedOpen.value = false
  applyFilters()
}

const selectedView = ref('')
const advancedOpen = ref(false)
const viewForm = useForm({ name: '', filters: {}, is_shared: false, is_default: false })
const filterKeys = ['search', 'queue', 'status', 'project', 'tracker', 'tag'] as const
const advancedFilterKeys = ['status', 'project', 'tracker', 'tag'] as const
const queueTabs = [
  { label: 'All', value: '' },
  { label: 'Mine', value: 'mine' },
  { label: 'Unassigned', value: 'unassigned' },
  { label: 'Overdue', value: 'overdue' },
  { label: 'Due soon', value: 'due_soon' },
]
const selectedSavedView = computed(() => props.savedViews.find((item) => item.id === selectedView.value))
const hasActiveFilters = computed(() => filterKeys.some((key) => Boolean(props.filters[key])))
const activeAdvancedCount = computed(() => advancedFilterKeys.filter((key) => Boolean(filter[key])).length)
const setQueue = (queue: string) => {
  filter.queue = queue
  applyFilters()
}
const resetFilters = () => {
  filterKeys.forEach((key) => {
    filter[key] = ''
  })
  advancedOpen.value = false
  applyFilters()
}
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

const formatDate = (value: string) => {
  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? value
    : new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}
const taxonomy = (ticket: TicketRow) => [ticket.project, ticket.tracker].filter(Boolean).join(' / ') || 'No project'
</script>

<template>
  <PortalLayout title="Tickets">
    <PageHeader
      title="Company tickets"
      description="Search and follow requests across your workspace."
      eyebrow="Workspace"
    >
      <template #actions>
        <Link :href="route('portal.tickets.create')">
          <Button><Plus class="h-4 w-4" /> Create ticket</Button>
        </Link>
      </template>
    </PageHeader>

    <Tabs :model-value="filter.queue" @update:model-value="setQueue">
      <TabsList class="w-full justify-start overflow-x-auto">
        <TabsTrigger v-for="tab in queueTabs" :key="tab.value || 'all'" :value="tab.value">
          {{ tab.label }}
        </TabsTrigger>
      </TabsList>
    </Tabs>

    <DataToolbar>
      <div class="relative min-w-0 flex-1">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input v-model="filter.search" class="pl-9" placeholder="Search #100001 or subject" @keydown.enter.prevent="applyFilters" />
      </div>
      <Button type="button" variant="secondary" @click="applyFilters">
        <Search class="h-4 w-4" />
        Search
      </Button>
      <FilterSheet
        v-model:open="advancedOpen"
        :count="activeAdvancedCount"
        title="Ticket filters"
        description="Filter by workflow state, project, tracker, and tags."
        @apply="applyFiltersAndClose"
        @reset="resetFilters"
      >
        <div class="grid gap-4">
          <Select v-model="filter.status">
            <option value="">All statuses</option>
            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
          </Select>
          <Select v-model="filter.project">
            <option value="">All projects</option>
            <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
          </Select>
          <Select v-model="filter.tracker">
            <option value="">All trackers</option>
            <option v-for="tracker in trackers" :key="tracker.id" :value="tracker.id">{{ tracker.name }}</option>
          </Select>
          <Select v-model="filter.tag">
            <option value="">All tags</option>
            <option v-for="tagOption in tags" :key="tagOption.id" :value="tagOption.id">{{ tagOption.name }}</option>
          </Select>
        </div>
      </FilterSheet>
      <Button v-if="hasActiveFilters" type="button" variant="ghost" @click="resetFilters">Reset</Button>

      <template #actions>
        <Select v-model="selectedView" class="w-48" @change="applySavedView">
          <option value="">Saved views</option>
          <option v-for="view in savedViews" :key="view.id" :value="view.id">{{ view.name }}{{ view.is_shared ? ' shared' : '' }}</option>
        </Select>
        <details class="relative">
          <summary class="list-none">
            <Button type="button" variant="secondary">View actions</Button>
          </summary>
          <div class="absolute right-0 top-full z-50 mt-2 w-72 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-xl">
            <div class="px-2 py-1.5 text-xs font-semibold uppercase text-muted-foreground">Save current view</div>
            <div class="space-y-3 px-2 py-2">
              <Input v-model="viewForm.name" placeholder="New view name" />
              <div class="flex flex-wrap gap-3">
                <label class="flex items-center gap-2 text-xs text-muted-foreground"><Checkbox v-model="viewForm.is_shared" /> Share</label>
                <label class="flex items-center gap-2 text-xs text-muted-foreground"><Checkbox v-model="viewForm.is_default" /> Default</label>
              </div>
              <Button size="sm" class="w-full" :disabled="!viewForm.name" @click="createSavedView"><Save class="h-4 w-4" /> Save view</Button>
            </div>
            <div class="-mx-1 my-1 h-px bg-border" />
            <div class="rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-secondary">
              <button type="button" class="flex w-full items-center gap-2" :disabled="!selectedSavedView" @click="updateSavedView">
                <RefreshCw class="h-4 w-4" />
                Update selected view
              </button>
            </div>
            <div class="rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-secondary">
              <button type="button" class="flex w-full items-center gap-2" :disabled="!selectedSavedView || selectedSavedView.is_default" @click="setDefaultView">
                <Star class="h-4 w-4" />
                Set as default
              </button>
            </div>
            <div class="rounded-sm px-2 py-1.5 text-sm text-destructive transition-colors hover:bg-secondary">
              <button type="button" class="flex w-full items-center gap-2" :disabled="!selectedSavedView" @click="deleteSavedView">
                <Trash2 class="h-4 w-4" />
                Delete view
              </button>
            </div>
          </div>
        </details>
      </template>
    </DataToolbar>

    <ResponsiveList>
      <div class="flex items-center justify-between bg-muted/30 px-4 py-3">
        <p class="text-sm font-medium">Ticket inbox</p>
        <p class="text-sm text-muted-foreground">{{ tickets.data.length }} visible</p>
      </div>
      <div v-if="tickets.data.length" class="divide-y">
        <div
          v-for="ticket in tickets.data"
          :key="ticket.id"
          class="group grid gap-3 p-4 transition-colors hover:bg-secondary/40 lg:grid-cols-[minmax(0,1.55fr)_minmax(180px,0.75fr)_minmax(180px,0.75fr)_auto]"
        >
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <Link :href="ticket.url" class="text-xs font-semibold text-primary transition-colors hover:text-accent">{{ ticket.display_id }}</Link>
              <SlaBadge :value="ticket.sla" />
              <StatusBadge :status="ticket.status" />
              <PriorityBadge :priority="ticket.priority" />
              <Badge v-for="tag in ticket.tags" :key="tag" tone="neutral">
                <Tag class="mr-1 h-3 w-3" />
                {{ tag }}
              </Badge>
            </div>
            <Link :href="ticket.url" class="mt-1 block truncate text-base font-semibold text-foreground transition-colors group-hover:text-primary">
              {{ ticket.subject }}
            </Link>
            <div class="mt-2 flex flex-wrap gap-1.5">
              <Badge v-if="ticket.tracker" tone="neutral">{{ ticket.tracker }}</Badge>
            </div>
          </div>

          <div class="min-w-0 text-sm text-muted-foreground">
            <p class="truncate font-medium text-foreground">{{ taxonomy(ticket) }}</p>
            <p class="mt-1 truncate text-xs">Project and tracker</p>
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
      <div v-else class="p-6"><EmptyState title="No tickets yet" /></div>
    </ResponsiveList>

    <div class="mt-4"><Pagination :links="tickets.links" /></div>
  </PortalLayout>
</template>
