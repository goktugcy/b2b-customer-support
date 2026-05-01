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
import FieldError from '@/Components/shared/FieldError.vue'

type Endpoint = { id: number; url: string; status: string; events: string[]; failure_count: number; deliveries_count: number; last_success_at?: string; last_failure_at?: string }

defineProps<{ endpoints: Endpoint[]; events: string[] }>()

const form = useForm({ url: '', events: ['ticket.created', 'ticket.status_changed'] as string[] })
const submit = () => form.post(route('portal.webhooks.store'), { preserveScroll: true, onSuccess: () => form.reset('url') })
</script>

<template>
  <PortalLayout title="Webhooks">
    <section class="grid gap-6 lg:grid-cols-[1fr_360px]">
      <Card>
        <CardHeader><CardTitle class="text-sm">Endpoints</CardTitle></CardHeader>
        <CardContent>
          <div class="divide-y">
            <div v-for="endpoint in endpoints" :key="endpoint.id" class="py-3 text-sm first:pt-0 last:pb-0">
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <p class="break-all font-medium">{{ endpoint.url }}</p>
                  <p class="mt-1 text-muted-foreground">{{ endpoint.events.join(', ') }} · {{ endpoint.deliveries_count }} deliveries</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                  <Badge :tone="endpoint.status === 'active' ? 'green' : 'red'">{{ endpoint.status }}</Badge>
                  <Link :href="route('portal.webhooks.destroy', endpoint.id)" method="delete" as="button" class="text-sm font-medium text-destructive">Disable</Link>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle class="text-sm">Add endpoint</CardTitle></CardHeader>
        <CardContent>
          <form class="space-y-3" @submit.prevent="submit">
            <div><Label>HTTPS URL</Label><Input v-model="form.url" class="mt-1" required /><FieldError :message="form.errors.url" /></div>
            <div>
              <Label>Events</Label>
              <div class="mt-2 space-y-2">
                <label v-for="event in events" :key="event" class="flex items-center gap-2 text-sm text-muted-foreground">
                  <Checkbox v-model="form.events" :value="event" />
                  {{ event }}
                </label>
              </div>
              <FieldError :message="form.errors.events" />
            </div>
            <Button type="submit" class="w-full">Create endpoint</Button>
          </form>
        </CardContent>
      </Card>
    </section>
  </PortalLayout>
</template>
