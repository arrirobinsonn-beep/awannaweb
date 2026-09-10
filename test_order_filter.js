const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  
  // Login
  await page.goto('http://127.0.0.1:8000/login');
  await page.fill('input[type=email]', 'owner@awanna.id');
  await page.fill('input[type=password]', 'password');
  await page.click('button[type=submit]');
  await page.waitForURL('**/');
  
  // Go to orders page
  await page.goto('http://127.0.0.1:8000/orders');
  await page.waitForTimeout(2000);
  await page.screenshot({ path: '/tmp/orders-initial.png', fullPage: true });
  
  // Check how many orders initially
  const initialText = await page.textContent('#ord-table-wrap');
  console.log('Initial wrap length:', initialText.length);
  
  // Test: change status filter
  await page.selectOption('#ord-filter-status', 'real');
  await page.waitForTimeout(1500);
  await page.screenshot({ path: '/tmp/orders-filter-status.png', fullPage: true });
  
  const afterStatus = await page.textContent('#ord-table-wrap');
  console.log('After status filter wrap length:', afterStatus.length);
  console.log('Status filter changed content:', initialText !== afterStatus);
  
  // Check pagination inside table-wrap
  const paginationInWrap = await page.locator('#ord-table-wrap .pagination').count();
  console.log('Pagination elements in table-wrap:', paginationInWrap);
  
  // Test: search
  await page.selectOption('#ord-filter-status', '');
  await page.waitForTimeout(1000);
  await page.fill('#ord-filter-search', 'CBC');
  await page.waitForTimeout(1500);
  const afterSearch = await page.textContent('#ord-table-wrap');
  console.log('After search CBC wrap length:', afterSearch.length);
  console.log('Search filter changed content:', afterStatus !== afterSearch);
  
  await page.screenshot({ path: '/tmp/orders-filter-search.png', fullPage: true });
  
  await browser.close();
  console.log('DONE');
})();
