<script setup lang="ts">
import { BookOpen, Bot, Building2, ClipboardList, FileClock, LayoutDashboard, MessageSquareText, Network, Send, SlidersHorizontal, Users, BarChart3 } from 'lucide-vue-next'
import AppShell from './AppShell.vue'

defineProps<{ title: string }>()

const navItems = [
  { label: 'Dashboard', routeName: 'admin.home', icon: LayoutDashboard, permission: 'tickets.view_any', providerOnly: true, group: 'Operations' },
  { label: 'Tickets', routeName: 'admin.tickets.index', icon: ClipboardList, permission: 'tickets.view_any', providerOnly: true, group: 'Operations' },
  { label: 'Reports', routeName: 'admin.reports.index', icon: BarChart3, permission: 'reports.view', providerOnly: true, group: 'Operations' },
  { label: 'Companies', routeName: 'admin.companies.index', icon: Building2, permission: 'companies.manage', providerOnly: true, group: 'Customers' },
  { label: 'Users', routeName: 'admin.users.index', icon: Users, permission: 'users.manage', providerOnly: true, group: 'Customers' },
  { label: 'Invitations', routeName: 'admin.invitations.index', icon: Send, permission: 'users.invite', providerOnly: true, group: 'Customers' },
  { label: 'Departments', routeName: 'admin.departments.index', icon: Network, permission: 'departments.manage', providerOnly: true, group: 'Configuration' },
  { label: 'Issue Tracking', routeName: 'admin.issue-tracking.index', icon: SlidersHorizontal, permission: 'issue_tracking.manage', providerOnly: true, group: 'Configuration' },
  { label: 'Knowledge Base', routeName: 'admin.knowledge-base.index', icon: BookOpen, permission: 'knowledge_base.manage', providerOnly: true, group: 'Configuration' },
  { label: 'Canned Responses', routeName: 'admin.canned-responses.index', icon: MessageSquareText, permission: 'canned_responses.manage', providerOnly: true, group: 'Configuration' },
  { label: 'Automation', routeName: 'admin.automation-rules.index', icon: Bot, permission: 'automation.manage', providerOnly: true, group: 'Automation' },
  { label: 'Audit Logs', routeName: 'admin.audit-logs.index', icon: FileClock, permission: 'audit.view', providerOnly: true, group: 'Governance' },
]
</script>

<template>
  <AppShell :title="title" section="admin" :nav-items="navItems">
    <slot />
  </AppShell>
</template>
