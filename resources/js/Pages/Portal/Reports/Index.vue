<script setup lang="ts">
import { computed, onBeforeUnmount, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { CheckCircle2, Clock3, Download, FileDown, Loader2, Play, XCircle } from 'lucide-vue-next'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'

type ExportRow = { id: string; type: string; format: string; status: string; error_message?: string | null; created_at?: string; completed_at?: string; download_url?: string | null }
type CsatSummary = { average?: number | null; sent: number; responded: number; response_rate: number; low_scores: { id: string; rating: number; comment?: string | null; ticket?: string; subject?: string }[] }

const props = defineProps<{ filters: { status?: string; priority?: string; from?: string; to?: string }; exports: ExportRow[]; csatSummary: CsatSummary }>()
const form = useForm({ status: props.filters.status ?? '', priority: props.filters.priority ?? '', from: props.filters.from ?? '', to: props.filters.to ?? '' })
const params = computed(() => form.data())
const hasRunningExports = computed(() => props.exports.some((item) => item.status === 'pending' || item.status === 'processing'))
let pollTimer: ReturnType<typeof setInterval> | null = null

const refreshExports = () => {
  router.reload({ only: ['exports'] })
}

const startPolling = () => {
  if (pollTimer || !hasRunningExports.value) {
    return
  }

  pollTimer = setInterval(refreshExports, 3500)
}

const stopPolling = () => {
  if (!pollTimer) {
    return
  }

  clearInterval(pollTimer)
  pollTimer = null
}

watch(hasRunningExports, (running) => {
  if (running) {
    startPolling()
    return
  }

  stopPolling()
}, { immediate: true })

onBeforeUnmount(stopPolling)

const queueExport = (type: 'tickets' | 'csat', format: 'csv' | 'pdf') => {
  router.post(route('portal.reports.exports.store'), { ...form.data(), type, format }, {
    preserveScroll: true,
    onSuccess: refreshExports,
  })
}

const statusTone = (status: string) => status === 'completed' ? 'green' : status === 'failed' ? 'red' : status === 'processing' ? 'blue' : 'amber'
const directDownloadButtonClass = 'inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-secondary hover:text-secondary-foreground active:bg-secondary/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/70 focus-visible:ring-offset-2 focus-visible:ring-offset-background'
const completedDownloadButtonClass = 'inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 active:bg-primary/85 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/70 focus-visible:ring-offset-2 focus-visible:ring-offset-background'
const formatDate = (value?: string) => {
  if (!value) {
    return '-'
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? value
    : new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}
</script>

<template>
  <PortalLayout title="Reports">
    <div>
      <h2 class="text-xl font-semibold tracking-normal">Company reports</h2>
      <p class="mt-1 text-sm text-muted-foreground">Export company tickets and CSAT responses. Queued exports refresh automatically while running.</p>
    </div>

    <Card class="mt-4">
      <CardHeader><CardTitle class="text-sm">Filters</CardTitle></CardHeader>
      <CardContent>
        <div class="grid gap-3 md:grid-cols-4">
          <div><Label>From</Label><Input v-model="form.from" type="date" class="mt-1" /></div>
          <div><Label>To</Label><Input v-model="form.to" type="date" class="mt-1" /></div>
          <div>
            <Label>Status</Label>
            <Select v-model="form.status" class="mt-1"><option value="">Any status</option><option value="open">open</option><option value="resolved">resolved</option><option value="closed">closed</option></Select>
          </div>
          <div>
            <Label>Priority</Label>
            <Select v-model="form.priority" class="mt-1"><option value="">Any priority</option><option value="low">low</option><option value="normal">normal</option><option value="high">high</option><option value="urgent">urgent</option></Select>
          </div>
        </div>
      </CardContent>
    </Card>

    <div class="mt-4 grid gap-4 md:grid-cols-4">
      <Card><CardContent class="p-4"><p class="text-xs text-muted-foreground">CSAT average</p><p class="mt-1 text-2xl font-semibold">{{ csatSummary.average ?? '-' }}</p></CardContent></Card>
      <Card><CardContent class="p-4"><p class="text-xs text-muted-foreground">Response rate</p><p class="mt-1 text-2xl font-semibold">{{ csatSummary.response_rate }}%</p></CardContent></Card>
      <Card><CardContent class="p-4"><p class="text-xs text-muted-foreground">Sent</p><p class="mt-1 text-2xl font-semibold">{{ csatSummary.sent }}</p></CardContent></Card>
      <Card><CardContent class="p-4"><p class="text-xs text-muted-foreground">Responded</p><p class="mt-1 text-2xl font-semibold">{{ csatSummary.responded }}</p></CardContent></Card>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
      <Card>
        <CardContent class="space-y-4 p-5">
          <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-md border bg-secondary text-primary"><FileDown class="h-5 w-5" /></div>
            <div>
              <p class="font-semibold">Tickets</p>
              <p class="mt-1 text-sm text-muted-foreground">Queue large ticket exports or download the filtered result immediately.</p>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button variant="secondary" @click="queueExport('tickets', 'csv')"><Play class="h-4 w-4" /> Queue CSV</Button>
            <Button variant="secondary" @click="queueExport('tickets', 'pdf')"><Play class="h-4 w-4" /> Queue PDF</Button>
            <a :href="route('portal.reports.tickets.csv', params)" :class="directDownloadButtonClass"><Download class="h-4 w-4" /> CSV</a>
            <a :href="route('portal.reports.tickets.pdf', params)" :class="directDownloadButtonClass"><Download class="h-4 w-4" /> PDF</a>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="space-y-4 p-5">
          <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-md border bg-secondary text-primary"><FileDown class="h-5 w-5" /></div>
            <div>
              <p class="font-semibold">CSAT</p>
              <p class="mt-1 text-sm text-muted-foreground">Export ratings, response rates, comments, and low-score follow-up data.</p>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button variant="secondary" @click="queueExport('csat', 'csv')"><Play class="h-4 w-4" /> Queue CSV</Button>
            <Button variant="secondary" @click="queueExport('csat', 'pdf')"><Play class="h-4 w-4" /> Queue PDF</Button>
            <a :href="route('portal.reports.csat.csv', params)" :class="directDownloadButtonClass"><Download class="h-4 w-4" /> CSV</a>
            <a :href="route('portal.reports.csat.pdf', params)" :class="directDownloadButtonClass"><Download class="h-4 w-4" /> PDF</a>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card class="mt-4">
      <CardHeader>
        <div class="flex items-center justify-between gap-3">
          <CardTitle class="text-sm">Export history</CardTitle>
          <Badge v-if="hasRunningExports" tone="blue"><Loader2 class="mr-1 h-3.5 w-3.5 animate-spin" />Refreshing</Badge>
        </div>
      </CardHeader>
      <CardContent>
        <div v-if="exports.length" class="space-y-2">
          <div v-for="exportItem in exports" :key="exportItem.id" class="flex flex-wrap items-center justify-between gap-3 rounded-md border bg-background/70 p-3 text-sm">
            <div class="flex min-w-0 items-start gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border bg-secondary text-muted-foreground">
                <CheckCircle2 v-if="exportItem.status === 'completed'" class="h-4 w-4 text-emerald-500" />
                <XCircle v-else-if="exportItem.status === 'failed'" class="h-4 w-4 text-destructive" />
                <Loader2 v-else-if="exportItem.status === 'processing'" class="h-4 w-4 animate-spin text-primary" />
                <Clock3 v-else class="h-4 w-4 text-amber-500" />
              </div>
              <div class="min-w-0">
                <p class="font-medium">{{ exportItem.type }}.{{ exportItem.format }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Queued {{ formatDate(exportItem.created_at) }}<span v-if="exportItem.completed_at"> · Completed {{ formatDate(exportItem.completed_at) }}</span></p>
                <p v-if="exportItem.error_message" class="mt-1 text-xs text-destructive">{{ exportItem.error_message }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Badge :tone="statusTone(exportItem.status)">{{ exportItem.status }}</Badge>
              <a v-if="exportItem.download_url" :href="exportItem.download_url" :class="completedDownloadButtonClass">Download</a>
            </div>
          </div>
        </div>
        <EmptyState v-else title="No exports queued" description="Queue a CSV or PDF export to build a downloadable report." />
      </CardContent>
    </Card>
  </PortalLayout>
</template>
