<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== handle_by analysis ===\n\n";

// Check handle_by values in PaketTracking
$handleBy = \App\Models\PaketTracking::selectRaw('handle_by, COUNT(*) as total')
    ->groupBy('handle_by')
    ->orderByDesc('total')
    ->pluck('total', 'handle_by');
echo "handle_by values (top 10):\n";
foreach ($handleBy->take(10) as $hb => $total) {
    echo "  '$hb' => $total\n";
}
echo "Total with handle_by set (non-empty): " . $handleBy->filter(function($v, $k) { return $k !== null && $k !== ''; })->count() . "\n";
echo "\n";

// Check OrderOnlineContact table
echo "Checking OrderOnlineContact...\n";
$hasTable = \Illuminate\Support\Facades\Schema::hasTable('order_online_contacts');
echo "order_online_contacts table exists: " . ($hasTable ? 'YES' : 'NO') . "\n";
if ($hasTable) {
    $count = \App\Models\OrderOnlineContact::count();
    echo "order_online_contacts count: $count\n";
    if ($count > 0) {
        $sample = \App\Models\OrderOnlineContact::first();
        echo "Sample record:\n";
        echo "  id=" . ($sample->id ?? 'N/A') . "\n";
        echo "  phone=" . ($sample->phone ?? 'null') . "\n";
        echo "  cs_name=" . ($sample->cs_name ?? 'null') . "\n";
        echo "  advertiser_id=" . ($sample->advertiser_id ?? 'null') . "\n";
    }
} else {
    echo "Table does not exist!\n";
}

echo "\n=== Check kiriman import for handle_by logic ===\n";
$kirimanService = file_get_contents('app/Services/KirimanImportService.php');
echo "KirimanImportService references handle_by: " . (strpos($kirimanService, 'handle_by') !== false ? 'YES' : 'NO') . "\n";
echo "KirimanImportService references no_telp: " . (strpos($kirimanService, 'no_telp') !== false ? 'YES' : 'NO') . "\n";