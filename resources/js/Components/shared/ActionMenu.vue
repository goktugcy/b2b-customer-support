<script setup lang="ts">
import { MoreHorizontal } from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import DropdownMenu from '@/Components/ui/dropdown-menu/DropdownMenu.vue'
import DropdownMenuTrigger from '@/Components/ui/dropdown-menu/DropdownMenuTrigger.vue'
import DropdownMenuContent from '@/Components/ui/dropdown-menu/DropdownMenuContent.vue'
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger>
      <slot name="trigger">
        <Button type="button" variant="ghost" size="icon" class="h-9 w-9" aria-label="Open actions">
          <MoreHorizontal class="h-4 w-4" />
        </Button>
      </slot>
    </DropdownMenuTrigger>
    <DropdownMenuContent>
      <slot />
    </DropdownMenuContent>
  </DropdownMenu>
</template>
