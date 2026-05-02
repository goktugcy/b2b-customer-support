<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { CheckCheck, ExternalLink } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import type { PageProps, Paginated } from '@/types'

type NotificationRow = {
  id: string
  type: string
  data: Record<string, string | number | null>
  read_at?: string | null
  created_at: string
}

defineProps<{ notifications: Paginated<NotificationRow> }>()

const page = usePage<PageProps>()
const Layout = page.props.auth.user?.is_provider ? AdminLayout : PortalLayout

const markRead = (id: string) => router.patch(route('notifications.read', id), {}, { preserveScroll: true })
const markAllRead = () => router.patch(route('notifications.read-all'), {}, { preserveScroll: true })

const titleFor = (item: NotificationRow): string => {
  const subject = item.data.ticket_subject ?? item.data.message ?? item.type

  return item.data.display_id ? `${item.data.display_id} · ${subject}` : String(subject)
}

const messageFor = (item: NotificationRow): string => String(item.data.message ?? 'Notification update')

const timeFor = (value: string): string => new Intl.DateTimeFormat(undefined, {
  month: 'short',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
}).format(new Date(value))
</script>

<template>
  <component :is="Layout" title="Notifications">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold tracking-normal">Notification center</h2>
        <p class="mt-1 text-sm text-muted-foreground">Ticket, mention, assignment, and CSAT updates in one place.</p>
      </div>
      <Button variant="secondary" @click="markAllRead">
        <CheckCheck class="h-4 w-4" />
        Mark all read
      </Button>
    </div>

    <Card class="mt-4 overflow-hidden">
      <CardHeader>
        <CardTitle class="text-sm">Inbox</CardTitle>
      </CardHeader>
      <CardContent class="p-0">
        <div v-if="notifications.data.length" class="divide-y">
          <div v-for="item in notifications.data" :key="item.id" class="flex flex-wrap items-start justify-between gap-3 p-4 transition-colors hover:bg-secondary/50">
            <div class="flex min-w-0 flex-1 gap-3">
              <span
                :class="[
                  'mt-2 h-2 w-2 shrink-0 rounded-full',
                  item.read_at ? 'bg-border' : 'bg-primary',
                ]"
              />
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <Badge :tone="item.read_at ? 'neutral' : 'blue'">{{ item.read_at ? 'read' : 'unread' }}</Badge>
                  <p class="truncate text-sm font-medium">{{ titleFor(item) }}</p>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">{{ messageFor(item) }}</p>
                <p class="mt-2 text-xs text-muted-foreground">{{ timeFor(item.created_at) }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Link v-if="item.data.url" :href="String(item.data.url)">
                <Button size="sm" variant="secondary"><ExternalLink class="h-4 w-4" /> Open</Button>
              </Link>
              <Button v-if="!item.read_at" size="sm" variant="ghost" @click="markRead(item.id)">Mark read</Button>
            </div>
          </div>
        </div>
        <div v-else class="p-4">
          <EmptyState title="No notifications" />
        </div>
      </CardContent>
    </Card>

    <div class="mt-4">
      <Pagination :links="notifications.links" />
    </div>
  </component>
</template>
