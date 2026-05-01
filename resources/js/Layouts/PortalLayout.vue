<script setup lang="ts">
import { ClipboardList, KeyRound, LayoutDashboard, Users, Webhook } from 'lucide-vue-next'
import AppShell from './AppShell.vue'

defineProps<{ title: string }>()

const navItems = [
  { label: 'Dashboard', routeName: 'portal.home', icon: LayoutDashboard, permission: 'tickets.view_company' },
  { label: 'Tickets', routeName: 'portal.tickets.index', icon: ClipboardList, permission: 'tickets.view_company' },
  { label: 'Users', routeName: 'portal.users.index', icon: Users, permission: 'users.invite' },
  { label: 'API Tokens', routeName: 'portal.api-tokens.index', icon: KeyRound, permission: 'api_tokens.manage' },
  { label: 'Webhooks', routeName: 'portal.webhooks.index', icon: Webhook, permission: 'webhooks.manage' },
]
</script>

<template>
  <AppShell :title="title" section="portal" :nav-items="navItems">
    <slot />
  </AppShell>
</template>
