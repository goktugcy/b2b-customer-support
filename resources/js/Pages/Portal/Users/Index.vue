<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
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
type Invitation = { id: number; name: string; email: string; role_name: string; accepted_at?: string; expires_at?: string }

defineProps<{ users: User[]; invitations: Invitation[]; roles: string[] }>()

const form = useForm({ name: '', email: '', role_name: 'customer_user' })
const submit = () => form.post(route('portal.users.invitations.store'), { preserveScroll: true, onSuccess: () => form.reset('name', 'email') })
</script>

<template>
  <PortalLayout title="Users">
    <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
      <Card>
        <CardHeader><CardTitle class="text-sm">Company users</CardTitle></CardHeader>
        <CardContent>
          <div class="divide-y">
            <div v-for="user in users" :key="user.id" class="flex items-center justify-between gap-4 py-3 text-sm first:pt-0 last:pb-0">
              <div><p class="font-medium">{{ user.name }}</p><p class="text-muted-foreground">{{ user.email }} · {{ user.roles.join(', ') }}</p></div>
              <Badge :tone="user.status === 'active' ? 'green' : 'red'">{{ user.status }}</Badge>
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
  </PortalLayout>
</template>
