<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Activity } from 'lucide-vue-next'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  label: string
  value: string | number
  description?: string
  href?: string | null
  icon?: unknown
  tone?: 'neutral' | 'blue' | 'green' | 'amber' | 'red'
  class?: string
}>(), {
  tone: 'neutral',
})

const iconTone = computed(() => ({
  neutral: 'bg-secondary text-muted-foreground',
  blue: 'bg-blue-50 text-blue-700 dark:bg-sky-950/50 dark:text-sky-300',
  green: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
  amber: 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
  red: 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
}[props.tone]))

const Icon = computed(() => props.icon || Activity)
</script>

<template>
  <component :is="href ? Link : 'div'" :href="href || undefined" :class="cn('group block', props.class)">
    <Card class="h-full transition-colors group-hover:border-primary/35">
      <CardContent class="p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="truncate text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ label }}</p>
            <p class="mt-2 text-3xl font-semibold tracking-normal text-foreground">{{ value }}</p>
          </div>
          <span :class="cn('flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-transparent', iconTone)">
            <component :is="Icon" class="h-4 w-4" />
          </span>
        </div>
        <p v-if="description" class="mt-2 text-sm text-muted-foreground">{{ description }}</p>
      </CardContent>
    </Card>
  </component>
</template>
