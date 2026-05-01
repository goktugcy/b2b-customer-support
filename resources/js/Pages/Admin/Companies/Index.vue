<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Select from '@/Components/ui/select/Select.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Table from '@/Components/ui/table/Table.vue'
import TableBody from '@/Components/ui/table/TableBody.vue'
import TableCell from '@/Components/ui/table/TableCell.vue'
import TableHead from '@/Components/ui/table/TableHead.vue'
import TableHeader from '@/Components/ui/table/TableHeader.vue'
import TableRow from '@/Components/ui/table/TableRow.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import type { Paginated } from '@/types'

type CompanyRow = {
  id: string
  name: string
  slug: string
  type: string
  status: string
  users_count: number
  tickets_count: number
}

defineProps<{ companies: Paginated<CompanyRow> }>()

const form = useForm({
  name: '',
  slug: '',
  type: 'client',
  timezone: 'UTC',
})

const submit = () => form.post(route('admin.companies.store'), { preserveScroll: true, onSuccess: () => form.reset() })
</script>

<template>
  <AdminLayout title="Companies">
    <section class="grid gap-6 xl:grid-cols-[1fr_340px]">
      <Card class="overflow-hidden">
        <CardContent class="p-0">
          <Table class="table-fixed">
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="w-[38%]">Company</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Users</TableHead>
                <TableHead>Tickets</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="company in companies.data" :key="company.id">
                <TableCell>
                  <Link :href="route('admin.companies.show', company.id)" class="font-medium text-foreground transition-colors hover:text-primary">{{ company.name }}</Link>
                  <p class="text-xs text-muted-foreground">{{ company.slug }}</p>
                </TableCell>
                <TableCell><Badge :tone="company.type === 'provider' ? 'blue' : 'green'">{{ company.type }}</Badge></TableCell>
                <TableCell class="text-muted-foreground">{{ company.users_count }}</TableCell>
                <TableCell class="text-muted-foreground">{{ company.tickets_count }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle class="text-sm">Create company</CardTitle></CardHeader>
        <CardContent>
          <form class="space-y-3" @submit.prevent="submit">
            <div>
              <Label>Name</Label>
              <Input v-model="form.name" class="mt-1" required />
              <FieldError :message="form.errors.name" />
            </div>
            <div>
              <Label>Slug</Label>
              <Input v-model="form.slug" class="mt-1" required />
              <FieldError :message="form.errors.slug" />
            </div>
            <div>
              <Label>Type</Label>
              <Select v-model="form.type" class="mt-1">
                <option value="client">Client</option>
                <option value="provider">Provider</option>
              </Select>
            </div>
            <div>
              <Label>Timezone</Label>
              <Input v-model="form.timezone" class="mt-1" required />
            </div>
            <Button type="submit" class="w-full">Create</Button>
          </form>
        </CardContent>
      </Card>
    </section>
    <div class="mt-4">
      <Pagination :links="companies.links" />
    </div>
  </AdminLayout>
</template>
