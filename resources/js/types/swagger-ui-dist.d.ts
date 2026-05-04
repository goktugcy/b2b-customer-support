declare module 'swagger-ui-dist/swagger-ui-bundle' {
  type SwaggerUIOptions = {
    dom_id: string
    url?: string
    deepLinking?: boolean
    defaultModelsExpandDepth?: number
    displayRequestDuration?: boolean
    layout?: string
    persistAuthorization?: boolean
  }

  const SwaggerUI: (options: SwaggerUIOptions) => void

  export default SwaggerUI
}
