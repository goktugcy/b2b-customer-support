<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
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
      <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <table class="w-full table-fixed divide-y divide-slate-200">
          <thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
            <tr><th class="px-4 py-3">Invitee</th><th class="px-4 py-3">Company</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Accepted</th></tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="invitation in invitations.data" :key="invitation.id" class="text-sm">
              <td class="px-4 py-3"><p class="font-medium">{{ invitation.name }}</p><p class="text-xs text-slate-500">{{ invitation.email }}</p></td>
              <td class="px-4 py-3 text-slate-600">{{ invitation.company }}</td>
              <td class="px-4 py-3 text-slate-600">{{ invitation.role_name }}</td>
              <td class="px-4 py-3 text-slate-600">{{ invitation.accepted_at || 'Pending' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="submit">
        <h2 class="text-sm font-semibold">Invite user</h2>
        <div class="mt-4 space-y-3">
          <div><Label>Company</Label><select v-model="form.company_id" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm"><option v-for="company in companies" :key="company.public_id" :value="company.public_id">{{ company.name }}</option></select></div>
          <div><Label>Name</Label><Input v-model="form.name" class="mt-1" /><FieldError :message="form.errors.name" /></div>
          <div><Label>Email</Label><Input v-model="form.email" type="email" class="mt-1" /><FieldError :message="form.errors.email" /></div>
          <div><Label>Role</Label><select v-model="form.role_name" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm"><option v-for="role in roles" :key="role" :value="role">{{ role }}</option></select><FieldError :message="form.errors.role_name" /></div>
          <Button type="submit" class="w-full">Send invitation</Button>
        </div>
      </form>
    </section>
    <div class="mt-4"><Pagination :links="invitations.links" /></div>
  </AdminLayout>
</template>
