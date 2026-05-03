<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { Trash2 } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import ResponsiveList from '@/Components/shared/ResponsiveList.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'

type ResponseRow = { id: string; title: string; shortcut?: string | null; body: string; scope: string; status: string; owner?: string | null }

defineProps<{
  responses: ResponseRow[]
  scopes: string[]
  statuses: string[]
}>()

const form = useForm({ title: '', shortcut: '', body: '', scope: 'personal', status: 'published', variables: [] as string[] })
const submit = () => form.post(route('admin.canned-responses.store'), { preserveScroll: true, onSuccess: () => form.reset() })
const remove = (id: string) => router.delete(route('admin.canned-responses.destroy', id), { preserveScroll: true })
</script>

<template>
  <AdminLayout title="Canned Responses">
    <PageHeader
      title="Canned responses"
      description="Reusable replies with ticket, requester, and company variables."
      eyebrow="Configuration"
    />

    <section class="grid gap-4 xl:grid-cols-[380px_1fr]">
      <Card>
        <CardHeader><CardTitle class="text-sm">New response</CardTitle></CardHeader>
        <CardContent>
          <form class="space-y-3" @submit.prevent="submit">
            <div><Label>Title</Label><Input v-model="form.title" class="mt-1" /></div>
            <div><Label>Shortcut</Label><Input v-model="form.shortcut" class="mt-1" placeholder="/refund" /></div>
            <div class="grid grid-cols-2 gap-2">
              <Select v-model="form.scope"><option v-for="scope in scopes" :key="scope" :value="scope">{{ scope }}</option></Select>
              <Select v-model="form.status"><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></Select>
            </div>
            <Textarea v-model="form.body" :rows="8" placeholder="Hello {{requester.name}}, ..." />
            <Button type="submit" class="w-full">Create response</Button>
          </form>
        </CardContent>
      </Card>

      <ResponsiveList>
        <div class="flex items-center justify-between bg-muted/30 px-4 py-3">
          <p class="text-sm font-medium">Response library</p>
          <p class="text-sm text-muted-foreground">{{ responses.length }} records</p>
        </div>
        <div v-for="response in responses" :key="response.id" class="grid gap-3 p-4 transition-colors hover:bg-secondary/40 lg:grid-cols-[minmax(0,1fr)_120px_130px_auto] lg:items-center">
          <div class="min-w-0">
            <p class="truncate font-medium">{{ response.title }}</p>
            <p class="truncate text-xs text-muted-foreground">{{ response.shortcut || 'No shortcut' }}</p>
          </div>
          <Badge>{{ response.scope }}</Badge>
          <StatusBadge :status="response.status" />
          <div class="flex justify-start lg:justify-end">
            <Button size="icon" variant="ghost" @click="remove(response.id)"><Trash2 class="h-4 w-4" /></Button>
          </div>
        </div>
      </ResponsiveList>
    </section>
  </AdminLayout>
</template>
