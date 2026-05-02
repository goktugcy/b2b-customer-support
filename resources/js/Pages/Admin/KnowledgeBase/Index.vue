<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
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

type Category = { id: string; name: string; slug: string; visibility: string; status: string; sort_order: number; articles_count: number }
type Article = { id: string; category_id?: string | null; category?: string | null; title: string; slug: string; excerpt?: string | null; body: string; visibility: string; status: string }

const props = defineProps<{
  categories: Category[]
  articles: Article[]
  visibilities: string[]
  statuses: string[]
}>()

const categoryForm = useForm({ name: '', slug: '', visibility: 'public', status: 'published', sort_order: 0 })
const articleForm = useForm({ category_id: '', title: '', slug: '', excerpt: '', body: '', visibility: 'public', status: 'draft' })

const storeCategory = () => categoryForm.post(route('admin.knowledge-base.categories.store'), { preserveScroll: true, onSuccess: () => categoryForm.reset() })
const storeArticle = () => articleForm.post(route('admin.knowledge-base.articles.store'), { preserveScroll: true, onSuccess: () => articleForm.reset() })

const publishArticle = (article: Article) => router.patch(route('admin.knowledge-base.articles.update', article.id), {
  title: article.title,
  body: article.body,
  visibility: article.visibility,
  status: article.status === 'published' ? 'archived' : 'published',
}, { preserveScroll: true })
</script>

<template>
  <AdminLayout title="Knowledge Base">
    <div>
      <h2 class="text-xl font-semibold tracking-normal">Knowledge base</h2>
      <p class="mt-1 text-sm text-muted-foreground">Manage public and internal help content.</p>
    </div>

    <section class="mt-4 grid gap-4 xl:grid-cols-[360px_1fr]">
      <div class="space-y-4">
        <Card>
          <CardHeader><CardTitle class="text-sm">New category</CardTitle></CardHeader>
          <CardContent>
            <form class="space-y-3" @submit.prevent="storeCategory">
              <div><Label>Name</Label><Input v-model="categoryForm.name" class="mt-1" /></div>
              <div><Label>Slug</Label><Input v-model="categoryForm.slug" class="mt-1" /></div>
              <div class="grid grid-cols-2 gap-2">
                <Select v-model="categoryForm.visibility"><option v-for="item in visibilities" :key="item" :value="item">{{ item }}</option></Select>
                <Select v-model="categoryForm.status"><option v-for="item in statuses" :key="item" :value="item">{{ item }}</option></Select>
              </div>
              <Button type="submit" class="w-full">Create category</Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">Categories</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div v-for="category in categories" :key="category.id" class="rounded-md border p-3">
                <div class="flex items-center justify-between gap-2">
                  <p class="font-medium">{{ category.name }}</p>
                  <Badge>{{ category.articles_count }}</Badge>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">{{ category.visibility }} · {{ category.status }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="space-y-4">
        <Card>
          <CardHeader><CardTitle class="text-sm">New article</CardTitle></CardHeader>
          <CardContent>
            <form class="grid gap-3 md:grid-cols-2" @submit.prevent="storeArticle">
              <div><Label>Title</Label><Input v-model="articleForm.title" class="mt-1" /></div>
              <div><Label>Slug</Label><Input v-model="articleForm.slug" class="mt-1" /></div>
              <Select v-model="articleForm.category_id"><option value="">No category</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></Select>
              <Select v-model="articleForm.visibility"><option v-for="item in visibilities" :key="item" :value="item">{{ item }}</option></Select>
              <Select v-model="articleForm.status"><option v-for="item in statuses" :key="item" :value="item">{{ item }}</option></Select>
              <Input v-model="articleForm.excerpt" placeholder="Excerpt" />
              <Textarea v-model="articleForm.body" class="md:col-span-2" :rows="8" placeholder="Article body" />
              <div class="md:col-span-2 flex justify-end"><Button type="submit">Create article</Button></div>
            </form>
          </CardContent>
        </Card>

        <Card class="overflow-hidden">
          <CardContent class="p-0">
            <Table>
              <TableHeader><TableRow><TableHead>Article</TableHead><TableHead>Category</TableHead><TableHead>Status</TableHead><TableHead class="w-32"></TableHead></TableRow></TableHeader>
              <TableBody>
                <TableRow v-for="article in articles" :key="article.id">
                  <TableCell><p class="font-medium">{{ article.title }}</p><p class="text-xs text-muted-foreground">{{ article.slug }}</p></TableCell>
                  <TableCell>{{ article.category || '-' }}</TableCell>
                  <TableCell><Badge :tone="article.status === 'published' ? 'green' : 'neutral'">{{ article.status }}</Badge></TableCell>
                  <TableCell><Button size="sm" variant="secondary" @click="publishArticle(article)">{{ article.status === 'published' ? 'Archive' : 'Publish' }}</Button></TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </section>
  </AdminLayout>
</template>
