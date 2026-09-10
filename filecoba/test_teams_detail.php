<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(1);
auth()->login($user);
view()->share('errors', app('Illuminate\Support\ViewErrorBag'));

$controller = new \App\Http\Controllers\BonusAllocationController(
    app(\App\Services\BonusCalculationService::class)
);

$request = \Illuminate\Http\Request::create('/keuangan/alokasi-bonus?period=2026-09', 'GET');
$view = $controller->index($request);
$teams = $view->getData()['teams'];

foreach ($teams as $t) {
    $advName = strtoupper($t->advertiser->panggilan ?: $t->advertiser->nama);
    echo "=== TIM {$advName} === Potensi Bonus: Rp " . number_format($t->potensi_bonus, 0, ',', '.') . PHP_EOL;
    echo str_pad('NO', 4) . str_pad('BAGIAN', 12) . str_pad('NAMA', 22) . str_pad('KET', 14) . str_pad('PAID', 8) . str_pad('PAYMENT', 16) . PHP_EOL;
    echo str_repeat('-', 76) . PHP_EOL;
    $no = 1;
    foreach ($t->members as $m) {
        echo str_pad($no++, 4)
            . str_pad(strtoupper($m->role), 12)
            . str_pad(strtoupper($m->name), 22)
            . str_pad($m->keterangan, 14)
            . str_pad($m->paid, 8)
            . 'Rp ' . number_format($m->payment, 0, ',', '.') . PHP_EOL;
    }
    echo str_repeat('-', 76) . PHP_EOL;
    echo '     TOTAL PAYMENT: Rp ' . number_format($t->total_payment, 0, ',', '.') . PHP_EOL . PHP_EOL;
}
