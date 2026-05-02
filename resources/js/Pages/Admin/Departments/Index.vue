<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { AlertTriangle, Network, Pencil, Plus, Power, UserPlus, Users } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Dialog from '@/Components/ui/dialog/Dialog.vue'
import DialogContent from '@/Components/ui/dialog/DialogContent.vue'
import DialogDescription from '@/Components/ui/dialog/DialogDescription.vue'
import DialogFooter from '@/Components/ui/dialog/DialogFooter.vue'
import DialogHeader from '@/Components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/Components/ui/dialog/DialogTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import MultiSelectCombobox from '@/Components/shared/MultiSelectCombobox.vue'
import type { MultiSelectOption } from '@/types'

type Department = {
  id: string
  name: string
  description?: string | null
  status: 'active' | 'disabled'
  user_ids: string[]
  users: { id: string; name: string }[]
}

const props = defineProps<{
  departments: Department[]
  providerUsers: MultiSelectOption[]
}>()

const formOpen = ref(false)
const disableOpen = ref(false)
const selectedDepartment = ref<Department | null>(null)
const departmentToDisable = ref<Department | null>(null)
const activeCount = computed(() => props.departments.filter((department) => department.status === 'active').length)

const form = useForm({
  name: '',
  description: '',
  status: 'active',
  user_ids: [] as string[],
})

const openCreate = () => {
  selectedDepartment.value = null
  form.reset()
  form.clearErrors()
  form.status = 'active'
  formOpen.value = true
}

const openEdit = (department: Department) => {
  selectedDepartment.value = department
  form.clearErrors()
  form.name = department.name
  form.description = department.description ?? ''
  form.status = department.status
  form.user_ids = [...department.user_ids]
  formOpen.value = true
}

const closeForm = () => {
  formOpen.value = false
  selectedDepartment.value = null
  form.reset()
  form.clearErrors()
}

const submitDepartment = () => {
  if (selectedDepartment.value) {
    form.patch(route('admin.departments.update', selectedDepartment.value.id), {
      preserveScroll: true,
      onSuccess: closeForm,
    })
    return
  }

  form.post(route('admin.departments.store'), {
    preserveScroll: true,
    onSuccess: closeForm,
  })
}

const confirmDisable = (department: Department) => {
  departmentToDisable.value = department
  disableOpen.value = true
}

const disableDepartment = () => {
  if (!departmentToDisable.value) {
    return
  }

  router.delete(route('admin.departments.destroy', departmentToDisable.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      disableOpen.value = false
      departmentToDisable.value = null
    },
  })
}

const statusTone = (status: Department['status']) => status === 'active' ? 'green' : 'red'
const initials = (name: string) => name.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase()
</script>

<template>
  <AdminLayout title="Departments">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold tracking-normal">Departments</h2>
        <p class="mt-1 text-sm text-muted-foreground">Organize provider-side teams for routing, watchers, and ticket ownership.</p>
      </div>
      <Button type="button" @click="openCreate">
        <Plus class="h-4 w-4" />
        New department
      </Button>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-3">
      <Card>
        <CardContent class="flex items-center gap-3 p-4">
          <div class="flex h-10 w-10 items-center justify-center rounded-md border bg-secondary text-primary">
            <Network class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs text-muted-foreground">Departments</p>
            <p class="text-2xl font-semibold">{{ departments.length }}</p>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="flex items-center gap-3 p-4">
          <div class="flex h-10 w-10 items-center justify-center rounded-md border bg-secondary text-primary">
            <Power class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs text-muted-foreground">Active</p>
            <p class="text-2xl font-semibold">{{ activeCount }}</p>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="flex items-center gap-3 p-4">
          <div class="flex h-10 w-10 items-center justify-center rounded-md border bg-secondary text-primary">
            <Users class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs text-muted-foreground">Provider users</p>
            <p class="text-2xl font-semibold">{{ providerUsers.length }}</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <div v-if="departments.length" class="mt-4 grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
      <Card v-for="department in departments" :key="department.id" class="transition-colors hover:border-primary/30">
        <CardContent class="space-y-5 p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="truncate text-base font-semibold">{{ department.name }}</h3>
                <Badge :tone="statusTone(department.status)">{{ department.status }}</Badge>
              </div>
              <p class="mt-2 line-clamp-2 text-sm text-muted-foreground">
                {{ department.description || 'No department description yet.' }}
              </p>
            </div>
            <div class="flex shrink-0 gap-1">
              <Button type="button" variant="ghost" size="icon" class="h-9 w-9" aria-label="Edit department" @click="openEdit(department)">
                <Pencil class="h-4 w-4" />
              </Button>
              <Button type="button" variant="ghost" size="icon" class="h-9 w-9 text-destructive hover:text-destructive" aria-label="Disable department" @click="confirmDisable(department)">
                <Power class="h-4 w-4" />
              </Button>
            </div>
          </div>

          <div>
            <div class="mb-2 flex items-center justify-between text-xs text-muted-foreground">
              <span>Members</span>
              <span>{{ department.users.length }}</span>
            </div>
            <div v-if="department.users.length" class="flex flex-wrap gap-2">
              <span
                v-for="user in department.users"
                :key="user.id"
                class="inline-flex items-center gap-2 rounded-md border bg-background px-2 py-1 text-xs font-medium"
              >
                <span class="flex h-6 w-6 items-center justify-center rounded bg-secondary text-[10px] text-primary">{{ initials(user.name) }}</span>
                {{ user.name }}
              </span>
            </div>
            <div v-else class="rounded-md border border-dashed bg-muted/25 px-3 py-4 text-center text-sm text-muted-foreground">
              No members assigned.
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card v-else class="mt-4">
      <CardContent class="p-6">
        <EmptyState title="No departments yet" description="Create departments to route tickets to the right provider team." />
      </CardContent>
    </Card>

    <Dialog v-model:open="formOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ selectedDepartment ? 'Edit department' : 'New department' }}</DialogTitle>
          <DialogDescription>Set the department details and assign active provider users.</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitDepartment">
          <div>
            <Label>Name</Label>
            <Input v-model="form.name" class="mt-1" required />
            <FieldError :message="form.errors.name" />
          </div>
          <div>
            <Label>Description</Label>
            <Textarea v-model="form.description" class="mt-1" :rows="3" />
            <FieldError :message="form.errors.description" />
          </div>
          <div v-if="selectedDepartment">
            <Label>Status</Label>
            <Select v-model="form.status" class="mt-1">
              <option value="active">Active</option>
              <option value="disabled">Disabled</option>
            </Select>
            <FieldError :message="form.errors.status" />
          </div>
          <div>
            <Label>Members</Label>
            <MultiSelectCombobox v-model="form.user_ids" class="mt-1" :options="providerUsers" placeholder="Add members" />
            <FieldError :message="form.errors.user_ids" />
          </div>
          <DialogFooter>
            <Button type="button" variant="secondary" @click="closeForm">Cancel</Button>
            <Button type="submit" :disabled="form.processing">
              <UserPlus class="h-4 w-4" />
              {{ selectedDepartment ? 'Save changes' : 'Create department' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="disableOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>Disable department</DialogTitle>
          <DialogDescription>This department will no longer be used for active routing. Existing tickets keep their history.</DialogDescription>
        </DialogHeader>
        <div class="flex gap-3 rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
          <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
          <p>Disable {{ departmentToDisable?.name }}?</p>
        </div>
        <DialogFooter>
          <Button type="button" variant="secondary" @click="disableOpen = false">Cancel</Button>
          <Button type="button" variant="danger" @click="disableDepartment">Disable</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
