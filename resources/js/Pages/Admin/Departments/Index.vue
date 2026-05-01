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
      <Card class="overflow-hidden">
        <CardContent class="p-0">
          <Table class="table-fixed">
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="w-[28%]">Department</TableHead>
                <TableHead>Members</TableHead>
                <TableHead class="w-[150px]">Status</TableHead>
                <TableHead class="w-[170px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="department in rows" :key="department.id" class="align-top">
                <TableCell class="space-y-2">
                  <Input v-model="department.name" />
                  <Textarea v-model="department.description" :rows="2" />
                </TableCell>
                <TableCell>
                  <MultiSelectChips v-model="department.user_ids" :options="providerUsers" placeholder="Add member" />
                </TableCell>
                <TableCell>
                  <Select v-model="department.status">
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                  </Select>
                </TableCell>
                <TableCell class="space-y-2">
                  <Button type="button" variant="secondary" class="w-full" @click="updateDepartment(department)">Save</Button>
                  <Button type="button" variant="danger" class="w-full" @click="disableDepartment(department)">Disable</Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle class="text-sm">New department</CardTitle>
            <Badge tone="blue">Provider</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <form class="space-y-4" @submit.prevent="createDepartment">
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
          </form>
        </CardContent>
      </Card>
    </section>
  </AdminLayout>
</template>
