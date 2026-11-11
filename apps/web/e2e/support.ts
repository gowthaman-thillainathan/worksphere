import { expect, type Page } from '@playwright/test';

export const credentials = {
  email: process.env.E2E_EMAIL ?? '',
  password: process.env.E2E_PASSWORD ?? '',
};

if (!credentials.email || !credentials.password) {
  throw new Error('Set E2E_EMAIL and E2E_PASSWORD to a verified test user (see playwright.config.ts).');
}

/**
 * Signs in through the UI. Each test signs in afresh: refresh tokens are
 * single-use, so a saved storage state cannot be shared between tests.
 */
export async function signIn(page: Page, target = '/dashboard'): Promise<void> {
  await page.goto(`/login?redirect=${encodeURIComponent(target)}`);
  await page.getByLabel('E-mail').fill(credentials.email);
  await page.getByLabel('Password').fill(credentials.password);
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page).toHaveURL(new RegExp(`${target.replace(/[/?]/g, '\\$&')}`));
}

export function uniqueSuffix(): string {
  return `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}`;
}
