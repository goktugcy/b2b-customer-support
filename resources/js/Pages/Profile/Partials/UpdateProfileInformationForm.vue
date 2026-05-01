<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Alert from '@/Components/ui/alert/Alert.vue'
import AlertDescription from '@/Components/ui/alert/AlertDescription.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import type { PageProps } from '@/types'

defineProps<{
  mustVerifyEmail: boolean
  status?: string
}>()

const user = usePage<PageProps>().props.auth.user!

const form = useForm({
  name: user.name,
  email: user.email,
})
</script>

<template>
  <form class="space-y-5" @submit.prevent="form.patch(route('profile.update'))">
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

    <Alert v-if="mustVerifyEmail && user.email_verified_at === null" variant="warning">
      <AlertDescription>
        Your email address is unverified.
        <Link :href="route('verification.send')" method="post" as="button" class="font-medium underline underline-offset-4">
          Re-send the verification email.
        </Link>
      </AlertDescription>
    </Alert>

    <Alert v-show="status === 'verification-link-sent'" variant="success">
      <AlertDescription>A new verification link has been sent to your email address.</AlertDescription>
    </Alert>

    <div class="flex items-center gap-4">
      <Button type="submit" :disabled="form.processing">Save</Button>
      <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
        <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
      </Transition>
    </div>
  </form>
</template>
