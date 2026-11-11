import { expect, test, type Page } from '@playwright/test';
import { signIn, uniqueSuffix } from './support';

async function createEmployee(page: Page, firstName: string, lastName: string, email: string): Promise<void> {
  await page.getByLabel('First name').fill(firstName);
  await page.getByLabel('Last name').fill(lastName);
  await page.getByLabel('Work e-mail').fill(email);
  await page.getByRole('button', { name: 'Next' }).click();

  await expect(page.getByRole('heading', { name: 'Employment' })).toBeVisible();
  await page.getByLabel('Designation').fill('Engineer');
  await page.getByRole('button', { name: 'Next' }).click();

  await expect(page.getByRole('heading', { name: 'Account' })).toBeVisible();
  await page.getByRole('button', { name: 'Next' }).click();

  await expect(page.getByRole('heading', { name: 'Review' })).toBeVisible();
  await expect(page.getByRole('region', { name: 'Personal information' })).toContainText(email);
  await page.getByRole('button', { name: 'Create employee' }).click();
}

test('creates, edits, finds, deletes and restores an employee', async ({ page }) => {
  const suffix = uniqueSuffix();
  const lastName = `Perera${suffix}`;
  const name = `Nimal ${lastName}`;

  await signIn(page, '/employees/new');
  await createEmployee(page, 'Nimal', lastName, `nimal.${suffix}@meridian.test`);

  await expect(page.getByRole('heading', { level: 1, name })).toBeVisible();
  await expect(page.getByText(/EMP-\d{6}/).first()).toBeVisible();

  // Edit
  await page.getByRole('link', { name: 'Edit' }).click();
  await page.getByLabel('Designation').fill('Lead Engineer');
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByRole('heading', { level: 1, name })).toBeVisible();
  await expect(page.getByText('Lead Engineer').first()).toBeVisible();

  // The change is in the audit history
  await page.getByRole('tab', { name: 'Audit history' }).click();
  await expect(page.getByRole('tabpanel').getByText('Profile updated')).toBeVisible();
  await expect(page.getByRole('tabpanel').getByText('Employee created')).toBeVisible();

  // Find it in the directory (table on desktop, cards on phones)
  await page.goto('/employees');
  await page.getByRole('searchbox', { name: 'Search employees' }).fill(lastName);
  await expect(page.getByRole('link', { name })).toBeVisible();

  // Soft delete with a reason
  await page.getByRole('button', { name: `Actions for ${name}` }).click();
  await page.getByRole('menuitem', { name: 'Delete' }).click();
  const deleteDialog = page.getByRole('dialog', { name: `Delete ${name}?` });
  await deleteDialog.getByLabel('Reason').fill('Created by the end-to-end test');
  await deleteDialog.getByRole('button', { name: 'Delete employee' }).click();
  await expect(deleteDialog).toBeHidden();
  await expect(page.getByRole('link', { name })).toBeHidden();

  // Show deleted records through the filter panel, then restore
  await page.getByRole('button', { name: /filters/i }).click();
  await page.getByRole('radio', { name: 'Deleted records' }).check();
  await page.getByRole('button', { name: 'Apply filters' }).click();
  await expect(page.getByRole('list', { name: 'Applied filters' })).toContainText('Deleted records');

  await page.getByRole('button', { name: `Actions for ${name}` }).click();
  await page.getByRole('menuitem', { name: 'Restore' }).click();
  const restoreDialog = page.getByRole('dialog', { name: `Restore ${name}?` });
  await expect(restoreDialog).toContainText('Created by the end-to-end test');
  await restoreDialog.getByRole('button', { name: 'Restore employee' }).click();
  await expect(page.getByText(`${name} was restored.`)).toBeVisible();
});

test('validates each wizard step before moving on', async ({ page }) => {
  await signIn(page, '/employees/new');

  await page.getByRole('button', { name: 'Next' }).click();
  await expect(page.getByText('Enter a first name.')).toBeVisible();
  await expect(page.getByLabel('First name')).toBeFocused();
  await expect(page.getByRole('heading', { name: 'Personal information' })).toBeVisible();
});
