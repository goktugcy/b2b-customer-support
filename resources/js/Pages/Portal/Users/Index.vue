<script setup lang="ts">
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { MailPlus, Pencil, RotateCcw, ShieldCheck, XCircle } from 'lucide-vue-next'
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
import Avatar from '@/Components/ui/avatar/Avatar.vue'
import Dialog from '@/Components/ui/dialog/Dialog.vue'
import DialogContent from '@/Components/ui/dialog/DialogContent.vue'
import DialogDescription from '@/Components/ui/dialog/DialogDescription.vue'
import DialogFooter from '@/Components/ui/dialog/DialogFooter.vue'
import DialogHeader from '@/Components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/Components/ui/dialog/DialogTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import ResponsiveList from '@/Components/shared/ResponsiveList.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'

type User = { id: string; name: string; email: string; status: string; roles: string[] }
type Invitation = { id: number; name: string; email: string; role_name: string; accepted_at?: string; expires_at?: string; revoked_at?: string }

defineProps<{ users: User[]; invitations: Invitation[]; roles: string[] }>()

const form = useForm({ name: '', email: '', role_name: 'customer_user' })
const editForm = useForm({ role_name: '', status: '' })
const selectedUser = ref<User | null>(null)
const editOpen = ref(false)

const submit = () => form.post(route('portal.users.invitations.store'), { preserveScroll: true, onSuccess: () => form.reset('name', 'email') })
const openEdit = (user: User) => {
  selectedUser.value = user
  editForm.role_name = user.roles[0] ?? 'customer_user'
  editForm.status = user.status
  editForm.clearErrors()
  editOpen.value = true
}
const closeEdit = () => {
  selectedUser.value = null
  editOpen.value = false
  editForm.reset()
  editForm.clearErrors()
}
const saveUser = () => {
  if (!selectedUser.value) return

  editForm.patch(route('portal.users.update', selectedUser.value.id), {
    preserveScroll: true,
    onSuccess: closeEdit,
  })
}
const resend = (invitation: Invitation) => router.patch(route('portal.users.invitations.resend', invitation.id), {}, { preserveScroll: true })
const revoke = (invitation: Invitation) => router.delete(route('portal.users.invitations.revoke', invitation.id), { preserveScroll: true })
const invitationStatus = (invitation: Invitation) => invitation.accepted_at ? 'accepted' : invitation.revoked_at ? 'revoked' : 'pending'
</script>

<template>
  <PortalLayout title="Users">
    <PageHeader
      title="Workspace users"
      description="Manage customer users and pending invitations for your company workspace."
      eyebrow="Administration"
    />

    <section class="grid gap-6 lg:grid-cols-[1fr_360px]">
      <ResponsiveList>
        <div class="flex items-center justify-between bg-muted/30 px-4 py-3">
          <p class="text-sm font-medium">Company users</p>
          <p class="text-sm text-muted-foreground">{{ users.length }} active records</p>
        </div>
        <div v-for="user in users" :key="user.id" class="grid gap-3 p-4 transition-colors hover:bg-secondary/40 lg:grid-cols-[minmax(0,1fr)_minmax(220px,0.65fr)_auto] lg:items-center">
          <div class="flex min-w-0 items-center gap-3">
            <Avatar :name="user.name" class="h-10 w-10" />
            <div class="min-w-0">
              <p class="truncate font-medium">{{ user.name }}</p>
              <p class="truncate text-sm text-muted-foreground">{{ user.email }}</p>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <Badge v-for="role in user.roles" :key="role" tone="neutral">
              <ShieldCheck class="mr-1 h-3 w-3" />
              {{ role }}
            </Badge>
            <StatusBadge :status="user.status" />
          </div>
          <div class="flex justify-start lg:justify-end">
            <Button type="button" size="sm" variant="secondary" @click="openEdit(user)">
              <Pencil class="h-4 w-4" />
              Manage
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
            <div><Label>Name</Label><Input v-model="form.name" class="mt-1" /><FieldError :message="form.errors.name" /></div>
            <div><Label>Email</Label><Input v-model="form.email" type="email" class="mt-1" /><FieldError :message="form.errors.email" /></div>
            <div><Label>Role</Label><Select v-model="form.role_name" class="mt-1"><option v-for="role in roles" :key="role" :value="role">{{ role }}</option></Select></div>
            <Button type="submit" class="w-full" :disabled="form.processing">Send invitation</Button>
          </form>
        </CardContent>
      </Card>
    </section>

    <ResponsiveList>
      <div class="flex items-center justify-between bg-muted/30 px-4 py-3">
        <p class="text-sm font-medium">Invitations</p>
        <p class="text-sm text-muted-foreground">{{ invitations.length }} records</p>
      </div>
      <div v-for="invitation in invitations" :key="invitation.id" class="grid gap-3 p-4 transition-colors hover:bg-secondary/40 lg:grid-cols-[minmax(0,1fr)_minmax(140px,0.35fr)_auto] lg:items-center">
        <div class="min-w-0">
          <p class="truncate font-medium">{{ invitation.name }}</p>
          <p class="truncate text-sm text-muted-foreground">{{ invitation.email }} · {{ invitation.role_name }}</p>
        </div>
        <StatusBadge :status="invitationStatus(invitation)" />
        <div v-if="!invitation.accepted_at" class="flex justify-start gap-2 lg:justify-end">
          <Button type="button" size="sm" variant="secondary" @click="resend(invitation)">
            <RotateCcw class="h-4 w-4" />
            Resend
          </Button>
          <Button v-if="!invitation.revoked_at" type="button" size="sm" variant="destructive" @click="revoke(invitation)">
            <XCircle class="h-4 w-4" />
            Revoke
          </Button>
        </div>
      </div>
    </ResponsiveList>

    <Dialog v-model:open="editOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Manage user</DialogTitle>
          <DialogDescription>{{ selectedUser?.name }} · {{ selectedUser?.email }}</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="saveUser">
          <div>
            <Label>Role</Label>
            <Select v-model="editForm.role_name" class="mt-1">
              <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
            </Select>
          </div>
          <div>
            <Label>Status</Label>
            <Select v-model="editForm.status" class="mt-1">
              <option value="active">active</option>
              <option value="disabled">disabled</option>
            </Select>
          </div>
          <DialogFooter>
            <Button type="button" variant="secondary" @click="closeEdit">Cancel</Button>
            <Button type="submit" :disabled="editForm.processing">Save changes</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </PortalLayout>
</template>
