<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  Building2,
  ClipboardList,
  FileClock,
  Home,
  KeyRound,
  LogOut,
  Menu,
  Send,
  UserCircle,
  Users,
  Webhook,
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

const visibleItems = computed(() => props.navItems.filter((item) => {
  if (item.providerOnly && !user.value?.is_provider) return false
  if (!item.permission) return true
  return user.value?.permissions.includes(item.permission)
}))
</script>

<template>
  <Head :title="title" />
  <div class="min-h-screen bg-slate-100 text-slate-950">
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 border-r border-slate-200 bg-white lg:block">
      <div class="flex h-16 items-center border-b border-slate-200 px-5">
        <Link :href="route(section === 'admin' ? 'admin.tickets.index' : 'portal.tickets.index')" class="flex items-center gap-2 font-semibold">
          <span class="flex h-8 w-8 items-center justify-center rounded-md bg-teal-700 text-sm font-bold text-white">CS</span>
          <span>Support Desk</span>
        </Link>
      </div>
      <nav class="space-y-1 px-3 py-4">
        <Link
          v-for="item in visibleItems"
          :key="item.routeName"
          :href="route(item.routeName)"
          :class="[
            'flex h-9 items-center gap-3 rounded-md px-3 text-sm font-medium transition',
            route().current(item.routeName) || route().current(item.routeName.replace('.index', '.*'))
              ? 'bg-teal-50 text-teal-800'
              : 'text-slate-700 hover:bg-slate-100',
          ]"
        >
          <component :is="item.icon" class="h-4 w-4" />
          {{ item.label }}
        </Link>
      </nav>
    </aside>

    <div class="lg:pl-64">
      <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
          <div class="flex min-w-0 items-center gap-3">
            <Button variant="ghost" class="lg:hidden" @click="open = true">
              <Menu class="h-5 w-5" />
            </Button>
            <div class="min-w-0">
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ section }}</p>
              <h1 class="truncate text-lg font-semibold text-slate-950">{{ title }}</h1>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-medium text-slate-900">{{ user?.name }}</p>
              <p class="text-xs text-slate-500">{{ user?.company?.name }}</p>
            </div>
            <Link :href="route('profile.edit')" class="rounded-md p-2 text-slate-600 hover:bg-slate-100">
              <UserCircle class="h-5 w-5" />
            </Link>
            <Link :href="route('logout')" method="post" as="button" class="rounded-md p-2 text-slate-600 hover:bg-slate-100">
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
      <div class="absolute inset-0 bg-slate-950/40" @click="open = false" />
      <div class="absolute inset-y-0 left-0 w-72 bg-white shadow-xl">
        <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
          <span class="font-semibold">Support Desk</span>
          <Button variant="ghost" @click="open = false">
            <X class="h-5 w-5" />
          </Button>
        </div>
        <nav class="space-y-1 px-3 py-4">
          <Link
            v-for="item in visibleItems"
            :key="item.routeName"
            :href="route(item.routeName)"
            class="flex h-9 items-center gap-3 rounded-md px-3 text-sm font-medium text-slate-700 hover:bg-slate-100"
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
