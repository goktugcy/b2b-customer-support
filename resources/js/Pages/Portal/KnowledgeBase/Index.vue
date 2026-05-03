<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import Input from '@/Components/ui/input/Input.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import DataToolbar from '@/Components/shared/DataToolbar.vue'
import PageHeader from '@/Components/shared/PageHeader.vue'

type Category = { id: string; name: string; slug: string; articles_count: number }
type Article = { id: string; title: string; slug: string; excerpt?: string | null; category?: string | null; category_slug?: string | null; published_at?: string | null }

const props = defineProps<{
  categories: Category[]
  articles: Article[]
  filters: { search?: string; category?: string }
}>()

const form = useForm({ search: props.filters.search ?? '', category: props.filters.category ?? '' })
const apply = () => router.get(route('portal.knowledge-base.index'), form.data(), { preserveState: true, replace: true })
</script>

<template>
  <PortalLayout title="Knowledge Base">
    <PageHeader
      title="Knowledge base"
      description="Find public support articles and troubleshooting steps without opening a support request."
      eyebrow="Help center"
    />

    <DataToolbar>
      <div class="relative min-w-0 flex-1">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input v-model="form.search" class="pl-9" placeholder="Search articles" @keydown.enter.prevent="apply" />
      </div>
      <Button variant="secondary" @click="apply">Search</Button>
      <template #actions>
        <div class="flex max-w-full gap-2 overflow-x-auto">
          <button type="button" class="inline-flex h-9 shrink-0 items-center rounded-md border px-3 text-sm transition-colors hover:bg-secondary" :class="!form.category ? 'border-primary bg-primary/5 text-primary' : 'text-muted-foreground'" @click="form.category = ''; apply()">All</button>
          <button v-for="category in categories" :key="category.id" type="button" class="inline-flex h-9 shrink-0 items-center rounded-md border px-3 text-sm transition-colors hover:bg-secondary" :class="form.category === category.slug ? 'border-primary bg-primary/5 text-primary' : 'text-muted-foreground'" @click="form.category = category.slug; apply()">
            {{ category.name }} ({{ category.articles_count }})
          </button>
        </div>
      </template>
    </DataToolbar>

    <div class="grid gap-3 lg:grid-cols-2">
      <Link v-for="article in articles" :key="article.id" :href="route('portal.knowledge-base.show', article.slug)">
        <Card class="h-full transition-colors hover:border-primary/50">
          <CardContent class="p-4">
            <div class="flex items-center gap-2">
              <Badge v-if="article.category" tone="blue">{{ article.category }}</Badge>
            </div>
            <h3 class="mt-3 font-semibold">{{ article.title }}</h3>
            <p class="mt-2 line-clamp-2 text-sm text-muted-foreground">{{ article.excerpt || 'Open article' }}</p>
          </CardContent>
        </Card>
      </Link>
    </div>

    <EmptyState v-if="!articles.length" class="mt-4" title="No articles found" />
  </PortalLayout>
</template>
