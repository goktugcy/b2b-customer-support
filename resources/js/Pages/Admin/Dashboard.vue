<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'
</script>

<template>
  <AdminLayout title="Dashboard">
    <Card>
      <CardHeader>
        <CardTitle>Operations overview</CardTitle>
        <CardDescription>Use the ticket queue to manage all customer requests.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid gap-3 md:grid-cols-3">
          <div class="rounded-md border bg-background p-4 text-sm">
            <p class="font-medium">Ticket queue</p>
            <p class="mt-1 text-muted-foreground">Prioritize open and pending requests.</p>
          </div>
          <div class="rounded-md border bg-background p-4 text-sm">
            <p class="font-medium">Teams</p>
            <p class="mt-1 text-muted-foreground">Route work through departments and agents.</p>
          </div>
          <div class="rounded-md border bg-background p-4 text-sm">
            <p class="font-medium">Audit trail</p>
            <p class="mt-1 text-muted-foreground">Review sensitive tenant activity.</p>
          </div>
        </div>
      </CardContent>
    </Card>
  </AdminLayout>
</template>
