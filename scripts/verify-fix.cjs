const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();
    
    // Login as admin/owner
    await page.goto('http://localhost:8000/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(1000);
    
    // Check /barang-masuk
    console.log('=== /barang-masuk ===');
    await page.goto('http://localhost:8000/barang-masuk');
    await page.waitForTimeout(1000);
    
    // Check page title
    const title = await page.textContent('h1, .page-title, h2');
    console.log('Page title:', title?.trim()?.substring(0, 50));
    
    // Check if there are any links/buttons pointing to /approval
    const approvalLinks = await page.$$eval('a[href*="approval"], form[action*="approval"]', els => els.map(e => ({
        tag: e.tagName,
        href: e.href || e.action,
        text: e.textContent?.trim()?.substring(0, 30)
    })));
    console.log('Links/forms pointing to /approval:', approvalLinks.length > 0 ? approvalLinks : 'NONE ✓');
    
    // Check verify button exists
    const verifyBtns = await page.$$('button:has-text("Verifikasi")');
    console.log('Verify buttons:', verifyBtns.length);
    
    // Check form action for verify
    if (verifyBtns.length > 0) {
        const action = await page.evaluate(() => {
            const form = document.getElementById('verifyForm');
            return form ? form.action : 'form not found';
        });
        console.log('Verify form action:', action);
    }
    
    // Check status filter options
    const statusOpts = await page.$$eval('select[name="status"] option', opts => opts.map(o => o.textContent?.trim()));
    console.log('Status filter options:', statusOpts);
    
    // Check table rows
    const rows = await page.$$('tbody tr');
    console.log('Table rows:', rows.length);
    
    // Take screenshot
    await page.screenshot({ path: 'screenshots/purchase-fixed.png', fullPage: true });
    console.log('Screenshot saved');
    
    await browser.close();
})();
