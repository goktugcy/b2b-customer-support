<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import FieldError from '@/Components/shared/FieldError.vue'

const props = defineProps<{
  token: string
  invitation: {
    email: string
    name: string
    company: string
    role: string
    expires_at: string
  }
}>()

const form = useForm({
  name: props.invitation.name,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('invitations.accept.store', props.token), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <AuthLayout title="Accept invitation">
    <Head title="Accept invitation" />

    <div class="mb-5 rounded-md border border-slate-200 bg-slate-50 p-3 text-sm">
      <p class="font-medium text-slate-900">{{ invitation.company }}</p>
      <p class="mt-1 text-slate-600">{{ invitation.email }}</p>
      <p class="mt-1 text-slate-600">{{ invitation.role }}</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <Label for="name">Name</Label>
        <Input id="name" v-model="form.name" class="mt-1" required />
        <FieldError :message="form.errors.name" />
      </div>

      <div>
        <Label for="password">Password</Label>
        <Input id="password" v-model="form.password" class="mt-1" type="password" required />
        <FieldError :message="form.errors.password" />
      </div>

      <div>
        <Label for="password_confirmation">Confirm password</Label>
        <Input id="password_confirmation" v-model="form.password_confirmation" class="mt-1" type="password" required />
      </div>

      <Button type="submit" class="w-full" :disabled="form.processing">Create account</Button>
    </form>
  </AuthLayout>
</template>
