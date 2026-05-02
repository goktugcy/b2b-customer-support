<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { Plus, Search } from 'lucide-vue-next'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
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

const props = defineProps<{
  tickets: Paginated<TicketRow>
  filters: { search?: string; queue?: string; status?: string; project?: string; tracker?: string; tag?: string }
  statuses: SelectOption[]
  projects: ProjectOption[]
  trackers: TrackerOption[]
  tags: TagOption[]
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
        <div class="grid gap-3 md:grid-cols-6">
          <div class="relative md:col-span-2">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="filter.search" class="pl-9" placeholder="Search tickets" @keydown.enter.prevent="applyFilters" />
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
                <Link :href="route('portal.tickets.show', ticket.id)" class="font-medium transition-colors hover:text-primary">{{ ticket.subject }}</Link>
                <div class="mt-2 flex flex-wrap gap-1">
                  <Badge v-if="ticket.tracker" tone="neutral">{{ ticket.tracker }}</Badge>
                  <Badge v-if="ticket.sla === 'breached'" tone="red">SLA breached</Badge>
                  <Badge v-for="tag in ticket.tags" :key="tag" tone="neutral">{{ tag }}</Badge>
                </div>
              </TableCell>
              <TableCell class="text-muted-foreground">{{ ticket.project || '-' }}</TableCell>
              <TableCell><Badge tone="blue">{{ ticket.status }}</Badge></TableCell>
              <TableCell class="text-muted-foreground">{{ ticket.priority }}</TableCell>
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
