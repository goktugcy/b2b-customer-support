<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import FieldError from '@/Components/shared/FieldError.vue'

const props = defineProps<{
  email: string
  token: string
}>()

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <AuthLayout title="Set new password">
    <Head title="Reset Password" />

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <Label for="email">Email</Label>
        <Input id="email" v-model="form.email" class="mt-1" type="email" autocomplete="username" required autofocus />
        <FieldError :message="form.errors.email" />
      </div>

      <div>
        <Label for="password">Password</Label>
        <Input id="password" v-model="form.password" class="mt-1" type="password" autocomplete="new-password" required />
        <FieldError :message="form.errors.password" />
      </div>

      <div>
        <Label for="password_confirmation">Confirm password</Label>
        <Input id="password_confirmation" v-model="form.password_confirmation" class="mt-1" type="password" autocomplete="new-password" required />
        <FieldError :message="form.errors.password_confirmation" />
      </div>

      <div class="flex justify-end">
        <Button type="submit" :disabled="form.processing">Reset password</Button>
      </div>
    </form>
  </AuthLayout>
</template>
