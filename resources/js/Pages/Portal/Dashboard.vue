<script setup lang="ts">
import PortalLayout from '@/Layouts/PortalLayout.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'
</script>

<template>
  <PortalLayout title="Dashboard">
    <Card>
      <CardHeader>
        <CardTitle>Support workspace</CardTitle>
        <CardDescription>Use tickets to contact the support team and track request progress.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid gap-3 md:grid-cols-3">
          <div class="rounded-md border bg-background p-4 text-sm">
            <p class="font-medium">Requests</p>
            <p class="mt-1 text-muted-foreground">Create and follow customer-side tickets.</p>
          </div>
          <div class="rounded-md border bg-background p-4 text-sm">
            <p class="font-medium">Users</p>
            <p class="mt-1 text-muted-foreground">Invite teammates into the portal.</p>
          </div>
          <div class="rounded-md border bg-background p-4 text-sm">
            <p class="font-medium">Integrations</p>
            <p class="mt-1 text-muted-foreground">Manage API tokens and webhook endpoints.</p>
          </div>
        </div>
      </CardContent>
    </Card>
  </PortalLayout>
</template>
