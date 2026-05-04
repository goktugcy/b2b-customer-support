<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import {
  AlertTriangle,
  Bot,
  Building2,
  CheckCircle2,
  ClipboardList,
  FileText,
  Gauge,
  KeyRound,
  RadioTower,
  Send,
  Settings2,
  ShieldCheck,
  Users,
  Webhook,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import PageSection from '@/Components/shared/PageSection.vue'
import MetricCard from '@/Components/shared/MetricCard.vue'
import ResponsiveList from '@/Components/shared/ResponsiveList.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'

type CompanyRow = {
  id: string
  name: string
  slug: string
  status: string
  users_count: number
  tickets_count: number
  api_docs_enabled: boolean
}

const props = defineProps<{
  platform: {
    queue_connection: string
    broadcast_connection: string | null
    reverb_enabled: boolean
    attachment_max_mb: number
    attachment_extensions: string[]
    report_exports: { pending: number; processing: number; failed: number }
  }
  operations: {
    open: number
    overdue: number
    unassigned: number
    due_soon: number
    failed_webhooks: number
    failed_automations: number
  }
  customers: {
    active_companies: number
    suspended_companies: number
    users: number
    disabled_users: number
    pending_invitations: number
    api_docs_enabled_companies: number
    companies: CompanyRow[]
  }
  configuration: {
    knowledge_base: { categories: number; published_articles: number; draft_articles: number }
    issue_tracking: { projects: number; trackers: number; categories: number; tags: number; custom_fields: number }
    support: { departments: number; canned_responses: number; automation_rules: number; sla_policies: number; client_companies_without_sla: number }
  }
}>()

const setApiDocsAccess = (company: CompanyRow, enabled: boolean) => {
  router.patch(route('admin.companies.api-docs-access.update', company.id), { enabled }, {
    preserveScroll: true,
  })
}

const healthItems = [
  { label: 'Queue', value: props.platform.queue_connection, icon: Gauge },
  { label: 'Broadcast', value: props.platform.broadcast_connection || 'disabled', icon: RadioTower },
  { label: 'Attachments', value: `${props.platform.attachment_max_mb} MB`, icon: FileText },
]
</script>

<template>
  <AdminLayout title="Command Center">
    <PageHeader
      title="Command Center"
      description="A single operational surface for platform health, customer workspaces, configuration coverage, and high-priority admin actions."
      eyebrow="Configuration"
    >
      <template #actions>
        <a :href="route('api-docs.index')">
          <Button type="button" variant="secondary">
            <FileText class="mr-2 h-4 w-4" />
            API Docs
          </Button>
        </a>
        <Link :href="route('admin.companies.index')">
          <Button type="button">
            <Building2 class="mr-2 h-4 w-4" />
            Companies
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <MetricCard label="Open tickets" :value="operations.open" :href="route('admin.tickets.index', { queue: 'all' })" :icon="ClipboardList" tone="blue" />
      <MetricCard label="Overdue" :value="operations.overdue" :href="route('admin.tickets.index', { queue: 'overdue' })" :icon="AlertTriangle" tone="red" />
      <MetricCard label="Unassigned" :value="operations.unassigned" :href="route('admin.tickets.index', { queue: 'unassigned' })" :icon="Users" tone="amber" />
      <MetricCard label="Due soon" :value="operations.due_soon" :href="route('admin.tickets.index', { queue: 'due_soon' })" :icon="Gauge" tone="green" />
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
      <PageSection title="Platform health" description="Runtime configuration and queues that affect operational reliability.">
        <div class="grid gap-4 md:grid-cols-3">
          <Card v-for="item in healthItems" :key="item.label">
            <CardContent class="p-4">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ item.label }}</p>
                  <p class="mt-2 text-lg font-semibold">{{ item.value }}</p>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-secondary text-muted-foreground">
                  <component :is="item.icon" class="h-4 w-4" />
                </span>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Reports queue</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <Badge tone="amber">Pending {{ platform.report_exports.pending }}</Badge>
                <Badge tone="blue">Processing {{ platform.report_exports.processing }}</Badge>
                <Badge :tone="platform.report_exports.failed ? 'red' : 'green'">Failed {{ platform.report_exports.failed }}</Badge>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Realtime</p>
              <div class="mt-3">
                <Badge :tone="platform.reverb_enabled ? 'green' : 'neutral'">
                  {{ platform.reverb_enabled ? 'Reverb enabled' : 'Broadcast not reverb' }}
                </Badge>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Allowed uploads</p>
              <p class="mt-2 line-clamp-2 text-sm text-muted-foreground">{{ platform.attachment_extensions.join(', ') }}</p>
            </CardContent>
          </Card>
        </div>
      </PageSection>

      <PageSection title="Quick actions" description="Shortcuts to common provider admin workflows.">
        <Card>
          <CardContent class="grid gap-2 p-3">
            <Link :href="route('admin.tickets.create')"><Button type="button" variant="ghost" class="w-full justify-start"><ClipboardList class="mr-2 h-4 w-4" />Create ticket</Button></Link>
            <Link :href="route('admin.invitations.index')"><Button type="button" variant="ghost" class="w-full justify-start"><Send class="mr-2 h-4 w-4" />Invite user</Button></Link>
            <Link :href="route('admin.reports.index')"><Button type="button" variant="ghost" class="w-full justify-start"><Gauge class="mr-2 h-4 w-4" />Reports</Button></Link>
            <Link :href="route('admin.automation-rules.index')"><Button type="button" variant="ghost" class="w-full justify-start"><Bot class="mr-2 h-4 w-4" />Automation</Button></Link>
            <Link :href="route('admin.audit-logs.index')"><Button type="button" variant="ghost" class="w-full justify-start"><ShieldCheck class="mr-2 h-4 w-4" />Audit logs</Button></Link>
            <a :href="route('api-docs.index')"><Button type="button" variant="ghost" class="w-full justify-start"><KeyRound class="mr-2 h-4 w-4" />API Docs</Button></a>
          </CardContent>
        </Card>
      </PageSection>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
      <PageSection title="Customer workspaces" description="Client companies, portal access, users, and pending invitations.">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <MetricCard label="Active companies" :value="customers.active_companies" :icon="Building2" tone="green" />
          <MetricCard label="Pending invites" :value="customers.pending_invitations" :icon="Send" tone="amber" />
          <MetricCard label="API docs access" :value="customers.api_docs_enabled_companies" :icon="KeyRound" tone="blue" />
        </div>

        <ResponsiveList class="mt-4">
          <div class="flex items-center justify-between bg-muted/30 px-4 py-3">
            <p class="text-sm font-medium">Recent client companies</p>
            <Link :href="route('admin.companies.index')" class="text-sm font-medium text-primary">View all</Link>
          </div>
          <div
            v-for="company in customers.companies"
            :key="company.id"
            class="grid gap-3 p-4 transition-colors hover:bg-secondary/40 lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-center"
          >
            <div class="min-w-0">
              <Link :href="route('admin.companies.show', company.id)" class="truncate font-medium hover:text-primary">{{ company.name }}</Link>
              <p class="truncate text-sm text-muted-foreground">{{ company.users_count }} users · {{ company.tickets_count }} tickets · {{ company.slug }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <StatusBadge :status="company.status" />
              <Badge :tone="company.api_docs_enabled ? 'green' : 'neutral'">{{ company.api_docs_enabled ? 'API docs on' : 'API docs off' }}</Badge>
            </div>
            <Button
              type="button"
              variant="secondary"
              size="sm"
              @click="setApiDocsAccess(company, !company.api_docs_enabled)"
            >
              {{ company.api_docs_enabled ? 'Disable docs' : 'Enable docs' }}
            </Button>
          </div>
        </ResponsiveList>
      </PageSection>

      <PageSection title="Configuration coverage" description="Content, issue tracking, support settings, and automation footprint.">
        <div class="grid gap-4 sm:grid-cols-2">
          <Card>
            <CardHeader><CardTitle class="flex items-center gap-2 text-sm"><FileText class="h-4 w-4 text-primary" />Knowledge base</CardTitle></CardHeader>
            <CardContent class="space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-muted-foreground">Categories</span><span class="font-medium">{{ configuration.knowledge_base.categories }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Published</span><span class="font-medium">{{ configuration.knowledge_base.published_articles }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Drafts</span><span class="font-medium">{{ configuration.knowledge_base.draft_articles }}</span></div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle class="flex items-center gap-2 text-sm"><Settings2 class="h-4 w-4 text-primary" />Issue tracking</CardTitle></CardHeader>
            <CardContent class="space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-muted-foreground">Projects</span><span class="font-medium">{{ configuration.issue_tracking.projects }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Trackers</span><span class="font-medium">{{ configuration.issue_tracking.trackers }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Categories</span><span class="font-medium">{{ configuration.issue_tracking.categories }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Tags</span><span class="font-medium">{{ configuration.issue_tracking.tags }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Custom fields</span><span class="font-medium">{{ configuration.issue_tracking.custom_fields }}</span></div>
            </CardContent>
          </Card>
          <Card class="sm:col-span-2">
            <CardHeader><CardTitle class="flex items-center gap-2 text-sm"><CheckCircle2 class="h-4 w-4 text-primary" />Support readiness</CardTitle></CardHeader>
            <CardContent class="grid gap-3 text-sm sm:grid-cols-2">
              <div class="flex justify-between"><span class="text-muted-foreground">Departments</span><span class="font-medium">{{ configuration.support.departments }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Canned responses</span><span class="font-medium">{{ configuration.support.canned_responses }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Automation rules</span><span class="font-medium">{{ configuration.support.automation_rules }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">SLA policies</span><span class="font-medium">{{ configuration.support.sla_policies }}</span></div>
              <div class="flex justify-between sm:col-span-2">
                <span class="text-muted-foreground">Client companies without SLA</span>
                <Badge :tone="configuration.support.client_companies_without_sla ? 'amber' : 'green'">{{ configuration.support.client_companies_without_sla }}</Badge>
              </div>
            </CardContent>
          </Card>
        </div>
      </PageSection>
    </div>

    <PageSection title="Operational exceptions" description="Failures that usually need provider attention.">
      <div class="grid gap-4 md:grid-cols-2">
        <MetricCard label="Failed webhooks" :value="operations.failed_webhooks" :icon="Webhook" :tone="operations.failed_webhooks ? 'red' : 'green'" />
        <MetricCard label="Failed automations" :value="operations.failed_automations" :href="route('admin.automation-rules.index')" :icon="Bot" :tone="operations.failed_automations ? 'red' : 'green'" />
      </div>
    </PageSection>
  </AdminLayout>
</template>
