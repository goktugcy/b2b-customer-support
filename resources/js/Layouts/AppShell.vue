<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  LogOut,
  Menu,
  X,
} from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import FlashMessages from '@/Components/shared/FlashMessages.vue'
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

const user = computed(() => page.props.auth.user)
const sectionLabel = computed(() => props.section === 'admin' ? 'Admin console' : 'Client portal')
const homeRoute = computed(() => props.section === 'admin' ? 'admin.tickets.index' : 'portal.tickets.index')
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
</script>

<template>
  <Head :title="title" />
  <div class="min-h-screen bg-background text-foreground">
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r bg-card lg:block">
      <div class="flex h-16 items-center border-b px-5">
        <Link :href="route(homeRoute)" class="flex min-w-0 items-center gap-3 font-semibold">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground shadow-sm">CS</span>
          <span class="min-w-0">
            <span class="block truncate text-sm">Support Desk</span>
            <span class="block truncate text-xs font-normal text-muted-foreground">{{ sectionLabel }}</span>
          </span>
        </Link>
      </div>
      <nav class="space-y-1 px-3 py-5">
        <Link
          v-for="item in visibleItems"
          :key="item.routeName"
          :href="route(item.routeName)"
          :class="[
            'flex h-10 items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors',
            isActive(item.routeName)
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'text-muted-foreground hover:bg-secondary hover:text-foreground',
          ]"
        >
          <component :is="item.icon" class="h-4 w-4" />
          {{ item.label }}
        </Link>
      </nav>
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
          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-medium text-foreground">{{ user?.name }}</p>
              <p class="text-xs text-muted-foreground">{{ user?.company?.name }}</p>
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

      <main class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <FlashMessages />
        <slot />
      </main>
    </div>

    <div v-if="open" class="fixed inset-0 z-50 lg:hidden">
      <div class="absolute inset-0 bg-foreground/40" @click="open = false" />
      <div class="absolute inset-y-0 left-0 w-72 border-r bg-card shadow-xl">
        <div class="flex h-16 items-center justify-between border-b px-5">
          <span class="font-semibold">Support Desk</span>
          <Button variant="ghost" size="icon" @click="open = false">
            <X class="h-5 w-5" />
          </Button>
        </div>
        <nav class="space-y-1 px-3 py-4">
          <Link
            v-for="item in visibleItems"
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
        </nav>
      </div>
    </div>
  </div>
</template>
