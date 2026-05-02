<script setup lang="ts">
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Download } from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'

const props = defineProps<{ filters: { status?: string; priority?: string; from?: string; to?: string } }>()
const form = useForm({ status: props.filters.status ?? '', priority: props.filters.priority ?? '', from: props.filters.from ?? '', to: props.filters.to ?? '' })
const params = computed(() => form.data())
</script>

<template>
  <AdminLayout title="Reports">
    <div>
      <h2 class="text-xl font-semibold tracking-normal">Reports</h2>
      <p class="mt-1 text-sm text-muted-foreground">Export filtered tickets and CSAT results.</p>
    </div>
    <Card class="mt-4">
      <CardHeader><CardTitle class="text-sm">Filters</CardTitle></CardHeader>
      <CardContent>
        <div class="grid gap-3 md:grid-cols-4">
          <div><Label>From</Label><Input v-model="form.from" type="date" class="mt-1" /></div>
          <div><Label>To</Label><Input v-model="form.to" type="date" class="mt-1" /></div>
          <Select v-model="form.status"><option value="">Any status</option><option value="open">open</option><option value="resolved">resolved</option><option value="closed">closed</option></Select>
          <Select v-model="form.priority"><option value="">Any priority</option><option value="low">low</option><option value="normal">normal</option><option value="high">high</option><option value="urgent">urgent</option></Select>
        </div>
      </CardContent>
    </Card>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
      <Card><CardContent class="flex items-center justify-between gap-3 p-4"><div><p class="font-medium">Tickets</p><p class="text-sm text-muted-foreground">CSV and PDF export</p></div><div class="flex gap-2"><Link :href="route('admin.reports.tickets.csv', params)"><Button variant="secondary"><Download /> CSV</Button></Link><Link :href="route('admin.reports.tickets.pdf', params)"><Button>PDF</Button></Link></div></CardContent></Card>
      <Card><CardContent class="flex items-center justify-between gap-3 p-4"><div><p class="font-medium">CSAT</p><p class="text-sm text-muted-foreground">Ratings and comments</p></div><div class="flex gap-2"><Link :href="route('admin.reports.csat.csv', params)"><Button variant="secondary"><Download /> CSV</Button></Link><Link :href="route('admin.reports.csat.pdf', params)"><Button>PDF</Button></Link></div></CardContent></Card>
    </div>
  </AdminLayout>
</template>
