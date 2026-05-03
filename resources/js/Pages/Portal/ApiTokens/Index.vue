<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import ResponsiveList from '@/Components/shared/ResponsiveList.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'

type Client = { id: string; name: string; status: string; last_used_at?: string; expires_at?: string; token_count: number }

defineProps<{ clients: Client[]; abilities: string[] }>()

const form = useForm({ name: '', abilities: ['tickets:create', 'tickets:read', 'tickets:comment', 'attachments:create'] as string[], expires_at: '' })
const submit = () => form.post(route('portal.api-tokens.store'), { preserveScroll: true, onSuccess: () => form.reset('name', 'expires_at') })
</script>

<template>
  <PortalLayout title="API Tokens">
    <PageHeader
      title="API tokens"
      description="Create scoped API clients for ticket, attachment, knowledge base, report, and CSAT access."
      eyebrow="Integrations"
    />

    <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
      <ResponsiveList>
        <div class="flex items-center justify-between bg-muted/30 px-4 py-3">
          <p class="text-sm font-medium">API clients</p>
          <p class="text-sm text-muted-foreground">{{ clients.length }} records</p>
        </div>
        <div v-for="client in clients" :key="client.id" class="grid gap-3 p-4 transition-colors hover:bg-secondary/40 lg:grid-cols-[minmax(0,1fr)_minmax(160px,0.4fr)_auto] lg:items-center">
          <div class="min-w-0">
            <p class="truncate font-medium">{{ client.name }}</p>
            <p class="truncate text-sm text-muted-foreground">{{ client.token_count }} token(s) · Last used {{ client.last_used_at || 'never' }}</p>
          </div>
          <StatusBadge :status="client.status" />
          <div class="flex justify-start lg:justify-end">
            <Link :href="route('portal.api-tokens.destroy', client.id)" method="delete" as="button" class="text-sm font-medium text-destructive">Disable</Link>
          </div>
        </div>
      </ResponsiveList>
      <Card>
        <CardHeader>
          <CardTitle class="text-sm">Create token</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="mb-4 rounded-md border bg-muted/30 p-3 text-xs leading-5 text-muted-foreground">
            Tokens should use the smallest ability set needed by the integration. The secret is only shown after creation.
          </div>
          <form class="space-y-3" @submit.prevent="submit">
            <div><Label>Name</Label><Input v-model="form.name" class="mt-1" required /></div>
            <div>
              <Label>Abilities</Label>
              <div class="mt-2 max-h-72 space-y-2 overflow-y-auto rounded-md border bg-background/70 p-3">
                <label v-for="ability in abilities" :key="ability" class="flex items-center gap-2 text-sm text-muted-foreground">
                  <Checkbox v-model="form.abilities" :value="ability" />
                  {{ ability }}
                </label>
              </div>
            </div>
            <div><Label>Expires at</Label><Input v-model="form.expires_at" class="mt-1" type="datetime-local" /></div>
            <Button type="submit" class="w-full">Create token</Button>
          </form>
        </CardContent>
      </Card>
    </section>
  </PortalLayout>
</template>
