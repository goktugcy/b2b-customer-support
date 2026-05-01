export type PaginationLink = {
  url: string | null
  label: string
  active: boolean
}

export type Paginated<T> = {
  data: T[]
  links: PaginationLink[]
  meta?: Record<string, unknown>
}

export type SelectOption = {
  value: string
  label: string
}

export type MultiSelectOption = {
  id: string
  name: string
  department_ids?: string[]
}

export type ProjectOption = {
  id: string
  name: string
  company_id?: string | null
  company?: string | null
  status?: string
  is_default?: boolean
}

export type TrackerOption = {
  id: string
  name: string
  color?: string
  status?: string
  is_default?: boolean
}

export type CategoryOption = {
  id: string
  name: string
  project_id?: string | null
  project?: string | null
  company_id?: string | null
  status?: string
}

export type TagOption = {
  id?: string
  name: string
  color?: string
}

export type CustomFieldOption = {
  value: string
  label: string
}

export type CustomFieldValue = string | number | boolean | string[] | null
export type CustomFieldValues = Record<string, CustomFieldValue>

export type CustomFieldDefinition = {
  id: string
  tracker_id?: string
  name: string
  slug: string
  type: 'text' | 'textarea' | 'number' | 'date' | 'boolean' | 'single_select' | 'multi_select' | 'user' | 'project' | 'category'
  is_required?: boolean
  required?: boolean
  validation_regex?: string | null
  status?: string
  sort_order?: number
  options: CustomFieldOption[]
  value?: unknown
}

export type Flash = {
  success?: string | null
  error?: string | null
  invitation_url?: string | null
  plain_text_token?: string | null
}

export type AuthUser = {
  id: string
  name: string
  email: string
  email_verified_at?: string | null
  is_provider: boolean
  company: {
    id: string
    name: string
    type: 'provider' | 'client'
  } | null
  roles: string[]
  permissions: string[]
}

export type PageProps = {
  auth: {
    user: AuthUser | null
  }
  flash: Flash
}

type RouteFunction = {
  (): { current: (name?: string, params?: unknown) => boolean }
  (name: string, params?: unknown, absolute?: boolean): string
  current: (name?: string, params?: unknown) => boolean
}

declare global {
  const route: RouteFunction

  interface Window {
    axios: typeof import('axios').default
    route: RouteFunction
  }
}

declare module 'vue' {
  interface ComponentCustomProperties {
    route: RouteFunction
  }
}
