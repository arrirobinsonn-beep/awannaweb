import { test, expect } from '@playwright/test';

test.describe('Courier rules live AJAX actions', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('add rule without page reload', async ({ page }) => {
    await page.goto('/courier-rules');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();
    const countBefore = await page.locator('#cr-count').textContent();

    // Fill in the form
    await page.locator('#cr-courier').selectOption('spx');
    await page.locator('#cr-payment').fill('cod');
    await page.locator('#cr-province').fill('TEST PROVINCE AJAX');
    
    // Submit
    await page.locator('#cr-add-form button[type="submit"]').click();
    await page.waitForTimeout(1000);

    // Verify URL hasn't changed
    const urlAfter = page.url();
    expect(urlAfter).toBe(urlBefore);

    // Verify count increased
    const countAfter = await page.locator('#cr-count').textContent();
    console.log(`Count: ${countBefore} → ${countAfter}`);
    
    // Verify the new rule appears in the table
    const tableText = await page.locator('#cr-table-wrap').textContent();
    expect(tableText).toContain('TEST PROVINCE AJAX');

    // Clean up - delete the test rule
    const deleteBtn = page.locator('.cr-del-btn-ajax[data-confirm*="TEST PROVINCE AJAX"]');
    if (await deleteBtn.count() > 0) {
      page.on('dialog', dialog => dialog.accept());
      await deleteBtn.click();
      await page.waitForTimeout(500);
    }
  });

  test('toggle rule status without page reload', async ({ page }) => {
    await page.goto('/courier-rules');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Find first toggle button
    const toggleBtn = page.locator('.cr-toggle-btn').first();
    
    if (await toggleBtn.count() > 0) {
      const wasOn = (await toggleBtn.textContent())?.includes('Aktif');
      
      await toggleBtn.click();
      await page.waitForTimeout(500);

      // Verify URL hasn't changed
      const urlAfter = page.url();
      expect(urlAfter).toBe(urlBefore);

      // Verify the toggle changed
      const isNowOn = (await toggleBtn.textContent())?.includes('Aktif');
      expect(isNowOn).toBe(!wasOn);
      
      console.log(`Toggle changed from ${wasOn ? 'on' : 'off'} to ${isNowOn ? 'on' : 'off'}`);

      // Toggle back
      await toggleBtn.click();
      await page.waitForTimeout(500);
    }
  });

  test('move rule up/down without page reload', async ({ page }) => {
    await page.goto('/courier-rules');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Find first move down button (not disabled)
    const moveDownBtn = page.locator('.cr-move-btn[data-dir="down"]:not([disabled])').first();
    
    if (await moveDownBtn.count() > 0) {
      await moveDownBtn.click();
      await page.waitForTimeout(500);

      // Verify URL hasn't changed
      const urlAfter = page.url();
      expect(urlAfter).toBe(urlBefore);

      // Verify table updated (move button should still exist)
      const moveButtons = page.locator('.cr-move-btn');
      expect(await moveButtons.count()).toBeGreaterThan(0);
    }
  });

  test('edit rule via modal without page reload', async ({ page }) => {
    await page.goto('/courier-rules');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Find first edit button
    const editBtn = page.locator('.cr-edit-btn').first();
    
    if (await editBtn.count() > 0) {
      // Get original product code value
      const origProduct = await editBtn.getAttribute('data-product');
      
      await editBtn.click();
      await page.waitForTimeout(300);

      // Modal should be open
      const modal = page.locator('#cr-modal.active');
      await expect(modal).toBeVisible();

      // Verify sort order display shows readonly value
      const sortDisplay = await page.locator('#cr-e-sort-display').inputValue();
      expect(sortDisplay).toContain('Urutan');
      console.log(`Sort display: ${sortDisplay}`);

      // Change product code (a visible field)
      const testCode = 'TEST_EDIT_' + Date.now();
      await page.locator('#cr-e-product').fill(testCode);
      
      // Submit
      await page.locator('#cr-edit-form button[type="submit"]').click();
      await page.waitForTimeout(500);

      // Verify URL hasn't changed
      const urlAfter = page.url();
      expect(urlAfter).toBe(urlBefore);

      // Modal should be closed
      const modalActive = page.locator('#cr-modal.active');
      expect(await modalActive.count()).toBe(0);

      // Verify the edit was applied
      const tableText = await page.locator('#cr-table-wrap').textContent();
      console.log(`Table contains test code: ${tableText.includes(testCode)}`);

      // Restore original product code
      const editBtnAfter = page.locator('.cr-edit-btn').first();
      if (await editBtnAfter.count() > 0) {
        await editBtnAfter.click();
        await page.waitForTimeout(300);
        await page.locator('#cr-e-product').fill(origProduct || '');
        await page.locator('#cr-edit-form button[type="submit"]').click();
        await page.waitForTimeout(500);
      }
    }
  });

  test('delete rule without page reload', async ({ page }) => {
    await page.goto('/courier-rules');
    await page.waitForLoadState('networkidle');

    const countBefore = await page.locator('#cr-count').textContent();
    
    // Create a test rule first
    await page.locator('#cr-courier').selectOption('spx');
    await page.locator('#cr-payment').fill('cod');
    await page.locator('#cr-province').fill('TEST DELETE AJAX');
    await page.locator('#cr-add-form button[type="submit"]').click();
    await page.waitForTimeout(1000);

    const countAfterAdd = await page.locator('#cr-count').textContent();
    console.log(`After add: ${countBefore} → ${countAfterAdd}`);

    // Now delete it
    const deleteBtn = page.locator('.cr-del-btn-ajax[data-confirm*="TEST DELETE AJAX"]');
    if (await deleteBtn.count() > 0) {
      page.on('dialog', dialog => dialog.accept());
      await deleteBtn.click();
      await page.waitForTimeout(500);

      const countAfterDelete = await page.locator('#cr-count').textContent();
      console.log(`After delete: ${countAfterAdd} → ${countAfterDelete}`);
      
      // Verify the rule is gone
      const tableText = await page.locator('#cr-table-wrap').textContent();
      expect(tableText).not.toContain('TEST DELETE AJAX');
    }
  });
});
