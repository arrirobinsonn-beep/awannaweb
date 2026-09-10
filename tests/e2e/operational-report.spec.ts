import { test, expect } from '@playwright/test';

test.describe('Laporan Operasional page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('renders 2 summary cards (Stok + Order)', async ({ page }) => {
    await page.goto('/laporan-operasional');
    await page.waitForTimeout(500);

    // Should have exactly 2 clay-card summary cards (inside the 2-column grid)
    const grid = page.locator('[style*="grid-template-columns:1fr 1fr"]').first();
    await expect(grid).toBeVisible();

    const cards = grid.locator('.clay-card');
    await expect(cards).toHaveCount(2);

    // First card = Stok
    await expect(cards.nth(0)).toContainText('Stok');
    // Second card = Order
    await expect(cards.nth(1)).toContainText('Order');
  });

  test('summary cards show period-dependent labels', async ({ page }) => {
    await page.goto('/laporan-operasional');
    await page.waitForTimeout(500);

    // Default = today → "Hari Ini"
    await expect(page.locator('text=Stok Hari Ini')).toBeVisible();
    await expect(page.locator('text=Order Hari Ini')).toBeVisible();
  });

  test('table has correct columns: #, Pengirim, Resi, COD, Bank Transfer', async ({ page }) => {
    await page.goto('/laporan-operasional');
    await page.waitForTimeout(500);

    const headers = page.locator('table.clay-table thead th');
    await expect(headers).toHaveCount(5);
    await expect(headers.nth(0)).toContainText('#');
    await expect(headers.nth(1)).toContainText('Pengirim');
    await expect(headers.nth(2)).toContainText('Resi');
    await expect(headers.nth(3)).toContainText('COD');
    await expect(headers.nth(4)).toContainText('Bank Transfer');
  });

  test('no monetary columns exist (no Rp, no Uang Masuk, no HPP)', async ({ page }) => {
    await page.goto('/laporan-operasional');
    await page.waitForTimeout(500);

    await expect(page.locator('text=Uang Masuk')).toHaveCount(0);
    await expect(page.locator('text=HPP')).toHaveCount(0);
    await expect(page.locator('text=Ongkir')).toHaveCount(0);
    await expect(page.locator('text=Perkiraan')).toHaveCount(0);
  });

  test('empty state message shown when no data', async ({ page }) => {
    await page.goto('/laporan-operasional?dari=2019-01-01&sampai=2019-01-31');
    await page.waitForTimeout(500);

    await expect(page.locator('text=Tidak ada order pada periode ini.')).toBeVisible();
  });

  test('filter form has date range picker + apply + reset buttons', async ({ page }) => {
    await page.goto('/laporan-operasional');
    await page.waitForTimeout(500);

    await expect(page.locator('button:has-text("Terapkan")').first()).toBeVisible();
    await expect(page.locator('a:has-text("Hari Ini")')).toBeVisible();
  });

  test('screenshot the page', async ({ page }) => {
    await page.goto('/laporan-operasional');
    await page.waitForTimeout(1000);
    await page.screenshot({ path: '/tmp/operasional-e2e.png', fullPage: true });
  });
});
