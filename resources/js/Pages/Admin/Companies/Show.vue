<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'

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
      <Card class="lg:col-span-1">
        <CardHeader><CardTitle class="text-sm">Company</CardTitle></CardHeader>
        <CardContent>
          <dl class="space-y-3 text-sm">
            <div><dt class="text-muted-foreground">Slug</dt><dd class="font-medium">{{ company.slug }}</dd></div>
            <div><dt class="text-muted-foreground">Type</dt><dd><Badge>{{ company.type }}</Badge></dd></div>
            <div><dt class="text-muted-foreground">Status</dt><dd><Badge tone="green">{{ company.status }}</Badge></dd></div>
            <div><dt class="text-muted-foreground">Timezone</dt><dd class="font-medium">{{ company.timezone }}</dd></div>
          </dl>
        </CardContent>
      </Card>
      <Card class="lg:col-span-2">
        <CardHeader><CardTitle class="text-sm">Users</CardTitle></CardHeader>
        <CardContent>
          <div class="divide-y">
            <div v-for="user in company.users" :key="user.id" class="py-3 text-sm first:pt-0 last:pb-0">
              <p class="font-medium">{{ user.name }}</p>
              <p class="text-muted-foreground">{{ user.email }} · {{ user.roles.join(', ') || 'No role' }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
