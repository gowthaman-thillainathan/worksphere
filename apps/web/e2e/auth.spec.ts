import { expect, test } from '@playwright/test';
import { credentials, signIn } from './support';

test('rejects wrong credentials with a clear message', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('E-mail').fill(credentials.email);
  await page.getByLabel('Password').fill('definitely-not-the-password');
  await page.getByRole('button', { name: 'Sign in' }).click();

  await expect(page.getByRole('alert')).toContainText('The provided credentials are incorrect.');
});

test('keeps the session across reloads and ends it on sign-out', async ({ page }) => {
  await signIn(page);
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Welcome back');

  // The access token lives in memory only; a reload restores it from the HttpOnly refresh cookie.
  await page.reload();
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Welcome back');

  await page.getByRole('button', { name: /account menu/i }).click();
  await page.getByRole('menuitem', { name: 'Sign out' }).click();
  await expect(page).toHaveURL(/\/login/);

  await page.goto('/dashboard');
  await expect(page).toHaveURL(/\/login\?redirect=%2Fdashboard/);
});
