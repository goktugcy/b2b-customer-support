<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import type { Paginated } from '@/types'

type UserRow = {
  id: string
  name: string
  email: string
  company?: string
  status: string
  roles: string[]
  last_login_at?: string
}

defineProps<{ users: Paginated<UserRow> }>()
</script>

<template>
  <AdminLayout title="Users">
    <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
      <table class="w-full table-fixed divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
          <tr>
            <th class="w-[32%] px-4 py-3">User</th>
            <th class="px-4 py-3">Company</th>
            <th class="px-4 py-3">Roles</th>
            <th class="px-4 py-3">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="user in users.data" :key="user.id" class="text-sm">
            <td class="px-4 py-3">
              <p class="font-medium">{{ user.name }}</p>
              <p class="text-xs text-slate-500">{{ user.email }}</p>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ user.company }}</td>
            <td class="px-4 py-3 text-slate-600">{{ user.roles.join(', ') }}</td>
            <td class="px-4 py-3"><Badge :tone="user.status === 'active' ? 'green' : 'red'">{{ user.status }}</Badge></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="mt-4"><Pagination :links="users.links" /></div>
  </AdminLayout>
</template>
