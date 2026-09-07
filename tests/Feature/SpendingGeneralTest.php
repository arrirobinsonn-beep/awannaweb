<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SpendingHarian;
use App\Models\User;
use App\Models\Whitelist;
use Tests\TestCase;

class SpendingGeneralTest extends TestCase
{
    private function makeUser(string $role, string $prefix = 'User'): User
    {
        $user = User::create([
            'nama' => $prefix.' '.uniqid(),
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeWhitelist(User $owner): Whitelist
    {
        return Whitelist::create([
            'nama' => 'WL '.$owner->nama.' '.uniqid(),
            'kode' => 'WL-'.uniqid(),
            'platform' => 'facebook',
            'user_id' => $owner->id,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'aktif',
            'total_topup' => 0,
            'total_spending' => 0,
            'nominal_terakhir_topup' => 0,
        ]);
    }

    private function makeProduct(string $adStatus = 'running'): Product
    {
        return Product::create([
            'code' => 'P'.strtoupper(substr(uniqid(), -6)),
            'name' => 'Produk Test '.uniqid(),
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

    public function test_admin_sees_summary_cards_chart_and_semua_spending_tab_with_combined_data(): void
    {
        $advA = $this->makeUser('advertiser', 'AdvA');
        $advB = $this->makeUser('advertiser', 'AdvB');
        $wlA = $this->makeWhitelist($advA);
        $wlB = $this->makeWhitelist($advB);
        $productA = $this->makeProduct();
        $productB = $this->makeProduct();

        // AdvA: 123.456 / 11 lead / 5 paid → ratio 45%
        $this->makeSpending($advA, $wlA, $productA, '2026-01-15', 123456, 11, 5);
        // AdvB: 98.765 / 7 lead / 2 paid → ratio 29%
        $this->makeSpending($advB, $wlB, $productB, '2026-01-15', 98765, 7, 2);

        $admin = $this->makeUser('super_admin', 'Admin');

        try {
            $resp = $this->actingAs($admin)
                ->get(route('spending.index', ['dari' => '2026-01-01', 'sampai' => '2026-01-31']))
                ->assertOk();

            // Tab paling kiri = "Semua spending" (carousel horizontal), plus chart & 4 kartu summary
            $resp->assertSee('📋 Semua spending', false)
                ->assertSee('tab-scroll')
                ->assertSee('Tren Lead & Paid', false)
                ->assertSee('spendingChartGeneral')
                ->assertSee('Total Spending', false)
                ->assertSee('Total Lead / Paid', false)
                ->assertSee('CPA Lead / Paid', false)
                ->assertSee('Paid Ratio', false);

            // Admin TIDAK punya akses pilih/bulk data & aksi setujui spending
            $resp->assertDontSee('bd-check')
                ->assertDontSee('Setujui spending ini');

            // Data gabungan kedua advertiser: 123.456 + 98.765 = 222.221; lead 18, paid 7 → ratio 39%
            $resp->assertSee('Rp 222.221')
                ->assertSee('39%');

            // Kedua advertiser tampil di tab & label pemilik di baris whitelist tabel "Semua"
            $resp->assertSee($advA->display_name)
                ->assertSee($advB->display_name)
                ->assertSee('👤 '.$advB->display_name, false);

            // Kedua whitelist tampil di tabel "Semua"
            $resp->assertSee($wlA->nama)
                ->assertSee($wlB->nama);

            // Banner alert TIDAK tampil di tab "Semua" (diganti warning merah di tab advertiser)
            $resp->assertDontSee('Ketidaksesuaian Data Ditemukan!');

            // Kedua advertiser punya spending tanpa regional → tab mereka ber-warning
            $resp->assertSee('Ada ketidaksesuaian data', false);
        } finally {
            SpendingHarian::whereIn('user_id', [$advA->id, $advB->id])->delete();
            Whitelist::whereIn('id', [$wlA->id, $wlB->id])->delete();
            User::whereIn('id', [$advA->id, $advB->id, $admin->id])->delete();
        }
    }

    public function test_advertiser_tab_shows_only_that_advertisers_data(): void
    {
        $advA = $this->makeUser('advertiser', 'AdvA');
        $advB = $this->makeUser('advertiser', 'AdvB');
        $wlA = $this->makeWhitelist($advA);
        $wlB = $this->makeWhitelist($advB);
        $productA = $this->makeProduct();
        $productB = $this->makeProduct();

        $this->makeSpending($advA, $wlA, $productA, '2026-01-15', 123456, 11, 5);
        $this->makeSpending($advB, $wlB, $productB, '2026-01-15', 98765, 7, 2);

        $admin = $this->makeUser('super_admin', 'Admin');

        try {
            $resp = $this->actingAs($admin)
                ->get(route('spending.index', ['dari' => '2026-01-01', 'sampai' => '2026-01-31', 'tab' => $advA->id]))
                ->assertOk();

            // Hanya data AdvA: 123.456, 11 lead, 5 paid → ratio 45%
            $resp->assertSee('Rp 123.456')
                ->assertSee('45%')
                ->assertDontSee('Rp 222.221')
                ->assertDontSee($wlB->nama)
                ->assertSee($wlA->nama);

            // Tab lain tetap ada di daftar tab; warning di tab tetap ada
            $resp->assertSee($advB->display_name)
                ->assertSee('Ada ketidaksesuaian data', false)
                ->assertDontSee('Ketidaksesuaian Data Ditemukan!');

            // Banner discrepancy TETAP tampil di tab advertiser yang datanya tidak sesuai
            // (AdvA punya spending tanpa regional → area "Data Belum Ditambahkan" muncul)
            $resp->assertSee('Data Belum Ditambahkan');
        } finally {
            SpendingHarian::whereIn('user_id', [$advA->id, $advB->id])->delete();
            Whitelist::whereIn('id', [$wlA->id, $wlB->id])->delete();
            User::whereIn('id', [$advA->id, $advB->id, $admin->id])->delete();
        }
    }

    public function test_general_table_has_running_testing_subtabs_with_split_data(): void
    {
        $advA = $this->makeUser('advertiser', 'AdvA');
        $advB = $this->makeUser('advertiser', 'AdvB');
        $wlA = $this->makeWhitelist($advA);
        $wlB = $this->makeWhitelist($advB);
        $productRunningA = $this->makeProduct();
        $productRunningB = $this->makeProduct();
        $productTestingA = $this->makeProduct('testing');

        // AdvA: running 100.000 (15 Jan) + testing 50.000 (16 Jan)
        $this->makeSpending($advA, $wlA, $productRunningA, '2026-01-15', 100000, 10, 5);
        $this->makeSpending($advA, $wlA, $productTestingA, '2026-01-16', 50000, 4, 1);
        // AdvB: running 200.000 (15 Jan)
        $this->makeSpending($advB, $wlB, $productRunningB, '2026-01-15', 200000, 7, 2);

        $admin = $this->makeUser('super_admin', 'Admin');

        try {
            $resp = $this->actingAs($admin)
                ->get(route('spending.index', ['dari' => '2026-01-01', 'sampai' => '2026-01-31']))
                ->assertOk();

            // Struktur sub-tab: strip dgn 2 tombol + 2 tabel (running tampil, testing disembunyikan)
            $resp->assertSee('table-subtabs', false)
                ->assertSee("switchSubTab('running')", false)
                ->assertSee("switchSubTab('testing')", false)
                ->assertSee('id="spending-general-running"', false)
                ->assertSee('id="spending-general-testing" style="display:none;"', false);

            // Tabel Running: hanya produk running (100.000 + 200.000 = 300.000 di 15 Jan)
            // Tabel Testing: hanya produk testing (50.000 di 16 Jan) dgn badge testing
            $resp->assertSee('Rp 300.000')
                ->assertSee('Rp 50.000')
                ->assertSee($productTestingA->name);

            // Kartu summary tetap gabungan running+testing (350.000)
            $resp->assertSee('Rp 350.000');
        } finally {
            SpendingHarian::whereIn('user_id', [$advA->id, $advB->id])->delete();
            Whitelist::whereIn('id', [$wlA->id, $wlB->id])->delete();
            User::whereIn('id', [$advA->id, $advB->id, $admin->id])->delete();
        }
    }

    public function test_advertiser_tab_subtabs_split_only_that_advertisers_products(): void
    {
        $advA = $this->makeUser('advertiser', 'AdvA');
        $advB = $this->makeUser('advertiser', 'AdvB');
        $wlA = $this->makeWhitelist($advA);
        $wlB = $this->makeWhitelist($advB);
        $productRunningA = $this->makeProduct();
        $productRunningB = $this->makeProduct();
        $productTestingA = $this->makeProduct('testing');

        // AdvA: running 100.000 + testing 50.000 (tanggal sama)
        $this->makeSpending($advA, $wlA, $productRunningA, '2026-01-15', 100000, 10, 5);
        $this->makeSpending($advA, $wlA, $productTestingA, '2026-01-15', 50000, 4, 1);
        // AdvB: running 200.000 (tanggal sama)
        $this->makeSpending($advB, $wlB, $productRunningB, '2026-01-15', 200000, 7, 2);

        $admin = $this->makeUser('super_admin', 'Admin');

        try {
            $resp = $this->actingAs($admin)
                ->get(route('spending.index', ['dari' => '2026-01-01', 'sampai' => '2026-01-31', 'tab' => $advA->id]))
                ->assertOk();

            // Tabel Running: hanya produk running milik AdvA (100.000)
            // Tabel Testing: hanya produk testing milik AdvA (50.000)
            $resp->assertSee('Rp 100.000')
                ->assertSee('Rp 50.000')
                ->assertSee($productTestingA->name)
                // Data AdvB tidak bocor ke tabel mana pun
                ->assertDontSee('Rp 200.000')
                ->assertDontSee($productRunningB->name);
        } finally {
            SpendingHarian::whereIn('user_id', [$advA->id, $advB->id])->delete();
            Whitelist::whereIn('id', [$wlA->id, $wlB->id])->delete();
            User::whereIn('id', [$advA->id, $advB->id, $admin->id])->delete();
        }
    }
}