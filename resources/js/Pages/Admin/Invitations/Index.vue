<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { MailPlus, RotateCcw, XCircle } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import ResponsiveList from '@/Components/shared/ResponsiveList.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'
import type { Paginated } from '@/types'

type Invitation = {
  id: number
  company?: string
  name: string
  email: string
  role_name: string
  accepted_at?: string
  revoked_at?: string
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
const resend = (invitation: Invitation) => router.patch(route('admin.invitations.resend', invitation.id), {}, { preserveScroll: true })
const revoke = (invitation: Invitation) => router.delete(route('admin.invitations.revoke', invitation.id), { preserveScroll: true })
const invitationStatus = (invitation: Invitation) => invitation.accepted_at ? 'accepted' : invitation.revoked_at ? 'revoked' : 'pending'
</script>

<template>
  <AdminLayout title="Invitations">
    <PageHeader
      title="Invitations"
      description="Send, resend, and revoke customer access invitations with a clear audit-friendly list."
      eyebrow="Customers"
    />

    <section class="grid gap-6 xl:grid-cols-[1fr_360px]">
      <ResponsiveList>
        <div class="flex items-center justify-between bg-muted/30 px-4 py-3">
          <p class="text-sm font-medium">Invitation queue</p>
          <p class="text-sm text-muted-foreground">{{ invitations.data.length }} visible</p>
        </div>
        <div v-for="invitation in invitations.data" :key="invitation.id" class="grid gap-3 p-4 transition-colors hover:bg-secondary/40 lg:grid-cols-[minmax(0,1fr)_minmax(160px,0.45fr)_minmax(140px,0.35fr)_auto] lg:items-center">
          <div class="min-w-0">
            <p class="truncate font-medium">{{ invitation.name }}</p>
            <p class="truncate text-sm text-muted-foreground">{{ invitation.email }}</p>
          </div>
          <div class="min-w-0">
            <p class="truncate text-sm font-medium">{{ invitation.company || 'Workspace' }}</p>
            <p class="text-xs text-muted-foreground">{{ invitation.role_name }}</p>
          </div>
          <StatusBadge :status="invitationStatus(invitation)" />
          <div class="flex justify-start gap-2 lg:justify-end">
            <Button v-if="!invitation.accepted_at" type="button" size="sm" variant="secondary" @click="resend(invitation)">
              <RotateCcw class="h-4 w-4" />
              Resend
            </Button>
            <Button v-if="!invitation.accepted_at && !invitation.revoked_at" type="button" size="sm" variant="destructive" @click="revoke(invitation)">
              <XCircle class="h-4 w-4" />
              Revoke
            </Button>
          </div>
        </div>
      </ResponsiveList>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-sm">
            <MailPlus class="h-4 w-4 text-primary" />
            Invite user
          </CardTitle>
        </CardHeader>
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
