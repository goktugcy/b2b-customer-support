<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Alert from '@/Components/ui/alert/Alert.vue'
import AlertDescription from '@/Components/ui/alert/AlertDescription.vue'
import FieldError from '@/Components/shared/FieldError.vue'

defineProps<{
  status?: string
}>()

const form = useForm({
  email: '',
})

const submit = () => {
  form.post(route('password.email'))
}
</script>

<template>
  <AuthLayout title="Reset access">
    <Head title="Forgot Password" />

    <Alert class="mb-4">
      <AlertDescription>Enter your email and we will send a password reset link.</AlertDescription>
    </Alert>

    <Alert v-if="status" variant="success" class="mb-4">
      <AlertDescription>{{ status }}</AlertDescription>
    </Alert>

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <Label for="email">Email</Label>
        <Input id="email" v-model="form.email" class="mt-1" type="email" autocomplete="username" required autofocus />
        <FieldError :message="form.errors.email" />
      </div>

      <div class="flex justify-end">
        <Button type="submit" :disabled="form.processing">Email reset link</Button>
      </div>
    </form>
  </AuthLayout>
</template>
