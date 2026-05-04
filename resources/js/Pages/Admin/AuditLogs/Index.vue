<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Download } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Dialog from '@/Components/ui/dialog/Dialog.vue'
import DialogContent from '@/Components/ui/dialog/DialogContent.vue'
import DialogHeader from '@/Components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/Components/ui/dialog/DialogTitle.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import DataToolbar from '@/Components/shared/DataToolbar.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import ResponsiveList from '@/Components/shared/ResponsiveList.vue'
import type { Paginated } from '@/types'

type LogRow = {
  id: number
  company?: string
  actor?: string
  action: string
  before?: unknown
  after?: unknown
  metadata?: unknown
  ip_address?: string
  user_agent?: string
  created_at?: string
}

const props = defineProps<{ logs: Paginated<LogRow>; filters: { action?: string; company?: string; actor?: string; from?: string; to?: string } }>()
const filter = useForm({
  action: props.filters.action ?? '',
  company: props.filters.company ?? '',
  actor: props.filters.actor ?? '',
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
})
const selected = ref<LogRow | null>(null)
const params = computed(() => filter.data())
const applyFilters = () => router.get(route('admin.audit-logs.index'), filter.data(), { preserveState: true, replace: true })
const formatDate = (value?: string) => {
  if (!value) return 'Not recorded'

  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? value
    : new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}
</script>

<template>
  <AdminLayout title="Audit Logs">
    <PageHeader
      title="Audit logs"
      description="Review tenant-scoped operational changes with filters, CSV export, and structured details."
      eyebrow="Governance"
    >
      <template #actions>
        <Link :href="route('admin.audit-logs.csv', params)"><Button variant="secondary"><Download class="h-4 w-4" /> Export CSV</Button></Link>
      </template>
    </PageHeader>

    <DataToolbar>
      <Input v-model="filter.action" placeholder="Action" @keydown.enter.prevent="applyFilters" />
      <Input v-model="filter.company" placeholder="Company" @keydown.enter.prevent="applyFilters" />
      <Input v-model="filter.actor" placeholder="Actor" @keydown.enter.prevent="applyFilters" />
      <Input v-model="filter.from" type="date" />
      <Input v-model="filter.to" type="date" />
      <template #actions>
        <Button @click="applyFilters">Apply filters</Button>
      </template>
    </DataToolbar>

    <ResponsiveList>
      <div class="flex items-center justify-between bg-muted/30 px-4 py-3">
        <p class="text-sm font-medium">Audit events</p>
        <p class="text-sm text-muted-foreground">{{ logs.data.length }} visible</p>
      </div>
      <button v-for="log in logs.data" :key="log.id" type="button" class="grid w-full gap-3 p-4 text-left transition-colors hover:bg-secondary/40 lg:grid-cols-[minmax(0,1fr)_minmax(160px,0.45fr)_minmax(160px,0.45fr)_160px] lg:items-center" @click="selected = log">
        <p class="truncate font-medium">{{ log.action }}</p>
        <p class="truncate text-sm text-muted-foreground">{{ log.company || 'System' }}</p>
        <p class="truncate text-sm text-muted-foreground">{{ log.actor || 'System' }}</p>
        <p class="text-sm text-muted-foreground">
          <time :datetime="log.created_at">{{ formatDate(log.created_at) }}</time>
        </p>
      </button>
    </ResponsiveList>
    <div class="mt-4"><Pagination :links="logs.links" /></div>

    <Dialog :open="Boolean(selected)" @update:open="selected = $event ? selected : null">
      <DialogContent class="max-w-4xl">
        <DialogHeader>
          <DialogTitle>{{ selected?.action }}</DialogTitle>
        </DialogHeader>
        <div class="grid gap-4 md:grid-cols-2">
          <div><p class="mb-2 text-xs font-medium text-muted-foreground">Before</p><pre class="max-h-96 overflow-auto rounded-md bg-muted p-3 text-xs">{{ JSON.stringify(selected?.before, null, 2) }}</pre></div>
          <div><p class="mb-2 text-xs font-medium text-muted-foreground">After</p><pre class="max-h-96 overflow-auto rounded-md bg-muted p-3 text-xs">{{ JSON.stringify(selected?.after, null, 2) }}</pre></div>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div><p class="mb-2 text-xs font-medium text-muted-foreground">Metadata</p><pre class="max-h-60 overflow-auto rounded-md bg-muted p-3 text-xs">{{ JSON.stringify(selected?.metadata, null, 2) }}</pre></div>
          <div class="text-sm text-muted-foreground">
            <p>Time: <time :datetime="selected?.created_at">{{ formatDate(selected?.created_at) }}</time></p>
            <p class="mt-2">IP: {{ selected?.ip_address || '-' }}</p>
            <p class="mt-2 break-all">User agent: {{ selected?.user_agent || '-' }}</p>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
