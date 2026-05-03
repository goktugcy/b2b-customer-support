<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  ChevronRight,
  Command,
  Loader2,
  LogOut,
  Menu,
  Plus,
  Search,
  Settings,
  ShieldCheck,
  UserRound,
  X,
} from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Avatar from '@/Components/ui/avatar/Avatar.vue'
import Dialog from '@/Components/ui/dialog/Dialog.vue'
import DialogContent from '@/Components/ui/dialog/DialogContent.vue'
import DropdownMenu from '@/Components/ui/dropdown-menu/DropdownMenu.vue'
import DropdownMenuContent from '@/Components/ui/dropdown-menu/DropdownMenuContent.vue'
import DropdownMenuItem from '@/Components/ui/dropdown-menu/DropdownMenuItem.vue'
import DropdownMenuLabel from '@/Components/ui/dropdown-menu/DropdownMenuLabel.vue'
import DropdownMenuSeparator from '@/Components/ui/dropdown-menu/DropdownMenuSeparator.vue'
import DropdownMenuTrigger from '@/Components/ui/dropdown-menu/DropdownMenuTrigger.vue'
import Sheet from '@/Components/ui/sheet/Sheet.vue'
import SheetContent from '@/Components/ui/sheet/SheetContent.vue'
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
  group?: string
}

const props = defineProps<{
  title: string
  section: 'admin' | 'portal'
  navItems: NavItem[]
}>()

const page = usePage<PageProps>()
const open = ref(false)
const searchOpen = ref(false)
const profileOpen = ref(false)
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
const canCreateTickets = computed(() => user.value?.permissions.includes('tickets.create') ?? false)
const createTicketRoute = computed(() => props.section === 'admin' ? 'admin.tickets.create' : 'portal.tickets.create')
const workspaceName = computed(() => user.value?.company?.branding?.display_name || user.value?.company?.name || 'Workspace')
const brandStyle = computed(() => user.value?.company?.branding?.brand_color ? { backgroundColor: user.value.company.branding.brand_color } : undefined)
const initials = computed(() => (user.value?.name ?? 'SD')
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

const groupedItems = computed(() => {
  const groups: { label: string; items: NavItem[] }[] = []

  visibleItems.value.forEach((item) => {
    const label = item.group || 'Workspace'
    const group = groups.find((candidate) => candidate.label === label)

    if (group) {
      group.items.push(item)
      return
    }

    groups.push({ label, items: [item] })
  })

  return groups
})

const isActive = (routeName: string) => route().current(routeName) || route().current(routeName.replace('.index', '.*'))
const activeItem = computed(() => visibleItems.value.find((item) => isActive(item.routeName)))
const activeGroup = computed(() => activeItem.value?.group || sectionLabel.value)

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

const openSearch = () => {
  searchOpen.value = true
}

const handleShortcut = (event: KeyboardEvent) => {
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault()
    openSearch()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleShortcut)

  if (!window.Echo || !user.value) return

  window.Echo.private(`users.${user.value.id}`)
    .listen('.notification.updated', (event: { unread_count: number }) => {
      liveUnreadCount.value = event.unread_count
    })
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleShortcut)

  if (window.Echo && user.value) {
    window.Echo.leave(`users.${user.value.id}`)
  }
})
</script>

<template>
  <Head :title="title" />
  <div class="min-h-screen bg-background text-foreground">
    <div class="pointer-events-none fixed inset-x-0 top-0 h-64 opacity-70 dark:opacity-25">
      <div class="surface-grid h-full" />
    </div>

    <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r bg-card/96 backdrop-blur-xl lg:block">
      <div class="flex h-full flex-col">
        <div class="flex h-16 items-center border-b px-5">
          <Link :href="route(homeRoute)" class="flex min-w-0 items-center gap-3 font-semibold">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground shadow-sm" :style="brandStyle">SD</span>
            <span class="min-w-0">
              <span class="block truncate text-sm">Support Desk</span>
              <span class="block truncate text-xs font-normal text-muted-foreground">{{ sectionDescription }}</span>
            </span>
          </Link>
        </div>

        <div class="px-4 py-4">
          <div class="rounded-md border bg-background/70 p-3">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ sectionLabel }}</p>
                <p class="mt-1 truncate text-sm font-semibold">{{ workspaceName }}</p>
              </div>
              <Badge :tone="props.section === 'admin' ? 'blue' : 'green'">{{ props.section }}</Badge>
            </div>
          </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-4">
          <div v-for="group in groupedItems" :key="group.label" class="mb-5">
            <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{{ group.label }}</p>
            <div class="space-y-1">
              <Link
                v-for="item in group.items"
                :key="item.routeName"
                :href="route(item.routeName)"
                :class="[
                  'group flex h-10 items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors',
                  isActive(item.routeName)
                    ? 'bg-primary text-primary-foreground shadow-sm'
                    : 'text-muted-foreground hover:bg-secondary/80 hover:text-foreground',
                ]"
              >
                <component :is="item.icon" class="h-4 w-4" />
                <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                <ChevronRight v-if="isActive(item.routeName)" class="h-3.5 w-3.5" />
              </Link>
            </div>
          </div>
        </nav>

        <div class="border-t p-4">
          <div class="flex items-center gap-3 rounded-md bg-secondary/60 p-2.5">
            <Avatar :name="user?.name" class="h-9 w-9" />
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium">{{ user?.name }}</p>
              <p class="truncate text-xs text-muted-foreground">{{ workspaceName }}</p>
            </div>
            <ShieldCheck class="h-4 w-4 shrink-0 text-muted-foreground" />
          </div>
        </div>
      </div>
    </aside>

    <div class="relative lg:pl-72">
      <header class="sticky top-0 z-30 border-b bg-background/92 backdrop-blur-xl supports-[backdrop-filter]:bg-background/78">
        <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
          <div class="flex min-w-0 items-center gap-3">
            <Button variant="ghost" size="icon" class="lg:hidden" aria-label="Open navigation" @click="open = true">
              <Menu class="h-5 w-5" />
            </Button>
            <div class="min-w-0">
              <div class="flex min-w-0 items-center gap-1.5 text-xs font-medium text-muted-foreground">
                <span class="truncate">{{ activeGroup }}</span>
                <ChevronRight class="h-3.5 w-3.5 shrink-0" />
                <span class="truncate">{{ activeItem?.label || title }}</span>
              </div>
              <h1 class="truncate text-lg font-semibold tracking-normal text-foreground">{{ title }}</h1>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <Button variant="secondary" class="hidden min-w-64 justify-start text-muted-foreground xl:inline-flex" @click="openSearch">
              <Search class="h-4 w-4" />
              Search workspace
              <kbd class="ml-auto rounded border bg-background px-1.5 py-0.5 text-[10px] text-muted-foreground">⌘K</kbd>
            </Button>
            <Button variant="ghost" size="icon" class="xl:hidden" aria-label="Search" @click="openSearch">
              <Search class="h-5 w-5" />
            </Button>
            <Link v-if="canCreateTickets" :href="route(createTicketRoute)" class="hidden sm:inline-flex">
              <Button>
                <Plus class="h-4 w-4" />
                New ticket
              </Button>
            </Link>
            <ThemeToggle />
            <NotificationInboxDropdown v-if="canViewNotifications" :unread-count="liveUnreadCount" />

            <div class="relative">
              <button type="button" class="flex items-center gap-2 rounded-md border bg-card p-1.5 shadow-sm transition-colors hover:bg-secondary" aria-label="Open profile menu" @click="profileOpen = !profileOpen">
                <Avatar :name="user?.name" class="h-7 w-7 text-xs" />
                <span class="hidden min-w-0 text-left md:block">
                  <span class="block max-w-36 truncate text-sm font-medium">{{ user?.name }}</span>
                  <span class="block max-w-36 truncate text-xs text-muted-foreground">{{ workspaceName }}</span>
                </span>
              </button>
              <div v-if="profileOpen" class="absolute right-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-xl" @click="profileOpen = false">
                <div class="px-2 py-1.5 text-xs font-semibold uppercase text-muted-foreground">Account</div>
                <div class="px-2 pb-2 text-xs text-muted-foreground">
                  <p class="truncate font-medium text-foreground">{{ user?.email }}</p>
                  <p class="truncate">{{ workspaceName }}</p>
                </div>
                <div class="-mx-1 my-1 h-px bg-border" />
                <Link :href="route('profile.edit')" class="flex min-h-9 items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-secondary">
                  <UserRound class="h-4 w-4" />
                  Profile settings
                </Link>
                <button type="button" class="flex min-h-9 w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm transition-colors hover:bg-secondary" @click="openSearch">
                  <Command class="h-4 w-4" />
                  Command search
                </button>
                <Link :href="route(homeRoute)" class="flex min-h-9 items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-secondary">
                  <Settings class="h-4 w-4" />
                  Workspace home
                </Link>
                <div class="-mx-1 my-1 h-px bg-border" />
                <Link :href="route('logout')" method="post" as="button" class="flex min-h-9 w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm text-destructive transition-colors hover:bg-secondary">
                  <LogOut class="h-4 w-4" />
                  Log out
                </Link>
              </div>
            </div>
          </div>
        </div>
      </header>

      <main class="mx-auto max-w-[1440px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <FlashMessages />
        <slot />
      </main>
    </div>

    <Dialog v-model:open="searchOpen">
      <DialogContent class="max-w-2xl gap-0 overflow-hidden p-0">
        <div class="flex items-center gap-3 border-b px-4 py-3">
          <Search class="h-5 w-5 text-muted-foreground" />
          <input
            v-model="searchQuery"
            class="h-11 min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
            placeholder="Search tickets, comments, users, companies, knowledge base"
            autofocus
            @keydown.esc="closeSearch"
          >
          <Button variant="ghost" size="icon" class="h-8 w-8" @click="closeSearch"><X class="h-4 w-4" /></Button>
        </div>
        <div class="max-h-[60vh] overflow-y-auto p-2">
          <div v-if="searchLoading" class="flex items-center gap-2 p-6 text-sm text-muted-foreground">
            <Loader2 class="h-4 w-4 animate-spin" />
            Searching
          </div>
          <div v-else-if="searchResults.length" class="space-y-1">
            <Link
              v-for="result in searchResults"
              :key="`${result.type}-${result.url}`"
              :href="result.url"
              class="block rounded-md p-3 transition-colors hover:bg-secondary/70"
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
          <div v-else class="p-8 text-center text-sm text-muted-foreground">
            Type at least two characters to search.
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <Sheet v-model:open="open">
      <SheetContent side="left" class="w-[min(100vw,300px)]">
        <div class="flex h-16 items-center justify-between border-b px-5">
          <Link :href="route(homeRoute)" class="flex min-w-0 items-center gap-3 font-semibold" @click="open = false">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground" :style="brandStyle">SD</span>
            <span class="min-w-0">
              <span class="block truncate text-sm">Support Desk</span>
              <span class="block truncate text-xs font-normal text-muted-foreground">{{ sectionLabel }}</span>
            </span>
          </Link>
          <Button variant="ghost" size="icon" @click="open = false">
            <X class="h-5 w-5" />
          </Button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4">
          <div v-for="group in groupedItems" :key="group.label" class="mb-5">
            <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{{ group.label }}</p>
            <div class="space-y-1">
              <Link
                v-for="item in group.items"
                :key="item.routeName"
                :href="route(item.routeName)"
                :class="[
                  'flex h-10 items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors',
                  isActive(item.routeName)
                    ? 'bg-primary text-primary-foreground shadow-sm'
                    : 'text-muted-foreground hover:bg-secondary hover:text-foreground',
                ]"
                @click="open = false"
              >
                <component :is="item.icon" class="h-4 w-4" />
                {{ item.label }}
              </Link>
            </div>
          </div>
        </nav>
        <div class="flex items-center justify-between border-t p-4">
          <span class="min-w-0">
            <span class="block truncate text-sm font-medium">{{ initials }}</span>
            <span class="block truncate text-xs text-muted-foreground">{{ workspaceName }}</span>
          </span>
          <ThemeToggle />
        </div>
      </SheetContent>
    </Sheet>
  </div>
</template>
