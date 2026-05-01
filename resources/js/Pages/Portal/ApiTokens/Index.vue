<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'

type Client = { id: string; name: string; status: string; last_used_at?: string; expires_at?: string; token_count: number }

defineProps<{ clients: Client[]; abilities: string[] }>()

const form = useForm({ name: '', abilities: ['tickets:create', 'tickets:read', 'tickets:comment', 'attachments:create'] as string[], expires_at: '' })
const submit = () => form.post(route('portal.api-tokens.store'), { preserveScroll: true, onSuccess: () => form.reset('name', 'expires_at') })
</script>

<template>
  <PortalLayout title="API Tokens">
    <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
      <Card>
        <CardHeader><CardTitle class="text-sm">API clients</CardTitle></CardHeader>
        <CardContent>
          <div class="divide-y">
            <div v-for="client in clients" :key="client.id" class="flex items-center justify-between gap-4 py-3 text-sm first:pt-0 last:pb-0">
              <div><p class="font-medium">{{ client.name }}</p><p class="text-muted-foreground">{{ client.token_count }} token(s) · Last used {{ client.last_used_at || 'never' }}</p></div>
              <div class="flex items-center gap-2">
                <Badge :tone="client.status === 'active' ? 'green' : 'red'">{{ client.status }}</Badge>
                <Link :href="route('portal.api-tokens.destroy', client.id)" method="delete" as="button" class="text-sm font-medium text-destructive">Disable</Link>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle class="text-sm">Create token</CardTitle></CardHeader>
        <CardContent>
          <form class="space-y-3" @submit.prevent="submit">
            <div><Label>Name</Label><Input v-model="form.name" class="mt-1" required /></div>
            <div>
              <Label>Abilities</Label>
              <div class="mt-2 space-y-2">
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
