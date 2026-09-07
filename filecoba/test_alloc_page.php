<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate a request to the bonus allocation page
$request = \Illuminate\Http\Request::create('/keuangan/alokasi-bonus?period=2026-09', 'GET');
$request->setLaravelSession($app['session.store']);

// Auth as owner
$user = \App\Models\User::find(1);
auth()->login($user);
view()->share('errors', app('Illuminate\Support\ViewErrorBag'));

$controller = new \App\Http\Controllers\BonusAllocationController(
    app(\App\Services\BonusCalculationService::class)
);

$view = $controller->index($request);
echo "View name: " . $view->getName() . PHP_EOL;
echo "View data keys: " . implode(', ', array_keys($view->getData())) . PHP_EOL;
echo PHP_EOL;

// Check recap data
$recap = $view->getData()['recap'] ?? null;
if ($recap) {
    echo "REKAP PENGELUARAN GAJI & BONUS:" . PHP_EOL;
    echo str_repeat('-', 50) . PHP_EOL;
    $i = 1;
    $total = 0;
    foreach ($recap as $r) {
        echo sprintf("%2d. %-20s Rp %s", $i++, strtoupper($r->name), number_format($r->total_payment, 0, ',', '.')) . PHP_EOL;
        $total += $r->total_payment;
    }
    echo str_repeat('-', 50) . PHP_EOL;
    echo sprintf("    %-20s Rp %s", 'TOTAL TIM', number_format($total, 0, ',', '.')) . PHP_EOL;
} else {
    echo "ERROR: recap data not found!" . PHP_EOL;
}
