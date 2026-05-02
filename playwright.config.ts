import { defineConfig, devices } from '@playwright/test'

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000'
const appKey = 'base64:6m1mH9H36uYQm2JIIKJgV23bOgvJVxNn6NqI7W0h9rY='
const appEnv = [
  'APP_ENV=testing',
  `APP_KEY=${appKey}`,
  'APP_DEBUG=true',
  'APP_URL=http://127.0.0.1:8000',
  'DB_CONNECTION=sqlite',
  'DB_DATABASE=/tmp/b2b-support-e2e.sqlite',
  'SESSION_DRIVER=file',
  'CACHE_STORE=array',
  'QUEUE_CONNECTION=sync',
  'MAIL_MAILER=array',
  'BROADCAST_CONNECTION=null',
  'VITE_REVERB_ENABLED=false',
].join(' ')

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 60_000,
  fullyParallel: false,
  reporter: [['list']],
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  webServer: process.env.PLAYWRIGHT_BASE_URL
    ? undefined
    : [
        {
          command: 'npm run dev -- --host 127.0.0.1 --port 5175',
          url: 'http://127.0.0.1:5175/@vite/client',
          reuseExistingServer: !process.env.CI,
          timeout: 120_000,
        },
        {
          command: `bash -lc "rm -f /tmp/b2b-support-e2e.sqlite && touch /tmp/b2b-support-e2e.sqlite && ${appEnv} php artisan migrate:fresh --seed && ${appEnv} php artisan db:seed --class=E2eSeeder && ${appEnv} php artisan serve --host=127.0.0.1 --port=8000"`,
          url: baseURL,
          reuseExistingServer: !process.env.CI,
          timeout: 120_000,
        },
      ],
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
})
