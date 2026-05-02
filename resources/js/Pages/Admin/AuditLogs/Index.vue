<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Download } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Input from '@/Components/ui/input/Input.vue'
import Table from '@/Components/ui/table/Table.vue'
import TableBody from '@/Components/ui/table/TableBody.vue'
import TableCell from '@/Components/ui/table/TableCell.vue'
import TableHead from '@/Components/ui/table/TableHead.vue'
import TableHeader from '@/Components/ui/table/TableHeader.vue'
import TableRow from '@/Components/ui/table/TableRow.vue'
import Pagination from '@/Components/shared/Pagination.vue'
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
</script>

<template>
  <AdminLayout title="Audit Logs">
    <Card>
      <CardHeader>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <CardTitle class="text-sm">Filters</CardTitle>
          <Link :href="route('admin.audit-logs.csv', params)"><Button variant="secondary"><Download class="h-4 w-4" /> Export CSV</Button></Link>
        </div>
      </CardHeader>
      <CardContent>
        <div class="grid gap-3 md:grid-cols-5">
          <Input v-model="filter.action" placeholder="Action" @keydown.enter.prevent="applyFilters" />
          <Input v-model="filter.company" placeholder="Company" @keydown.enter.prevent="applyFilters" />
          <Input v-model="filter.actor" placeholder="Actor" @keydown.enter.prevent="applyFilters" />
          <Input v-model="filter.from" type="date" />
          <Input v-model="filter.to" type="date" />
        </div>
        <div class="mt-3 flex justify-end"><Button @click="applyFilters">Apply filters</Button></div>
      </CardContent>
    </Card>

    <Card class="mt-4 overflow-hidden">
      <CardContent class="p-0">
        <Table class="table-fixed">
          <TableHeader class="bg-muted/50">
            <TableRow><TableHead>Action</TableHead><TableHead>Company</TableHead><TableHead>Actor</TableHead><TableHead>Time</TableHead></TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="log in logs.data" :key="log.id" class="cursor-pointer" @click="selected = log">
              <TableCell class="font-medium">{{ log.action }}</TableCell>
              <TableCell class="text-muted-foreground">{{ log.company || 'System' }}</TableCell>
              <TableCell class="text-muted-foreground">{{ log.actor || 'System' }}</TableCell>
              <TableCell class="text-muted-foreground">{{ log.created_at }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
    <div class="mt-4"><Pagination :links="logs.links" /></div>

    <Card v-if="selected" class="mt-4">
      <CardHeader>
        <div class="flex items-center justify-between gap-3">
          <CardTitle class="text-sm">{{ selected.action }}</CardTitle>
          <Button variant="ghost" size="sm" @click="selected = null">Close</Button>
        </div>
      </CardHeader>
      <CardContent>
        <div class="grid gap-4 md:grid-cols-2">
          <div><p class="mb-2 text-xs font-medium text-muted-foreground">Before</p><pre class="max-h-96 overflow-auto rounded-md bg-muted p-3 text-xs">{{ JSON.stringify(selected.before, null, 2) }}</pre></div>
          <div><p class="mb-2 text-xs font-medium text-muted-foreground">After</p><pre class="max-h-96 overflow-auto rounded-md bg-muted p-3 text-xs">{{ JSON.stringify(selected.after, null, 2) }}</pre></div>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div><p class="mb-2 text-xs font-medium text-muted-foreground">Metadata</p><pre class="max-h-60 overflow-auto rounded-md bg-muted p-3 text-xs">{{ JSON.stringify(selected.metadata, null, 2) }}</pre></div>
          <div class="text-sm text-muted-foreground"><p>IP: {{ selected.ip_address || '-' }}</p><p class="mt-2 break-all">User agent: {{ selected.user_agent || '-' }}</p></div>
        </div>
      </CardContent>
    </Card>
  </AdminLayout>
</template>
