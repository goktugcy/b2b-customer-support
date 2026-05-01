<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Activity, ShieldCheck, Webhook } from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'

defineProps<{
  canLogin: boolean
}>()
</script>

<template>
  <Head title="Support Desk" />
  <main class="min-h-screen bg-background text-foreground">
    <section class="border-b">
      <div class="mx-auto flex min-h-[82vh] max-w-6xl flex-col px-6 py-8">
        <header class="flex items-center justify-between">
          <Link href="/" class="flex items-center gap-3 font-semibold">
            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground shadow-sm">CS</span>
            <span>Support Desk</span>
          </Link>
          <Link v-if="canLogin" :href="route('login')">
            <Button variant="secondary">Sign in</Button>
          </Link>
        </header>

        <div class="grid flex-1 content-center gap-8 py-10">
          <div class="max-w-3xl">
            <div class="flex h-12 w-12 items-center justify-center rounded-md bg-primary text-base font-bold text-primary-foreground shadow-sm">CS</div>
            <h1 class="mt-6 text-5xl font-semibold tracking-normal text-foreground sm:text-6xl">Support Desk</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-muted-foreground">
              Customer support operations for B2B teams: tickets, tenant-aware users, API-created issues, attachments, and webhook delivery in one workspace.
            </p>
          </div>

          <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="rounded-lg border bg-card p-4 shadow-panel">
            <div class="flex items-center justify-between border-b pb-3">
              <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-xs font-bold text-primary-foreground">CS</span>
                <div>
                  <p class="text-sm font-semibold">Support Desk</p>
                  <p class="text-xs text-muted-foreground">Admin console</p>
                </div>
              </div>
              <Badge tone="green">live</Badge>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
              <div class="rounded-md border bg-background p-3">
                <Activity class="h-4 w-4 text-primary" />
                <p class="mt-3 text-sm font-medium">Ticket queue</p>
                <p class="mt-1 text-xs text-muted-foreground">42 active requests</p>
              </div>
              <div class="rounded-md border bg-background p-3">
                <ShieldCheck class="h-4 w-4 text-emerald-600" />
                <p class="mt-3 text-sm font-medium">Tenant controls</p>
                <p class="mt-1 text-xs text-muted-foreground">Scoped users and roles</p>
              </div>
              <div class="rounded-md border bg-background p-3">
                <Webhook class="h-4 w-4 text-blue-600" />
                <p class="mt-3 text-sm font-medium">Webhooks</p>
                <p class="mt-1 text-xs text-muted-foreground">Delivery history</p>
              </div>
            </div>
            <div class="mt-4 overflow-hidden rounded-md border">
              <div class="grid grid-cols-[1.4fr_1fr_120px] bg-muted/50 px-4 py-2 text-xs font-medium uppercase text-muted-foreground">
                <span>Ticket</span><span>Company</span><span>Status</span>
              </div>
              <div class="divide-y text-sm">
                <div class="grid grid-cols-[1.4fr_1fr_120px] px-4 py-3"><span class="font-medium">ACME onboarding request</span><span class="text-muted-foreground">ACME</span><span><Badge tone="amber">waiting</Badge></span></div>
                <div class="grid grid-cols-[1.4fr_1fr_120px] px-4 py-3"><span class="font-medium">API integration failed</span><span class="text-muted-foreground">Northwind</span><span><Badge tone="blue">open</Badge></span></div>
                <div class="grid grid-cols-[1.4fr_1fr_120px] px-4 py-3"><span class="font-medium">Webhook signature review</span><span class="text-muted-foreground">Globex</span><span><Badge tone="green">resolved</Badge></span></div>
              </div>
            </div>
          </div>
            <div class="hidden rounded-lg border bg-card p-4 shadow-panel lg:block">
            <p class="text-sm font-semibold">Client portal</p>
            <div class="mt-4 space-y-3">
              <div class="rounded-md border bg-background p-3 text-sm">
                <p class="font-medium">Create ticket</p>
                <p class="mt-1 text-muted-foreground">Priority, targets, attachments</p>
              </div>
              <div class="rounded-md border bg-background p-3 text-sm">
                <p class="font-medium">API token</p>
                <p class="mt-1 text-muted-foreground">tickets:create, attachments:create</p>
              </div>
              <div class="rounded-md border bg-background p-3 text-sm">
                <p class="font-medium">Endpoint</p>
                <p class="mt-1 break-all text-muted-foreground">https://client.example/webhooks/support</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
    </section>

    <section class="mx-auto grid max-w-6xl gap-4 px-6 py-8 md:grid-cols-3">
      <Card>
        <CardContent class="p-5 text-sm">
          <p class="font-medium">Provider control</p>
          <p class="mt-1 text-muted-foreground">Manage companies, departments, assignments, watchers, and audit logs.</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-5 text-sm">
          <p class="font-medium">Customer portal</p>
          <p class="mt-1 text-muted-foreground">Give clients a focused place to submit requests and follow replies.</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-5 text-sm">
          <p class="font-medium">Integration ready</p>
          <p class="mt-1 text-muted-foreground">API tokens, idempotent ticket creation, and webhook event delivery are built in.</p>
        </CardContent>
      </Card>
    </section>
  </main>
</template>
