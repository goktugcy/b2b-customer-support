<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { watch } from 'vue'
import { Building2, ExternalLink, Plus, Ticket, Users } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Select from '@/Components/ui/select/Select.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import Pagination from '@/Components/shared/Pagination.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'
import { slugify } from '@/lib/slug'
import type { Paginated } from '@/types'

type CompanyRow = {
  id: string
  name: string
  slug: string
  type: string
  status: string
  users_count: number
  tickets_count: number
}

defineProps<{ companies: Paginated<CompanyRow> }>()

const form = useForm({
  name: '',
  slug: '',
  type: 'client',
  timezone: 'UTC',
})

watch(() => form.name, (name) => {
  form.slug = slugify(name)
})

const submit = () => form.post(route('admin.companies.store'), {
  preserveScroll: true,
  onSuccess: () => form.reset(),
})

const typeTone = (type: string) => type === 'provider' ? 'blue' : 'green'
</script>

<template>
  <AdminLayout title="Companies">
    <PageHeader
      title="Companies"
      description="Manage provider and client workspaces, account structure, and support usage."
      eyebrow="Customers"
    />

    <section class="grid gap-6 xl:grid-cols-[1fr_360px]">
      <div class="space-y-4">
        <div v-if="companies.data.length" class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
          <Card
            v-for="company in companies.data"
            :key="company.id"
            class="group transition-colors hover:border-primary/30"
          >
            <CardContent class="space-y-5 p-5">
              <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-start gap-3">
                  <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md border bg-secondary text-primary">
                    <Building2 class="h-5 w-5" />
                  </div>
                  <div class="min-w-0">
                    <Link :href="route('admin.companies.show', company.id)" class="block truncate text-base font-semibold text-foreground transition-colors group-hover:text-primary">
                      {{ company.name }}
                    </Link>
                    <p class="mt-1 truncate text-sm text-muted-foreground">{{ company.slug }}</p>
                  </div>
                </div>
                <Link :href="route('admin.companies.show', company.id)">
                  <Button variant="ghost" size="icon" class="h-9 w-9" aria-label="Open company">
                    <ExternalLink class="h-4 w-4" />
                  </Button>
                </Link>
              </div>

              <div class="flex flex-wrap gap-2">
                <Badge :tone="typeTone(company.type)">{{ company.type }}</Badge>
                <StatusBadge :status="company.status" />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="rounded-md border bg-background/60 p-3">
                  <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <Users class="h-3.5 w-3.5" />
                    Users
                  </div>
                  <p class="mt-1 text-2xl font-semibold">{{ company.users_count }}</p>
                </div>
                <div class="rounded-md border bg-background/60 p-3">
                  <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <Ticket class="h-3.5 w-3.5" />
                    Tickets
                  </div>
                  <p class="mt-1 text-2xl font-semibold">{{ company.tickets_count }}</p>
                </div>
              </div>

              <Link :href="route('admin.companies.show', company.id)">
                <Button variant="secondary" class="w-full">Open workspace</Button>
              </Link>
            </CardContent>
          </Card>
        </div>

        <Card v-else>
          <CardContent class="p-6">
            <EmptyState title="No companies found" description="Create a client or provider company to start managing support workspaces." />
          </CardContent>
        </Card>

        <Pagination :links="companies.links" />
      </div>

      <Card class="h-fit">
        <CardHeader>
          <div class="flex items-center justify-between gap-3">
            <div>
              <CardTitle class="text-sm">Create company</CardTitle>
              <p class="mt-1 text-xs text-muted-foreground">New client companies receive default project and SLA setup.</p>
            </div>
            <Badge tone="blue"><Plus class="mr-1 h-3.5 w-3.5" />New</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <form class="space-y-4" @submit.prevent="submit">
            <div>
              <Label>Name</Label>
              <Input v-model="form.name" class="mt-1" required autocomplete="organization" />
              <FieldError :message="form.errors.name" />
            </div>
            <div>
              <Label>Slug</Label>
              <Input v-model="form.slug" class="mt-1" required readonly />
              <FieldError :message="form.errors.slug" />
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
              <div>
                <Label>Type</Label>
                <Select v-model="form.type" class="mt-1">
                  <option value="client">Client</option>
                  <option value="provider">Provider</option>
                </Select>
                <FieldError :message="form.errors.type" />
              </div>
              <div>
                <Label>Timezone</Label>
                <Input v-model="form.timezone" class="mt-1" required />
                <FieldError :message="form.errors.timezone" />
              </div>
            </div>
            <Button type="submit" class="w-full" :disabled="form.processing">
              <Plus class="h-4 w-4" />
              Create company
            </Button>
          </form>
        </CardContent>
      </Card>
    </section>
  </AdminLayout>
</template>
