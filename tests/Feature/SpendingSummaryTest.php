<?php

namespace Tests\Feature;

use App\Models\Product;
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
}
