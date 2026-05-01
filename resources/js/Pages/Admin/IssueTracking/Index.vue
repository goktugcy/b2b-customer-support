<script setup lang="ts">
import { ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import type { CategoryOption, CustomFieldDefinition, ProjectOption, TagOption, TrackerOption } from '@/types'

type CompanyOption = { id: string; name: string }
type TrackerWithFields = TrackerOption & { description?: string | null; custom_fields: CustomFieldDefinition[] }
type TagRow = { id: string; name: string; color: string }

const props = defineProps<{
  companies: CompanyOption[]
  projects: ProjectOption[]
  trackers: TrackerWithFields[]
  categories: CategoryOption[]
  tags: TagOption[]
  customFieldTypes: { value: string; label: string }[]
}>()

const projectForm = useForm({ company_id: props.companies[0]?.id ?? '', name: '', description: '', status: 'active', is_default: false })
const trackerForm = useForm({ name: '', description: '', color: '#2563eb', status: 'active', is_default: false, sort_order: 0 })
const categoryForm = useForm({ project_id: props.projects[0]?.id ?? '', name: '', description: '', status: 'active' })
const tagForm = useForm({ name: '', color: '#64748b' })
const tagRows = ref<TagRow[]>(props.tags.map((tag) => ({ id: tag.id ?? '', name: tag.name, color: tag.color ?? '#64748b' })))

watch(() => props.tags, (tags) => {
  tagRows.value = tags.map((tag) => ({ id: tag.id ?? '', name: tag.name, color: tag.color ?? '#64748b' }))
})
const fieldForm = useForm({
  tracker_id: props.trackers[0]?.id ?? '',
  name: '',
  type: 'text',
  is_required: false,
  validation_regex: '',
  status: 'active',
  sort_order: 0,
  options: '',
})

const submitProject = () => projectForm.post(route('admin.issue-tracking.projects.store'), { preserveScroll: true, onSuccess: () => projectForm.reset('name', 'description', 'is_default') })
const submitTracker = () => trackerForm.post(route('admin.issue-tracking.trackers.store'), { preserveScroll: true, onSuccess: () => trackerForm.reset('name', 'description', 'is_default', 'sort_order') })
const submitCategory = () => categoryForm.post(route('admin.issue-tracking.categories.store'), { preserveScroll: true, onSuccess: () => categoryForm.reset('name', 'description') })
const submitTag = () => tagForm.post(route('admin.issue-tracking.tags.store'), { preserveScroll: true, onSuccess: () => tagForm.reset('name') })
const submitField = () => fieldForm.post(route('admin.issue-tracking.custom-fields.store'), { preserveScroll: true, onSuccess: () => fieldForm.reset('name', 'validation_regex', 'options', 'is_required', 'sort_order') })

const saveTag = (tag: TagRow) => {
  router.patch(route('admin.issue-tracking.tags.update', tag.id), {
    name: tag.name,
    color: tag.color,
  }, { preserveScroll: true })
}

const deleteTag = (tag: TagRow) => {
  if (window.confirm(`Delete tag "${tag.name}"?`)) {
    router.delete(route('admin.issue-tracking.tags.destroy', tag.id), {
      preserveScroll: true,
      onSuccess: () => {
        tagRows.value = tagRows.value.filter((row) => row.id !== tag.id)
      },
    })
  }
}

const editProject = (project: ProjectOption) => {
  const name = window.prompt('Project name', project.name)
  if (!name) return
  router.patch(route('admin.issue-tracking.projects.update', project.id), {
    name,
    description: '',
    status: project.status ?? 'active',
    is_default: project.is_default ?? false,
  }, { preserveScroll: true })
}

const editTracker = (tracker: TrackerWithFields) => {
  const name = window.prompt('Tracker name', tracker.name)
  if (!name) return
  router.patch(route('admin.issue-tracking.trackers.update', tracker.id), {
    name,
    description: tracker.description ?? '',
    color: tracker.color ?? '#2563eb',
    status: tracker.status ?? 'active',
    is_default: tracker.is_default ?? false,
    sort_order: 0,
  }, { preserveScroll: true })
}

const editCategory = (category: CategoryOption) => {
  const name = window.prompt('Category name', category.name)
  if (!name) return
  router.patch(route('admin.issue-tracking.categories.update', category.id), {
    name,
    description: '',
    status: category.status ?? 'active',
  }, { preserveScroll: true })
}

const editCustomField = (field: CustomFieldDefinition) => {
  const name = window.prompt('Field name', field.name)
  if (!name) return
  router.patch(route('admin.issue-tracking.custom-fields.update', field.id), {
    name,
    type: field.type,
    is_required: field.is_required ?? field.required ?? false,
    validation_regex: field.validation_regex ?? '',
    status: field.status ?? 'active',
    sort_order: field.sort_order ?? 0,
    options: field.options.map((option) => option.label).join('\n'),
  }, { preserveScroll: true })
}

const deleteCustomField = (field: CustomFieldDefinition) => {
  if (window.confirm(`Delete field "${field.name}"?`)) {
    router.delete(route('admin.issue-tracking.custom-fields.destroy', field.id), { preserveScroll: true })
  }
}
</script>

<template>
  <AdminLayout title="Issue Tracking">
    <div class="grid gap-6 xl:grid-cols-2">
      <Card>
        <CardHeader><CardTitle>Projects</CardTitle></CardHeader>
        <CardContent class="space-y-4">
          <form class="grid gap-3 md:grid-cols-2" @submit.prevent="submitProject">
            <div>
              <Label>Company</Label>
              <Select v-model="projectForm.company_id" class="mt-1">
                <option v-for="company in companies" :key="company.id" :value="company.id">{{ company.name }}</option>
              </Select>
            </div>
            <div>
              <Label>Name</Label>
              <Input v-model="projectForm.name" class="mt-1" />
              <FieldError :message="projectForm.errors.name" />
            </div>
            <div class="md:col-span-2">
              <Label>Description</Label>
              <Input v-model="projectForm.description" class="mt-1" />
            </div>
            <label class="flex items-center gap-2 text-sm"><Checkbox v-model="projectForm.is_default" /> Default</label>
            <Button type="submit" :disabled="projectForm.processing"><Plus class="h-4 w-4" /> Add project</Button>
          </form>
          <div class="divide-y rounded-md border">
            <div v-for="project in projects" :key="project.id" class="flex items-center justify-between gap-3 p-3">
              <div><p class="font-medium">{{ project.name }}</p><p class="text-xs text-muted-foreground">{{ project.company }}</p></div>
              <Button type="button" size="icon" variant="ghost" @click="editProject(project)"><Pencil class="h-4 w-4" /></Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Trackers</CardTitle></CardHeader>
        <CardContent class="space-y-4">
          <form class="grid gap-3 md:grid-cols-2" @submit.prevent="submitTracker">
            <div>
              <Label>Name</Label>
              <Input v-model="trackerForm.name" class="mt-1" />
              <FieldError :message="trackerForm.errors.name" />
            </div>
            <div>
              <Label>Color</Label>
              <Input v-model="trackerForm.color" class="mt-1" type="color" />
            </div>
            <div class="md:col-span-2">
              <Label>Description</Label>
              <Input v-model="trackerForm.description" class="mt-1" />
            </div>
            <label class="flex items-center gap-2 text-sm"><Checkbox v-model="trackerForm.is_default" /> Default</label>
            <Button type="submit" :disabled="trackerForm.processing"><Plus class="h-4 w-4" /> Add tracker</Button>
          </form>
          <div class="divide-y rounded-md border">
            <div v-for="tracker in trackers" :key="tracker.id" class="p-3">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                  <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: tracker.color }" />
                  <p class="font-medium">{{ tracker.name }}</p>
                  <Badge v-if="tracker.is_default">Default</Badge>
                </div>
                <Button type="button" size="icon" variant="ghost" @click="editTracker(tracker)"><Pencil class="h-4 w-4" /></Button>
              </div>
              <div class="mt-2 flex flex-wrap gap-1">
                <Badge v-for="field in tracker.custom_fields" :key="field.id" tone="neutral">{{ field.name }}</Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Categories</CardTitle></CardHeader>
        <CardContent class="space-y-4">
          <form class="grid gap-3 md:grid-cols-2" @submit.prevent="submitCategory">
            <div>
              <Label>Project</Label>
              <Select v-model="categoryForm.project_id" class="mt-1">
                <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
              </Select>
            </div>
            <div>
              <Label>Name</Label>
              <Input v-model="categoryForm.name" class="mt-1" />
              <FieldError :message="categoryForm.errors.name" />
            </div>
            <Button type="submit" :disabled="categoryForm.processing"><Plus class="h-4 w-4" /> Add category</Button>
          </form>
          <div class="divide-y rounded-md border">
            <div v-for="category in categories" :key="category.id" class="flex items-center justify-between gap-3 p-3">
              <div><p class="font-medium">{{ category.name }}</p><p class="text-xs text-muted-foreground">{{ category.project }}</p></div>
              <Button type="button" size="icon" variant="ghost" @click="editCategory(category)"><Pencil class="h-4 w-4" /></Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Tags</CardTitle></CardHeader>
        <CardContent class="space-y-4">
          <form class="grid gap-3 md:grid-cols-[1fr_96px_auto]" @submit.prevent="submitTag">
            <div>
              <Label>Name</Label>
              <Input v-model="tagForm.name" class="mt-1" />
              <FieldError :message="tagForm.errors.name" />
            </div>
            <div>
              <Label>Color</Label>
              <Input v-model="tagForm.color" class="mt-1" type="color" />
            </div>
            <Button type="submit" class="self-end" :disabled="tagForm.processing"><Plus class="h-4 w-4" /> Add</Button>
          </form>
          <div class="divide-y rounded-md border">
            <div v-for="tag in tagRows" :key="tag.id" class="grid items-center gap-3 p-3 md:grid-cols-[1fr_88px_auto]">
              <Input v-model="tag.name" />
              <Input v-model="tag.color" type="color" />
              <div class="flex justify-end gap-1">
                <Button type="button" size="icon" variant="ghost" @click="saveTag(tag)"><Pencil class="h-4 w-4" /></Button>
                <Button type="button" size="icon" variant="ghost" @click="deleteTag(tag)"><Trash2 class="h-4 w-4" /></Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="xl:col-span-2">
        <CardHeader><CardTitle>Custom fields</CardTitle></CardHeader>
        <CardContent class="grid gap-6 lg:grid-cols-[360px_1fr]">
          <form class="space-y-3" @submit.prevent="submitField">
            <div>
              <Label>Tracker</Label>
              <Select v-model="fieldForm.tracker_id" class="mt-1">
                <option v-for="tracker in trackers" :key="tracker.id" :value="tracker.id">{{ tracker.name }}</option>
              </Select>
            </div>
            <div>
              <Label>Name</Label>
              <Input v-model="fieldForm.name" class="mt-1" />
              <FieldError :message="fieldForm.errors.name" />
            </div>
            <div>
              <Label>Type</Label>
              <Select v-model="fieldForm.type" class="mt-1">
                <option v-for="type in customFieldTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
              </Select>
            </div>
            <div>
              <Label>Regex</Label>
              <Input v-model="fieldForm.validation_regex" class="mt-1" placeholder="/^[A-Z]+$/" />
            </div>
            <div>
              <Label>Options</Label>
              <Textarea v-model="fieldForm.options" class="mt-1" :rows="4" />
            </div>
            <label class="flex items-center gap-2 text-sm"><Checkbox v-model="fieldForm.is_required" /> Required</label>
            <Button type="submit" :disabled="fieldForm.processing"><Plus class="h-4 w-4" /> Add field</Button>
          </form>
          <div class="divide-y rounded-md border">
            <div v-for="tracker in trackers" :key="tracker.id" class="p-4">
              <h3 class="font-medium">{{ tracker.name }}</h3>
              <div class="mt-3 grid gap-2 md:grid-cols-2">
                <div v-for="field in tracker.custom_fields" :key="field.id" class="flex items-center justify-between gap-3 rounded-md border p-3">
                  <div>
                    <p class="font-medium">{{ field.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ field.type }} <span v-if="field.is_required || field.required">· required</span></p>
                  </div>
                  <div class="flex gap-1">
                    <Button type="button" size="icon" variant="ghost" @click="editCustomField(field)"><Pencil class="h-4 w-4" /></Button>
                    <Button type="button" size="icon" variant="ghost" @click="deleteCustomField(field)"><Trash2 class="h-4 w-4" /></Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
