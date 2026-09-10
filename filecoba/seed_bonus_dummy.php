<?php
/**
 * Seed dummy data for Bonus Allocation page testing.
 * Run: php filecoba/seed_bonus_dummy.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BonusAllocationSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

$period = '2026-09';
$start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
$end = $start->copy()->endOfMonth();

echo "=== SEEDING BONUS ALLOCATION DUMMY DATA ===\n";

// 1. CS assignments — map CS to advertisers
echo "\n[1] CS Assignments...\n";
DB::table('cs_assignments')->where('bulan', $period)->delete();

$assignments = [
    // Tim RENDI (id=4)
    ['cs_user_id' => 11, 'advertiser_id' => 4, 'bulan' => $period], // asep pace → rendi (utama)
    ['cs_user_id' => 12, 'advertiser_id' => 4, 'bulan' => $period], // feri → rendi (utama)
    // Tim YANCA (id=5)
    ['cs_user_id' => 13, 'advertiser_id' => 5, 'bulan' => $period], // mayang → yanca (utama)
    // Tim PARHAN (id=6)
    ['cs_user_id' => 10, 'advertiser_id' => 6, 'bulan' => $period], // opus → parhan (utama)
    // Tim RAMA (id=7)
    ['cs_user_id' => 14, 'advertiser_id' => 7, 'bulan' => $period], // putri → rama (utama)
    ['cs_user_id' => 15, 'advertiser_id' => 7, 'bulan' => $period], // muklas → rama (utama)
];
DB::table('cs_assignments')->insert($assignments);
echo "  Inserted " . count($assignments) . " assignments\n";

// 2. Spending harian — 30 days of data per advertiser
echo "\n[2] Spending Harian...\n";
DB::table('spending_harians')->whereBetween('tanggal', [$start, $end])->delete();

$spendingData = [
    // RENDI — high performer (paid ~600/bulan)
    4 => ['spending_day' => 500000, 'lead_day' => 30, 'paid_day' => 22],
    // YANCA — good performer (paid ~550/bulan)
    5 => ['spending_day' => 420000, 'lead_day' => 25, 'paid_day' => 19],
    // PARHAN — moderate (paid ~520/bulan)
    6 => ['spending_day' => 350000, 'lead_day' => 22, 'paid_day' => 18],
    // RAMA — low performer (paid ~30/bulan → below threshold)
    7 => ['spending_day' => 200000, 'lead_day' => 12, 'paid_day' => 1],
];

$rows = [];
foreach ($spendingData as $advId => $cfg) {
    for ($d = 1; $d <= 30; $d++) {
        $date = $start->copy()->addDays($d - 1)->format('Y-m-d');
        // Add some variance
        $variance = 1 + (mt_rand(-20, 20) / 100);
        $rows[] = [
            'user_id' => $advId,
            'tanggal' => $date,
            'whitelist_id' => 1,
            'product_id' => 1,
            'spending' => round($cfg['spending_day'] * $variance),
            'lead' => max(1, round($cfg['lead_day'] * $variance)),
            'paid' => max(0, round($cfg['paid_day'] * $variance)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
DB::table('spending_harians')->insert($rows);
echo "  Inserted " . count($rows) . " spending rows\n";

// 3. Regional CS stats — paid data per CS per day
echo "\n[3] Regional CS Stats...\n";
DB::table('regional_cs_stats')->whereBetween('tanggal', [$start, $end])->delete();

$csStats = [
    // CS Rendi team (utama)
    11 => ['user_id' => 4, 'panggilan' => 'ASEP PACE CS', 'paid_day' => 12],
    12 => ['user_id' => 4, 'panggilan' => 'FERI CS', 'paid_day' => 10],
    // CS Yanca team (utama)
    13 => ['user_id' => 5, 'panggilan' => 'MAYANG CS', 'paid_day' => 10],
    // CS Parhan team (utama)
    10 => ['user_id' => 6, 'panggilan' => 'OPUS CS', 'paid_day' => 10],
    // CS Rama team (utama)
    14 => ['user_id' => 7, 'panggilan' => 'PUTRI CS', 'paid_day' => 8],
    15 => ['user_id' => 7, 'panggilan' => 'MUKLAS CS', 'paid_day' => 8],
];

// CS PENGGAANTI: CS yang bantu tim lain (data di bawah user_id tim yang dibantu)
$guestCsStats = [
    // Opus CS (utama Parhan) bantu Rendi sebagai pengganti
    ['user_id' => 4, 'panggilan' => 'OPUS CS', 'paid_day' => 5],
    // Asep Pace CS (utama Rendi) bantu Yanca sebagai pengganti
    ['user_id' => 5, 'panggilan' => 'ASEP PACE CS', 'paid_day' => 4],
    // Mayang CS (utama Yanca) bantu Parhan sebagai pengganti
    ['user_id' => 6, 'panggilan' => 'MAYANG CS', 'paid_day' => 3],
];

$csRows = [];
foreach ($csStats as $csId => $cfg) {
    for ($d = 1; $d <= 30; $d++) {
        $date = $start->copy()->addDays($d - 1)->format('Y-m-d');
        $variance = 1 + (mt_rand(-15, 15) / 100);
        $csRows[] = [
            'user_id' => $cfg['user_id'],
            'cs_panggilan' => $cfg['panggilan'],
            'tanggal' => $date,
            'lead' => max(0, round(3 * $variance)),
            'paid' => max(0, round($cfg['paid_day'] * $variance)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
// Tambah data CS pengganti
foreach ($guestCsStats as $cfg) {
    for ($d = 1; $d <= 30; $d++) {
        $date = $start->copy()->addDays($d - 1)->format('Y-m-d');
        $variance = 1 + (mt_rand(-15, 15) / 100);
        $csRows[] = [
            'user_id' => $cfg['user_id'],
            'cs_panggilan' => $cfg['panggilan'],
            'tanggal' => $date,
            'lead' => max(0, round(2 * $variance)),
            'paid' => max(0, round($cfg['paid_day'] * $variance)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
DB::table('regional_cs_stats')->insert($csRows);
echo "  Inserted " . count($csRows) . " CS stat rows\n";

// 4. Ensure bonus allocation settings exist
echo "\n[4] Bonus Allocation Settings...\n";
$settings = [
    ['advertiser_id' => null, 'role' => 'keuangan', 'percentage' => 9],
    ['advertiser_id' => null, 'role' => 'admin', 'percentage' => 7],
];
foreach ($settings as $s) {
    BonusAllocationSetting::updateOrCreate(
        ['advertiser_id' => null, 'role' => $s['role']],
        ['percentage' => $s['percentage']]
    );
}
foreach ([4, 5, 6, 7] as $advId) {
    BonusAllocationSetting::updateOrCreate(
        ['advertiser_id' => $advId, 'role' => 'advertiser'],
        ['percentage' => 36]
    );
    BonusAllocationSetting::updateOrCreate(
        ['advertiser_id' => $advId, 'role' => 'cs'],
        ['percentage' => 48]
    );
}
echo "  Settings ready\n";

// 5. Quick verification
echo "\n=== VERIFICATION ===\n";
$spending = DB::table('spending_harians')->whereBetween('tanggal', [$start, $end])->count();
$csStatsCount = DB::table('regional_cs_stats')->whereBetween('tanggal', [$start, $end])->count();
$assignmentsCount = DB::table('cs_assignments')->where('bulan', $period)->count();
$settingsCount = DB::table('bonus_allocation_settings')->count();

echo "Spending rows: $spending\n";
echo "CS stat rows: $csStatsCount\n";
echo "CS assignments: $assignmentsCount\n";
echo "Allocation settings: $settingsCount\n";

echo "\n=== DONE ===\n";
echo "Visit: /keuangan/alokasi-bonus?period=$period\n";
