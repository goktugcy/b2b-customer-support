<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
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
  created_at?: string
}

defineProps<{ logs: Paginated<LogRow> }>()
</script>

<template>
  <AdminLayout title="Audit Logs">
    <Card class="overflow-hidden">
      <CardContent class="p-0">
        <Table class="table-fixed">
          <TableHeader class="bg-muted/50">
            <TableRow><TableHead>Action</TableHead><TableHead>Company</TableHead><TableHead>Actor</TableHead><TableHead>Time</TableHead></TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="log in logs.data" :key="log.id">
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
  </AdminLayout>
</template>
