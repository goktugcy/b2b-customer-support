<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, ThumbsDown, ThumbsUp } from 'lucide-vue-next'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import Button from '@/Components/ui/button/Button.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'
import RichContent from '@/Components/shared/RichContent.vue'

const props = defineProps<{
  article: { id: string; title: string; slug: string; body: string; excerpt?: string | null; category?: string | null; published_at?: string | null; feedback_count: number; helpful_count: number; not_helpful_count: number }
}>()

const feedbackForm = useForm({ helpful: true, comment: '' })
const submitFeedback = (helpful: boolean) => {
  feedbackForm.helpful = helpful
  feedbackForm.post(route('portal.knowledge-base.feedback', props.article.slug), {
    preserveScroll: true,
    onSuccess: () => feedbackForm.reset('comment'),
  })
}
</script>

<template>
  <PortalLayout :title="article.title">
    <Link :href="route('portal.knowledge-base.index')" class="link inline-flex items-center gap-2 text-sm">
      <ArrowLeft class="h-4 w-4" />
      Back to knowledge base
    </Link>
    <PageHeader class="mt-4" :title="article.title" :description="article.excerpt || undefined" eyebrow="Knowledge base">
      <template #meta>
        <Badge v-if="article.category" tone="blue">{{ article.category }}</Badge>
      </template>
    </PageHeader>
    <Card>
      <CardContent class="p-6">
        <RichContent :html="article.body" />
        <div class="mt-8 rounded-md border bg-muted/30 p-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-sm font-medium">Was this article helpful?</p>
              <p class="text-xs text-muted-foreground">{{ article.helpful_count }} helpful · {{ article.not_helpful_count }} not helpful</p>
            </div>
            <div class="flex gap-2">
              <Button type="button" variant="secondary" @click="submitFeedback(true)"><ThumbsUp class="h-4 w-4" /> Yes</Button>
              <Button type="button" variant="secondary" @click="submitFeedback(false)"><ThumbsDown class="h-4 w-4" /> No</Button>
            </div>
          </div>
          <Textarea v-model="feedbackForm.comment" class="mt-3" :rows="3" placeholder="Optional comment" />
        </div>
      </CardContent>
    </Card>
  </PortalLayout>
</template>
