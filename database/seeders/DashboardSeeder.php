<?php

namespace Database\Seeders;

use App\Models\Dashboard;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $dashboards = ['SPX', 'FLIK', 'SICEPAT', 'PEACHTREE'];

        foreach ($dashboards as $name) {
            Dashboard::firstOrCreate(['name' => $name]);
        }
    }
}
