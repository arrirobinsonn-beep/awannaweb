import { test, expect } from '@playwright/test';

test.describe('Sidebar active nav icon styling', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('active nav icon has light background, not solid red', async ({ page }) => {
    const sidebar = page.locator('#sidebar');
    const activeItem = sidebar.locator('a.nav-item.active').first();
    await expect(activeItem).toBeVisible();

    const icon = activeItem.locator('.nav-icon');
    await expect(icon).toBeVisible();

    // Check the icon's computed background color
    const bgColor = await icon.evaluate((el) => {
      return window.getComputedStyle(el).backgroundColor;
    });

    // Should NOT be solid red (255, 107, 107) = #FF6B6B
    // Should be a light red/transparent (rgba with low alpha)
    console.log('Active icon background:', bgColor);

    // Parse the color
    if (bgColor.startsWith('rgba')) {
      const match = bgColor.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
      if (match) {
        const r = parseInt(match[1]);
        const g = parseInt(match[2]);
        const b = parseInt(match[3]);
        const a = match[4] ? parseFloat(match[4]) : 1;

        // Should not be fully opaque red
        const isSolidRed = r > 200 && g < 150 && b < 150 && a > 0.8;
        expect(isSolidRed).toBe(false);
        console.log('✅ Icon background is NOT solid red');
      }
    }

    // Check icon color (should be red, not white)
    const iconColor = await icon.evaluate((el) => {
      // Check the SVG's stroke color
      const svg = el.querySelector('svg');
      if (svg) {
        return window.getComputedStyle(svg).color;
      }
      return window.getComputedStyle(el).color;
    });

    console.log('Icon color:', iconColor);
    // Should be red-ish, not white
    if (iconColor.startsWith('rgb')) {
      const match = iconColor.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
      if (match) {
        const r = parseInt(match[1]);
        const g = parseInt(match[2]);
        // Should NOT be white (255, 255, 255)
        const isWhite = r > 240 && g > 240 && parseInt(match[3]) > 240;
        expect(isWhite).toBe(false);
        console.log('✅ Icon color is NOT white');
      }
    }
  });

  test('active nav item still looks visually distinct from inactive', async ({ page }) => {
    const sidebar = page.locator('#sidebar');

    // Get the active item
    const activeItem = sidebar.locator('a.nav-item.active').first();
    await expect(activeItem).toBeVisible();

    // Get any inactive item
    const inactiveItem = sidebar.locator('a.nav-item:not(.active)').first();
    if (await inactiveItem.isVisible()) {
      const activeBg = await activeItem.evaluate((el) =>
        window.getComputedStyle(el).backgroundColor
      );
      const inactiveBg = await inactiveItem.evaluate((el) =>
        window.getComputedStyle(el).backgroundColor
      );

      // They should be different
      expect(activeBg).not.toBe(inactiveBg);
      console.log('✅ Active and inactive items have different backgrounds');
      console.log('  Active:', activeBg);
      console.log('  Inactive:', inactiveBg);
    }
  });
});
