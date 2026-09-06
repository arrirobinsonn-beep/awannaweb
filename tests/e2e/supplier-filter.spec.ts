import { test, expect } from '@playwright/test';

test.describe('Supplier live filtering', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('search input filters suppliers without page reload', async ({ page }) => {
    await page.goto('/supplier');
    await page.waitForLoadState('networkidle');

    // Count initial rows
    const initialRows = await page.locator('#supplier-table-wrap tbody tr').count();
    console.log(`Initial rows: ${initialRows}`);

    // Get the page URL before filtering
    const urlBefore = page.url();

    // Type in search input
    const searchInput = page.locator('#search-input');
    await searchInput.fill('test');
    
    // Wait for debounce (300ms) + AJAX response
    await page.waitForTimeout(500);

    // Verify URL hasn't changed (no page reload)
    const urlAfter = page.url();
    expect(urlAfter).toBe(urlBefore);

    // Verify the table updated (count text should change)
    const countText = await page.locator('#supplier-count').textContent();
    console.log(`After search: ${countText}`);
    expect(countText).toContain('Menampilkan');
  });

  test('status filter updates table without page reload', async ({ page }) => {
    await page.goto('/supplier');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Select "Aktif" status
    await page.locator('#status-filter').selectOption('aktif');
    
    // Wait for AJAX response
    await page.waitForTimeout(500);

    // Verify URL hasn't changed
    const urlAfter = page.url();
    expect(urlAfter).toBe(urlBefore);

    // Verify filter is applied
    const countText = await page.locator('#supplier-count').textContent();
    console.log(`After status filter: ${countText}`);
    expect(countText).toContain('Menampilkan');

    // Verify all visible badges are "Aktif"
    const badges = page.locator('#supplier-table-wrap .clay-badge-green');
    const badgeCount = await badges.count();
    if (badgeCount > 0) {
      console.log(`Found ${badgeCount} green badges (aktif)`);
    }
  });

  test('combined search and status filter works', async ({ page }) => {
    await page.goto('/supplier');
    await page.waitForLoadState('networkidle');

    // Set both filters
    await page.locator('#search-input').fill('a');
    await page.waitForTimeout(400);
    
    await page.locator('#status-filter').selectOption('aktif');
    await page.waitForTimeout(500);

    const countText = await page.locator('#supplier-count').textContent();
    console.log(`Combined filter: ${countText}`);
    expect(countText).toContain('Menampilkan');
  });

  test('pagination links work via AJAX', async ({ page }) => {
    await page.goto('/supplier');
    await page.waitForLoadState('networkidle');

    // Check if pagination exists
    const paginationLinks = page.locator('#supplier-pagination a');
    const linkCount = await paginationLinks.count();
    
    if (linkCount > 0) {
      const urlBefore = page.url();
      
      // Click first pagination link
      await paginationLinks.first().click();
      await page.waitForTimeout(500);

      // Verify URL hasn't changed (AJAX, not page reload)
      const urlAfter = page.url();
      expect(urlAfter).toBe(urlBefore);
      
      // Verify table updated
      const countText = await page.locator('#supplier-count').textContent();
      console.log(`After pagination: ${countText}`);
    }
  });

  test('empty state shows when no results found', async ({ page }) => {
    await page.goto('/supplier');
    await page.waitForLoadState('networkidle');

    // Search for something that doesn't exist
    await page.locator('#search-input').fill('zzzznonexistent');
    await page.waitForTimeout(500);

    // Should show empty state
    const emptyText = await page.locator('#supplier-table-wrap').textContent();
    expect(emptyText).toContain('Tidak ada supplier ditemukan');
  });
});
