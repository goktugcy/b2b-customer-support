<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import FieldError from '@/Components/shared/FieldError.vue'

defineProps<{
  canResetPassword: boolean
  status?: string
}>()

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <AuthLayout title="Sign in">
    <Head title="Sign in" />

    <p v-if="status" class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
      {{ status }}
    </p>

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <Label for="email">Email</Label>
        <Input id="email" v-model="form.email" class="mt-1" type="email" autocomplete="username" required autofocus />
        <FieldError :message="form.errors.email" />
      </div>

      <div>
        <Label for="password">Password</Label>
        <Input id="password" v-model="form.password" class="mt-1" type="password" autocomplete="current-password" required />
        <FieldError :message="form.errors.password" />
      </div>

      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-teal-700 focus:ring-teal-700" />
        Remember me
      </label>

      <div class="flex items-center justify-between">
        <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-medium text-teal-800 hover:text-teal-900">
          Forgot password?
        </Link>
        <span v-else />
        <Button type="submit" :disabled="form.processing">Sign in</Button>
      </div>
    </form>
  </AuthLayout>
</template>
