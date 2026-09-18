<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RegionalReport;
use App\Models\SpendingHarian;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RegionalImportTest extends TestCase
{
    private function makeUser(): User
    {
        return User::create([
            'nama' => 'User '.uniqid(),
            'email' => 'regional-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }

    private function makeProduct(string $adStatus): Product
    {
        $code = 'RG'.strtoupper(substr(uniqid(), -6));

        return Product::create([
            'code' => $code,
            'name' => 'Produk '.$adStatus.' Regional '.$code,
            'status' => 'active',
            'ad_status' => $adStatus,
            'start_testing' => '2026-01-01',
            'start_running' => $adStatus === 'running' ? '2026-01-01' : null,
        ]);
    }

    private function makeWhitelist(int $ownerId): Whitelist
    {
        return Whitelist::create([
            'nama' => 'WL Regional '.uniqid(),
            'kode' => 'WLR-'.uniqid(),
            'platform' => 'facebook',
            'user_id' => $ownerId,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'aktif',
            'total_topup' => 0,
            'total_spending' => 0,
        ]);
    }

    private function tempFile(string $name, string $content): UploadedFile
    {
        // Mime ditentukan dari ekstensi nama (.csv → text/csv) agar lolos validasi
        // mimetypes (file nyata di-sniff finfo → text/plain).
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function csvRegional(Product $running, Product $testing): string
    {
        return "province,product,payment_status,created_at\n"
            ."JAWA BARAT,A.1 - {$running->name} - WL1,paid,2026-08-01\n"
            ."JAWA BARAT,A.1 - {$running->name} - WL1,unpaid,2026-08-01\n"
            ."JAWA BARAT,A.1 - {$testing->name} - WL1,paid,2026-08-01\n"
            ."JAWA BARAT,A.1 - {$testing->name} - WL1,unpaid,2026-08-01\n";
    }

    /**
     * File regional yang sama dengan file spending: kolom product memuat nama produk.
     * Lead/paid produk TESTING harus dilewati — tabel hanya menampilkan produk Running.
     */
    public function test_preview_excludes_testing_products_lead_paid(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');
        $running = $this->makeProduct('running');
        $testing = $this->makeProduct('testing');

        try {
            $resp = $this->actingAs($user)
                ->postJson(route('regional.preview'), [
                    'file' => $this->tempFile('regional.csv', $this->csvRegional($running, $testing)),
                ]);

            $resp->assertOk()->assertJson(['success' => true]);

            // 4 baris mentah, tapi hanya 2 milik produk Running yang dihitung
            $this->assertSame(4, $resp->json('total_raw_rows'));
            $this->assertSame(2, $resp->json('skipped_testing'));

            $data = $resp->json('data');
            $this->assertSame(2, $data['total_lead'], 'Lead hanya dari produk Running');
            $this->assertSame(1, $data['total_paid'], 'Paid hanya dari produk Running');

            $prov = collect($data['by_date']['2026-08-01'] ?? [])->firstWhere('province', 'JAWA BARAT');
            $this->assertNotNull($prov, 'Provinsi JAWA BARAT harus ada di preview');
            $this->assertSame(2, $prov['lead']);
            $this->assertSame(1, $prov['paid']);
        } finally {
            $running->delete();
            $testing->delete();
        }
    }

    /**
     * Save dari preview: regional_reports tersimpan HANYA lead/paid produk Running.
     */
    public function test_save_stores_only_running_lead_paid(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');
        $running = $this->makeProduct('running');
        $testing = $this->makeProduct('testing');

        try {
            $preview = $this->actingAs($user)
                ->postJson(route('regional.preview'), [
                    'file' => $this->tempFile('regional.csv', $this->csvRegional($running, $testing)),
                ])
                ->assertOk()
                ->json('data');

            // Tiru JS preview: hanya kirim baris yang punya lead/paid > 0
            $items = [];
            foreach ($preview['by_date'] as $tgl => $rows) {
                foreach ($rows as $r) {
                    if (($r['lead'] ?? 0) > 0 || ($r['paid'] ?? 0) > 0) {
                        $items[] = [
                            'tanggal' => $tgl,
                            'province' => $r['province'],
                            'lead' => $r['lead'],
                            'paid' => $r['paid'],
                        ];
                    }
                }
            }

            $this->actingAs($user)
                ->postJson(route('regional.save'), ['items' => $items])
                ->assertOk()
                ->assertJson(['success' => true]);

            $report = RegionalReport::where('user_id', $user->id)
                ->where('province', 'JAWA BARAT')
                ->whereDate('tanggal', '2026-08-01')
                ->first();

            $this->assertNotNull($report, 'RegionalReport JAWA BARAT 2026-08-01 harus ada');
            $this->assertSame(2, (int) $report->lead, 'Lead tersimpan hanya dari produk Running');
            $this->assertSame(1, (int) $report->paid);
        } finally {
            RegionalReport::where('user_id', $user->id)->delete();
            $running->delete();
            $testing->delete();
        }
    }

    /**
     * Upload ulang tanggal yang SUDAH ada datanya → baris lama di-UPDATE (diganti),
     * bukan membuat baris baru apalagi pindah ke tanggal lain.
     */
    public function test_save_updates_existing_date_rows_instead_of_creating_new(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');
        $running = $this->makeProduct('running');

        // Data lama di tanggal 15-08-2026 (sudah ada isian, seperti kasus user)
        RegionalReport::create([
            'tanggal' => '2026-08-15',
            'user_id' => $user->id,
            'province' => 'JAWA BARAT',
            'lead' => 1,
            'paid' => 0,
            'paid_ratio' => 0,
        ]);

        try {
            $csv = "province,product,payment_status,created_at\n"
                ."JAWA BARAT,A.1 - {$running->name} - WL1,paid,15-08-2026 - 10:00\n"
                ."JAWA BARAT,A.1 - {$running->name} - WL1,unpaid,15-08-2026 - 11:00\n";

            $preview = $this->actingAs($user)
                ->postJson(route('regional.preview'), [
                    'file' => $this->tempFile('regional.csv', $csv),
                ])
                ->assertOk()
                ->json('data');

            $this->assertArrayHasKey('2026-08-15', $preview['by_date'], 'Preview harus memuat tanggal 15-08-2026');
            $this->assertArrayNotHasKey('2026-08-16', $preview['by_date'], 'Tidak boleh ada tanggal lain');

            // Tiru JS preview: hanya kirim baris yang punya lead/paid > 0
            // (preview menambahkan 34 provinsi pengisi 0 — itu tidak dikirim).
            $items = [];
            foreach ($preview['by_date'] as $tgl => $rows) {
                foreach ($rows as $r) {
                    if (($r['lead'] ?? 0) > 0 || ($r['paid'] ?? 0) > 0) {
                        $items[] = [
                            'tanggal' => $tgl,
                            'province' => $r['province'],
                            'lead' => $r['lead'],
                            'paid' => $r['paid'],
                        ];
                    }
                }
            }

            $save = $this->actingAs($user)
                ->postJson(route('regional.save'), ['items' => $items])
                ->assertOk()
                ->assertJson(['success' => true]);

            $this->assertSame(0, $save->json('imported'), 'Tidak boleh ada insert baru');
            $this->assertSame(1, $save->json('updated'), 'Baris lama harus di-update');

            $count = RegionalReport::where('user_id', $user->id)
                ->where('province', 'JAWA BARAT')
                ->whereDate('tanggal', '2026-08-15')
                ->count();
            $this->assertSame(1, $count, 'Harus tetap 1 baris (tidak dobel)');

            $report = RegionalReport::where('user_id', $user->id)
                ->where('province', 'JAWA BARAT')
                ->whereDate('tanggal', '2026-08-15')
                ->first();
            $this->assertSame(2, (int) $report->lead, 'Lead harus diganti dengan data terbaru');
            $this->assertSame(1, (int) $report->paid);

            // Tidak ada baris nyasar ke tanggal lain
            $stray = RegionalReport::where('user_id', $user->id)
                ->whereDate('tanggal', '!=', '2026-08-15')
                ->count();
            $this->assertSame(0, $stray, 'Tidak boleh ada data tersimpan di tanggal lain');
        } finally {
            RegionalReport::where('user_id', $user->id)->delete();
            $running->delete();
        }
    }

    /**
     * File TANPA kolom product → semua baris dihitung (perilaku lama, backward-compatible).
     */
    public function test_preview_without_product_column_counts_all_rows(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');

        $csv = "province,payment_status,created_at\n"
            ."JAWA BARAT,paid,2026-08-01\n"
            ."JAWA BARAT,unpaid,2026-08-01\n";

        $resp = $this->actingAs($user)
            ->postJson(route('regional.preview'), [
                'file' => $this->tempFile('regional.csv', $csv),
            ])
            ->assertOk();

        $this->assertSame(0, $resp->json('skipped_testing'));
        $this->assertSame(2, $resp->json('data.total_lead'));
        $this->assertSame(1, $resp->json('data.total_paid'));
    }

    /**
     * Produk sudah running SEKARANG tapi baru running sejak 2 Sep → baris file
     * yang tanggalnya masih masa testing (1 Sep) tetap dilewati, bukan dihitung
     * karena status produk saat ini running.
     */
    public function test_regional_classifies_by_phase_date_not_current_status(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');

        $product = Product::create([
            'code' => 'RG'.strtoupper(substr(uniqid(), -6)),
            'name' => 'Produk Fase Regional '.uniqid(),
            'status' => 'active',
            'ad_status' => 'running',
            'start_testing' => '2026-08-01',
            'start_running' => '2026-09-02',
        ]);

        $csv = "province,product,payment_status,created_at\n"
            ."JAWA BARAT,A.1 - {$product->name} - WL1,paid,2026-09-01\n"  // masa testing → dilewati
            ."JAWA BARAT,A.1 - {$product->name} - WL1,paid,2026-09-03\n"; // masa running → dihitung

        try {
            $resp = $this->actingAs($user)
                ->postJson(route('regional.preview'), [
                    'file' => $this->tempFile('regional_fase_'.uniqid().'.csv', $csv),
                ]);

            $resp->assertOk()->assertJson(['success' => true]);
            $this->assertSame(2, $resp->json('total_raw_rows'));
            $this->assertSame(1, $resp->json('skipped_testing'));

            $data = $resp->json('data');
            $this->assertSame(1, $data['total_lead'], 'Hanya baris masa running yang dihitung');
            $this->assertArrayHasKey('2026-09-03', $data['by_date']);
            $this->assertArrayNotHasKey('2026-09-01', $data['by_date']);
        } finally {
            $product->delete();
        }
    }

    /**
     * REGRESI (18 Sep 2026): baris tanggal EXACT sama dgn start_running (hari H
     * produk mulai running) harus dihitung RUNNING — konsisten dgn halaman
     * Spending (phaseOn memakai toDateString di kedua sisi).
     *
     * Bug lama: pluck('start_running') mengembalikan objek Carbon, sehingga
     * `$tanggal >= $runningStart` membandingkan string 'Y-m-d' vs 'Y-m-d 00:00:00'
     * → string lebih pendek (prefix) dianggap LEBIH KECIL → baris di hari H
     * dianggap testing dan lead/paid-nya hilang dari detail per daerah.
     */
    public function test_row_on_exact_start_running_date_counts_as_running(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');

        $product = Product::create([
            'code' => 'RG'.strtoupper(substr(uniqid(), -6)),
            'name' => 'Produk Exact H Regional '.uniqid(),
            'status' => 'active',
            'ad_status' => 'running',
            'start_testing' => '2026-08-01',
            'start_running' => '2026-09-04',
        ]);

        $csv = "province,product,payment_status,created_at\n"
            ."JAWA BARAT,A.1 - {$product->name} - WL1,paid,2026-09-04\n"  // H → running
            ."JAWA BARAT,A.1 - {$product->name} - WL1,paid,2026-09-05\n"; // H+1 → running

        try {
            $resp = $this->actingAs($user)
                ->postJson(route('regional.preview'), [
                    'file' => $this->tempFile('regional_exact_'.uniqid().'.csv', $csv),
                ]);

            $resp->assertOk()->assertJson(['success' => true]);
            $this->assertSame(2, $resp->json('total_raw_rows'));
            $this->assertSame(0, $resp->json('skipped_testing'), 'Baris di hari H (exact start_running) tidak boleh dianggap testing');

            $data = $resp->json('data');
            $this->assertSame(2, $data['total_lead']);
            $this->assertSame(2, $data['total_paid']);
            $this->assertArrayHasKey('2026-09-04', $data['by_date']);
        } finally {
            $product->delete();
        }
    }

    /**
     * DUAL FASE (18 Sep): baris produk TESTING TIDAK dibuang — masuk grup
     * preview terpisah (by_date_testing) dan tersimpan ke regional_reports
     * dgn ad_phase='testing'. Regional running TIDAK tersentuh testing.
     */
    public function test_testing_rows_save_to_ad_phase_testing(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');
        $running = $this->makeProduct('running');
        $testing = $this->makeProduct('testing');

        try {
            $preview = $this->actingAs($user)
                ->postJson(route('regional.preview'), [
                    'file' => $this->tempFile('regional.csv', $this->csvRegional($running, $testing)),
                ])
                ->assertOk()
                ->json('data');

            // Grup preview terpisah: running berisi 2 baris, testing 2 baris
            $this->assertSame(2, $preview['total_lead']);
            $this->assertSame(2, $preview['total_testing_lead']);
            $this->assertNotEmpty($preview['by_date_testing']['2026-08-01'] ?? []);

            // Tiru JS: kirim items + ad_phase dari data-phase tabel preview
            $items = [];
            foreach (['by_date' => 'running', 'by_date_testing' => 'testing'] as $key => $phase) {
                foreach ($preview[$key] as $tgl => $rows) {
                    foreach ($rows as $r) {
                        if (($r['lead'] ?? 0) > 0 || ($r['paid'] ?? 0) > 0) {
                            $items[] = [
                                'tanggal' => $tgl,
                                'province' => $r['province'],
                                'lead' => $r['lead'],
                                'paid' => $r['paid'],
                                'ad_phase' => $phase,
                            ];
                        }
                    }
                }
            }

            $this->actingAs($user)
                ->postJson(route('regional.save'), ['items' => $items])
                ->assertOk()
                ->assertJson(['success' => true]);

            // 2 baris terpisah per (tanggal, province): running & testing
            $this->assertSame(2, RegionalReport::where('user_id', $user->id)
                ->whereDate('tanggal', '2026-08-01')->count());

            $runRow = RegionalReport::where('user_id', $user->id)
                ->where('ad_phase', 'running')->whereDate('tanggal', '2026-08-01')->first();
            $testRow = RegionalReport::where('user_id', $user->id)
                ->where('ad_phase', 'testing')->whereDate('tanggal', '2026-08-01')->first();

            $this->assertNotNull($runRow);
            $this->assertNotNull($testRow, 'Baris testing harus tersimpan dgn ad_phase testing');
            $this->assertSame(2, (int) $runRow->lead);
            $this->assertSame(2, (int) $testRow->lead, 'Lead testing tersimpan terpisah');
            $this->assertSame(1, (int) $runRow->paid);
            $this->assertSame(1, (int) $testRow->paid);

            // Halaman Detail Per Daerah merender 2 matriks (Running + Testing)
            // + banner ketidaksesuaian TESTING (regional testing ada, spending kosong)
            $this->actingAs($user)
                ->get(route('regional.index', ['dari' => '2026-08-01', 'sampai' => '2026-08-31']))
                ->assertOk()
                ->assertSee('Regional Running')
                ->assertSee('Regional Testing')
                ->assertSee('Ketidaksesuaian Data TESTING Ditemukan!');
        } finally {
            RegionalReport::where('user_id', $user->id)->delete();
            $running->delete();
            $testing->delete();
        }
    }

    /**
     * Discrepancy DUAL FASE (18 Sep): spending fase testing dipasangkan dgn
     * regional testing (bukan dgn running). Mismatch testing memicu banner
     * testing sendiri (has_discrepancy tetap true).
     */
    public function test_discrepancy_check_includes_testing_phase(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');
        $product = $this->makeProduct('testing'); // start_running null → semua spending testing

        try {
            SpendingHarian::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'whitelist_id' => $this->makeWhitelist($user->id)->id,
                'tanggal' => '2026-08-01',
                'spending' => 0,
                'lead' => 5,
                'paid' => 2,
            ]);

            $resp = $this->actingAs($user)
                ->getJson(route('regional.check', ['dari' => '2026-08-01', 'sampai' => '2026-08-31']))
                ->assertOk();

            $this->assertTrue($resp->json('has_discrepancy'), 'Regional testing kosong vs spending testing 5/2 → mismatch');
            $this->assertSame(5, $resp->json('spending_testing.lead'));
            $this->assertSame(2, $resp->json('spending_testing.paid'));
            $this->assertSame(0, $resp->json('spending.lead'), 'Spending testing tidak boleh terhitung di sisi running');
        } finally {
            SpendingHarian::where('user_id', $user->id)->delete();
            $product->delete();
        }
    }
}