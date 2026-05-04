import '../css/app.css'
import 'swagger-ui-dist/swagger-ui.css'

import SwaggerUI from 'swagger-ui-dist/swagger-ui-bundle'
import { applyInitialTheme } from './composables/useTheme'

applyInitialTheme()

const root = document.getElementById('swagger-ui')

if (root) {
  SwaggerUI({
    dom_id: '#swagger-ui',
    url: root.dataset.specUrl,
    deepLinking: true,
    defaultModelsExpandDepth: 1,
    displayRequestDuration: true,
    layout: 'BaseLayout',
    persistAuthorization: true,
  })
}
