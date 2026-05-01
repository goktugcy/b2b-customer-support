<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '@/types'

const page = usePage<PageProps>()
</script>

<template>
  <div v-if="page.props.flash.success || page.props.flash.error || page.props.flash.invitation_url || page.props.flash.plain_text_token" class="space-y-2">
    <div v-if="page.props.flash.success" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
      {{ page.props.flash.success }}
    </div>
    <div v-if="page.props.flash.error" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
      {{ page.props.flash.error }}
    </div>
    <div v-if="page.props.flash.invitation_url" class="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
      <div class="font-medium">Invitation URL</div>
      <div class="mt-1 break-all font-mono text-xs">{{ page.props.flash.invitation_url }}</div>
    </div>
    <div v-if="page.props.flash.plain_text_token" class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
      <div class="font-medium">API token</div>
      <div class="mt-1 break-all font-mono text-xs">{{ page.props.flash.plain_text_token }}</div>
    </div>
  </div>
</template>
