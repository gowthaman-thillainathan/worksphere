import { expect, test } from '@playwright/test';
import { signIn, uniqueSuffix } from './support';

test('creates, finds, edits, deletes and restores an employee', async ({ page }) => {
  const suffix = uniqueSuffix();
  const lastName = `Perera${suffix}`;

  await signIn(page, '/employees/new');

  await page.getByLabel('First name').fill('Nimal');
  await page.getByLabel('Last name').fill(lastName);
  await page.getByLabel('Work e-mail').fill(`nimal.${suffix}@meridian.test`);
  await page.getByLabel('Job title').fill('Engineer');
  await page.getByRole('button', { name: 'Create employee' }).click();

  await expect(page.getByRole('heading', { level: 1, name: `Nimal ${lastName}` })).toBeVisible();
  await expect(page.getByText(/EMP-\d{6}/).first()).toBeVisible();

  // Edit
  await page.getByRole('link', { name: 'Edit' }).click();
  await page.getByLabel('Job title').fill('Lead Engineer');
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByText('Lead Engineer · EMP-', { exact: false })).toBeVisible();

  // Find it in the directory
  await page.goto('/employees');
  await page.getByRole('searchbox', { name: 'Search employees' }).fill(lastName);
  const row = page.getByRole('row', { name: new RegExp(lastName) });
  await expect(row).toBeVisible();

  // Soft delete, then restore from the deleted view
  await row.getByRole('button', { name: /actions for/i }).click();
  await page.getByRole('menuitem', { name: 'Delete' }).click();
  await page.getByRole('dialog').getByRole('button', { name: 'Delete employee' }).click();
  await expect(page.getByRole('dialog')).toBeHidden();
  await expect(row).toBeHidden();

  // The filter lives in the URL; React Router applies it in a transition, so assert rather than check().
  const deletedOnly = page.getByRole('checkbox', { name: 'Deleted only' });
  await deletedOnly.click();
  await expect(deletedOnly).toBeChecked();
  await page.getByRole('row', { name: new RegExp(lastName) }).getByRole('button', { name: 'Restore' }).click();
  await expect(page.getByText(`Nimal ${lastName} was restored.`)).toBeVisible();
});

test('shows validation errors next to the fields', async ({ page }) => {
  await signIn(page, '/employees/new');

  await page.getByRole('button', { name: 'Create employee' }).click();
  await expect(page.getByText('Enter a first name.')).toBeVisible();
  await expect(page.getByLabel('First name')).toBeFocused();
});
