<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Activity, KeyRound, ShieldCheck } from 'lucide-vue-next'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import ThemeToggle from '@/Components/shared/ThemeToggle.vue'

defineProps<{ title?: string }>()
</script>

<template>
  <div class="min-h-screen overflow-hidden bg-muted/30 text-foreground dark:bg-background">
    <div class="mx-auto grid min-h-screen w-full max-w-6xl items-center gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_430px] lg:px-8">
      <section class="hidden max-w-xl lg:block">
        <div class="flex items-center justify-between">
          <Link href="/" class="inline-flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground shadow-sm">CS</span>
            <span>
              <span class="block text-sm font-semibold">Support Desk</span>
              <span class="block text-xs text-muted-foreground">B2B customer operations</span>
            </span>
          </Link>
          <ThemeToggle />
        </div>
        <div class="mt-10 max-w-xl">
          <p class="text-3xl font-semibold tracking-normal text-foreground">Secure customer support workspace for provider and client teams.</p>
          <p class="mt-4 text-sm leading-6 text-muted-foreground">Tickets, invitations, API tokens, departments, and webhook operations share one consistent tenant-aware console.</p>
        </div>
        <div class="mt-8 grid gap-3">
          <div class="flex items-center gap-3 rounded-lg border bg-card/80 p-3 shadow-sm">
            <span class="flex h-10 w-10 items-center justify-center rounded-md bg-primary/10 text-primary"><Activity class="h-5 w-5" /></span>
            <div>
              <p class="text-sm font-medium">Operational queue</p>
              <p class="text-xs text-muted-foreground">SLA, assignment, watchers, and comments in one flow.</p>
            </div>
          </div>
          <div class="flex items-center gap-3 rounded-lg border bg-card/80 p-3 shadow-sm">
            <span class="flex h-10 w-10 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-300"><ShieldCheck class="h-5 w-5" /></span>
            <div>
              <p class="text-sm font-medium">Tenant-aware access</p>
              <p class="text-xs text-muted-foreground">Provider and customer roles stay scoped by workspace.</p>
            </div>
          </div>
          <div class="flex items-center gap-3 rounded-lg border bg-card/80 p-3 shadow-sm">
            <span class="flex h-10 w-10 items-center justify-center rounded-md bg-sky-500/10 text-sky-600 dark:text-sky-300"><KeyRound class="h-5 w-5" /></span>
            <div>
              <p class="text-sm font-medium">Integration ready</p>
              <p class="text-xs text-muted-foreground">API tokens and webhook delivery are part of the portal.</p>
            </div>
          </div>
        </div>
      </section>

      <Card class="w-full">
        <CardHeader>
          <div class="mb-3 flex items-center justify-between lg:hidden">
            <Link href="/" class="inline-flex items-center gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground">CS</span>
              <span class="text-sm font-semibold">Support Desk</span>
            </Link>
            <ThemeToggle />
          </div>
          <CardTitle class="text-2xl">{{ title ?? 'Support Desk' }}</CardTitle>
          <CardDescription>Use your workspace credentials to continue.</CardDescription>
        </CardHeader>
        <CardContent>
          <slot />
        </CardContent>
      </Card>
    </div>
  </div>
</template>
