<script setup lang="ts">
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import FilePicker from '@/Components/shared/FilePicker.vue'
import MultiSelectChips from '@/Components/shared/MultiSelectChips.vue'
import type { MultiSelectOption, SelectOption } from '@/types'

const props = defineProps<{
  priorities: SelectOption[]
  departments: MultiSelectOption[]
  providerUsers: MultiSelectOption[]
}>()

const form = useForm({
  subject: '',
  description: '',
  priority: 'normal',
  target_department_ids: [] as string[],
  target_user_ids: [] as string[],
  attachments: [] as File[],
})

const targetErrors = computed(() => form.errors.target_department_ids || form.errors.target_user_ids || (form.errors as Record<string, string | undefined>).targets)
const filteredProviderUsers = computed(() => {
  if (!form.target_department_ids.length) {
    return props.providerUsers
  }

  return props.providerUsers.filter((user) => user.department_ids?.some((id) => form.target_department_ids.includes(id)))
})

const submit = () => form.post(route('portal.tickets.store'), { forceFormData: true })
</script>

<template>
  <PortalLayout title="Create ticket">
    <form class="max-w-3xl rounded-md border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
      <div class="space-y-4">
        <div>
          <Label>Subject</Label>
          <Input v-model="form.subject" class="mt-1" required />
          <FieldError :message="form.errors.subject" />
        </div>
        <div>
          <Label>Description</Label>
          <Textarea v-model="form.description" class="mt-1" :rows="8" required />
          <FieldError :message="form.errors.description" />
        </div>
        <div>
          <Label>Priority</Label>
          <Select v-model="form.priority" class="mt-1">
            <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
          </Select>
        </div>
        <div>
          <Label>Target departments</Label>
          <MultiSelectChips v-model="form.target_department_ids" class="mt-1" :options="departments" placeholder="Add department" />
        </div>
        <div>
          <Label>Target users</Label>
          <MultiSelectChips v-model="form.target_user_ids" class="mt-1" :options="filteredProviderUsers" placeholder="Add provider user" />
          <FieldError :message="targetErrors" />
        </div>
        <div>
          <Label>Attachments</Label>
          <FilePicker v-model="form.attachments" class="mt-1" />
          <FieldError :message="form.errors.attachments" />
        </div>
        <Button type="submit" :disabled="form.processing">Submit ticket</Button>
      </div>
    </form>
  </PortalLayout>
</template>
