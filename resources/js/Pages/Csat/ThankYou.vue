<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
</script>

<template>
  <GuestLayout>
    <Head title="Thanks" />
    <Card class="w-full max-w-md">
      <CardHeader>
        <CardTitle>Thanks for the feedback</CardTitle>
        <CardDescription>Your rating has been recorded.</CardDescription>
      </CardHeader>
      <CardContent />
    </Card>
  </GuestLayout>
</template>
