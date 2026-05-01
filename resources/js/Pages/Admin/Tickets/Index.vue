<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import type { Paginated, SelectOption } from '@/types'

type TicketRow = {
  id: string
  subject: string
  status: string
  priority: string
  company: string
  assignee?: string
  created_at: string
}

const props = defineProps<{
  tickets: Paginated<TicketRow>
  filters: { status?: string; priority?: string; company?: string }
  companies: { public_id: string; name: string }[]
  statuses: SelectOption[]
  priorities: SelectOption[]
}>()

const filter = useForm({
  status: props.filters.status ?? '',
  priority: props.filters.priority ?? '',
  company: props.filters.company ?? '',
})

const form = useForm({
  company_id: props.companies[0]?.public_id ?? '',
  subject: '',
  description: '',
  priority: 'normal',
  assigned_to_user_id: '',
})

const applyFilters = () => {
  router.get(route('admin.tickets.index'), filter.data(), { preserveState: true, replace: true })
}

const createTicket = () => {
  form.post(route('admin.tickets.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset('subject', 'description', 'assigned_to_user_id'),
  })
}

const statusTone = (status: string) => status === 'closed' || status === 'resolved' ? 'green' : status === 'waiting_on_customer' || status === 'pending' ? 'amber' : 'blue'
</script>

<template>
  <AdminLayout title="Tickets">
    <section class="grid gap-6 xl:grid-cols-[1fr_360px]">
      <div class="space-y-4">
        <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
          <div class="grid gap-3 md:grid-cols-4">
            <select v-model="filter.status" class="h-10 rounded-md border-slate-300 text-sm" @change="applyFilters">
              <option value="">All statuses</option>
              <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
            </select>
            <select v-model="filter.priority" class="h-10 rounded-md border-slate-300 text-sm" @change="applyFilters">
              <option value="">All priorities</option>
              <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
            </select>
            <select v-model="filter.company" class="h-10 rounded-md border-slate-300 text-sm md:col-span-2" @change="applyFilters">
              <option value="">All companies</option>
              <option v-for="company in companies" :key="company.public_id" :value="company.public_id">{{ company.name }}</option>
            </select>
          </div>
        </div>

        <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
          <table class="w-full table-fixed divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr class="text-left text-xs font-medium uppercase text-slate-500">
                <th class="w-[42%] px-4 py-3">Ticket</th>
                <th class="px-4 py-3">Company</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Priority</th>
              </tr>
            </thead>
            <tbody v-if="tickets.data.length" class="divide-y divide-slate-100">
              <tr v-for="ticket in tickets.data" :key="ticket.id" class="text-sm">
                <td class="px-4 py-3">
                  <Link :href="route('admin.tickets.show', ticket.id)" class="font-medium text-slate-950 hover:text-teal-800">
                    {{ ticket.subject }}
                  </Link>
                  <p class="mt-1 text-xs text-slate-500">{{ ticket.assignee || 'Unassigned' }}</p>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ ticket.company }}</td>
                <td class="px-4 py-3"><Badge :tone="statusTone(ticket.status)">{{ ticket.status }}</Badge></td>
                <td class="px-4 py-3 text-slate-600">{{ ticket.priority }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="!tickets.data.length" class="p-4">
            <EmptyState title="No tickets found" />
          </div>
        </div>

        <Pagination :links="tickets.links" />
      </div>

      <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="createTicket">
        <div class="mb-4 flex items-center gap-2">
          <Plus class="h-4 w-4 text-teal-700" />
          <h2 class="text-sm font-semibold text-slate-950">Create ticket</h2>
        </div>
        <div class="space-y-4">
          <div>
            <Label>Company</Label>
            <select v-model="form.company_id" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
              <option v-for="company in companies" :key="company.public_id" :value="company.public_id">{{ company.name }}</option>
            </select>
            <FieldError :message="form.errors.company_id" />
          </div>
          <div>
            <Label>Subject</Label>
            <Input v-model="form.subject" class="mt-1" required />
            <FieldError :message="form.errors.subject" />
          </div>
          <div>
            <Label>Description</Label>
            <Textarea v-model="form.description" class="mt-1" required />
            <FieldError :message="form.errors.description" />
          </div>
          <div>
            <Label>Priority</Label>
            <select v-model="form.priority" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
              <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
            </select>
          </div>
          <Button type="submit" class="w-full" :disabled="form.processing">Create</Button>
        </div>
      </form>
    </section>
  </AdminLayout>
</template>
