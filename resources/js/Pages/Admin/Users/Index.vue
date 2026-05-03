<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Pencil, ShieldCheck } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'
import Select from '@/Components/ui/select/Select.vue'
import Avatar from '@/Components/ui/avatar/Avatar.vue'
import Dialog from '@/Components/ui/dialog/Dialog.vue'
import DialogContent from '@/Components/ui/dialog/DialogContent.vue'
import DialogDescription from '@/Components/ui/dialog/DialogDescription.vue'
import DialogFooter from '@/Components/ui/dialog/DialogFooter.vue'
import DialogHeader from '@/Components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/Components/ui/dialog/DialogTitle.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import ResponsiveList from '@/Components/shared/ResponsiveList.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'
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

defineProps<{ users: Paginated<UserRow>; roles: string[]; statuses: string[] }>()

const selectedUser = ref<UserRow | null>(null)
const formOpen = ref(false)
const form = useForm({
  role_name: '',
  status: '',
})

const openEdit = (user: UserRow) => {
  selectedUser.value = user
  form.clearErrors()
  form.role_name = user.roles[0] ?? ''
  form.status = user.status
  formOpen.value = true
}

const closeEdit = () => {
  formOpen.value = false
  selectedUser.value = null
  form.reset()
  form.clearErrors()
}

const saveUser = () => {
  if (!selectedUser.value) return

  form.patch(route('admin.users.update', selectedUser.value.id), {
    preserveScroll: true,
    onSuccess: closeEdit,
  })
}
</script>

<template>
  <AdminLayout title="Users">
    <PageHeader
      title="Users"
      description="Manage provider and customer users, roles, and account status without leaving the people list."
      eyebrow="Customers"
    />

    <ResponsiveList>
      <div class="flex items-center justify-between bg-muted/30 px-4 py-3">
        <p class="text-sm font-medium">User directory</p>
        <p class="text-sm text-muted-foreground">{{ users.data.length }} visible</p>
      </div>
      <div v-for="user in users.data" :key="user.id" class="grid gap-3 p-4 transition-colors hover:bg-secondary/40 lg:grid-cols-[minmax(0,1fr)_minmax(160px,0.45fr)_minmax(180px,0.55fr)_auto] lg:items-center">
        <div class="flex min-w-0 items-center gap-3">
          <Avatar :name="user.name" class="h-10 w-10" />
          <div class="min-w-0">
            <p class="truncate font-medium">{{ user.name }}</p>
            <p class="truncate text-sm text-muted-foreground">{{ user.email }}</p>
          </div>
        </div>
        <div class="min-w-0">
          <p class="truncate text-sm font-medium">{{ user.company || 'System' }}</p>
          <p class="text-xs text-muted-foreground">Company</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Badge v-for="role in user.roles" :key="role" tone="neutral">
            <ShieldCheck class="mr-1 h-3 w-3" />
            {{ role }}
          </Badge>
          <StatusBadge :status="user.status" />
        </div>
        <div class="flex justify-start lg:justify-end">
          <Button type="button" variant="secondary" size="sm" @click="openEdit(user)">
            <Pencil class="h-4 w-4" />
            Manage
          </Button>
        </div>
      </div>
    </ResponsiveList>

    <div class="mt-4"><Pagination :links="users.links" /></div>

    <Dialog v-model:open="formOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Manage user</DialogTitle>
          <DialogDescription>{{ selectedUser?.name }} · {{ selectedUser?.email }}</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="saveUser">
          <div>
            <label class="text-sm font-medium">Role</label>
            <Select v-model="form.role_name" class="mt-1">
              <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
            </Select>
          </div>
          <div>
            <label class="text-sm font-medium">Status</label>
            <Select v-model="form.status" class="mt-1">
              <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
            </Select>
          </div>
          <DialogFooter>
            <Button type="button" variant="secondary" @click="closeEdit">Cancel</Button>
            <Button type="submit" :disabled="form.processing">Save changes</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
