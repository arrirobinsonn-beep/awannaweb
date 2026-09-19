import { test, expect } from '@playwright/test';

/**
 * Regresi halaman /product:
 * 1. Setelah membuat SATU produk, produk berikutnya harus bisa langsung dibuat
 *    dari modal yang sama — TANPA hard refresh (Ctrl+Shift+R). Dulu tombol
 *    `#pm-save` tertinggal `disabled` + "Menyimpan..." setelah simpan sukses
 *    (label nya tetap "Simpan Produk"/"Simpan Varian", bukan "Simpan Lagi").
 * 2. Setelah menambah SATU varian, varian berikutnya juga harus bisa ditambah
 *    (tombol `#pv-save` ikut tertinggal disabled, dan baris varian harus tetap
 *    terbuka setelah tabel di-refresh AJAX).
 */
const stamp = Date.now().toString(36).toUpperCase();
const codeA = `E2E${stamp}A`;
const codeB = `E2E${stamp}B`;
const varA1 = `${codeA}V1`;
const varA2 = `${codeA}V2`;

test.describe('Tambah berulang di /product', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'owner@awanna.id');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
  });

  test('produk & varian kedua bisa ditambahkan tanpa hard refresh', async ({ page }) => {
    const logs: string[] = [];
    page.on('console', (m) => logs.push(`[console.${m.type()}] ${m.text()}`));
    page.on('pageerror', (e) => logs.push(`[pageerror] ${e.message}`));
    page.on('dialog', async (d) => {
      logs.push(`[dialog ${d.type()}] ${d.message()}`);
      if (d.type() === 'confirm') await d.accept();
      else await d.dismiss();
    });

    const tambahProduk = page.locator('#core-section button:has-text("Tambah Produk")').first();
    const pmSave = page.locator('#pm-save');
    const pvSave = page.locator('#pv-save');
    const coreTable = page.locator('#core-table-wrap');

    const isiProduk = async (kode: string, nama: string, harga: string) => {
      await page.fill('#pm-kode', kode);
      await page.fill('#pm-nama', nama);
      await page.fill('#pm-selling', harga);
    };

    try {
      await page.goto('/product');
      await page.waitForLoadState('networkidle');

      // ── 1. Produk pertama ─────────────────────────────────────────────
      await tambahProduk.click();
      await expect(page.locator('#modal-product')).toHaveClass(/active/);
      await isiProduk(codeA, 'E2E Produk A', '100000');
      await pmSave.click();
      await expect(coreTable).toContainText(codeA, { timeout: 10000 });
      logs.push(`[ok] produk 1 (${codeA}) tersimpan`);

      // ── 2. Produk kedua: modal dibuka lagi TANPA reload ───────────────
      await tambahProduk.click();
      await expect(page.locator('#modal-product')).toHaveClass(/active/);
      // Tombol harus siap pakai lagi (bukan disabled "Menyimpan...")
      await expect(pmSave).toBeEnabled();
      await expect(pmSave).toHaveText(/Simpan Produk/);
      await isiProduk(codeB, 'E2E Produk B', '120000');
      await pmSave.click();
      await expect(coreTable).toContainText(codeB, { timeout: 10000 });
      logs.push(`[ok] produk 2 (${codeB}) tersimpan tanpa refresh`);

      // ── 3. Varian pertama pada produk A ───────────────────────────────
      const rowA = coreTable.locator('tbody tr').filter({ hasText: codeA }).first();
      await rowA.locator('button:has-text("Varian")').click();

      const panelA = page.locator('#core-table-wrap tr[id^="pv-"]:visible').first();
      await expect(panelA).toContainText('Varian');

      await panelA.locator('button:has-text("Tambah Varian")').click();
      await expect(page.locator('#modal-variant')).toHaveClass(/active/);
      await page.fill('#pv-kode', varA1);
      await page.fill('#pv-nama', 'E2E Plus 1.00');
      await page.fill('#pv-power', '1');
      await pvSave.click();
      await expect(page.locator('#core-table-wrap')).toContainText(varA1, { timeout: 10000 });
      logs.push(`[ok] varian 1 (${varA1}) tersimpan`);

      // ── 4. Varian kedua: baris tetap terbuka, tombol siap pakai ───────
      const panelA2 = page.locator('#core-table-wrap tr[id^="pv-"]:visible').first();
      await panelA2.locator('button:has-text("Tambah Varian")').click();
      await expect(page.locator('#modal-variant')).toHaveClass(/active/);
      await expect(pvSave).toBeEnabled();
      await expect(pvSave).toHaveText(/Simpan Varian/);
      await page.fill('#pv-kode', varA2);
      await page.fill('#pv-nama', 'E2E Plus 1.25');
      await page.fill('#pv-power', '1.25');
      await pvSave.click();
      await expect(page.locator('#core-table-wrap')).toContainText(varA2, { timeout: 10000 });
      logs.push(`[ok] varian 2 (${varA2}) tersimpan tanpa refresh`);
    } finally {
      // Bersihkan produk uji (hard delete lewat UI) supaya DB dev tidak kotor.
      for (const code of [codeB, codeA]) {
        try {
          const row = page.locator('#core-table-wrap tbody tr').filter({ hasText: code }).first();
          if (await row.count() === 0) continue;
          await row.locator('button[title="Hapus produk"]').click();
          await expect(page.locator('#core-table-wrap')).not.toContainText(code, { timeout: 5000 });
          logs.push(`[cleanup] ${code} dihapus`);
        } catch (e) {
          logs.push(`[cleanup gagal] ${code}: ${(e as Error).message.split('\n')[0]}`);
        }
      }
      console.log(`--- jejak langkah (${codeA} / ${codeB}) ---\n${logs.join('\n')}`);
    }
  });
});
