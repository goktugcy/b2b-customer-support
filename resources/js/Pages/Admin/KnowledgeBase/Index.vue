<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Archive, BookOpen, Edit3, Eye, FileText, History, MessageSquare, Plus, Search, ThumbsDown, ThumbsUp, Trash2 } from 'lucide-vue-next'
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
import FieldError from '@/Components/shared/FieldError.vue'
import EmptyState from '@/Components/shared/EmptyState.vue'
import RichContent from '@/Components/shared/RichContent.vue'
import RichTextEditor from '@/Components/shared/RichTextEditor.vue'

type Category = { id: string; parent_id?: string | null; parent?: string | null; name: string; slug: string; visibility: string; status: string; sort_order: number; articles_count: number }
type ArticleVersion = { version: number; editor?: string; status: string; visibility: string; created_at?: string }
type Article = { id: string; category_id?: string | null; category?: string | null; title: string; slug: string; excerpt?: string | null; body: string; visibility: string; status: string; feedback_count: number; helpful_count: number; not_helpful_count: number; versions_count: number; versions: ArticleVersion[] }

const props = defineProps<{
  categories: Category[]
  articles: Article[]
  filters: { search?: string }
  visibilities: string[]
  statuses: string[]
}>()

const selectedCategory = ref<Category | null>(null)
const selectedArticle = ref<Article | null>(null)
const categoryForm = useForm({ parent_id: '', name: '', slug: '', visibility: 'public', status: 'published', sort_order: 0 })
const articleForm = useForm({ category_id: '', title: '', slug: '', excerpt: '', body: '', visibility: 'public', status: 'draft' })
const searchForm = useForm({ search: props.filters.search ?? '' })
const articleVersions = computed(() => selectedArticle.value?.versions ?? [])

const resetCategory = () => {
  selectedCategory.value = null
  categoryForm.reset()
  categoryForm.clearErrors()
}
const editCategory = (category: Category) => {
  selectedCategory.value = category
  categoryForm.clearErrors()
  categoryForm.parent_id = category.parent_id ?? ''
  categoryForm.name = category.name
  categoryForm.slug = category.slug
  categoryForm.visibility = category.visibility
  categoryForm.status = category.status
  categoryForm.sort_order = category.sort_order
}
const submitCategory = () => {
  if (selectedCategory.value) {
    categoryForm.patch(route('admin.knowledge-base.categories.update', selectedCategory.value.id), { preserveScroll: true, onSuccess: resetCategory })
    return
  }
  categoryForm.post(route('admin.knowledge-base.categories.store'), { preserveScroll: true, onSuccess: resetCategory })
}
const deleteCategory = (category: Category) => router.delete(route('admin.knowledge-base.categories.destroy', category.id), { preserveScroll: true })

const resetArticle = () => {
  selectedArticle.value = null
  articleForm.reset()
  articleForm.clearErrors()
}
const editArticle = (article: Article) => {
  selectedArticle.value = article
  articleForm.clearErrors()
  articleForm.category_id = article.category_id ?? ''
  articleForm.title = article.title
  articleForm.slug = article.slug
  articleForm.excerpt = article.excerpt ?? ''
  articleForm.body = article.body
  articleForm.visibility = article.visibility
  articleForm.status = article.status
}
const submitArticle = () => {
  if (selectedArticle.value) {
    articleForm.patch(route('admin.knowledge-base.articles.update', selectedArticle.value.id), { preserveScroll: true, onSuccess: resetArticle })
    return
  }
  articleForm.post(route('admin.knowledge-base.articles.store'), { preserveScroll: true, onSuccess: resetArticle })
}
const deleteArticle = (article: Article) => router.delete(route('admin.knowledge-base.articles.destroy', article.id), { preserveScroll: true })

const publishArticle = (article: Article) => router.patch(route('admin.knowledge-base.articles.update', article.id), {
  title: article.title,
  body: article.body,
  visibility: article.visibility,
  status: article.status === 'published' ? 'archived' : 'published',
}, { preserveScroll: true })

const applySearch = () => router.get(route('admin.knowledge-base.index'), searchForm.data(), { preserveState: true, replace: true })
const clearSearch = () => {
  searchForm.search = ''
  applySearch()
}
const statusTone = (status: string) => status === 'published' ? 'green' : status === 'archived' ? 'neutral' : 'amber'
const visibilityTone = (visibility: string) => visibility === 'public' ? 'blue' : 'amber'
const formatDate = (value?: string) => {
  if (!value) {
    return ''
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? value
    : new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}
</script>

<template>
  <AdminLayout title="Knowledge Base">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold tracking-normal">Knowledge base</h2>
        <p class="mt-1 text-sm text-muted-foreground">Manage public and internal help content with versioned rich text articles.</p>
      </div>
      <Button type="button" variant="secondary" @click="resetArticle">
        <Plus class="h-4 w-4" />
        New article
      </Button>
    </div>

    <section class="mt-4 grid gap-4 xl:grid-cols-[360px_minmax(0,1fr)]">
      <div class="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm">{{ selectedCategory ? 'Edit category' : 'New category' }}</CardTitle>
          </CardHeader>
          <CardContent>
            <form class="space-y-3" @submit.prevent="submitCategory">
              <div>
                <Label>Parent</Label>
                <Select v-model="categoryForm.parent_id" class="mt-1">
                  <option value="">No parent</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id" :disabled="selectedCategory?.id === category.id">{{ category.name }}</option>
                </Select>
                <FieldError :message="categoryForm.errors.parent_id" />
              </div>
              <div>
                <Label>Name</Label>
                <Input v-model="categoryForm.name" class="mt-1" />
                <FieldError :message="categoryForm.errors.name" />
              </div>
              <div>
                <Label>Slug</Label>
                <Input v-model="categoryForm.slug" class="mt-1" />
                <FieldError :message="categoryForm.errors.slug" />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <Label>Visibility</Label>
                  <Select v-model="categoryForm.visibility" class="mt-1">
                    <option v-for="item in visibilities" :key="item" :value="item">{{ item }}</option>
                  </Select>
                </div>
                <div>
                  <Label>Status</Label>
                  <Select v-model="categoryForm.status" class="mt-1">
                    <option v-for="item in statuses" :key="item" :value="item">{{ item }}</option>
                  </Select>
                </div>
              </div>
              <div class="flex gap-2">
                <Button type="submit" class="flex-1" :disabled="categoryForm.processing">{{ selectedCategory ? 'Update category' : 'Create category' }}</Button>
                <Button v-if="selectedCategory" type="button" variant="secondary" @click="resetCategory">Cancel</Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div class="flex items-center justify-between gap-3">
              <CardTitle class="text-sm">Categories</CardTitle>
              <Badge tone="blue">{{ categories.length }}</Badge>
            </div>
          </CardHeader>
          <CardContent>
            <div v-if="categories.length" class="max-h-[560px] space-y-2 overflow-y-auto pr-1">
              <button
                v-for="category in categories"
                :key="category.id"
                type="button"
                class="w-full rounded-md border p-3 text-left transition-colors hover:border-primary/30 hover:bg-secondary/40"
                :class="selectedCategory?.id === category.id ? 'border-primary/40 bg-primary/5 ring-1 ring-primary/20' : 'bg-background/60'"
                @click="editCategory(category)"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="min-w-0">
                    <p class="truncate font-medium">{{ category.name }}</p>
                    <p v-if="category.parent" class="mt-0.5 truncate text-xs text-muted-foreground">Under {{ category.parent }}</p>
                  </div>
                  <Badge tone="neutral">{{ category.articles_count }}</Badge>
                </div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                  <Badge :tone="visibilityTone(category.visibility)">{{ category.visibility }}</Badge>
                  <Badge :tone="statusTone(category.status)">{{ category.status }}</Badge>
                </div>
                <div class="mt-3 flex justify-end gap-1">
                  <Button size="sm" variant="ghost" @click.stop="editCategory(category)"><Edit3 class="h-4 w-4" /></Button>
                  <Button size="sm" variant="ghost" @click.stop="deleteCategory(category)"><Trash2 class="h-4 w-4" /></Button>
                </div>
              </button>
            </div>
            <EmptyState v-else title="No categories yet" />
          </CardContent>
        </Card>
      </div>

      <div class="space-y-4">
        <Card>
          <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <CardTitle class="text-sm">{{ selectedArticle ? 'Edit article' : 'New article' }}</CardTitle>
                <p class="mt-1 text-xs text-muted-foreground">Use rich text formatting for portal-ready help content.</p>
              </div>
              <Badge :tone="statusTone(articleForm.status)">{{ articleForm.status }}</Badge>
            </div>
          </CardHeader>
          <CardContent>
            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submitArticle">
              <div>
                <Label>Title</Label>
                <Input v-model="articleForm.title" class="mt-1" />
                <FieldError :message="articleForm.errors.title" />
              </div>
              <div>
                <Label>Slug</Label>
                <Input v-model="articleForm.slug" class="mt-1" />
                <FieldError :message="articleForm.errors.slug" />
              </div>
              <div>
                <Label>Category</Label>
                <Select v-model="articleForm.category_id" class="mt-1">
                  <option value="">No category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </Select>
                <FieldError :message="articleForm.errors.category_id" />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <Label>Visibility</Label>
                  <Select v-model="articleForm.visibility" class="mt-1">
                    <option v-for="item in visibilities" :key="item" :value="item">{{ item }}</option>
                  </Select>
                </div>
                <div>
                  <Label>Status</Label>
                  <Select v-model="articleForm.status" class="mt-1">
                    <option v-for="item in statuses" :key="item" :value="item">{{ item }}</option>
                  </Select>
                </div>
              </div>
              <div class="md:col-span-2">
                <Label>Excerpt</Label>
                <Input v-model="articleForm.excerpt" class="mt-1" placeholder="Short article summary" />
                <FieldError :message="articleForm.errors.excerpt" />
              </div>
              <div class="md:col-span-2">
                <Label>Content</Label>
                <RichTextEditor v-model="articleForm.body" class="mt-1" placeholder="Write the article body..." />
                <FieldError :message="articleForm.errors.body" />
              </div>
              <div v-if="articleForm.body" class="md:col-span-2 rounded-md border bg-background/70 p-4">
                <div class="mb-3 flex items-center gap-2 text-xs font-medium uppercase text-muted-foreground">
                  <Eye class="h-3.5 w-3.5" />
                  Preview
                </div>
                <RichContent :html="articleForm.body" class="text-foreground" />
              </div>
              <div class="md:col-span-2 flex flex-wrap justify-end gap-2">
                <Button v-if="selectedArticle" type="button" variant="secondary" @click="resetArticle">Cancel</Button>
                <Button type="submit" :disabled="articleForm.processing">{{ selectedArticle ? 'Update article' : 'Create article' }}</Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card v-if="selectedArticle">
          <CardHeader>
            <CardTitle class="flex items-center gap-2 text-sm">
              <History class="h-4 w-4 text-primary" />
              Version history
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid gap-2 md:grid-cols-2">
              <div v-for="version in articleVersions" :key="version.version" class="rounded-md border bg-background/70 p-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                  <p class="font-medium">Version {{ version.version }}</p>
                  <Badge :tone="statusTone(version.status)">{{ version.status }}</Badge>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">{{ version.editor || 'System' }} · {{ version.visibility }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ formatDate(version.created_at) }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <CardTitle class="text-sm">Articles</CardTitle>
                <p class="mt-1 text-xs text-muted-foreground">{{ articles.length }} articles in the current view</p>
              </div>
              <form class="flex gap-2" @submit.prevent="applySearch">
                <div class="relative min-w-0">
                  <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                  <Input v-model="searchForm.search" class="w-64 pl-9" placeholder="Search articles" />
                </div>
                <Button type="submit" variant="secondary">Search</Button>
                <Button v-if="searchForm.search" type="button" variant="ghost" @click="clearSearch">Clear</Button>
              </form>
            </div>
          </CardHeader>
          <CardContent>
            <div v-if="articles.length" class="space-y-3">
              <article
                v-for="article in articles"
                :key="article.id"
                class="rounded-lg border bg-background/70 p-4 transition-colors hover:border-primary/30"
                :class="selectedArticle?.id === article.id ? 'border-primary/40 ring-1 ring-primary/20' : ''"
              >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <Badge :tone="statusTone(article.status)">{{ article.status }}</Badge>
                      <Badge :tone="visibilityTone(article.visibility)">{{ article.visibility }}</Badge>
                      <Badge v-if="article.category" tone="neutral"><BookOpen class="mr-1 h-3 w-3" />{{ article.category }}</Badge>
                    </div>
                    <h3 class="mt-3 truncate text-base font-semibold">{{ article.title }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">{{ article.excerpt || article.slug }}</p>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground">
                      <span class="inline-flex items-center gap-1"><FileText class="h-3.5 w-3.5" />{{ article.versions_count }} versions</span>
                      <span class="inline-flex items-center gap-1"><MessageSquare class="h-3.5 w-3.5" />{{ article.feedback_count }} feedback</span>
                      <span class="inline-flex items-center gap-1"><ThumbsUp class="h-3.5 w-3.5" />{{ article.helpful_count }}</span>
                      <span class="inline-flex items-center gap-1"><ThumbsDown class="h-3.5 w-3.5" />{{ article.not_helpful_count }}</span>
                    </div>
                  </div>
                  <div class="flex flex-wrap gap-2 lg:justify-end">
                    <Button size="sm" variant="secondary" @click="editArticle(article)">
                      <Edit3 class="h-4 w-4" />
                      Edit
                    </Button>
                    <Button size="sm" variant="secondary" @click="publishArticle(article)">
                      <Archive class="h-4 w-4" />
                      {{ article.status === 'published' ? 'Archive' : 'Publish' }}
                    </Button>
                    <Button size="sm" variant="ghost" @click="deleteArticle(article)">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </article>
            </div>
            <EmptyState v-else title="No articles found" description="Create an article or adjust the search query." />
          </CardContent>
        </Card>
      </div>
    </section>
  </AdminLayout>
</template>
