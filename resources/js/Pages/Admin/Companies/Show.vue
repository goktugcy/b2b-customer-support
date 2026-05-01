<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Input from '@/Components/ui/input/Input.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'

type Company = {
  id: string
  name: string
  slug: string
  type: string
  status: string
  timezone: string
  users: { id: string; name: string; email: string; status: string; roles: string[] }[]
  api_clients: { id: string; name: string; status: string; last_used_at?: string }[]
  webhooks: { id: string; url: string; status: string; events: string[] }[]
  sla_policies: { id: number; priority: string; first_response_minutes: number; resolution_minutes: number; enabled: boolean }[]
}

defineProps<{ company: Company }>()

const saveSla = (company: Company, policy: Company['sla_policies'][number]) => {
  router.patch(route('admin.companies.sla-policies.update', [company.id, policy.id]), {
    first_response_minutes: policy.first_response_minutes,
    resolution_minutes: policy.resolution_minutes,
    enabled: policy.enabled,
  }, { preserveScroll: true })
}
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

    <Card v-if="company.type === 'client'" class="mt-6">
      <CardHeader><CardTitle class="text-sm">SLA policies</CardTitle></CardHeader>
      <CardContent>
        <div class="space-y-3">
          <div v-for="policy in company.sla_policies" :key="policy.id" class="grid gap-3 rounded-md border bg-background p-3 text-sm md:grid-cols-[120px_1fr_1fr_100px_auto]">
            <div class="font-medium capitalize">{{ policy.priority }}</div>
            <label class="space-y-1">
              <span class="text-xs text-muted-foreground">First response minutes</span>
              <Input v-model.number="policy.first_response_minutes" type="number" min="1" />
            </label>
            <label class="space-y-1">
              <span class="text-xs text-muted-foreground">Resolution minutes</span>
              <Input v-model.number="policy.resolution_minutes" type="number" min="1" />
            </label>
            <label class="flex items-center gap-2 self-end">
              <Checkbox v-model="policy.enabled" />
              Enabled
            </label>
            <Button type="button" class="self-end" variant="secondary" @click="saveSla(company, policy)">Save</Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </AdminLayout>
</template>
