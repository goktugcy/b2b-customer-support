<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Label from '@/Components/ui/label/Label.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import FieldError from '@/Components/shared/FieldError.vue'

type Ticket = {
  id: string
  subject: string
  description: string
  status: string
  priority: string
  assignee?: string
  comments: { id: string; body: string; author?: string; created_at: string }[]
}

const props = defineProps<{ ticket: Ticket }>()

const form = useForm({ body: '' })
const submit = () => form.post(route('portal.tickets.comments.store', props.ticket.id), { preserveScroll: true, onSuccess: () => form.reset() })
</script>

<template>
  <PortalLayout :title="ticket.subject">
    <Link :href="route('portal.tickets.index')" class="text-sm font-medium text-teal-800">Back to tickets</Link>
    <section class="mt-4 grid gap-6 lg:grid-cols-[1fr_320px]">
      <div class="space-y-6">
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold">{{ ticket.subject }}</h2>
            <div class="flex gap-2"><Badge tone="blue">{{ ticket.status }}</Badge><Badge>{{ ticket.priority }}</Badge></div>
          </div>
          <p class="mt-5 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ ticket.description }}</p>
        </div>

        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
          <h3 class="text-sm font-semibold">Conversation</h3>
          <div class="mt-4 space-y-3">
            <div v-for="comment in ticket.comments" :key="comment.id" class="rounded-md border border-slate-200 p-3">
              <p class="text-sm font-medium">{{ comment.author || 'Support' }}</p>
              <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ comment.body }}</p>
            </div>
          </div>
          <form class="mt-5 space-y-3" @submit.prevent="submit">
            <Label>Reply</Label>
            <Textarea v-model="form.body" required />
            <FieldError :message="form.errors.body" />
            <Button type="submit" :disabled="form.processing">Add reply</Button>
          </form>
        </div>
      </div>
      <aside class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold">Ticket details</h3>
        <dl class="mt-4 space-y-3 text-sm">
          <div><dt class="text-slate-500">Assignee</dt><dd class="font-medium">{{ ticket.assignee || 'Unassigned' }}</dd></div>
          <div><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ ticket.status }}</dd></div>
          <div><dt class="text-slate-500">Priority</dt><dd class="font-medium">{{ ticket.priority }}</dd></div>
        </dl>
      </aside>
    </section>
  </PortalLayout>
</template>
