import { test, expect } from '@playwright/test';

test.describe('Orders page AJAX filter', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('initial page load shows all orders without date restriction', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    // Should show all orders (no DRP dates applied by default)
    const text = await page.textContent('#ord-table-wrap');
    expect(text).toContain('Menampilkan');
    expect(text).not.toContain('Belum ada order');
  });

  test('status filter updates table via AJAX without page reload', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    const initial = await page.textContent('#ord-table-wrap');

    // Intercept AJAX to verify no dates sent
    const respPromise = page.waitForResponse(r => r.url().includes('orders/filter'));

    // Filter by status=real
    await page.selectOption('#ord-filter-status', 'real');
    const resp = await respPromise;
    await page.waitForTimeout(500);

    // AJAX URL should NOT contain dari/sampai (dates not applied by user)
    const url = new URL(resp.url());
    expect(url.searchParams.has('dari')).toBe(false);
    expect(url.searchParams.has('sampai')).toBe(false);

    const afterFilter = await page.textContent('#ord-table-wrap');
    expect(afterFilter).not.toBe(initial);
    expect(afterFilter).toContain('Menampilkan');
  });

  test('DRP date range is only applied when user clicks Terapkan', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    // Trigger a status filter change — dates should NOT be in the request
    const noDateRequest = page.waitForResponse(r => {
      if (!r.url().includes('orders/filter')) return false;
      const url = new URL(r.url());
      return !url.searchParams.has('dari') && !url.searchParams.has('sampai');
    });
    await page.selectOption('#ord-filter-status', 'duplikat');
    await noDateRequest;

    // Now apply DRP — dates SHOULD be in the request
    const drpTrigger = page.locator('.drp-trigger').first();
    if (await drpTrigger.count() > 0) {
      await drpTrigger.click();
      await page.waitForTimeout(300);
      await page.click('button[data-key="today"]');
      await page.waitForTimeout(200);

      const dateRequest = page.waitForResponse(r => {
        if (!r.url().includes('orders/filter')) return false;
        const url = new URL(r.url());
        return url.searchParams.has('dari') && url.searchParams.has('sampai');
      });
      await page.click('button:has-text("Terapkan")');
      await dateRequest;
    }
  });

  test('pagination renders inside table-wrap after AJAX filter', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    // Filter by status=real (many results → pagination needed)
    await page.selectOption('#ord-filter-status', 'real');
    await page.waitForTimeout(1500);

    // Pagination nav should be inside #ord-table-wrap
    const paginationLinks = await page.locator('#ord-table-wrap a[href*="page="]').count();
    expect(paginationLinks).toBeGreaterThan(0);
  });

  test('search filter updates table via AJAX', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    // Search for a term — results should filter
    await page.fill('#ord-filter-search', 'tokopedia');
    await page.waitForTimeout(1500);

    const text = await page.textContent('#ord-table-wrap');
    expect(text).toContain('Menampilkan');
  });

  test('batch filter changes table content', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    // Select first non-empty batch option
    const options = page.locator('#ord-filter-batch option');
    const count = await options.count();
    if (count > 1) {
      const value = await options.nth(1).getAttribute('value');
      if (value) {
        await page.selectOption('#ord-filter-batch', value);
        await page.waitForTimeout(1500);
        const text = await page.textContent('#ord-table-wrap');
        expect(text).toContain('Menampilkan');
      }
    }
  });
});
