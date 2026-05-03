<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Activity, AlertTriangle, BarChart3, Clock3, Inbox } from 'lucide-vue-next'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'
import MetricCard from '@/Components/shared/MetricCard.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import PageSection from '@/Components/shared/PageSection.vue'

type Metrics = {
  summary: Record<string, number>
  by_status: Record<string, number>
  recent_tickets: { id: string; display_id: string; url: string; subject: string; status: string; priority: string; assignee?: string; updated_at?: string }[]
}

defineProps<{ metrics: Metrics }>()

const label = (value: string) => value.replaceAll('_', ' ')
const total = (items: Record<string, number>) => Object.values(items).reduce((sum, value) => sum + value, 0)
const percentage = (items: Record<string, number>, value: number) => {
  const count = total(items)

  return count > 0 ? Math.round((value / count) * 100) : 0
}

const metricHref = (key: string) => {
  if (key.includes('overdue')) return route('portal.tickets.index', { queue: 'overdue' })
  if (key.includes('due_soon')) return route('portal.tickets.index', { queue: 'due_soon' })
  if (key.includes('open')) return route('portal.tickets.index', { status: 'open' })
  if (key.includes('resolved')) return route('portal.tickets.index', { status: 'resolved' })

  return route('portal.tickets.index')
}

const metricTone = (key: string) => {
  if (key.includes('overdue')) return 'red'
  if (key.includes('due_soon')) return 'amber'
  if (key.includes('resolved')) return 'green'

  return 'neutral'
}

const metricIcon = (key: string) => {
  if (key.includes('overdue') || key.includes('due_soon')) return AlertTriangle
  if (key.includes('open') || key.includes('resolved')) return Inbox

  return Activity
}
</script>

<template>
  <PortalLayout title="Dashboard">
    <PageHeader
      title="Workspace dashboard"
      description="Track open requests, SLA risk, and the latest customer-side ticket activity."
      eyebrow="Client portal"
    />

    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
      <MetricCard
        v-for="(value, key) in metrics.summary"
        :key="key"
        :label="label(String(key))"
        :value="value"
        :href="metricHref(String(key))"
        :tone="metricTone(String(key))"
        :icon="metricIcon(String(key))"
      />
    </div>

    <PageSection title="Ticket health" description="A compact view of workload mix and recent activity in your company workspace.">
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
                <Link :href="ticket.url" class="font-medium transition-colors hover:text-primary">{{ ticket.display_id }} · {{ ticket.subject }}</Link>
                <p class="text-muted-foreground">{{ ticket.assignee || 'Unassigned' }} · {{ ticket.updated_at }}</p>
              </div>
              <div class="flex shrink-0 gap-2"><Badge>{{ ticket.status }}</Badge><Badge tone="neutral">{{ ticket.priority }}</Badge></div>
            </div>
          </div>
        </CardContent>
      </Card>
      </div>
    </PageSection>
  </PortalLayout>
</template>
