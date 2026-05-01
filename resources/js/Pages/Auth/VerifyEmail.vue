<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Alert from '@/Components/ui/alert/Alert.vue'
import AlertDescription from '@/Components/ui/alert/AlertDescription.vue'

const props = defineProps<{
  status?: string
}>()

const form = useForm({})

const submit = () => {
  form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
  <AuthLayout title="Verify email">
    <Head title="Email Verification" />

    <Alert class="mb-4">
      <AlertDescription>Verify your email address using the link we sent. You can request a new link if needed.</AlertDescription>
    </Alert>

    <Alert v-if="verificationLinkSent" variant="success" class="mb-4">
      <AlertDescription>A new verification link has been sent to your email address.</AlertDescription>
    </Alert>

    <form @submit.prevent="submit">
      <div class="flex items-center justify-between gap-4">
        <Button type="submit" :disabled="form.processing">Resend email</Button>

        <Link :href="route('logout')" method="post" as="button" class="link text-sm">Log out</Link>
      </div>
    </form>
  </AuthLayout>
</template>
