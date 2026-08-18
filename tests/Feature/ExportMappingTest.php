<?php

namespace Tests\Feature;

use App\Models\ExportTemplate;
use App\Models\ExportTemplateMapping;
use App\Models\OrderOnlineImportBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingOrder;
use App\Models\User;
use App\Services\OrderTemplateExportService;
use App\Services\StockService;
use Database\Seeders\ExportTemplateMappingSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ExportMappingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ExportTemplateMappingSeeder::class);
    }

    private function adminUser(): User
    {
        return User::create([
            'nama' => 'EM Test Admin',
            'email' => 'em-test-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }

    private function makeProduct(): Product
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

    private function createOrder(OrderOnlineImportBatch $batch, string $orderId, string $courier, Product $product): ShippingOrder
    {
        $this->app->make(StockService::class)->recordIn($this->variant($product)->id, now()->format('Y-m-d'), 100, 10000, 'adjustment');

        return ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => $orderId,
            'customer_name' => 'Cust '.$orderId,
            'phone_normalized' => '6281234567890',
            'province' => 'JAWA BARAT',
            'city' => 'Bandung',
            'subdistrict' => 'Coblong',
            'address' => 'Jl. Test No. 1',
            'postal_code' => '40131',
            'courier' => $courier,
            'status' => 'real',
            'payment_method' => 'cod',
            'is_cod' => true,
            'product_id' => $product->id,
            'product_variant_id' => $this->variant($product)->id,
            'product_code' => $product->code,
            'product_name' => 'Produk Test',
            'quantity' => 1,
            'amount' => 10000,
            'weight' => 1,
        ]);
    }

    private function readXlsxRows($response): array
    {
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $path = tempnam(sys_get_temp_dir(), 'em').'.xlsx';
        file_put_contents($path, $content);
        $spreadsheet = IOFactory::load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray();
        @unlink($path);

        return $rows;
    }

    private function sampleItems(): array
    {
        return [
            ['column_index' => 0, 'header' => 'Nama', 'source_type' => 'column', 'source_value' => 'customer_name'],
            ['column_index' => 1, 'header' => 'Catatan', 'source_type' => 'computed', 'source_value' => 'default_courier_note'],
            ['column_index' => 2, 'header' => 'Label', 'source_type' => 'static', 'source_value' => 'Barang'],
            ['column_index' => 3, 'header' => 'Kosong', 'source_type' => 'empty', 'source_value' => ''],
        ];
    }

    // ── Index (daftar) ─────────────────────────────────────────────

    public function test_index_lists_templates_with_actions(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('export-mapping.index'))
            ->assertOk()
            ->assertSee('FLIK')
            ->assertSee('SiCepat')
            ->assertSee('SPX')
            ->assertSee('Template Baru')
            ->assertSee('Edit')
            ->assertSee('Hapus')
            ->assertSee('16'); // jumlah kolom FLIK
    }

    public function test_create_page_renders(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('export-mapping.create'))
            ->assertOk()
            ->assertSee('Nama Template')
            ->assertSee('Upload Template CSV')
            ->assertSee('Kembali');
    }

    public function test_edit_page_renders_existing_mapping(): void
    {
        $tpl = ExportTemplate::where('key', 'flik')->firstOrFail();

        $this->actingAs($this->adminUser())
            ->get(route('export-mapping.edit', $tpl))
            ->assertOk()
            ->assertSee('Kode Warehouse')
            ->assertSee('Simpan Perubahan')
            ->assertSee('FLIK');
    }

    // ── Upload CSV ────────────────────────────────────────────────

    public function test_upload_parses_template_headers(): void
    {
        $file = UploadedFile::fake()->createWithContent('template.csv', "Kolom A,Kolom B,Kolom C\n");

        $this->actingAs($this->adminUser())
            ->post(route('export-mapping.upload'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('headers.0.header', 'Kolom A')
            ->assertJsonPath('headers.2.source_type', 'empty')
            ->assertJsonCount(3, 'headers');
    }

    public function test_upload_carries_over_previous_source_by_header_name(): void
    {
        $file = UploadedFile::fake()->createWithContent('template.csv', "Nama Pelanggan,No HP Pelanggan (mulai dengan \"62\"),Kolom Tidak Dikenal\n");

        $this->actingAs($this->adminUser())
            ->post(route('export-mapping.upload'), ['file' => $file, 'template' => 'flik'])
            ->assertOk()
            ->assertJsonPath('headers.0.source_type', 'column')
            ->assertJsonPath('headers.0.source_value', 'customer_name')
            ->assertJsonPath('headers.1.source_value', 'phone_normalized')
            ->assertJsonPath('headers.2.source_type', 'empty');
    }

    // ── Store (create template baru) ───────────────────────────────

    public function test_store_creates_custom_template_and_mapping(): void
    {
        $items = $this->sampleItems();

        $this->actingAs($this->adminUser())
            ->post(route('export-mapping.store'), [
                'name' => 'JNE Express',
                'couriers' => 'jne, jne-cod',
                'items' => $items,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $tpl = ExportTemplate::where('key', 'jne-express')->first();
        $this->assertNotNull($tpl);
        $this->assertSame('JNE Express', $tpl->name);
        $this->assertSame(['jne', 'jne-cod'], $tpl->couriers);
        $this->assertSame(4, ExportTemplateMapping::where('template', 'jne-express')->count());
    }

    public function test_store_defaults_couriers_to_key(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('export-mapping.store'), [
                'name' => 'AnterAja',
                'couriers' => '',
                'items' => $this->sampleItems(),
            ])
            ->assertRedirect();

        $tpl = ExportTemplate::where('key', 'anteraja')->first();
        $this->assertNotNull($tpl);
        $this->assertSame(['anteraja'], $tpl->couriers);
    }

    public function test_store_rejects_duplicate_column_index(): void
    {
        $items = $this->sampleItems();
        $items[1]['column_index'] = 0; // dobel dgn index 0

        $response = $this->actingAs($this->adminUser())
            ->post(route('export-mapping.store'), ['name' => 'X', 'items' => $items]);

        $response->assertStatus(422);
        $this->assertNull(ExportTemplate::where('key', 'x')->first());
    }

    public function test_store_rejects_unknown_column_source(): void
    {
        $items = [['column_index' => 0, 'header' => 'X', 'source_type' => 'column', 'source_value' => 'not_a_real_column']];

        $this->actingAs($this->adminUser())
            ->post(route('export-mapping.store'), ['name' => 'X', 'items' => $items])
            ->assertStatus(422);
    }

    // ── Update ─────────────────────────────────────────────────────

    public function test_update_changes_template_and_mapping(): void
    {
        $tpl = ExportTemplate::where('key', 'spx')->firstOrFail();
        $items = $this->sampleItems();

        $this->actingAs($this->adminUser())
            ->put(route('export-mapping.update', $tpl), [
                'name' => 'SPX Baru',
                'couriers' => 'spx, spx-next',
                'items' => $items,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $tpl->refresh();
        $this->assertSame('SPX Baru', $tpl->name);
        $this->assertSame(['spx', 'spx-next'], $tpl->couriers);
        $this->assertSame(4, ExportTemplateMapping::where('template', 'spx')->count());
        $this->assertSame('customer_name', ExportTemplateMapping::where('template', 'spx')->where('column_index', 0)->value('source_value'));
    }

    // ── Destroy (hapus permanen) ───────────────────────────────────

    public function test_destroy_removes_template_and_mapping_permanently(): void
    {
        $tpl = ExportTemplate::where('key', 'sicepat')->firstOrFail();

        $this->actingAs($this->adminUser())
            ->delete(route('export-mapping.destroy', $tpl))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull(ExportTemplate::find($tpl->id));
        $this->assertSame(0, ExportTemplateMapping::where('template', 'sicepat')->count());
    }

    // ── Export mengikuti template ──────────────────────────────────

    public function test_export_uses_custom_template(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('export-mapping.store'), [
                'name' => 'JNE',
                'couriers' => 'jne',
                'items' => [
                    ['column_index' => 0, 'header' => 'Nama', 'source_type' => 'column', 'source_value' => 'customer_name'],
                    ['column_index' => 1, 'header' => 'Alamat', 'source_type' => 'column', 'source_value' => 'address'],
                    ['column_index' => 2, 'header' => 'Catatan', 'source_type' => 'computed', 'source_value' => 'default_courier_note'],
                ],
            ])
            ->assertRedirect();

        $batch = $this->newBatch();
        $product = $this->makeProduct();
        $this->createOrder($batch, 'JNE-1', 'jne', $product);
        $this->createOrder($batch, 'SPX-1', 'spx', $product); // courier lain TIDAK ikut

        $svc = new OrderTemplateExportService;
        $rows = $this->readXlsxRows($svc->download($batch, 'jne'));

        $this->assertCount(2, $rows); // header + 1 data
        $this->assertSame(['Nama', 'Alamat', 'Catatan'], $rows[0]);
        $this->assertSame('Cust JNE-1', $rows[1][0]);
        $this->assertSame(OrderTemplateExportService::DEFAULT_COURIER_NOTE, $rows[1][2]);
    }

    public function test_export_seeded_default_matches_legacy_layout(): void
    {
        $batch = $this->newBatch();
        $product = $this->makeProduct();
        $this->createOrder($batch, 'DEFAULT-1', 'flix-tf', $product);

        $svc = new OrderTemplateExportService;
        $rows = $this->readXlsxRows($svc->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf'));

        $this->assertSame('Kode Warehouse', $rows[0][0]);
        $this->assertSame('No HP Pelanggan (mulai dengan "62")', $rows[0][2]);
        $this->assertSame('Nama Produk', $rows[0][15]);
        $this->assertCount(16, $rows[0]);

        $this->assertSame('eresgestore', $rows[1][0]);
        $this->assertSame('Cust DEFAULT-1', $rows[1][1]);
        $this->assertSame('6281234567890', $rows[1][2]);
        $this->assertSame(OrderTemplateExportService::DEFAULT_COURIER_NOTE, $rows[1][9]);
        $this->assertSame(10000, (int) $rows[1][10]);
        $this->assertSame(10, (int) $rows[1][11]);
        $this->assertSame(8, (int) $rows[1][12]);
        $this->assertSame(6, (int) $rows[1][13]);
        $this->assertSame(1, (int) $rows[1][14]);
        $this->assertSame('Produk Test', $rows[1][15]);
    }

    public function test_export_throws_when_mapping_missing(): void
    {
        $batch = $this->newBatch();
        $product = $this->makeProduct();
        $this->createOrder($batch, 'NO-MAP-1', 'flix-tf', $product);

        ExportTemplateMapping::where('template', 'flik')->delete();

        $this->expectException(\RuntimeException::class);

        (new OrderTemplateExportService)->download($batch, OrderTemplateExportService::TEMPLATE_FLIK, 'flix-tf');
    }

    public function test_spx_mapping_uses_computed_transforms(): void
    {
        $batch = $this->newBatch();
        $product = $this->makeProduct();
        $this->createOrder($batch, 'SPX-1', 'spx', $product);
        ShippingOrder::where('order_online_import_batch_id', $batch->id)->update(['province' => 'jawa barat', 'city' => 'bandung', 'subdistrict' => 'coblong']);

        $svc = new OrderTemplateExportService;
        $rows = $this->readXlsxRows($svc->download($batch, OrderTemplateExportService::TEMPLATE_SPX));

        $this->assertSame('81234567890', $rows[1][2]);
        $this->assertSame('JAWA BARAT', $rows[1][4]);
        $this->assertSame('BANDUNG', $rows[1][5]);
        $this->assertSame('Y', $rows[1][10]);
        $this->assertSame('N', $rows[1][12]);
    }
}
