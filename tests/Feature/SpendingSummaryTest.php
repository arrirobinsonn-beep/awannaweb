<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RegionalReport;
use App\Models\SpendingHarian;
use App\Models\User;
use App\Models\Whitelist;
use Tests\TestCase;

class SpendingSummaryTest extends TestCase
{
    private function makeUser(): User
    {
        return User::create([
            'nama' => 'User '.uniqid(),
            'email' => 'summary-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }

    private function makeWhitelist(User $owner): Whitelist
    {
        return Whitelist::create([
            'nama' => 'WL Test '.uniqid(),
            'kode' => 'WL-'.uniqid(),
            'platform' => 'facebook',
            'user_id' => $owner->id,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'aktif',
            'total_topup' => 0,
            'total_spending' => 0,
        ]);
    }

    private function makeProduct(string $adStatus = 'running'): Product
    {
        return Product::create([
            'code' => 'P'.strtoupper(substr(uniqid(), -6)),
            'name' => 'Produk Test '.$adStatus,
            'status' => 'active',
            'ad_status' => $adStatus,
        ]);
    }

    private function makeSpending(User $user, Whitelist $wl, Product $product, string $tanggal, float $spending, int $lead, int $paid): SpendingHarian
    {
        $data = [
            'tanggal' => $tanggal,
            'user_id' => $user->id,
            'whitelist_id' => $wl->id,
            'product_id' => $product->id,
            'spending' => $spending,
            'lead' => $lead,
            'paid' => $paid,
        ];
        SpendingHarian::computeMetrics($data);

        return SpendingHarian::create($data);
    }

    public function test_advertiser_sees_summary_cards_with_period_totals(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);
        $product = $this->makeProduct();

        // 2026-08-01: spending 100.000, lead 10, paid 4
        $this->makeSpending($adv, $wl, $product, '2026-08-01', 100000, 10, 4);
        // 2026-08-05: spending 50.000, lead 5, paid 1 → total periode = 150.000, 15 lead, 5 paid
        $this->makeSpending($adv, $wl, $product, '2026-08-05', 50000, 5, 1);

        $this->actingAs($adv)
            ->get(route('spending.index', ['dari' => '2026-08-01', 'sampai' => '2026-08-31']))
            ->assertOk()
            // Label kartu
            ->assertSee('Total Spending')
            ->assertSee('Total Lead / Paid')
            ->assertSee('CPA Lead / Paid')
            ->assertSee('Paid Ratio')
            // Nilai agregat periode: 100.000 + 50.000
            ->assertSee('Rp 150.000')
            // 15 lead / 5 paid
            ->assertSee('15')
            ->assertSee('5')
            // paid_ratio = 5/15 = 33,33 → 33%
            ->assertSee('33%');
    }

    public function test_summary_cards_follow_period_range(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);
        $product = $this->makeProduct();

        $this->makeSpending($adv, $wl, $product, '2026-08-01', 100000, 10, 4);
        $this->makeSpending($adv, $wl, $product, '2026-08-05', 50000, 5, 1);

        // Rentang sempit → hanya baris 2026-08-01 yang masuk
        $this->actingAs($adv)
            ->get(route('spending.index', ['dari' => '2026-08-01', 'sampai' => '2026-08-03']))
            ->assertOk()
            ->assertSee('Rp 100.000')
            ->assertDontSee('Rp 150.000')
            // 10 lead, 4 paid → ratio 40%
            ->assertSee('40%');

        // Data di luar rentang tidak bocor ke summary
        $this->actingAs($adv)
            ->get(route('spending.index', ['dari' => '2026-08-06', 'sampai' => '2026-08-31']))
            ->assertOk()
            ->assertSee('Rp 0');
    }

    public function test_testing_tab_counts_cpa_but_global_summary_only_running(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);

        // Produk Running: 100.000, 10 lead, 4 paid → CPA lead 10.000, CPA paid 25.000, ratio 40%
        $running = $this->makeProduct('running');
        $this->makeSpending($adv, $wl, $running, '2026-08-01', 100000, 10, 4);

        // Produk Testing: 60.000, 7 lead, 3 paid → CPA lead 8.571, CPA paid 20.000, ratio 43%
        $testing = $this->makeProduct('testing');
        $this->makeSpending($adv, $wl, $testing, '2026-08-01', 60000, 7, 3);

        $resp = $this->actingAs($adv)
            ->get(route('spending.index', ['dari' => '2026-08-01', 'sampai' => '2026-08-31']))
            ->assertOk();

        // ── Kartu summary GLOBAL hanya Running (testing TIDAK masuk) ──
        // Total Spending global = 100.000 (bukan 160.000 — testing tidak ikut)
        $resp->assertSee('Rp 100.000')
            ->assertDontSee('Rp 160.000');

        // ── Tab Testing tetap menghitung CPA sendiri (di halaman yang sama) ──
        $resp->assertSee('Rp 8.571')   // CPA Lead testing
            ->assertSee('Rp 20.000')   // CPA Paid testing
            ->assertSee('43%')         // Paid ratio testing
            ->assertSee('Rp 60.000')   // Spending testing
            // Struktur expand tab Testing ditiru dari tab Running (Level 2 produk):
            // id `tlvl2-{tanggal}-{produk}` hanya ada di tab Testing (Running pakai `lvl2-`)
            ->assertSee('tlvl2-20260801')
            // Kartu summary peka tab: data kedua tab di-render ke atribut data-*
            // (JS menukar tampilan saat tab berpindah)
            ->assertSee('data-run="Rp 100.000"', false)
            ->assertSee('data-test="Rp 60.000"', false)
            ->assertSee('data-run="40%"', false)
            ->assertSee('data-test="43%"', false)
            // Badge silang di kartu Paid Ratio dihapus (info tab tidak aktif tak lagi diperlukan)
            ->assertDontSee('sum-cross')
            // Chart kini 4 garis: Lead/Paid Running & Testing (data per status, per tanggal)
            ->assertSee('Lead (Running)', false)
            ->assertSee('Paid (Running)', false)
            ->assertSee('Lead (Testing)', false)
            ->assertSee('Paid (Testing)', false)
            ->assertSee('var runLead = [10]', false)
            ->assertSee('var runPaid = [4]', false)
            ->assertSee('var testLead = [7]', false)
            ->assertSee('var testPaid = [3]', false);
    }

    /**
     * Discrepancy Regional vs Spending harus SELARAS: regional_reports hanya memuat
     * produk RUNNING → sisi spending pembanding juga HANYA produk running.
     * Spending produk testing TIDAK boleh memicu alarm ketidaksesuaian.
     */
    public function test_discrepancy_ignores_testing_product_spending(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);

        $running = $this->makeProduct('running');
        $testing = $this->makeProduct('testing');

        // Spending tanggal sama: running 5 lead/2 paid + testing 5 lead/2 paid
        $this->makeSpending($adv, $wl, $running, '2026-08-01', 50000, 5, 2);
        $this->makeSpending($adv, $wl, $testing, '2026-08-01', 40000, 5, 2);

        // Regional HANYA running (konsisten dgn upload regional yg memfilter testing):
        // lead 5, paid 2 di JAWA BARAT
        RegionalReport::create([
            'user_id' => $adv->id,
            'tanggal' => '2026-08-01',
            'province' => 'JAWA BARAT',
            'lead' => 5,
            'paid' => 2,
            'paid_ratio' => 40.0,
        ]);

        // Selaras (5/2 vs 5/2) → TIDAK ada alarm di kedua halaman
        $this->actingAs($adv)
            ->get(route('spending.index', ['dari' => '2026-08-01', 'sampai' => '2026-08-31']))
            ->assertOk()
            ->assertDontSee('Ketidaksesuaian Data Ditemukan!');

        $this->actingAs($adv)
            ->get(route('regional.index', ['dari' => '2026-08-01', 'sampai' => '2026-08-31']))
            ->assertOk()
            ->assertDontSee('Ketidaksesuaian Data Ditemukan!');

        // Sanity check: bila REGIONAL tidak selaras (lead 6), alarm tetap muncul
        RegionalReport::where('user_id', $adv->id)
            ->where('tanggal', '2026-08-01')
            ->update(['lead' => 6]);

        $this->actingAs($adv)
            ->get(route('spending.index', ['dari' => '2026-08-01', 'sampai' => '2026-08-31']))
            ->assertOk()
            ->assertSee('Ketidaksesuaian Data Ditemukan!');
    }

    /**
     * Dua kelompok peringatan dalam SATU banner:
     * 1) Ketidaksesuaian Data — kedua sisi punya data tapi selisih.
     * 2) Data Belum Ditambahkan — regional ada datanya, spending kosong.
     */
    public function test_discrepancy_banner_separates_mismatch_from_missing_spending(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);
        $product = $this->makeProduct('running');

        // Tanggal 1: kedua sisi punya data tapi TIDAK selaras (5/2 vs 6/3)
        $this->makeSpending($adv, $wl, $product, '2026-08-01', 50000, 5, 2);
        RegionalReport::create([
            'user_id' => $adv->id,
            'tanggal' => '2026-08-01',
            'province' => 'JAWA BARAT',
            'lead' => 6,
            'paid' => 3,
            'paid_ratio' => 50.0,
        ]);

        // Tanggal 2: regional ADA, spending KOSONG → "Data Belum Ditambahkan"
        RegionalReport::create([
            'user_id' => $adv->id,
            'tanggal' => '2026-08-02',
            'province' => 'JAWA TENGAH',
            'lead' => 4,
            'paid' => 1,
            'paid_ratio' => 25.0,
        ]);

        $resp = $this->actingAs($adv)
            ->get(route('spending.index', ['dari' => '2026-08-01', 'sampai' => '2026-08-31']))
            ->assertOk();

        // ── Area 1: Ketidaksesuaian (tanggal 1) ──
        $resp->assertSee('Ketidaksesuaian Data Ditemukan!')
            ->assertSee('Regional: Lead 6, Paid 3 |')
            ->assertSee('Spending: Lead 5, Paid 2')
            ->assertSee('DATA TIDAK SESUAI');

        // ── Area 2: Data Belum Ditambahkan (tanggal 2 — spending kosong) ──
        $resp->assertSee('Data Belum Ditambahkan')
            ->assertSee('Anda belum mengisi data spending iklan tanggal 2 Agustus 2026');

        // ── Regional page menampilkan kelompok yang sama ──
        $this->actingAs($adv)
            ->get(route('regional.index', ['dari' => '2026-08-01', 'sampai' => '2026-08-31']))
            ->assertOk()
            ->assertSee('Ketidaksesuaian Data Ditemukan!')
            ->assertSee('Data Belum Ditambahkan')
            ->assertSee('Belum mengisi data spending iklan tanggal 2 Agustus 2026');
    }
}
