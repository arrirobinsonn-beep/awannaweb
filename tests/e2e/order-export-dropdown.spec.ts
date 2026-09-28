import { test, expect, Page } from '@playwright/test';

/**
 * Regresi dropdown export per courier di halaman Data Mentah (/orders):
 * isi dropdown = kolom `export_templates.couriers` (array, bisa diedit admin
 * di /export-mapping/{id}/edit) — BUKAN konstanta hardcoded FLIK_COURIERS
 * dan bukan hanya courier yang punya order.
 *
 * Bukti regresi: di DB dev template FLIK memuat 6 courier
 * (flix-tf, flix-idx, flix-sicepat, flix-spx, jne, jnt) — kode lama hanya
 * menampilkan FLIK_COURIERS yang punya data di batch itu.
 */

const SHOT_DIR = 'tests/e2e/screenshots';

async function login(page: Page): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'owner@awanna.id');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard**');
}

async function openFlikDropdown(page: Page) {
  await page.goto('/orders');
  await page.waitForLoadState('networkidle');
  const batchValue = await page.locator('#ord-filter-batch').locator('option').nth(1).getAttribute('value');
  expect(batchValue).toBeTruthy();
  await page.goto(`/orders?batch=${batchValue}`);
  await page.waitForLoadState('networkidle');

  const details = page.locator('details[data-couriers]', {
    has: page.locator('summary', { hasText: 'Export FLIK' }),
  });
  await expect(details).toHaveCount(1);
  await details.locator('summary').click();

  return details;
}

test.describe('Dropdown export FLIK mengikuti kolom couriers template', () => {
  test('opsi dropdown == array couriers template (tanpa hardcode, tetap tampil walau 0 data)', async ({ page }) => {
    await login(page);
    const details = await openFlikDropdown(page);

    const expected: string[] = JSON.parse((await details.getAttribute('data-couriers')) || '[]');
    expect(expected.length).toBeGreaterThan(1);

    const links = details.locator('a');
    await expect(links).toHaveCount(expected.length);

    const texts = (await links.allTextContents()).map((t) => t.trim());
    const hrefs = await links.evaluateAll((els) =>
      els.map((e) => (e as HTMLAnchorElement).getAttribute('href') || '')
    );

    expected.forEach((courier, i) => {
      // Label tiap opsi memuat courier persis dari array template + jumlah (bisa 0)
      expect(texts[i]).toContain(`— ${courier}`);
      expect(texts[i]).toMatch(/\(\d+\)$/);
      // URL export memakai courier yang sama
      expect(hrefs[i]).toContain(`/export/flik/${courier}`);
    });

    // Tidak ada opsi di luar array template (bukti tidak hardcode)
    const extra = texts.filter((t) => !expected.some((c) => t.includes(`— ${c}`)));
    expect(extra).toHaveLength(0);

    await page.screenshot({ path: `${SHOT_DIR}/orders-export-dropdown.png` });

    // Klik link pertama (via request, tetap memakai sesi login) → download sukses
    const resp = await page.request.get(hrefs[0]);
    expect(resp.status()).toBe(200);
  });
});
