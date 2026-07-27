<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "PaketTracking count: " . \App\Models\PaketTracking::count() . PHP_EOL;
echo "Statuses:" . PHP_EOL;
$statuses = \App\Models\PaketTracking::selectRaw("status, COUNT(*) as total")
    ->groupBy("status")
    ->orderBy("status")
    ->get();
foreach ($statuses as $row) {
    echo "  '" . $row->status . "' => " . $row->total . PHP_EOL;
}

echo PHP_EOL . "Date range:" . PHP_EOL;
echo "Min: " . (\App\Models\PaketTracking::min("tanggal_pembuatan")->format("Y-m-d") ?? "null") . PHP_EOL;
echo "Max: " . (\App\Models\PaketTracking::max("tanggal_pembuatan")->format("Y-m-d") ?? "null") . PHP_EOL;

echo PHP_EOL . "Categories mapping:" . PHP_EOL;
$kategoris = [
    'proses_retur' => ['label' => 'Proses Retur', 'icon' => '🔄', 'color' => '#f59e0b'],
    'retur' => ['label' => 'Retur', 'icon' => '↩️', 'color' => '#ef4444'],
    'proses_kirim' => ['label' => 'Proses Pengiriman', 'icon' => '🚚', 'color' => '#06b6d4'],
    'terkirim' => ['label' => 'Terkirim', 'icon' => '✅', 'color' => '#10b981'],
    'bermasalah' => ['label' => 'Bermasalah', 'icon' => '⛔', 'color' => '#dc2626'],
];

$allStatuses = \App\Models\PaketTracking::selectRaw("status, COUNT(*) as total")
    ->groupBy("status")
    ->pluck("total", "status");

echo "  categorizeStatus mapping:" . PHP_EOL;
foreach ($allStatuses as $status => $count) {
    $s = strtolower(trim($status));
    $cat = 'unknown';
    if (in_array($s, ['retur', 'diretur', 'return', 'return to sender', 'rts'])) {
        $cat = 'retur';
    } elseif (in_array($s, ['proses retur', 'dalam retur', 'retur dalam proses', 'pengembalian'])) {
        $cat = 'proses_retur';
    } elseif (in_array($s, ['proses kirim', 'dikirim', 'dalam pengiriman', 'out for delivery', 'shipping', 'pengiriman'])) {
        $cat = 'proses_kirim';
    } elseif (in_array($s, ['terkirim', 'diterima', 'delivered'])) {
        $cat = 'terkirim';
    } elseif (in_array($s, ['bermasalah', 'bermasalah', 'exception', 'gagal'])) {
        $cat = 'bermasalah';
    }
    echo "    '$status' => cat: $cat, count: $count" . PHP_EOL;
}
