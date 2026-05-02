<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Activity, BarChart3, Clock3 } from 'lucide-vue-next'
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
const total = (items: Record<string, number>) => Object.values(items).reduce((sum, value) => sum + value, 0)
const percentage = (items: Record<string, number>, value: number) => {
  const count = total(items)

  return count > 0 ? Math.round((value / count) * 100) : 0
}
</script>

<template>
  <PortalLayout title="Dashboard">
    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
      <Card v-for="(value, key) in metrics.summary" :key="key">
        <CardContent class="p-4">
          <div class="flex items-center justify-between gap-3">
            <p class="truncate text-xs font-medium uppercase text-muted-foreground">{{ label(String(key)) }}</p>
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
              <Activity class="h-4 w-4" />
            </span>
          </div>
          <p class="mt-3 text-3xl font-semibold tracking-normal">{{ value }}</p>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
      <Card>
        <CardHeader>
          <div class="flex items-center gap-2">
            <BarChart3 class="h-4 w-4 text-primary" />
            <CardTitle>Status</CardTitle>
          </div>
          <CardDescription>Your company's ticket mix.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-2">
          <div v-for="(value, status) in metrics.by_status" :key="status" class="rounded-md border bg-background/70 px-3 py-2 text-sm">
            <div class="flex items-center justify-between gap-3">
              <span class="font-medium capitalize">{{ label(String(status)) }}</span>
              <Badge>{{ value }}</Badge>
            </div>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
              <div class="h-full rounded-full bg-primary" :style="{ width: `${percentage(metrics.by_status, value)}%` }" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <div class="flex items-center gap-2">
            <Clock3 class="h-4 w-4 text-primary" />
            <CardTitle>Recent tickets</CardTitle>
          </div>
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
