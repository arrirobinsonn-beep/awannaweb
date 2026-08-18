<?php

namespace Tests\Feature;

use App\Models\OrderOnlineImportBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingOrder;
use App\Models\StockMovement;
use App\Models\User;
use Tests\TestCase;

class OperationalReportTest extends TestCase
{
    private function adminUser(): User
    {
        return User::create([
            'nama' => 'Ops Admin',
            'email' => 'ops-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }

    private function makeProduct(int $hpp = 70000): Product
    {
        $code = 'OPS'.strtoupper(substr(uniqid(), -6));
        $product = Product::create([
            'code' => $code,
            'name' => 'Produk Ops',
            'purchase_price' => $hpp,
            'status' => 'active',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'code' => $code,
            'name' => 'Produk Ops',
            'power' => 0,
            'stock' => 0,
            'status' => 'active',
        ]);

        return $product;
    }

    /** Produk kacamata dengan varian ber-power (mis. KMP+1.50) — uji pembeda pcs. */
    private function makePowerProduct(string $code, float $power, int $hpp = 70000): array
    {
        $product = Product::create([
            'code' => $code,
            'name' => 'Kacamata '.$code,
            'purchase_price' => $hpp,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'code' => $code.'+'.number_format($power, 2),
            'name' => 'Kacamata '.$code.' +'.$power,
            'power' => $power,
            'stock' => 100,
            'status' => 'active',
        ]);

        return [$product, $variant];
    }

    private function makeBatch(string $sender): OrderOnlineImportBatch
    {
        return OrderOnlineImportBatch::create([
            'original_filename' => $sender.'.csv',
            'stored_path' => $sender.'.csv',
            'sender' => $sender,
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);
    }

    private function createOrder(
        OrderOnlineImportBatch $batch,
        string $orderId,
        Product $product,
        string $paymentMethod,
        int $quantity = 1,
        int $amount = 169000,
        string $awb = '',
        ?string $createdAt = null,
        string $status = 'real',
        ?string $courier = 'flix-tf'
    ): ShippingOrder {
        $order = ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => $orderId,
            'customer_name' => 'Cust '.$orderId,
            'phone_normalized' => '628'.rand(1000000000, 1999999999),
            'address' => 'Jl. Test No. 1',
            'payment_method' => $paymentMethod,
            'status' => $status,
            'courier' => $courier,
            'product_id' => $product->id,
            'product_variant_id' => $product->variants()->first()->id,
            'product_code' => $product->code,
            'quantity' => $quantity,
            'amount' => $amount,
            'awb' => $awb,
        ]);

        if ($createdAt !== null) {
            ShippingOrder::where('id', $order->id)->update(['created_at' => $createdAt]);
            $order->refresh();
        }

        return $order;
    }

    /** Tanggal unik per test agar isolasi dari data lain di DB bersama. */
    private function uniqueDate(int $dayOffset = 0): string
    {
        return '2026-01-'.str_pad((string) (($dayOffset % 27) + 1), 2, '0', STR_PAD_LEFT);
    }

    public function test_dashboard_general_shows_ops_today_cards(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Barang Keluar Hari Ini')
            ->assertSee('Barang Masuk Hari Ini')
            ->assertSee('Resi Hari Ini')
            ->assertSee('Metode Pembayaran');
    }

    public function test_report_lists_sender_with_amounts_and_totals(): void
    {
        $product = $this->makeProduct(70000);
        $day = $this->uniqueDate(3);
        $gudang = $this->makeBatch('Gudang Pusat '.uniqid());
        $gtm = $this->makeBatch('GTM '.uniqid());

        // Sender Gudang Pusat: 2 order COD (1 resi, 1 belum), qty 2 & 1 → HPP 140000+70000
        $this->createOrder($gudang, 'R1-'.uniqid(), $product, 'cod', 2, 238000, 'AWB1', $day.' 10:00:00');
        $this->createOrder($gudang, 'R2-'.uniqid(), $product, 'cod', 1, 119000, '', $day.' 10:00:00');
        // Sender GTM: 1 order bank_transfer ber-resi, qty 3 → HPP 210000
        $this->createOrder($gtm, 'R3-'.uniqid(), $product, 'bank_transfer', 3, 357000, 'AWB2', $day.' 10:00:00');

        try {
            $response = $this->actingAs($this->adminUser())
                ->get(route('operational-report.index', ['dari' => $day, 'sampai' => $day]))
                ->assertOk()
                ->assertSee($gudang->sender)
                ->assertSee($gtm->sender);

            // Uang masuk per sender: GTM = 357000 & Gudang Pusat = 238000+119000 = 357000
            $response->assertSee('357.000');
            // HPP GTM = 3*70000 = 210000
            $response->assertSee('210.000');
            // Total uang masuk = 714000, HPP total = 420000
            $response->assertSee('714.000');
            $response->assertSee('420.000');
        } finally {
            $gudang->delete();
            $gtm->delete();
        }
    }

    public function test_report_respects_date_range(): void
    {
        $product = $this->makeProduct(70000);
        $batch = $this->makeBatch('DR '.uniqid());

        $today = $this->uniqueDate(5);
        $old = $this->uniqueDate(1);
        $this->createOrder($batch, 'TODAY-1-'.uniqid(), $product, 'cod', 1, 169000, 'AWB1', $today.' 10:00:00');
        $this->createOrder($batch, 'OLD-1-'.uniqid(), $product, 'bank_transfer', 1, 169000, '', $old.' 10:00:00');

        try {
            $this->actingAs($this->adminUser())
                ->get(route('operational-report.index', ['dari' => $today, 'sampai' => $today]))
                ->assertOk()
                ->assertSee($batch->sender)
                // Total uang masuk periode = hanya order hari ini (169000)
                ->assertSee('169.000')
                // HPP hanya 1 order = 70000
                ->assertSee('70.000');
        } finally {
            $batch->delete();
        }
    }

    public function test_report_cards_follow_selected_period(): void
    {
        // Catat jurnal stok pada tanggal unik di masa lalu (bukan hari ini)
        $product = $this->makeProduct(70000);
        $variant = $product->variants()->first();
        $day = $this->uniqueDate(9);

        StockMovement::create([
            'product_variant_id' => $variant->id,
            'date' => $day,
            'type' => 'in',
            'quantity' => 111111,
            'reference' => 'adjustment',
            'reference_id' => $variant->id,
            'note' => 'Test masuk',
        ]);
        StockMovement::create([
            'product_variant_id' => $variant->id,
            'date' => $day,
            'type' => 'out',
            'quantity' => 22222,
            'reference' => 'manual',
            'reference_id' => random_int(1, 999999),
            'note' => 'Test keluar',
        ]);

        try {
            // Periode = tanggal unik → kartu harus menampilkan angka periode tsb
            $this->actingAs($this->adminUser())
                ->get(route('operational-report.index', ['dari' => $day, 'sampai' => $day]))
                ->assertOk()
                ->assertSee('Barang Keluar Periode Terpilih')
                ->assertSee('data-counter="22222"', false)
                ->assertSee('data-counter="111111"', false);

            // Periode hari ini (default) → label "Hari Ini" tetap tampil
            $this->actingAs($this->adminUser())
                ->get(route('operational-report.index'))
                ->assertOk()
                ->assertSee('Barang Keluar Hari Ini');
        } finally {
            StockMovement::where('product_variant_id', $variant->id)
                ->where('note', 'like', 'Test %')
                ->delete();
            $product->delete();
        }
    }

    public function test_report_rows_link_to_batch_detail(): void
    {
        $product = $this->makeProduct(70000);
        $day = $this->uniqueDate(11);
        $batch = $this->makeBatch('Link Sender '.uniqid());
        $this->createOrder($batch, 'LNK-1-'.uniqid(), $product, 'cod', 1, 169000, 'AWB-L', $day.' 10:00:00');

        try {
            $url = route('operational-report.batch', ['batch' => $batch->id, 'dari' => $day, 'sampai' => $day]);
            $this->actingAs($this->adminUser())
                ->get(route('operational-report.index', ['dari' => $day, 'sampai' => $day]))
                ->assertOk()
                // href di-render HTML-escape (& → &amp;) — bandingkan versi ter-escape
                ->assertSee(htmlspecialchars($url), false)
                ->assertSee('/laporan-operasional/'.$batch->id, false);
        } finally {
            $batch->delete();
        }
    }

    public function test_batch_detail_lists_products_variants_and_pcs(): void
    {
        [$kmp, $kmpV] = $this->makePowerProduct('KMPS'.strtoupper(substr(uniqid(), -4)), 1.50);
        $day = $this->uniqueDate(13);
        $batch = $this->makeBatch('Detail Sender '.uniqid());

        // Varian sama (KMP+1.50) tapi promo beda isi: 2 pcs vs 4 pcs → baris TERPISAH
        $this->createOrder($batch, 'D1-'.uniqid(), $kmp, 'cod', 2, 238000, 'AWB-D1', $day.' 10:00:00');
        $o2 = $this->createOrder($batch, 'D2-'.uniqid(), $kmp, 'cod', 4, 476000, '', $day.' 10:00:00');
        // Nama terjual di-set agar mirip promo Dapat N (product_name berisi "N pcs")
        ShippingOrder::where('id', $o2->id)->update(['product_name' => 'Kacamata 4 pcs']);

        try {
            $response = $this->actingAs($this->adminUser())
                ->get(route('operational-report.batch', ['batch' => $batch->id, 'dari' => $day, 'sampai' => $day]))
                ->assertOk()
                ->assertSee($batch->sender);

            // Nama produk master tampil + varian + badge power
            $response->assertSee('Kacamata '.$kmp->code);
            $response->assertSee($kmpV->code);
            // Qty per order terpisah: 2 dan 4
            $response->assertSee('2');
            $response->assertSee('4');
            // Total qty = 6, uang = 238000+476000 = 714000, HPP = 6*70000 = 420000
            $response->assertSee('714.000');
            $response->assertSee('420.000');
            // Total keseluruhan qty 6
            $response->assertSee('6');
        } finally {
            $batch->delete();
            $kmp->delete();
        }
    }

    public function test_batch_detail_respects_date_range(): void
    {
        $product = $this->makeProduct(70000);
        $batch = $this->makeBatch('Range Sender '.uniqid());
        $day = $this->uniqueDate(15);
        $old = $this->uniqueDate(2);
        $this->createOrder($batch, 'RD-1-'.uniqid(), $product, 'cod', 1, 169000, 'AWB-RD', $day.' 10:00:00');
        $this->createOrder($batch, 'RD-2-'.uniqid(), $product, 'bank_transfer', 1, 169000, '', $old.' 10:00:00');

        try {
            $response = $this->actingAs($this->adminUser())
                ->get(route('operational-report.batch', ['batch' => $batch->id, 'dari' => $day, 'sampai' => $day]))
                ->assertOk();

            // Hanya 1 order periode ini → qty total 1, uang 169000, HPP 70000
            $response->assertSee('169.000');
            $response->assertSee('70.000');
            $response->assertDontSee('Tidak ada order pada periode ini.');
        } finally {
            $batch->delete();
        }
    }

    public function test_report_excludes_cancel_belum_diproses_duplikat_and_undeliverable(): void
    {
        $product = $this->makeProduct(70000);
        $day = $this->uniqueDate(17);
        $batch = $this->makeBatch('Filter Sender '.uniqid());

        // Yang DIPROSES: 1 real + 1 tembakan (total uang 169000+169000 = 338000)
        $this->createOrder($batch, 'F1-'.uniqid(), $product, 'cod', 1, 169000, 'AWB-F1', $day.' 10:00:00', 'real');
        $this->createOrder($batch, 'F2-'.uniqid(), $product, 'bank_transfer', 1, 169000, '', $day.' 10:00:00', 'tembakan');

        // Yang TIDAK diproses: cancel, belum_diproses, duplikat, real-undeliverable
        $this->createOrder($batch, 'F3-'.uniqid(), $product, 'cod', 1, 999000, '', $day.' 10:00:00', 'cancel');
        $this->createOrder($batch, 'F4-'.uniqid(), $product, 'cod', 1, 999000, '', $day.' 10:00:00', 'belum_diproses');
        $this->createOrder($batch, 'F5-'.uniqid(), $product, 'cod', 1, 999000, '', $day.' 10:00:00', 'duplikat');
        $this->createOrder($batch, 'F6-'.uniqid(), $product, 'cod', 1, 999000, '', $day.' 10:00:00', 'real', 'undeliverable');

        try {
            // Laporan per sender: hanya 2 order, uang 338000, HPP 2*70000 = 140000
            $this->actingAs($this->adminUser())
                ->get(route('operational-report.index', ['dari' => $day, 'sampai' => $day]))
                ->assertOk()
                ->assertSee('338.000')
                ->assertSee('140.000')
                ->assertDontSee('999.000');

            // Detail batch: qty 2 (bukan 6), uang 338000
            $this->actingAs($this->adminUser())
                ->get(route('operational-report.batch', ['batch' => $batch->id, 'dari' => $day, 'sampai' => $day]))
                ->assertOk()
                ->assertSee('338.000')
                ->assertDontSee('999.000');
        } finally {
            $batch->delete();
        }
    }

    public function test_report_empty_state_for_old_date(): void
    {
        // Tanggal di masa lalu yang tidak punya data (pakai 2019)
        $this->actingAs($this->adminUser())
            ->get(route('operational-report.index', ['dari' => '2019-01-01', 'sampai' => '2019-01-31']))
            ->assertOk()
            ->assertSee('Tidak ada order pada periode ini.');
    }

    public function test_report_totals_match_per_sender_sum(): void
    {
        $product = $this->makeProduct(50000);
        $day = $this->uniqueDate(7);
        $a = $this->makeBatch('Sender A '.uniqid());
        $b = $this->makeBatch('Sender B '.uniqid());

        $this->createOrder($a, 'TA-1-'.uniqid(), $product, 'cod', 1, 100000, 'AWB-A', $day.' 10:00:00');
        $this->createOrder($a, 'TA-2-'.uniqid(), $product, 'bank_transfer', 2, 200000, '', $day.' 10:00:00');
        $this->createOrder($b, 'TB-1-'.uniqid(), $product, 'cod', 1, 100000, 'AWB-B', $day.' 10:00:00');

        try {
            $response = $this->actingAs($this->adminUser())
                ->get(route('operational-report.index', ['dari' => $day, 'sampai' => $day]))
                ->assertOk();

            // Total uang masuk = 400000, HPP = (1+2+1)*50000 = 200000
            $response->assertSee('400.000');
            $response->assertSee('200.000');
            // Resi total = 2, order total = 3 (tampil di kolom resi "2 / 3")
            $response->assertSee('2');
            $response->assertSee('3');
            // COD = 2, Bank Transfer = 1
            $response->assertSee('2');
            $response->assertSee('1');
        } finally {
            $a->delete();
            $b->delete();
        }
    }
}
