<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
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

type UserRow = {
  id: string
  name: string
  email: string
  company?: string
  status: string
  roles: string[]
  last_login_at?: string
}

defineProps<{ users: Paginated<UserRow> }>()
</script>

<template>
  <AdminLayout title="Users">
    <Card class="overflow-hidden">
      <CardContent class="p-0">
        <Table class="table-fixed">
          <TableHeader class="bg-muted/50">
            <TableRow>
              <TableHead class="w-[32%]">User</TableHead>
              <TableHead>Company</TableHead>
              <TableHead>Roles</TableHead>
              <TableHead>Status</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="user in users.data" :key="user.id">
              <TableCell>
                <p class="font-medium">{{ user.name }}</p>
                <p class="text-xs text-muted-foreground">{{ user.email }}</p>
              </TableCell>
              <TableCell class="text-muted-foreground">{{ user.company }}</TableCell>
              <TableCell class="text-muted-foreground">{{ user.roles.join(', ') }}</TableCell>
              <TableCell><Badge :tone="user.status === 'active' ? 'green' : 'red'">{{ user.status }}</Badge></TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
    <div class="mt-4"><Pagination :links="users.links" /></div>
  </AdminLayout>
</template>
