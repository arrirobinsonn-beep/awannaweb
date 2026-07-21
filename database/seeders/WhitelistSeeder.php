<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Database\Seeder;

class WhitelistSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil advertiser yang sudah ada
        $adv1 = User::where('email', 'advertiser1@awanna.id')->first()?->id;
        $adv2 = User::where('email', 'advertiser2@awanna.id')->first()?->id;
        $adv3 = User::where('email', 'advertiser3@awanna.id')->first()?->id;

        if (! $adv1) {
            return;
        } // skip jika user belum ada

        $whitelists = [
            [
                'nama' => 'Awanna Beauty FB 01',
                'kode' => 'WL-FB-001',
                'platform' => 'facebook',
                'user_id' => $adv1,
                'tanggal' => '2025-01-15',
                'status' => 'aktif',
                'total_topup' => 15000000,
                'total_spending' => 12300000,
                'nominal_terakhir_topup' => 5000000,
                'catatan' => 'Akun utama campaign skincare',
            ],
            [
                'nama' => 'Awanna Beauty FB 02',
                'kode' => 'WL-FB-002',
                'platform' => 'facebook',
                'user_id' => $adv1,
                'tanggal' => '2025-02-01',
                'status' => 'aktif',
                'total_topup' => 8000000,
                'total_spending' => 6700000,
                'nominal_terakhir_topup' => 3000000,
                'catatan' => 'Backup akun skincare',
            ],
            [
                'nama' => 'Awanna Herbal TikTok 01',
                'kode' => 'WL-TT-001',
                'platform' => 'tiktok',
                'user_id' => $adv2,
                'tanggal' => '2025-03-10',
                'status' => 'aktif',
                'total_topup' => 20000000,
                'total_spending' => 17500000,
                'nominal_terakhir_topup' => 7000000,
                'catatan' => 'TikTok for Business suplemen',
            ],
            [
                'nama' => 'Awanna Tech Google 01',
                'kode' => 'WL-GG-001',
                'platform' => 'google',
                'user_id' => $adv2,
                'tanggal' => '2025-04-05',
                'status' => 'aktif',
                'total_topup' => 12000000,
                'total_spending' => 9800000,
                'nominal_terakhir_topup' => 4000000,
                'catatan' => 'Google Ads aksesoris',
            ],
            [
                'nama' => 'Awanna Fashion IG 01',
                'kode' => 'WL-IG-001',
                'platform' => 'instagram',
                'user_id' => $adv3,
                'tanggal' => '2025-05-20',
                'status' => 'aktif',
                'total_topup' => 5000000,
                'total_spending' => 3200000,
                'nominal_terakhir_topup' => 2000000,
                'catatan' => 'Instagram Ads fashion',
            ],
            [
                'nama' => 'Awanna Beauty TikTok 02',
                'kode' => 'WL-TT-002',
                'platform' => 'tiktok',
                'user_id' => $adv3,
                'tanggal' => '2025-06-01',
                'status' => 'nonaktif',
                'total_topup' => 3000000,
                'total_spending' => 3000000,
                'nominal_terakhir_topup' => 1000000,
                'catatan' => 'Saldo habis, belum di-topup',
            ],
        ];

        foreach ($whitelists as $wl) {
            Whitelist::firstOrCreate(['kode' => $wl['kode']], $wl);
        }
    }
}
