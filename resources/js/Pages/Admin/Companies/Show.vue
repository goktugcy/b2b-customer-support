<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'

type Company = {
  id: string
  name: string
  slug: string
  type: string
  status: string
  timezone: string
  users: { id: string; name: string; email: string; status: string; roles: string[] }[]
  api_clients: { id: string; name: string; status: string; last_used_at?: string }[]
  webhooks: { id: number; url: string; status: string; events: string[] }[]
}

defineProps<{ company: Company }>()
</script>

<template>
  <AdminLayout :title="company.name">
    <div class="grid gap-6 lg:grid-cols-3">
      <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm lg:col-span-1">
        <h2 class="text-sm font-semibold">Company</h2>
        <dl class="mt-4 space-y-3 text-sm">
          <div><dt class="text-slate-500">Slug</dt><dd class="font-medium">{{ company.slug }}</dd></div>
          <div><dt class="text-slate-500">Type</dt><dd><Badge>{{ company.type }}</Badge></dd></div>
          <div><dt class="text-slate-500">Status</dt><dd><Badge tone="green">{{ company.status }}</Badge></dd></div>
          <div><dt class="text-slate-500">Timezone</dt><dd class="font-medium">{{ company.timezone }}</dd></div>
        </dl>
      </section>
      <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
        <h2 class="text-sm font-semibold">Users</h2>
        <div class="mt-4 divide-y divide-slate-100">
          <div v-for="user in company.users" :key="user.id" class="py-3 text-sm">
            <p class="font-medium">{{ user.name }}</p>
            <p class="text-slate-500">{{ user.email }} · {{ user.roles.join(', ') || 'No role' }}</p>
          </div>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>
