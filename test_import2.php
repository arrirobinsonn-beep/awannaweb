<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check for Kiriman Excel files
echo "=== Files in storage/app ===\n";
$files = glob(storage_path('app/*.xlsx'));
foreach ($files as $f) {
    echo "  - " . basename($f) . " (" . filesize($f) . " bytes)\n";
}

// Parse the debug file if it exists
$debugFile = storage_path('app/debug_test.xlsx');
if (file_exists($debugFile)) {
    echo "\n=== Parsing debug_test.xlsx ===\n";
    $s = $app->make(App\Services\KirimanImportService::class);
    try {
        $r = $s->parseExcel($debugFile);
        echo "Rows: " . count($r['data']) . "\n";
        echo "Groups: " . count($r['groups']) . "\n";
        echo "Errors: " . count($r['errors']) . "\n";
        foreach ($r['errors'] as $e) {
            echo "  ERROR: $e\n";
        }
        echo "\nMatched products:\n";
        foreach ($r['matched_products'] as $pid => $prod) {
            echo "  [$pid] " . $prod->nama_produk . "\n";
        }
        echo "\nParsed rows:\n";
        foreach ($r['data'] as $d) {
            echo "  AWB={$d['awb']} | nama={$d['nama_produk']} | jumlah={$d['jumlah']} | product_id=" . ($d['product_id'] ?? 'null') . "\n";
        }
        echo "\nGroups:\n";
        foreach ($r['groups'] as $g) {
            echo "  {$g['tanggal']} {$g['kurir']} {$g['jenis']} - {$g['jumlah_resi']} resi\n";
            foreach ($g['products'] as $p) {
                echo "    product_id={$p['product_id']} jumlah={$p['jumlah']}\n";
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}
