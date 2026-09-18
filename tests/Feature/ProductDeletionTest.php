<?php

namespace Tests\Feature;

use App\Models\OrderOnlineImportBatch;
use App\Models\PackagingRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingOrder;
use App\Models\SpendingHarian;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Whitelist;
use App\Services\ProductDeletionService;
use Tests\TestCase;

/**
 * Hard delete produk + arsip label spending (keputusan 17 September 2026).
 *
 * DB test = webawanna_test tanpa refresh antar-run (pola project) → semua
 * entitas memakai kode/email unik (uniqid) dan dibersihkan di finally.
 */
class ProductDeletionTest extends TestCase
{
    private function makeAdmin(): User
    {
        return User::create([
            'nama' => 'PD Test Admin '.uniqid(),
            'email' => 'pd-admin-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }

    private function makeAdvertiser(): User
    {
        $adv = User::create([
            'nama' => 'PD Adv '.uniqid(),
            'email' => 'pd-adv-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
        $adv->assignRole('advertiser');

        return $adv;
    }

    private function makeProduct(array $extra = []): Product
    {
        return Product::create(array_merge([
            'code' => 'PD'.strtoupper(substr(uniqid(), -7)),
            'name' => 'Produk Delete Test '.uniqid(),
            'goods_type' => 'core',
            'status' => 'active',
            'unit' => 'pcs',
            'selling_price' => 100000,
            'purchase_price' => 50000,
        ], $extra));
    }

    private function makeWhitelist(User $owner): Whitelist
    {
        return Whitelist::create([
            'nama' => 'WL PD '.uniqid(),
            'kode' => 'WLPD-'.uniqid(),
            'platform' => 'facebook',
            'user_id' => $owner->id,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'aktif',
            'total_topup' => 0,
            'total_spending' => 0,
        ]);
    }

    public function test_destroy_hard_deletes_product_and_code_is_reusable(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $code = $product->code;

        $resp = $this->actingAs($admin)
            ->deleteJson(route('product.destroy', $product));
        $resp->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        // Kode yang sama bisa langsung dipakai produk baru (bug lama: ditolak).
        $resp = $this->actingAs($admin)
            ->postJson(route('product.store'), [
                'code' => $code,
                'name' => 'Produk Baru Nama Sama Kode',
                'goods_type' => 'core',
                'unit' => 'pcs',
                'selling_price' => 90000,
                'status' => 'active',
            ]);
        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('products', ['code' => $code, 'name' => 'Produk Baru Nama Sama Kode']);

        // Cleanup (DB test tanpa refresh — jangan tinggalkan sisa).
        Product::where('code', $code)->delete();
        ProductVariant::where('code', $code)->delete();
    }

    public function test_destroy_relabels_spending_across_advertisers(): void
    {
        $admin = $this->makeAdmin();
        $adv1 = $this->makeAdvertiser();
        $adv2 = $this->makeAdvertiser();
        $product = $this->makeProduct();
        $wl1 = $this->makeWhitelist($adv1);
        $wl2 = $this->makeWhitelist($adv2);

        $sp1 = SpendingHarian::create([
            'tanggal' => '2026-09-01',
            'user_id' => $adv1->id,
            'whitelist_id' => $wl1->id,
            'product_id' => $product->id,
            'product_label' => $product->name,
            'spending' => 100000,
            'lead' => 10,
            'paid' => 4,
        ]);
        $sp2 = SpendingHarian::create([
            'tanggal' => '2026-09-02',
            'user_id' => $adv2->id,
            'whitelist_id' => $wl2->id,
            'product_id' => $product->id,
            'product_label' => $product->name,
            'spending' => 200000,
            'lead' => 20,
            'paid' => 8,
        ]);

        try {
            $resp = $this->actingAs($admin)->deleteJson(route('product.destroy', $product));
            $resp->assertOk()->assertJson(['success' => true]);

            // Baris spending TIDAK hilang — hanya dilepas + diberi label arsip.
            $this->assertDatabaseHas('spending_harians', ['id' => $sp1->id]);
            $this->assertDatabaseHas('spending_harians', ['id' => $sp2->id]);

            $sp1->refresh();
            $sp2->refresh();
            $this->assertNull($sp1->product_id);
            $this->assertNull($sp2->product_id);
            $this->assertSame($product->name.'(Produk dihapus)', $sp1->product_label);
            $this->assertSame($product->name.'(Produk dihapus)', $sp2->product_label);
            $this->assertTrue($sp1->is_orphan_label);
            $this->assertSame($product->name.'(Produk dihapus)', $sp1->display_name);

            // Nilai keuangan tidak berubah.
            $this->assertSame('100000.00', (string) $sp1->spending);
        } finally {
            SpendingHarian::whereIn('id', [$sp1->id, $sp2->id])->delete();
            $wl1->delete();
            $wl2->delete();
            $adv1->delete();
            $adv2->delete();
        }
    }

    public function test_service_delete_reports_relabelled_count(): void
    {
        $adv = $this->makeAdvertiser();
        $product = $this->makeProduct();
        $wl = $this->makeWhitelist($adv);

        SpendingHarian::create([
            'tanggal' => '2026-09-01',
            'user_id' => $adv->id,
            'whitelist_id' => $wl->id,
            'product_id' => $product->id,
            'product_label' => $product->name,
            'spending' => 50000,
            'lead' => 5,
            'paid' => 1,
        ]);

        try {
            $result = app(ProductDeletionService::class)->delete($product);

            $this->assertSame(1, $result['spending_relabelled']);
            $this->assertSame($product->name, $result['product_name']);
            $this->assertDatabaseMissing('products', ['id' => $product->id]);
        } finally {
            SpendingHarian::where('user_id', $adv->id)->delete();
            $wl->delete();
            $adv->delete();
        }
    }

    public function test_destroy_cascades_variants_and_stock_but_keeps_orders(): void
    {
        $admin = $this->makeAdmin();
        $adv = $this->makeAdvertiser();
        $product = $this->makeProduct();
        $wl = $this->makeWhitelist($adv);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'power' => 0,
            'stock' => 50,
            'status' => 'active',
        ]);

        $movement = StockMovement::create([
            'product_variant_id' => $variant->id,
            'date' => now()->format('Y-m-d'),
            'type' => 'in',
            'quantity' => 50,
            'reference' => 'adjustment',
            'reference_id' => $variant->id,
            'note' => 'PD test opening',
        ]);

        $order = ShippingOrder::create([
            'order_online_import_batch_id' => OrderOnlineImportBatch::create([
                'original_filename' => 'pd.csv',
                'stored_path' => 'order-online/pd.csv',
                'sender' => 'PDTEST',
                'status' => 'completed',
                'total_rows' => 1,
                'success_rows' => 1,
            ])->id,
            'order_id' => 'PD-ORD-'.uniqid(),
            'customer_name' => 'PD Customer',
            'phone_normalized' => '62812'.substr(uniqid(), -8),
            'address' => 'Jl. PD Test',
            'province' => 'JAWA BARAT',
            'city' => 'Bandung',
            'subdistrict' => 'Coblong',
            'courier' => 'sicepat',
            'status' => 'real',
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_code' => $product->code,
            'quantity' => 2,
            'amount' => 10000,
            'payment_method' => 'cod',
            'is_cod' => true,
        ]);

        try {
            $resp = $this->actingAs($admin)->deleteJson(route('product.destroy', $product));
            $resp->assertOk();

            // Varian + jurnal ikut hilang (cascade).
            $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
            $this->assertDatabaseMissing('stock_movements', ['id' => $movement->id]);

            // Order TETAP ada; tautan produk dilepas (nominal tidak berubah).
            $order->refresh();
            $this->assertNull($order->product_id);
            $this->assertNull($order->product_variant_id);
            $this->assertSame('10000.00', (string) $order->amount);
        } finally {
            ShippingOrder::where('id', $order->id)->delete();
            OrderOnlineImportBatch::where('sender', 'PDTEST')->delete();
            $wl->delete();
            $adv->delete();
        }
    }

    public function test_destroy_removes_packaging_rules_for_source_and_target(): void
    {
        $admin = $this->makeAdmin();
        $source = $this->makeProduct();
        $target = $this->makeProduct();

        $ruleAsSource = PackagingRule::create([
            'source_product_id' => $source->id,
            'target_product_id' => $target->id,
            'qty_per' => 2,
            'rule_type' => 'additional',
            'is_active' => true,
        ]);
        $ruleAsTarget = PackagingRule::create([
            'source_product_id' => $target->id,
            'target_product_id' => $source->id,
            'qty_per' => 3,
            'rule_type' => 'additional',
            'is_active' => true,
        ]);

        try {
            $resp = $this->actingAs($admin)->deleteJson(route('product.destroy', $source));
            $resp->assertOk();

            // Sebagai source → ter-cascade FK; sebagai target → dihapus manual service.
            $this->assertDatabaseMissing('packaging_rules', ['id' => $ruleAsSource->id]);
            $this->assertDatabaseMissing('packaging_rules', ['id' => $ruleAsTarget->id]);
            $this->assertDatabaseHas('products', ['id' => $target->id]);
        } finally {
            PackagingRule::whereIn('id', [$ruleAsSource->id, $ruleAsTarget->id])->delete();
            Product::where('id', $target->id)->delete();
        }
    }

    public function test_spending_page_shows_deleted_product_label(): void
    {
        // Simulasi baris spending yatim hasil hard-delete produk: tautan lepas,
        // label arsip tersisa. Halaman spending harus menampilkan label itu —
        // bukan "Tidak Diketahui".
        $adv = $this->makeAdvertiser();
        $wl = $this->makeWhitelist($adv);

        $sp = SpendingHarian::create([
            'tanggal' => '2026-09-10',
            'user_id' => $adv->id,
            'whitelist_id' => $wl->id,
            'product_id' => null,
            'product_label' => 'Kacamata Retro(Produk dihapus)',
            'spending' => 75000,
            'lead' => 7,
            'paid' => 3,
        ]);

        try {
            $resp = $this->actingAs($adv)
                ->get(route('spending.index', ['dari' => '2026-09-01', 'sampai' => '2026-09-30']));
            $resp->assertOk();

            $html = $resp->getContent();
            $this->assertStringContainsString('Kacamata Retro(Produk dihapus)', $html);
            // Catatan: assert "Tidak Diketahui" tidak dipakai — string itu juga
            // ada sebagai literal fallback JS di halaman & baris yatim lama dari
            // run sebelumnya (DB test bersama tanpa refresh).
        } finally {
            SpendingHarian::where('id', $sp->id)->delete();
            $wl->delete();
            $adv->delete();
        }
    }
}
