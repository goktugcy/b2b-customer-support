<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import type { Paginated } from '@/types'

type LogRow = {
  id: number
  company?: string
  actor?: string
  action: string
  before?: unknown
  after?: unknown
  created_at?: string
}

defineProps<{ logs: Paginated<LogRow> }>()
</script>

<template>
  <AdminLayout title="Audit Logs">
    <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
      <table class="w-full table-fixed divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
          <tr><th class="px-4 py-3">Action</th><th class="px-4 py-3">Company</th><th class="px-4 py-3">Actor</th><th class="px-4 py-3">Time</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="log in logs.data" :key="log.id" class="text-sm">
            <td class="px-4 py-3 font-medium">{{ log.action }}</td>
            <td class="px-4 py-3 text-slate-600">{{ log.company || 'System' }}</td>
            <td class="px-4 py-3 text-slate-600">{{ log.actor || 'System' }}</td>
            <td class="px-4 py-3 text-slate-600">{{ log.created_at }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="mt-4"><Pagination :links="logs.links" /></div>
  </AdminLayout>
</template>
