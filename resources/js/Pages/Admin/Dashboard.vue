<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'

type Metrics = {
  summary: Record<string, number>
  by_status: Record<string, number>
  by_priority: Record<string, number>
  recent_events: { id: number; type: string; ticket_id?: string; ticket?: string; company?: string; actor: string; occurred_at?: string }[]
}

defineProps<{ metrics: Metrics }>()

const label = (value: string) => value.replaceAll('_', ' ')
</script>

<template>
  <AdminLayout title="Dashboard">
    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
      <Card v-for="(value, key) in metrics.summary" :key="key">
        <CardContent class="p-4">
          <p class="text-xs font-medium uppercase text-muted-foreground">{{ label(String(key)) }}</p>
          <p class="mt-2 text-2xl font-semibold">{{ value }}</p>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
      <Card>
        <CardHeader>
          <CardTitle>Status distribution</CardTitle>
          <CardDescription>Current queue composition by workflow state.</CardDescription>
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
          <CardTitle>Priority distribution</CardTitle>
          <CardDescription>Open and historical tickets grouped by priority.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-2">
          <div v-for="(value, priority) in metrics.by_priority" :key="priority" class="flex items-center justify-between rounded-md border bg-background px-3 py-2 text-sm">
            <span class="font-medium capitalize">{{ label(String(priority)) }}</span>
            <Badge>{{ value }}</Badge>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Recent activity</CardTitle>
        <CardDescription>Latest ticket events across customer companies.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="divide-y">
          <div v-for="event in metrics.recent_events" :key="event.id" class="py-3 text-sm first:pt-0 last:pb-0">
            <p class="font-medium">{{ label(event.type) }} · {{ event.ticket || 'Ticket' }}</p>
            <p class="text-muted-foreground">{{ event.company || 'Company' }} · {{ event.actor }} · {{ event.occurred_at }}</p>
          </div>
        </div>
      </CardContent>
    </Card>
  </AdminLayout>
</template>
