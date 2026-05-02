<script setup lang="ts">
import { computed, ref } from 'vue'
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
import Dialog from '@/Components/ui/dialog/Dialog.vue'
import DialogContent from '@/Components/ui/dialog/DialogContent.vue'
import DialogDescription from '@/Components/ui/dialog/DialogDescription.vue'
import DialogFooter from '@/Components/ui/dialog/DialogFooter.vue'
import DialogHeader from '@/Components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/Components/ui/dialog/DialogTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import type { CategoryOption, CustomFieldDefinition, ProjectOption, TagOption, TrackerOption } from '@/types'

type CompanyOption = { id: string; name: string }
type ProjectRow = ProjectOption & { description?: string | null }
type CategoryRow = CategoryOption & { description?: string | null }
type TrackerWithFields = TrackerOption & { description?: string | null; sort_order?: number; custom_fields: CustomFieldDefinition[] }
type TagRow = { id: string; name: string; color: string }
type FieldType = { value: string; label: string }

const props = defineProps<{
  companies: CompanyOption[]
  projects: ProjectRow[]
  trackers: TrackerWithFields[]
  categories: CategoryRow[]
  tags: TagOption[]
  customFieldTypes: FieldType[]
}>()

const projectDialogOpen = ref(false)
const trackerDialogOpen = ref(false)
const categoryDialogOpen = ref(false)
const tagDialogOpen = ref(false)
const fieldDialogOpen = ref(false)
const confirmDialogOpen = ref(false)
const editingProject = ref<ProjectRow | null>(null)
const editingTracker = ref<TrackerWithFields | null>(null)
const editingCategory = ref<CategoryRow | null>(null)
const editingTag = ref<TagRow | null>(null)
const editingField = ref<CustomFieldDefinition | null>(null)
const confirmTitle = ref('')
const confirmDescription = ref('')
const confirmAction = ref<(() => void) | null>(null)

const projectForm = useForm({ company_id: props.companies[0]?.id ?? '', name: '', description: '', status: 'active', is_default: false })
const trackerForm = useForm({ name: '', description: '', color: '#2563eb', status: 'active', is_default: false, sort_order: 0 })
const categoryForm = useForm({ project_id: props.projects[0]?.id ?? '', name: '', description: '', status: 'active' })
const tagForm = useForm({ name: '', color: '#64748b' })
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

const tagRows = computed<TagRow[]>(() => props.tags.map((tag) => ({ id: tag.id ?? '', name: tag.name, color: tag.color ?? '#64748b' })))
const projectDialogTitle = computed(() => editingProject.value ? 'Edit project' : 'Create project')
const trackerDialogTitle = computed(() => editingTracker.value ? 'Edit tracker' : 'Create tracker')
const categoryDialogTitle = computed(() => editingCategory.value ? 'Edit category' : 'Create category')
const tagDialogTitle = computed(() => editingTag.value ? 'Edit tag' : 'Create tag')
const fieldDialogTitle = computed(() => editingField.value ? 'Edit custom field' : 'Create custom field')
const customFieldRows = computed(() => props.trackers.flatMap((tracker) => tracker.custom_fields.map((field) => ({ ...field, tracker_name: tracker.name }))))

const resetProjectForm = () => {
  editingProject.value = null
  projectForm.defaults({ company_id: props.companies[0]?.id ?? '', name: '', description: '', status: 'active', is_default: false })
  projectForm.reset()
  projectForm.clearErrors()
}

const openProjectDialog = (project?: ProjectRow) => {
  resetProjectForm()
  if (project) {
    editingProject.value = project
    projectForm.company_id = project.company_id ?? props.companies[0]?.id ?? ''
    projectForm.name = project.name
    projectForm.description = project.description ?? ''
    projectForm.status = project.status ?? 'active'
    projectForm.is_default = project.is_default ?? false
  }
  projectDialogOpen.value = true
}

const submitProject = () => {
  const options = { preserveScroll: true, onSuccess: () => { projectDialogOpen.value = false; resetProjectForm() } }
  if (editingProject.value) {
    projectForm.patch(route('admin.issue-tracking.projects.update', editingProject.value.id), options)
    return
  }
  projectForm.post(route('admin.issue-tracking.projects.store'), options)
}

const resetTrackerForm = () => {
  editingTracker.value = null
  trackerForm.defaults({ name: '', description: '', color: '#2563eb', status: 'active', is_default: false, sort_order: 0 })
  trackerForm.reset()
  trackerForm.clearErrors()
}

const openTrackerDialog = (tracker?: TrackerWithFields) => {
  resetTrackerForm()
  if (tracker) {
    editingTracker.value = tracker
    trackerForm.name = tracker.name
    trackerForm.description = tracker.description ?? ''
    trackerForm.color = tracker.color ?? '#2563eb'
    trackerForm.status = tracker.status ?? 'active'
    trackerForm.is_default = tracker.is_default ?? false
    trackerForm.sort_order = tracker.sort_order ?? 0
  }
  trackerDialogOpen.value = true
}

const submitTracker = () => {
  const options = { preserveScroll: true, onSuccess: () => { trackerDialogOpen.value = false; resetTrackerForm() } }
  if (editingTracker.value) {
    trackerForm.patch(route('admin.issue-tracking.trackers.update', editingTracker.value.id), options)
    return
  }
  trackerForm.post(route('admin.issue-tracking.trackers.store'), options)
}

const resetCategoryForm = () => {
  editingCategory.value = null
  categoryForm.defaults({ project_id: props.projects[0]?.id ?? '', name: '', description: '', status: 'active' })
  categoryForm.reset()
  categoryForm.clearErrors()
}

const openCategoryDialog = (category?: CategoryRow) => {
  resetCategoryForm()
  if (category) {
    editingCategory.value = category
    categoryForm.project_id = category.project_id ?? props.projects[0]?.id ?? ''
    categoryForm.name = category.name
    categoryForm.description = category.description ?? ''
    categoryForm.status = category.status ?? 'active'
  }
  categoryDialogOpen.value = true
}

const submitCategory = () => {
  const options = { preserveScroll: true, onSuccess: () => { categoryDialogOpen.value = false; resetCategoryForm() } }
  if (editingCategory.value) {
    categoryForm.patch(route('admin.issue-tracking.categories.update', editingCategory.value.id), options)
    return
  }
  categoryForm.post(route('admin.issue-tracking.categories.store'), options)
}

const resetTagForm = () => {
  editingTag.value = null
  tagForm.defaults({ name: '', color: '#64748b' })
  tagForm.reset()
  tagForm.clearErrors()
}

const openTagDialog = (tag?: TagRow) => {
  resetTagForm()
  if (tag) {
    editingTag.value = tag
    tagForm.name = tag.name
    tagForm.color = tag.color
  }
  tagDialogOpen.value = true
}

const submitTag = () => {
  const options = { preserveScroll: true, onSuccess: () => { tagDialogOpen.value = false; resetTagForm() } }
  if (editingTag.value) {
    tagForm.patch(route('admin.issue-tracking.tags.update', editingTag.value.id), options)
    return
  }
  tagForm.post(route('admin.issue-tracking.tags.store'), options)
}

const resetFieldForm = () => {
  editingField.value = null
  fieldForm.defaults({
    tracker_id: props.trackers[0]?.id ?? '',
    name: '',
    type: 'text',
    is_required: false,
    validation_regex: '',
    status: 'active',
    sort_order: 0,
    options: '',
  })
  fieldForm.reset()
  fieldForm.clearErrors()
}

const openFieldDialog = (field?: CustomFieldDefinition) => {
  resetFieldForm()
  if (field) {
    editingField.value = field
    fieldForm.tracker_id = field.tracker_id ?? props.trackers[0]?.id ?? ''
    fieldForm.name = field.name
    fieldForm.type = field.type
    fieldForm.is_required = field.is_required ?? field.required ?? false
    fieldForm.validation_regex = field.validation_regex ?? ''
    fieldForm.status = field.status ?? 'active'
    fieldForm.sort_order = field.sort_order ?? 0
    fieldForm.options = field.options.map((option) => option.label).join('\n')
  }
  fieldDialogOpen.value = true
}

const submitField = () => {
  const options = { preserveScroll: true, onSuccess: () => { fieldDialogOpen.value = false; resetFieldForm() } }
  if (editingField.value) {
    fieldForm.patch(route('admin.issue-tracking.custom-fields.update', editingField.value.id), options)
    return
  }
  fieldForm.post(route('admin.issue-tracking.custom-fields.store'), options)
}

const openConfirm = (title: string, description: string, action: () => void) => {
  confirmTitle.value = title
  confirmDescription.value = description
  confirmAction.value = action
  confirmDialogOpen.value = true
}

const runConfirm = () => {
  confirmAction.value?.()
  confirmDialogOpen.value = false
  confirmAction.value = null
}

const deleteTag = (tag: TagRow) => openConfirm(
  'Delete tag',
  `Delete "${tag.name}"? Existing ticket tag links will be removed.`,
  () => router.delete(route('admin.issue-tracking.tags.destroy', tag.id), { preserveScroll: true }),
)

const deleteCustomField = (field: CustomFieldDefinition) => openConfirm(
  'Delete custom field',
  `Delete "${field.name}"? Existing custom field values will no longer be shown.`,
  () => router.delete(route('admin.issue-tracking.custom-fields.destroy', field.id), { preserveScroll: true }),
)
</script>

<template>
  <AdminLayout title="Issue Tracking">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold tracking-normal">Issue tracking</h2>
        <p class="mt-1 text-sm text-muted-foreground">Manage taxonomy, trackers, tags, and custom fields for support tickets.</p>
      </div>
    </div>

    <div class="mt-4 grid gap-6 xl:grid-cols-2">
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between gap-3">
            <CardTitle>Projects</CardTitle>
            <Button size="sm" @click="openProjectDialog()"><Plus class="h-4 w-4" /> Project</Button>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3">
            <div v-for="project in projects" :key="project.id" class="rounded-md border bg-background/60 p-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate font-medium">{{ project.name }}</p>
                  <p class="mt-1 text-xs text-muted-foreground">{{ project.company || 'No company' }}</p>
                </div>
                <Button type="button" size="icon" variant="ghost" @click="openProjectDialog(project)"><Pencil class="h-4 w-4" /></Button>
              </div>
              <div class="mt-3 flex flex-wrap gap-1.5">
                <Badge :tone="project.status === 'active' ? 'green' : 'neutral'">{{ project.status || 'active' }}</Badge>
                <Badge v-if="project.is_default" tone="blue">Default</Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between gap-3">
            <CardTitle>Trackers</CardTitle>
            <Button size="sm" @click="openTrackerDialog()"><Plus class="h-4 w-4" /> Tracker</Button>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3">
            <div v-for="tracker in trackers" :key="tracker.id" class="rounded-md border bg-background/60 p-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: tracker.color }" />
                    <p class="truncate font-medium">{{ tracker.name }}</p>
                  </div>
                  <p v-if="tracker.description" class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ tracker.description }}</p>
                </div>
                <Button type="button" size="icon" variant="ghost" @click="openTrackerDialog(tracker)"><Pencil class="h-4 w-4" /></Button>
              </div>
              <div class="mt-3 flex flex-wrap gap-1.5">
                <Badge :tone="tracker.status === 'active' ? 'green' : 'neutral'">{{ tracker.status || 'active' }}</Badge>
                <Badge v-if="tracker.is_default" tone="blue">Default</Badge>
                <Badge tone="neutral">{{ tracker.custom_fields.length }} fields</Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between gap-3">
            <CardTitle>Categories</CardTitle>
            <Button size="sm" @click="openCategoryDialog()"><Plus class="h-4 w-4" /> Category</Button>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3">
            <div v-for="category in categories" :key="category.id" class="rounded-md border bg-background/60 p-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate font-medium">{{ category.name }}</p>
                  <p class="mt-1 text-xs text-muted-foreground">{{ category.project || 'No project' }}</p>
                </div>
                <Button type="button" size="icon" variant="ghost" @click="openCategoryDialog(category)"><Pencil class="h-4 w-4" /></Button>
              </div>
              <div class="mt-3 flex flex-wrap gap-1.5">
                <Badge :tone="category.status === 'active' ? 'green' : 'neutral'">{{ category.status || 'active' }}</Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between gap-3">
            <CardTitle>Tags</CardTitle>
            <Button size="sm" @click="openTagDialog()"><Plus class="h-4 w-4" /> Tag</Button>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3">
            <div v-for="tag in tagRows" :key="tag.id" class="flex items-center justify-between gap-3 rounded-md border bg-background/60 p-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: tag.color }" />
                  <p class="truncate font-medium">{{ tag.name }}</p>
                </div>
              </div>
              <div class="flex justify-end gap-1">
                <Button type="button" size="icon" variant="ghost" @click="openTagDialog(tag)"><Pencil class="h-4 w-4" /></Button>
                <Button type="button" size="icon" variant="ghost" @click="deleteTag(tag)"><Trash2 class="h-4 w-4" /></Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="xl:col-span-2">
        <CardHeader>
          <div class="flex items-center justify-between gap-3">
            <CardTitle>Custom fields</CardTitle>
            <Button size="sm" @click="openFieldDialog()"><Plus class="h-4 w-4" /> Field</Button>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 md:grid-cols-2">
            <div v-for="field in customFieldRows" :key="field.id" class="flex items-start justify-between gap-3 rounded-md border bg-background/60 p-3">
              <div class="min-w-0">
                <p class="truncate font-medium">{{ field.name }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ field.tracker_name }} · {{ field.type }}<span v-if="field.is_required || field.required"> · required</span></p>
              </div>
              <div class="flex shrink-0 gap-1">
                <Button type="button" size="icon" variant="ghost" @click="openFieldDialog(field)"><Pencil class="h-4 w-4" /></Button>
                <Button type="button" size="icon" variant="ghost" @click="deleteCustomField(field)"><Trash2 class="h-4 w-4" /></Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <Dialog v-model:open="projectDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ projectDialogTitle }}</DialogTitle>
          <DialogDescription>Projects group ticket work by customer workspace.</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitProject">
          <div>
            <Label>Company</Label>
            <Select v-model="projectForm.company_id" class="mt-1" :disabled="Boolean(editingProject)">
              <option v-for="company in companies" :key="company.id" :value="company.id">{{ company.name }}</option>
            </Select>
            <FieldError :message="projectForm.errors.company_id" />
          </div>
          <div>
            <Label>Name</Label>
            <Input v-model="projectForm.name" class="mt-1" />
            <FieldError :message="projectForm.errors.name" />
          </div>
          <div>
            <Label>Description</Label>
            <Textarea v-model="projectForm.description" class="mt-1" :rows="3" />
            <FieldError :message="projectForm.errors.description" />
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <Label>Status</Label>
              <Select v-model="projectForm.status" class="mt-1">
                <option value="active">active</option>
                <option value="disabled">disabled</option>
              </Select>
              <FieldError :message="projectForm.errors.status" />
            </div>
            <label class="mt-7 flex items-center gap-2 text-sm"><Checkbox v-model="projectForm.is_default" /> Default</label>
          </div>
          <DialogFooter>
            <Button type="button" variant="ghost" @click="projectDialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="projectForm.processing">{{ editingProject ? 'Update project' : 'Create project' }}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="trackerDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ trackerDialogTitle }}</DialogTitle>
          <DialogDescription>Trackers define ticket workflows and custom fields.</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitTracker">
          <div>
            <Label>Name</Label>
            <Input v-model="trackerForm.name" class="mt-1" />
            <FieldError :message="trackerForm.errors.name" />
          </div>
          <div>
            <Label>Description</Label>
            <Textarea v-model="trackerForm.description" class="mt-1" :rows="3" />
            <FieldError :message="trackerForm.errors.description" />
          </div>
          <div class="grid gap-3 sm:grid-cols-3">
            <div>
              <Label>Color</Label>
              <Input v-model="trackerForm.color" class="mt-1" type="color" />
              <FieldError :message="trackerForm.errors.color" />
            </div>
            <div>
              <Label>Status</Label>
              <Select v-model="trackerForm.status" class="mt-1">
                <option value="active">active</option>
                <option value="disabled">disabled</option>
              </Select>
              <FieldError :message="trackerForm.errors.status" />
            </div>
            <div>
              <Label>Sort order</Label>
              <Input v-model="trackerForm.sort_order" class="mt-1" type="number" min="0" />
              <FieldError :message="trackerForm.errors.sort_order" />
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm"><Checkbox v-model="trackerForm.is_default" /> Default</label>
          <DialogFooter>
            <Button type="button" variant="ghost" @click="trackerDialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="trackerForm.processing">{{ editingTracker ? 'Update tracker' : 'Create tracker' }}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="categoryDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ categoryDialogTitle }}</DialogTitle>
          <DialogDescription>Categories refine project-level ticket classification.</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitCategory">
          <div>
            <Label>Project</Label>
            <Select v-model="categoryForm.project_id" class="mt-1" :disabled="Boolean(editingCategory)">
              <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
            </Select>
            <FieldError :message="categoryForm.errors.project_id" />
          </div>
          <div>
            <Label>Name</Label>
            <Input v-model="categoryForm.name" class="mt-1" />
            <FieldError :message="categoryForm.errors.name" />
          </div>
          <div>
            <Label>Description</Label>
            <Textarea v-model="categoryForm.description" class="mt-1" :rows="3" />
            <FieldError :message="categoryForm.errors.description" />
          </div>
          <div>
            <Label>Status</Label>
            <Select v-model="categoryForm.status" class="mt-1">
              <option value="active">active</option>
              <option value="disabled">disabled</option>
            </Select>
            <FieldError :message="categoryForm.errors.status" />
          </div>
          <DialogFooter>
            <Button type="button" variant="ghost" @click="categoryDialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="categoryForm.processing">{{ editingCategory ? 'Update category' : 'Create category' }}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="tagDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ tagDialogTitle }}</DialogTitle>
          <DialogDescription>Tags help agents group and filter tickets.</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitTag">
          <div>
            <Label>Name</Label>
            <Input v-model="tagForm.name" class="mt-1" />
            <FieldError :message="tagForm.errors.name" />
          </div>
          <div>
            <Label>Color</Label>
            <Input v-model="tagForm.color" class="mt-1" type="color" />
            <FieldError :message="tagForm.errors.color" />
          </div>
          <DialogFooter>
            <Button type="button" variant="ghost" @click="tagDialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="tagForm.processing">{{ editingTag ? 'Update tag' : 'Create tag' }}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="fieldDialogOpen">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle>{{ fieldDialogTitle }}</DialogTitle>
          <DialogDescription>Custom fields collect structured data for a tracker.</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitField">
          <div>
            <Label>Tracker</Label>
            <Select v-model="fieldForm.tracker_id" class="mt-1" :disabled="Boolean(editingField)">
              <option v-for="tracker in trackers" :key="tracker.id" :value="tracker.id">{{ tracker.name }}</option>
            </Select>
            <FieldError :message="fieldForm.errors.tracker_id" />
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
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
              <FieldError :message="fieldForm.errors.type" />
            </div>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <Label>Status</Label>
              <Select v-model="fieldForm.status" class="mt-1">
                <option value="active">active</option>
                <option value="disabled">disabled</option>
              </Select>
              <FieldError :message="fieldForm.errors.status" />
            </div>
            <div>
              <Label>Sort order</Label>
              <Input v-model="fieldForm.sort_order" class="mt-1" type="number" min="0" />
              <FieldError :message="fieldForm.errors.sort_order" />
            </div>
          </div>
          <div>
            <Label>Regex</Label>
            <Input v-model="fieldForm.validation_regex" class="mt-1" placeholder="/^[A-Z]+$/" />
            <FieldError :message="fieldForm.errors.validation_regex" />
          </div>
          <div>
            <Label>Options</Label>
            <Textarea v-model="fieldForm.options" class="mt-1" :rows="4" placeholder="One option per line" />
            <FieldError :message="fieldForm.errors.options" />
          </div>
          <label class="flex items-center gap-2 text-sm"><Checkbox v-model="fieldForm.is_required" /> Required</label>
          <DialogFooter>
            <Button type="button" variant="ghost" @click="fieldDialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="fieldForm.processing">{{ editingField ? 'Update field' : 'Create field' }}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="confirmDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ confirmTitle }}</DialogTitle>
          <DialogDescription>{{ confirmDescription }}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button type="button" variant="ghost" @click="confirmDialogOpen = false">Cancel</Button>
          <Button type="button" variant="destructive" @click="runConfirm">Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
