<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { Filter, Plus, Search } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Select from '@/Components/ui/select/Select.vue'
import Input from '@/Components/ui/input/Input.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Table from '@/Components/ui/table/Table.vue'
import TableBody from '@/Components/ui/table/TableBody.vue'
import TableCell from '@/Components/ui/table/TableCell.vue'
import TableHead from '@/Components/ui/table/TableHead.vue'
import TableHeader from '@/Components/ui/table/TableHeader.vue'
import TableRow from '@/Components/ui/table/TableRow.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import type { CategoryOption, Paginated, ProjectOption, SelectOption, TagOption, TrackerOption } from '@/types'

type TicketRow = {
  id: string
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
}>()

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

const applyFilters = () => {
  router.get(route('admin.tickets.index'), filter.data(), { preserveState: true, replace: true })
}

const statusTone = (status: string) => status === 'closed' || status === 'resolved' ? 'green' : status === 'waiting_on_customer' || status === 'pending' ? 'amber' : 'blue'
</script>

<template>
  <AdminLayout title="Tickets">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold tracking-normal">Ticket operations</h2>
        <p class="mt-1 text-sm text-muted-foreground">Filter customer requests by queue, SLA, taxonomy, and ownership.</p>
      </div>
      <Link :href="route('admin.tickets.create')">
        <Button><Plus class="h-4 w-4" /> Create ticket</Button>
      </Link>
    </div>

    <Card class="mt-4">
      <CardHeader class="pb-3">
        <div class="flex items-center gap-2">
          <Filter class="h-4 w-4 text-primary" />
          <CardTitle class="text-sm">Filters</CardTitle>
        </div>
      </CardHeader>
      <CardContent class="p-4">
        <div class="grid gap-3 md:grid-cols-4 xl:grid-cols-9">
          <div class="relative md:col-span-2 xl:col-span-2">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="filter.search" class="pl-9" placeholder="Search tickets" @keydown.enter.prevent="applyFilters" />
          </div>
          <Select v-model="filter.queue" @change="applyFilters">
            <option value="">All queues</option>
            <option value="mine">Mine</option>
            <option value="unassigned">Unassigned</option>
            <option value="overdue">Overdue</option>
            <option value="due_soon">Due soon</option>
          </Select>
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

    <Card class="mt-4 overflow-hidden">
      <CardContent class="p-0">
        <Table class="table-fixed">
          <TableHeader class="bg-muted/50">
            <TableRow>
              <TableHead class="w-[38%]">Ticket</TableHead>
              <TableHead>Company</TableHead>
              <TableHead>Project</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Priority</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody v-if="tickets.data.length">
            <TableRow v-for="ticket in tickets.data" :key="ticket.id">
              <TableCell>
                <Link :href="route('admin.tickets.show', ticket.id)" class="font-medium text-foreground transition-colors hover:text-primary">
                  {{ ticket.subject }}
                </Link>
                <div class="mt-2 flex flex-wrap gap-1">
                  <Badge v-if="ticket.tracker" tone="neutral">{{ ticket.tracker }}</Badge>
                  <Badge v-if="ticket.sla === 'breached'" tone="red">SLA breached</Badge>
                  <Badge v-for="tag in ticket.tags" :key="tag.name" tone="neutral">{{ tag.name }}</Badge>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">{{ ticket.assignee || 'Unassigned' }}</p>
              </TableCell>
              <TableCell class="text-muted-foreground">{{ ticket.company }}</TableCell>
              <TableCell class="text-muted-foreground">{{ ticket.project || '-' }}</TableCell>
              <TableCell><Badge :tone="statusTone(ticket.status)">{{ ticket.status }}</Badge></TableCell>
              <TableCell class="text-muted-foreground">{{ ticket.priority }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <div v-if="!tickets.data.length" class="p-4">
          <EmptyState title="No tickets found" />
        </div>
      </CardContent>
    </Card>

    <div class="mt-4">
      <Pagination :links="tickets.links" />
    </div>
  </AdminLayout>
</template>
