<?php

namespace Database\Seeders;

use App\Models\BonusAllocationSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class BonusAllocationSettingSeeder extends Seeder
{
    public function run(): void
    {
        // Global: keuangan & admin (advertiser_id = null)
        BonusAllocationSetting::updateOrCreate(
            ['advertiser_id' => null, 'role' => 'keuangan'],
            ['percentage' => 9]
        );
        BonusAllocationSetting::updateOrCreate(
            ['advertiser_id' => null, 'role' => 'admin'],
            ['percentage' => 7]
        );

        // Per advertiser: default 36% advertiser, 48% CS
        $advertisers = User::where('role', 'advertiser')->where('is_active', true)->get();
        foreach ($advertisers as $adv) {
            BonusAllocationSetting::updateOrCreate(
                ['advertiser_id' => $adv->id, 'role' => 'advertiser'],
                ['percentage' => 36]
            );
            BonusAllocationSetting::updateOrCreate(
                ['advertiser_id' => $adv->id, 'role' => 'cs'],
                ['percentage' => 48]
            );
        }
    }
}
