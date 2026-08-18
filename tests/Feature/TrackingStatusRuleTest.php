<?php

namespace Tests\Feature;

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

    public function test_index_page_renders(): void
    {
        $this->withoutExceptionHandling();

        $res = $this->actingAs($this->makeAdmin())->get(route('tracking-status-rule.index'));
        $res->assertOk();
        $res->assertSee('Aturan Status');
        $res->assertSee('Status Sistem');
        $res->assertSee('Kolom Masalah');
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

            // Dengan keyword: harus MENGANDUNG keyword (service BARU — cache per instance)
            $rule2 = TrackingStatusRule::create([
                'source' => 'flik',
                'raw_status' => $raw,
                'match_type' => 'exact',
                'status' => 'problem',
                'problem_mode' => 'required',
                'problem_keyword' => 'problem',
                'sort_order' => 1,
                'is_active' => true,
            ]);
            $flikSvc = new TrackingStatusRuleService;
            $this->assertNull($flikSvc->resolve('flik', $raw, 'OK'));
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
                'sort_order' => 5,
                'is_active' => 1,
            ]);
            $this->assertSame('returned', $rule->refresh()->status);
            $this->assertSame('returned', (new TrackingStatusRuleService)->resolve('flik', $raw, ''));
        } finally {
            $this->cleanup($rule->id);
        }
    }

    public function test_import_uses_db_rules_for_problem_status(): void
    {
        // Mapping dinamis ikut terpakai saat import file sungguhan
        $svc = new AggregatorTrackingImportService;

        $this->assertSame('problem', $svc->mapStatus('flik', 'Dikonfirmasi', 'Problem: alamat tidak lengkap'));
        $this->assertSame('waiting_pickup', $svc->mapStatus('flik', 'Dikonfirmasi', 'OK'));
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
