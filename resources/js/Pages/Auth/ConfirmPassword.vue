<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Alert from '@/Components/ui/alert/Alert.vue'
import AlertDescription from '@/Components/ui/alert/AlertDescription.vue'
import FieldError from '@/Components/shared/FieldError.vue'

const form = useForm({
  password: '',
})

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => form.reset(),
  })
}
</script>

<template>
  <AuthLayout title="Confirm password">
    <Head title="Confirm Password" />

    <Alert class="mb-4">
      <AlertDescription>This is a secure area. Confirm your password before continuing.</AlertDescription>
    </Alert>

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <Label for="password">Password</Label>
        <Input id="password" v-model="form.password" class="mt-1" type="password" autocomplete="current-password" required autofocus />
        <FieldError :message="form.errors.password" />
      </div>

      <div class="flex justify-end">
        <Button type="submit" :disabled="form.processing">Confirm</Button>
      </div>
    </form>
  </AuthLayout>
</template>
