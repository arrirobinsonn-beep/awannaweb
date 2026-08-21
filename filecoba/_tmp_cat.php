<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\TransactionCategory::orderBy('type')->get(['id', 'name', 'type']) as $c) {
    echo $c->id.'|'.$c->name.'|'.$c->type."\n";
}