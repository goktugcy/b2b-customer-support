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
import Table from '@/Components/ui/table/Table.vue'
import TableBody from '@/Components/ui/table/TableBody.vue'
import TableCell from '@/Components/ui/table/TableCell.vue'
import TableHead from '@/Components/ui/table/TableHead.vue'
import TableHeader from '@/Components/ui/table/TableHeader.vue'
import TableRow from '@/Components/ui/table/TableRow.vue'

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
    <div>
      <h2 class="text-xl font-semibold tracking-normal">Canned responses</h2>
      <p class="mt-1 text-sm text-muted-foreground">Reusable replies with ticket variables.</p>
    </div>

    <section class="mt-4 grid gap-4 xl:grid-cols-[380px_1fr]">
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

      <Card class="overflow-hidden">
        <CardContent class="p-0">
          <Table>
            <TableHeader><TableRow><TableHead>Response</TableHead><TableHead>Scope</TableHead><TableHead>Status</TableHead><TableHead class="w-16"></TableHead></TableRow></TableHeader>
            <TableBody>
              <TableRow v-for="response in responses" :key="response.id">
                <TableCell>
                  <p class="font-medium">{{ response.title }}</p>
                  <p class="text-xs text-muted-foreground">{{ response.shortcut || 'No shortcut' }}</p>
                </TableCell>
                <TableCell><Badge>{{ response.scope }}</Badge></TableCell>
                <TableCell><Badge :tone="response.status === 'published' ? 'green' : 'neutral'">{{ response.status }}</Badge></TableCell>
                <TableCell><Button size="icon" variant="ghost" @click="remove(response.id)"><Trash2 class="h-4 w-4" /></Button></TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </section>
  </AdminLayout>
</template>
