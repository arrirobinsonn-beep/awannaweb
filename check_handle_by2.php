<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$f = file_get_contents('app/Http/Controllers/GudangController.php');
$lines = explode(PHP_EOL, $f);
echo "=== handle_by / order_online / no_telp in GudangController ===\n";
foreach ($lines as $i => $line) {
    if (strpos($line, 'handle_by') !== false || strpos($line, 'order_online_contact') !== false || strpos($line, 'no_telp') !== false) {
        echo ($i+1) . ': ' . trim($line) . PHP_EOL;
    }
}

echo "\n=== OrderOnlineContact table data (sample) ===\n";
$contacts = \App\Models\OrderOnlineContact::limit(5)->get();
foreach ($contacts as $c) {
    echo "  phone=" . var_export($c->phone, true) . " cs=" . var_export($c->cs_name, true) . " adv=" . $c->advertiser_id . "\n";
}