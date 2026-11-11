import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end tests against the real stack: the built SPA (vite preview,
 * proxying /api) and the Laravel API on PostgreSQL. Locally, start both and
 * run `npm run test:e2e`; in CI the servers are started below.
 *
 * Credentials come from E2E_EMAIL / E2E_PASSWORD (a verified user with the
 * hr_admin and auditor roles, see .github/workflows/ci.yml).
 */
const baseURL = process.env.E2E_BASE_URL ?? 'http://localhost:5173';

export default defineConfig({
  testDir: './e2e',
  fullyParallel: false, // tests share one database
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
    { name: 'mobile', use: { ...devices['Pixel 7'] } },
  ],
  webServer: process.env.CI
    ? [
        { command: 'php ../api/artisan serve --host=127.0.0.1 --port=8000', url: 'http://127.0.0.1:8000/health/live', reuseExistingServer: false },
        { command: 'npm run preview -- --port=5173', url: baseURL, reuseExistingServer: false },
      ]
    : undefined,
});
