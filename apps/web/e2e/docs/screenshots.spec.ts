// page.evaluate() runs in the browser, so this file also needs the DOM types.
/// <reference lib="dom" />
import { fileURLToPath } from 'node:url';
import { expect, test, type Page } from '@playwright/test';
import { signIn, uniqueSuffix } from '../support';

/*
| Regenerates the screenshots in docs/screenshots from the running app:
|   DOCS_SCREENSHOTS=1 npm run docs:screenshots   (with E2E_EMAIL / E2E_PASSWORD)
| It creates one demo employee per run.
*/

const OUTPUT = fileURLToPath(new URL('../../../../docs/screenshots/', import.meta.url));
const MINIMAL_PDF = '%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n';

const TOAST_TIMEOUT = 15_000;

async function capture(page: Page, name: string): Promise<void> {
  await page.waitForLoadState('networkidle');
  // Settled styles: no half-finished colour transitions in the image.
  await page.addStyleTag({ content: '*, *::before, *::after { transition: none !important; animation: none !important; }' });
  // Toasts would cover the header; wait until they are gone.
  await expect(page.locator('[data-sonner-toast]')).toHaveCount(0, { timeout: TOAST_TIMEOUT });
  await page.screenshot({ path: `${OUTPUT}${name}.png` });
}

async function openTab(page: Page, name: string): Promise<void> {
  const tab = page.getByRole('tab', { name });
  await tab.click();
  await expect(tab).toHaveAttribute('aria-selected', 'true');
}

/** A 512 × 512 gradient portrait placeholder, drawn by the browser. */
async function portrait(page: Page): Promise<Buffer> {
  const dataUrl = await page.evaluate(() => {
    const canvas = document.createElement('canvas');
    canvas.width = 512;
    canvas.height = 512;
    const context = canvas.getContext('2d');

    if (!context) return '';
    const gradient = context.createLinearGradient(0, 0, 512, 512);
    gradient.addColorStop(0, '#6366f1');
    gradient.addColorStop(1, '#14b8a6');
    context.fillStyle = gradient;
    context.fillRect(0, 0, 512, 512);
    context.fillStyle = 'rgba(255,255,255,0.85)';
    context.beginPath();
    context.arc(256, 200, 90, 0, Math.PI * 2);
    context.fill();
    context.beginPath();
    context.ellipse(256, 470, 170, 150, 0, 0, Math.PI * 2);
    context.fill();

    return canvas.toDataURL('image/png');
  });

  return Buffer.from(dataUrl.split(',')[1] ?? '', 'base64');
}

test('employee module screenshots', async ({ page, browser }) => {
  test.setTimeout(240_000);
  const suffix = uniqueSuffix();
  const name = `Ishara Fernando`;

  // Directory with a selection
  await signIn(page, '/employees');
  await expect(page.getByRole('table')).toBeVisible();
  await page.getByRole('row').nth(1).getByRole('checkbox').check();
  await page.getByRole('row').nth(2).getByRole('checkbox').check();
  await capture(page, 'employee-directory');
  await page.getByRole('button', { name: 'Clear selection' }).click();

  // Advanced filters
  await page.getByRole('button', { name: /filters/i }).click();
  await page.getByLabel('Designation').fill('Engineer');
  await page.getByRole('checkbox', { name: 'Active' }).check();
  await expect(page.getByRole('checkbox', { name: 'Active' })).toBeChecked();
  await capture(page, 'employee-filters');
  await page.keyboard.press('Escape');

  // Create wizard
  await page.goto('/employees/new');
  await page.getByLabel('First name').fill('Ishara');
  await page.getByLabel('Last name').fill('Fernando');
  await page.getByLabel('Work e-mail').fill(`ishara.${suffix}@meridian.test`);
  await page.getByRole('button', { name: 'Next' }).click();
  await page.getByLabel('Designation').fill('Payroll Specialist');
  await page.getByLabel('Department').selectOption({ index: 1 });
  await expect(page.getByLabel('Designation')).toHaveValue('Payroll Specialist');
  await capture(page, 'employee-create-wizard');
  await page.getByRole('button', { name: 'Next' }).click();
  await expect(page.getByRole('heading', { name: 'Account' })).toBeVisible();
  await page.getByRole('button', { name: 'Next' }).click();
  await expect(page.getByRole('heading', { name: 'Review' })).toBeVisible();
  await capture(page, 'employee-create-review');
  await page.getByRole('button', { name: 'Create employee' }).click();
  await expect(page.getByRole('heading', { level: 1, name })).toBeVisible();
  const profileUrl = page.url();

  // History worth showing: a department change, a photo and a document
  await page.getByRole('link', { name: 'Edit' }).click();
  await page.getByLabel('Designation').fill('Senior Payroll Specialist');
  await page.getByLabel('Department').selectOption({ index: 2 });
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByRole('heading', { level: 1, name })).toBeVisible();

  await page.getByRole('button', { name: 'Add photo' }).click();
  await page.locator('input[type="file"]').setInputFiles({ name: 'portrait.png', mimeType: 'image/png', buffer: await portrait(page) });
  await page.getByLabel('Zoom').fill('1.4');
  await expect(page.getByRole('button', { name: /photo position/i })).toBeVisible();
  await capture(page, 'employee-photo-crop');
  await page.getByRole('button', { name: 'Save photo' }).click();
  await expect(page.getByRole('dialog')).toBeHidden();

  await openTab(page, 'Documents');
  await page.getByRole('button', { name: 'Upload document' }).click();
  await page.locator('input[type="file"]').setInputFiles({ name: 'employment-contract.pdf', mimeType: 'application/pdf', buffer: Buffer.from(MINIMAL_PDF) });
  await page.getByLabel('Title').fill('Employment contract');
  await page.getByRole('button', { name: 'Upload', exact: true }).click();
  await expect(page.getByRole('list', { name: 'Documents' })).toContainText('Employment contract');
  await capture(page, 'employee-documents');

  await openTab(page, 'Overview');
  await expect(page.getByText('Recent activity')).toBeVisible();
  await capture(page, 'employee-profile');
  await openTab(page, 'Audit history');
  await expect(page.getByRole('tabpanel').getByText('Department changed')).toBeVisible();
  await capture(page, 'employee-audit-history');

  // Optimistic concurrency: someone else saves while this form is open
  await page.goto(`${profileUrl}/edit`);
  await page.getByLabel('Preferred name').fill('Ishi');

  const other = await browser.newPage();
  await signIn(other, new URL(`${profileUrl}/edit`).pathname);
  await other.getByLabel('Designation').fill('Payroll Lead');
  await other.getByRole('button', { name: 'Save changes' }).click();
  await expect(other.getByRole('heading', { level: 1, name })).toBeVisible();
  await other.close();

  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByRole('dialog', { name: 'This employee was changed by someone else' })).toBeVisible();
  await page.getByRole('dialog').getByRole('table').scrollIntoViewIfNeeded();
  await capture(page, 'employee-edit-conflict');
  await page.getByRole('button', { name: 'Discard my changes' }).click();

  // Phone layout
  const phone = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await signIn(phone, '/employees');
  await expect(phone.getByRole('link', { name }).first()).toBeVisible();
  await capture(phone, 'employee-directory-mobile');
  await phone.close();
});
