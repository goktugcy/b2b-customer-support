<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import FieldError from '@/Components/shared/FieldError.vue'

type User = { id: string; name: string; email: string; status: string; roles: string[] }
type Invitation = { id: number; name: string; email: string; role_name: string; accepted_at?: string; expires_at?: string }

defineProps<{ users: User[]; invitations: Invitation[]; roles: string[] }>()

const form = useForm({ name: '', email: '', role_name: 'customer_user' })
const submit = () => form.post(route('portal.users.invitations.store'), { preserveScroll: true, onSuccess: () => form.reset('name', 'email') })
</script>

<template>
  <PortalLayout title="Users">
    <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
      <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold">Company users</h2>
        <div class="mt-4 divide-y divide-slate-100">
          <div v-for="user in users" :key="user.id" class="flex items-center justify-between gap-4 py-3 text-sm">
            <div><p class="font-medium">{{ user.name }}</p><p class="text-slate-500">{{ user.email }} · {{ user.roles.join(', ') }}</p></div>
            <Badge :tone="user.status === 'active' ? 'green' : 'red'">{{ user.status }}</Badge>
          </div>
        </div>
      </div>
      <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="submit">
        <h2 class="text-sm font-semibold">Invite user</h2>
        <div class="mt-4 space-y-3">
          <div><Label>Name</Label><Input v-model="form.name" class="mt-1" /><FieldError :message="form.errors.name" /></div>
          <div><Label>Email</Label><Input v-model="form.email" type="email" class="mt-1" /><FieldError :message="form.errors.email" /></div>
          <div><Label>Role</Label><select v-model="form.role_name" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm"><option v-for="role in roles" :key="role" :value="role">{{ role }}</option></select></div>
          <Button type="submit" class="w-full">Send invitation</Button>
        </div>
      </form>
    </section>
  </PortalLayout>
</template>
