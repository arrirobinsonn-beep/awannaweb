<?php

/*
 * Generator file uji rules import order online.
 *
 * Header 52 kolom PERSIS sama dengan training/DataDariOrderOnline(mentah).csv.
 * Tiap baris mewakili 1 rule; kolom `name` = label ekspektasi singkat (tampil di
 * tabel /orders), kolom `notes` = penjelasan ekspektasi (bisa dibaca saat file
 * dibuka di Excel).
 *
 * Jalankan: php training/make_test_rules_csv.php
 */

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

$prod = 'A.3 Kacamata Multifokus Photocromic';
$prodMeta = $prod.' - 13722';

function row(array $data, string $status, string $paymentStatus, string $paymentMethod, string $productCode, array $extra = []): array
{
    return array_merge([
        'status' => $status,
        'payment_status' => $paymentStatus,
        'payment_method' => $paymentMethod,
        'product_code' => $productCode,
        'quantity' => 1,
        'product_price' => 129000,
    ], $data, $extra);
}

$rows = [

    // ── Status mapping ────────────────────────────────────────────────
    row(['order_id' => 'RULES-001', 'product' => $prod, 'name' => 'EXP: real / sicepat',
        'phone' => '08123450001', 'address' => 'Jl. Uji 1', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'variation' => 'Ukuran: Usia 44-45 Tahun Plus +1.50',
        'notes' => 'status=processing -> real; cod+Jabar -> sicepat; Plus +1.50 -> varian KMP+1.5'],
        'processing', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-002', 'product' => $prod, 'name' => 'EXP: tembakan / spx',
        'phone' => '08123450002', 'address' => 'Jl. Uji 2', 'province' => 'Sumatera Barat', 'city' => 'Kab. Padang Pariaman', 'subdistrict' => 'Nan Sabaris', 'zip' => '25571',
        'notes' => 'status=pending+paid -> tembakan; tembakan SELALU courier spx (override rules)'],
        'pending', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-003', 'product' => $prod, 'name' => 'EXP: belum_diproses / -',
        'phone' => '08123450003', 'address' => 'Jl. Uji 3', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'status=pending+unpaid -> belum_diproses; courier null (badge "-")'],
        'pending', 'unpaid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-004', 'product' => $prod, 'name' => 'EXP: cancel / -',
        'phone' => '08123450004', 'address' => 'Jl. Uji 4', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'status=cancelled -> cancel; courier null'],
        'cancelled', 'paid', 'bank_transfer', 'KMP'),

    row(['order_id' => 'RULES-005', 'product' => $prod, 'name' => 'EXP: completed (di-skip)',
        'phone' => '08123450005', 'address' => 'Jl. Uji 5', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'status=completed -> TIDAK disimpan; muncul di pesan "errors" modal Preview, tidak ikut batch'],
        'completed', 'paid', 'cod', 'KMP'),

    // ── Courier rules (real) ──────────────────────────────────────────
    row(['order_id' => 'RULES-006', 'product' => $prod, 'name' => 'EXP: flix-tf',
        'phone' => '08123450006', 'address' => 'Jl. Uji 6', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'payment_method=bank_transfer -> flix-tf (semua provinsi)'],
        'processing', 'paid', 'bank_transfer', 'KMP'),

    row(['order_id' => 'RULES-007', 'product' => $prod, 'name' => 'EXP: flix-idx',
        'phone' => '08123450007', 'address' => 'Jl. Uji 7', 'province' => 'Sumatera Barat', 'city' => 'Kab. Padang Pariaman', 'subdistrict' => 'Nan Sabaris', 'zip' => '25571',
        'notes' => 'cod + provinsi Sumatera -> flix-idx; sekaligus uji kalibrasi "Sumatera"->"SUMATRA BARAT"'],
        'processing', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-008', 'product' => $prod, 'name' => 'EXP: sicepat',
        'phone' => '08123450008', 'address' => 'Jl. Uji 8', 'province' => 'Jakarta', 'city' => 'Kebayoran Baru', 'subdistrict' => 'Kebayoran Baru', 'zip' => '12120',
        'notes' => 'cod + Jawa/Bali -> sicepat (template SiCepat); sekaligus uji mapping "Jakarta" -> "DKI JAKARTA"'],
        'processing', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-009', 'product' => $prod, 'name' => 'EXP: flix-spx',
        'phone' => '08123450009', 'address' => 'Jl. Uji 9', 'province' => 'Kalimantan Selatan', 'city' => 'Banjarmasin', 'subdistrict' => 'Banjarmasin Barat', 'zip' => '70114',
        'notes' => 'cod + Kalimantan -> flix-spx'],
        'processing', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-010', 'product' => $prod, 'name' => 'EXP: spx (fallback)',
        'phone' => '08123450010', 'address' => 'Jl. Uji 10', 'province' => 'Pulau Tak Dikenal', 'city' => 'Kota Misteri', 'subdistrict' => 'Xyz', 'zip' => '00000',
        'notes' => 'provinsi tidak punya rule -> fallback spx'],
        'processing', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-011', 'product' => $prod, 'name' => 'EXP: flix-spx',
        'phone' => '08123450011', 'address' => 'Jl. Uji 11', 'province' => 'Aceh', 'city' => 'Banda Aceh', 'subdistrict' => 'Kuta Alam', 'zip' => '23126',
        'notes' => 'uji mapping "Aceh" -> "NANGGROE ACEH DARUSSALAM (NAD)" -> flix-spx'],
        'processing', 'paid', 'cod', 'KMP'),

    // ── Deteksi duplikat (belum_diproses) ─────────────────────────────
    row(['order_id' => 'RULES-012', 'product' => $prod, 'name' => 'EXP: belum_diproses (baru)',
        'phone' => '08123450012', 'address' => 'Jl. Duplikat 1', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'signature phone+product+address pertama -> normal belum_diproses'],
        'pending', 'unpaid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-013', 'product' => $prod, 'name' => 'EXP: duplikat / -',
        'phone' => '08123450012', 'address' => 'Jl. Duplikat 1', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'phone+product+address SAMA dengan RULES-012 -> duplikat; courier null; jumlah "Duplikat" di pesan import = 1'],
        'pending', 'unpaid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-014', 'product' => $prod, 'name' => 'EXP: real (promosi)',
        'phone' => '08123450013', 'address' => 'Jl. Duplikat 2', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'status real/processing tidak pernah ditandai duplikat, juga TIDAK menambah signature duplikat'],
        'processing', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-015', 'product' => $prod, 'name' => 'EXP: belum_diproses (tidak duplikat)',
        'phone' => '08123450013', 'address' => 'Jl. Duplikat 2', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'signature sama dengan RULES-014 (real) tetapi real tidak menambah signature -> tetap belum_diproses, BUKAN duplikat'],
        'pending', 'unpaid', 'cod', 'KMP'),

    // ── Produk & varian ───────────────────────────────────────────────
    row(['order_id' => 'RULES-016', 'product' => $prod, 'name' => 'EXP: PC=KMP+1.75',
        'phone' => '08123450016', 'address' => 'Jl. Uji 16', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'variation' => 'Ukuran: Usia 45-46 Tahun Plus +1.75',
        'notes' => 'product_code KMP + variation "Plus +1.75" -> varian KMP+1.75 (product_code di DB menjadi KMP+1.75)'],
        'processing', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-017', 'product' => $prod, 'name' => 'EXP: PC=KMP+1 (default)',
        'phone' => '08123450017', 'address' => 'Jl. Uji 17', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'product_code KMP tanpa variation -> varian default power terkecil (KMP+1)'],
        'processing', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-018', 'product' => $prod, 'name' => 'EXP: PC tetap XYZ999 (tidak dikenal)',
        'phone' => '08123450018', 'address' => 'Jl. Uji 18', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'notes' => 'product_code tidak terdaftar -> product_id null; courier tetap terisi; saat export order ini dilewati (stock_note "Produk tidak dikenal")'],
        'processing', 'paid', 'cod', 'XYZ999'),

    row(['order_id' => 'RULES-019', 'product' => $prodMeta, 'name' => 'EXP: qty2, nama "...2 pcs", PC=KMP+1.25',
        'phone' => '08123450019', 'address' => 'Jl. Uji 19', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'variation' => 'Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 43-44 Tahun Plus +1.25',
        'notes' => 'variation mengandung "Dapat 2" -> qty override 2, product_name jadi "... 2 pcs"; angka "13722" (setelah " - ") masuk meta_account (DB saja); varian KMP+1.25'],
        'processing', 'paid', 'cod', 'KMP'),

    row(['order_id' => 'RULES-020', 'product' => $prodMeta, 'name' => 'EXP: qty3 (tanpa Dapat), PC=KMP+1 (default)',
        'phone' => '08123450020', 'address' => 'Jl. Uji 20', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'quantity' => 3,
        'notes' => 'tanpa "Dapat" -> qty ikut kolom CSV (3), nama TIDAK ditambah "pcs"; meta_account "13722"; varian default KMP+1'],
        'processing', 'paid', 'cod', 'KMP'),

    // ── CS handling ───────────────────────────────────────────────────
    row(['order_id' => 'RULES-021', 'product' => $prod, 'name' => 'EXP: CS tak dikenal (warning)',
        'phone' => '08123450021', 'address' => 'Jl. Uji 21', 'province' => 'Jawa Barat', 'city' => 'Bandung', 'subdistrict' => 'Coblong', 'zip' => '40132',
        'handled_by' => 'TESTCS-BUKAN-USER',
        'notes' => 'handled_by tidak cocok dengan users -> muncul di pesan "CS Tak Dikenal" (jumlah = 1)'],
        'processing', 'paid', 'cod', 'KMP'),
];

$path = __DIR__.'/TestRules_OrderOnline.csv';
$handle = fopen($path, 'w');

fputcsv($handle, $header);
foreach ($rows as $r) {
    $line = array_map(fn ($h) => $r[$h] ?? '', $header);
    fputcsv($handle, $line);
}
fclose($handle);

echo 'OK: '.count($rows)." baris ditulis ke $path\n";
