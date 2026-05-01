<script setup lang="ts">
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import type { PageProps } from '@/types'

defineProps<{
  mustVerifyEmail: boolean
  status?: string
}>()

const page = usePage<PageProps>()
const layoutComponent = computed(() => page.props.auth.user?.is_provider ? AdminLayout : PortalLayout)
</script>

<template>
  <Head title="Profile" />

  <component :is="layoutComponent" title="Profile">
    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Profile information</CardTitle>
            <CardDescription>Update your account name and email address.</CardDescription>
          </CardHeader>
          <CardContent>
            <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Security</CardTitle>
            <CardDescription>Keep your account protected with a current password.</CardDescription>
          </CardHeader>
          <CardContent>
            <UpdatePasswordForm />
          </CardContent>
        </Card>
      </div>

      <Card class="border-destructive/20">
        <CardHeader>
          <CardTitle>Danger zone</CardTitle>
          <CardDescription>Delete your user account and remove access to this workspace.</CardDescription>
        </CardHeader>
        <CardContent>
          <DeleteUserForm />
        </CardContent>
      </Card>
    </div>
  </component>
</template>
