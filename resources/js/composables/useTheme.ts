import { computed, ref } from 'vue'

export type ThemePreference = 'light' | 'dark' | 'system'

const storageKey = 'supportdesk-theme'
const theme = ref<ThemePreference>('system')
const systemPrefersDark = ref(false)
let initialized = false
let mediaQuery: MediaQueryList | null = null

const isThemePreference = (value: string | null): value is ThemePreference => value === 'light' || value === 'dark' || value === 'system'

const readStoredTheme = (): ThemePreference => {
  if (typeof window === 'undefined') {
    return 'system'
  }

  const stored = window.localStorage.getItem(storageKey)

  return isThemePreference(stored) ? stored : 'system'
}

const resolveTheme = (preference: ThemePreference) => {
  return preference === 'system'
    ? (systemPrefersDark.value ? 'dark' : 'light')
    : preference
}

const applyTheme = (preference: ThemePreference) => {
  if (typeof document === 'undefined') {
    return
  }

  const resolved = resolveTheme(preference)
  document.documentElement.classList.toggle('dark', resolved === 'dark')
  document.documentElement.style.colorScheme = resolved
}

const onSystemThemeChange = (event: MediaQueryListEvent) => {
  systemPrefersDark.value = event.matches

  if (theme.value === 'system') {
    applyTheme(theme.value)
  }
}

export const applyInitialTheme = () => {
  if (typeof window === 'undefined') {
    return
  }

  const preference = readStoredTheme()
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  const resolved = preference === 'system' ? (prefersDark ? 'dark' : 'light') : preference

  document.documentElement.classList.toggle('dark', resolved === 'dark')
  document.documentElement.style.colorScheme = resolved
}

export const useTheme = () => {
  if (typeof window !== 'undefined' && !initialized) {
    theme.value = readStoredTheme()
    mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    systemPrefersDark.value = mediaQuery.matches
    mediaQuery.addEventListener('change', onSystemThemeChange)
    applyTheme(theme.value)

    initialized = true
  }

  const effectiveTheme = computed(() => resolveTheme(theme.value))
  const isDark = computed(() => effectiveTheme.value === 'dark')

  const setTheme = (preference: ThemePreference) => {
    theme.value = preference

    if (typeof window !== 'undefined') {
      window.localStorage.setItem(storageKey, preference)
    }

    applyTheme(preference)
  }

  const toggleTheme = () => {
    setTheme(isDark.value ? 'light' : 'dark')
  }

  return {
    theme,
    effectiveTheme,
    isDark,
    setTheme,
    toggleTheme,
  }
}
