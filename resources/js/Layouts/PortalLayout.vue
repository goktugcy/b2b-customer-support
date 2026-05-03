<script setup lang="ts">
import { BarChart3, BookOpen, ClipboardList, KeyRound, LayoutDashboard, Users, Webhook } from 'lucide-vue-next'
import AppShell from './AppShell.vue'

defineProps<{ title: string }>()

const navItems = [
  { label: 'Dashboard', routeName: 'portal.home', icon: LayoutDashboard, permission: 'tickets.view_company', group: 'Workspace' },
  { label: 'Tickets', routeName: 'portal.tickets.index', icon: ClipboardList, permission: 'tickets.view_company', group: 'Workspace' },
  { label: 'Knowledge Base', routeName: 'portal.knowledge-base.index', icon: BookOpen, group: 'Workspace' },
  { label: 'Reports', routeName: 'portal.reports.index', icon: BarChart3, permission: 'reports.view', group: 'Workspace' },
  { label: 'Users', routeName: 'portal.users.index', icon: Users, permission: 'users.invite', group: 'Administration' },
  { label: 'API Tokens', routeName: 'portal.api-tokens.index', icon: KeyRound, permission: 'api_tokens.manage', group: 'Integrations' },
  { label: 'Webhooks', routeName: 'portal.webhooks.index', icon: Webhook, permission: 'webhooks.manage', group: 'Integrations' },
]
</script>

<template>
  <AppShell :title="title" section="portal" :nav-items="navItems">
    <slot />
  </AppShell>
</template>
