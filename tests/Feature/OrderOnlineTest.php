<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\OrderOnlineImportBatch;
use App\Models\PackagingRule;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Purchase;
use App\Models\ProductVariant;
use App\Models\ProductVariantInventory;
use App\Models\ShippingOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CourierRuleService;
use App\Services\OrderOnlineImportService;
use App\Services\OrderTemplateExportService;
use App\Services\ShipmentImportService;
use App\Services\StockService;
use Database\Seeders\CourierRuleSeeder;
use Database\Seeders\ExportTemplateMappingSeeder;
use Database\Seeders\ProductSeeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class OrderOnlineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Export memakai mapping dinamis dari DB (menu Aturan Export) —
        // seed mapping bawaan agar test export tetap identik dgn layout lama.
        $this->seed(ExportTemplateMappingSeeder::class);
    }

    private function adminUser(): User
    {
        return User::create([
            'nama' => 'Test Admin',
            'email' => 'order-test-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }

    private function makeProduct(int $stock = 1000): Product
    {
        $code = 'TST'.strtoupper(substr(uniqid(), -6));
        $product = Product::create([
            'code' => $code,
            'name' => 'Produk Test',
            'status' => 'active',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'code' => $code,
            'name' => 'Produk Test',
            'power' => 0,
            'stock' => 0,
            'status' => 'active',
        ]);

        return $product;
    }

    private function variant(Product $product): ProductVariant
    {
        return ProductVariant::where('product_id', $product->id)->firstOrFail();
    }

    private function writeTempCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'order').'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, [
            'order_id', 'product', 'name', 'phone', 'province', 'status',
            'payment_status', 'payment_method', 'product_code', 'quantity', 'address', 'variation',
        ]);
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }
        fclose($handle);

        return $path;
    }

    private function row(string $orderId, string $phone, string $status, string $paymentStatus, string $productCode, string $address = 'Jl. Test No. 1', string $variation = '', string $product = 'X', string $paymentMethod = 'cod'): array
    {
        return [
            'order_id' => $orderId,
            'product' => $product,
            'name' => 'Cust '.$orderId,
            'phone' => $phone,
            'province' => 'JAWA BARAT',
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'product_code' => $productCode,
            'quantity' => 1,
            'address' => $address,
            'variation' => $variation,
        ];
    }

    public function test_courier_rule_resolution(): void
    {
        $this->seed(CourierRuleSeeder::class);

        $svc = new CourierRuleService;

        $this->assertSame('flix-tf', $svc->resolve('bank_transfer', 'JAWA BARAT'));
        $this->assertSame('sicepat', $svc->resolve('cod', 'BALI'));
        $this->assertSame('flix-idx', $svc->resolve('cod', 'RIAU'));
        $this->assertSame('flix-spx', $svc->resolve('cod', 'KALIMANTAN SELATAN'));
        $this->assertSame('spx', $svc->resolve('cod', 'PULAU TAK DIKENAL'));
    }

    public function test_order_status_mapping(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $uid = uniqid();

        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811', 'processing', 'paid', $product->code),
            $this->row($uid.'-2', '0812', 'pending', 'paid', $product->code),
            $this->row($uid.'-3', '0813', 'pending', 'unpaid', $product->code),
            $this->row($uid.'-4', '0814', 'cancelled', 'paid', $product->code),
            $this->row($uid.'-5', '0815', 'completed', 'paid', $product->code),
        ]);

        $svc = new OrderOnlineImportService;
        $result = $svc->import($path, 'eresgestore');

        $map = ShippingOrder::whereIn('order_id', [$uid.'-1', $uid.'-2', $uid.'-3', $uid.'-4', $uid.'-5'])
            ->pluck('status', 'order_id')->toArray();
        $this->assertSame('real', $map[$uid.'-1'] ?? null);
        $this->assertSame('tembakan', $map[$uid.'-2'] ?? null);
        $this->assertSame('belum_diproses', $map[$uid.'-3'] ?? null);
        $this->assertSame('cancel', $map[$uid.'-4'] ?? null);
        $this->assertArrayNotHasKey($uid.'-5', $map);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_courier_null_for_non_exportable_statuses(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $uid = uniqid();

        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811', 'pending', 'unpaid', $product->code),
            $this->row($uid.'-2', '0812', 'cancelled', 'paid', $product->code),
            $this->row($uid.'-3', '0813', 'processing', 'paid', $product->code),
        ]);

        $svc = new OrderOnlineImportService;
        $svc->import($path, 'eresgestore');

        $pending = ShippingOrder::where('order_id', $uid.'-1')->first();
        $cancel = ShippingOrder::where('order_id', $uid.'-2')->first();
        $real = ShippingOrder::where('order_id', $uid.'-3')->first();

        $this->assertNull($pending->courier);
        $this->assertNull($cancel->courier);
        $this->assertNotNull($real->courier);
    }

    private function makeBatch(): OrderOnlineImportBatch
    {
        return OrderOnlineImportBatch::create([
            'original_filename' => 'a.csv',
            'stored_path' => 'a.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);
    }

    private function createOldOrder(array $data, string $createdAt): ShippingOrder
    {
        $order = ShippingOrder::create($data);
        ShippingOrder::where('id', $order->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $order->refresh();
    }

    public function test_duplicate_detected_within_window(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();

        $this->createOldOrder([
            'order_online_import_batch_id' => $this->makeBatch()->id,
            'order_id' => 'OLD-1',
            'customer_name' => 'Dup',
            'phone_normalized' => '6281234567000',
            'address' => 'Jl. Merdeka No. 1',
            'product_code' => $product->code,
            'product_id' => $product->id,
            'status' => 'belum_diproses',
            'courier' => null,
        ], now()->subDays(3));

        $uid = uniqid();
        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '6281234567000', 'pending', 'unpaid', $product->code, 'Jl. Merdeka No. 1'),
        ]);

        $svc = new OrderOnlineImportService;
        $result = $svc->import($path, 'eresgestore');

        $new = ShippingOrder::where('order_id', $uid.'-1')->first();
        $this->assertSame('duplikat', $new->status);
        $this->assertNull($new->courier);
        $this->assertSame(1, $result['duplicates']);
    }

    public function test_duplicate_within_same_file(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $uid = uniqid();

        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811111111', 'pending', 'unpaid', $product->code),
            $this->row($uid.'-2', '0811111111', 'pending', 'unpaid', $product->code),
        ]);

        $svc = new OrderOnlineImportService;
        $result = $svc->import($path, 'eresgestore');

        $first = ShippingOrder::where('order_id', $uid.'-1')->first();
        $second = ShippingOrder::where('order_id', $uid.'-2')->first();

        $this->assertSame('belum_diproses', $first->status);
        $this->assertSame('duplikat', $second->status);
        $this->assertSame(1, $result['duplicates']);
    }

    public function test_old_record_is_repeat_order_not_duplicate(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();

        $this->createOldOrder([
            'order_online_import_batch_id' => $this->makeBatch()->id,
            'order_id' => 'OLD-1',
            'customer_name' => 'Repeat',
            'phone_normalized' => '6281234567000',
            'address' => 'Jl. Merdeka No. 1',
            'product_code' => $product->code,
            'product_id' => $product->id,
            'status' => 'belum_diproses',
            'courier' => null,
        ], now()->subDays(20));

        $uid = uniqid();
        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '6281234567000', 'pending', 'unpaid', $product->code, 'Jl. Merdeka No. 1'),
        ]);

        $svc = new OrderOnlineImportService;
        $result = $svc->import($path, 'eresgestore');

        $new = ShippingOrder::where('order_id', $uid.'-1')->first();
        $this->assertSame('belum_diproses', $new->status);
        $this->assertSame(0, $result['duplicates']);
    }

    public function test_bank_transfer_repeat_order_not_duplicate(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();

        // Order pertama COD (2 hari lalu)
        $this->createOldOrder([
            'order_online_import_batch_id' => $this->makeBatch()->id,
            'order_id' => 'OLD-1',
            'customer_name' => 'Promo',
            'phone_normalized' => '6281234567000',
            'address' => 'Jl. Merdeka No. 1',
            'product_code' => $product->code,
            'product_id' => $product->id,
            'payment_method' => 'cod',
            'status' => 'belum_diproses',
            'courier' => null,
        ], now()->subDays(2));

        // Order kedua bank_transfer, phone+produk+alamat sama, order_id BEDA → repeat order, BUKAN duplikat
        $uid = uniqid();
        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '6281234567000', 'pending', 'paid', $product->code, 'Jl. Merdeka No. 1', '', 'X', 'bank_transfer'),
        ]);

        $svc = new OrderOnlineImportService;
        $result = $svc->import($path, 'eresgestore');

        $new = ShippingOrder::where('order_id', $uid.'-1')->first();
        $this->assertSame('tembakan', $new->status);
        $this->assertNotNull($new->courier);
        $this->assertSame(0, $result['duplicates']);
    }

    public function test_cod_after_bank_transfer_is_duplicate(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();

        // Order pertama bank_transfer (3 hari lalu) → jadi source
        $this->createOldOrder([
            'order_online_import_batch_id' => $this->makeBatch()->id,
            'order_id' => 'BT-OLD-1',
            'customer_name' => 'BT Source',
            'phone_normalized' => '6281234567000',
            'address' => 'Jl. Merdeka No. 1',
            'product_code' => $product->code,
            'product_id' => $product->id,
            'payment_method' => 'bank_transfer',
            'status' => 'real',
            'courier' => 'flix-tf',
        ], now()->subDays(3));

        // Order kedua COD, signature sama, order_id BEDA, ≤14 hari → DUPLIKAT
        $uid = uniqid();
        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '6281234567000', 'processing', 'paid', $product->code, 'Jl. Merdeka No. 1'),
        ]);

        $svc = new OrderOnlineImportService;
        $result = $svc->import($path, 'eresgestore');

        $new = ShippingOrder::where('order_id', $uid.'-1')->first();
        $this->assertSame('duplikat', $new->status);
        $this->assertNull($new->courier);
        $this->assertSame(1, $result['duplicates']);
    }

    public function test_real_cod_duplicate_within_same_file(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $uid = uniqid();

        // Dua baris real COD, phone+produk+alamat sama, order_id BEDA → baris ke-2 duplikat
        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811111111', 'processing', 'paid', $product->code),
            $this->row($uid.'-2', '0811111111', 'processing', 'paid', $product->code),
        ]);

        $svc = new OrderOnlineImportService;
        $result = $svc->import($path, 'eresgestore');

        $first = ShippingOrder::where('order_id', $uid.'-1')->first();
        $second = ShippingOrder::where('order_id', $uid.'-2')->first();

        $this->assertSame('real', $first->status);
        $this->assertSame('duplikat', $second->status);
        $this->assertNull($second->courier);
        $this->assertSame(1, $result['duplicates']);
    }

    public function test_bank_transfer_duplicate_within_same_file_is_repeat(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $uid = uniqid();

        // Dua baris bank_transfer, signature sama, order_id BEDA → dua-duanya repeat (bukan duplikat)
        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811111111', 'processing', 'paid', $product->code, 'Jl. Test No. 1', '', 'X', 'bank_transfer'),
            $this->row($uid.'-2', '0811111111', 'processing', 'paid', $product->code, 'Jl. Test No. 1', '', 'X', 'bank_transfer'),
        ]);

        $svc = new OrderOnlineImportService;
        $result = $svc->import($path, 'eresgestore');

        $first = ShippingOrder::where('order_id', $uid.'-1')->first();
        $second = ShippingOrder::where('order_id', $uid.'-2')->first();

        $this->assertSame('real', $first->status);
        $this->assertSame('real', $second->status);
        $this->assertSame(0, $result['duplicates']);
    }

    public function test_reimport_same_order_id_is_not_duplicate(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $orderId = uniqid('REV-');

        $svc = new OrderOnlineImportService;

        // Import pertama: real (masuk source)
        $path1 = $this->writeTempCsv([
            $this->row($orderId, '081111', 'processing', 'paid', $product->code),
        ]);
        $svc->import($path1, 'eresgestore');

        // Re-import order_id yang SAMA → bukan duplikat, tapi double_real (anti double export)
        $path2 = $this->writeTempCsv([
            $this->row($orderId, '081111', 'processing', 'paid', $product->code),
        ]);
        $r2 = $svc->import($path2, 'eresgestore');

        $this->assertSame(0, $r2['duplicates']);
        $this->assertSame(1, $r2['double_real']);
    }

    public function test_reimport_real_deletes_old_belum_diproses(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $orderId = uniqid('RE-');

        $svc = new OrderOnlineImportService;

        $path1 = $this->writeTempCsv([
            $this->row($orderId, '081111', 'pending', 'unpaid', $product->code),
        ]);
        $r1 = $svc->import($path1, 'eresgestore');
        $this->assertSame(1, $r1['inserted']);
        $this->assertSame('belum_diproses', ShippingOrder::where('order_id', $orderId)->value('status'));

        $path2 = $this->writeTempCsv([
            $this->row($orderId, '081111', 'processing', 'paid', $product->code),
        ]);
        $r2 = $svc->import($path2, 'eresgestore');

        $this->assertSame(1, $r2['deleted']);
        $this->assertSame(1, $r2['inserted']);
        $this->assertSame(0, $r2['double_real']);

        $rows = ShippingOrder::where('order_id', $orderId)->get();
        $this->assertSame(1, $rows->count());
        $this->assertSame('real', $rows->first()->status);
    }

    public function test_reimport_real_does_not_create_double_real(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $orderId = uniqid('RD-');

        $svc = new OrderOnlineImportService;

        $path1 = $this->writeTempCsv([
            $this->row($orderId, '081222', 'processing', 'paid', $product->code),
        ]);
        $r1 = $svc->import($path1, 'eresgestore');
        $this->assertSame(1, $r1['inserted']);

        $path2 = $this->writeTempCsv([
            $this->row($orderId, '081222', 'processing', 'paid', $product->code),
        ]);
        $r2 = $svc->import($path2, 'eresgestore');

        $this->assertSame(1, $r2['double_real']);
        $this->assertSame(0, $r2['inserted']);
        $this->assertSame(
            1,
            ShippingOrder::where('order_id', $orderId)
                ->whereIn('status', ShippingOrder::EXPORTABLE_STATUSES)
                ->count(),
        );
    }

    public function test_reimport_real_keeps_old_duplikat(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $orderId = uniqid('RDK-');

        // Baris lama berstatus duplikat (order_id sama dgn yang akan di-reimport)
        $this->createOldOrder([
            'order_online_import_batch_id' => $this->makeBatch()->id,
            'order_id' => $orderId,
            'customer_name' => 'Dup Lama',
            'phone_normalized' => '6281234567000',
            'address' => 'Jl. Test No. 1',
            'product_code' => $product->code,
            'product_id' => $product->id,
            'status' => 'duplikat',
            'courier' => null,
        ], now()->subDays(2));

        $svc = new OrderOnlineImportService;

        $path = $this->writeTempCsv([
            $this->row($orderId, '081111', 'processing', 'paid', $product->code),
        ]);
        $r = $svc->import($path, 'eresgestore');

        // Baris duplikat TIDAK dihapus; real baru tetap masuk
        $this->assertSame(0, $r['deleted']);
        $this->assertSame(1, $r['inserted']);
        $this->assertSame(0, $r['double_real']);

        $rows = ShippingOrder::where('order_id', $orderId)->orderBy('id')->get();
        $this->assertSame(2, $rows->count());
        $this->assertSame(['duplikat', 'real'], $rows->pluck('status')->all());
    }

    public function test_import_resolves_product_id(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $uid = uniqid();

        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811', 'processing', 'paid', $product->code),
            $this->row($uid.'-2', '0812', 'processing', 'paid', 'UNKNOWN'),
        ]);

        $svc = new OrderOnlineImportService;
        $svc->import($path, 'eresgestore');

        $known = ShippingOrder::where('order_id', $uid.'-1')->first();
        $unknown = ShippingOrder::where('order_id', $uid.'-2')->first();

        $this->assertNotNull($known->product_id);
        $this->assertSame($product->code, $known->product_code);
        $this->assertNotNull($known->product_variant_id);
        $this->assertSame($this->variant($product)->id, $known->product_variant_id);
        $this->assertNull($unknown->product_id);
        $this->assertNull($unknown->product_variant_id);
    }

    public function test_order_page_renders(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);
        ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => '123',
            'customer_name' => 'Test Nama',
            'courier' => 'flix-tf',
            'status' => 'real',
        ]);

        $this->actingAs($this->adminUser())
            ->get(route('orders.index', ['batch' => $batch->id]))
            ->assertOk()
            ->assertSee('Test Nama')
            ->assertSee('FLIK — flix-tf');
    }

    public function test_order_show_page_renders(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);
        $product = $this->makeProduct();
        $order = ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => 'SHOW-'.uniqid(),
            'customer_name' => 'Nama Detail',
            'phone' => '081234567890',
            'phone_normalized' => '81234567890',
            'province' => 'JAWA BARAT',
            'city' => 'BANDUNG',
            'address' => 'Jl. Detail No. 1',
            'product_name' => 'Produk Detail',
            'product_code' => $this->variant($product)->code,
            'product_id' => $product->id,
            'product_variant_id' => $this->variant($product)->id,
            'quantity' => 2,
            'amount' => 169000,
            'shipping_cost' => 50000,
            'payment_method' => 'cod',
            'is_cod' => true,
            'status' => 'real',
            'courier' => 'sicepat',
            'raw_payload' => ['order_id' => 'SHOW-1', 'utm_source' => 'meta'],
        ]);

        $this->actingAs($this->adminUser())
            ->get(route('orders.show', $order->id))
            ->assertOk()
            ->assertSee($order->order_id)
            ->assertSee('Nama Detail')
            ->assertSee('081234567890')
            ->assertSee($this->variant($product)->code)
            ->assertSee('raw_payload')
            ->assertSee('Jl. Detail No. 1');
    }

    public function test_index_filters_by_product_code(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 2,
            'success_rows' => 2,
        ]);
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $code1 = $this->variant($p1)->code;
        $code2 = $this->variant($p2)->code;

        ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => 'F1-'.uniqid(),
            'customer_name' => 'Cust Filter Satu',
            'product_code' => $code1,
            'product_id' => $p1->id,
            'status' => 'real',
        ]);
        ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => 'F2-'.uniqid(),
            'customer_name' => 'Cust Filter Dua',
            'product_code' => $code2,
            'product_id' => $p2->id,
            'status' => 'real',
        ]);

        $this->actingAs($this->adminUser())
            ->get(route('orders.index', ['batch' => $batch->id, 'product_code' => $code1]))
            ->assertOk()
            ->assertSee('Cust Filter Satu')
            ->assertDontSee('Cust Filter Dua');
    }

    public function test_flik_export_separated_by_courier_and_status(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 3,
            'success_rows' => 3,
        ]);

        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');

        $this->createOrder($batch->id, 'TF-1', 'TF Customer', 'flix-tf', 'real', $product->id, $product->code, 1);
        $this->createOrder($batch->id, 'IDX-1', 'IDX Customer', 'flix-idx', 'tembakan', $product->id, $product->code, 1);
        $this->createOrder($batch->id, 'BAD-1', 'Bad Customer', 'flix-tf', 'belum_diproses', $product->id, $product->code, 1);

        $svc = new OrderTemplateExportService;

        $this->assertSame(
            ['flix-tf', 'flix-idx', 'flix-sicepat', 'flix-spx'],
            $svc->couriersForTemplate(OrderTemplateExportService::TEMPLATE_FLIK)
        );

        $tfResponse = $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
        $tfRows = $this->readXlsxRows($tfResponse);
        $this->assertCount(2, $tfRows);
        $this->assertSame('Kode Warehouse', $tfRows[0][0]);
        $this->assertSame('eresgestore', $tfRows[1][0]);
        $this->assertSame('TF Customer', $tfRows[1][1]);

        $idxResponse = $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-idx');
        $idxRows = $this->readXlsxRows($idxResponse);
        $this->assertCount(2, $idxRows);
        $this->assertSame('eresgestore', $idxRows[1][0]);
        $this->assertSame('IDX Customer', $idxRows[1][1]);
    }

    public function test_export_reduces_stock_idempotent(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');
        $this->createOrder($batch->id, 'TF-1', 'TF Customer', 'flix-tf', 'real', $product->id, $product->code, 3);

        $svc = new OrderTemplateExportService;

        $this->assertSame(100, $this->app->make(StockService::class)->stockOf($this->variant($product)->id));

        $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
        $this->assertSame(97, $this->app->make(StockService::class)->stockOf($this->variant($product)->id));

        $order = ShippingOrder::where('order_online_import_batch_id', $batch->id)->first();

        $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
        $this->assertSame(97, $this->app->make(StockService::class)->stockOf($this->variant($product)->id));
        $this->assertSame(1, StockMovement::where('reference', 'order_online')->where('reference_id', $order->id)->where('type', 'out')->count());
    }

    public function test_export_skips_insufficient_stock(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(2);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 2, 10000, 'adjustment');
        $order = $this->createOrder($batch->id, 'TF-1', 'TF Customer', 'flix-tf', 'real', $product->id, $product->code, 5);

        $svc = new OrderTemplateExportService;
        $response = $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        $rows = $this->readXlsxRows($response);
        $this->assertCount(1, $rows);

        $order->refresh();
        $this->assertNotNull($order->stock_note);
        $this->assertStringContainsString('Stok tidak mencukupi', $order->stock_note);
        $this->assertSame(2, $this->app->make(StockService::class)->stockOf($this->variant($product)->id));
    }

    public function test_undeliverable_returns_reserved_stock(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');
        $order = $this->createOrder($batch->id, 'TF-1', 'TF Customer', 'flix-tf', 'real', $product->id, $product->code, 3);

        $svc = new OrderTemplateExportService;
        $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        $this->assertSame(97, $this->app->make(StockService::class)->stockOf($this->variant($product)->id));
        $this->assertSame(1, StockMovement::where('reference', 'order_online')->where('reference_id', $order->id)->where('type', 'out')->count());

        $this->actingAs($this->adminUser())
            ->put(route('orders.update', $order->id), [
                'courier' => 'undeliverable',
                'courier_note' => 'Alamat tidak ditemukan',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('undeliverable', $order->courier);
        $this->assertSame(100, $this->app->make(StockService::class)->stockOf($this->variant($product)->id));
        $this->assertSame(0, StockMovement::where('reference', 'order_online')->where('reference_id', $order->id)->where('type', 'out')->count());
    }

    public function test_undeliverable_to_normal_courier_does_not_double_stock(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');
        $order = $this->createOrder($batch->id, 'TF-1', 'TF Customer', 'flix-tf', 'real', $product->id, $product->code, 3);

        $svc = new OrderTemplateExportService;
        $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
        $this->assertSame(97, $this->app->make(StockService::class)->stockOf($this->variant($product)->id));

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->put(route('orders.update', $order->id), ['courier' => 'undeliverable'])
            ->assertRedirect();
        $this->assertSame(100, $this->app->make(StockService::class)->stockOf($this->variant($product)->id));

        $this->actingAs($admin)
            ->put(route('orders.update', $order->id), ['courier' => 'flix-tf'])
            ->assertRedirect();
        $order->refresh();
        $this->assertSame('flix-tf', $order->courier);
        $this->assertSame(100, $this->app->make(StockService::class)->stockOf($this->variant($product)->id));
    }

    public function test_undeliverable_restores_non_default_variant_and_keeps_variant(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(100);
        $default = $this->variant($product);
        $nonDefault = ProductVariant::create([
            'product_id' => $product->id,
            'code' => $product->code.'+1.75',
            'name' => 'Ukuran Plus',
            'power' => 1.75,
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->app->make(StockService::class)->recordIn($nonDefault->id, now()->format('Y-m-d'), 100, 10000, 'adjustment', $nonDefault->id);
        $this->app->make(StockService::class)->recordIn($default->id, now()->format('Y-m-d'), 50, 10000, 'adjustment', $default->id);

        $order = ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => 'VAR-1',
            'customer_name' => 'VAR Customer',
            'phone_normalized' => '6281234567890',
            'province' => 'JAWA BARAT',
            'city' => 'Bandung',
            'subdistrict' => 'Coblong',
            'courier' => 'flix-tf',
            'status' => 'real',
            'product_id' => $product->id,
            'product_variant_id' => $nonDefault->id,
            'product_code' => $product->code,
            'quantity' => 3,
            'amount' => 10000,
            'payment_method' => 'cod',
            'is_cod' => true,
        ]);

        $svc = new OrderTemplateExportService;
        $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        $this->assertSame(97, $this->app->make(StockService::class)->stockOf($nonDefault->id));
        $this->assertSame(50, $this->app->make(StockService::class)->stockOf($default->id));

        $this->actingAs($this->adminUser())
            ->put(route('orders.update', $order->id), [
                'courier' => 'undeliverable',
                'courier_note' => 'Alamat tidak ditemukan',
                'product_code' => $product->code,
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('undeliverable', $order->courier);
        $this->assertSame($nonDefault->id, $order->product_variant_id);
        $this->assertSame(100, $this->app->make(StockService::class)->stockOf($nonDefault->id));
        $this->assertSame(50, $this->app->make(StockService::class)->stockOf($default->id));
        $this->assertSame(0, StockMovement::where('reference', 'order_online')->where('reference_id', $order->id)->where('type', 'out')->count());
    }

    public function test_courier_edit_with_same_product_code_keeps_variant(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(100);
        $nonDefault = ProductVariant::create([
            'product_id' => $product->id,
            'code' => $product->code.'+1.75',
            'name' => 'Ukuran Plus',
            'power' => 1.75,
            'stock' => 0,
            'status' => 'active',
        ]);
        $this->app->make(StockService::class)->recordIn($nonDefault->id, now()->format('Y-m-d'), 100, 10000, 'adjustment', $nonDefault->id);

        $order = ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => 'VAR-2',
            'customer_name' => 'VAR Customer 2',
            'phone_normalized' => '6281234567890',
            'province' => 'JAWA BARAT',
            'city' => 'Bandung',
            'subdistrict' => 'Coblong',
            'courier' => 'flix-tf',
            'status' => 'real',
            'product_id' => $product->id,
            'product_variant_id' => $nonDefault->id,
            'product_code' => $product->code,
            'quantity' => 1,
            'amount' => 10000,
            'payment_method' => 'cod',
            'is_cod' => true,
        ]);

        $this->actingAs($this->adminUser())
            ->put(route('orders.update', $order->id), [
                'courier' => 'spx',
                'product_code' => $product->code,
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('spx', $order->courier);
        $this->assertSame($nonDefault->id, $order->product_variant_id);
    }

    public function test_product_code_change_with_existing_reservation_restores_old_variant_stock(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(100);
        $nonDefault = ProductVariant::create([
            'product_id' => $product->id,
            'code' => $product->code.'+1.75',
            'name' => 'Ukuran Plus',
            'power' => 1.75,
            'stock' => 0,
            'status' => 'active',
        ]);
        $this->app->make(StockService::class)->recordIn($nonDefault->id, now()->format('Y-m-d'), 100, 10000, 'adjustment', $nonDefault->id);

        $order = ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => 'VAR-3',
            'customer_name' => 'VAR Customer 3',
            'phone_normalized' => '6281234567890',
            'province' => 'JAWA BARAT',
            'city' => 'Bandung',
            'subdistrict' => 'Coblong',
            'courier' => 'flix-tf',
            'status' => 'real',
            'product_id' => $product->id,
            'product_variant_id' => $nonDefault->id,
            'product_code' => $product->code,
            'quantity' => 2,
            'amount' => 10000,
            'payment_method' => 'cod',
            'is_cod' => true,
        ]);

        $svc = new OrderTemplateExportService;
        $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
        $this->assertSame(98, $this->app->make(StockService::class)->stockOf($nonDefault->id));

        $other = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($other)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment', $this->variant($other)->id);

        $this->actingAs($this->adminUser())
            ->put(route('orders.update', $order->id), [
                'product_code' => $other->code,
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(100, $this->app->make(StockService::class)->stockOf($nonDefault->id));
        $this->assertSame(0, StockMovement::where('reference', 'order_online')->where('reference_id', $order->id)->where('type', 'out')->count());
        $this->assertSame($this->variant($other)->id, $order->product_variant_id);
    }

    public function test_warehouse_code_maps_product_code(): void
    {
        $svc = new OrderTemplateExportService;

        // Gudang utama produk (pivot is_primary) memenangkan mapping kode lama.
        $this->assertSame('Aurora', $svc->warehouseFor('KSP', 'eresgestore'));
        $this->assertSame('GTM', $svc->warehouseFor('SH', 'eresgestore'));
        $this->assertSame('GTM', $svc->warehouseFor('sh', 'eresgestore'));
        $this->assertSame('Gudang Pusat', $svc->warehouseFor('KMP', 'eresgestore'));
        $this->assertSame('eresgestore', $svc->warehouseFor(null, 'eresgestore'));
        $this->assertSame('Aurora', $svc->warehouseFor('KSP', null));

        // Produk tanpa gudang utama → mapping kode lama, lalu sender.
        $this->assertSame('Aurora', $svc->warehouseFor('KSP', 'x'));
    }

    public function test_warehouse_export_uses_primary_inventory(): void
    {
        $this->ensureCatalog();
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $origPrimaryId = $kmp->primaryInventoryId();
        $inv = Inventory::create(['name' => 'TEST WH '.uniqid()]);

        try {
            ProductInventory::where('product_id', $kmp->id)->update(['is_primary' => false]);
            ProductInventory::updateOrCreate(
                ['product_id' => $kmp->id, 'inventory_id' => $inv->id],
                ['is_primary' => true]
            );

            $svc = new OrderTemplateExportService;
            $this->assertSame($inv->name, $svc->warehouseFor('KMP', 'eresgestore'));
            $this->assertSame($inv->name, $svc->warehouseFor('KMP+1.50', 'eresgestore'));
        } finally {
            // Kembalikan gudang utama asli agar test lain tidak terpengaruh
            ProductInventory::where('product_id', $kmp->id)
                ->where('inventory_id', $inv->id)
                ->delete();
            ProductInventory::where('product_id', $kmp->id)->update(['is_primary' => false]);
            ProductInventory::updateOrCreate(
                ['product_id' => $kmp->id, 'inventory_id' => (int) $origPrimaryId],
                ['is_primary' => true]
            );
        }
    }

    public function test_flik_kode_warehouse_from_product_code(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 3,
            'success_rows' => 3,
        ]);

        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');

        $this->createOrder($batch->id, 'KSP-1', 'KSP Customer', 'flix-tf', 'real', $product->id, 'KSP', 1);
        $this->createOrder($batch->id, 'SH-1', 'SH Customer', 'flix-tf', 'real', $product->id, 'SH', 1);
        $this->createOrder($batch->id, 'KMP-1', 'KMP Customer', 'flix-tf', 'real', $product->id, 'KMP', 1);

        $svc = new OrderTemplateExportService;
        $response = $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        $this->assertSame('application/zip', $response->headers->get('Content-Type'));

        $files = $this->readZipFiles($response);
        $this->assertCount(3, $files);

        $aurora = collect($files)->first(fn ($rows) => ($rows[1][0] ?? null) === 'Aurora');
        $this->assertNotNull($aurora);
        $this->assertSame('KSP Customer', $aurora[1][1]);

        $gtm = collect($files)->first(fn ($rows) => ($rows[1][0] ?? null) === 'GTM');
        $this->assertNotNull($gtm);
        $this->assertSame('SH Customer', $gtm[1][1]);

        // KMP (gudang utama Gudang Pusat) → file Gudang Pusat
        $pusat = collect($files)->first(fn ($rows) => ($rows[1][0] ?? null) === 'Gudang Pusat');
        $this->assertNotNull($pusat);
        $this->assertSame('KMP Customer', $pusat[1][1]);
    }

    public function test_spx_export_phone_starts_with_8_and_uppercase_region(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');
        $this->createOrder($batch->id, 'SPX-1', 'SPX Customer', 'spx', 'tembakan', $product->id, $product->code, 1, 'jawa barat', 'bandung', 'coblong');

        $svc = new OrderTemplateExportService;
        $response = $svc->download($batch, OrderTemplateExportService::TEMPLATE_SPX);

        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));

        $rows = $this->readXlsxRows($response);
        $this->assertCount(2, $rows);
        $this->assertSame('81234567890', $rows[1][2]);
        $this->assertSame('JAWA BARAT', $rows[1][4]);
        $this->assertSame('BANDUNG', $rows[1][5]);
        $this->assertSame('COBLONG', $rows[1][6]);
    }

    public function test_spx_filename_contains_sender(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');
        $this->createOrder($batch->id, 'SPX-1', 'SPX Customer', 'spx', 'tembakan', $product->id, $product->code, 1);

        $svc = new OrderTemplateExportService;
        $response = $svc->download($batch, OrderTemplateExportService::TEMPLATE_SPX);

        $this->assertStringContainsString(
            date('Ymd').'_spx_eresgestore_'.$batch->id.'.xlsx',
            $this->filenameFromDisposition($response)
        );
    }

    public function test_tembakan_always_uses_spx(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $uid = uniqid();

        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811111111', 'pending', 'paid', $product->code, 'Jl. Jawa Barat'),
            $this->row($uid.'-2', '0822222222', 'pending', 'paid', $product->code, 'Jl. Riau'),
            $this->row($uid.'-3', '0833333333', 'processing', 'paid', $product->code, 'Jl. Jakarta'),
        ]);

        $svc = new OrderOnlineImportService;
        $svc->import($path, 'eresgestore');

        $orders = ShippingOrder::whereIn('order_id', [$uid.'-1', $uid.'-2', $uid.'-3'])->get()->keyBy('order_id');

        $this->assertSame('spx', $orders[$uid.'-1']->courier);
        $this->assertSame('spx', $orders[$uid.'-2']->courier);

        $this->assertNotSame('spx', $orders[$uid.'-3']->courier);
    }

    public function test_product_meta_account_split(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $uid = uniqid();

        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811', 'processing', 'paid', $product->code, 'Jl. Satu', '', 'A.3 Kacamata Multifokus - 13722'),
            $this->row($uid.'-2', '0812', 'processing', 'paid', $product->code, 'Jl. Dua', '', 'X'),
        ]);

        $svc = new OrderOnlineImportService;
        $svc->import($path, 'eresgestore');

        $orders = ShippingOrder::whereIn('order_id', [$uid.'-1', $uid.'-2'])->get()->keyBy('order_id');

        $this->assertSame('A.3 Kacamata Multifokus', $orders[$uid.'-1']->product_name);
        $this->assertSame('13722', $orders[$uid.'-1']->meta_account);
        $this->assertNull($orders[$uid.'-2']->meta_account);
        $this->assertSame('X', $orders[$uid.'-2']->product_name);
    }

    public function test_dapat_qty_override_and_product_name(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $uid = uniqid();

        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811', 'processing', 'paid', $product->code, 'Jl. Satu', 'Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 43-44 Tahun Plus +1.25'),
            $this->row($uid.'-2', '0812', 'processing', 'paid', $product->code, 'Jl. Dua', 'Ukuran: Usia 45-47 Tahun Plus +1.50'),
        ]);

        $svc = new OrderOnlineImportService;
        $svc->import($path, 'eresgestore');

        $orders = ShippingOrder::whereIn('order_id', [$uid.'-1', $uid.'-2'])->get()->keyBy('order_id');

        $this->assertSame(2, $orders[$uid.'-1']->quantity);
        $this->assertSame('X 2 pcs', $orders[$uid.'-1']->product_name);
        $this->assertSame(1, $orders[$uid.'-2']->quantity);
        $this->assertSame('X', $orders[$uid.'-2']->product_name);
    }

    public function test_product_code_stores_variant_code(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $product = $this->makeProduct();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'code' => $product->code.'+1.75',
            'name' => 'Ukuran Plus',
            'power' => 1.75,
            'stock' => 0,
            'status' => 'active',
        ]);
        $uid = uniqid();

        $path = $this->writeTempCsv([
            $this->row($uid.'-1', '0811', 'processing', 'paid', $product->code, 'Jl. Satu', 'Ukuran: Usia 45-47 Tahun Plus +1.75'),
        ]);

        $svc = new OrderOnlineImportService;
        $svc->import($path, 'eresgestore');

        $order = ShippingOrder::where('order_id', $uid.'-1')->first();

        $this->assertSame($product->code.'+1.75', $order->product_code);
        $this->assertSame($variant->id, $order->product_variant_id);
    }

    public function test_warehouse_for_variant_code(): void
    {
        $svc = new OrderTemplateExportService;

        $this->assertSame('Aurora', $svc->warehouseFor('KSP+1.50', 'eresgestore'));
        $this->assertSame('GTM', $svc->warehouseFor('SH+1.25', 'eresgestore'));
        $this->assertSame('Gudang Pusat', $svc->warehouseFor('KMP+1.50', 'eresgestore'));
        $this->assertSame('Aurora', $svc->warehouseFor('KSP', null));
    }

    public function test_import_sh_product_always_gets_flix(): void
    {
        $this->seed(CourierRuleSeeder::class);
        $uid = uniqid();
        // Phone unik per run — kalau tetap, signature duplikat (phone+produk+alamat)
        // dari run sebelumnya menandai order ini `duplikat` → courier null.
        $ph1 = '628'.random_int(100000000, 999999999);
        $ph2 = '628'.random_int(100000000, 999999999);

        $path = $this->writeTempCsv([
            // SH + cod + JAWA BARAT → rule produk SH (flix-tf) menang atas sicepat (provinsi)
            $this->row($uid.'-1', $ph1, 'processing', 'paid', 'SH'),
            // KMP + cod + JAWA BARAT → tetap rule provinsi sicepat
            $this->row($uid.'-2', $ph2, 'processing', 'paid', 'KMP'),
        ]);

        $svc = new OrderOnlineImportService;
        $svc->import($path, 'eresgestore');

        $orders = ShippingOrder::whereIn('order_id', [$uid.'-1', $uid.'-2'])->get()->keyBy('order_id');

        $this->assertSame('flix-tf', $orders[$uid.'-1']->courier);
        $this->assertSame('sicepat', $orders[$uid.'-2']->courier);
    }

    public function test_warehouse_rule_overrides_primary_inventory(): void
    {
        $rule = \App\Models\WarehouseRule::create([
            'product_code' => 'KMP',
            'warehouse' => 'WH TEST '.uniqid(),
            'is_active' => true,
        ]);

        try {
            $svc = new OrderTemplateExportService;
            $this->assertSame($rule->warehouse, $svc->warehouseFor('KMP', 'eresgestore'));
            $this->assertSame($rule->warehouse, $svc->warehouseFor('KMP+1.50', 'eresgestore'));

            // Nonaktif → jatuh ke gudang utama produk (Gudang Pusat)
            $rule->update(['is_active' => false]);
            $this->assertSame('Gudang Pusat', (new OrderTemplateExportService)->warehouseFor('KMP', 'eresgestore'));
        } finally {
            $rule->delete();
        }
    }

    public function test_export_dimensions_and_courier_note_per_template(): void
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');
        $this->createOrder($batch->id, 'DIM-1', 'DIM Customer', 'flix-tf', 'real', $product->id, $product->code, 1);

        $svc = new OrderTemplateExportService;

        // FLIK
        $flik = $this->readXlsxRows($svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf'));
        $this->assertSame(10, (int) $flik[1][11]);
        $this->assertSame(8, (int) $flik[1][12]);
        $this->assertSame(6, (int) $flik[1][13]);
        $this->assertSame(OrderTemplateExportService::DEFAULT_COURIER_NOTE, $flik[1][9]);

        // SiCepat
        $batch2 = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);
        $this->createOrder($batch2->id, 'DIM-2', 'DIM Customer 2', 'sicepat', 'real', $product->id, $product->code, 1);
        $sicepat = $this->readXlsxRows($svc->download($batch2, OrderTemplateExportService::TEMPLATE_SICEPAT));
        $this->assertSame(10, (int) $sicepat[1][12]);
        $this->assertSame(8, (int) $sicepat[1][13]);
        $this->assertSame(6, (int) $sicepat[1][14]);
        $this->assertSame(OrderTemplateExportService::DEFAULT_COURIER_NOTE, $sicepat[1][20]);

        // SPX
        $batch3 = OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);
        $this->createOrder($batch3->id, 'DIM-3', 'DIM Customer 3', 'spx', 'real', $product->id, $product->code, 1);
        $spx = $this->readXlsxRows($svc->download($batch3, OrderTemplateExportService::TEMPLATE_SPX));
        $this->assertSame(10, (int) $spx[1][13]);
        $this->assertSame(8, (int) $spx[1][14]);
        $this->assertSame(6, (int) $spx[1][15]);
        $this->assertSame(OrderTemplateExportService::DEFAULT_COURIER_NOTE, $spx[1][21]);
    }

    public function test_flik_export_single_phone_62_only(): void
    {
        $batch = $this->newBatch();
        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');
        $this->createOrder($batch->id, 'HP-1', 'HP Customer', 'flix-tf', 'real', $product->id, $product->code, 1);

        $svc = new OrderTemplateExportService;
        $rows = $this->readXlsxRows($svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf'));

        $this->assertSame('No HP Pelanggan (mulai dengan "62")', $rows[0][2]);
        $this->assertFalse(collect($rows[0])->contains('No HP Pelanggan (mulai dengan "8")'));
        $this->assertSame('6281234567890', $rows[1][2]);
    }

    public function test_export_kacamata_product_name_with_power(): void
    {
        $this->ensureCatalog();
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $variant = ProductVariant::where('code', 'KMP+1.5')->firstOrFail();

        $this->app->make(StockService::class)->recordIn($variant->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');

        $batch = $this->newBatch();
        $this->createOrder($batch->id, 'NM-1', 'Nm Customer', 'flix-tf', 'real', $kmp->id, 'KMP+1.5', 2);
        ShippingOrder::where('order_online_import_batch_id', $batch->id)->update(['product_name' => 'Kacamata 2 pcs', 'product_variant_id' => $variant->id]);

        $svc = new OrderTemplateExportService;
        $rows = $this->readXlsxRows($svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf'));

        $this->assertSame('Kacamata +1.50 2 pcs', $rows[1][15]);
    }

    public function test_export_non_kacamata_name_unchanged(): void
    {
        $batch = $this->newBatch();
        $product = $this->makeProduct(100);
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');
        $this->createOrder($batch->id, 'NM-2', 'Nm Customer 2', 'flix-tf', 'real', $product->id, $product->code, 2);
        ShippingOrder::where('order_online_import_batch_id', $batch->id)->update(['product_name' => 'Produk Test 2 pcs']);

        $svc = new OrderTemplateExportService;
        $rows = $this->readXlsxRows($svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf'));

        $this->assertSame('Produk Test 2 pcs', $rows[1][15]);
    }

    private function ensureCatalog(): void
    {
        $this->seed(ProductSeeder::class);
    }

    private function newBatch(): OrderOnlineImportBatch
    {
        return OrderOnlineImportBatch::create([
            'original_filename' => 'test.csv',
            'stored_path' => 'order-online/test.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);
    }

    public function test_export_kacamata_reduces_box_lap(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $box = ProductVariant::where('code', 'BOX')->firstOrFail();
        $lap = ProductVariant::where('code', 'LAP')->firstOrFail();

        $batch = $this->newBatch();
        $order = $this->createOrder($batch->id, 'PKG-KMP-1', 'Pkg Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 4);
        $variantId = $order->product_variant_id;
        $kdf = ProductVariant::where('product_id', Product::where('code', 'KDF')->firstOrFail()->id)
            ->where('power', ProductVariant::find($variantId)->power)
            ->first();

        $before = [
            'kmp' => $stock->stockOf($variantId),
            'kdf' => $stock->stockOf($kdf->id),
            'box' => $stock->stockOf($box->id),
            'lap' => $stock->stockOf($lap->id),
        ];

        $svc = new OrderTemplateExportService;
        $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
        $svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        // Promo "Beli 1 Dapat 2": qty 4 KMP → 2 KMP + 2 KDF keluar (+ 2 BOX + 2 LAP)
        $this->assertSame($before['kmp'] - 2, $stock->stockOf($variantId));
        $this->assertSame($before['kdf'] - 2, $stock->stockOf($kdf->id));
        $this->assertSame($before['box'] - 2, $stock->stockOf($box->id));
        $this->assertSame($before['lap'] - 2, $stock->stockOf($lap->id));
        $this->assertSame(4, StockMovement::where('reference', 'order_online')->where('reference_id', $order->id)->where('type', 'out')->count());

        $order->refresh();
        $this->assertNull($order->stock_note);
    }

    public function test_export_kmp_promo_splits_to_kdf(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $kdf = Product::where('code', 'KDF')->firstOrFail();

        // qty 2 (Beli 1 Dapat 2) → 1 KMP + 1 KDF keluar
        $kmpVariant = $kmp->defaultVariant();
        $kdfVariant = ProductVariant::where('product_id', $kdf->id)
            ->where('power', $kmpVariant->power)
            ->firstOrFail();

        $batch = $this->newBatch();
        $order = $this->createOrder($batch->id, 'PKG-KMP2-'.uniqid(), 'KMP2 Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 2);
        $order->update(['product_variant_id' => $kmpVariant->id]);

        $beforeKmp = $stock->stockOf($kmpVariant->id);
        $beforeKdf = $stock->stockOf($kdfVariant->id);

        (new OrderTemplateExportService)->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        $this->assertSame($beforeKmp - 1, $stock->stockOf($kmpVariant->id));
        $this->assertSame($beforeKdf - 1, $stock->stockOf($kdfVariant->id));

        // qty 1 (tanpa promo) → 1 KMP + 0 KDF
        $batch1 = $this->newBatch();
        $order1 = $this->createOrder($batch1->id, 'PKG-KMP1-'.uniqid(), 'KMP1 Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 1);
        $order1->update(['product_variant_id' => $kmpVariant->id]);

        $beforeKmp1 = $stock->stockOf($kmpVariant->id);
        $beforeKdf1 = $stock->stockOf($kdfVariant->id);

        (new OrderTemplateExportService)->download($batch1, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        $this->assertSame($beforeKmp1 - 1, $stock->stockOf($kmpVariant->id));
        $this->assertSame($beforeKdf1, $stock->stockOf($kdfVariant->id));
    }

    public function test_export_kbj_splits_to_kdf_and_packaging(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kbj = Product::where('code', 'KBJ')->firstOrFail();
        $kbjVariant = ProductVariant::where('code', 'KBJ+1.25')->firstOrFail();
        $kdfVariant = ProductVariant::where('code', 'KDF+1.25')->firstOrFail();
        $box = ProductVariant::where('code', 'BOX')->firstOrFail();
        $lap = ProductVariant::where('code', 'LAP')->firstOrFail();

        $batch = $this->newBatch();
        $order = $this->createOrder($batch->id, 'PKG-KBJ-1', 'Pkg Customer', 'flix-tf', 'real', $kbj->id, 'KBJ', 3);
        $order->update(['product_variant_id' => $kbjVariant->id]);

        $before = [
            'kbj' => $stock->stockOf($kbjVariant->id),
            'kdf' => $stock->stockOf($kdfVariant->id),
            'box' => $stock->stockOf($box->id),
            'lap' => $stock->stockOf($lap->id),
        ];

        (new OrderTemplateExportService)->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        $this->assertSame($before['kbj'] - 2, $stock->stockOf($kbjVariant->id));
        $this->assertSame($before['kdf'] - 1, $stock->stockOf($kdfVariant->id));
        $this->assertSame($before['box'] - 1, $stock->stockOf($box->id));
        $this->assertSame($before['lap'] - 1, $stock->stockOf($lap->id));

        $order->refresh();
        $this->assertNull($order->stock_note);
    }

    public function test_export_kbj_qty_one_skips_packaging(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kbj = Product::where('code', 'KBJ')->firstOrFail();
        $kbjVariant = ProductVariant::where('code', 'KBJ+1.25')->firstOrFail();
        $kdfVariant = ProductVariant::where('code', 'KDF+1.25')->firstOrFail();
        $box = ProductVariant::where('code', 'BOX')->firstOrFail();
        $lap = ProductVariant::where('code', 'LAP')->firstOrFail();

        $batch = $this->newBatch();
        $order = $this->createOrder($batch->id, 'PKG-KBJ-0', 'Pkg Customer', 'flix-tf', 'real', $kbj->id, 'KBJ', 1);
        $order->update(['product_variant_id' => $kbjVariant->id]);

        $before = [
            'kbj' => $stock->stockOf($kbjVariant->id),
            'kdf' => $stock->stockOf($kdfVariant->id),
            'box' => $stock->stockOf($box->id),
            'lap' => $stock->stockOf($lap->id),
        ];

        (new OrderTemplateExportService)->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        $this->assertSame($before['kbj'] - 1, $stock->stockOf($kbjVariant->id));
        $this->assertSame($before['kdf'], $stock->stockOf($kdfVariant->id));
        $this->assertSame($before['box'], $stock->stockOf($box->id));
        $this->assertSame($before['lap'], $stock->stockOf($lap->id));
        $this->assertSame(1, StockMovement::where('reference', 'order_online')->where('reference_id', $order->id)->where('type', 'out')->count());
    }

    public function test_undeliverable_restores_packaging_stock(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $box = ProductVariant::where('code', 'BOX')->firstOrFail();
        $lap = ProductVariant::where('code', 'LAP')->firstOrFail();

        $batch = $this->newBatch();
        $order = $this->createOrder($batch->id, 'PKG-UND-1', 'Pkg Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 4);
        $variantId = $order->product_variant_id;

        $before = [
            'kmp' => $stock->stockOf($variantId),
            'box' => $stock->stockOf($box->id),
            'lap' => $stock->stockOf($lap->id),
        ];

        (new OrderTemplateExportService)->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
        $this->assertSame($before['box'] - 2, $stock->stockOf($box->id));

        $this->actingAs($this->adminUser())
            ->put(route('orders.update', $order->id), ['courier' => 'undeliverable'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('undeliverable', $order->courier);
        $this->assertSame($before['kmp'], $stock->stockOf($variantId));
        $this->assertSame($before['box'], $stock->stockOf($box->id));
        $this->assertSame($before['lap'], $stock->stockOf($lap->id));
        $this->assertSame(0, StockMovement::where('reference', 'order_online')->where('reference_id', $order->id)->where('type', 'out')->count());
    }

    public function test_export_skips_when_box_unregistered(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $box = ProductVariant::where('code', 'BOX')->firstOrFail();

        $batch = $this->newBatch();
        $order = $this->createOrder($batch->id, 'PKG-NOBOX', 'Pkg Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 4);
        $variantId = $order->product_variant_id;
        $beforeKmp = $stock->stockOf($variantId);

        $box->update(['status' => 'inactive']);
        try {
            (new OrderTemplateExportService)->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
        } finally {
            $box->update(['status' => 'active']);
        }

        $order->refresh();
        $this->assertNotNull($order->stock_note);
        $this->assertStringContainsString('BOX belum terdaftar', $order->stock_note);
        $this->assertSame($beforeKmp, $stock->stockOf($variantId));
    }

    public function test_export_excludes_orders_with_awb(): void
    {
        $this->ensureCatalog();
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $batch = $this->newBatch();

        $shipped = $this->createOrder($batch->id, 'AWB-1', 'Awb Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 1);
        $shipped->update(['awb' => 'FLIK123456789']);
        $pending = $this->createOrder($batch->id, 'NOAWB-1', 'NoAwb Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 1);

        $response = (new OrderTemplateExportService)->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
        $rows = $this->readXlsxRows($response);

        $this->assertCount(2, $rows); // hanya header + 1 order (tanpa AWB)
        $this->assertSame('NoAwb Customer', $rows[1][1]);

        // order ber-AWB tidak di-reserve stoknya (tidak ada jurnal out)
        $this->assertSame(0, StockMovement::where('reference', 'order_online')->where('reference_id', $shipped->id)->where('type', 'out')->count());
        $this->assertSame(1, StockMovement::where('reference', 'order_online')->where('reference_id', $pending->id)->where('type', 'out')->count());
    }

    public function test_update_rejected_when_order_has_awb(): void
    {
        $this->ensureCatalog();
        $batch = $this->newBatch();
        $order = $this->createOrder($batch->id, 'AWB-EDIT', 'Edit Customer', 'flix-tf', 'real', null, null, 1);
        $order->update(['awb' => 'FLIK001']);

        $this->actingAs($this->adminUser())
            ->put(route('orders.update', $order->id), ['courier' => 'spx'])
            ->assertSessionHasErrors('order');

        $order->refresh();
        $this->assertSame('flix-tf', $order->courier);
    }

    public function test_shipment_import_reduces_box_lap(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $variant = $kmp->defaultVariant();
        $kdf = ProductVariant::where('product_id', Product::where('code', 'KDF')->firstOrFail()->id)
            ->where('power', $variant->power)
            ->first();
        $box = ProductVariant::where('code', 'BOX')->firstOrFail();
        $lap = ProductVariant::where('code', 'LAP')->firstOrFail();

        $before = [
            'kmp' => $stock->stockOf($variant->id),
            'kdf' => $stock->stockOf($kdf->id),
            'box' => $stock->stockOf($box->id),
            'lap' => $stock->stockOf($lap->id),
        ];

        $path = $this->writeShipmentCsv('SPX'.uniqid(), 'Kacamata Multifokus Photocromic', 4);
        $result = (new ShipmentImportService)->import($path);

        $this->assertSame(1, $result['inserted']);
        $this->assertCount(0, $result['unmatched']);
        $this->assertSame($before['kmp'] - 2, $stock->stockOf($variant->id));
        $this->assertSame($before['kdf'] - 2, $stock->stockOf($kdf->id));
        $this->assertSame($before['box'] - 2, $stock->stockOf($box->id));
        $this->assertSame($before['lap'] - 2, $stock->stockOf($lap->id));
    }

    private function writeShipmentCsv(string $trackingNumber, string $productName, int $qty): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ship').'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, [
            'Tracking No.', 'Recipient Name', 'Recipient Phone', 'Detail Address',
            'City', 'Province', 'Postal Code', 'Parcel Value', 'Item Name',
            'Item Quantity', 'COD Amount', 'Tanggal',
        ]);
        fputcsv($handle, [
            $trackingNumber, 'R. Penerima', '08123456789', 'Jl. Kirim No. 5',
            'Bandung', 'JAWA BARAT', '40123', '238000', $productName,
            $qty, '0', '2026-08-09',
        ]);        fclose($handle);


        return $path;
    }

    public function test_packaging_rule_qty_per_changes_reduction(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $box = ProductVariant::where('code', 'BOX')->firstOrFail();
        $lap = ProductVariant::where('code', 'LAP')->firstOrFail();

        // Ubah rasio BOX jadi 1:1 (default 1:2)
        PackagingRule::whereHas('sourceProduct', fn ($q) => $q->where('code', 'KMP'))
            ->whereHas('targetProduct', fn ($q) => $q->where('code', 'BOX'))
            ->update(['qty_per' => 1]);

        $batch = $this->newBatch();
        $order = $this->createOrder($batch->id, 'PKG-Q1-'.uniqid(), 'Q1 Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 4);
        $variantId = $order->product_variant_id;

        $before = [
            'kmp' => $stock->stockOf($variantId),
            'box' => $stock->stockOf($box->id),
            'lap' => $stock->stockOf($lap->id),
        ];

        (new OrderTemplateExportService)->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        // KMP terpecah (Beli 1 Dapat 2): qty 4 → 2 KMP + 2 KDF; BOX jadi 1:1 → 4
        $this->assertSame($before['kmp'] - 2, $stock->stockOf($variantId));
        $this->assertSame($before['box'] - 4, $stock->stockOf($box->id));   // rasio diubah jadi 1:1
        $this->assertSame($before['lap'] - 2, $stock->stockOf($lap->id));    // rasio LAP tetap 1:2
    }

    public function test_packaging_rule_inactive_skips_reduction(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $box = ProductVariant::where('code', 'BOX')->firstOrFail();
        $lap = ProductVariant::where('code', 'LAP')->firstOrFail();

        // Nonaktifkan rule BOX untuk KMP → BOX tidak berkurang, LAP tetap
        PackagingRule::whereHas('sourceProduct', fn ($q) => $q->where('code', 'KMP'))
            ->whereHas('targetProduct', fn ($q) => $q->where('code', 'BOX'))
            ->update(['is_active' => false]);

        $batch = $this->newBatch();
        $order = $this->createOrder($batch->id, 'PKG-IA-'.uniqid(), 'IA Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 4);
        $variantId = $order->product_variant_id;

        $before = [
            'kmp' => $stock->stockOf($variantId),
            'box' => $stock->stockOf($box->id),
            'lap' => $stock->stockOf($lap->id),
        ];

        (new OrderTemplateExportService)->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');

        // KMP terpecah (Beli 1 Dapat 2): qty 4 → 2 KMP + 2 KDF; rule BOX nonaktif → BOX tetap
        $this->assertSame($before['kmp'] - 2, $stock->stockOf($variantId));
        $this->assertSame($before['box'], $stock->stockOf($box->id));
        $this->assertSame($before['lap'] - 2, $stock->stockOf($lap->id));
    }

    public function test_gudang_page_renders_and_adjusts_consumable_stock(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $inv = Inventory::create(['name' => 'TEST INV '.uniqid()]);

        // Barang Pasti muncul di gudang tempatnya TERDAFTAR (many-to-many)
        $kth = Product::where('code', 'KTH')->firstOrFail();
        ProductInventory::firstOrCreate(
            ['product_id' => $kth->id, 'inventory_id' => $inv->id],
            ['is_primary' => false]
        );

        $this->actingAs($user)
            ->get(route('gudang.index', ['inventory_id' => $inv->id]))
            ->assertOk()
            ->assertSee('Barang Pasti')
            ->assertSee('Barang Inti')
            ->assertSee('Barang Additional')
            ->assertSee('Kertas Thermal')
            ->assertSee($inv->name) // gudang terpilih tampil di header & picker
            ->assertSee('Aturan Kemasan');

        $variant = $kth->defaultVariant();
        $stock = $this->app->make(StockService::class);
        $before = $stock->stockOf($variant->id);

        $this->actingAs($user)
            ->post(route('gudang.adjust'), [
                'product_variant_id' => $variant->id,
                'inventory_id' => $inv->id,
                'direction' => 'in',
                'quantity' => 25,
                'note' => 'Tambah gulungan baru',
            ])
            ->assertRedirect();
        $this->assertSame(25, $stock->stockOf($variant->id, $inv->id));
        $this->assertSame($before + 25, $stock->stockOf($variant->id));

        $this->actingAs($user)
            ->post(route('gudang.adjust'), [
                'product_variant_id' => $variant->id,
                'inventory_id' => $inv->id,
                'direction' => 'out',
                'quantity' => 5,
            ])
            ->assertRedirect();
        $this->assertSame(20, $stock->stockOf($variant->id, $inv->id));
        $this->assertSame($before + 20, $stock->stockOf($variant->id));
    }

    public function test_gudang_adjust_stock_per_inventory(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $invA = Inventory::create(['name' => 'TEST INV A '.uniqid()]);
        $invB = Inventory::create(['name' => 'TEST INV B '.uniqid()]);

        $kth = Product::where('code', 'KTH')->firstOrFail();
        $variant = $kth->defaultVariant();
        $stock = $this->app->make(StockService::class);
        $before = $stock->stockOf($variant->id);

        $post = fn (int $invId, string $dir, int $qty) => $this->actingAs($user)
            ->post(route('gudang.adjust'), [
                'product_variant_id' => $variant->id,
                'inventory_id' => $invId,
                'direction' => $dir,
                'quantity' => $qty,
            ])
            ->assertRedirect();

        $post($invA->id, 'in', 10);
        $post($invB->id, 'in', 7);

        $this->assertSame(10, $stock->stockOf($variant->id, $invA->id));
        $this->assertSame(7, $stock->stockOf($variant->id, $invB->id));
        $this->assertSame($before + 17, $stock->stockOf($variant->id));

        // Kurangi khusus gudang A — gudang B tidak terpengaruh
        $post($invA->id, 'out', 3);
        $this->assertSame(7, $stock->stockOf($variant->id, $invA->id));
        $this->assertSame(7, $stock->stockOf($variant->id, $invB->id));
        $this->assertSame($before + 14, $stock->stockOf($variant->id));
    }

    public function test_gudang_packaging_rule_crud(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        // KSP → KDF dipilih karena kombinasi ini TIDAK di-seed (KMP & KBJ → KDF sudah ada)
        $ksp = Product::where('code', 'KSP')->firstOrFail();
        $kdf = Product::where('code', 'KDF')->firstOrFail();
        $inv = Inventory::create(['name' => 'TEST INV '.uniqid()]);

        // Rule global (semua gudang)
        $this->actingAs($user)
            ->post(route('gudang.packaging-store'), [
                'source_product_id' => $ksp->id,
                'target_product_id' => $kdf->id,
                'inventory_id' => '',
                'qty_per' => 3,
                'rule_type' => 'split',
            ])
            ->assertRedirect();

        $rule = PackagingRule::where('source_product_id', $ksp->id)
            ->where('target_product_id', $kdf->id)
            ->whereNull('inventory_id')
            ->first();
        $this->assertNotNull($rule);
        $this->assertSame(3, $rule->qty_per);
        $this->assertSame('split', $rule->rule_type);

        // Kombinasi sama + gudang sama ditolak
        $this->actingAs($user)
            ->post(route('gudang.packaging-store'), [
                'source_product_id' => $ksp->id,
                'target_product_id' => $kdf->id,
                'qty_per' => 1,
            ])
            ->assertSessionHasErrors('rule');

        // Kombinasi sama tapi gudang berbeda → boleh (rule per gudang)
        $this->actingAs($user)
            ->post(route('gudang.packaging-store'), [
                'source_product_id' => $ksp->id,
                'target_product_id' => $kdf->id,
                'inventory_id' => $inv->id,
                'qty_per' => 4,
                'rule_type' => 'additional',
            ])
            ->assertRedirect();
        $invRule = PackagingRule::where('source_product_id', $ksp->id)
            ->where('target_product_id', $kdf->id)
            ->where('inventory_id', $inv->id)
            ->first();
        $this->assertNotNull($invRule);
        $this->assertSame(4, $invRule->qty_per);
        $this->assertSame('additional', $invRule->rule_type);

        // Update qty_per + nonaktifkan
        $this->actingAs($user)
            ->put(route('gudang.packaging-update', $rule), ['qty_per' => 5, 'is_active' => 0])
            ->assertRedirect();
        $rule->refresh();
        $this->assertSame(5, $rule->qty_per);
        $this->assertFalse($rule->is_active);

        $this->actingAs($user)
            ->delete(route('gudang.packaging-destroy', $rule))
            ->assertRedirect();
        $this->assertNull(PackagingRule::find($rule->id));

        PackagingRule::where('inventory_id', $inv->id)->delete();
    }

    public function test_packaging_rule_per_inventory_overrides_global(): void
    {
        $this->ensureCatalog();
        $stock = $this->app->make(StockService::class);
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $box = ProductVariant::where('code', 'BOX')->firstOrFail();
        $origInv = $kmp->primaryInventoryId();
        $invA = Inventory::create(['name' => 'TEST RULE A '.uniqid()]);
        $invB = Inventory::create(['name' => 'TEST RULE B '.uniqid()]);

        // Pindahkan gudang UTAMA KMP (pivot is_primary) antar gudang test
        $setPrimary = function (Product $p, int $invId) {
            ProductInventory::where('product_id', $p->id)->update(['is_primary' => false]);
            ProductInventory::updateOrCreate(
                ['product_id' => $p->id, 'inventory_id' => $invId],
                ['is_primary' => true]
            );
        };

        // Pastikan rule global KMP→BOX 1:2 aktif
        $boxRule = PackagingRule::whereHas('sourceProduct', fn ($q) => $q->where('code', 'KMP'))
            ->whereHas('targetProduct', fn ($q) => $q->where('code', 'BOX'))
            ->whereNull('inventory_id')
            ->firstOrFail();
        $boxRule->update(['qty_per' => 2, 'is_active' => true]);

        // Rule khusus gudang A: KMP→BOX 1:1
        $specific = PackagingRule::create([
            'source_product_id' => $kmp->id,
            'target_product_id' => $boxRule->target_product_id,
            'inventory_id' => $invA->id,
            'qty_per' => 1,
            'is_active' => true,
        ]);

        try {
            // Produk KMP gudang utama A → rule khusus 1:1 (BOX −4)
            $setPrimary($kmp, $invA->id);
            $batchA = $this->newBatch();
            $this->createOrder($batchA->id, 'RULE-A-'.uniqid(), 'Rule A Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 4);
            $beforeBox = $stock->stockOf($box->id);
            (new OrderTemplateExportService)->download($batchA, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
            $this->assertSame($beforeBox - 4, $stock->stockOf($box->id));

            // Produk KMP gudang utama B (tanpa rule khusus) → global 1:2 (BOX −2)
            $setPrimary($kmp, $invB->id);
            $batchB = $this->newBatch();
            $this->createOrder($batchB->id, 'RULE-B-'.uniqid(), 'Rule B Customer', 'flix-tf', 'real', $kmp->id, 'KMP', 4);
            $beforeBox2 = $stock->stockOf($box->id);
            (new OrderTemplateExportService)->download($batchB, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
            $this->assertSame($beforeBox2 - 2, $stock->stockOf($box->id));
        } finally {
            ProductInventory::where('product_id', $kmp->id)
                ->where('inventory_id', $invA->id)
                ->orWhere('inventory_id', $invB->id)
                ->delete();
            $setPrimary($kmp, (int) $origInv);
            $specific->delete();
            $boxRule->update(['qty_per' => 2, 'is_active' => true]);
        }
    }

    public function test_product_belongs_to_multiple_warehouses_with_per_warehouse_stock(): void
    {
        $this->ensureCatalog();
        $product = $this->makeProduct();
        $variant = $this->variant($product);
        $invA = Inventory::create(['name' => 'TEST M2M A '.uniqid()]);
        $invB = Inventory::create(['name' => 'TEST M2M B '.uniqid()]);

        // 1 produk terdaftar di 2 gudang (A = utama)
        ProductInventory::create(['product_id' => $product->id, 'inventory_id' => $invA->id, 'is_primary' => true]);
        ProductInventory::create(['product_id' => $product->id, 'inventory_id' => $invB->id, 'is_primary' => false]);

        $this->assertSame(2, $product->inventories()->count());
        $this->assertSame($invA->id, $product->primaryInventoryId());

        // Stok per gudang tercatat terpisah + cache tersinkron
        $stock = $this->app->make(StockService::class);
        $stock->recordIn($variant->id, now()->format('Y-m-d'), 10, 10000, 'adjustment', $variant->id, 'in A', null, $invA->id);
        $stock->recordIn($variant->id, now()->format('Y-m-d'), 6, 10000, 'adjustment', $variant->id + 1000, 'in B', null, $invB->id);

        $this->assertSame(10, $stock->stockOf($variant->id, $invA->id));
        $this->assertSame(6, $stock->stockOf($variant->id, $invB->id));
        $this->assertSame(16, $stock->stockOf($variant->id));
        $this->assertSame(10, $variant->stockAt($invA->id));
        $this->assertSame(6, $variant->stockAt($invB->id));

        // Keluar dari gudang A tidak menyentuh stok gudang B
        $stock->recordOut($variant->id, now()->format('Y-m-d'), 4, 'adjustment', $variant->id + 2000, 'out A', null, $invA->id);
        $this->assertSame(6, $stock->stockOf($variant->id, $invA->id));
        $this->assertSame(6, $stock->stockOf($variant->id, $invB->id));

        // Halaman gudang A menampilkan produk ini, gudang lain tidak (kecuali terdaftar)
        $user = $this->adminUser();
        $this->actingAs($user)
            ->get(route('gudang.index', ['inventory_id' => $invA->id]))
            ->assertSee($product->name);

        // Detach dari gudang B → cache stok B ikut hilang, total jurnal tetap
        ProductInventory::where('product_id', $product->id)->where('inventory_id', $invB->id)->delete();
        ProductVariantInventory::where('product_variant_id', $variant->id)->where('inventory_id', $invB->id)->delete();
        $this->assertSame(6, $stock->stockOf($variant->id, $invA->id));
        $this->assertSame(12, $stock->stockOf($variant->id)); // total jurnal tidak berubah
    }

    public function test_gudang_page_shows_restock_warning(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $pusat = Inventory::orderBy('id')->first(); // gudang induk KMP

        $kmp->update(['min_stock' => 99999]); // jauh di atas total stok → perlu restock
        $this->actingAs($user)
            ->get(route('gudang.index', ['inventory_id' => $pusat->id]))
            ->assertSee('Perlu Restock');

        $kmp->update(['min_stock' => 0]);
        $this->actingAs($user)
            ->get(route('gudang.index', ['inventory_id' => $pusat->id]))
            ->assertDontSee('Perlu Restock');
    }

    public function test_gudang_page_scoped_per_warehouse(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $gtm = Inventory::where('name', 'GTM')->firstOrFail();
        $aurora = Inventory::where('name', 'Aurora')->firstOrFail();

        // Tanpa pilihan gudang → hanya picker, tanpa isi produk
        $this->actingAs($user)
            ->get(route('gudang.index'))
            ->assertOk()
            ->assertSee('Pilih Gudang')
            ->assertDontSee('Kertas Thermal');

        // GTM → Barang Pasti + Barang Inti HANYA SH (count 1); Aurora → HANYA KSP.
        // (nama produk lintas-gudang tetap tampil di dropdown "Tambah Produk ke Gudang"
        // karena memang bisa di-attach — jadi scoping dicek via jumlah per section)
        $this->actingAs($user)
            ->get(route('gudang.index', ['inventory_id' => $gtm->id]))
            ->assertOk()
            ->assertSee('Barang Pasti: 3')
            ->assertSee('Barang Inti: 1')
            ->assertSee('Barang Additional: 0')
            ->assertSee('Kertas Thermal')
            ->assertSee('Shendara Herbal')
            ->assertSee('Aurora'); // produk KSP (Aurora) tersedia untuk di-attach di GTM

        // Aurora → KSP (Sporty), tanpa SH di tabel inti
        $this->actingAs($user)
            ->get(route('gudang.index', ['inventory_id' => $aurora->id]))
            ->assertOk()
            ->assertSee('Barang Inti: 1')
            ->assertSee('Kacamata Sporty Photocromic')
            ->assertSee('GTM'); // produk SH (GTM) tersedia untuk di-attach di Aurora
    }

    public function test_gudang_attach_existing_product_to_warehouse(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $inv = Inventory::create(['name' => 'TEST INV '.uniqid()]);
        $stock = $this->app->make(StockService::class);
        $product = $this->makeProduct(); // produk sudah dibuat di halaman Produk

        $this->actingAs($user)
            ->post(route('gudang.product.attach'), [
                'product_id' => $product->id,
                'inventory_id' => $inv->id,
                'is_primary' => 1,
                'stock_awal' => 12,
            ])
            ->assertRedirect(route('gudang.index', ['inventory_id' => $inv->id]));

        // Produk TIDAK dibuat ulang — hanya terdaftar di gudang ini (menjadi gudang utama)
        $this->assertSame(1, Product::where('code', $product->code)->count());
        $this->assertSame((int) $inv->id, (int) $product->refresh()->primaryInventoryId());
        $this->assertSame(1, $product->inventories()->count());

        // Varian default produk ikut serta; stok awal tercatat ke gudang tsb
        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        $this->assertSame(12, $stock->stockOf($variant->id, $inv->id));
        $this->assertSame(12, $variant->stockAt($inv->id));

        // Attach dua kali ditolak
        $this->actingAs($user)
            ->post(route('gudang.product.attach'), [
                'product_id' => $product->id,
                'inventory_id' => $inv->id,
            ])
            ->assertSessionHasErrors('attach');
    }

    public function test_gudang_product_warehouses_update_and_detach(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $invA = Inventory::create(['name' => 'TEST WH A '.uniqid()]);
        $invB = Inventory::create(['name' => 'TEST WH B '.uniqid()]);

        $product = $this->makeProduct();
        ProductInventory::create(['product_id' => $product->id, 'inventory_id' => $invA->id, 'is_primary' => true]);
        ProductInventory::create(['product_id' => $product->id, 'inventory_id' => $invB->id, 'is_primary' => false]);

        // Pindahkan gudang utama ke B + lepas A sekaligus
        $this->actingAs($user)
            ->put(route('gudang.product.warehouses', $product), [
                'inventory_ids' => [$invB->id],
                'primary_inventory_id' => $invB->id,
            ])
            ->assertRedirect(route('gudang.index', ['inventory_id' => $invB->id]));

        $product->refresh();
        $this->assertSame((int) $invB->id, (int) $product->primaryInventoryId());
        $this->assertSame(1, $product->inventories()->count());
        $this->assertNull(ProductInventory::where('product_id', $product->id)->where('inventory_id', $invA->id)->first());

        // Lepas dari gudang B — produk tidak ikut terhapus (master tetap ada)
        $this->actingAs($user)
            ->delete(route('gudang.product.detach', $product), ['inventory_id' => $invB->id])
            ->assertRedirect(route('gudang.index', ['inventory_id' => $invB->id]));
        $this->assertSame(0, $product->refresh()->inventories()->count());
        $this->assertNotNull(Product::find($product->id));
    }

    public function test_primary_only_for_core_products(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $inv = Inventory::create(['name' => 'TEST PRIM '.uniqid()]);

        // Barang Pasti (consumable) — attach tidak boleh jadi gudang utama
        $kth = Product::where('code', 'KTH')->firstOrFail();
        $this->actingAs($user)
            ->post(route('gudang.product.attach'), [
                'product_id' => $kth->id,
                'inventory_id' => $inv->id,
                'is_primary' => 1,
            ])
            ->assertRedirect();
        $this->assertFalse((bool) ProductInventory::where('product_id', $kth->id)->where('inventory_id', $inv->id)->value('is_primary'));
        $this->assertNull($kth->refresh()->primaryInventoryId());

        // Barang Additional (BOX) — sama
        $box = Product::where('code', 'BOX')->firstOrFail();
        $this->actingAs($user)
            ->post(route('gudang.product.attach'), [
                'product_id' => $box->id,
                'inventory_id' => $inv->id,
                'is_primary' => 1,
            ])
            ->assertRedirect();
        $this->assertNull($box->refresh()->primaryInventoryId());

        // Kelola gudang produk non-core tanpa radio utama → semua baris is_primary=false
        $this->actingAs($user)
            ->put(route('gudang.product.warehouses', $box), [
                'inventory_ids' => [$inv->id],
            ])
            ->assertRedirect();
        $this->assertSame(0, ProductInventory::where('product_id', $box->id)->where('is_primary', true)->count());

        // Barang Inti (core) tetap bisa jadi gudang utama — dan menggantikan primary lama
        $kmp = Product::where('code', 'KMP')->firstOrFail();
        $origPrimary = $kmp->primaryInventoryId();
        try {
            $this->actingAs($user)
                ->post(route('gudang.product.attach'), [
                    'product_id' => $kmp->id,
                    'inventory_id' => $inv->id,
                    'is_primary' => 1,
                ])
                ->assertRedirect();
            $this->assertSame((int) $inv->id, (int) $kmp->refresh()->primaryInventoryId());
            $this->assertSame(1, ProductInventory::where('product_id', $kmp->id)->where('is_primary', true)->count());
        } finally {
            ProductInventory::where('product_id', $kmp->id)->update(['is_primary' => false]);
            ProductInventory::updateOrCreate(
                ['product_id' => $kmp->id, 'inventory_id' => (int) $origPrimary],
                ['is_primary' => true]
            );
        }
    }

    public function test_purchase_records_stock_to_selected_warehouse(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $invA = Inventory::create(['name' => 'TEST PUR A '.uniqid()]);
        $invB = Inventory::create(['name' => 'TEST PUR B '.uniqid()]);

        $product = $this->makeProduct();
        $variant = $this->variant($product);
        $stock = $this->app->make(StockService::class);

        // Produk inti terdaftar di gudang A (utama) — tapi pembelian dikirim ke gudang B
        ProductInventory::create(['product_id' => $product->id, 'inventory_id' => $invA->id, 'is_primary' => true]);

        $this->actingAs($user)
            ->post(route('purchase.store'), [
                'date' => now()->format('Y-m-d'),
                'product_variant_id' => $variant->id,
                'inventory_id' => $invB->id,
                'quantity' => 10,
                'unit_price' => 5000,
                'shipping_cost' => 0,
            ])
            ->assertRedirect();

        $purchase = Purchase::where('product_variant_id', $variant->id)->latest('id')->first();
        $this->assertNotNull($purchase);
        $this->assertSame((int) $invB->id, (int) $purchase->inventory_id);
        $this->assertSame(10, $stock->stockOf($variant->id, $invB->id));
        $this->assertSame(0, $stock->stockOf($variant->id, $invA->id)); // gudang utama TIDAK terisi

        // Index menampilkan & menyaring per gudang
        $this->actingAs($user)
            ->get(route('purchase.index', ['inventory_id' => $invB->id]))
            ->assertOk()
            ->assertSee($invB->name)
            ->assertSee('Barang Masuk');
    }

    public function test_product_master_variant_crud(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $product = $this->makeProduct();

        $this->actingAs($user)
            ->postJson(route('product.variant.store', $product), [
                'code' => $product->code.'+1.50',
                'name' => 'Plus +1.50',
                'jenis' => 'ukuran',
                'power' => 1.5,
                'status' => 'active',
            ])
            ->assertJson(['success' => true]);

        $variant = ProductVariant::where('code', $product->code.'+1.50')->firstOrFail();
        $this->assertSame(0, $variant->stock); // stok diisi per gudang, bukan di master

        $this->actingAs($user)
            ->putJson(route('product.variant.update', $variant), [
                'code' => $variant->code,
                'name' => 'Plus +1.50 (update)',
                'jenis' => 'ukuran',
                'power' => 1.5,
                'status' => 'inactive',
            ])
            ->assertJson(['success' => true]);
        $variant->refresh();
        $this->assertSame('Plus +1.50 (update)', $variant->name);
        $this->assertSame('inactive', $variant->status);

        $this->actingAs($user)
            ->deleteJson(route('product.variant.destroy', $variant))
            ->assertJson(['success' => true]);
        $this->assertNull(ProductVariant::find($variant->id));
    }

    public function test_product_master_page_create_update_toggle_destroy(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $code = 'MP-'.strtoupper(substr(uniqid(), -5));

        $this->actingAs($user)->get(route('product.index'))->assertOk()->assertSee('Produk');

        // Buat produk di halaman master → varian default otomatis, BELUM terdaftar gudang mana pun
        $this->actingAs($user)
            ->post(route('product.store'), [
                'code' => $code,
                'name' => 'Produk Master Baru',
                'goods_type' => 'core',
                'selling_price' => 30000,
                'purchase_price' => 8000,
                'unit' => 'pcs',
                'status' => 'active',
                'min_stock' => 4,
            ])
            ->assertRedirect(route('product.index'));

        $product = Product::where('code', $code)->firstOrFail();
        $this->assertSame('core', $product->goods_type);
        $this->assertSame(4, $product->min_stock);
        $this->assertNotNull($product->defaultVariant());
        $this->assertSame($code, $product->defaultVariant()->code);
        $this->assertSame(0, $product->inventories()->count()); // belum di-attach ke gudang

        // Update
        $this->actingAs($user)
            ->put(route('product.update', $product), [
                'code' => $code,
                'name' => 'Produk Master Diubah',
                'goods_type' => 'additional',
                'selling_price' => 35000,
                'unit' => 'pcs',
                'status' => 'active',
            ])
            ->assertRedirect(route('product.index'));
        $product->refresh();
        $this->assertSame('Produk Master Diubah', $product->name);
        $this->assertSame('additional', $product->goods_type);

        // Toggle status
        $this->actingAs($user)
            ->patchJson(route('product.toggle-status', $product))
            ->assertJson(['success' => true, 'status' => 'inactive']);

        // Hapus (soft delete)
        $this->actingAs($user)
            ->delete(route('product.destroy', $product))
            ->assertRedirect(route('product.index'));
        $this->assertNull(Product::find($product->id));
    }

    public function test_product_store_via_json_returns_json_not_type_error(): void
    {
        $this->ensureCatalog();
        $user = $this->adminUser();
        $code = 'MPJ-'.strtoupper(substr(uniqid(), -5));

        // Form modal produk dikirim via fetch (Accept: application/json) → store()
        // HARUS mengembalikan JsonResponse, bukan TypeError (regresi: deklarasi
        // return type lama hanya RedirectResponse padahal data tetap tersimpan).
        $this->actingAs($user)
            ->postJson(route('product.store'), [
                'code' => $code,
                'name' => 'Produk JSON Store',
                'goods_type' => 'core',
                'selling_price' => 30000,
                'purchase_price' => 8000,
                'unit' => 'pcs',
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $product = Product::where('code', $code)->first();
        $this->assertNotNull($product, 'Produk harus tersimpan saat store() lewat JSON');
        $this->assertNotNull($product->defaultVariant());
    }

    private function createOrder(int $batchId, string $orderId, string $name, string $courier, string $status, ?int $productId, ?string $productCode, int $qty, string $province = 'JAWA BARAT', string $city = 'Bandung', string $subdistrict = 'Coblong'): ShippingOrder
    {
        $variantId = $productId ? ProductVariant::where('product_id', $productId)->first()?->id : null;

        return ShippingOrder::create([
            'order_online_import_batch_id' => $batchId,
            'order_id' => $orderId,
            'customer_name' => $name,
            'phone_normalized' => '6281234567890',
            'province' => $province,
            'city' => $city,
            'subdistrict' => $subdistrict,
            'courier' => $courier,
            'status' => $status,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'product_code' => $productCode,
            'quantity' => $qty,
            'amount' => 10000,
            'payment_method' => 'cod',
            'is_cod' => true,
        ]);
    }

    private function filenameFromDisposition($response): string
    {
        $disposition = $response->headers->get('content-disposition') ?? '';
        if (preg_match('/filename="?([^";]+)"?/', $disposition, $m)) {
            return $m[1];
        }

        return '';
    }

    private function readZipFiles($response): array
    {
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmp, $content);

        $zip = new ZipArchive;
        $zip->open($tmp);

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $inner = $zip->getFromIndex($i);

            $innerTmp = tempnam(sys_get_temp_dir(), 'xlsx');
            file_put_contents($innerTmp, $inner);

            $spreadsheet = IOFactory::load($innerTmp);
            @unlink($innerTmp);

            $files[$name] = $spreadsheet->getActiveSheet()->toArray();
        }
        $zip->close();
        @unlink($tmp);

        return $files;
    }

    private function readXlsxRows($response): array
    {
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($tmp, $content);

        $spreadsheet = IOFactory::load($tmp);
        @unlink($tmp);

        return $spreadsheet->getActiveSheet()->toArray();
    }
}
