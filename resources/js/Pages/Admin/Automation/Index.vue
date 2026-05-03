<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import PageSection from '@/Components/shared/PageSection.vue'
import StatusBadge from '@/Components/shared/StatusBadge.vue'

type Rule = { id: string; company_id?: string | null; company: string; name: string; trigger: string; conditions: unknown; actions: unknown; enabled: boolean; priority: number; last_run_at?: string | null }
type Execution = { id: number; rule?: string | null; ticket?: string | null; ticket_subject?: string | null; company?: string | null; trigger: string; status: string; error_message?: string | null; executed_at?: string | null }
type Option = { id: string; name: string }

defineProps<{
  rules: Rule[]
  executions: Execution[]
  companies: Option[]
  providerUsers: Option[]
  triggers: string[]
}>()

const form = useForm({
  name: '',
  company_id: '',
  trigger: 'ticket.created',
  conditions: '{\n  "priority": "urgent"\n}',
  actions: '[\n  { "type": "add_tag", "name": "Needs review" }\n]',
  enabled: true,
  priority: 100,
})

const submit = () => form.post(route('admin.automation-rules.store'), { preserveScroll: true, onSuccess: () => form.reset() })
const toggleRule = (rule: Rule) => router.patch(route('admin.automation-rules.update', rule.id), { enabled: !rule.enabled }, { preserveScroll: true })
const deleteRule = (rule: Rule) => router.delete(route('admin.automation-rules.destroy', rule.id), { preserveScroll: true })
</script>

<template>
  <AdminLayout title="Automation">
    <PageHeader
      title="Automation rules"
      description="Run simple ticket actions when operational events happen."
      eyebrow="Automation"
    />

    <section class="grid gap-4 xl:grid-cols-[420px_1fr]">
      <Card>
        <CardHeader><CardTitle class="text-sm">New rule</CardTitle></CardHeader>
        <CardContent>
          <form class="space-y-3" @submit.prevent="submit">
            <div><Label>Name</Label><Input v-model="form.name" class="mt-1" required /></div>
            <div class="grid grid-cols-2 gap-2">
              <Select v-model="form.trigger"><option v-for="trigger in triggers" :key="trigger" :value="trigger">{{ trigger }}</option></Select>
              <Select v-model="form.company_id"><option value="">All companies</option><option v-for="company in companies" :key="company.id" :value="company.id">{{ company.name }}</option></Select>
            </div>
            <div><Label>Conditions JSON</Label><Textarea v-model="form.conditions" class="mt-1 font-mono text-xs" :rows="5" /></div>
            <div><Label>Actions JSON</Label><Textarea v-model="form.actions" class="mt-1 font-mono text-xs" :rows="7" /></div>
            <div class="grid grid-cols-[1fr_120px] gap-2">
              <label class="flex items-center gap-2 text-sm text-muted-foreground"><Checkbox v-model="form.enabled" /> Enabled</label>
              <Input v-model.number="form.priority" type="number" min="1" />
            </div>
            <Button type="submit" class="w-full">Create rule</Button>
          </form>
        </CardContent>
      </Card>

      <div class="space-y-4">
        <PageSection title="Rules" description="Ordered provider automations scoped by company and trigger.">
          <Card>
          <CardHeader><CardTitle class="text-sm">Rules</CardTitle></CardHeader>
          <CardContent>
            <div class="divide-y">
              <div v-for="rule in rules" :key="rule.id" class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                <div class="min-w-0">
                  <p class="font-medium">{{ rule.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ rule.trigger }} · {{ rule.company }} · priority {{ rule.priority }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <StatusBadge :status="rule.enabled ? 'enabled' : 'disabled'" />
                  <Button size="sm" variant="secondary" @click="toggleRule(rule)">{{ rule.enabled ? 'Disable' : 'Enable' }}</Button>
                  <Button size="sm" variant="ghost" @click="deleteRule(rule)">Delete</Button>
                </div>
              </div>
            </div>
          </CardContent>
          </Card>
        </PageSection>

        <PageSection title="Execution log" description="Recent automation runs and failures.">
          <Card>
          <CardHeader><CardTitle class="text-sm">Execution log</CardTitle></CardHeader>
          <CardContent>
            <div class="divide-y">
              <div v-for="execution in executions" :key="execution.id" class="grid gap-2 py-3 text-sm md:grid-cols-[1fr_120px_130px] first:pt-0 last:pb-0">
                <div>
                  <p class="font-medium">{{ execution.rule || 'Deleted rule' }}</p>
                  <p class="text-xs text-muted-foreground">{{ execution.ticket }} {{ execution.ticket_subject }} · {{ execution.trigger }}</p>
                  <p v-if="execution.error_message" class="mt-1 text-xs text-destructive">{{ execution.error_message }}</p>
                </div>
                <StatusBadge :status="execution.status" />
                <span class="text-xs text-muted-foreground">{{ execution.executed_at }}</span>
              </div>
            </div>
          </CardContent>
          </Card>
        </PageSection>
      </div>
    </section>
  </AdminLayout>
</template>
