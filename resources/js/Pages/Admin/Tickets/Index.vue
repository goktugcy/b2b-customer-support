<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Plus } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Select from '@/Components/ui/select/Select.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import FilePicker from '@/Components/shared/FilePicker.vue'
import MultiSelectChips from '@/Components/shared/MultiSelectChips.vue'
import type { MultiSelectOption, Paginated, SelectOption } from '@/types'

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
  departments: MultiSelectOption[]
  providerUsers: MultiSelectOption[]
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
  target_department_ids: [] as string[],
  target_user_ids: [] as string[],
  attachments: [] as File[],
})

const targetErrors = computed(() => form.errors.target_department_ids || form.errors.target_user_ids || (form.errors as Record<string, string | undefined>).targets)

const applyFilters = () => {
  router.get(route('admin.tickets.index'), filter.data(), { preserveState: true, replace: true })
}

const createTicket = () => {
  form.post(route('admin.tickets.store'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      form.reset('subject', 'description', 'assigned_to_user_id')
      form.target_department_ids = []
      form.target_user_ids = []
      form.attachments = []
    },
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
            <Select v-model="filter.status" @change="applyFilters">
              <option value="">All statuses</option>
              <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
            </Select>
            <Select v-model="filter.priority" @change="applyFilters">
              <option value="">All priorities</option>
              <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
            </Select>
            <Select v-model="filter.company" class="md:col-span-2" @change="applyFilters">
              <option value="">All companies</option>
              <option v-for="company in companies" :key="company.public_id" :value="company.public_id">{{ company.name }}</option>
            </Select>
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
            <Select v-model="form.company_id" class="mt-1">
              <option v-for="company in companies" :key="company.public_id" :value="company.public_id">{{ company.name }}</option>
            </Select>
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
            <Select v-model="form.priority" class="mt-1">
              <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
            </Select>
          </div>
          <div>
            <Label>Target departments</Label>
            <MultiSelectChips v-model="form.target_department_ids" class="mt-1" :options="departments" placeholder="Add department" />
            <FieldError :message="targetErrors" />
          </div>
          <div>
            <Label>Target users</Label>
            <MultiSelectChips v-model="form.target_user_ids" class="mt-1" :options="providerUsers" placeholder="Add provider user" />
            <FieldError :message="form.errors.target_user_ids" />
          </div>
          <div>
            <Label>Assignee</Label>
            <Select v-model="form.assigned_to_user_id" class="mt-1">
              <option value="">Unassigned</option>
              <option v-for="user in providerUsers" :key="user.id" :value="user.id">{{ user.name }}</option>
            </Select>
          </div>
          <div>
            <Label>Attachments</Label>
            <FilePicker v-model="form.attachments" class="mt-1" />
            <FieldError :message="form.errors.attachments" />
          </div>
          <Button type="submit" class="w-full" :disabled="form.processing">Create</Button>
        </div>
      </form>
    </section>
  </AdminLayout>
</template>
