<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  ChevronRight,
  Loader2,
  LogOut,
  Menu,
  Search,
  ShieldCheck,
  X,
} from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import FlashMessages from '@/Components/shared/FlashMessages.vue'
import NotificationInboxDropdown from '@/Components/shared/NotificationInboxDropdown.vue'
import ThemeToggle from '@/Components/shared/ThemeToggle.vue'
import type { PageProps } from '@/types'

type NavItem = {
  label: string
  routeName: string
  icon: unknown
  permission?: string
  providerOnly?: boolean
}

const props = defineProps<{
  title: string
  section: 'admin' | 'portal'
  navItems: NavItem[]
}>()

const page = usePage<PageProps>()
const open = ref(false)
const searchOpen = ref(false)
const searchQuery = ref('')
const searchLoading = ref(false)
const searchResults = ref<{ type: string; title: string; subtitle?: string; url: string }[]>([])
const liveUnreadCount = ref(page.props.notifications?.unread_count ?? 0)
let searchTimer: ReturnType<typeof setTimeout> | null = null

const user = computed(() => page.props.auth.user)
const sectionLabel = computed(() => props.section === 'admin' ? 'Admin Console' : 'Client Portal')
const sectionDescription = computed(() => props.section === 'admin' ? 'Provider operations' : 'Customer workspace')
const homeRoute = computed(() => props.section === 'admin' ? 'admin.home' : 'portal.home')
const canViewNotifications = computed(() => user.value?.permissions.includes('notifications.view') ?? false)
const workspaceName = computed(() => user.value?.company?.branding?.display_name || user.value?.company?.name || 'Workspace')
const brandStyle = computed(() => user.value?.company?.branding?.brand_color ? { backgroundColor: user.value.company.branding.brand_color } : undefined)
const initials = computed(() => (user.value?.name ?? 'CS')
  .split(' ')
  .map((part) => part[0])
  .join('')
  .slice(0, 2)
  .toUpperCase())

const visibleItems = computed(() => props.navItems.filter((item) => {
  if (item.providerOnly && !user.value?.is_provider) return false
  if (!item.permission) return true
  return user.value?.permissions.includes(item.permission)
}))

const isActive = (routeName: string) => route().current(routeName) || route().current(routeName.replace('.index', '.*'))

watch(() => page.props.notifications?.unread_count, (value) => {
  liveUnreadCount.value = value ?? 0
})

watch(searchQuery, (value) => {
  if (searchTimer) clearTimeout(searchTimer)

  if (value.trim().length < 2) {
    searchResults.value = []
    return
  }

  searchTimer = setTimeout(async () => {
    searchLoading.value = true
    try {
      const response = await window.axios.get<{ results: typeof searchResults.value }>(route(`${props.section}.search`), {
        params: { q: value },
      })
      searchResults.value = response.data.results
    } finally {
      searchLoading.value = false
    }
  }, 220)
})

const closeSearch = () => {
  searchOpen.value = false
  searchQuery.value = ''
  searchResults.value = []
}

onMounted(() => {
  if (!window.Echo || !user.value) return

  window.Echo.private(`users.${user.value.id}`)
    .listen('.notification.updated', (event: { unread_count: number }) => {
      liveUnreadCount.value = event.unread_count
    })
})

onBeforeUnmount(() => {
  if (window.Echo && user.value) {
    window.Echo.leave(`users.${user.value.id}`)
  }
})
</script>

<template>
  <Head :title="title" />
  <div class="min-h-screen bg-muted/30 text-foreground dark:bg-background">
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r bg-card/95 backdrop-blur lg:block">
      <div class="flex h-full flex-col">
        <div class="flex h-16 items-center border-b px-5">
          <Link :href="route(homeRoute)" class="flex min-w-0 items-center gap-3 font-semibold">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground shadow-sm" :style="brandStyle">CS</span>
            <span class="min-w-0">
              <span class="block truncate text-sm">Support Desk</span>
              <span class="block truncate text-xs font-normal text-muted-foreground">{{ sectionDescription }}</span>
            </span>
          </Link>
        </div>

        <div class="px-4 py-4">
          <div class="rounded-lg border bg-background/70 p-3">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-xs font-medium uppercase text-muted-foreground">{{ sectionLabel }}</p>
                <p class="mt-1 truncate text-sm font-semibold">{{ workspaceName }}</p>
              </div>
              <Badge :tone="props.section === 'admin' ? 'blue' : 'green'">{{ props.section }}</Badge>
            </div>
          </div>
        </div>

        <nav class="flex-1 space-y-1 px-3 pb-4">
          <Link
            v-for="item in visibleItems"
            :key="item.routeName"
            :href="route(item.routeName)"
            :class="[
              'group flex h-10 items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors',
              isActive(item.routeName)
                ? 'bg-primary/10 text-primary ring-1 ring-primary/15'
                : 'text-muted-foreground hover:bg-secondary/80 hover:text-foreground',
            ]"
          >
            <component :is="item.icon" class="h-4 w-4" />
            <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
            <ChevronRight v-if="isActive(item.routeName)" class="h-3.5 w-3.5" />
          </Link>
        </nav>

        <div class="border-t p-4">
          <div class="flex items-center gap-3 rounded-lg bg-secondary/60 p-2.5">
            <Link :href="route('profile.edit')" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-card text-sm font-semibold text-primary shadow-sm ring-1 ring-border" aria-label="Profile">
              {{ initials }}
            </Link>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium">{{ user?.name }}</p>
              <p class="truncate text-xs text-muted-foreground">{{ workspaceName }}</p>
            </div>
            <ShieldCheck class="h-4 w-4 shrink-0 text-muted-foreground" />
          </div>
        </div>
      </div>
    </aside>

    <div class="lg:pl-72">
      <header class="sticky top-0 z-30 border-b bg-background/90 backdrop-blur supports-[backdrop-filter]:bg-background/75">
        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
          <div class="flex min-w-0 items-center gap-3">
            <Button variant="ghost" size="icon" class="lg:hidden" @click="open = true">
              <Menu class="h-5 w-5" />
            </Button>
            <div class="min-w-0">
              <p class="text-xs font-medium uppercase text-muted-foreground">{{ sectionLabel }}</p>
              <h1 class="truncate text-lg font-semibold text-foreground">{{ title }}</h1>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button variant="ghost" size="icon" class="text-muted-foreground hover:text-foreground" aria-label="Search" @click="searchOpen = true">
              <Search class="h-5 w-5" />
            </Button>
            <ThemeToggle />
            <NotificationInboxDropdown v-if="canViewNotifications" :unread-count="liveUnreadCount" />
            <div class="hidden text-right md:block">
              <p class="text-sm font-medium text-foreground">{{ user?.name }}</p>
              <p class="text-xs text-muted-foreground">{{ workspaceName }}</p>
            </div>
            <Link :href="route('profile.edit')" class="flex h-9 w-9 items-center justify-center rounded-md border bg-card text-sm font-semibold text-primary shadow-sm transition-colors hover:bg-secondary" aria-label="Profile">
              {{ initials }}
            </Link>
            <Link :href="route('logout')" method="post" as="button" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground" aria-label="Log out">
              <LogOut class="h-5 w-5" />
            </Link>
          </div>
        </div>
      </header>

      <main class="mx-auto max-w-[1440px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <FlashMessages />
        <slot />
      </main>
    </div>

    <div v-if="searchOpen" class="fixed inset-0 z-[60] bg-foreground/40 p-3 backdrop-blur-sm" @click.self="closeSearch">
      <div class="mx-auto mt-16 max-w-2xl overflow-hidden rounded-lg border bg-card shadow-2xl">
        <div class="flex items-center gap-3 border-b px-4 py-3">
          <Search class="h-5 w-5 text-muted-foreground" />
          <input
            v-model="searchQuery"
            class="h-10 min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
            placeholder="Search tickets, comments, users, companies, knowledge base"
            autofocus
            @keydown.esc="closeSearch"
          >
          <Button variant="ghost" size="icon" class="h-8 w-8" @click="closeSearch"><X class="h-4 w-4" /></Button>
        </div>
        <div class="max-h-[60vh] overflow-y-auto">
          <div v-if="searchLoading" class="flex items-center gap-2 p-6 text-sm text-muted-foreground">
            <Loader2 class="h-4 w-4 animate-spin" />
            Searching
          </div>
          <div v-else-if="searchResults.length" class="divide-y">
            <Link
              v-for="result in searchResults"
              :key="`${result.type}-${result.url}`"
              :href="result.url"
              class="block p-4 transition-colors hover:bg-secondary/70"
              @click="closeSearch"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium">{{ result.title }}</p>
                  <p class="mt-1 truncate text-xs text-muted-foreground">{{ result.subtitle }}</p>
                </div>
                <Badge tone="neutral">{{ result.type }}</Badge>
              </div>
            </Link>
          </div>
          <div v-else class="p-6 text-center text-sm text-muted-foreground">
            Type at least two characters to search.
          </div>
        </div>
      </div>
    </div>

    <div v-if="open" class="fixed inset-0 z-50 lg:hidden">
      <div class="absolute inset-0 bg-foreground/45 backdrop-blur-sm" @click="open = false" />
      <div class="absolute inset-y-0 left-0 flex w-72 flex-col border-r bg-card shadow-xl">
        <div class="flex h-16 items-center justify-between border-b px-5">
          <Link :href="route(homeRoute)" class="flex min-w-0 items-center gap-3 font-semibold" @click="open = false">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground" :style="brandStyle">CS</span>
            <span class="min-w-0">
              <span class="block truncate text-sm">Support Desk</span>
              <span class="block truncate text-xs font-normal text-muted-foreground">{{ sectionLabel }}</span>
            </span>
          </Link>
          <Button variant="ghost" size="icon" @click="open = false">
            <X class="h-5 w-5" />
          </Button>
        </div>
        <nav class="flex-1 space-y-1 px-3 py-4">
          <Link
            v-for="item in visibleItems"
            :key="item.routeName"
            :href="route(item.routeName)"
            :class="[
              'flex h-10 items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors',
              isActive(item.routeName)
                ? 'bg-primary/10 text-primary ring-1 ring-primary/15'
                : 'text-muted-foreground hover:bg-secondary hover:text-foreground',
            ]"
            @click="open = false"
          >
            <component :is="item.icon" class="h-4 w-4" />
            {{ item.label }}
          </Link>
        </nav>
        <div class="flex items-center justify-between border-t p-4">
          <span class="min-w-0">
            <span class="block truncate text-sm font-medium">{{ user?.name }}</span>
            <span class="block truncate text-xs text-muted-foreground">{{ workspaceName }}</span>
          </span>
          <ThemeToggle />
        </div>
      </div>
    </div>
  </div>
</template>
