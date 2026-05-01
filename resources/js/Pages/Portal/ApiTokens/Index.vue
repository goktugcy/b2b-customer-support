<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'

type Client = { id: string; name: string; status: string; last_used_at?: string; expires_at?: string; token_count: number }

defineProps<{ clients: Client[]; abilities: string[] }>()

const form = useForm({ name: '', abilities: ['tickets:create', 'tickets:read', 'tickets:comment', 'attachments:create'] as string[], expires_at: '' })
const submit = () => form.post(route('portal.api-tokens.store'), { preserveScroll: true, onSuccess: () => form.reset('name', 'expires_at') })
</script>

<template>
  <PortalLayout title="API Tokens">
    <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
      <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold">API clients</h2>
        <div class="mt-4 divide-y divide-slate-100">
          <div v-for="client in clients" :key="client.id" class="flex items-center justify-between gap-4 py-3 text-sm">
            <div><p class="font-medium">{{ client.name }}</p><p class="text-slate-500">{{ client.token_count }} token(s) · Last used {{ client.last_used_at || 'never' }}</p></div>
            <div class="flex items-center gap-2">
              <Badge :tone="client.status === 'active' ? 'green' : 'red'">{{ client.status }}</Badge>
              <Link :href="route('portal.api-tokens.destroy', client.id)" method="delete" as="button" class="text-sm font-medium text-rose-700">Disable</Link>
            </div>
          </div>
        </div>
      </div>
      <form class="rounded-md border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="submit">
        <h2 class="text-sm font-semibold">Create token</h2>
        <div class="mt-4 space-y-3">
          <div><Label>Name</Label><Input v-model="form.name" class="mt-1" required /></div>
          <div>
            <Label>Abilities</Label>
            <div class="mt-2 space-y-2">
              <label v-for="ability in abilities" :key="ability" class="flex items-center gap-2 text-sm text-slate-700">
                <input v-model="form.abilities" type="checkbox" :value="ability" class="rounded border-slate-300 text-teal-700 focus:ring-teal-700" />
                {{ ability }}
              </label>
            </div>
          </div>
          <div><Label>Expires at</Label><Input v-model="form.expires_at" class="mt-1" type="datetime-local" /></div>
          <Button type="submit" class="w-full">Create token</Button>
        </div>
      </form>
    </section>
  </PortalLayout>
</template>
