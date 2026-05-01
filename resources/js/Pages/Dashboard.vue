<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout title="Dashboard">
    <Card>
      <CardContent class="p-6 text-sm text-muted-foreground">
        You're logged in.
      </CardContent>
    </Card>
  </AuthenticatedLayout>
</template>
