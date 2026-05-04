import { expect, test, type Page } from '@playwright/test'

async function signIn(page: Page, email = 'admin@example.com') {
  await page.goto('/login')
  await page.locator('#email').fill(email)
  await page.locator('#password').fill('password')
  await page.getByRole('button', { name: 'Sign in' }).click()
  await expect(page).not.toHaveURL(/\/login$/)
}

test('admin core screens render and slug previews are generated', async ({ page }) => {
  await signIn(page)

  await page.goto('/admin/dashboard')
  await expect(page.getByText('Status distribution')).toBeVisible()

  await page.goto('/admin/command-center')
  await expect(page.locator('h2', { hasText: 'Command Center' })).toBeVisible()
  await expect(page.getByRole('link', { name: 'API Docs' }).first()).toBeVisible()

  await page.goto('/api-docs')
  await expect(page.getByRole('heading', { name: 'Interactive API documentation' })).toBeVisible()

  await page.goto('/admin/tickets')
  await expect(page.getByRole('heading', { name: 'Ticket operations' })).toBeVisible()
  await page.getByPlaceholder('Search #100001, subject, company').fill('support')

  await page.goto('/admin/issue-tracking')
  await expect(page.locator('h2', { hasText: 'Issue tracking' })).toBeVisible()
  await page.getByRole('button', { name: 'Project' }).click()
  await expect(page.getByRole('heading', { name: 'Create project' })).toBeVisible()
  await page.getByRole('button', { name: 'Cancel' }).click()

  await page.goto('/admin/companies')
  await page.locator('input[autocomplete="organization"]').fill('Çağrı Öz Şirket')
  await expect(page.locator('input[readonly]').first()).toHaveValue('cagri-oz-sirket')

  await page.goto('/admin/knowledge-base')
  await page.locator('input').first().fill('İlk Yardım Kategorisi')
  await expect(page.locator('input[readonly]').first()).toHaveValue('ilk-yardim-kategorisi')
})

test('reports queue controls and portal screens render', async ({ page }) => {
  await signIn(page)

  await page.goto('/admin/reports')
  await expect(page.locator('h2', { hasText: 'Reports' })).toBeVisible()
  await page.getByRole('button', { name: 'Queue CSV' }).first().click()
  await expect(page.getByText('tickets.csv')).toBeVisible()

  await page.getByLabel('Open profile menu').click()
  await page.getByRole('button', { name: 'Log out' }).click()

  await signIn(page, 'customer@example.com')
  await page.goto('/portal/tickets')
  await expect(page.locator('h2', { hasText: 'Company tickets' })).toBeVisible()
  await page.goto('/portal/knowledge-base')
  await expect(page.locator('h2', { hasText: 'Knowledge base' })).toBeVisible()
  await page.goto('/portal/api-tokens')
  await expect(page.getByRole('link', { name: 'API Docs' }).first()).toBeVisible()
  await page.goto('/api-docs')
  await expect(page.getByRole('heading', { name: 'Interactive API documentation' })).toBeVisible()
})
