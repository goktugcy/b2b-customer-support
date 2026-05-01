<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import FieldError from '@/Components/shared/FieldError.vue'

type Endpoint = { id: number; url: string; status: string; events: string[]; failure_count: number; deliveries_count: number; last_success_at?: string; last_failure_at?: string }

defineProps<{ endpoints: Endpoint[]; events: string[] }>()

const form = useForm({ url: '', events: ['ticket.created', 'ticket.status_changed'] as string[] })
const submit = () => form.post(route('portal.webhooks.store'), { preserveScroll: true, onSuccess: () => form.reset('url') })
</script>

<template>
  <PortalLayout title="Webhooks">
    <section class="grid gap-6 lg:grid-cols-[1fr_360px]">
      <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold">Endpoints</h2>
        <div class="mt-4 divide-y divide-slate-100">
          <div v-for="endpoint in endpoints" :key="endpoint.id" class="py-3 text-sm">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="break-all font-medium">{{ endpoint.url }}</p>
                <p class="mt-1 text-slate-500">{{ endpoint.events.join(', ') }} · {{ endpoint.deliveries_count }} deliveries</p>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <Badge :tone="endpoint.status === 'active' ? 'green' : 'red'">{{ endpoint.status }}</Badge>
                <Link :href="route('portal.webhooks.destroy', endpoint.id)" method="delete" as="button" class="text-sm font-medium text-rose-700">Disable</Link>
              </div>
            </div>
          </div>
        </div>
      </div>
      <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="submit">
        <h2 class="text-sm font-semibold">Add endpoint</h2>
        <div class="mt-4 space-y-3">
          <div><Label>HTTPS URL</Label><Input v-model="form.url" class="mt-1" required /><FieldError :message="form.errors.url" /></div>
          <div>
            <Label>Events</Label>
            <div class="mt-2 space-y-2">
              <label v-for="event in events" :key="event" class="flex items-center gap-2 text-sm text-slate-700">
                <input v-model="form.events" type="checkbox" :value="event" class="rounded border-slate-300 text-teal-700 focus:ring-teal-700" />
                {{ event }}
              </label>
            </div>
            <FieldError :message="form.errors.events" />
          </div>
          <Button type="submit" class="w-full">Create endpoint</Button>
        </div>
      </form>
    </section>
  </PortalLayout>
</template>
