<script setup lang="ts">
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import MultiSelectChips from '@/Components/shared/MultiSelectChips.vue'
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

const rows = ref(props.departments.map((department) => ({ ...department, user_ids: [...department.user_ids] })))

const form = useForm({
  name: '',
  description: '',
  user_ids: [] as string[],
})

const createDepartment = () => {
  form.post(route('admin.departments.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  })
}

const updateDepartment = (department: Department) => {
  router.patch(route('admin.departments.update', department.id), {
    name: department.name,
    description: department.description,
    status: department.status,
    user_ids: department.user_ids,
  }, { preserveScroll: true })
}

const disableDepartment = (department: Department) => {
  router.delete(route('admin.departments.destroy', department.id), { preserveScroll: true })
}
</script>

<template>
  <AdminLayout title="Departments">
    <section class="grid gap-6 xl:grid-cols-[1fr_360px]">
      <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <table class="w-full table-fixed divide-y divide-slate-200">
          <thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
            <tr>
              <th class="w-[28%] px-4 py-3">Department</th>
              <th class="px-4 py-3">Members</th>
              <th class="w-[150px] px-4 py-3">Status</th>
              <th class="w-[170px] px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="department in rows" :key="department.id" class="align-top text-sm">
              <td class="space-y-2 px-4 py-4">
                <Input v-model="department.name" />
                <Textarea v-model="department.description" :rows="2" />
              </td>
              <td class="px-4 py-4">
                <MultiSelectChips v-model="department.user_ids" :options="providerUsers" placeholder="Add member" />
              </td>
              <td class="px-4 py-4">
                <Select v-model="department.status">
                  <option value="active">Active</option>
                  <option value="disabled">Disabled</option>
                </Select>
              </td>
              <td class="space-y-2 px-4 py-4">
                <Button type="button" variant="secondary" class="w-full" @click="updateDepartment(department)">Save</Button>
                <Button type="button" variant="danger" class="w-full" @click="disableDepartment(department)">Disable</Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="createDepartment">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-950">New department</h2>
          <Badge tone="blue">Provider</Badge>
        </div>
        <div class="space-y-4">
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
          <div>
            <Label>Members</Label>
            <MultiSelectChips v-model="form.user_ids" class="mt-1" :options="providerUsers" placeholder="Add member" />
            <FieldError :message="form.errors.user_ids" />
          </div>
          <Button type="submit" class="w-full" :disabled="form.processing">Create</Button>
        </div>
      </form>
    </section>
  </AdminLayout>
</template>
