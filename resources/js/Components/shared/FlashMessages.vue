<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import Alert from '@/Components/ui/alert/Alert.vue'
import AlertTitle from '@/Components/ui/alert/AlertTitle.vue'
import AlertDescription from '@/Components/ui/alert/AlertDescription.vue'
import type { PageProps } from '@/types'

const page = usePage<PageProps>()
</script>

<template>
  <div v-if="page.props.flash.success || page.props.flash.error || page.props.flash.invitation_url || page.props.flash.plain_text_token" class="space-y-2">
    <Alert v-if="page.props.flash.success" variant="success">
      <AlertDescription>{{ page.props.flash.success }}</AlertDescription>
    </Alert>
    <Alert v-if="page.props.flash.error" variant="destructive">
      <AlertDescription>{{ page.props.flash.error }}</AlertDescription>
    </Alert>
    <Alert v-if="page.props.flash.invitation_url" variant="info">
      <AlertTitle>Invitation URL</AlertTitle>
      <AlertDescription class="break-all font-mono text-xs">{{ page.props.flash.invitation_url }}</AlertDescription>
    </Alert>
    <Alert v-if="page.props.flash.plain_text_token" variant="warning">
      <AlertTitle>API token</AlertTitle>
      <AlertDescription class="break-all font-mono text-xs">{{ page.props.flash.plain_text_token }}</AlertDescription>
    </Alert>
  </div>
</template>
