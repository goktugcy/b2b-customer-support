<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Alert from '@/Components/ui/alert/Alert.vue'
import AlertDescription from '@/Components/ui/alert/AlertDescription.vue'
import AlertTitle from '@/Components/ui/alert/AlertTitle.vue'
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

    <Alert class="mb-5">
      <AlertTitle>{{ invitation.company }}</AlertTitle>
      <AlertDescription>{{ invitation.email }} · {{ invitation.role }}</AlertDescription>
    </Alert>

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
