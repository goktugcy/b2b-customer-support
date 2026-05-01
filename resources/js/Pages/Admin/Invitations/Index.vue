<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
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

type Invitation = {
  id: number
  company?: string
  name: string
  email: string
  role_name: string
  accepted_at?: string
  expires_at?: string
}

const props = defineProps<{
  invitations: Paginated<Invitation>
  companies: { public_id: string; name: string; type: string }[]
  roles: string[]
}>()

const form = useForm({
  company_id: props.companies[0]?.public_id ?? '',
  name: '',
  email: '',
  role_name: 'customer_user',
})

const submit = () => form.post(route('admin.invitations.store'), { preserveScroll: true, onSuccess: () => form.reset('name', 'email') })
</script>

<template>
  <AdminLayout title="Invitations">
    <section class="grid gap-6 xl:grid-cols-[1fr_340px]">
      <Card class="overflow-hidden">
        <CardContent class="p-0">
          <Table class="table-fixed">
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead>Invitee</TableHead>
                <TableHead>Company</TableHead>
                <TableHead>Role</TableHead>
                <TableHead>Accepted</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="invitation in invitations.data" :key="invitation.id">
                <TableCell><p class="font-medium">{{ invitation.name }}</p><p class="text-xs text-muted-foreground">{{ invitation.email }}</p></TableCell>
                <TableCell class="text-muted-foreground">{{ invitation.company }}</TableCell>
                <TableCell class="text-muted-foreground">{{ invitation.role_name }}</TableCell>
                <TableCell class="text-muted-foreground">{{ invitation.accepted_at || 'Pending' }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle class="text-sm">Invite user</CardTitle></CardHeader>
        <CardContent>
          <form class="space-y-3" @submit.prevent="submit">
            <div><Label>Company</Label><Select v-model="form.company_id" class="mt-1"><option v-for="company in companies" :key="company.public_id" :value="company.public_id">{{ company.name }}</option></Select></div>
            <div><Label>Name</Label><Input v-model="form.name" class="mt-1" /><FieldError :message="form.errors.name" /></div>
            <div><Label>Email</Label><Input v-model="form.email" type="email" class="mt-1" /><FieldError :message="form.errors.email" /></div>
            <div><Label>Role</Label><Select v-model="form.role_name" class="mt-1"><option v-for="role in roles" :key="role" :value="role">{{ role }}</option></Select><FieldError :message="form.errors.role_name" /></div>
            <Button type="submit" class="w-full">Send invitation</Button>
          </form>
        </CardContent>
      </Card>
    </section>
    <div class="mt-4"><Pagination :links="invitations.links" /></div>
  </AdminLayout>
</template>
