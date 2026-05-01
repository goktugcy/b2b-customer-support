<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Label from '@/Components/ui/label/Label.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import type { SelectOption } from '@/types'

defineProps<{ priorities: SelectOption[] }>()

const form = useForm({
  subject: '',
  description: '',
  priority: 'normal',
})

const submit = () => form.post(route('portal.tickets.store'))
</script>

<template>
  <PortalLayout title="Create ticket">
    <form class="max-w-2xl rounded-md border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
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
          <select v-model="form.priority" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
          </select>
        </div>
        <Button type="submit" :disabled="form.processing">Submit ticket</Button>
      </div>
    </form>
  </PortalLayout>
</template>
