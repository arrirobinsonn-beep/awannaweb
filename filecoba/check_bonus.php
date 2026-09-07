<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = new \App\Services\BonusCalculationService();
$result = $svc->calculateRealtime('2026-09');
foreach ($result as $r) {
    echo $r->user->nama . ': spending=' . number_format($r->spending) . ' lead=' . $r->lead . ' paid=' . $r->paid . ' potensi=' . number_format($r->potensi_bonus) . PHP_EOL;
}
echo PHP_EOL . 'Total potensi bonus: Rp ' . number_format($result->sum('potensi_bonus'), 0, ',', '.') . PHP_EOL;
