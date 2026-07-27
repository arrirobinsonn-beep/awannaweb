<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "Total PaketTracking: " . \App\Models\PaketTracking::count() . PHP_EOL;
echo "Dikonfirmasi count: " . \App\Models\PaketTracking::where('status', 'Dikonfirmasi')->count() . PHP_EOL;
echo "Date >= 2026-07-01: " . \App\Models\PaketTracking::where('tanggal_pembuatan', '>=', '2026-07-01')->count() . PHP_EOL;
echo "Date between 07-01 and 07-27: " . \App\Models\PaketTracking::whereBetween('tanggal_pembuatan', ['2026-07-01', '2026-07-27'])->count() . PHP_EOL;
echo "Date between 06-01 and 07-27: " . \App\Models\PaketTracking::whereBetween('tanggal_pembuatan', ['2026-06-01', '2026-07-27'])->count() . PHP_EOL;
echo PHP_EOL . "All statuses:" . PHP_EOL;
$stats = \App\Models\PaketTracking::selectRaw('status, COUNT(*) as total')->groupBy('status')->orderBy('status')->get();
foreach ($stats as $row) { echo "  '" . $row->status . "' = " . $row->total . PHP_EOL; }
echo PHP_EOL . "First 3 dates:" . PHP_EOL;
$all = \App\Models\PaketTracking::orderBy('tanggal_pembuatan')->take(3)->get();
foreach ($all as $a) { echo "  date=" . $a->tanggal_pembuatan . " status=" . $a->status . PHP_EOL; }
echo "Last 3 dates:" . PHP_EOL;
$last = \App\Models\PaketTracking::orderBy('tanggal_pembuatan', 'desc')->take(3)->get();
foreach ($last as $l) { echo "  date=" . $l->tanggal_pembuatan . " status=" . $l->status . PHP_EOL; }
echo PHP_EOL . "CategorizeStatus('Dikonfirmasi') => " . (new \App\Http\Controllers\DashboardController)->categorizeStatus('Dikonfirmasi') . PHP_EOL;