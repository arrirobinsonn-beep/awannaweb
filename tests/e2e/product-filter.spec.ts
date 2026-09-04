import { test, expect } from '@playwright/test';

test.describe('Product live filtering', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('search input filters products without page reload', async ({ page }) => {
    await page.goto('/product');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Type in search input
    const searchInput = page.locator('#search-input');
    await searchInput.fill('kacamata');
    
    // Wait for debounce (300ms) + AJAX response
    await page.waitForTimeout(500);

    // Verify URL hasn't changed (no page reload)
    const urlAfter = page.url();
    expect(urlAfter).toBe(urlBefore);

    // Verify the count updated
    const countText = await page.locator('#product-count').textContent();
    console.log(`After search: ${countText}`);
    expect(countText).toContain('Menampilkan');
  });

  test('goods type filter updates table without page reload', async ({ page }) => {
    await page.goto('/product');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Select goods type
    await page.locator('#goods-type-filter').selectOption('core');
    await page.waitForTimeout(500);

    // Verify URL hasn't changed
    const urlAfter = page.url();
    expect(urlAfter).toBe(urlBefore);

    const countText = await page.locator('#product-count').textContent();
    console.log(`After goods type filter: ${countText}`);
    expect(countText).toContain('Menampilkan');
  });

  test('status filter updates table without page reload', async ({ page }) => {
    await page.goto('/product');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    await page.locator('#status-filter').selectOption('active');
    await page.waitForTimeout(500);

    const urlAfter = page.url();
    expect(urlAfter).toBe(urlBefore);

    const countText = await page.locator('#product-count').textContent();
    console.log(`After status filter: ${countText}`);
    expect(countText).toContain('Menampilkan');
  });

  test('ad status filter updates table without page reload', async ({ page }) => {
    await page.goto('/product');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    await page.locator('#ad-status-filter').selectOption('running');
    await page.waitForTimeout(500);

    const urlAfter = page.url();
    expect(urlAfter).toBe(urlBefore);

    const countText = await page.locator('#product-count').textContent();
    console.log(`After ad status filter: ${countText}`);
    expect(countText).toContain('Menampilkan');
  });

  test('combined filters work together', async ({ page }) => {
    await page.goto('/product');
    await page.waitForLoadState('networkidle');

    await page.locator('#search-input').fill('a');
    await page.waitForTimeout(400);
    
    await page.locator('#goods-type-filter').selectOption('core');
    await page.waitForTimeout(400);
    
    await page.locator('#status-filter').selectOption('active');
    await page.waitForTimeout(500);

    const countText = await page.locator('#product-count').textContent();
    console.log(`Combined filters: ${countText}`);
    expect(countText).toContain('Menampilkan');
  });

  test('toggle status does not reload page', async ({ page }) => {
    await page.goto('/product');
    await page.waitForLoadState('networkidle');

    // Find first toggle label (clicking the label triggers the checkbox)
    const firstToggleLabel = page.locator('#product-table-wrap .clay-toggle').first();
    const firstCheckbox = firstToggleLabel.locator('input[type="checkbox"]');
    
    if (await firstCheckbox.count() > 0) {
      const urlBefore = page.url();
      
      // Get initial checked state
      const wasChecked = await firstCheckbox.isChecked();
      
      // Click the label (which triggers the checkbox)
      await firstToggleLabel.click();
      
      // Wait for AJAX response
      await page.waitForTimeout(500);

      // Verify URL hasn't changed
      const urlAfter = page.url();
      expect(urlAfter).toBe(urlBefore);
      
      // Verify the checked state changed
      const isNowChecked = await firstCheckbox.isChecked();
      expect(isNowChecked).toBe(!wasChecked);
      
      console.log(`Toggle changed from ${wasChecked} to ${isNowChecked} without reload`);
    }
  });

  test('toggle ad status does not reload page', async ({ page }) => {
    await page.goto('/product');
    await page.waitForLoadState('networkidle');

    // Find ad status toggle label (second toggle in first data row)
    const firstDataRow = page.locator('#product-table-wrap tbody tr').first();
    const adToggleLabels = firstDataRow.locator('.clay-toggle');
    
    if (await adToggleLabels.count() > 1) {
      const adToggleLabel = adToggleLabels.nth(1);
      const adCheckbox = adToggleLabel.locator('input[type="checkbox"]');
      const urlBefore = page.url();
      const wasChecked = await adCheckbox.isChecked();
      
      await adToggleLabel.click();
      await page.waitForTimeout(500);

      const urlAfter = page.url();
      expect(urlAfter).toBe(urlBefore);
      
      const isNowChecked = await adCheckbox.isChecked();
      expect(isNowChecked).toBe(!wasChecked);
      
      console.log(`Ad toggle changed from ${wasChecked} to ${isNowChecked} without reload`);
    }
  });

  test('pagination works via AJAX', async ({ page }) => {
    await page.goto('/product');
    await page.waitForLoadState('networkidle');

    const paginationLinks = page.locator('#product-pagination a');
    const linkCount = await paginationLinks.count();
    
    if (linkCount > 0) {
      const urlBefore = page.url();
      
      await paginationLinks.first().click();
      await page.waitForTimeout(500);

      const urlAfter = page.url();
      expect(urlAfter).toBe(urlBefore);
      
      const countText = await page.locator('#product-count').textContent();
      console.log(`After pagination: ${countText}`);
    }
  });

  test('empty state shows when no results found', async ({ page }) => {
    await page.goto('/product');
    await page.waitForLoadState('networkidle');

    await page.locator('#search-input').fill('zzzznonexistent');
    await page.waitForTimeout(500);

    const emptyText = await page.locator('#product-table-wrap').textContent();
    expect(emptyText).toContain('Tidak ada produk ditemukan');
  });
});
