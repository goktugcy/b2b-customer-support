<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next'
import Button from '@/Components/ui/button/Button.vue'
import { useTheme, type ThemePreference } from '@/composables/useTheme'

const { theme, isDark, setTheme } = useTheme()

const nextTheme = () => {
  const preferences: ThemePreference[] = ['light', 'dark', 'system']
  const currentIndex = preferences.indexOf(theme.value)

  setTheme(preferences[(currentIndex + 1) % preferences.length])
}

const label = () => {
  if (theme.value === 'system') {
    return `System theme (${isDark.value ? 'dark' : 'light'})`
  }

  return `${theme.value === 'dark' ? 'Dark' : 'Light'} theme`
}
</script>

<template>
  <Button
    type="button"
    variant="ghost"
    size="icon"
    :aria-label="label()"
    :title="label()"
    @click="nextTheme"
  >
    <Monitor v-if="theme === 'system'" class="h-4 w-4" />
    <Moon v-else-if="isDark" class="h-4 w-4" />
    <Sun v-else class="h-4 w-4" />
  </Button>
</template>
