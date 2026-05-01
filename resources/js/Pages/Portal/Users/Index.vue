<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Select from '@/Components/ui/select/Select.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'

type User = { id: string; name: string; email: string; status: string; roles: string[] }
type Invitation = { id: number; name: string; email: string; role_name: string; accepted_at?: string; expires_at?: string; revoked_at?: string }

defineProps<{ users: User[]; invitations: Invitation[]; roles: string[] }>()

const form = useForm({ name: '', email: '', role_name: 'customer_user' })
const submit = () => form.post(route('portal.users.invitations.store'), { preserveScroll: true, onSuccess: () => form.reset('name', 'email') })
const saveUser = (user: User) => router.patch(route('portal.users.update', user.id), {
  role_name: user.roles[0] ?? 'customer_user',
  status: user.status,
}, { preserveScroll: true })
const resend = (invitation: Invitation) => router.patch(route('portal.users.invitations.resend', invitation.id), {}, { preserveScroll: true })
const revoke = (invitation: Invitation) => router.delete(route('portal.users.invitations.revoke', invitation.id), { preserveScroll: true })
</script>

<template>
  <PortalLayout title="Users">
    <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
      <Card>
        <CardHeader><CardTitle class="text-sm">Company users</CardTitle></CardHeader>
        <CardContent>
          <div class="divide-y">
            <div v-for="user in users" :key="user.id" class="flex items-center justify-between gap-4 py-3 text-sm first:pt-0 last:pb-0">
              <div class="min-w-0"><p class="font-medium">{{ user.name }}</p><p class="text-muted-foreground">{{ user.email }}</p></div>
              <div class="grid shrink-0 gap-2 sm:grid-cols-[150px_120px_auto]">
                <Select v-model="user.roles[0]"><option v-for="role in roles" :key="role" :value="role">{{ role }}</option></Select>
                <Select v-model="user.status"><option value="active">active</option><option value="disabled">disabled</option></Select>
                <Button type="button" size="sm" variant="secondary" @click="saveUser(user)">Save</Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle class="text-sm">Invite user</CardTitle></CardHeader>
        <CardContent>
          <form class="space-y-3" @submit.prevent="submit">
            <div><Label>Name</Label><Input v-model="form.name" class="mt-1" /><FieldError :message="form.errors.name" /></div>
            <div><Label>Email</Label><Input v-model="form.email" type="email" class="mt-1" /><FieldError :message="form.errors.email" /></div>
            <div><Label>Role</Label><Select v-model="form.role_name" class="mt-1"><option v-for="role in roles" :key="role" :value="role">{{ role }}</option></Select></div>
            <Button type="submit" class="w-full">Send invitation</Button>
          </form>
        </CardContent>
      </Card>
    </section>

    <Card class="mt-6">
      <CardHeader><CardTitle class="text-sm">Invitations</CardTitle></CardHeader>
      <CardContent>
        <div class="divide-y">
          <div v-for="invitation in invitations" :key="invitation.id" class="flex items-center justify-between gap-4 py-3 text-sm first:pt-0 last:pb-0">
            <div><p class="font-medium">{{ invitation.name }}</p><p class="text-muted-foreground">{{ invitation.email }} · {{ invitation.role_name }} · {{ invitation.accepted_at ? 'accepted' : invitation.revoked_at ? 'revoked' : 'pending' }}</p></div>
            <div v-if="!invitation.accepted_at" class="flex gap-2">
              <Button type="button" size="sm" variant="secondary" @click="resend(invitation)">Resend</Button>
              <Button type="button" size="sm" variant="destructive" @click="revoke(invitation)">Revoke</Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </PortalLayout>
</template>
