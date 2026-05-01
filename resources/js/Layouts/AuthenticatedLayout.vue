<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Building2, ClipboardList, FileClock, KeyRound, Network, Send, Users, Webhook } from 'lucide-vue-next'
import AppShell from './AppShell.vue'
import type { PageProps } from '@/types'

withDefaults(defineProps<{ title?: string }>(), {
  title: 'Dashboard',
})

const page = usePage<PageProps>()
const section = computed(() => page.props.auth.user?.is_provider ? 'admin' : 'portal')
const navItems = computed(() => section.value === 'admin'
  ? [
      { label: 'Tickets', routeName: 'admin.tickets.index', icon: ClipboardList, permission: 'tickets.view_any', providerOnly: true },
      { label: 'Companies', routeName: 'admin.companies.index', icon: Building2, permission: 'companies.manage', providerOnly: true },
      { label: 'Departments', routeName: 'admin.departments.index', icon: Network, permission: 'departments.manage', providerOnly: true },
      { label: 'Users', routeName: 'admin.users.index', icon: Users, permission: 'users.manage', providerOnly: true },
      { label: 'Invitations', routeName: 'admin.invitations.index', icon: Send, permission: 'users.invite', providerOnly: true },
      { label: 'Audit Logs', routeName: 'admin.audit-logs.index', icon: FileClock, permission: 'audit.view', providerOnly: true },
    ]
  : [
      { label: 'Tickets', routeName: 'portal.tickets.index', icon: ClipboardList, permission: 'tickets.view_company' },
      { label: 'Users', routeName: 'portal.users.index', icon: Users, permission: 'users.invite' },
      { label: 'API Tokens', routeName: 'portal.api-tokens.index', icon: KeyRound, permission: 'api_tokens.manage' },
      { label: 'Webhooks', routeName: 'portal.webhooks.index', icon: Webhook, permission: 'webhooks.manage' },
    ])
</script>

<template>
  <AppShell :title="title" :section="section" :nav-items="navItems">
    <slot />
  </AppShell>
</template>
