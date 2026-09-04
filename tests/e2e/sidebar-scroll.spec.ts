import { test, expect } from '@playwright/test';

test.describe('Sidebar scroll position persistence', () => {
  test.beforeEach(async ({ page }) => {
    // Login as owner
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('sidebar scroll position is preserved after navigating to a different page', async ({ page }) => {
    const sidebar = page.locator('#sidebar');
    await expect(sidebar).toBeVisible();

    // Expand all nav groups to make sidebar content tall enough to scroll
    const groups = sidebar.locator('.nav-group');
    const groupCount = await groups.count();
    for (let i = 0; i < groupCount; i++) {
      const group = groups.nth(i);
      const isOpen = await group.evaluate((el) => el.classList.contains('open'));
      if (!isOpen) {
        await group.locator('.nav-group-header').click();
        await page.waitForTimeout(100);
      }
    }
    await page.waitForTimeout(300);

    const nav = sidebar.locator('nav');
    await expect(nav).toBeVisible();

    // Check if sidebar is scrollable
    const scrollHeight = await nav.evaluate((el) => el.scrollHeight);
    const clientHeight = await nav.evaluate((el) => el.clientHeight);
    console.log(`Nav scrollHeight: ${scrollHeight}, clientHeight: ${clientHeight}`);

    if (scrollHeight <= clientHeight) {
      // If still not scrollable (viewport too tall), use evaluate to set a max height on nav
      // to simulate a scrollable state, or skip
      console.log('Sidebar not scrollable at this viewport size, testing with forced scroll');
      await nav.evaluate((el) => { el.style.maxHeight = '200px'; });
      await page.waitForTimeout(200);
    }

    const finalScrollHeight = await nav.evaluate((el) => el.scrollHeight);
    const finalClientHeight = await nav.evaluate((el) => el.clientHeight);

    if (finalScrollHeight > finalClientHeight) {
      // Scroll to bottom-ish area
      const targetScroll = finalScrollHeight - finalClientHeight;
      await nav.evaluate((el, scroll) => { el.scrollTop = scroll; }, targetScroll);
      await page.waitForTimeout(300);

      const scrollBefore = await nav.evaluate((el) => el.scrollTop);
      console.log(`Scroll position before navigation: ${scrollBefore}`);
      expect(scrollBefore).toBeGreaterThan(0);

      // Find a visible nav-item link to click
      const navItems = nav.locator('a.nav-item');
      const count = await navItems.count();
      let clicked = false;

      for (let i = count - 1; i >= 0; i--) {
        const item = navItems.nth(i);
        if (await item.isVisible()) {
          const box = await item.boundingBox();
          const navBox = await nav.boundingBox();
          if (box && navBox &&
              box.y >= navBox.y &&
              box.y + box.height <= navBox.y + navBox.height) {
            const href = await item.getAttribute('href');
            console.log(`Clicking link: ${href}`);
            await item.click();
            clicked = true;
            break;
          }
        }
      }

      expect(clicked).toBeTruthy();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(800);

      const scrollAfter = await nav.evaluate((el) => el.scrollTop);
      console.log(`Scroll position after navigation: ${scrollAfter}`);

      // The scroll position should be preserved (within tolerance)
      expect(scrollAfter).toBeGreaterThan(0);
      expect(Math.abs(scrollAfter - scrollBefore)).toBeLessThan(150);
    }
  });

  test('sidebar scroll position is saved to localStorage on scroll', async ({ page }) => {
    const sidebar = page.locator('#sidebar');
    const nav = sidebar.locator('nav');

    // Expand all groups
    const groups = sidebar.locator('.nav-group');
    const groupCount = await groups.count();
    for (let i = 0; i < groupCount; i++) {
      const group = groups.nth(i);
      const isOpen = await group.evaluate((el) => el.classList.contains('open'));
      if (!isOpen) {
        await group.locator('.nav-group-header').click();
        await page.waitForTimeout(100);
      }
    }
    await page.waitForTimeout(300);

    // Force scrollable if needed
    const scrollHeight = await nav.evaluate((el) => el.scrollHeight);
    const clientHeight = await nav.evaluate((el) => el.clientHeight);
    if (scrollHeight <= clientHeight) {
      await nav.evaluate((el) => { el.style.maxHeight = '200px'; });
      await page.waitForTimeout(200);
    }

    // Scroll down
    await nav.evaluate((el) => { el.scrollTop = 150; });
    await page.waitForTimeout(300);

    // Verify localStorage has the scroll position saved
    const storedScroll = await page.evaluate(() => localStorage.getItem('wa_sidebar_scroll'));
    expect(storedScroll).not.toBeNull();
    expect(parseInt(storedScroll!, 10)).toBeGreaterThanOrEqual(140);
  });

  test('sidebar open/closed state is preserved across navigations', async ({ page }) => {
    const sidebar = page.locator('#sidebar');
    
    // Verify sidebar is open on desktop
    await expect(sidebar).toBeVisible();
    const stateBefore = await page.evaluate(() => localStorage.getItem('wa_sidebar'));
    
    // Click on a visible nav link (Dashboard is always visible)
    const dashboardLink = sidebar.locator('a.nav-item[href*="dashboard"]');
    await expect(dashboardLink).toBeVisible();
    await dashboardLink.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    // Check sidebar state is the same
    const stateAfter = await page.evaluate(() => localStorage.getItem('wa_sidebar'));
    expect(stateAfter).toBe(stateBefore);
    
    // Sidebar should still be visible
    await expect(sidebar).toBeVisible();
  });
});
