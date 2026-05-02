<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Activity, ArrowRight, BellRing, KeyRound, Send, ShieldCheck, Webhook } from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import ThemeToggle from '@/Components/shared/ThemeToggle.vue'

defineProps<{
  canLogin: boolean
}>()
</script>

<template>
  <Head title="Support Desk" />
  <main class="min-h-screen bg-muted/30 text-foreground dark:bg-background">
    <section class="border-b bg-background/70">
      <div class="mx-auto flex max-w-6xl flex-col px-6 py-6">
        <header class="flex items-center justify-between gap-4">
          <Link href="/" class="flex items-center gap-3 font-semibold">
            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground shadow-sm">CS</span>
            <span>Support Desk</span>
          </Link>
          <div class="flex items-center gap-2">
            <ThemeToggle />
            <Link v-if="canLogin" :href="route('login')">
              <Button variant="secondary">Sign in</Button>
            </Link>
          </div>
        </header>

        <div class="grid gap-8 py-10 lg:grid-cols-[minmax(0,0.86fr)_minmax(520px,1fr)] lg:items-center">
          <div class="max-w-3xl">
            <Badge tone="blue">Tenant-aware support operations</Badge>
            <h1 class="mt-5 text-4xl font-semibold tracking-normal text-foreground sm:text-5xl">Support Desk</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-muted-foreground sm:text-lg">
              Customer support operations for B2B teams: tickets, tenant-aware users, API-created issues, attachments, and webhook delivery in one workspace.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
              <Link v-if="canLogin" :href="route('login')">
                <Button>Open workspace <ArrowRight class="h-4 w-4" /></Button>
              </Link>
              <span class="inline-flex h-10 items-center rounded-md border bg-card px-4 text-sm font-medium text-muted-foreground shadow-sm">API ready</span>
            </div>
            <div class="mt-8 grid gap-3 sm:grid-cols-3">
              <div class="rounded-lg border bg-card/80 p-3 shadow-sm">
                <Activity class="h-4 w-4 text-primary" />
                <p class="mt-3 text-sm font-medium">Ticket queue</p>
                <p class="mt-1 text-xs text-muted-foreground">SLA-aware workflow</p>
              </div>
              <div class="rounded-lg border bg-card/80 p-3 shadow-sm">
                <ShieldCheck class="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                <p class="mt-3 text-sm font-medium">Tenant controls</p>
                <p class="mt-1 text-xs text-muted-foreground">Scoped roles</p>
              </div>
              <div class="rounded-lg border bg-card/80 p-3 shadow-sm">
                <Webhook class="h-4 w-4 text-sky-600 dark:text-sky-300" />
                <p class="mt-3 text-sm font-medium">Webhooks</p>
                <p class="mt-1 text-xs text-muted-foreground">Delivery history</p>
              </div>
            </div>
          </div>

          <div class="grid gap-4">
            <div class="rounded-lg border bg-card p-4 shadow-panel dark:shadow-none">
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
                <div class="rounded-md border bg-background/70 p-3">
                  <BellRing class="h-4 w-4 text-primary" />
                  <p class="mt-3 text-sm font-medium">42 active</p>
                  <p class="mt-1 text-xs text-muted-foreground">Requests in queue</p>
                </div>
                <div class="rounded-md border bg-background/70 p-3">
                  <Send class="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                  <p class="mt-3 text-sm font-medium">18 replies</p>
                  <p class="mt-1 text-xs text-muted-foreground">This week</p>
                </div>
                <div class="rounded-md border bg-background/70 p-3">
                  <KeyRound class="h-4 w-4 text-sky-600 dark:text-sky-300" />
                  <p class="mt-3 text-sm font-medium">API tokens</p>
                  <p class="mt-1 text-xs text-muted-foreground">Scoped abilities</p>
                </div>
              </div>
              <div class="mt-4 overflow-hidden rounded-md border">
                <div class="grid grid-cols-[minmax(160px,1.4fr)_minmax(90px,1fr)_92px] bg-muted/50 px-4 py-2 text-xs font-medium uppercase text-muted-foreground">
                  <span>Ticket</span><span>Company</span><span>Status</span>
                </div>
                <div class="divide-y text-sm">
                  <div class="grid grid-cols-[minmax(160px,1.4fr)_minmax(90px,1fr)_92px] px-4 py-3"><span class="truncate font-medium">ACME onboarding request</span><span class="truncate text-muted-foreground">ACME</span><span><Badge tone="amber">waiting</Badge></span></div>
                  <div class="grid grid-cols-[minmax(160px,1.4fr)_minmax(90px,1fr)_92px] px-4 py-3"><span class="truncate font-medium">API integration failed</span><span class="truncate text-muted-foreground">Northwind</span><span><Badge tone="blue">open</Badge></span></div>
                  <div class="grid grid-cols-[minmax(160px,1.4fr)_minmax(90px,1fr)_92px] px-4 py-3"><span class="truncate font-medium">Webhook signature review</span><span class="truncate text-muted-foreground">Globex</span><span><Badge tone="green">done</Badge></span></div>
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
