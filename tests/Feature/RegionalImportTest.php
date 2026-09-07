<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RegionalReport;
use App\Models\User;
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
}