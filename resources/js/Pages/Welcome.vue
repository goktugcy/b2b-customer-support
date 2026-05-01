<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Button from '@/Components/ui/button/Button.vue'

defineProps<{
  canLogin: boolean
}>()
</script>

<template>
  <Head title="Support Desk" />
  <main class="min-h-screen bg-slate-100 text-slate-950">
    <div class="mx-auto flex min-h-screen max-w-6xl items-center px-6 py-12">
      <section class="grid w-full gap-8 lg:grid-cols-[1fr_420px] lg:items-center">
        <div>
          <div class="flex h-12 w-12 items-center justify-center rounded-md bg-teal-700 text-base font-bold text-white">CS</div>
          <h1 class="mt-6 max-w-3xl text-4xl font-semibold tracking-normal text-slate-950 sm:text-5xl">
            Customer support operations for B2B teams
          </h1>
          <p class="mt-4 max-w-2xl text-lg text-slate-600">
            Manage client requests, agent queues, ticket history, API-created issues, and webhook delivery from one tenant-aware support console.
          </p>
          <div class="mt-6 flex gap-3">
            <Link v-if="canLogin" :href="route('login')">
              <Button>Sign in</Button>
            </Link>
          </div>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <div class="border-b border-slate-200 pb-4">
            <p class="text-sm font-medium text-slate-900">Live queue</p>
            <p class="mt-1 text-sm text-slate-500">Invitation-only workspace</p>
          </div>
          <div class="mt-4 space-y-3">
            <div class="rounded-md border border-slate-200 p-3">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium">ACME onboarding request</p>
                <span class="rounded bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">waiting</span>
              </div>
              <p class="mt-2 text-sm text-slate-500">Client portal, public thread, assigned agent</p>
            </div>
            <div class="rounded-md border border-slate-200 p-3">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium">API integration failed</p>
                <span class="rounded bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-800">open</span>
              </div>
              <p class="mt-2 text-sm text-slate-500">Created through company API token</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>
</template>
