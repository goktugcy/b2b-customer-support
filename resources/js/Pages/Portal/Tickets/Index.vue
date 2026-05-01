<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import type { Paginated, SelectOption } from '@/types'

type TicketRow = {
  id: string
  subject: string
  status: string
  priority: string
  assignee?: string
  created_at: string
}

const props = defineProps<{
  tickets: Paginated<TicketRow>
  filters: { status?: string }
  statuses: SelectOption[]
}>()

const filter = useForm({ status: props.filters.status ?? '' })
const applyFilters = () => router.get(route('portal.tickets.index'), filter.data(), { preserveState: true, replace: true })
</script>

<template>
  <PortalLayout title="Tickets">
    <div class="flex items-center justify-between gap-4">
      <select v-model="filter.status" class="h-10 rounded-md border-slate-300 text-sm" @change="applyFilters">
        <option value="">All statuses</option>
        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
      </select>
      <Link :href="route('portal.tickets.create')"><Button>Create ticket</Button></Link>
    </div>

    <div class="mt-4 overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
      <table class="w-full table-fixed divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
          <tr><th class="w-[52%] px-4 py-3">Ticket</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Priority</th><th class="px-4 py-3">Assignee</th></tr>
        </thead>
        <tbody v-if="tickets.data.length" class="divide-y divide-slate-100">
          <tr v-for="ticket in tickets.data" :key="ticket.id" class="text-sm">
            <td class="px-4 py-3"><Link :href="route('portal.tickets.show', ticket.id)" class="font-medium hover:text-teal-800">{{ ticket.subject }}</Link></td>
            <td class="px-4 py-3"><Badge tone="blue">{{ ticket.status }}</Badge></td>
            <td class="px-4 py-3 text-slate-600">{{ ticket.priority }}</td>
            <td class="px-4 py-3 text-slate-600">{{ ticket.assignee || 'Unassigned' }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="!tickets.data.length" class="p-4"><EmptyState title="No tickets yet" description="Create a ticket when your team needs help." /></div>
    </div>
    <div class="mt-4"><Pagination :links="tickets.links" /></div>
  </PortalLayout>
</template>
