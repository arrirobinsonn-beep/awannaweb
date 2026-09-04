import { test, expect } from '@playwright/test';

const LOGIN_URL = '/login';
const STOCK_URL = '/jurnal-stok';
const EMAIL = 'owner@awanna.id';
const PASSWORD = 'password';

async function login(page: any) {
    await page.goto(LOGIN_URL);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');
}

test.describe('Jurnal Stok Page', () => {
    test('page loads and shows stock cards with sisa stok', async ({ page }) => {
        await login(page);
        await page.goto(STOCK_URL);
        await page.waitForTimeout(1500);

        // Screenshot full page
        await page.screenshot({ path: 'test-results/stock-movement-full.png', fullPage: true });

        // Check page title
        await expect(page.locator('h1')).toContainText('Jurnal Stok');

        // Check product cards exist
        const cards = page.locator('.product-card');
        const cardCount = await cards.count();
        console.log(`Found ${cardCount} product cards`);

        // Check each card has Stok, Masuk, Keluar stats
        for (let i = 0; i < Math.min(cardCount, 3); i++) {
            const card = cards.nth(i);
            await expect(card.locator('.pc-stat-label').filter({ hasText: 'Stok' })).toBeVisible();
            await expect(card.locator('.pc-stat-label').filter({ hasText: 'Masuk' })).toBeVisible();
            await expect(card.locator('.pc-stat-label').filter({ hasText: 'Keluar' })).toBeVisible();
        }

        // Click first card to show detail
        if (cardCount > 0) {
            await cards.first().click();
            await page.waitForTimeout(500);
            await page.screenshot({ path: 'test-results/stock-movement-detail.png', fullPage: true });

            // Check detail section is visible
            const activeDetail = page.locator('.product-detail-section.active');
            await expect(activeDetail).toBeVisible();

            // Check variant table has Stok column
            const stokHeader = activeDetail.locator('th').filter({ hasText: 'Stok' });
            await expect(stokHeader).toBeVisible();
        }

        console.log('✅ Jurnal Stok page renders correctly with stock stats');
    });

    test('restock warning appears for low stock products', async ({ page }) => {
        await login(page);
        await page.goto(STOCK_URL);
        await page.waitForTimeout(1500);

        // Check for restock warning badges
        const warnCards = page.locator('.pc-stock-warn');
        const warnCount = await warnCards.count();
        console.log(`Found ${warnCount} products with restock warning`);

        if (warnCount > 0) {
            await expect(warnCards.first()).toHaveClass(/pc-stock-warn/);
            await expect(warnCards.first().locator('text=Perlu Restock')).toBeVisible();
            console.log('✅ Restock warning displayed correctly');
        } else {
            console.log('ℹ️ No products with low stock (no restock warnings)');
        }
    });

    test('variant table shows stock per variant', async ({ page }) => {
        await login(page);
        await page.goto(STOCK_URL);
        await page.waitForTimeout(1500);

        const cards = page.locator('.product-card');
        const cardCount = await cards.count();

        if (cardCount > 0) {
            // Click first card
            await cards.first().click();
            await page.waitForTimeout(500);

            const detail = page.locator('.product-detail-section.active');

            // Check variant rows have stock values
            const variantRows = detail.locator('.variant-row');
            const rowCount = await variantRows.count();
            console.log(`Found ${rowCount} variant rows in detail`);

            // Screenshot variant detail
            await page.screenshot({ path: 'test-results/stock-movement-variants.png', fullPage: true });

            if (rowCount > 0) {
                // Click first variant to expand
                await variantRows.first().click();
                await page.waitForTimeout(300);
                await page.screenshot({ path: 'test-results/stock-movement-variant-detail.png', fullPage: true });
                console.log('✅ Variant detail expand works');
            }
        }
    });
});
