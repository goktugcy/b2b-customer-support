<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'

type Delivery = { id: string; event_type: string; status: string; attempts: number; response_status?: number; response_body_excerpt?: string; payload?: unknown; next_attempt_at?: string; delivered_at?: string; created_at?: string }
type Endpoint = { id: number; public_id: string; url: string; status: string; events: string[]; failure_count: number; deliveries_count: number; last_success_at?: string; last_failure_at?: string; deliveries: Delivery[] }

defineProps<{ endpoints: Endpoint[]; events: string[] }>()

const form = useForm({ url: '', events: ['ticket.created', 'ticket.status_changed'] as string[] })
const testPayload = ref(JSON.stringify({ type: 'webhook.test', data: { message: 'Custom test payload' } }, null, 2))
const submit = () => form.post(route('portal.webhooks.store'), { preserveScroll: true, onSuccess: () => form.reset('url') })
const testEndpoint = (endpoint: Endpoint) => router.post(route('portal.webhooks.test', endpoint.public_id), { payload: testPayload.value }, { preserveScroll: true })
const rotateSecret = (endpoint: Endpoint) => router.patch(route('portal.webhooks.secret', endpoint.public_id), {}, { preserveScroll: true })
const retryDelivery = (endpoint: Endpoint, delivery: Delivery) => router.post(route('portal.webhooks.deliveries.retry', [endpoint.public_id, delivery.id]), {}, { preserveScroll: true })
</script>

<template>
  <PortalLayout title="Webhooks">
    <PageHeader
      title="Webhooks"
      description="Deliver ticket and support events to your integration endpoints with test, retry, and secret rotation controls."
      eyebrow="Integrations"
    />

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
                  <StatusBadge :status="endpoint.status" />
                  <Button type="button" size="sm" variant="secondary" @click="testEndpoint(endpoint)">Test</Button>
                  <Button type="button" size="sm" variant="secondary" @click="rotateSecret(endpoint)">Rotate</Button>
                  <Link :href="route('portal.webhooks.destroy', endpoint.public_id)" method="delete" as="button" class="text-sm font-medium text-destructive">Disable</Link>
                </div>
              </div>
              <div v-if="endpoint.deliveries.length" class="mt-3 space-y-2 rounded-md border bg-background/70 p-3">
                <div v-for="delivery in endpoint.deliveries" :key="delivery.id" class="grid gap-2 text-xs md:grid-cols-[1fr_100px_80px_80px_auto]">
                  <div class="min-w-0">
                    <p class="font-medium">{{ delivery.event_type }}</p>
                    <p class="truncate text-muted-foreground">{{ delivery.response_body_excerpt || delivery.created_at }}</p>
                    <pre v-if="delivery.payload" class="mt-2 max-h-40 overflow-auto rounded-md bg-muted p-2 text-[11px]">{{ JSON.stringify(delivery.payload, null, 2) }}</pre>
                  </div>
                  <StatusBadge :status="delivery.status" />
                  <span class="text-muted-foreground">{{ delivery.response_status || '-' }}</span>
                  <span class="text-muted-foreground">{{ delivery.attempts }} attempt(s)</span>
                  <Button type="button" size="sm" variant="secondary" @click="retryDelivery(endpoint, delivery)">Retry</Button>
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
            <div>
              <Label>Test payload</Label>
              <Textarea v-model="testPayload" class="mt-1 font-mono text-xs" :rows="7" />
            </div>
            <Button type="submit" class="w-full">Create endpoint</Button>
          </form>
        </CardContent>
      </Card>
    </section>
  </PortalLayout>
</template>
