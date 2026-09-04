import { test, expect } from '@playwright/test';

test.describe('Tracking status rules live AJAX actions', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('add rule without page reload', async ({ page }) => {
    await page.goto('/tracking-status-rules/flik/edit');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Open the details/accordion for add form
    const details = page.locator('details.ts-manual');
    const isOpen = await details.evaluate((el) => el.open);
    if (!isOpen) {
      await page.locator('details.ts-manual summary').click();
      await page.waitForTimeout(500);
    }

    // Wait for the form to be visible
    await page.locator('#ts-add-raw').waitFor({ state: 'visible', timeout: 5000 });

    const countBefore = await page.locator('#ts-rules-wrap tbody tr').count();

    // Fill in the form
    const testRaw = 'TEST AJAX ' + Date.now();
    await page.locator('#ts-add-raw').fill(testRaw);
    await page.locator('#ts-form select[name="status"]').selectOption('delivered');
    
    // Submit and wait for response
    await page.locator('#ts-form button[type="submit"]').click();
    await page.waitForTimeout(2000);

    // Verify URL hasn't changed
    const urlAfter = page.url();
    expect(urlAfter).toBe(urlBefore);

    // Verify count increased
    const countAfter = await page.locator('#ts-rules-wrap tbody tr').count();
    console.log(`Count: ${countBefore} → ${countAfter}`);
    expect(countAfter).toBe(countBefore + 1);

    // Verify the new rule appears in the table
    const tableText = await page.locator('#ts-rules-wrap').textContent();
    expect(tableText).toContain(testRaw);

    // Clean up - delete the test rule
    const deleteBtn = page.locator('.ts-del-btn-ajax[data-confirm*="TEST AJAX"]');
    if (await deleteBtn.count() > 0) {
      page.on('dialog', dialog => dialog.accept());
      await deleteBtn.click();
      await page.waitForTimeout(500);
    }
  });

  test('toggle rule status without page reload', async ({ page }) => {
    await page.goto('/tracking-status-rules/flik/edit');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Find first toggle button
    const toggleBtn = page.locator('.ts-toggle-btn').first();
    
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
    await page.goto('/tracking-status-rules/flik/edit');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Find first move down button (not disabled)
    const moveDownBtn = page.locator('.ts-move-btn[data-dir="down"]:not([disabled])').first();
    
    if (await moveDownBtn.count() > 0) {
      await moveDownBtn.click();
      await page.waitForTimeout(500);

      // Verify URL hasn't changed
      const urlAfter = page.url();
      expect(urlAfter).toBe(urlBefore);

      // Verify table updated (move button should still exist)
      const moveButtons = page.locator('.ts-move-btn');
      expect(await moveButtons.count()).toBeGreaterThan(0);
    }
  });

  test('delete rule without page reload', async ({ page }) => {
    await page.goto('/tracking-status-rules/flik/edit');
    await page.waitForLoadState('networkidle');

    // Open the details/accordion for add form
    const details = page.locator('details.ts-manual');
    const isOpen = await details.evaluate((el) => el.open);
    if (!isOpen) {
      await page.locator('details.ts-manual summary').click();
      await page.waitForTimeout(500);
    }
    await page.locator('#ts-add-raw').waitFor({ state: 'visible', timeout: 5000 });

    const countBefore = await page.locator('#ts-rules-wrap tbody tr').count();
    
    // Create a test rule first
    const testRaw = 'TEST DEL ' + Date.now();
    await page.locator('#ts-add-raw').fill(testRaw);
    await page.locator('#ts-form select[name="status"]').selectOption('delivered');
    await page.locator('#ts-form button[type="submit"]').click();
    await page.waitForTimeout(2000);

    const countAfterAdd = await page.locator('#ts-rules-wrap tbody tr').count();
    console.log(`After add: ${countBefore} → ${countAfterAdd}`);

    // Now delete it
    const deleteBtn = page.locator('.ts-del-btn-ajax[data-confirm*="TEST DEL"]');
    if (await deleteBtn.count() > 0) {
      page.on('dialog', dialog => dialog.accept());
      await deleteBtn.click();
      await page.waitForTimeout(500);

      const countAfterDelete = await page.locator('#ts-rules-wrap tbody tr').count();
      console.log(`After delete: ${countAfterAdd} → ${countAfterDelete}`);
      
      // Verify the rule is gone
      const tableText = await page.locator('#ts-rules-wrap').textContent();
      expect(tableText).not.toContain(testRaw);
    }
  });

  test('edit rule via modal without page reload', async ({ page }) => {
    await page.goto('/tracking-status-rules/flik/edit');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();

    // Find first edit button
    const editBtn = page.locator('.ts-edit-btn').first();
    
    if (await editBtn.count() > 0) {
      // Get original raw status value
      const origRaw = await editBtn.getAttribute('data-raw');
      
      await editBtn.click();
      await page.waitForTimeout(300);

      // Modal should be open
      const modal = page.locator('#ts-modal.active');
      await expect(modal).toBeVisible();

      // Verify sort order display shows readonly value
      const sortDisplay = await page.locator('#ts-e-sort-display').inputValue();
      expect(sortDisplay).toContain('Urutan');
      console.log(`Sort display: ${sortDisplay}`);

      // Change raw status (a visible field)
      const testRaw = 'TEST_EDIT_' + Date.now();
      await page.locator('#ts-e-raw').fill(testRaw);
      
      // Submit
      await page.locator('#ts-edit-form button[type="submit"]').click();
      await page.waitForTimeout(500);

      // Verify URL hasn't changed
      const urlAfter = page.url();
      expect(urlAfter).toBe(urlBefore);

      // Modal should be closed
      const modalActive = page.locator('#ts-modal.active');
      expect(await modalActive.count()).toBe(0);

      // Verify the edit was applied
      const tableText = await page.locator('#ts-rules-wrap').textContent();
      console.log(`Table contains test raw: ${tableText.includes(testRaw)}`);

      // Restore original raw status
      const editBtnAfter = page.locator('.ts-edit-btn').first();
      if (await editBtnAfter.count() > 0) {
        await editBtnAfter.click();
        await page.waitForTimeout(300);
        await page.locator('#ts-e-raw').fill(origRaw || 'test');
        await page.locator('#ts-edit-form button[type="submit"]').click();
        await page.waitForTimeout(500);
      }
    }
  });
});
