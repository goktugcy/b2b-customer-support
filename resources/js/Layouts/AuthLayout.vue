<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'

defineProps<{ title?: string }>()
</script>

<template>
  <div class="min-h-screen bg-background px-4 py-10 text-foreground">
    <div class="mx-auto grid min-h-[calc(100vh-5rem)] max-w-5xl items-center gap-8 lg:grid-cols-[1fr_440px]">
      <section class="hidden lg:block">
        <Link href="/" class="inline-flex items-center gap-3">
          <span class="flex h-10 w-10 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground shadow-sm">CS</span>
          <span>
            <span class="block text-sm font-semibold">Support Desk</span>
            <span class="block text-xs text-muted-foreground">B2B customer operations</span>
          </span>
        </Link>
        <div class="mt-10 max-w-xl">
          <p class="text-3xl font-semibold tracking-normal text-foreground">Secure workspace for support teams and customer accounts.</p>
          <p class="mt-4 text-sm leading-6 text-muted-foreground">Tickets, invitations, API tokens, departments, and webhook operations share one consistent tenant-aware console.</p>
        </div>
      </section>

      <Card>
        <CardHeader>
          <Link href="/" class="mb-2 inline-flex items-center gap-3 lg:hidden">
            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground">CS</span>
            <span class="text-sm font-semibold">Support Desk</span>
          </Link>
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
