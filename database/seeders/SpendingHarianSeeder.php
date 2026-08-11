<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\SpendingHarian;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Database\Seeder;

class SpendingHarianSeeder extends Seeder
{
    public function run(): void
    {
        $advertisers = User::role('advertiser')->get();
        $products = Product::aktif()->get();

        if ($advertisers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $records = [];

        foreach ($advertisers as $advertiser) {
            // Ambil whitelist milik advertiser ini
            $myWhitelists = Whitelist::where('user_id', $advertiser->id)->get();
            if ($myWhitelists->isEmpty()) {
                continue;
            }

            // Generate data 30 hari terakhir, 1-3 baris per hari (beda produk)
            for ($i = 29; $i >= 0; $i--) {
                $tanggal = now()->subDays($i)->format('Y-m-d');
                $jumlah = rand(1, 3);

                for ($j = 0; $j < $jumlah; $j++) {
                    $whitelist = $myWhitelists->random();
                    $product = $products->random();
                    $spending = rand(50, 500) * 1000;
                    $lead = rand(5, 80);
                    $paid = (int) ($lead * (rand(10, 60) / 100));

                    $data = [
                        'tanggal' => $tanggal,
                        'user_id' => $advertiser->id,
                        'whitelist_id' => $whitelist->id,
                        'product_id' => $product->id,
                        'spending' => $spending,
                        'lead' => $lead,
                        'paid' => $paid,
                        'catatan' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Hitung metrik
                    SpendingHarian::computeMetrics($data);

                    $records[] = $data;
                }
            }
        }

        foreach (array_chunk($records, 100) as $chunk) {
            SpendingHarian::insert($chunk);
        }

        // Sinkronkan total_spending tiap whitelist dengan data yang baru di-generate
        // (pola batch: 1 query aggregate → map → update, sesuai AGENTS.md)
        $affectedWhitelistIds = array_unique(array_column($records, 'whitelist_id'));
        if (! empty($affectedWhitelistIds)) {
            $totals = SpendingHarian::whereIn('whitelist_id', $affectedWhitelistIds)
                ->selectRaw('whitelist_id, SUM(spending) as total')
                ->groupBy('whitelist_id')
                ->get()
                ->keyBy('whitelist_id');

            foreach ($totals as $wlId => $t) {
                Whitelist::whereKey($wlId)->update(['total_spending' => $t->total]);
            }
        }
    }
}
