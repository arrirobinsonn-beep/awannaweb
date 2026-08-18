<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WarehouseRule;
use App\Services\OrderTemplateExportService;
use App\Services\WarehouseRuleService;
use Tests\TestCase;

/**
 * Kelola aturan dinamis kode produk → gudang (tabel `warehouse_rules`) via
 * halaman admin — pengganti konstanta WAREHOUSE_BY_PRODUCT (SH→GTM, KSP→Aurora).
 *
 * Catatan: test memakai DB aktif tanpa refresh (pola project) → setiap rule
 * memakai kode produk UNIK (prefix WR…) dan dihapus di akhir test.
 */
class WarehouseRuleTest extends TestCase
{
    private function adminUser(): User
    {
        return User::create([
            'nama' => 'WR Test Admin',
            'email' => 'wr-test-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }

    private function uniqueCode(): string
    {
        return 'WR'.strtoupper(substr(uniqid(), -6));
    }

    private function createRule(array $data = []): WarehouseRule
    {
        return WarehouseRule::create(array_merge([
            'product_code' => $this->uniqueCode(),
            'warehouse' => 'WH '.uniqid(),
            'is_active' => true,
        ], $data));
    }

    public function test_index_renders_rules(): void
    {
        $rule = $this->createRule(['warehouse' => 'WH INDEX']);

        $this->actingAs($this->adminUser())
            ->get(route('warehouse-rule.index'))
            ->assertOk()
            ->assertSee($rule->product_code)
            ->assertSee('WH INDEX');

        $rule->delete();
    }

    public function test_store_creates_rule_and_affects_export_dynamically(): void
    {
        $code = $this->uniqueCode();

        $this->actingAs($this->adminUser())
            ->post(route('warehouse-rule.store'), [
                'product_code' => $code,
                'warehouse' => 'WH DINAMIS',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $rule = WarehouseRule::where('product_code', $code)->first();
        $this->assertNotNull($rule);
        $this->assertSame('WH DINAMIS', $rule->warehouse);

        // Bukti dinamis: warehouseFor langsung memakai rule baru dari DB
        $this->assertSame('WH DINAMIS', (new WarehouseRuleService)->resolve($code));
        $this->assertSame('WH DINAMIS', (new OrderTemplateExportService)->warehouseFor($code, 'eresgestore'));

        $rule->delete();
    }

    public function test_store_normalizes_product_code(): void
    {
        $code = $this->uniqueCode();

        $this->actingAs($this->adminUser())
            ->post(route('warehouse-rule.store'), [
                'product_code' => ' '.strtolower($code).'+1.50 ',
                'warehouse' => 'WH NORMALIZED',
            ])
            ->assertRedirect();

        $rule = WarehouseRule::where('product_code', $code)->first();
        $this->assertNotNull($rule);
        $this->assertSame($code, $rule->product_code);

        $rule->delete();
    }

    public function test_store_rejects_duplicate_product_code(): void
    {
        $code = $this->uniqueCode();
        $rule = $this->createRule(['product_code' => $code, 'warehouse' => 'WH A']);

        $this->actingAs($this->adminUser())
            ->post(route('warehouse-rule.store'), [
                'product_code' => $code,
                'warehouse' => 'WH B',
            ])
            ->assertSessionHasErrors('rule');

        $rule->delete();
    }

    public function test_update_changes_rule_and_resolution(): void
    {
        $code = $this->uniqueCode();
        $rule = $this->createRule(['product_code' => $code, 'warehouse' => 'WH A']);

        $this->actingAs($this->adminUser())
            ->put(route('warehouse-rule.update', $rule), [
                'product_code' => $code,
                'warehouse' => 'WH B',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $rule->refresh();
        $this->assertSame('WH B', $rule->warehouse);
        $this->assertSame('WH B', (new OrderTemplateExportService)->warehouseFor($code, 'eresgestore'));

        $rule->delete();
    }

    public function test_toggle_disables_rule(): void
    {
        $code = $this->uniqueCode();
        $rule = $this->createRule(['product_code' => $code, 'warehouse' => 'WH A']);

        $this->assertSame('WH A', (new OrderTemplateExportService)->warehouseFor($code, 'eresgestore'));

        $this->actingAs($this->adminUser())
            ->patch(route('warehouse-rule.toggle', $rule))
            ->assertRedirect();

        $rule->refresh();
        $this->assertFalse($rule->is_active);

        // Rule nonaktif di-skip → kode tak dikenal jatuh ke sender
        $this->assertSame('eresgestore', (new OrderTemplateExportService)->warehouseFor($code, 'eresgestore'));

        $rule->delete();
    }

    public function test_destroy_removes_rule(): void
    {
        $code = $this->uniqueCode();
        $rule = $this->createRule(['product_code' => $code, 'warehouse' => 'WH A']);

        $this->actingAs($this->adminUser())
            ->delete(route('warehouse-rule.destroy', $rule))
            ->assertRedirect();

        $this->assertNull(WarehouseRule::find($rule->id));
        $this->assertNull((new WarehouseRuleService)->resolve($code));
    }
}
