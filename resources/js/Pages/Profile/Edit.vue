<script setup lang="ts">
import { computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
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
import Button from '@/Components/ui/button/Button.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import type { PageProps } from '@/types'

const props = defineProps<{
  mustVerifyEmail: boolean
  status?: string
  notificationPreferences: {
    database_enabled: boolean
    mail_enabled: boolean
    digest_enabled: boolean
    event_settings?: Record<string, unknown>
  }
}>()

const page = usePage<PageProps>()
const layoutComponent = computed(() => page.props.auth.user?.is_provider ? AdminLayout : PortalLayout)
type NotificationForm = {
  database_enabled: boolean
  mail_enabled: boolean
  digest_enabled: boolean
  event_settings: Record<string, any>
}

const notificationForm = useForm<NotificationForm>({
  database_enabled: props.notificationPreferences.database_enabled,
  mail_enabled: props.notificationPreferences.mail_enabled,
  digest_enabled: props.notificationPreferences.digest_enabled,
  event_settings: props.notificationPreferences.event_settings ?? {},
})
const saveNotifications = () => notificationForm.patch(route('profile.notifications.update'), { preserveScroll: true })
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

        <Card>
          <CardHeader>
            <CardTitle>Notifications</CardTitle>
            <CardDescription>Choose the delivery channels used for ticket and automation updates.</CardDescription>
          </CardHeader>
          <CardContent>
            <form class="space-y-3" @submit.prevent="saveNotifications">
              <label class="flex items-center gap-2 text-sm"><Checkbox v-model="notificationForm.database_enabled" /> In-app inbox notifications</label>
              <label class="flex items-center gap-2 text-sm"><Checkbox v-model="notificationForm.mail_enabled" /> Email notifications</label>
              <label class="flex items-center gap-2 text-sm"><Checkbox v-model="notificationForm.digest_enabled" /> Digest mode</label>
              <Button type="submit" :disabled="notificationForm.processing">Save notification preferences</Button>
            </form>
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
