<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import FieldError from '@/Components/shared/FieldError.vue'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <AuthLayout title="Create account">
    <Head title="Register" />

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <Label for="name">Name</Label>
        <Input id="name" v-model="form.name" class="mt-1" autocomplete="name" required autofocus />
        <FieldError :message="form.errors.name" />
      </div>

      <div>
        <Label for="email">Email</Label>
        <Input id="email" v-model="form.email" class="mt-1" type="email" autocomplete="username" required />
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

      <div class="flex items-center justify-between gap-4">
        <Link :href="route('login')" class="link text-sm">Already registered?</Link>
        <Button type="submit" :disabled="form.processing">Register</Button>
      </div>
    </form>
  </AuthLayout>
</template>
