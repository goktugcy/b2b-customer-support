<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import RichContent from '@/Components/shared/RichContent.vue'

defineProps<{
  article: { id: string; title: string; body: string; excerpt?: string | null; category?: string | null; published_at?: string | null }
}>()
</script>

<template>
  <PortalLayout :title="article.title">
    <Link :href="route('portal.knowledge-base.index')" class="link inline-flex items-center gap-2 text-sm">
      <ArrowLeft class="h-4 w-4" />
      Back to knowledge base
    </Link>
    <Card class="mt-4">
      <CardContent class="p-6">
        <Badge v-if="article.category" tone="blue">{{ article.category }}</Badge>
        <h2 class="mt-4 text-2xl font-semibold tracking-normal">{{ article.title }}</h2>
        <p v-if="article.excerpt" class="mt-2 text-sm text-muted-foreground">{{ article.excerpt }}</p>
        <RichContent class="mt-6" :html="article.body" />
      </CardContent>
    </Card>
  </PortalLayout>
</template>
