<?php

/*
 * Verifikasi pipeline order-online end-to-end dengan file di folder filecoba/:
 *
 *   1. Import data mentah (01_*) → cek courier/status/varian/qty per order
 *   2. Export (02_* referensi) → jalankan OrderTemplateExportService, diff dgn referensi
 *   3. Tracking (03_*) → jalankan AggregatorTrackingImportService, cek awb/status/
 *      delivered_at + stok kembali saat `returned` + idempotent re-import
 *   4. Stok: capture sebelum export → sesudah export → sesudah tracking
 *
 * Syarat: produk (ProductSeeder), courier_rules (CourierRuleSeeder), dan
 * export_template_mappings (ExportTemplateMappingSeeder) sudah di-seed di DB
 * aktif (.env). Skrip TIDAK menjalankan seeder (semua seeder truncate).
 * Skrip boleh dijalankan ulang: order CBC-* lama di-bersihkan dulu (jurnal + row).
 *
 * Jalankan: php filecoba/verify_pipeline.php
 */

use App\Models\CourierRule;
use App\Models\ExportTemplateMapping;
use App\Models\TrackingStatusRule;
use App\Models\OrderOnlineImportBatch;
use App\Models\ProductVariant;
use App\Models\ShippingOrder;
use App\Services\AggregatorTrackingImportService;
use App\Services\OrderOnlineImportService;
use App\Services\OrderTemplateExportService;
use App\Services\StockService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$dir = __DIR__;
$results = [];

function check(string $label, bool $ok, string $detail = ''): void
{
    global $results;
    $results[] = [$label, $ok];
    echo ($ok ? '  [PASS] ' : '  [FAIL] ').$label.($detail && ! $ok ? " — {$detail}" : '')."\n";
}

function summary(): int
{
    global $results;
    $fails = array_filter($results, fn ($r) => ! $r[1]);
    $passed = count($results) - count($fails);
    echo "\n=== Hasil: {$passed}/".count($results).' PASS, '.count($fails)." FAIL ===\n";
    foreach ($fails as $f) {
        echo '  FAIL: '.$f[0]."\n";
    }

    return $fails ? 1 : 0;
}

function variantByCode(string $code): ?ProductVariant
{
    return ProductVariant::where('code', $code)->first();
}

function stockOf(?ProductVariant $v): int
{
    return $v ? (new StockService)->stockOf($v->id) : -1;
}

function invoke($obj, string $method, array $args)
{
    $ref = new ReflectionMethod($obj, $method);
    $ref->setAccessible(true);

    return $ref->invokeArgs($obj, $args);
}

function readCsvRows(string $path): array
{
    $rows = [];
    $h = fopen($path, 'r');
    while (($r = fgetcsv($h)) !== false) {
        $rows[] = $r;
    }
    fclose($h);

    return $rows;
}

echo "── Precheck ────────────────────────────────────────────────\n";
check('courier_rules terisi', CourierRule::count() > 0,
    'jalankan: php artisan db:seed --class=CourierRuleSeeder');
check('produk & varian ter-seed', ProductVariant::count() > 0,
    'jalankan: php artisan db:seed --class=ProductSeeder');
check('export_template_mappings terisi (Aturan Export)', ExportTemplateMapping::count() > 0,
    'jalankan: php artisan db:seed --class=ExportTemplateMappingSeeder');
check('tracking_status_rules terisi (Aturan Status)', TrackingStatusRule::count() > 0,
    'jalankan: php artisan db:seed --class=TrackingStatusRuleSeeder');
if (CourierRule::count() === 0 || ProductVariant::count() === 0 || ExportTemplateMapping::count() === 0 || TrackingStatusRule::count() === 0) {
    exit(summary());
}

$orderIds = ['CBC-101', 'CBC-102', 'CBC-103', 'CBC-104', 'CBC-105', 'CBC-201', 'CBC-202', 'CBC-203', 'CBC-301', 'CBC-302'];

echo "\n── Bersihkan sisa run sebelumnya (re-runnable) ─────────────\n";
$stock = new StockService;
$removed = 0;
foreach (ShippingOrder::whereIn('order_id', $orderIds)->get() as $o) {
    $stock->reverseReference('order_online', $o->id);
    $o->delete();
    $removed++;
}
echo "  Dihapus {$removed} order lama (jurnal dibalik).\n";

// ─── 1. IMPORT ──────────────────────────────────────────────────────────────
echo "\n── 1. Import data mentah ─────────────────────────────────\n";
$import = (new OrderOnlineImportService)->import("$dir/01_order_online_mentah.csv", 'GUDANG-PUSAT');
echo '  report: '.json_encode($import)."\n";
check('inserted = 10', ($import['inserted'] ?? 0) === 10, 'inserted='.($import['inserted'] ?? '?'));
check('tidak ada duplikat/double_real', ($import['duplicates'] ?? 1) === 0 && ($import['double_real'] ?? 1) === 0);

$batch = OrderOnlineImportBatch::where('sender', 'GUDANG-PUSAT')->orderByDesc('id')->first();
check('batch terbuat', $batch !== null);
$orders = ShippingOrder::where('order_online_import_batch_id', $batch->id)->get()->keyBy('order_id');

$expectedCourier = ['CBC-101' => 'flix-tf', 'CBC-102' => 'flix-tf', 'CBC-103' => 'flix-tf', 'CBC-104' => 'flix-tf', 'CBC-105' => 'flix-tf', 'CBC-201' => 'sicepat', 'CBC-202' => 'sicepat', 'CBC-203' => 'sicepat', 'CBC-301' => 'spx', 'CBC-302' => 'spx'];
$expectedStatus = ['CBC-101' => 'real', 'CBC-102' => 'real', 'CBC-103' => 'real', 'CBC-104' => 'real', 'CBC-105' => 'real', 'CBC-201' => 'real', 'CBC-202' => 'real', 'CBC-203' => 'real', 'CBC-301' => 'tembakan', 'CBC-302' => 'tembakan'];
$expectedQty = ['CBC-101' => 1, 'CBC-102' => 2, 'CBC-103' => 1, 'CBC-104' => 2, 'CBC-105' => 1, 'CBC-201' => 1, 'CBC-202' => 3, 'CBC-203' => 1, 'CBC-301' => 2, 'CBC-302' => 1];
$expectedCode = ['CBC-101' => 'KMP+1.5', 'CBC-102' => 'KMP+1', 'CBC-103' => 'KSP+2', 'CBC-104' => 'KBJ+1.25', 'CBC-105' => 'KCHP', 'CBC-201' => 'KMP+1.75', 'CBC-202' => 'KMP+2.25', 'CBC-203' => 'KCHP', 'CBC-301' => 'KBJ+1.5', 'CBC-302' => 'KMP+1.25'];

foreach ($orderIds as $id) {
    $o = $orders[$id] ?? null;
    if ($o === null) {
        check("$id tersimpan", false, 'tidak ada di batch');

        continue;
    }
    check("$id courier={$expectedCourier[$id]}", $o->courier === $expectedCourier[$id], 'courier='.($o->courier ?? 'null'));
    check("$id status={$expectedStatus[$id]}", $o->status === $expectedStatus[$id], 'status='.$o->status);
    check("$id qty={$expectedQty[$id]}", (int) $o->quantity === $expectedQty[$id], 'qty='.$o->quantity);
    check("$id product_code={$expectedCode[$id]}", $o->product_code === $expectedCode[$id], 'product_code='.$o->product_code);
}
check('CBC-301 product_name "...2 pcs"', ($orders['CBC-301']->product_name ?? '') === 'A.3 Kacamata Baca & Jalan 2 pcs');

// ─── Stok sebelum export ─────────────────────────────────────────────────────
echo "\n── Stok (sebelum export) ─────────────────────────────────\n";
$trackVariants = [
    'KMP+1.5' => variantByCode('KMP+1.5'),
    'KMP+1' => variantByCode('KMP+1'),
    'KSP+2' => variantByCode('KSP+2'),
    'KBJ+1.25' => variantByCode('KBJ+1.25'),
    'KDF+1.25' => variantByCode('KDF+1.25'),
    'KMP+1.25' => variantByCode('KMP+1.25'),
    'BOX' => variantByCode('BOX'),
    'LAP' => variantByCode('LAP'),
];
$before = [];
foreach ($trackVariants as $label => $v) {
    $before[$label] = stockOf($v);
    echo "  {$label}: stok awal = {$before[$label]}\n";
}

// ─── 2. EXPORT ──────────────────────────────────────────────────────────────
echo "\n── 2. Export & diff dgn referensi ────────────────────────\n";
$export = new OrderTemplateExportService;
$templateDefs = ['flik' => true, 'sicepat' => false, 'spx' => false]; // [template => pakai sender?]
foreach ($templateDefs as $tpl => $useSender) {
    $templateOrders = ShippingOrder::where('order_online_import_batch_id', $batch->id)
        ->whereIn('status', ShippingOrder::EXPORTABLE_STATUSES)
        ->whereIn('courier', $export->couriersForTemplate($tpl))
        ->with('variant')
        ->orderBy('order_id')
        ->get();

    $exportable = invoke($export, 'reserveStock', [$templateOrders]);
    $actual = invoke($export, 'buildRows', [$tpl, $exportable, $useSender ? $batch->sender : null]);

    $refRows = readCsvRows("$dir/02_export_{$tpl}.csv");
    $mismatch = null;
    $wc = max(count($refRows), count($actual));
    for ($r = 0; $r < $wc; $r++) {
        $ra = $refRows[$r] ?? [];
        $ac = $actual[$r] ?? [];
        $maxc = max(count($ra), count($ac));
        for ($c = 0; $c < $maxc; $c++) {
            $va = (string) ($ra[$c] ?? '');
            $vb = (string) ($ac[$c] ?? '');
            if ($va !== $vb) {
                $mismatch = "row=$r col=$c ref='$va' actual='$vb'";
                break 2;
            }
        }
    }
    check("export {$tpl} identik dgn referensi (".count($actual).' baris)', $mismatch === null, $mismatch ?? '');

    $handle = fopen("$dir/actual_export_{$tpl}.csv", 'w');
    foreach ($actual as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    echo "  actual_export_{$tpl}.csv ditulis (".count($actual)." baris).\n";
}

$after = [];
foreach ($trackVariants as $label => $v) {
    $after[$label] = stockOf($v);
}
$delta = fn ($label) => ($before[$label] ?? 0) - ($after[$label] ?? 0);
echo '  Delta stok sesudah export: KMP+1.5='.$delta('KMP+1.5').' KMP+1='.$delta('KMP+1').' KSP+2='.$delta('KSP+2').' KBJ+1.25='.$delta('KBJ+1.25').' KDF+1.25='.$delta('KDF+1.25').' KMP+1.25='.$delta('KMP+1.25').' BOX='.$delta('BOX').' LAP='.$delta('LAP')."\n";
check('packaging/split: KSP+2 −1 (CBC-103)', $delta('KSP+2') === 1);
check('packaging/split: KMP+1.25 −1 (CBC-302)', $delta('KMP+1.25') === 1);
check('packaging/split: KMP+1.5 −1 (CBC-101)', $delta('KMP+1.5') === 1);
check('packaging/split: KDF+1.25 −1 (KBJ qty2 CBC-104 → 1 KDF)', $delta('KDF+1.25') === 1);
check('packaging/split: BOX −4 (2+1+1+1+1)', $delta('BOX') === 4, 'BOX delta='.$delta('BOX'));
check('packaging/split: LAP −4', $delta('LAP') === 4, 'LAP delta='.$delta('LAP'));

// ─── 3. TRACKING ────────────────────────────────────────────────────────────
echo "\n── 3. Import tracking aggregator ─────────────────────────\n";
$tracking = new AggregatorTrackingImportService;

$flik = $tracking->import("$dir/03_tracking_flik.csv");
echo '  FLIK:   '.json_encode(array_intersect_key($flik, array_flip(['source', 'total', 'matched', 'updated', 'stock_returned', 'unmatched', 'ambiguous'])))."\n";
check('FLIK matched 5/5', $flik['matched'] === 5 && $flik['total'] === 5, 'matched='.$flik['matched']);
check('FLIK returned 1 (CBC-103)', $flik['stock_returned'] === 1, 'stock_returned='.$flik['stock_returned']);
check('FLIK tidak ada unmatched/ambiguous', empty($flik['unmatched']) && empty($flik['ambiguous']));

$sicepat = $tracking->import("$dir/03_tracking_sicepat.csv");
echo '  SICEPAT: '.json_encode(array_intersect_key($sicepat, array_flip(['source', 'total', 'matched', 'updated', 'stock_returned', 'unmatched', 'ambiguous'])))."\n";
check('SICEPAT matched 3/3', $sicepat['matched'] === 3 && $sicepat['total'] === 3);
check('SICEPAT tidak ada returned', $sicepat['stock_returned'] === 0);

$spx = $tracking->import("$dir/03_tracking_spx.csv");
echo '  SPX:    '.json_encode(array_intersect_key($spx, array_flip(['source', 'total', 'matched', 'updated', 'stock_returned', 'unmatched', 'ambiguous'])))."\n";
check('SPX matched 2/2', $spx['matched'] === 2 && $spx['total'] === 2);
check('SPX returned 1 (CBC-302)', $spx['stock_returned'] === 1);

$trackingOrders = ShippingOrder::whereIn('order_id', $orderIds)->get()->keyBy('order_id');
$expectedTracking = [
    'CBC-101' => ['FLIK20260809101', 'delivered', '2026-08-09 17:34:00'],
    'CBC-102' => ['FLIK20260809102', 'in_transit', null],
    'CBC-103' => ['FLIK20260809103', 'returned', null],
    'CBC-104' => ['FLIK20260809104', 'waiting_pickup', null],
    'CBC-105' => ['FLIK20260809105', 'problem', null],
    'CBC-201' => ['SICEPAT2026080901', 'delivered', '2026-08-09 09:30:00'],
    'CBC-202' => ['SICEPAT2026080902', 'in_transit', null],
    'CBC-203' => ['SICEPAT2026080903', 'returning', null],
    'CBC-301' => ['SPXID0012026080501', 'delivered', '2026-08-05 14:20:00'],
    'CBC-302' => ['SPXID0012026080502', 'returned', null],
];
foreach ($expectedTracking as $id => [$awb, $st, $dAt]) {
    $o = $trackingOrders[$id] ?? null;
    if ($o === null) {
        check("$id tracking terisi", false, 'order tidak ditemukan');

        continue;
    }
    check("$id awb=$awb", $o->awb === $awb, 'awb='.($o->awb ?? 'null'));
    check("$id status=$st", $o->aggregator_status === $st, 'status='.($o->aggregator_status ?? 'null'));
    check("$id delivered_at=".($dAt ?? 'null'), (string) ($o->delivered_at ?? '') === (string) ($dAt ?? ''), 'delivered_at='.($o->delivered_at ?? 'null'));
}

// ─── 4. STOK SETELAH TRACKING (returned → kembali) ─────────────────────────
echo "\n── 4. Stok setelah tracking (returned → balik) ────────────\n";
foreach ($trackVariants as $label => $v) {
    $now = stockOf($v);
    echo "  {$label}: {$now} (awal {$before[$label]})\n";
}
check('returned CBC-103: KSP+2 kembali ke stok awal', stockOf($trackVariants['KSP+2']) === $before['KSP+2']);
check('returned CBC-302: KMP+1.25 kembali ke stok awal', stockOf($trackVariants['KMP+1.25']) === $before['KMP+1.25']);
check('delivered CBC-101: KMP+1.5 tetap ter-reserve (−1)', stockOf($trackVariants['KMP+1.5']) === $before['KMP+1.5'] - 1);
check('waiting_pickup CBC-104: KBJ+1.25 tetap ter-reserve (−1, split KBJ qty2→1)', stockOf($trackVariants['KBJ+1.25']) === $before['KBJ+1.25'] - 1);
check('KBJ split KDF tetap ter-reserve (−1)', stockOf($trackVariants['KDF+1.25']) === $before['KDF+1.25'] - 1);

// ─── 5. IDEMPOTEN RE-IMPORT ────────────────────────────────────────────────
echo "\n── 5. Re-import FLIK (idempotent) ─────────────────────────\n";
$reflik = $tracking->import("$dir/03_tracking_flik.csv");
echo '  FLIK (ulang): stock_returned='.$reflik['stock_returned']."\n";
check('re-import returned tidak menggandakan (stock_returned=0)', $reflik['stock_returned'] === 0);

// ─── 6. RE-EXPORT SETELAH SEMUA ORDER BER-AWB → HANYA HEADER ───────────────
echo "\n── 6. Re-export setelah seluruh order ber-AWB → hanya header ─\n";
check('semua 10 order sudah ber-AWB', ShippingOrder::whereIn('order_id', $orderIds)->whereNull('awb')->count() === 0);
foreach (['flik' => true, 'sicepat' => false, 'spx' => false] as $tpl => $useSender) {
    $templateOrders = ShippingOrder::where('order_online_import_batch_id', $batch->id)
        ->whereIn('status', ShippingOrder::EXPORTABLE_STATUSES)
        ->where(fn ($q) => $q->whereNull('awb')->orWhere('awb', ''))
        ->whereIn('courier', $export->couriersForTemplate($tpl))
        ->with('variant')
        ->orderBy('order_id')
        ->get();

    $exportable = invoke($export, 'reserveStock', [$templateOrders]);
    $actual = invoke($export, 'buildRows', [$tpl, $exportable, $useSender ? $batch->sender : null]);
    check("re-export {$tpl} hanya header (0 order ber-AWB)", count($actual) === 1, 'baris='.count($actual));
}

exit(summary());
