<script setup lang="ts">
import { computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
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
import FilePicker from '@/Components/shared/FilePicker.vue'
import MultiSelectCombobox from '@/Components/shared/MultiSelectCombobox.vue'
import RichTextEditor from '@/Components/shared/RichTextEditor.vue'
import TagPicker from '@/Components/shared/TagPicker.vue'
import CustomFieldForm from '@/Components/shared/CustomFieldForm.vue'
import type { CategoryOption, CustomFieldDefinition, CustomFieldValues, MultiSelectOption, ProjectOption, SelectOption, TagOption, TrackerOption } from '@/types'

const props = defineProps<{
  companies: { public_id: string; name: string }[]
  departments: MultiSelectOption[]
  providerUsers: MultiSelectOption[]
  priorities: SelectOption[]
  projects: ProjectOption[]
  trackers: TrackerOption[]
  categories: CategoryOption[]
  tags: TagOption[]
  customFields: CustomFieldDefinition[]
}>()

const defaultCompany = props.companies[0]?.public_id ?? ''
const defaultProject = props.projects.find((project) => project.company_id === defaultCompany && project.is_default)?.id
  ?? props.projects.find((project) => project.company_id === defaultCompany)?.id
  ?? ''
const defaultTracker = props.trackers.find((tracker) => tracker.is_default)?.id ?? props.trackers[0]?.id ?? ''

const form = useForm({
  company_id: defaultCompany,
  project_id: defaultProject,
  tracker_id: defaultTracker,
  category_id: '',
  subject: '',
  description: '',
  priority: 'normal',
  tag_names: [] as string[],
  custom_fields: {} as CustomFieldValues,
  assigned_to_user_id: '',
  target_department_ids: [] as string[],
  target_user_ids: [] as string[],
  attachments: [] as File[],
})

const filteredProjects = computed(() => props.projects.filter((project) => project.company_id === form.company_id && project.status !== 'disabled'))
const filteredCategories = computed(() => props.categories.filter((category) => category.project_id === form.project_id && category.status !== 'disabled'))
const selectedCustomFields = computed(() => props.customFields.filter((field) => field.tracker_id === form.tracker_id && field.status !== 'disabled'))
const filteredProviderUsers = computed(() => {
  if (!form.target_department_ids.length) {
    return props.providerUsers
  }

  return props.providerUsers.filter((user) => user.department_ids?.some((id) => form.target_department_ids.includes(id)))
})
const targetErrors = computed(() => form.errors.target_department_ids || form.errors.target_user_ids || (form.errors as Record<string, string | undefined>).targets)

watch(() => form.company_id, () => {
  const project = filteredProjects.value.find((item) => item.is_default) ?? filteredProjects.value[0]
  form.project_id = project?.id ?? ''
  form.category_id = ''
})

watch(() => form.project_id, () => {
  if (!filteredCategories.value.some((category) => category.id === form.category_id)) {
    form.category_id = ''
  }
})

watch(() => form.tracker_id, () => {
  form.custom_fields = {}
})

const submit = () => form.post(route('admin.tickets.store'), {
  preserveScroll: true,
  forceFormData: true,
})
</script>

<template>
  <AdminLayout title="Create ticket">
    <div class="mb-4">
      <Link :href="route('admin.tickets.index')" class="link inline-flex items-center gap-2 text-sm">
        <ArrowLeft class="h-4 w-4" />
        Back to tickets
      </Link>
    </div>

    <form class="grid gap-6 xl:grid-cols-[1fr_360px]" @submit.prevent="submit">
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>New ticket</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
              <div>
                <Label>Company</Label>
                <Select v-model="form.company_id" class="mt-1">
                  <option v-for="company in companies" :key="company.public_id" :value="company.public_id">{{ company.name }}</option>
                </Select>
                <FieldError :message="form.errors.company_id" />
              </div>
              <div>
                <Label>Priority</Label>
                <Select v-model="form.priority" class="mt-1">
                  <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
                </Select>
              </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
              <div>
                <Label>Project</Label>
                <Select v-model="form.project_id" class="mt-1">
                  <option value="">Select project</option>
                  <option v-for="project in filteredProjects" :key="project.id" :value="project.id">{{ project.name }}</option>
                </Select>
                <FieldError :message="form.errors.project_id" />
              </div>
              <div>
                <Label>Tracker</Label>
                <Select v-model="form.tracker_id" class="mt-1">
                  <option v-for="tracker in trackers" :key="tracker.id" :value="tracker.id">{{ tracker.name }}</option>
                </Select>
                <FieldError :message="form.errors.tracker_id" />
              </div>
              <div>
                <Label>Category</Label>
                <Select v-model="form.category_id" class="mt-1">
                  <option value="">No category</option>
                  <option v-for="category in filteredCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </Select>
                <FieldError :message="form.errors.category_id" />
              </div>
            </div>

            <div>
              <Label>Subject</Label>
              <Input v-model="form.subject" class="mt-1" required />
              <FieldError :message="form.errors.subject" />
            </div>

            <div>
              <Label>Description</Label>
              <RichTextEditor v-model="form.description" class="mt-1" placeholder="Describe the issue" />
              <FieldError :message="form.errors.description" />
            </div>

            <div>
              <Label>Tags</Label>
              <TagPicker v-model="form.tag_names" class="mt-1" :options="tags" />
              <FieldError :message="form.errors.tag_names" />
            </div>

            <CustomFieldForm
              v-model="form.custom_fields"
              :fields="selectedCustomFields"
              :projects="filteredProjects"
              :categories="filteredCategories"
              :users="providerUsers"
              :errors="form.errors"
            />
          </CardContent>
        </Card>
      </div>

      <div class="space-y-4">
        <Card>
          <CardHeader><CardTitle class="text-sm">Routing</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label>Target departments</Label>
              <MultiSelectCombobox v-model="form.target_department_ids" class="mt-1" :options="departments" placeholder="Select departments" />
              <FieldError :message="targetErrors" />
            </div>
            <div>
              <Label>Target users</Label>
              <MultiSelectCombobox v-model="form.target_user_ids" class="mt-1" :options="filteredProviderUsers" placeholder="Select provider users" />
            </div>
            <div>
              <Label>Assignee</Label>
              <Select v-model="form.assigned_to_user_id" class="mt-1">
                <option value="">Unassigned</option>
                <option v-for="user in providerUsers" :key="user.id" :value="user.id">{{ user.name }}</option>
              </Select>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">Attachments</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <FilePicker v-model="form.attachments" />
            <FieldError :message="form.errors.attachments" />
            <Button type="submit" class="w-full" :disabled="form.processing">Create ticket</Button>
          </CardContent>
        </Card>
      </div>
    </form>
  </AdminLayout>
</template>
