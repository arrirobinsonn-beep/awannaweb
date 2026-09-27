import { test, expect } from '@playwright/test';

/**
 * Regresi halaman /export-mapping/create — tombol "➕ Tambah Kolom":
 * 1. Modal terbuka berisi form Header Template + select Sumber Isi yang
 *    OPsi-nya identik dengan dropdown di tabel mapping.
 * 2. Kolom baru masuk sebagai baris draft TANPA reload halaman
 *    (URL tetap + marker window tidak hilang + badge draft muncul).
 * 3. Header dobel (case/spasi berbeda) & "teks tetap" kosong ditolak.
 *
 * Halaman create tidak menyimpan apa pun ke DB (draft only, tombol Simpan
 * tidak ditekan) → tidak ada pembersihan data yang diperlukan.
 */

const SHOT_DIR = 'tests/e2e/screenshots';

test.describe('Tambah Kolom di /export-mapping', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('modal menampilkan form header + sumber isi, baris masuk draft tanpa reload', async ({ page }) => {
    await page.goto('/export-mapping/create');
    await page.waitForLoadState('networkidle');

    const urlBefore = page.url();
    const countBefore = parseInt((await page.locator('#em-count').textContent()) || '0', 10);

    // Marker window — kalau halaman di-reload, marker hilang.
    await page.evaluate(() => {
      (window as unknown as Record<string, unknown>).__emMarker = 'alive';
    });

    // ── Buka modal ─────────────────────────────────────────────
    await page.locator('#em-add-btn').click();
    const modal = page.locator('#em-add-modal');
    await expect(modal).toHaveClass(/active/);
    await expect(page.locator('#em-add-header')).toBeVisible();
    await expect(page.locator('#em-add-source')).toBeVisible();
    await page.screenshot({ path: `${SHOT_DIR}/export-mapping-modal.png` });

    // ── Isi form + submit ──────────────────────────────────────
    await page.fill('#em-add-header', 'Header E2E Baru');
    await page.selectOption('#em-add-source', 'column:customer_name');
    await page.locator('#em-add-save').click();

    // Modal tertutup, baris baru muncul — tanpa navigasi
    await expect(modal).not.toHaveClass(/active/);
    const row = page.locator('#em-body tr.em-row', { hasText: 'Header E2E Baru' });
    await expect(row).toHaveCount(1);
    await expect(row.locator('select')).toHaveValue('column:customer_name');

    // Hitungan & badge draft ikut ter-update
    const countAfter = parseInt((await page.locator('#em-count').textContent()) || '0', 10);
    expect(countAfter).toBe(countBefore + 1);
    await expect(page.locator('#em-draft')).toHaveClass(/show/);

    // ── Bukti TIDAK ada reload ─────────────────────────────────
    expect(page.url()).toBe(urlBefore);
    const marker = await page.evaluate(() => (window as unknown as Record<string, unknown>).__emMarker);
    expect(marker).toBe('alive');

    // ── Opsi select modal == opsi select baris tabel mapping ───
    const optValues = (sel: string) =>
      page.locator(sel).evaluateAll((els) => els.map((e) => (e as HTMLOptionElement).value));
    const modalOpts = await optValues('#em-add-source option');
    const rowOpts = await optValues('#em-body tr.em-row select >> nth=0 >> option');
    expect(modalOpts.length).toBeGreaterThan(1);
    expect(modalOpts).toEqual(rowOpts);

    await page.screenshot({ path: `${SHOT_DIR}/export-mapping-after-add.png` });
  });

  test('header dobel ditolak, teks tetap kosong ditolak', async ({ page }) => {
    await page.goto('/export-mapping/create');
    await page.waitForLoadState('networkidle');

    // Tambah kolom pertama dulu (draft)
    await page.locator('#em-add-btn').click();
    await page.fill('#em-add-header', 'Header Dabel');
    await page.locator('#em-add-save').click();
    await expect(page.locator('#em-add-modal')).not.toHaveClass(/active/);

    const countAfterFirst = parseInt((await page.locator('#em-count').textContent()) || '0', 10);

    // ── Coba header dobel (case + spasi beda) ──────────────────
    await page.locator('#em-add-btn').click();
    await page.fill('#em-add-header', '  header dabel  ');
    await page.locator('#em-add-save').click();

    const error = page.locator('#em-add-error');
    await expect(error).toBeVisible();
    await expect(error).toContainText('sudah dipakai');
    // Modal tetap terbuka, jumlah kolom tidak berubah
    await expect(page.locator('#em-add-modal')).toHaveClass(/active/);
    const countDup = parseInt((await page.locator('#em-count').textContent()) || '0', 10);
    expect(countDup).toBe(countAfterFirst);
    await page.screenshot({ path: `${SHOT_DIR}/export-mapping-duplicate-error.png` });

    // ── Teks tetap tanpa nilai ditolak ─────────────────────────
    await page.fill('#em-add-header', 'Catatan E2E');
    await page.selectOption('#em-add-source', 'static');
    await expect(page.locator('#em-add-static')).toHaveClass(/show/);
    await page.locator('#em-add-save').click();
    await expect(error).toBeVisible();
    await expect(error).toContainText('teks tetap');
    await expect(page.locator('#em-add-modal')).toHaveClass(/active/);

    // Isi nilainya → berhasil
    await page.fill('#em-add-static-input', 'nilai tetap e2e');
    await page.locator('#em-add-save').click();
    await expect(page.locator('#em-add-modal')).not.toHaveClass(/active/);

    const row = page.locator('#em-body tr.em-row', { hasText: 'Catatan E2E' });
    await expect(row).toHaveCount(1);
    await expect(row.locator('select')).toHaveValue('static');
    await expect(row.locator('.em-static input')).toHaveValue('nilai tetap e2e');
    const countFinal = parseInt((await page.locator('#em-count').textContent()) || '0', 10);
    expect(countFinal).toBe(countAfterFirst + 1);
  });
});
