<?php

namespace Tests\Feature;

use App\Models\CourierRule;
use App\Models\User;
use App\Services\CourierRuleService;
use Tests\TestCase;

/**
 * Kelola aturan courier (tabel `courier_rules`) via halaman admin.
 *
 * Catatan: test memakai DB aktif tanpa refresh (pola project) → setiap rule
 * memakai nama provinsi UNIK (prefix TEST PROVINCE) dan dihapus di akhir
 * test agar tidak mengganggu rules produksi/dev maupun test lain.
 */
class CourierRuleTest extends TestCase
{
    private function adminUser(): User
    {
        return User::create([
            'nama' => 'CR Test Admin',
            'email' => 'cr-test-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }

    private function uniqueProvince(): string
    {
        return 'TEST PROVINCE '.uniqid();
    }

    private function createRule(array $data = []): CourierRule
    {
        return CourierRule::create(array_merge([
            'sort_order' => 1,
            'payment_method' => 'cod',
            'province' => $this->uniqueProvince(),
            'courier' => 'spx',
            'is_active' => true,
        ], $data));
    }

    public function test_index_renders_rules(): void
    {
        $rule = $this->createRule(['courier' => 'flix-tf']);

        $this->actingAs($this->adminUser())
            ->get(route('courier-rule.index'))
            ->assertOk()
            ->assertSee($rule->province)
            ->assertSee('flix-tf');

        $rule->delete();
    }

    public function test_store_creates_rule_and_affects_resolution_dynamically(): void
    {
        $province = $this->uniqueProvince();

        $this->actingAs($this->adminUser())
            ->post(route('courier-rule.store'), [
                'sort_order' => 1,
                'payment_method' => 'cod',
                'province' => $province,
                'courier' => 'sicepat',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $rule = CourierRule::where('province', $province)->first();
        $this->assertNotNull($rule);
        $this->assertSame('sicepat', $rule->courier);

        // Bukti dinamis: resolve() langsung memakai rule baru dari DB
        $this->assertSame('sicepat', (new CourierRuleService)->resolve('cod', $province));

        $rule->delete();
    }

    public function test_store_normalizes_payment_and_province(): void
    {
        $province = $this->uniqueProvince();

        $this->actingAs($this->adminUser())
            ->post(route('courier-rule.store'), [
                'sort_order' => 1,
                'payment_method' => ' Bank_Transfer ',
                'province' => ' '.\strtolower($province).' ',
                'courier' => 'flix-tf',
            ])
            ->assertRedirect();

        $rule = CourierRule::where('province', $province)->first();
        $this->assertNotNull($rule);
        $this->assertSame('bank_transfer', $rule->payment_method);
        $this->assertSame(strtoupper($province), $rule->province);

        $rule->delete();
    }

    public function test_store_rejects_duplicate_combination(): void
    {
        $province = $this->uniqueProvince();
        $rule = $this->createRule(['payment_method' => 'cod', 'province' => $province, 'courier' => 'flix-idx']);

        $this->actingAs($this->adminUser())
            ->post(route('courier-rule.store'), [
                'sort_order' => 99,
                'payment_method' => 'cod',
                'province' => $province,
                'courier' => 'sicepat',
            ])
            ->assertSessionHasErrors('rule');

        $rule->delete();
    }

    public function test_store_requires_valid_courier(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('courier-rule.store'), [
                'sort_order' => 1,
                'payment_method' => 'cod',
                'province' => $this->uniqueProvince(),
                'courier' => 'NOT-A-COURIER',
            ])
            ->assertSessionHasErrors('courier');
    }

    public function test_update_changes_rule_and_resolution(): void
    {
        $province = $this->uniqueProvince();
        $rule = $this->createRule(['payment_method' => 'cod', 'province' => $province, 'courier' => 'flix-idx']);

        $this->actingAs($this->adminUser())
            ->put(route('courier-rule.update', $rule), [
                'sort_order' => $rule->sort_order,
                'payment_method' => 'cod',
                'province' => $province,
                'courier' => 'flix-spx',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $rule->refresh();
        $this->assertSame('flix-spx', $rule->courier);
        $this->assertSame('flix-spx', (new CourierRuleService)->resolve('cod', $province));

        $rule->delete();
    }

    public function test_toggle_disables_rule(): void
    {
        $province = $this->uniqueProvince();
        $rule = $this->createRule(['payment_method' => 'cod', 'province' => $province, 'courier' => 'flix-idx']);

        $this->assertSame('flix-idx', (new CourierRuleService)->resolve('cod', $province));

        $this->actingAs($this->adminUser())
            ->patch(route('courier-rule.toggle', $rule))
            ->assertRedirect();

        $rule->refresh();
        $this->assertFalse($rule->is_active);

        // Rule nonaktif di-skip → fallback spx
        $this->assertSame('spx', (new CourierRuleService)->resolve('cod', $province));

        $rule->delete();
    }

    public function test_destroy_removes_rule(): void
    {
        $province = $this->uniqueProvince();
        $rule = $this->createRule(['payment_method' => 'cod', 'province' => $province, 'courier' => 'flix-idx']);

        $this->actingAs($this->adminUser())
            ->delete(route('courier-rule.destroy', $rule))
            ->assertRedirect();

        $this->assertNull(CourierRule::find($rule->id));
        $this->assertSame('spx', (new CourierRuleService)->resolve('cod', $province));
    }

    public function test_move_down_swaps_sort_order(): void
    {
        $a = $this->createRule(['sort_order' => 50, 'payment_method' => 'cod', 'province' => 'TEST PROVINCE A '.uniqid(), 'courier' => 'sicepat']);
        $b = $this->createRule(['sort_order' => 51, 'payment_method' => 'cod', 'province' => 'TEST PROVINCE B '.uniqid(), 'courier' => 'flix-spx']);

        $this->actingAs($this->adminUser())
            ->post(route('courier-rule.move', [$a, 'down']))
            ->assertRedirect();

        $this->assertSame(51, $a->refresh()->sort_order);
        $this->assertSame(50, $b->refresh()->sort_order);

        $a->delete();
        $b->delete();
    }

    public function test_lower_sort_order_wins(): void
    {
        $province = $this->uniqueProvince();
        $low = $this->createRule(['sort_order' => 5, 'payment_method' => 'cod', 'province' => $province, 'courier' => 'sicepat']);
        $high = $this->createRule(['sort_order' => 9, 'payment_method' => 'cod', 'province' => $province, 'courier' => 'flix-idx']);

        $this->assertSame('sicepat', (new CourierRuleService)->resolve('cod', $province));

        $low->delete();
        $high->delete();
    }

    public function test_product_specific_rule_overrides_province_rules(): void
    {
        $province = $this->uniqueProvince();
        // Kode unik (bukan SH — SH sudah punya rule seed di DB dev)
        $code = 'CR'.strtoupper(substr(uniqid(), -6));
        $provinceRule = $this->createRule(['payment_method' => 'cod', 'province' => $province, 'courier' => 'sicepat']);
        $productRule = CourierRule::create([
            'sort_order' => 1,
            'payment_method' => null,
            'province' => null,
            'product_code' => $code,
            'courier' => 'flix-tf',
            'is_active' => true,
        ]);

        try {
            $svc = new CourierRuleService;

            // Produk lain tetap ikut rule provinsi
            $this->assertSame('sicepat', $svc->resolve('cod', $province, 'KMP'));

            // Produk ber-rule selalu flix-tf, apa pun metode bayar / provinsi
            $this->assertSame('flix-tf', $svc->resolve('cod', $province, $code));
            $this->assertSame('flix-tf', $svc->resolve('bank_transfer', 'RIAU', $code));

            // Normalisasi: kode varian tetap cocok rule master
            $this->assertSame('flix-tf', $svc->resolve('cod', $province, $code.'+1.25'));

            // Nonaktifkan rule produk → jatuh ke rule provinsi
            $productRule->update(['is_active' => false]);
            $this->assertSame('sicepat', (new CourierRuleService)->resolve('cod', $province, $code));
        } finally {
            $provinceRule->delete();
            $productRule->delete();
        }
    }

    public function test_store_accepts_product_code_and_normalizes(): void
    {
        $province = $this->uniqueProvince();
        $code = 'CR'.strtoupper(substr(uniqid(), -6));

        $this->actingAs($this->adminUser())
            ->post(route('courier-rule.store'), [
                'sort_order' => 1,
                'payment_method' => 'cod',
                'province' => $province,
                'product_code' => ' '.strtolower($code).'+1.25 ',
                'courier' => 'flix-tf',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $rule = CourierRule::where('product_code', $code)->first();
        $this->assertNotNull($rule);
        $this->assertSame($code, $rule->product_code);
        $this->assertSame('flix-tf', (new CourierRuleService)->resolve('cod', $province, $code));

        $rule->delete();
    }

    public function test_store_rejects_duplicate_product_combination(): void
    {
        $province = $this->uniqueProvince();
        $code = 'CR'.strtoupper(substr(uniqid(), -6));
        $rule = $this->createRule([
            'payment_method' => 'cod',
            'province' => $province,
            'product_code' => $code,
            'courier' => 'flix-tf',
        ]);

        $this->actingAs($this->adminUser())
            ->post(route('courier-rule.store'), [
                'sort_order' => 99,
                'payment_method' => 'cod',
                'province' => $province,
                'product_code' => $code,
                'courier' => 'sicepat',
            ])
            ->assertSessionHasErrors('rule');

        $rule->delete();
    }
}
