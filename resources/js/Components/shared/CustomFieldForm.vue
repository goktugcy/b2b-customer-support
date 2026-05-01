<script setup lang="ts">
import { computed } from 'vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import MultiSelectCombobox from '@/Components/shared/MultiSelectCombobox.vue'
import type { CategoryOption, CustomFieldDefinition, CustomFieldValues, MultiSelectOption, ProjectOption } from '@/types'

const model = defineModel<CustomFieldValues>({ default: {} })

const props = withDefaults(defineProps<{
  fields: CustomFieldDefinition[]
  projects?: ProjectOption[]
  categories?: CategoryOption[]
  users?: MultiSelectOption[]
  errors?: Record<string, string | undefined>
}>(), {
  projects: () => [],
  categories: () => [],
  users: () => [],
  errors: () => ({}),
})

const projectOptions = computed(() => props.projects.map((project) => ({ id: project.id, name: project.name })))
const categoryOptions = computed(() => props.categories.map((category) => ({ id: category.id, name: category.name })))
const userOptions = computed(() => props.users.map((user) => ({ id: user.id, name: user.name })))
const fieldValues = computed<Record<string, any>>({
  get: () => model.value,
  set: (value) => {
    model.value = value
  },
})
const fieldError = (id: string) => props.errors[`custom_fields.${id}`]
</script>

<template>
  <div v-if="fields.length" class="space-y-4">
    <div v-for="field in fields" :key="field.id" class="space-y-1.5">
      <Label :for="field.id">{{ field.name }} <span v-if="field.is_required || field.required" class="text-destructive">*</span></Label>

      <Textarea v-if="field.type === 'textarea'" :id="field.id" v-model="fieldValues[field.id]" :rows="4" />
      <Input v-else-if="field.type === 'number'" :id="field.id" v-model="fieldValues[field.id]" type="number" />
      <Input v-else-if="field.type === 'date'" :id="field.id" v-model="fieldValues[field.id]" type="date" />
      <label v-else-if="field.type === 'boolean'" class="flex h-10 items-center gap-2 rounded-md border px-3 text-sm">
        <Checkbox v-model="fieldValues[field.id]" />
        <span>{{ field.name }}</span>
      </label>
      <Select v-else-if="field.type === 'single_select'" :id="field.id" v-model="fieldValues[field.id]">
        <option value="">Select</option>
        <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
      </Select>
      <MultiSelectCombobox
        v-else-if="field.type === 'multi_select'"
        v-model="fieldValues[field.id]"
        :options="field.options.map((option) => ({ id: option.value, name: option.label }))"
        placeholder="Select options"
      />
      <Select v-else-if="field.type === 'user'" :id="field.id" v-model="fieldValues[field.id]">
        <option value="">Select user</option>
        <option v-for="user in userOptions" :key="user.id" :value="user.id">{{ user.name }}</option>
      </Select>
      <Select v-else-if="field.type === 'project'" :id="field.id" v-model="fieldValues[field.id]">
        <option value="">Select project</option>
        <option v-for="project in projectOptions" :key="project.id" :value="project.id">{{ project.name }}</option>
      </Select>
      <Select v-else-if="field.type === 'category'" :id="field.id" v-model="fieldValues[field.id]">
        <option value="">Select category</option>
        <option v-for="category in categoryOptions" :key="category.id" :value="category.id">{{ category.name }}</option>
      </Select>
      <Input v-else :id="field.id" v-model="fieldValues[field.id]" />

      <p v-if="fieldError(field.id)" class="text-sm text-destructive">{{ fieldError(field.id) }}</p>
    </div>
  </div>
</template>
