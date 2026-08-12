<?php

/*
 * Generator test kit pipeline order-online → export aggregator → tracking status.
 *
 * Menghasilkan 7 file di folder filecoba/:
 *  01_order_online_mentah.csv   – data mentah 52 kolom (pola training/DataDariOrderOnline)
 *  02_export_flik.csv           – referensi statis export FLIK (format RUNTIME 1-kolom HP)
 *  02_export_sicepat.csv        – referensi statis export SiCepat
 *  02_export_spx.csv            – referensi statis export SPX
 *  03_tracking_flik.csv         – file dashboard FLIK (header asli dashboard)
 *  03_tracking_sicepat.csv      – file dashboard SiCepat
 *  03_tracking_spx.csv          – file dashboard SPX
 *
 * Referensi export 02_* MENIRU logika OrderTemplateExportService saat ini
 * (bukan template lama FLIX 2-kolom HP). File 03_* memakai header dashboard
 * asli agar AggregatorTrackingImportService dapat mendeteksi sumber & kolom.
 *
 * Jalankan: php filecoba/generate_test_kit.php
 */

$dir = __DIR__;
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// ─── Definisi order (data konsisten untuk import/export/tracking) ───────────
// Kolom: id, product, name, phone, address, province, city, subdistrict, zip,
//        product_code, variation, quantity, product_price, payment_method,
//        status(CSV: processing|pending), payment_status
$orders = [
    [
        'id' => 'CBC-101', 'product' => 'A.3 Kacamata Multifokus Photocromic',
        'name' => 'Budi Santoso', 'phone' => '081234567891',
        'address' => 'Jl. Melati No. 10, RT 01/RW 02', 'province' => 'DKI JAKARTA',
        'city' => 'Jakarta Pusat', 'subdistrict' => 'Gambir', 'zip' => '10110',
        'product_code' => 'KMP', 'variation' => 'Ukuran: Usia 45-46 Tahun Plus +1.50',
        'quantity' => 1, 'product_price' => 119000, 'payment_method' => 'bank_transfer',
        'status' => 'processing', 'payment_status' => 'paid',
    ],
    [
        'id' => 'CBC-102', 'product' => 'A.3 Kacamata Multifokus Photocromic',
        'name' => 'Andi Wijaya', 'phone' => '081234567892',
        'address' => 'Jl. Kenanga No. 5, Cileunyi', 'province' => 'JAWA BARAT',
        'city' => 'Bandung', 'subdistrict' => 'Cileunyi', 'zip' => '40623',
        'product_code' => 'KMP', 'variation' => 'Ukuran: Usia 43-44 Tahun Plus +1.00',
        'quantity' => 2, 'product_price' => 119000, 'payment_method' => 'bank_transfer',
        'status' => 'processing', 'payment_status' => 'paid',
    ],
    [
        'id' => 'CBC-103', 'product' => 'Kacamata Sporty Photocromic',
        'name' => 'Citra Lestari', 'phone' => '081234567893',
        'address' => 'Jl. Cemara No. 7, Cikarang Utara', 'province' => 'JAWA BARAT',
        'city' => 'Bekasi', 'subdistrict' => 'Cikarang Utara', 'zip' => '17530',
        'product_code' => 'KSP', 'variation' => 'Ukuran: Usia 45-46 Tahun Plus +2.00',
        'quantity' => 1, 'product_price' => 119000, 'payment_method' => 'bank_transfer',
        'status' => 'processing', 'payment_status' => 'paid',
    ],
    [
        'id' => 'CBC-104', 'product' => 'A.3 Kacamata Baca & Jalan',
        'name' => 'Dewi Anggraini', 'phone' => '081234567894',
        'address' => 'Jl. Mawar No. 12, Padang', 'province' => 'SUMATRA BARAT',
        'city' => 'Padang', 'subdistrict' => 'Padang Barat', 'zip' => '25111',
        'product_code' => 'KBJ', 'variation' => 'Ukuran: Usia 44-45 Tahun Plus +1.25',
        'quantity' => 2, 'product_price' => 119000, 'payment_method' => 'bank_transfer',
        'status' => 'processing', 'payment_status' => 'paid',
    ],
    [
        'id' => 'CBC-105', 'product' => 'A.3 Kabel Casan Hp 3IN1',
        'name' => 'Eko Prasetyo', 'phone' => '081234567895',
        'address' => 'Jl. Anggrek No. 3, Pontianak', 'province' => 'KALIMANTAN BARAT',
        'city' => 'Pontianak', 'subdistrict' => 'Pontianak Timur', 'zip' => '78231',
        'product_code' => 'KCHP', 'variation' => '',
        'quantity' => 1, 'product_price' => 25000, 'payment_method' => 'bank_transfer',
        'status' => 'processing', 'payment_status' => 'paid',
    ],
    [
        'id' => 'CBC-201', 'product' => 'A.3 Kacamata Multifokus Photocromic',
        'name' => 'Fitri Handayani', 'phone' => '081234567896',
        'address' => 'Jl. Dahlia No. 9, Depok', 'province' => 'JAWA BARAT',
        'city' => 'Depok', 'subdistrict' => 'Pancoran Mas', 'zip' => '16431',
        'product_code' => 'KMP', 'variation' => 'Ukuran: Usia 46-47 Tahun Plus +1.75',
        'quantity' => 1, 'product_price' => 119000, 'payment_method' => 'cod',
        'status' => 'processing', 'payment_status' => 'paid',
    ],
    [
        'id' => 'CBC-202', 'product' => 'A.3 Kacamata Multifokus Photocromic',
        'name' => 'Gunawan Hartono', 'phone' => '081234567897',
        'address' => 'Jl. Kamboja No. 21, Bogor', 'province' => 'JAWA BARAT',
        'city' => 'Bogor', 'subdistrict' => 'Bogor Timur', 'zip' => '16143',
        'product_code' => 'KMP', 'variation' => 'Ukuran: Usia 47-48 Tahun Plus +2.25',
        'quantity' => 3, 'product_price' => 119000, 'payment_method' => 'cod',
        'status' => 'processing', 'payment_status' => 'paid',
    ],
    [
        'id' => 'CBC-203', 'product' => 'A.3 Kabel Casan Hp 3IN1',
        'name' => 'Hana Putri', 'phone' => '081234567898',
        'address' => 'Jl. Teratai No. 15, Denpasar', 'province' => 'BALI',
        'city' => 'Denpasar', 'subdistrict' => 'Denpasar Barat', 'zip' => '80113',
        'product_code' => 'KCHP', 'variation' => '',
        'quantity' => 1, 'product_price' => 25000, 'payment_method' => 'cod',
        'status' => 'processing', 'payment_status' => 'paid',
    ],
    [
        'id' => 'CBC-301', 'product' => 'A.3 Kacamata Baca & Jalan',
        'name' => 'Indra Maulana', 'phone' => '081234567899',
        'address' => 'Jl. Flamboyan No. 4, Banda Aceh', 'province' => 'NANGGROE ACEH DARUSSALAM (NAD)',
        'city' => 'Banda Aceh', 'subdistrict' => 'Kuta Alam', 'zip' => '23126',
        'product_code' => 'KBJ', 'variation' => 'Promo Beli 1 Dapat 2 - Ukuran: Usia 45-46 Tahun Plus +1.50',
        'quantity' => 1, 'product_price' => 119000, 'payment_method' => 'bank_transfer',
        'status' => 'pending', 'payment_status' => 'paid',
    ],
    [
        'id' => 'CBC-302', 'product' => 'A.3 Kacamata Multifokus Photocromic',
        'name' => 'Joko Susilo', 'phone' => '081234567810',
        'address' => 'Jl. Cempaka No. 8, Banjarmasin', 'province' => 'KALIMANTAN SELATAN',
        'city' => 'Banjarmasin', 'subdistrict' => 'Banjarmasin Timur', 'zip' => '70239',
        'product_code' => 'KMP', 'variation' => 'Ukuran: Usia 44-45 Tahun Plus +1.25',
        'quantity' => 1, 'product_price' => 119000, 'payment_method' => 'cod',
        'status' => 'pending', 'payment_status' => 'paid',
    ],
];

const SENDER = 'GUDANG-PUSAT';
const DEFAULT_NOTE = 'HUBUNGI KONSUMEN SEBELUM DIKIRIM';
const KACAMATA = ['KMP', 'KSP', 'KBJ'];

// ─── Helper (meniru logika import/export) ───────────────────────────────────

function normalizePhone(string $phone): string
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($phone, '0')) {
        $phone = '62'.substr($phone, 1);
    } elseif (! str_starts_with($phone, '62')) {
        $phone = '62'.$phone;
    }

    return $phone;
}

function phoneSpx(string $phone): string
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($phone, '62')) {
        $phone = substr($phone, 2);
    } elseif (str_starts_with($phone, '0')) {
        $phone = substr($phone, 1);
    }

    return $phone;
}

const DEFAULT_SHIPPING = 50000;

function shippingCost(array $o): float
{
    return (float) ($o['shipping_cost'] ?? DEFAULT_SHIPPING);
}

/** Nilai amount (gross_revenue) = product_price + ongkir. */
function grossRevenue(array $o): float
{
    return (float) $o['product_price'] + shippingCost($o);
}

/** Nama produk yang tersimpan di DB (meta_account dipisah, Dapat N → suffix pcs). */
function storedProductName(array $o): array
{
    $name = $o['product'];
    if (($sep = strrpos($name, ' - ')) !== false) {
        $name = trim(substr($name, 0, $sep));
    }
    $qty = (int) $o['quantity'];
    if (preg_match('/Dapat\s*(\d+)/i', $o['variation'], $m)) {
        $qty = max(1, (int) $m[1]);
        $name = trim($name.' '.$qty.' pcs');
    }

    return [$name, $qty];
}

/** Power lensa dari kolom variation ("Plus +1.50" → 1.5), null bila tak ada. */
function powerFrom(array $o): ?float
{
    if (preg_match('/Plus\s*([+\-]?\d+(?:\.\d+)?)/i', $o['variation'], $m)) {
        return (float) $m[1];
    }

    return null;
}

/** Nama produk kolom export (kacamata → "<nama> +<power> <qty> pcs"). */
function displayName(array $o): string
{
    [$name, $qty] = storedProductName($o);
    $base = strtoupper(trim(explode('+', $o['product_code'])[0]));
    if (! in_array($base, KACAMATA, true)) {
        return $name;
    }
    $power = powerFrom($o);
    if ($power === null || $power <= 0) {
        return $name;
    }
    $name = preg_replace('/\s+\d+\s*pcs$/i', '', trim($name));

    return trim($name).' '.sprintf('+%.2f', $power).' '.max(1, (int) $qty).' pcs';
}

function warehouseFor(array $o): string
{
    $code = strtoupper(trim(explode('+', $o['product_code'])[0]));
    if ($code === 'KSP') {
        return 'GTM';
    }
    if ($code === 'SH') {
        return 'Aurora';
    }

    return SENDER;
}

function writeCsv(string $path, array $rows): void
{
    $handle = fopen($path, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    echo 'OK: '.count($rows).' baris → '.$path."\n";
}

// ─── 01. Data mentah order online (52 kolom) ────────────────────────────────

$header = [
    'order_id', 'product', 'name', 'email', 'phone', 'address', 'province', 'city',
    'subdistrict', 'zip', 'status', 'payment_status', 'payment_method', 'payment_info',
    'product_price', 'cogs', 'discount', 'quantity', 'bump', 'bump_price', 'notes',
    'courier', 'shipping_cost', 'cod_cost', 'shipping_markup', 'receipt_number', 'other_cost',
    'gross_revenue', 'net_revenue', 'created_at', 'processing_at', 'completed_at', 'paid_at',
    'handled_by', 'coupon', 'product_code', 'utm_campaign', 'utm_medium', 'utm_source',
    'utm_content', 'utm_term', 'tags', 'dropshipper_name', 'dropshipper_phone', 'variation',
    'order_type', 'reseller_name', 'weight', 'original_shipping_cost', 'ip_address',
    'variation_code', 'shipping_method',
];

$rowData = [];
foreach ($orders as $o) {
    $rowData[] = [
        'order_id' => $o['id'], 'product' => $o['product'], 'name' => $o['name'],
        'phone' => $o['phone'], 'address' => $o['address'], 'province' => $o['province'],
        'city' => $o['city'], 'subdistrict' => $o['subdistrict'], 'zip' => $o['zip'],
        'status' => $o['status'], 'payment_status' => $o['payment_status'],
        'payment_method' => $o['payment_method'], 'product_price' => $o['product_price'],
        'shipping_cost' => shippingCost($o), 'gross_revenue' => grossRevenue($o),
        'quantity' => $o['quantity'], 'product_code' => $o['product_code'],
        'variation' => $o['variation'], 'weight' => 1,
    ];
}
$lines = array_merge([$header], array_map(fn ($d) => array_map(fn ($h) => $d[$h] ?? '', $header), $rowData));
writeCsv("$dir/01_order_online_mentah.csv", $lines);

// ─── 02. Referensi statis export (format runtime) ──────────────────────────

$flik = array_merge([[
    'Kode Warehouse', 'Nama Pelanggan', 'No HP Pelanggan (mulai dengan "62")',
    'Alamat: Lengkap', 'Alamat: Provinsi', 'Alamat: Kota', 'Alamat: Kecamatan',
    'Alamat: Kelurahan', 'Alamat: Kode Pos', 'Alamat: Catatan Kurir',
    'Total Nilai Barang / Total Nilai COD', 'Panjang Barang (cm)', 'Lebar Barang (cm)',
    'Tinggi Barang (cm)', 'Berat (kg)', 'Nama Produk',
]], array_map(fn ($o) => [
    warehouseFor($o), $o['name'], normalizePhone($o['phone']), $o['address'],
    $o['province'], $o['city'], $o['subdistrict'], '', $o['zip'], DEFAULT_NOTE,
    number_format(grossRevenue($o), 2, '.', ''), 10, 8, 6, 1, displayName($o),
], array_values(array_filter($orders, fn ($o) => in_array($o['id'], ['CBC-101', 'CBC-102', 'CBC-103', 'CBC-104', 'CBC-105'], true)))));

$sicepat = array_merge([[
    'Penerima', 'No.HP Penerima', 'Jumlah Paket', 'No.Referensi (Maksimal 50 Karakter)',
    'Alamat Penerima', 'Kecamatan', 'Kota/Kabupaten', 'Kode Pos', 'Layanan', 'Jenis Paket',
    'Isi Paket', 'Berat Paket (Kg)', 'Panjang Paket', 'Lebar Paket', 'Tinggi Paket',
    'Harga Paket', 'Packing Kayu', 'Proteksi Paket?', 'Total COD', 'COD Ongkir?',
    'Catatan Pengiriman', 'Tipe DO Balik', 'Tipe Alamat DO Balik', 'Alamat DO Balik',
    'Kecamatan DO Balik', 'Kota/Kabupaten DO Balik', 'Kode Pos DO Balik',
]], array_map(fn ($o) => [
    $o['name'], normalizePhone($o['phone']), storedProductName($o)[1], $o['id'],
    $o['address'], $o['subdistrict'], $o['city'], $o['zip'], '', 'Barang',
    displayName($o), 1, 10, 8, 6, number_format(grossRevenue($o), 2, '.', ''), '', '',
    $o['payment_method'] === 'cod' ? number_format(grossRevenue($o), 2, '.', '') : '', '', DEFAULT_NOTE,
    '', '', '', '', '', '',
], array_values(array_filter($orders, fn ($o) => in_array($o['id'], ['CBC-201', 'CBC-202', 'CBC-203'], true)))));

$spx = array_merge([[
    '*Nomor Pesanan', '*Nama Penerima // *Recipient Name',
    '*Nomor Telepon Penerima // *Recipient Phone', '*Alamat Lengkap // *Detail Address',
    '*Provinsi // *Province', '*Kota // *City', '*Kecamatan // *District',
    '*Kode Pos // *Postal Code', '*Berat Paket (KG) // *Parcel Weight (KG)',
    '*Harga Barang // *Parcel Value',
    '*COD? (Paket COD/Bukan Paket COD) // *COD? (COD Parcel//Non-COD Parcel)',
    '*Nominal COD yang harus ditagihkan ke Penerima // * COD Amount',
    '*Asuransi (Y/N) / *Insurance (Y/N)', 'Panjang Paket (CM) // Parcel Length (CM)',
    'Lebar Paket (CM) // Parcel Width (CM)', 'Tinggi Paket (CM) // Parcel Height (CM)',
    '*Nama Barang // *Item Name', 'Jumlah Barang // Item Quantity', 'Harga Barang // Item Price',
    'Nomer Referensi Pembeli // Customer Reference Number',
    '*Metode Pembayaran // *Payment Method', 'Instruksi Pengiriman // Delivery Instruction',
]], array_map(fn ($o) => [
    $o['id'], $o['name'], phoneSpx(normalizePhone($o['phone'])), $o['address'],
    mb_strtoupper($o['province']), mb_strtoupper($o['city']), mb_strtoupper($o['subdistrict']),
    $o['zip'], 1, number_format(grossRevenue($o), 2, '.', ''),
    $o['payment_method'] === 'cod' ? 'Y' : 'N',
    $o['payment_method'] === 'cod' ? number_format(grossRevenue($o), 2, '.', '') : '', 'N', 10, 8, 6,
    displayName($o), storedProductName($o)[1], number_format(grossRevenue($o), 2, '.', ''), $o['id'],
    strtoupper($o['payment_method']), DEFAULT_NOTE,
], array_values(array_filter($orders, fn ($o) => in_array($o['id'], ['CBC-301', 'CBC-302'], true)))));

writeCsv("$dir/02_export_flik.csv", $flik);
writeCsv("$dir/02_export_sicepat.csv", $sicepat);
writeCsv("$dir/02_export_spx.csv", $spx);

// ─── 03. File dashboard tracking (header asli aggregator) ──────────────────

// FLIK (29 kolom) — status: CBC-101 delivered, CBC-102 in_transit, CBC-103 returned,
//                   CBC-104 waiting_pickup, CBC-105 problem
$flikTrack = [
    'Order ID', 'AWB', 'Kurir', 'Service', 'Tanggal Pembuatan', 'Detail Penjemputan',
    'COD', 'Nama Shopper', 'No Telp', 'Ongkir Sebelum Diskon', 'Diskon',
    'Harga Setelah Diskon', 'Nominal COD', 'Status', 'Status Terakhir dari 3PL',
    'Nama Produk', 'Provinsi', 'Catatan Kurir', 'POD', 'Scheduled Pickup',
    'Terakhir Update', 'Nama Warehouse', 'Sumber', 'Komisi COD', 'Komisi Jagokurir',
    'Actual Pickup', 'Kecamatan', 'Kota', 'Alamat Lengkap Penerima',
];
$flikRows = [];
foreach ([
    'CBC-101' => ['FLIK20260809101', 'Terkirim', 'OK', '8/9/2026 17:34'],
    'CBC-102' => ['FLIK20260809102', 'Sedang Diantar', 'OK', '8/9/2026 18:10'],
    'CBC-103' => ['FLIK20260809103', 'Dikembalikan', 'OK', '8/10/2026 09:00'],
    'CBC-104' => ['FLIK20260809104', 'Dikonfirmasi', 'OK', '8/9/2026 12:05'],
    'CBC-105' => ['FLIK20260809105', 'Dikonfirmasi', 'Problem: alamat tidak lengkap', '8/9/2026 14:00'],
] as $id => [$awb, $st, $pl, $upd]) {
    $o = array_values(array_filter($orders, fn ($x) => $x['id'] === $id))[0];
    $flikRows[] = [
        $o['id'], $awb, 'SPX Express', 'Eco/Hemat', '8/8/2026 10:00', '', 'FALSE',
        $o['name'], normalizePhone($o['phone']), '', '', '', '',
        $st, $pl, displayName($o), $o['province'], '', 'FALSE', '',
        $upd, '', 'Online', '', '', '', $o['subdistrict'], $o['city'], $o['address'],
    ];
}
writeCsv("$dir/03_tracking_flik.csv", array_merge([$flikTrack], $flikRows));

// SICEPAT (43 kolom) — status: CBC-201 delivered, CBC-202 in_transit, CBC-203 returning
$sicepatTrack = [
    '#', 'Nomor Resi', 'Status', 'Tanggal', 'Tanggal Dipickup', 'Tanggal Terkirim',
    'Tanggal Dikembalikan', 'Nomor Pengiriman', 'Nomor Referensi', 'Nomor Multikoli',
    'Tipe Multikoli', 'Tipe DO Balik', 'Pengirim', 'Nama Penerima', 'Tipe Pembayaran',
    'Jenis Paket', 'Isi Paket', 'Jumlah Isi Paket', 'Alamat Penerima', 'Kecamatan',
    'Kota', 'Provinsi', 'Kode Pos', 'No. HP Penerima', 'Total Berat', 'Berat Asli',
    'Harga Ongkir', 'Proteksi Paket', 'Layanan', 'Harga Paket', 'Total Biaya',
    'Order Source', 'POD Bermasalah 1', 'POD Bermasalah 2', 'POD Bermasalah 3',
    'POD Delivery', 'POD DO Return', 'POD Attempt 1', 'Tanggal Attempt 1',
    'POD Attempt 2', 'Tanggal Attempt 2', 'POD Attempt 3', 'Tanggal Attempt 3',
];
$sicepatRows = [];
foreach ([
    'CBC-201' => ['SICEPAT2026080901', 'Terkirim', '09/08/2026 09:30:00'],
    'CBC-202' => ['SICEPAT2026080902', 'Proses pengiriman', ''],
    'CBC-203' => ['SICEPAT2026080903', 'Proses retur', ''],
] as $id => [$awb, $st, $tgl]) {
    $o = array_values(array_filter($orders, fn ($x) => $x['id'] === $id))[0];
    $sicepatRows[] = [
        '858', $awb, $st, '09/08/2026 08:00:00', '', $tgl, '', '2.63647684496224E+017', '',
        '', '', '', 'eresgestore', $o['name'], 'cod', 'Barang', displayName($o),
        storedProductName($o)[1], $o['address'], $o['subdistrict'], $o['city'],
        $o['province'], $o['zip'], normalizePhone($o['phone']), '1', '1', '', '', '',
        $o['product_price'], '', 'Online', '', '', '', '', '', '', '', '', '', '',
    ];
}
writeCsv("$dir/03_tracking_sicepat.csv", array_merge([$sicepatTrack], $sicepatRows));

// SPX (47 kolom) — status: CBC-301 delivered, CBC-302 returned
$spxTrack = [
    'Tracking No.', 'Tracking No. link', 'Customer Reference No.',
    'Customer Reference No. link', 'Create Time', 'Tracking Status', 'Account ID',
    'Original pickup option', 'Actual pickup option', 'Scheduled Pickup Time',
    'Actual Pickup/Drop Off Time', 'Delivered Time', 'Delivery OnHold Times',
    'Delivery OnHold Reason', 'Returning Start Time', 'Recipient Name',
    'Recipient Phone Number', 'Recipient Province', 'Recipient City',
    'Recipient District', 'Recipient Detail Address', 'Recipient Postal Code',
    'Sender Name', 'Sender Phone Number', 'Sender Province', 'Sender City',
    'Sender District', 'Sender Detail Address', 'Sender Postal Code', 'Payment Role',
    'Item List', 'Item in Parcel', 'No. of item in Parcel', 'COD Collection(Y/N)',
    'COD Amount', 'Parcel Value', 'Parcel Weight', 'Actual Weight',
    'Estimated Shipping Fee', 'Actual Shipping Fee', 'Basic Shipping Fee',
    'Insurance Fee', 'COD Service Fee', 'Return Shipping Fee',
    'Delivery failed Reason', 'Create Method', 'Order Creator',
];
$spxRows = [];
foreach ([
    'CBC-301' => ['SPXID0012026080501', 'Delivered', '05-08-2026 14:20'],
    'CBC-302' => ['SPXID0012026080502', 'Returned', ''],
] as $id => [$awb, $st, $delivered]) {
    $o = array_values(array_filter($orders, fn ($x) => $x['id'] === $id))[0];
    $spxRows[] = [
        $awb, 'https://spx.co.id/track?'.$awb, '-', '-', '03-08-2026 10:00', $st,
        '1569304762', 'Pickup', 'Pickup', '', '', $delivered, '', '', '', $o['name'],
        phoneSpx(normalizePhone($o['phone'])), mb_strtoupper($o['province']),
        mb_strtoupper($o['city']), mb_strtoupper($o['subdistrict']), $o['address'],
        $o['zip'], '', '', '', '', '', '', '', '', '', displayName($o),
        storedProductName($o)[1], $o['payment_method'] === 'cod' ? 'Y' : 'N',
        '', '', '1', '', '', '', '', '', '', '', '', '',
    ];
}
writeCsv("$dir/03_tracking_spx.csv", array_merge([$spxTrack], $spxRows));

echo "\nSelesai. Status tracking yang tercakup:\n";
echo "  FLIK:   delivered, in_transit, returned, waiting_pickup, problem\n";
echo "  SICEPAT: delivered, in_transit, returning\n";
echo "  SPX:    delivered, returned\n";
