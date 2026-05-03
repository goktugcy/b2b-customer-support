<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Activity, AlertTriangle, BarChart3, Clock3, Inbox, UserRound } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
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
  by_priority: Record<string, number>
  recent_events: { id: number; type: string; ticket_id?: string; ticket_url?: string | null; ticket?: string; company?: string; actor: string; occurred_at?: string }[]
}

defineProps<{ metrics: Metrics }>()

const label = (value: string) => value.replaceAll('_', ' ')
const total = (items: Record<string, number>) => Object.values(items).reduce((sum, value) => sum + value, 0)
const percentage = (items: Record<string, number>, value: number) => {
  const count = total(items)

  return count > 0 ? Math.round((value / count) * 100) : 0
}

const metricHref = (key: string) => {
  if (key.includes('overdue')) return route('admin.tickets.index', { queue: 'overdue' })
  if (key.includes('due_soon')) return route('admin.tickets.index', { queue: 'due_soon' })
  if (key.includes('unassigned')) return route('admin.tickets.index', { queue: 'unassigned' })
  if (key.includes('waiting')) return route('admin.tickets.index', { status: 'waiting_on_customer' })
  if (key.includes('open')) return route('admin.tickets.index', { status: 'open' })

  return route('admin.tickets.index')
}

const metricTone = (key: string) => {
  if (key.includes('overdue')) return 'red'
  if (key.includes('due_soon') || key.includes('waiting')) return 'amber'
  if (key.includes('unassigned')) return 'blue'

  return 'neutral'
}

const metricIcon = (key: string) => {
  if (key.includes('overdue') || key.includes('due_soon')) return AlertTriangle
  if (key.includes('unassigned')) return UserRound
  if (key.includes('open')) return Inbox

  return Activity
}
</script>

<template>
  <AdminLayout title="Dashboard">
    <PageHeader
      title="Operations dashboard"
      description="Live queue health, SLA risk, workload mix, and recent activity across customer companies."
      eyebrow="Provider operations"
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

    <PageSection title="Queue mix" description="Use these distributions to spot stale or high-risk work before opening the ticket inbox.">
      <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
      <Card>
        <CardHeader>
          <div class="flex items-center gap-2">
            <BarChart3 class="h-4 w-4 text-primary" />
            <CardTitle>Status distribution</CardTitle>
          </div>
          <CardDescription>Current queue composition by workflow state.</CardDescription>
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
            <BarChart3 class="h-4 w-4 text-primary" />
            <CardTitle>Priority distribution</CardTitle>
          </div>
          <CardDescription>Open and historical tickets grouped by priority.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-2">
          <div v-for="(value, priority) in metrics.by_priority" :key="priority" class="rounded-md border bg-background/70 px-3 py-2 text-sm">
            <div class="flex items-center justify-between gap-3">
              <span class="font-medium capitalize">{{ label(String(priority)) }}</span>
              <Badge>{{ value }}</Badge>
            </div>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
              <div class="h-full rounded-full bg-accent" :style="{ width: `${percentage(metrics.by_priority, value)}%` }" />
            </div>
          </div>
        </CardContent>
      </Card>
      </div>
    </PageSection>

    <PageSection title="Recent activity" description="Latest ticket events across customer companies.">
      <Card>
        <CardContent class="p-0">
          <div class="divide-y">
            <div v-for="event in metrics.recent_events" :key="event.id" class="flex gap-3 px-5 py-4 text-sm">
              <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-md border bg-secondary text-primary">
                <Clock3 class="h-3.5 w-3.5" />
              </span>
              <div class="min-w-0">
                <p class="font-medium">
                  {{ label(event.type) }} ·
                  <Link v-if="event.ticket_url" :href="event.ticket_url" class="transition-colors hover:text-primary">
                    {{ event.ticket_id || 'Ticket' }} · {{ event.ticket || 'Ticket' }}
                  </Link>
                  <span v-else>{{ event.ticket_id || 'Ticket' }} · {{ event.ticket || 'Ticket' }}</span>
                </p>
                <p class="mt-1 text-muted-foreground">{{ event.company || 'Company' }} · {{ event.actor }} · {{ event.occurred_at }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </PageSection>
  </AdminLayout>
</template>
