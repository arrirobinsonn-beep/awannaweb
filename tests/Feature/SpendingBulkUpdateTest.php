<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SpendingHarian;
use App\Models\User;
use App\Models\Whitelist;
use Tests\TestCase;

class SpendingBulkUpdateTest extends TestCase
{
    private function makeUser(): User
    {
        return User::create([
            'nama' => 'User '.uniqid(),
            'email' => 'bulk-'.uniqid().'@example.com',
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

    private function makeProduct(): Product
    {
        return Product::create([
            'code' => 'P'.strtoupper(substr(uniqid(), -6)),
            'name' => 'Produk Test',
            'status' => 'active',
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

    public function test_advertiser_bulk_updates_own_rows_per_item_and_recalculates_whitelist(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');

        $wl = $this->makeWhitelist($adv);
        $product = $this->makeProduct();

        $row1 = $this->makeSpending($adv, $wl, $product, '2026-08-01', 100000, 10, 4);
        $row2 = $this->makeSpending($adv, $wl, $product, '2026-08-02', 200000, 20, 8);

        $this->actingAs($adv)
            ->from(route('spending.index'))
            ->post(route('spending.bulk-update'), [
                'items' => [
                    ['id' => $row1->id, 'spending' => 500000, 'lead' => 25, 'paid' => 15],
                    ['id' => $row2->id, 'spending' => 300000, 'lead' => 10, 'paid' => 4],
                ],
            ])
            ->assertRedirect(route('spending.index'))
            ->assertSessionHas('success');

        $row1->refresh();
        $row2->refresh();

        // Tiap baris dapat nilainya sendiri + metrik ter-recompute
        // row1: 15/25 = 60%, 500000/25 = 20000, 500000/15 = 33333.33
        $this->assertSame('500000.00', (string) $row1->spending);
        $this->assertSame(25, $row1->lead);
        $this->assertSame(15, $row1->paid);
        $this->assertSame(60, $row1->paid_ratio);
        $this->assertSame('20000.00', (string) $row1->cpa_lead);
        $this->assertSame('33333.33', (string) $row1->cpa_paid);
        // row2: 4/10 = 40%, 300000/10 = 30000, 300000/4 = 75000
        $this->assertSame('300000.00', (string) $row2->spending);
        $this->assertSame(10, $row2->lead);
        $this->assertSame(4, $row2->paid);
        $this->assertSame(40, $row2->paid_ratio);
        $this->assertSame('30000.00', (string) $row2->cpa_lead);
        $this->assertSame('75000.00', (string) $row2->cpa_paid);

        // total_spending whitelist = 500000 + 300000
        $this->assertSame('800000.00', (string) $wl->fresh()->total_spending);
    }

    public function test_bulk_update_handles_rows_across_products_and_dates_individually(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);
        $prodA = $this->makeProduct();
        $prodB = $this->makeProduct();

        // 2 tanggal x 2 produk → skenario "lintas tanggal + lintas produk"
        $a1 = $this->makeSpending($adv, $wl, $prodA, '2026-08-01', 100000, 10, 4);
        $a2 = $this->makeSpending($adv, $wl, $prodA, '2026-08-02', 200000, 20, 8);
        $b1 = $this->makeSpending($adv, $wl, $prodB, '2026-08-01', 50000, 5, 2);

        // Pakai keying array persis seperti form asli: items[<db-id>][spending] dst.
        $this->actingAs($adv)
            ->from(route('spending.index'))
            ->post(route('spending.bulk-update'), [
                'items' => [
                    $a1->id => ['id' => $a1->id, 'spending' => 111000, 'lead' => 11, 'paid' => 5],
                    $a2->id => ['id' => $a2->id, 'spending' => 222000, 'lead' => 22, 'paid' => 9],
                    $b1->id => ['id' => $b1->id, 'spending' => 333000, 'lead' => 33, 'paid' => 12],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $a1->refresh();
        $a2->refresh();
        $b1->refresh();

        $this->assertSame('111000.00', (string) $a1->spending);
        $this->assertSame(11, $a1->lead);
        $this->assertSame(5, $a1->paid);
        $this->assertSame('222000.00', (string) $a2->spending);
        $this->assertSame(22, $a2->lead);
        $this->assertSame(9, $a2->paid);
        $this->assertSame('333000.00', (string) $b1->spending);
        $this->assertSame(33, $b1->lead);
        $this->assertSame(12, $b1->paid);

        $this->assertSame('666000.00', (string) $wl->fresh()->total_spending);
    }

    public function test_advertiser_cannot_bulk_update_others_rows(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);
        $product = $this->makeProduct();
        $own = $this->makeSpending($adv, $wl, $product, '2026-08-01', 100000, 10, 4);

        $other = $this->makeUser();
        $otherWl = $this->makeWhitelist($other);
        $otherRow = $this->makeSpending($other, $otherWl, $product, '2026-08-01', 70000, 7, 2);

        $this->actingAs($adv)
            ->from(route('spending.index'))
            ->post(route('spending.bulk-update'), [
                'items' => [
                    ['id' => $own->id, 'spending' => 90000, 'lead' => 9, 'paid' => 3],
                    ['id' => $otherRow->id, 'spending' => 11111, 'lead' => 1, 'paid' => 1],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Punya sendiri berubah; punya orang lain dilewati (tidak tersentuh)
        $own->refresh();
        $otherRow->refresh();
        $this->assertSame('90000.00', (string) $own->spending);
        $this->assertSame('70000.00', (string) $otherRow->spending);
        $this->assertSame(7, $otherRow->lead);
        $this->assertSame(2, $otherRow->paid);

        $this->assertStringContainsString('1 data dilewati', session('success'));
    }

    public function test_cs_mapped_to_advertiser_can_bulk_update(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);
        $product = $this->makeProduct();
        $row = $this->makeSpending($adv, $wl, $product, '2026-08-01', 100000, 10, 4);

        $cs = $this->makeUser();
        $cs->assignRole('cs');
        $cs->forceFill(['advertiser_id' => $adv->id])->save();

        $this->actingAs($cs)
            ->from(route('spending.index'))
            ->post(route('spending.bulk-update'), [
                'items' => [
                    ['id' => $row->id, 'spending' => 250000, 'lead' => 20, 'paid' => 6],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $row->refresh();
        $this->assertSame('250000.00', (string) $row->spending);
        $this->assertSame(20, $row->lead);
        $this->assertSame(6, $row->paid);
    }

    public function test_cs_outside_team_cannot_bulk_update(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);
        $product = $this->makeProduct();
        $row = $this->makeSpending($adv, $wl, $product, '2026-08-01', 100000, 10, 4);

        // CS tanpa mapping advertiser → tidak ada data yang valid
        $cs = $this->makeUser();
        $cs->assignRole('cs');

        $this->actingAs($cs)
            ->from(route('spending.index'))
            ->post(route('spending.bulk-update'), [
                'items' => [
                    ['id' => $row->id, 'spending' => 50000, 'lead' => 5, 'paid' => 1],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $row->refresh();
        $this->assertSame('100000.00', (string) $row->spending);
        $this->assertSame(10, $row->lead);
    }

    public function test_bulk_update_rejects_invalid_payload(): void
    {
        $adv = $this->makeUser();
        $adv->assignRole('advertiser');
        $wl = $this->makeWhitelist($adv);
        $product = $this->makeProduct();
        $row = $this->makeSpending($adv, $wl, $product, '2026-08-01', 100000, 10, 4);

        // spending negatif → validasi gagal, data tidak berubah
        $this->actingAs($adv)
            ->from(route('spending.index'))
            ->post(route('spending.bulk-update'), [
                'items' => [
                    $row->id => ['id' => $row->id, 'spending' => -5, 'lead' => 10, 'paid' => 4],
                ],
            ])
            ->assertSessionHasErrors();

        $row->refresh();
        $this->assertSame('100000.00', (string) $row->spending);

        // items kosong → validasi gagal
        $this->actingAs($adv)
            ->from(route('spending.index'))
            ->post(route('spending.bulk-update'), [])
            ->assertSessionHasErrors();

        $row->refresh();
        $this->assertSame('100000.00', (string) $row->spending);
    }
}
