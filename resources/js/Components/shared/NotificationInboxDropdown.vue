<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Bell, Check, CheckCheck, Inbox, Loader2, X } from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'

type NotificationData = Record<string, string | number | null | undefined>

type NotificationRow = {
  id: string
  type: string
  data: NotificationData
  read_at?: string | null
  created_at?: string | null
}

type InboxResponse = {
  unread_count: number
  notifications: NotificationRow[]
}

const props = defineProps<{
  unreadCount: number
}>()

const open = ref(false)
const filter = ref<'all' | 'unread'>('all')
const loading = ref(false)
const rows = ref<NotificationRow[]>([])
const localUnreadCount = ref(props.unreadCount)

watch(() => props.unreadCount, (value) => {
  localUnreadCount.value = value
})

watch(filter, () => {
  if (open.value) {
    void fetchInbox()
  }
})

const countLabel = computed(() => localUnreadCount.value > 99 ? '99+' : String(localUnreadCount.value))

const toggle = () => {
  open.value = !open.value

  if (open.value) {
    void fetchInbox()
  }
}

const fetchInbox = async () => {
  loading.value = true

  try {
    const response = await window.axios.get<InboxResponse>(route('notifications.inbox'), {
      params: {
        filter: filter.value,
        limit: 24,
      },
    })

    rows.value = response.data.notifications
    localUnreadCount.value = response.data.unread_count
  } finally {
    loading.value = false
  }
}

const markRead = async (item: NotificationRow) => {
  if (item.read_at) return

  const response = await window.axios.patch<{ unread_count: number }>(route('notifications.read', item.id))
  item.read_at = new Date().toISOString()
  localUnreadCount.value = response.data.unread_count
}

const markAllRead = async () => {
  const response = await window.axios.patch<{ unread_count: number }>(route('notifications.read-all'))

  rows.value = rows.value.map((item) => ({ ...item, read_at: item.read_at ?? new Date().toISOString() }))
  localUnreadCount.value = response.data.unread_count
}

const openNotification = async (item: NotificationRow) => {
  await markRead(item)

  const target = item.data.url
  if (typeof target === 'string' && target.length > 0) {
    open.value = false
    router.visit(target)
  }
}

const titleFor = (item: NotificationRow): string => {
  const subject = item.data.ticket_subject ?? item.data.message ?? item.type
  const displayId = item.data.display_id

  return displayId ? `${displayId} · ${subject}` : String(subject)
}

const messageFor = (item: NotificationRow): string => String(item.data.message ?? 'Notification update')

const timeFor = (value?: string | null): string => {
  if (!value) return ''

  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}
</script>

<template>
  <div class="relative">
    <Button
      variant="ghost"
      size="icon"
      class="relative text-muted-foreground hover:text-foreground"
      aria-label="Notifications"
      @click="toggle"
    >
      <Bell class="h-5 w-5" />
      <span
        v-if="localUnreadCount > 0"
        class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold leading-none text-primary-foreground"
      >
        {{ countLabel }}
      </span>
    </Button>

    <div v-if="open" class="fixed inset-0 z-40 bg-foreground/30 backdrop-blur-[1px] sm:hidden" @click="open = false" />

    <div
      v-if="open"
      class="fixed inset-x-3 top-16 z-50 overflow-hidden rounded-lg border bg-card shadow-2xl sm:absolute sm:inset-auto sm:right-0 sm:top-full sm:mt-2 sm:w-[430px]"
    >
      <div class="flex items-start justify-between gap-3 border-b p-4">
        <div>
          <p class="text-sm font-semibold">Inbox</p>
          <p class="mt-0.5 text-xs text-muted-foreground">{{ localUnreadCount }} unread notification(s)</p>
        </div>
        <div class="flex items-center gap-1">
          <Button variant="ghost" size="sm" :disabled="localUnreadCount === 0" @click="markAllRead">
            <CheckCheck class="h-4 w-4" />
            Read all
          </Button>
          <Button variant="ghost" size="icon" class="h-8 w-8" aria-label="Close notifications" @click="open = false">
            <X class="h-4 w-4" />
          </Button>
        </div>
      </div>

      <div class="flex items-center gap-2 border-b px-4 py-3">
        <button
          type="button"
          :class="[
            'h-8 rounded-md px-3 text-xs font-medium transition-colors',
            filter === 'all' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-secondary hover:text-foreground',
          ]"
          @click="filter = 'all'"
        >
          All
        </button>
        <button
          type="button"
          :class="[
            'h-8 rounded-md px-3 text-xs font-medium transition-colors',
            filter === 'unread' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-secondary hover:text-foreground',
          ]"
          @click="filter = 'unread'"
        >
          Unread
        </button>
      </div>

      <div class="max-h-[calc(100vh-220px)] min-h-40 overflow-y-auto sm:max-h-[430px]">
        <div v-if="loading" class="flex items-center justify-center gap-2 p-8 text-sm text-muted-foreground">
          <Loader2 class="h-4 w-4 animate-spin" />
          Loading inbox
        </div>

        <div v-else-if="rows.length" class="divide-y">
          <div
            v-for="item in rows"
            :key="item.id"
            class="group flex w-full items-start gap-3 p-4 text-left transition-colors hover:bg-secondary/70"
          >
            <button type="button" class="flex min-w-0 flex-1 items-start gap-3 text-left" @click="openNotification(item)">
              <span
                :class="[
                  'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                  item.read_at ? 'bg-border' : 'bg-primary',
                ]"
              />
              <span class="min-w-0 flex-1">
                <span class="flex flex-wrap items-center gap-2">
                  <span class="truncate text-sm font-medium text-foreground">{{ titleFor(item) }}</span>
                  <Badge :tone="item.read_at ? 'neutral' : 'blue'">{{ item.read_at ? 'read' : 'unread' }}</Badge>
                </span>
                <span class="mt-1 line-clamp-2 block text-sm text-muted-foreground">{{ messageFor(item) }}</span>
                <span class="mt-2 block text-xs text-muted-foreground">{{ timeFor(item.created_at) }}</span>
              </span>
            </button>
            <button
              v-if="!item.read_at"
              type="button"
              class="rounded-md p-1 text-muted-foreground opacity-100 transition-colors hover:bg-background hover:text-foreground sm:opacity-0 sm:group-hover:opacity-100"
              aria-label="Mark notification read"
              @click.stop="markRead(item)"
            >
              <Check class="h-4 w-4" />
            </button>
          </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center gap-2 p-8 text-center">
          <span class="flex h-10 w-10 items-center justify-center rounded-md bg-secondary text-muted-foreground">
            <Inbox class="h-5 w-5" />
          </span>
          <p class="text-sm font-medium">No notifications</p>
          <p class="text-xs text-muted-foreground">Ticket updates and mentions will appear here.</p>
        </div>
      </div>

      <div class="flex items-center justify-between gap-3 border-t bg-muted/30 px-4 py-3">
        <p class="text-xs text-muted-foreground">Showing the latest inbox activity.</p>
        <Link :href="route('notifications.index')" class="text-sm font-medium text-primary hover:underline" @click="open = false">
          View all
        </Link>
      </div>
    </div>
  </div>
</template>
