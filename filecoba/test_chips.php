<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Auth as owner
$user = \App\Models\User::find(1);
auth()->login($user);

// Share view variables needed by layout
view()->share('errors', app('Illuminate\Support\ViewErrorBag'));

$controller = new \App\Http\Controllers\BonusAllocationController(
    app(\App\Services\BonusCalculationService::class)
);

$request = \Illuminate\Http\Request::create('/keuangan/alokasi-bonus?period=2026-09', 'GET');
$view = $controller->index($request);
$html = $view->render();

// Check chips
if (strpos($html, 'ba-chip') !== false) {
    echo "✓ Chips rendered" . PHP_EOL;
    preg_match_all('/ba-chip[^"]*"[^>]*data-target="([^"]*)"[^>]*>.*?<span class="ba-chip-name">(.*?)<\/span>.*?<span class="ba-chip-amount">(.*?)<\/span>/s', $html, $m, PREG_SET_ORDER);
    foreach ($m as $match) {
        echo "  " . $match[2] . " → " . $match[3] . " → #" . $match[1] . PHP_EOL;
    }
} else {
    echo "✗ Chips NOT found" . PHP_EOL;
}

// Check team IDs
if (preg_match_all('/id="team-(\d+)"/', $html, $ids)) {
    echo PHP_EOL . "✓ Team IDs: " . implode(', ', $ids[1]) . PHP_EOL;
}
