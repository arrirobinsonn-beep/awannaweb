<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SpendingHarian;
use App\Models\User;
use App\Models\Whitelist;
use Tests\TestCase;

/**
 * Regresi alur "Upload Meta Ads → 💾 Simpan ke Server → konfirmasi 💾 Ya, Simpan"
 * di halaman spending sisi advertiser. Tiga penyebab data tidak pernah tersimpan:
 *
 * 1. CSS `.modal-regional` (dipakai #sp-confirm-modal) dulu berada DI DALAM blok
 *    "belum punya whitelist", sehingga advertiser yang SUDAH punya whitelist tidak
 *    mendapat CSS-nya → modal konfirmasi tampil polos & tertimbun di belakang modal upload.
 * 2. Binding tombol "💾 Ya, Simpan" dulu jalan langsung di blok skrip, padahal markup
 *    modal dikirim lewat stack "body-end" yang dirender SETELAH stack "scripts" →
 *    `getElementById` bernilai null dan handler tak pernah terpasang. Kini tombol
 *    memakai inline onclick ke fungsi global (tak tergantung urutan render).
 * 3. Server selalu MELEWATI baris yang sudah ada (dedupe), padahal modal menjanjikan
 *    "isian lama akan DIGANTI" → upload ulang tidak pernah mengubah data. Upload Excel
 *    kini mengirim `replace=1` → baris lama benar-benar diganti.
 *
 * Komentar yang memuat direktif Blade di dalam blok JS/CSS juga ikut dikompilasi Blade
 * (mis. "@push(...)" → startPush tanpa endpush) → output buffer rusak & isi <main> hilang.
 */
class SpendingSaveModalTest extends TestCase
{
    private function makeAdvertiser(): array
    {
        $adv = User::create([
            'nama' => 'User '.uniqid(),
            'email' => 'savereg-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
        $adv->assignRole('advertiser');

        $wl = Whitelist::create([
            'nama' => 'WL Test '.uniqid(),
            'kode' => 'WL-'.uniqid(),
            'platform' => 'facebook',
            'user_id' => $adv->id,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'aktif',
            'total_topup' => 0,
            'total_spending' => 0,
        ]);

        $product = Product::create([
            'code' => 'P'.strtoupper(substr(uniqid(), -6)),
            'name' => 'Produk Test running',
            'status' => 'active',
            'ad_status' => 'running',
            'start_testing' => '2026-01-01',
            'start_running' => '2026-01-01',
        ]);

        return [$adv, $wl, $product];
    }

    public function test_save_confirm_modal_is_available_and_clickable(): void
    {
        [$adv] = $this->makeAdvertiser();

        $html = $this->actingAs($adv)
            ->get(route('spending.index'))
            ->assertOk()
            ->getContent();

        // Modal konfirmasi simpan + CSS-nya WAJIB ada walau advertiser sudah punya whitelist.
        $this->assertStringContainsString('.modal-regional {', $html);
        $this->assertStringContainsString('id="sp-confirm-modal"', $html);

        // Tombol konfirmasi memakai inline onclick → selalu ter-bind walau markup
        // dikirim belakangan (stack body-end) setelah skrip (stack scripts).
        $this->assertStringContainsString('id="sp-confirm-yes" type="button" onclick="spConfirmSave()"', $html);
        $this->assertStringContainsString('window.spConfirmSave = function()', $html);

        // Notifikasi hasil simpan/gagal harus terlihat (showFlash pakai .sp-toast).
        $this->assertStringContainsString('.sp-toast {', $html);

        // Isi halaman utuh — tidak ada sisa kompilasi Blade yang bocor ke HTML.
        $this->assertStringContainsString('Total Spending', $html);
        $this->assertStringNotContainsString('startPush', $html);
        $this->assertStringNotContainsString('$__env->', $html);
    }

    public function test_upload_flow_replaces_existing_rows_with_replace_flag(): void
    {
        [$adv, $wl, $product] = $this->makeAdvertiser();

        $items = fn ($spending) => [[
            'tanggal' => '2026-08-01',
            'product_id' => $product->id,
            'whitelist_id' => $wl->id,
            'spending' => (string) $spending,
            'lead' => '3',
            'paid' => '1',
        ]];

        // 1) Simpan pertama → baris baru
        $this->actingAs($adv)
            ->post(route('spending.store'), ['items' => $items(100000)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['imported' => 1, 'updated' => 0, 'skipped' => 0]);

        // 2) Tanpa replace (form manual) → masih dilewati seperti sebelumnya
        $this->actingAs($adv)
            ->post(route('spending.store'), ['items' => $items(50000)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['imported' => 0, 'updated' => 0, 'skipped' => 1]);

        $this->assertEquals(100000.0, (float) SpendingHarian::where('user_id', $adv->id)
            ->where('tanggal', '2026-08-01')->value('spending'));

        // 3) Upload Excel (replace=1) → baris lama DIGANTI, tidak digandakan
        $this->actingAs($adv)
            ->post(route('spending.store'), ['replace' => '1', 'items' => $items(75000)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['imported' => 0, 'updated' => 1, 'skipped' => 0]);

        $rows = SpendingHarian::where('user_id', $adv->id)->where('tanggal', '2026-08-01')->get();
        $this->assertCount(1, $rows, 'Baris lama harus diganti, bukan digandakan.');
        $this->assertEquals(75000.0, (float) $rows->first()->spending);
    }
}
