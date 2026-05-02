<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Star } from 'lucide-vue-next'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import Textarea from '@/Components/ui/textarea/Textarea.vue'
import FieldError from '@/Components/shared/FieldError.vue'

defineProps<{
  token: string
  ticket: { id: string; display_id?: string; subject: string }
}>()

const form = useForm({ rating: 0, comment: '' })
</script>

<template>
  <GuestLayout>
    <Head title="Rate support" />
    <Card class="w-full max-w-xl">
      <CardHeader>
        <CardTitle>Rate your support experience</CardTitle>
        <p class="text-sm text-muted-foreground">{{ ticket.display_id ? `${ticket.display_id} · ${ticket.subject}` : ticket.subject }}</p>
      </CardHeader>
      <CardContent>
        <form class="space-y-5" @submit.prevent="form.post(route('csat.submit', token))">
          <div class="flex gap-2">
            <button
              v-for="rating in [1, 2, 3, 4, 5]"
              :key="rating"
              type="button"
              class="flex h-12 w-12 items-center justify-center rounded-md border transition-colors"
              :class="rating <= form.rating ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:bg-secondary'"
              @click="form.rating = rating"
            >
              <Star class="h-5 w-5" />
            </button>
          </div>
          <FieldError :message="form.errors.rating" />
          <Textarea v-model="form.comment" placeholder="Optional comment" />
          <FieldError :message="form.errors.comment" />
          <Button type="submit" :disabled="form.processing || !form.rating">Submit rating</Button>
        </form>
      </CardContent>
    </Card>
  </GuestLayout>
</template>
