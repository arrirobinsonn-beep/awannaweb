<?php

namespace Tests\Feature;

use App\Models\OrderOnlineImportBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingOrder;
use App\Models\StockMovement;
use App\Services\AggregatorTrackingImportService;
use App\Services\StockService;
use Tests\TestCase;

class AggregatorTrackingImportTest extends TestCase
{
    private function makeProduct(int $stock = 100): Product
    {
        $code = 'TRK'.strtoupper(substr(uniqid(), -6));
        $product = Product::create([
            'code' => $code,
            'name' => 'Produk Tracking',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'code' => $code,
            'name' => 'Produk Tracking',
            'power' => 0,
            'stock' => 0,
            'status' => 'active',
        ]);

        if ($stock > 0) {
            $this->app->make(StockService::class)->recordIn(
                $variant->id,
                now()->format('Y-m-d'),
                $stock,
                10000,
                'adjustment',
                $variant->id
            );
        }

        return $product;
    }

    private function variant(Product $product): ProductVariant
    {
        return ProductVariant::where('product_id', $product->id)->firstOrFail();
    }

    private function makeOrder(string $phone, Product $product, int $quantity = 1, string $address = 'Jl. Test No. 1', string $status = 'real'): ShippingOrder
    {
        $batch = OrderOnlineImportBatch::create([
            'original_filename' => 'src.csv',
            'stored_path' => 'order-online/src.csv',
            'sender' => 'eresgestore',
            'status' => 'completed',
            'total_rows' => 1,
            'success_rows' => 1,
        ]);

        return ShippingOrder::create([
            'order_online_import_batch_id' => $batch->id,
            'order_id' => 'ORD-'.uniqid(),
            'customer_name' => 'Customer Tracking',
            'phone_normalized' => $phone,
            'address' => $address,
            'province' => 'JAWA BARAT',
            'city' => 'Bandung',
            'subdistrict' => 'Coblong',
            'courier' => 'sicepat',
            'status' => $status,
            'product_id' => $product->id,
            'product_variant_id' => $this->variant($product)->id,
            'product_code' => $product->code,
            'quantity' => $quantity,
            'amount' => 10000,
            'payment_method' => 'cod',
            'is_cod' => true,
        ]);
    }

    private function writeCsv(string $extension, array $headers, array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'track').'.'.$extension;
        $handle = fopen($path, 'w');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($handle, $line);
        }
        fclose($handle);

        return $path;
    }

    private function flikCsv(array $rows): string
    {
        return $this->writeCsv('csv', [
            'Order ID', 'AWB', 'Kurir', 'Service', 'Tanggal Pembuatan', 'Detail Penjemputan', 'COD',
            'Nama Shopper', 'No Telp', 'Ongkir Sebelum Diskon', 'Diskon', 'Harga Setelah Diskon',
            'Nominal COD', 'Status', 'Status Terakhir dari 3PL', 'Nama Produk', 'Provinsi',
            'Catatan Kurir', 'POD', 'Scheduled Pickup', 'Terakhir Update', 'Nama Warehouse',
            'Sumber', 'Komisi COD', 'Komisi Jagokurir', 'Actual Pickup', 'Kecamatan', 'Kota',
            'Alamat Lengkap Penerima',
        ], $rows);
    }

    private function sicepatCsv(array $rows): string
    {
        return $this->writeCsv('csv', [
            '#', 'Nomor Resi', 'Status', 'Tanggal', 'Tanggal Dipickup', 'Tanggal Terkirim',
            'Tanggal Dikembalikan', 'Nomor Pengiriman', 'Nomor Referensi', 'Nomor Multikoli',
            'Tipe Multikoli', 'Tipe DO Balik', 'Pengirim', 'Nama Penerima', 'Tipe Pembayaran',
            'Jenis Paket', 'Isi Paket', 'Jumlah Isi Paket', 'Alamat Penerima', 'Kecamatan',
            'Kota', 'Provinsi', 'Kode Pos', 'No. HP Penerima', 'Total Berat', 'Berat Asli',
            'Harga Ongkir', 'Proteksi Paket', 'Layanan', 'Harga Paket', 'Total Biaya',
            'Order Source', 'POD Bermasalah 1', 'POD Bermasalah 2', 'POD Bermasalah 3',
            'POD Delivery', 'POD DO Return', 'POD Attempt 1', 'Tanggal Attempt 1',
            'POD Attempt 2', 'Tanggal Attempt 2', 'POD Attempt 3', 'Tanggal Attempt 3',
        ], $rows);
    }

    private function spxCsv(array $rows): string
    {
        return $this->writeCsv('csv', [
            'Tracking No.', 'Tracking No. link', 'Customer Reference No.', 'Customer Reference No. link',
            'Create Time', 'Tracking Status', 'Account ID', 'Original pickup option',
            'Actual pickup option', 'Scheduled Pickup Time', 'Actual Pickup/Drop Off Time',
            'Delivered Time', 'Delivery OnHold Times', 'Delivery OnHold Reason',
            'Returning Start Time', 'Recipient Name', 'Recipient Phone Number',
            'Recipient Province', 'Recipient City', 'Recipient District',
            'Recipient Detail Address', 'Recipient Postal Code', 'Sender Name',
            'Sender Phone Number', 'Sender Province', 'Sender City', 'Sender District',
            'Sender Detail Address', 'Sender Postal Code', 'Payment Role', 'Item List',
            'Item in Parcel', 'No. of item in Parcel', 'COD Collection(Y/N)', 'COD Amount',
            'Parcel Value', 'Parcel Weight', 'Actual Weight', 'Estimated Shipping Fee',
            'Actual Shipping Fee', 'Basic Shipping Fee', 'Insurance Fee', 'COD Service Fee',
            'Return Shipping Fee', 'Delivery failed Reason', 'Create Method', 'Order Creator',
        ], $rows);
    }

    public function test_map_status_english_values(): void
    {
        $svc = new AggregatorTrackingImportService;

        $this->assertSame('waiting_pickup', $svc->mapStatus('flik', 'Dikonfirmasi'));
        $this->assertSame('in_transit', $svc->mapStatus('flik', 'Sedang Diantar'));
        $this->assertSame('delivered', $svc->mapStatus('flik', 'Terkirim'));
        $this->assertSame('delivered', $svc->mapStatus('flik', 'Dicairkan'));
        $this->assertSame('returning', $svc->mapStatus('flik', 'Dalam Transit Pengembalian'));
        $this->assertSame('returned', $svc->mapStatus('flik', 'Dikembalikan'));

        $this->assertSame('waiting_pickup', $svc->mapStatus('sicepat', 'Menunggu pickup'));
        $this->assertSame('in_transit', $svc->mapStatus('sicepat', 'Proses pengiriman'));
        $this->assertSame('delivered', $svc->mapStatus('sicepat', 'Terkirim'));
        $this->assertSame('returning', $svc->mapStatus('sicepat', 'Proses retur'));
        $this->assertSame('returned', $svc->mapStatus('sicepat', 'Retur'));
        $this->assertSame('problem', $svc->mapStatus('sicepat', 'Bermasalah'));

        $this->assertSame('waiting_pickup', $svc->mapStatus('spx', 'Pending Pickup'));
        $this->assertSame('in_transit', $svc->mapStatus('spx', 'In Transit'));
        $this->assertSame('in_transit', $svc->mapStatus('spx', 'Delivering'));
        $this->assertSame('delivered', $svc->mapStatus('spx', 'Delivered'));
        $this->assertSame('returning', $svc->mapStatus('spx', 'Returning'));
        $this->assertSame('returned', $svc->mapStatus('spx', 'Returned'));

        $this->assertSame('problem', $svc->mapStatus('flik', 'Dikonfirmasi', 'Problem: alamat tidak lengkap'));
        $this->assertSame('waiting_pickup', $svc->mapStatus('flik', 'Dikonfirmasi', 'OK'));
        $this->assertSame('problem', $svc->mapStatus('spx', 'In Transit', 'On Hold - alamat'));
        $this->assertSame('in_transit', $svc->mapStatus('spx', 'In Transit', ''));
        $this->assertNull($svc->mapStatus('flik', 'Status Tidak Dikenal'));
    }

    public function test_import_flik_fills_awb_status_and_delivered_at(): void
    {
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');
        $local = substr($phone, 2);

        $path = $this->flikCsv([
            [
                'Order ID' => 'uuid-1',
                'AWB' => 'SPXID123456789',
                'No Telp' => '0'.$local,
                'Nama Shopper' => 'Customer Tracking',
                'Status' => 'Terkirim',
                'Nama Produk' => 'Produk Tracking 2 pcs',
                'Terakhir Update' => '8/9/2026 17:34',
                'Alamat Lengkap Penerima' => 'Jl. Merdeka No. 10',
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame('flik', $result['source']);
        $this->assertSame(1, $result['matched']);

        $order->refresh();
        $this->assertSame('SPXID123456789', $order->awb);
        $this->assertSame('delivered', $order->aggregator_status);
        $this->assertSame('2026-08-09 17:34:00', $order->delivered_at?->format('Y-m-d H:i:s'));
    }

    public function test_import_sicepat_fills_status(): void
    {
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');
        $local = substr($phone, 2);

        $path = $this->sicepatCsv([
            [
                'Nomor Resi' => '999515101688',
                'Status' => 'Proses pengiriman',
                'Nama Penerima' => 'Customer Tracking',
                'Isi Paket' => 'Produk Tracking 2 pcs',
                'Jumlah Isi Paket' => 2,
                'Alamat Penerima' => 'Jl. Merdeka No. 10',
                'No. HP Penerima' => '0'.$local,
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame('sicepat', $result['source']);
        $order->refresh();
        $this->assertSame('999515101688', $order->awb);
        $this->assertSame('in_transit', $order->aggregator_status);
        $this->assertNull($order->delivered_at);
    }

    public function test_import_spx_fills_delivered_at(): void
    {
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');
        $local = substr($phone, 2);

        $path = $this->spxCsv([
            [
                'Tracking No.' => 'SPXID060407040698',
                'Tracking Status' => 'Delivered',
                'Delivered Time' => '04-07-2026 19:47',
                'Recipient Name' => 'Customer Tracking',
                'Recipient Phone Number' => '0'.$local,
                'Recipient Detail Address' => 'Jl. Merdeka No. 10',
                'Item in Parcel' => 'Produk Tracking 2 pcs',
                'No. of item in Parcel' => 2,
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame('spx', $result['source']);
        $order->refresh();
        $this->assertSame('SPXID060407040698', $order->awb);
        $this->assertSame('delivered', $order->aggregator_status);
        $this->assertSame('2026-07-04 19:47:00', $order->delivered_at?->format('Y-m-d H:i:s'));
    }

    public function test_import_spx_phone_starting_with_8_matches_db_62(): void
    {
        // SPX file memakai format 8xxxxx, DB menyimpan 62xxxxx → harus tetap merge
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');
        $local = substr($phone, 2);

        $path = $this->spxCsv([
            [
                'Tracking No.' => 'SPXID060407040699',
                'Tracking Status' => 'Delivered',
                'Delivered Time' => '04-07-2026 19:47',
                'Recipient Name' => 'Customer Tracking',
                'Recipient Phone Number' => $local,
                'Recipient Detail Address' => 'Jl. Merdeka No. 10',
                'Item in Parcel' => 'Produk Tracking 2 pcs',
                'No. of item in Parcel' => 2,
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame('spx', $result['source']);
        $this->assertSame(1, $result['matched']);
        $order->refresh();
        $this->assertSame('SPXID060407040699', $order->awb);
        $this->assertSame('delivered', $order->aggregator_status);
    }

    public function test_import_returned_restores_stock(): void
    {
        $product = $this->makeProduct(100);
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');
        $local = substr($phone, 2);

        $stock = $this->app->make(StockService::class);
        $variant = $this->variant($product);
        $stock->recordOut($variant->id, now()->format('Y-m-d'), 2, 'order_online', $order->id, 'Order online '.$order->order_id);
        $this->assertSame(98, $stock->stockOf($variant->id));

        $path = $this->sicepatCsv([
            [
                'Nomor Resi' => '999515101688',
                'Status' => 'Retur',
                'Nama Penerima' => 'Customer Tracking',
                'Isi Paket' => 'Produk Tracking 2 pcs',
                'Jumlah Isi Paket' => 2,
                'Alamat Penerima' => 'Jl. Merdeka No. 10',
                'No. HP Penerima' => '0'.$local,
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame(1, $result['stock_returned']);
        $order->refresh();
        $this->assertSame('returned', $order->aggregator_status);
        $this->assertSame(100, $stock->stockOf($variant->id));
        $this->assertSame(0, StockMovement::where('reference', 'order_online')->where('reference_id', $order->id)->where('type', 'out')->count());
    }

    public function test_import_returned_is_idempotent_for_stock(): void
    {
        $product = $this->makeProduct(100);
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');
        $local = substr($phone, 2);

        $stock = $this->app->make(StockService::class);
        $variant = $this->variant($product);
        $stock->recordOut($variant->id, now()->format('Y-m-d'), 2, 'order_online', $order->id, 'Order online '.$order->order_id);

        $path = $this->sicepatCsv([
            [
                'Nomor Resi' => '999515101688',
                'Status' => 'Retur',
                'Nama Penerima' => 'Customer Tracking',
                'Isi Paket' => 'Produk Tracking 2 pcs',
                'Jumlah Isi Paket' => 2,
                'Alamat Penerima' => 'Jl. Merdeka No. 10',
                'No. HP Penerima' => '0'.$local,
            ],
        ]);

        $svc = new AggregatorTrackingImportService;

        $first = $svc->import($path);
        $this->assertSame(1, $first['stock_returned']);

        $second = $svc->import($path);
        $this->assertSame(0, $second['stock_returned']);

        $this->assertSame(100, $stock->stockOf($variant->id));
    }

    public function test_import_matches_by_phone_and_name_regardless_of_address(): void
    {
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 1, 'Alamat Asli');
        $local = substr($phone, 2);

        $path = $this->sicepatCsv([
            [
                'Nomor Resi' => '999515101688',
                'Status' => 'Terkirim',
                'Nama Penerima' => 'Customer Tracking',
                'Isi Paket' => 'Produk Tracking 1 pcs',
                'Jumlah Isi Paket' => 1,
                'Alamat Penerima' => 'Alamat Berbeda Dari DB',
                'No. HP Penerima' => '0'.$local,
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame(1, $result['matched']);
        $order->refresh();
        $this->assertSame('999515101688', $order->awb);
        $this->assertSame('delivered', $order->aggregator_status);
    }

    public function test_import_matches_even_with_unrecognizable_product_name(): void
    {
        // Dashboard FLIK asli: kolom "Nama Produk" berisi nama PROMO (bukan nama
        // produk). Matching hanya pakai phone + customer_name, jadi produk tidak relevan.
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');
        $order->update(['customer_name' => 'Deny Promo']);

        $path = $this->flikCsv([
            [
                'Order ID' => 'uuid-promo-1',
                'AWB' => 'SPXIDPROMO001',
                'No Telp' => '0'.substr($phone, 2),
                'Status' => 'Dikonfirmasi',
                'Nama Shopper' => 'Deny Promo',
                'Nama Produk' => 'Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 43-44 Tahun Plus +1.25',
                'Alamat Lengkap Penerima' => 'Jl. Merdeka No. 10',
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame(1, $result['matched']);
        $order->refresh();
        $this->assertSame('SPXIDPROMO001', $order->awb);
        $this->assertSame('waiting_pickup', $order->aggregator_status);
    }

    public function test_import_extracts_qty_from_dapat_promo_text(): void
    {
        // "Beli 1 Dapat 2" → qty 2 (kolom qty tidak ada di FLIK, diekstrak dari teks)
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');

        $path = $this->flikCsv([
            [
                'Order ID' => 'uuid-dapat-1',
                'AWB' => 'SPXIDDAPAT001',
                'No Telp' => '0'.substr($phone, 2),
                'Status' => 'Terkirim',
                'Nama Shopper' => 'Customer Tracking',
                'Nama Produk' => 'Promo: PROMO Beli 1 Dapat 2 - Rp 129.000',
                'Alamat Lengkap Penerima' => 'Jl. Merdeka No. 10',
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame(1, $result['matched']);
        $this->assertSame('SPXIDDAPAT001', $order->refresh()->awb);
    }

    public function test_import_ambiguous_when_same_name_multiple_orders(): void
    {
        // 2 order dengan phone + nama SAMA → ambiguous (tidak bisa dibedakan)
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');
        $this->makeOrder($phone, $product, 2, 'Jl. Merdeka No. 10');
        // makeOrder default name = 'Customer Tracking'

        $path = $this->flikCsv([
            [
                'Order ID' => 'uuid-ambig-1',
                'AWB' => 'SPXIDAMBIG001',
                'No Telp' => '0'.substr($phone, 2),
                'Status' => 'Dikonfirmasi',
                'Nama Shopper' => 'Customer Tracking',
                'Nama Produk' => 'Promo: PROMO Beli 1 Dapat 2 - Rp 129.000',
                'Alamat Lengkap Penerima' => 'Jl. Merdeka No. 10',
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame(0, $result['matched']);
        $this->assertCount(1, $result['ambiguous']);
    }

    public function test_import_matches_by_customer_name(): void
    {
        // Dua order HP sama, nama BEDA → nama memutuskan
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $budi = $this->makeOrder($phone, $product, 1, 'Jl. A No. 1');
        $andi = $this->makeOrder($phone, $product, 1, 'Jl. B No. 2');
        $budi->update(['customer_name' => 'Budi Santoso']);
        $andi->update(['customer_name' => 'Andi Wijaya']);
        $local = substr($phone, 2);

        $path = $this->sicepatCsv([
            [
                'Nomor Resi' => '999515101688',
                'Status' => 'Terkirim',
                'Nama Penerima' => 'Andi Wijaya',
                'Isi Paket' => 'Produk Tracking 1 pcs',
                'Jumlah Isi Paket' => 1,
                'Alamat Penerima' => 'Jl. B No. 2',
                'No. HP Penerima' => '0'.$local,
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame(1, $result['matched']);
        $this->assertSame('999515101688', $andi->refresh()->awb);
        $this->assertNull($budi->refresh()->awb);
    }

    public function test_import_name_wins_over_address_when_unique(): void
    {
        // Nama cocok unik meski alamat file beda dari DB → nama jadi pembeda
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $order = $this->makeOrder($phone, $product, 1, 'Jl. Asli No. 1');
        $order->update(['customer_name' => 'Budi Santoso']);
        $local = substr($phone, 2);

        $path = $this->sicepatCsv([
            [
                'Nomor Resi' => '999515101688',
                'Status' => 'Terkirim',
                'Nama Penerima' => 'Budi Santoso',
                'Isi Paket' => 'Produk Tracking 1 pcs',
                'Jumlah Isi Paket' => 1,
                'Alamat Penerima' => 'Jl. Berbeda',
                'No. HP Penerima' => '0'.$local,
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame(1, $result['matched']);
        $this->assertSame('999515101688', $order->refresh()->awb);
    }

    public function test_import_ambiguous_is_not_updated(): void
    {
        $product = $this->makeProduct();
        $phone = '62812'.substr(preg_replace('/\D/', '', uniqid()), -8);
        $orderA = $this->makeOrder($phone, $product, 1, 'Jl. Merdeka No. 10');
        $orderB = $this->makeOrder($phone, $product, 1, 'Jl. Merdeka No. 10');
        // Both have same default name 'Customer Tracking'
        $local = substr($phone, 2);

        $path = $this->sicepatCsv([
            [
                'Nomor Resi' => '999515101688',
                'Status' => 'Terkirim',
                'Nama Penerima' => 'Customer Tracking',
                'Isi Paket' => 'Produk Tracking 1 pcs',
                'Jumlah Isi Paket' => 1,
                'Alamat Penerima' => 'Jl. Merdeka No. 10',
                'No. HP Penerima' => '0'.$local,
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame(0, $result['matched']);
        $this->assertCount(1, $result['ambiguous']);
        $this->assertNull($orderA->refresh()->aggregator_status);
        $this->assertNull($orderB->refresh()->aggregator_status);
    }

    public function test_import_unmatched_is_reported(): void
    {
        $product = $this->makeProduct();

        $path = $this->sicepatCsv([
            [
                'Nomor Resi' => '999515101688',
                'Status' => 'Terkirim',
                'Nama Penerima' => 'Siapapun',
                'Isi Paket' => 'Produk Tracking 1 pcs',
                'Jumlah Isi Paket' => 1,
                'Alamat Penerima' => 'Jl. Lain',
                'No. HP Penerima' => '085299999999',
            ],
        ]);

        $result = (new AggregatorTrackingImportService)->import($path);

        $this->assertSame(0, $result['matched']);
        $this->assertCount(1, $result['unmatched']);
        $this->assertSame(0, ShippingOrder::where('phone_normalized', '6285299999999')->whereNotNull('awb')->count());

        $product->delete();
    }
}
