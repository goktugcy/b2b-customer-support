<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import type { Paginated } from '@/types'

type CompanyRow = {
  id: string
  name: string
  slug: string
  type: string
  status: string
  users_count: number
  tickets_count: number
}

defineProps<{ companies: Paginated<CompanyRow> }>()

const form = useForm({
  name: '',
  slug: '',
  type: 'client',
  timezone: 'UTC',
})

const submit = () => form.post(route('admin.companies.store'), { preserveScroll: true, onSuccess: () => form.reset() })
</script>

<template>
  <AdminLayout title="Companies">
    <section class="grid gap-6 xl:grid-cols-[1fr_340px]">
      <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <table class="w-full table-fixed divide-y divide-slate-200">
          <thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
            <tr>
              <th class="w-[38%] px-4 py-3">Company</th>
              <th class="px-4 py-3">Type</th>
              <th class="px-4 py-3">Users</th>
              <th class="px-4 py-3">Tickets</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="company in companies.data" :key="company.id" class="text-sm">
              <td class="px-4 py-3">
                <Link :href="route('admin.companies.show', company.id)" class="font-medium text-slate-950 hover:text-teal-800">{{ company.name }}</Link>
                <p class="text-xs text-slate-500">{{ company.slug }}</p>
              </td>
              <td class="px-4 py-3"><Badge :tone="company.type === 'provider' ? 'blue' : 'green'">{{ company.type }}</Badge></td>
              <td class="px-4 py-3 text-slate-600">{{ company.users_count }}</td>
              <td class="px-4 py-3 text-slate-600">{{ company.tickets_count }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="submit">
        <h2 class="text-sm font-semibold">Create company</h2>
        <div class="mt-4 space-y-3">
          <div>
            <Label>Name</Label>
            <Input v-model="form.name" class="mt-1" required />
            <FieldError :message="form.errors.name" />
          </div>
          <div>
            <Label>Slug</Label>
            <Input v-model="form.slug" class="mt-1" required />
            <FieldError :message="form.errors.slug" />
          </div>
          <div>
            <Label>Type</Label>
            <select v-model="form.type" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
              <option value="client">Client</option>
              <option value="provider">Provider</option>
            </select>
          </div>
          <div>
            <Label>Timezone</Label>
            <Input v-model="form.timezone" class="mt-1" required />
          </div>
          <Button type="submit" class="w-full">Create</Button>
        </div>
      </form>
    </section>
    <div class="mt-4">
      <Pagination :links="companies.links" />
    </div>
  </AdminLayout>
</template>
