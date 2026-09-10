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

    const text = await page.textContent('#ord-table-wrap');
    expect(text).toContain('Menampilkan');
    expect(text).not.toContain('Belum ada order');
  });

  test('status filter updates table via AJAX without page reload', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    const initial = await page.textContent('#ord-table-wrap');

    const respPromise = page.waitForResponse(r => r.url().includes('orders/filter'));

    await page.selectOption('#ord-filter-status', 'real');
    const resp = await respPromise;
    await page.waitForTimeout(500);

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

    const noDateRequest = page.waitForResponse(r => {
      if (!r.url().includes('orders/filter')) return false;
      const url = new URL(r.url());
      return !url.searchParams.has('dari') && !url.searchParams.has('sampai');
    });
    await page.selectOption('#ord-filter-status', 'duplikat');
    await noDateRequest;

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

    await page.selectOption('#ord-filter-status', 'real');
    await page.waitForTimeout(1500);

    const paginationLinks = await page.locator('#ord-table-wrap a[href*="page="]').count();
    expect(paginationLinks).toBeGreaterThan(0);
  });

  test('search filter updates table via AJAX', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    await page.fill('#ord-filter-search', 'tokopedia');
    await page.waitForTimeout(1500);

    const text = await page.textContent('#ord-table-wrap');
    expect(text).toContain('Menampilkan');
  });

  test('batch filter changes table content', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

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

  test('reset button navigates to /orders clean', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    // Apply a filter first
    await page.selectOption('#ord-filter-status', 'real');
    await page.waitForTimeout(1500);

    // Click reset
    await page.click('#ord-filter-reset');
    await page.waitForURL('**/orders');

    // URL should be clean /orders with no query params
    const url = new URL(page.url());
    expect(url.pathname).toBe('/orders');
    expect(url.searchParams.has('status')).toBe(false);
    expect(url.searchParams.has('courier')).toBe(false);
    expect(url.searchParams.has('batch')).toBe(false);

    // Table should show all orders
    const text = await page.textContent('#ord-table-wrap');
    expect(text).toContain('Menampilkan');
  });
});

test.describe('Orders filter endpoint redirects non-AJAX', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('direct access to /orders/filter redirects to /orders', async ({ page }) => {
    const response = await page.goto('/orders/filter?courier=spx&page=2');
    expect(response?.url()).toContain('/orders');
    expect(response?.url()).not.toContain('/orders/filter');
    await expect(page.locator('#ord-table-wrap')).toBeVisible();
  });

  test('direct access to /orders/filter preserves all query params', async ({ page }) => {
    const response = await page.goto('/orders/filter?courier=spx&status=real&page=1');
    const finalUrl = new URL(response?.url() || '');
    expect(finalUrl.pathname).toBe('/orders');
    expect(finalUrl.searchParams.get('courier')).toBe('spx');
    expect(finalUrl.searchParams.get('status')).toBe('real');
    expect(finalUrl.searchParams.get('page')).toBe('1');
  });

  test('pagination links in AJAX-loaded table use /orders path not /orders/filter', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    // Apply a filter to trigger AJAX load
    await page.selectOption('#ord-filter-status', 'real');
    await page.waitForTimeout(1500);

    // Check that pagination links point to /orders, not /orders/filter
    const paginationHrefs = await page.locator('#ord-table-wrap .pagination a').allInnerTexts();
    const paginationUrls = await page.locator('#ord-table-wrap .pagination a').evaluateAll(
      (links: HTMLAnchorElement[]) => links.map(l => l.href)
    );

    for (const url of paginationUrls) {
      expect(url).toContain('/orders?');
      expect(url).not.toContain('/orders/filter');
    }
  });

  test('clicking pagination after AJAX filter loads correct page without JSON response', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    // Apply filter
    await page.selectOption('#ord-filter-status', 'real');
    await page.waitForTimeout(1500);

    // Find pagination links
    const paginationLinks = page.locator('#ord-table-wrap .pagination a');
    const count = await paginationLinks.count();
    if (count > 0) {
      // Click the last pagination link (e.g. page 2 or higher)
      const targetLink = paginationLinks.last();
      const linkText = await targetLink.innerText();

      // Intercept the AJAX request
      const respPromise = page.waitForResponse(r => r.url().includes('orders/filter'));

      await targetLink.click();
      const resp = await respPromise;
      await page.waitForTimeout(1000);

      // Response should be JSON (AJAX), not a page navigation
      const contentType = resp.headers()['content-type'] || '';
      expect(contentType).toContain('application/json');

      // Table should still be visible (not navigated away)
      await expect(page.locator('#ord-table-wrap')).toBeVisible();

      // Content should have updated
      const text = await page.textContent('#ord-table-wrap');
      expect(text).toContain('Menampilkan');
    }
  });

  test('pagination with multiple filters preserves all params', async ({ page }) => {
    await page.goto('/orders');
    await page.waitForTimeout(2000);

    // Apply multiple filters
    await page.selectOption('#ord-filter-status', 'real');
    await page.waitForTimeout(500);

    const respPromise = page.waitForResponse(r => r.url().includes('orders/filter'));
    await page.selectOption('#ord-filter-courier', 'spx');
    const resp = await respPromise;
    await page.waitForTimeout(500);

    const url = new URL(resp.url());
    expect(url.searchParams.get('status')).toBe('real');
    expect(url.searchParams.get('courier')).toBe('spx');

    // Now click pagination — should preserve status=real AND courier=spx
    const paginationLinks = page.locator('#ord-table-wrap .pagination a');
    const count = await paginationLinks.count();
    if (count > 0) {
      const pageRespPromise = page.waitForResponse(r => r.url().includes('orders/filter'));
      await paginationLinks.first().click();
      const pageResp = await pageRespPromise;
      await page.waitForTimeout(1000);

      const pageUrl = new URL(pageResp.url());
      expect(pageUrl.searchParams.get('status')).toBe('real');
      expect(pageUrl.searchParams.get('courier')).toBe('spx');
      expect(pageUrl.searchParams.has('page')).toBe(true);
    }
  });
});
