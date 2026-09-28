import { test, expect, Page } from '@playwright/test';

/**
 * Regresi aturan file export (split_mode) di /export-mapping:
 * 1. Form create/edit punya kontrol "Aturan File Export":
 *    Bentuk File (1 File / Dipisah per gudang) + pemisah (CSV / Aturan Gudang).
 * 2. Saat "1 File" dipilih, select pemisah disembunyikan & hidden split_mode=single.
 * 3. Nilai tersimpan ke DB dan tampil lagi saat halaman edit dibuka ulang.
 * 4. Bisa diubah ke mode lain lalu disimpan ulang (persist).
 * 5. Template uji dibuat lewat UI dan DIHAPUS di akhir (cleanup) — DB dev dipakai
 *    bersama, jangan tinggalkan template sampah.
 */

const SHOT_DIR = 'tests/e2e/screenshots';
const TPL_NAME = `E2E Split ${Date.now()}`;

async function login(page: Page): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'owner@awanna.id');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard**');
}

test.describe('Aturan File Export (split_mode) di /export-mapping', () => {
  test('create → split_rules tersimpan → edit persist → ubah ke 1 File → cleanup', async ({ page }) => {
    await login(page);

    // ── 1. Buat template baru dengan mode "Dipisah — aturan gudang" ──
    await page.goto('/export-mapping/create');
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="name"]', TPL_NAME);
    await page.fill('input[name="couriers"]', 'e2e-split-' + Date.now());

    // Minimal 1 kolom mapping via modal Tambah Kolom
    await page.locator('#em-add-btn').click();
    await page.fill('#em-add-header', 'Nama');
    await page.selectOption('#em-add-source', 'column:customer_name');
    await page.locator('#em-add-save').click();
    await expect(page.locator('#em-add-modal')).not.toHaveClass(/active/);

    // Kontrol aturan file export tampil di form create
    await expect(page.locator('#em-split-rule')).toBeVisible();
    await expect(page.locator('#em-split-shape')).toBeVisible();
    await expect(page.locator('#em-split-basis')).toBeHidden(); // default 1 File

    // Pilih "Dipisah per gudang" → pemisah muncul, pilih aturan gudang
    await page.selectOption('#em-split-shape', 'split');
    await expect(page.locator('#em-split-basis-wrap')).toBeVisible();
    await page.selectOption('#em-split-basis', 'split_rules');
    await expect(page.locator('#em-split-mode')).toHaveValue('split_rules');
    await page.screenshot({ path: `${SHOT_DIR}/export-mapping-split-rule.png` });

    // Simpan → redirect ke index
    await page.locator('#em-main-form button[type="submit"]').click();
    await page.waitForURL('**/export-mapping');

    const card = page.locator('.et-card', { hasText: TPL_NAME });
    await expect(card).toHaveCount(1);

    // ── 2. Edit → nilai persist ────────────────────────────────────
    await card.locator('a[href*="/edit"]').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#em-split-shape')).toHaveValue('split');
    await expect(page.locator('#em-split-basis-wrap')).toBeVisible();
    await expect(page.locator('#em-split-basis')).toHaveValue('split_rules');
    await expect(page.locator('#em-split-mode')).toHaveValue('split_rules');

    // ── 3. Ubah ke "1 File" → pemisah disembunyikan, hidden=single ─
    await page.selectOption('#em-split-shape', 'single');
    await expect(page.locator('#em-split-basis-wrap')).toBeHidden();
    await expect(page.locator('#em-split-mode')).toHaveValue('single');
    await page.screenshot({ path: `${SHOT_DIR}/export-mapping-split-single.png` });

    await page.locator('#em-main-form button[type="submit"]').click();
    await page.waitForURL('**/export-mapping');

    // ── 4. Buka lagi → mode "1 File" yang persist ─────────────────
    await page.locator('.et-card', { hasText: TPL_NAME }).locator('a[href*="/edit"]').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#em-split-shape')).toHaveValue('single');
    await expect(page.locator('#em-split-basis-wrap')).toBeHidden();
    await expect(page.locator('#em-split-mode')).toHaveValue('single');

    // Balikkan lagi ke split_csv → persist juga (mode ke-3 teruji lewat UI)
    await page.selectOption('#em-split-shape', 'split');
    await page.selectOption('#em-split-basis', 'split_csv');
    await page.locator('#em-main-form button[type="submit"]').click();
    await page.waitForURL('**/export-mapping');

    await page.locator('.et-card', { hasText: TPL_NAME }).locator('a[href*="/edit"]').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#em-split-mode')).toHaveValue('split_csv');

    // ── 5. Cleanup: hapus template uji ────────────────────────────
    page.on('dialog', (d) => d.accept());
    await page.goto('/export-mapping');
    const testCard = page.locator('.et-card', { hasText: TPL_NAME });
    await testCard.locator('button:has-text("Hapus")').click();
    await expect(testCard).toHaveCount(0);
  });
});
