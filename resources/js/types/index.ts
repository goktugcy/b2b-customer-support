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
