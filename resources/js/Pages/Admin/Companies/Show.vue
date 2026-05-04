<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { KeyRound, Link2, Palette, ShieldCheck, Users } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Input from '@/Components/ui/input/Input.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Avatar from '@/Components/ui/avatar/Avatar.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import PageSection from '@/Components/shared/PageSection.vue'
import PriorityBadge from '@/Components/shared/PriorityBadge.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'

type Company = {
  id: string
  name: string
  slug: string
  type: string
  status: string
  timezone: string
  settings: { branding?: { display_name?: string | null; logo_url?: string | null; brand_color?: string | null } }
  api_docs_enabled: boolean
  users: { id: string; name: string; email: string; status: string; roles: string[] }[]
  api_clients: { id: string; name: string; status: string; last_used_at?: string }[]
  webhooks: { id: string; url: string; status: string; events: string[] }[]
  sla_policies: { id: number; priority: string; first_response_minutes: number; resolution_minutes: number; enabled: boolean }[]
}

const props = defineProps<{ company: Company }>()

const brandingForm = useForm({
  display_name: props.company.settings?.branding?.display_name ?? '',
  logo_url: props.company.settings?.branding?.logo_url ?? '',
  brand_color: props.company.settings?.branding?.brand_color ?? '#0f766e',
  timezone: props.company.timezone,
})

const saveSla = (company: Company, policy: Company['sla_policies'][number]) => {
  router.patch(route('admin.companies.sla-policies.update', [company.id, policy.id]), {
    first_response_minutes: policy.first_response_minutes,
    resolution_minutes: policy.resolution_minutes,
    enabled: policy.enabled,
  }, { preserveScroll: true })
}
const saveBranding = (company: Company) => {
  brandingForm.patch(route('admin.companies.branding.update', company.id), { preserveScroll: true })
}
const setApiDocsAccess = (company: Company, enabled: boolean) => {
  router.patch(route('admin.companies.api-docs-access.update', company.id), { enabled }, { preserveScroll: true })
}
</script>

<template>
  <AdminLayout :title="company.name">
    <PageHeader
      :title="company.name"
      description="Workspace profile, portal branding, SLA policies, users, and integration surface."
      eyebrow="Company detail"
    >
      <template #meta>
        <div class="flex flex-wrap gap-2">
          <Badge>{{ company.type }}</Badge>
          <StatusBadge :status="company.status" />
          <Badge tone="neutral">{{ company.timezone }}</Badge>
        </div>
      </template>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
      <Card class="lg:col-span-1">
        <CardHeader><CardTitle class="text-sm">Company</CardTitle></CardHeader>
        <CardContent>
          <dl class="space-y-3 text-sm">
            <div><dt class="text-muted-foreground">Slug</dt><dd class="font-medium">{{ company.slug }}</dd></div>
            <div><dt class="text-muted-foreground">Type</dt><dd><Badge>{{ company.type }}</Badge></dd></div>
            <div><dt class="text-muted-foreground">Status</dt><dd><StatusBadge :status="company.status" /></dd></div>
            <div><dt class="text-muted-foreground">Timezone</dt><dd class="font-medium">{{ company.timezone }}</dd></div>
          </dl>
        </CardContent>
      </Card>
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-sm">
            <Users class="h-4 w-4 text-primary" />
            Users
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 md:grid-cols-2">
            <div v-for="user in company.users" :key="user.id" class="flex min-w-0 items-center gap-3 rounded-md border bg-background/70 p-3 text-sm">
              <Avatar :name="user.name" class="h-9 w-9" />
              <div class="min-w-0 flex-1">
                <p class="truncate font-medium">{{ user.name }}</p>
                <p class="truncate text-muted-foreground">{{ user.email }}</p>
              </div>
              <StatusBadge :status="user.status" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <PageSection v-if="company.type === 'client'" title="Portal branding" description="Customer-facing identity used in the portal shell.">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-sm">
            <Palette class="h-4 w-4 text-primary" />
            Branding settings
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form class="grid gap-3 md:grid-cols-4" @submit.prevent="saveBranding(company)">
            <label class="space-y-1">
              <span class="text-xs text-muted-foreground">Display name</span>
              <Input v-model="brandingForm.display_name" />
            </label>
            <label class="space-y-1">
              <span class="text-xs text-muted-foreground">Logo URL</span>
              <Input v-model="brandingForm.logo_url" />
            </label>
            <label class="space-y-1">
              <span class="text-xs text-muted-foreground">Brand color</span>
              <Input v-model="brandingForm.brand_color" type="color" />
            </label>
            <label class="space-y-1">
              <span class="text-xs text-muted-foreground">Timezone</span>
              <Input v-model="brandingForm.timezone" />
            </label>
            <div class="flex justify-end md:col-span-4">
              <Button type="submit" :disabled="brandingForm.processing">Save branding</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </PageSection>

    <PageSection v-if="company.type === 'client'" title="SLA policies" description="24/7 first-response and resolution targets by priority.">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-sm">
            <ShieldCheck class="h-4 w-4 text-primary" />
            Policy matrix
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="policy in company.sla_policies" :key="policy.id" class="grid gap-3 rounded-md border bg-background/70 p-3 text-sm md:grid-cols-[120px_1fr_1fr_100px_auto]">
              <div class="self-center"><PriorityBadge :priority="policy.priority" /></div>
              <label class="space-y-1">
                <span class="text-xs text-muted-foreground">First response minutes</span>
                <Input v-model.number="policy.first_response_minutes" type="number" min="1" />
              </label>
              <label class="space-y-1">
                <span class="text-xs text-muted-foreground">Resolution minutes</span>
                <Input v-model.number="policy.resolution_minutes" type="number" min="1" />
              </label>
              <label class="flex items-center gap-2 self-end">
                <Checkbox v-model="policy.enabled" />
                Enabled
              </label>
              <Button type="button" class="self-end" variant="secondary" @click="saveSla(company, policy)">Save</Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </PageSection>

    <PageSection title="Integrations" description="Company API clients and webhook endpoints.">
      <div class="grid gap-4 lg:grid-cols-2">
        <Card v-if="company.type === 'client'" class="lg:col-span-2">
          <CardHeader>
            <CardTitle class="flex items-center gap-2 text-sm">
              <KeyRound class="h-4 w-4 text-primary" />
              API documentation access
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex flex-col gap-4 rounded-md border bg-background/70 p-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <p class="font-medium">Swagger UI</p>
                  <Badge :tone="company.api_docs_enabled ? 'green' : 'neutral'">
                    {{ company.api_docs_enabled ? 'Enabled for this company' : 'Disabled for this company' }}
                  </Badge>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                  Customer users can open the API docs only when access is enabled for their company.
                </p>
              </div>
              <div class="flex flex-wrap gap-2">
                <a v-if="company.api_docs_enabled" :href="route('api-docs.index')">
                  <Button type="button" variant="secondary">Open docs</Button>
                </a>
                <Button
                  type="button"
                  :variant="company.api_docs_enabled ? 'destructive' : 'secondary'"
                  @click="setApiDocsAccess(company, !company.api_docs_enabled)"
                >
                  {{ company.api_docs_enabled ? 'Disable access' : 'Enable access' }}
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle class="flex items-center gap-2 text-sm"><KeyRound class="h-4 w-4 text-primary" />API clients</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <div v-for="client in company.api_clients" :key="client.id" class="rounded-md border bg-background/70 p-3 text-sm">
              <div class="flex items-center justify-between gap-3">
                <p class="truncate font-medium">{{ client.name }}</p>
                <StatusBadge :status="client.status" />
              </div>
              <p class="mt-1 text-xs text-muted-foreground">Last used {{ client.last_used_at || 'never' }}</p>
            </div>
            <p v-if="!company.api_clients.length" class="text-sm text-muted-foreground">No API clients yet.</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle class="flex items-center gap-2 text-sm"><Link2 class="h-4 w-4 text-primary" />Webhooks</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <div v-for="webhook in company.webhooks" :key="webhook.id" class="rounded-md border bg-background/70 p-3 text-sm">
              <div class="flex items-center justify-between gap-3">
                <p class="truncate font-medium">{{ webhook.url }}</p>
                <StatusBadge :status="webhook.status" />
              </div>
              <p class="mt-1 truncate text-xs text-muted-foreground">{{ webhook.events.join(', ') || 'No events' }}</p>
            </div>
            <p v-if="!company.webhooks.length" class="text-sm text-muted-foreground">No webhooks yet.</p>
          </CardContent>
        </Card>
      </div>
    </PageSection>
  </AdminLayout>
</template>
