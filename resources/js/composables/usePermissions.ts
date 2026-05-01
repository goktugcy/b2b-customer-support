import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '@/types'

export function usePermissions() {
  const page = usePage<PageProps>()

  const permissions = computed(() => page.props.auth.user?.permissions ?? [])
  const roles = computed(() => page.props.auth.user?.roles ?? [])

  const can = (permission: string) => permissions.value.includes(permission)
  const hasRole = (role: string) => roles.value.includes(role)

  return { can, hasRole, permissions, roles }
}
