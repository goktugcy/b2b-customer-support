<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'

type Metrics = {
  summary: Record<string, number>
  by_status: Record<string, number>
  recent_tickets: { id: string; subject: string; status: string; priority: string; assignee?: string; updated_at?: string }[]
}

defineProps<{ metrics: Metrics }>()

const label = (value: string) => value.replaceAll('_', ' ')
</script>

<template>
  <PortalLayout title="Dashboard">
    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
      <Card v-for="(value, key) in metrics.summary" :key="key">
        <CardContent class="p-4">
          <p class="text-xs font-medium uppercase text-muted-foreground">{{ label(String(key)) }}</p>
          <p class="mt-2 text-2xl font-semibold">{{ value }}</p>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
      <Card>
        <CardHeader>
          <CardTitle>Status</CardTitle>
          <CardDescription>Your company's ticket mix.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-2">
          <div v-for="(value, status) in metrics.by_status" :key="status" class="flex items-center justify-between rounded-md border bg-background px-3 py-2 text-sm">
            <span class="font-medium capitalize">{{ label(String(status)) }}</span>
            <Badge>{{ value }}</Badge>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Recent tickets</CardTitle>
          <CardDescription>Latest customer-side activity.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="divide-y">
            <div v-for="ticket in metrics.recent_tickets" :key="ticket.id" class="flex items-center justify-between gap-4 py-3 text-sm first:pt-0 last:pb-0">
              <div class="min-w-0">
                <Link :href="route('portal.tickets.show', ticket.id)" class="font-medium transition-colors hover:text-primary">{{ ticket.subject }}</Link>
                <p class="text-muted-foreground">{{ ticket.assignee || 'Unassigned' }} · {{ ticket.updated_at }}</p>
              </div>
              <div class="flex shrink-0 gap-2"><Badge>{{ ticket.status }}</Badge><Badge tone="neutral">{{ ticket.priority }}</Badge></div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </PortalLayout>
</template>
