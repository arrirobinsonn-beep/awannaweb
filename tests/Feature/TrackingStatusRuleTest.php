<?php

namespace Tests\Feature;

use App\Models\TrackingSourceConfig;
use App\Models\TrackingStatusRule;
use App\Services\AggregatorTrackingImportService;
use App\Services\TrackingStatusRuleService;
use Tests\TestCase;

/**
 * Aturan status aggregator dinamis (fitur S) — tabel tracking_status_rules.
 *
 * Test memakai DB aktif (awannacoba) tanpa refresh (pola project): rule test
 * memakai raw_status unik prefix "teststatus" dan di-delete di akhir test agar
 * tidak mengganggu rules asli / seeding bawaan.
 */
class TrackingStatusRuleTest extends TestCase
{
    private function uniqueRaw(): string
    {
        return 'teststatus'.strtolower(substr(uniqid(), -6));
    }

    private function cleanup(int $id): void
    {
        TrackingStatusRule::where('id', $id)->delete();
    }

    public function test_index_page_renders_dashboard_cards(): void
    {
        $this->withoutExceptionHandling();

        $res = $this->actingAs($this->makeAdmin())->get(route('tracking-status-rule.index'));
        $res->assertOk();
        $res->assertSee('Aturan Status');
        $res->assertSee('FLIK');
        $res->assertSee('SiCepat');
        $res->assertSee('SPX');
        $res->assertSee('Mapping header CSV');
        $res->assertSee(route('tracking-status-rule.edit', 'flik'));
    }

    public function test_edit_page_renders_per_dashboard_scoped(): void
    {
        $flikRaw = $this->uniqueRaw().'a';
        $spxRaw = $this->uniqueRaw().'b';
        $flik = TrackingStatusRule::create([
            'source' => 'flik', 'raw_status' => $flikRaw, 'match_type' => 'exact',
            'status' => 'in_transit', 'problem_mode' => 'none', 'problem_keyword' => null,
            'sort_order' => 5, 'is_active' => true,
        ]);
        $spx = TrackingStatusRule::create([
            'source' => 'spx', 'raw_status' => $spxRaw, 'match_type' => 'exact',
            'status' => 'delivered', 'problem_mode' => 'none', 'problem_keyword' => null,
            'sort_order' => 5, 'is_active' => true,
        ]);

        try {
            $flikPage = $this->actingAs($this->makeAdmin())->get(route('tracking-status-rule.edit', 'flik'));
            $flikPage->assertOk();
            $flikPage->assertSee('Mapping Kolom Database → Header CSV');
            $flikPage->assertSee('Kolom Database (tetap)');
            $flikPage->assertSee('Nama Pelanggan (customer_name)');
            $flikPage->assertSee($flikRaw);
            $flikPage->assertDontSee($spxRaw);

            $spxPage = $this->actingAs($this->makeAdmin())->get(route('tracking-status-rule.edit', 'spx'));
            $spxPage->assertOk();
            $spxPage->assertSee($spxRaw);
            $spxPage->assertDontSee($flikRaw);
        } finally {
            $this->cleanup($flik->id);
            $this->cleanup($spx->id);
        }
    }

    public function test_upload_parses_csv_headers_with_carry_over(): void
    {
        \App\Models\TrackingHeaderMapping::where('source', 'flik')->delete();

        try {
            $csv = "Order ID,AWB,Nama Shopper,No HP,Status Terakhir,Status Terakhir dari 3PL,Alamat\n"
                . "1,AWB001,Budi,081234567890,Dikonfirmasi,OK,Jakarta\n";
            $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('tracking_flik.csv', $csv);

            $res = $this->actingAs($this->makeAdmin())->post(route('tracking-status-rule.upload'), ['file' => $file, 'source' => 'flik']);
            $res->assertOk();
            $res->assertJson(['success' => true, 'source' => 'flik']);

            $headers = $res->json('headers');
            $this->assertContains('order id', $headers);
            $this->assertContains('awb', $headers);
            $this->assertContains('status terakhir', $headers);
            $this->assertContains('status terakhir dari 3pl', $headers);
            $this->assertArrayHasKey('mapping', $res->json());
            $this->assertSame([], $res->json('mapping'));
        } finally {
            \App\Models\TrackingHeaderMapping::where('source', 'flik')->delete();
        }
    }

    public function test_upload_carries_existing_mapping_per_db_column(): void
    {
        \App\Models\TrackingHeaderMapping::where('source', 'flik')->delete();
        \App\Models\TrackingHeaderMapping::create([
            'source' => 'flik', 'header' => 'awb', 'db_column' => 'tracking_number',
        ]);

        try {
            $csv = "Order ID,AWB,No HP,Status\n1,AWB001,081234567890,Dikonfirmasi\n";
            $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('tracking_flik.csv', $csv);

            $res = $this->actingAs($this->makeAdmin())->post(route('tracking-status-rule.upload'), ['file' => $file, 'source' => 'flik']);
            $res->assertOk();
            $this->assertSame('awb', $res->json('mapping.tracking_number'));
        } finally {
            \App\Models\TrackingHeaderMapping::where('source', 'flik')->delete();
        }
    }

    public function test_save_mapping_is_used_by_import(): void
    {
        $raw = $this->uniqueRaw();
        $rule = TrackingStatusRule::create([
            'source' => 'flik', 'raw_status' => $raw, 'match_type' => 'exact',
            'status' => 'in_transit', 'problem_mode' => 'none', 'problem_keyword' => null,
            'sort_order' => 1, 'is_active' => true,
        ]);

        $path = sys_get_temp_dir().'/tsm_test_'.uniqid().'.csv';
        $csv = "Order ID,AWB,HP Customer,Nama Customer,Status Paket,Alamat Rumah,Produk,Jumlah\n"
            . "1,RESI001,081234567890,Budi,{$raw},Jakarta,Kacamata,2\n";
        file_put_contents($path, $csv);

        try {
            $res = $this->actingAs($this->makeAdmin())->post(route('tracking-status-rule.mapping', 'flik'), [
                'items' => [
                    ['db_column' => 'tracking_number', 'header' => 'awb'],
                    ['db_column' => 'phone', 'header' => 'hp customer'],
                    ['db_column' => 'customer_name', 'header' => 'nama customer'],
                    ['db_column' => 'status', 'header' => 'status paket'],
                    ['db_column' => 'address', 'header' => 'alamat rumah'],
                    ['db_column' => 'product_name', 'header' => 'produk'],
                    ['db_column' => 'quantity', 'header' => 'jumlah'],
                ],
            ]);
            $res->assertRedirect(route('tracking-status-rule.edit', 'flik'));
            $this->assertSame(7, \App\Models\TrackingHeaderMapping::where('source', 'flik')->count());

            // Header tak standar (bukan alias bawaan) kini dikenali via mapping DB
            $result = (new AggregatorTrackingImportService)->parse($path, 'flik');
            $this->assertSame('flik', $result['source']);
            $row = $result['data']->first();
            $this->assertSame('RESI001', $row['awb']);
            $this->assertSame('6281234567890', $row['phone_normalized']);
            $this->assertSame('budi', $row['name_norm']);
            $this->assertSame('in_transit', $row['status']);
            $this->assertSame('jakarta', $row['address_norm']);
            $this->assertSame('Kacamata', $row['product_text']);
            $this->assertSame(2, $row['quantity']);
        } finally {
            \App\Models\TrackingHeaderMapping::where('source', 'flik')->delete();
            $this->cleanup($rule->id);
            @unlink($path);
        }
    }

    public function test_save_mapping_rejects_duplicate_header(): void
    {
        \App\Models\TrackingHeaderMapping::where('source', 'flik')->delete();

        try {
            $res = $this->actingAs($this->makeAdmin())->post(route('tracking-status-rule.mapping', 'flik'), [
                'items' => [
                    ['db_column' => 'phone', 'header' => 'no hp'],
                    ['db_column' => 'customer_name', 'header' => 'no hp'],
                ],
            ]);
            $res->assertSessionHasErrors('mapping');
            $this->assertSame(0, \App\Models\TrackingHeaderMapping::where('source', 'flik')->count());
        } finally {
            \App\Models\TrackingHeaderMapping::where('source', 'flik')->delete();
        }
    }

    public function test_save_phone_format_config_is_used_by_import(): void
    {
        // SPX file berformat 8xxxxx → dengan config phone_format=8 tetap cocok dgn DB 62xxxxx
        TrackingSourceConfig::where('source', 'spx')->delete();

        try {
            $res = $this->actingAs($this->makeAdmin())->post(route('tracking-status-rule.config', 'spx'), [
                'phone_format' => '8',
            ]);
            $res->assertRedirect(route('tracking-status-rule.edit', 'spx'));
            $this->assertSame('8', TrackingSourceConfig::where('source', 'spx')->value('phone_format'));

            $path = sys_get_temp_dir().'/cfg_test_'.uniqid().'.csv';
            $csv = "Tracking No.,Tracking Status,Recipient Phone Number,Recipient Detail Address,Item in Parcel,No. of item in Parcel\n"
                . "SPXID001,Delivered,81234567890,Jl. Test 1,Produk 1 pcs,1\n";
            file_put_contents($path, $csv);

            $result = (new AggregatorTrackingImportService)->parse($path, 'spx');
            $this->assertSame('spx', $result['source']);
            $row = $result['data']->first();
            $this->assertSame('6281234567890', $row['phone_normalized']);
            @unlink($path);
        } finally {
            TrackingSourceConfig::where('source', 'spx')->delete();
        }
    }

    public function test_phone_format_default_auto_normalizes_8(): void
    {
        // Tanpa config → auto: 8xxxxx tetap dinormalisasi ke 62xxxxx
        TrackingSourceConfig::where('source', 'flik')->delete();

        try {
            $path = sys_get_temp_dir().'/cfg_test_'.uniqid().'.csv';
            $csv = "Order ID,AWB,No HP,Status\n1,AWB001,81234567890,Dikonfirmasi\n";
            file_put_contents($path, $csv);

            $result = (new AggregatorTrackingImportService)->parse($path, 'flik');
            $row = $result['data']->first();
            $this->assertSame('6281234567890', $row['phone_normalized']);
            @unlink($path);
        } finally {
            TrackingSourceConfig::where('source', 'flik')->delete();
        }
    }

    public function test_store_rule_is_used_by_resolver_immediately(): void
    {
        $raw = $this->uniqueRaw();

        $res = $this->actingAs($this->makeAdmin())->post(route('tracking-status-rule.store'), [
            'source' => 'flik',
            'raw_status' => $raw,
            'match_type' => 'exact',
            'status' => 'in_transit',
            'problem_mode' => 'none',
            'problem_keyword' => '',
            'problem_match_type' => 'contains',
            'sort_order' => 5,
            'is_active' => 1,
        ]);
        $res->assertRedirect(route('tracking-status-rule.index'));

        $rule = TrackingStatusRule::where('raw_status', $raw)->firstOrFail();
        try {
            $this->assertSame('in_transit', (new TrackingStatusRuleService)->resolve('flik', $raw, ''));
        } finally {
            $this->cleanup($rule->id);
        }
    }

    public function test_problem_required_rule_only_matches_when_problem_column_fulfilled(): void
    {
        $raw = $this->uniqueRaw();
        $svc = new TrackingStatusRuleService;

        $rule = TrackingStatusRule::create([
            'source' => 'spx',
            'raw_status' => $raw,
            'match_type' => 'exact',
            'status' => 'problem',
            'problem_mode' => 'required',
            'problem_keyword' => null, // cukup kolom masalah tidak kosong
            'sort_order' => 1,
            'is_active' => true,
        ]);

        try {
            // Kolom masalah kosong → rule problem dilewati → tak dikenal
            $this->assertNull($svc->resolve('spx', $raw, ''));
            // Kolom masalah terisi → problem
            $this->assertSame('problem', $svc->resolve('spx', $raw, 'On Hold - alamat lengkap'));

            // Dengan keyword + starts_with: harus DIAWALI keyword (keunikan FLIK)
            $rule2 = TrackingStatusRule::create([
                'source' => 'flik',
                'raw_status' => $raw,
                'match_type' => 'exact',
                'status' => 'problem',
                'problem_mode' => 'required',
                'problem_keyword' => 'problem',
                'problem_match_type' => 'starts_with',
                'sort_order' => 1,
                'is_active' => true,
            ]);
            $flikSvc = new TrackingStatusRuleService;
            $this->assertNull($flikSvc->resolve('flik', $raw, 'OK'));
            $this->assertNull($flikSvc->resolve('flik', $raw, 'Ada masalah di tengah kalimat')); // contains tapi bukan awalan
            $this->assertSame('problem', $flikSvc->resolve('flik', $raw, 'Problem: alamat tidak lengkap'));
            $this->cleanup($rule2->id);
        } finally {
            $this->cleanup($rule->id);
        }
    }

    public function test_problem_rule_takes_priority_over_normal_rule_by_sort_order(): void
    {
        $raw = $this->uniqueRaw();
        $svc = new TrackingStatusRuleService;

        $problemRule = TrackingStatusRule::create([
            'source' => 'flik',
            'raw_status' => $raw,
            'match_type' => 'exact',
            'status' => 'problem',
            'problem_mode' => 'required',
            'problem_keyword' => 'problem',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        TrackingStatusRule::create([
            'source' => 'flik',
            'raw_status' => $raw,
            'match_type' => 'exact',
            'status' => 'waiting_pickup',
            'problem_mode' => 'none',
            'problem_keyword' => null,
            'sort_order' => 10,
            'is_active' => true,
        ]);

        try {
            $this->assertSame('problem', $svc->resolve('flik', $raw, 'Problem with courier'));
            $this->assertSame('waiting_pickup', $svc->resolve('flik', $raw, 'OK'));
        } finally {
            $this->cleanup($problemRule->id);
            TrackingStatusRule::where('source', 'flik')->where('raw_status', $raw)->delete();
        }
    }

    public function test_contains_match_type(): void
    {
        $raw = $this->uniqueRaw();
        $svc = new TrackingStatusRuleService;

        $rule = TrackingStatusRule::create([
            'source' => 'sicepat',
            'raw_status' => 'sedang',
            'match_type' => 'contains',
            'status' => 'in_transit',
            'problem_mode' => 'none',
            'problem_keyword' => null,
            'sort_order' => 5,
            'is_active' => true,
        ]);

        try {
            $this->assertSame('in_transit', $svc->resolve('sicepat', 'Sedang Dikirim', ''));
            $this->assertSame('in_transit', $svc->resolve('sicepat', 'Paket sedang diproses', ''));
            $this->assertNull($svc->resolve('sicepat', 'Selesai', ''));
        } finally {
            $this->cleanup($rule->id);
        }
    }

    public function test_toggle_inactivates_rule(): void
    {
        $raw = $this->uniqueRaw();
        $rule = TrackingStatusRule::create([
            'source' => 'flik',
            'raw_status' => $raw,
            'match_type' => 'exact',
            'status' => 'delivered',
            'problem_mode' => 'none',
            'problem_keyword' => null,
            'sort_order' => 5,
            'is_active' => true,
        ]);

        try {
            $this->actingAs($this->makeAdmin())->patch(route('tracking-status-rule.toggle', $rule));
            $this->assertFalse($rule->refresh()->is_active);
            $this->assertNull((new TrackingStatusRuleService)->resolve('flik', $raw, ''));
        } finally {
            $this->cleanup($rule->id);
        }
    }

    public function test_destroy_removes_rule(): void
    {
        $raw = $this->uniqueRaw();
        $rule = TrackingStatusRule::create([
            'source' => 'flik',
            'raw_status' => $raw,
            'match_type' => 'exact',
            'status' => 'delivered',
            'problem_mode' => 'none',
            'problem_keyword' => null,
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->makeAdmin())->delete(route('tracking-status-rule.destroy', $rule));
        $this->assertDatabaseMissing('tracking_status_rules', ['id' => $rule->id]);
    }

    public function test_duplicate_combo_is_rejected(): void
    {
        $raw = $this->uniqueRaw();
        $rule = TrackingStatusRule::create([
            'source' => 'flik',
            'raw_status' => $raw,
            'match_type' => 'exact',
            'status' => 'delivered',
            'problem_mode' => 'none',
            'problem_keyword' => null,
            'sort_order' => 5,
            'is_active' => true,
        ]);

        try {
            $res = $this->actingAs($this->makeAdmin())->post(route('tracking-status-rule.store'), [
                'source' => 'flik',
                'raw_status' => $raw,
                'match_type' => 'exact',
                'status' => 'delivered',
                'problem_mode' => 'none',
                'problem_keyword' => '',
                'problem_match_type' => 'contains',
                'sort_order' => 9,
                'is_active' => 1,
            ]);
            $res->assertSessionHasErrors('rule');
            $this->assertSame(1, TrackingStatusRule::where('raw_status', $raw)->count());
        } finally {
            $this->cleanup($rule->id);
        }
    }

    public function test_move_swaps_sort_order(): void
    {
        $rawA = $this->uniqueRaw().'a';
        $rawB = $this->uniqueRaw().'b';
        $a = TrackingStatusRule::create([
            'source' => 'flik', 'raw_status' => $rawA, 'match_type' => 'exact',
            'status' => 'delivered', 'problem_mode' => 'none', 'problem_keyword' => null,
            'sort_order' => 1, 'is_active' => true,
        ]);
        $b = TrackingStatusRule::create([
            'source' => 'flik', 'raw_status' => $rawB, 'match_type' => 'exact',
            'status' => 'delivered', 'problem_mode' => 'none', 'problem_keyword' => null,
            'sort_order' => 2, 'is_active' => true,
        ]);

        try {
            $this->actingAs($this->makeAdmin())->post(route('tracking-status-rule.move', [$b, 'up']));
            $this->assertSame(2, $a->refresh()->sort_order);
            $this->assertSame(1, $b->refresh()->sort_order);
        } finally {
            $this->cleanup($a->id);
            $this->cleanup($b->id);
        }
    }

    public function test_update_rule_changes_mapping(): void
    {
        $raw = $this->uniqueRaw();
        $rule = TrackingStatusRule::create([
            'source' => 'flik',
            'raw_status' => $raw,
            'match_type' => 'exact',
            'status' => 'delivered',
            'problem_mode' => 'none',
            'problem_keyword' => null,
            'sort_order' => 5,
            'is_active' => true,
        ]);

        try {
            $this->actingAs($this->makeAdmin())->put(route('tracking-status-rule.update', $rule), [
                'source' => 'flik',
                'raw_status' => $raw,
                'match_type' => 'exact',
                'status' => 'returned',
                'problem_mode' => 'none',
                'problem_keyword' => '',
                'problem_match_type' => 'contains',
                'sort_order' => 5,
                'is_active' => 1,
            ]);
            $this->assertSame('returned', $rule->refresh()->status);
            $this->assertSame('returned', (new TrackingStatusRuleService)->resolve('flik', $raw, ''));
        } finally {
            $this->cleanup($rule->id);
        }
    }

    public function test_seeder_populates_default_header_mappings_from_templates(): void
    {
        // Hapus semua mapping dulu agar deterministik, lalu jalankan seeder
        \App\Models\TrackingHeaderMapping::query()->delete();

        try {
            $this->seed(\Database\Seeders\TrackingHeaderMappingSeeder::class);

            $get = fn (string $src, string $header) => \App\Models\TrackingHeaderMapping::where('source', $src)
                ->where('header', $header)->value('db_column');

            // FLIK — termasuk keunikan kolom masalah terpisah (status terakhir dari 3pl)
            $this->assertSame('tracking_number', $get('flik', 'awb'));
            $this->assertSame('phone', $get('flik', 'no telp'));
            $this->assertSame('customer_name', $get('flik', 'nama shopper'));
            $this->assertSame('problem', $get('flik', 'status terakhir dari 3pl'));
            $this->assertSame('delivered_date', $get('flik', 'terakhir update'));
            $this->assertSame('address', $get('flik', 'alamat lengkap penerima'));

            // SICEPAT
            $this->assertSame('tracking_number', $get('sicepat', 'nomor resi'));
            $this->assertSame('phone', $get('sicepat', 'no. hp penerima'));
            $this->assertSame('product_name', $get('sicepat', 'isi paket'));
            $this->assertSame('quantity', $get('sicepat', 'jumlah isi paket'));
            $this->assertSame('delivered_date', $get('sicepat', 'tanggal terkirim'));

            // SPX — nomor 8-prefix + kolom masalah OnHold + nama/alamat recipient
            $this->assertSame('tracking_number', $get('spx', 'tracking no.'));
            $this->assertSame('status', $get('spx', 'tracking status'));
            $this->assertSame('phone', $get('spx', 'recipient phone number'));
            $this->assertSame('customer_name', $get('spx', 'recipient name'));
            $this->assertSame('problem', $get('spx', 'delivery onhold reason'));
            $this->assertSame('delivered_date', $get('spx', 'delivered time'));
            $this->assertSame('quantity', $get('spx', 'no. of item in parcel'));

            // Idempotent — dijalankan dua kali tidak menggandakan
            $counts = \App\Models\TrackingHeaderMapping::select('source')
                ->selectRaw('count(*) as c')->groupBy('source')->pluck('c', 'source');
            $this->seed(\Database\Seeders\TrackingHeaderMappingSeeder::class);
            $counts2 = \App\Models\TrackingHeaderMapping::select('source')
                ->selectRaw('count(*) as c')->groupBy('source')->pluck('c', 'source');
            $this->assertSame($counts->all(), $counts2->all());
        } finally {
            // Kembalikan state: mapping bawaan (seeder) dipasang ulang — idempotent
            $this->seed(\Database\Seeders\TrackingHeaderMappingSeeder::class);
        }
    }

    public function test_import_uses_db_rules_for_problem_status(): void
    {
        // Mapping dinamis ikut terpakai saat import file sungguhan
        $svc = new AggregatorTrackingImportService;

        $this->assertSame('problem', $svc->mapStatus('flik', 'Dikonfirmasi', 'Problem: alamat tidak lengkap'));
        $this->assertSame('waiting_pickup', $svc->mapStatus('flik', 'Dikonfirmasi', 'OK'));
        // FLIK unik: status NORMAL + kolom 3PL terpisah diawali "Problem" → problem
        $this->assertSame('problem', $svc->mapStatus('flik', 'Sedang Diantar', 'Problem - penerima menolak'));
        $this->assertSame('problem', $svc->mapStatus('spx', 'In Transit', 'On Hold'));
        $this->assertSame('in_transit', $svc->mapStatus('spx', 'In Transit', ''));
        $this->assertSame('returned', $svc->mapStatus('spx', 'Returned', ''));
        $this->assertNull($svc->mapStatus('sicepat', 'Status tak dikenal', ''));
    }

    private function makeAdmin(): \App\Models\User
    {
        return \App\Models\User::create([
            'nama' => 'TS Test Admin',
            'email' => 'ts-test-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }
}
