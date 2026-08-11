<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Database\Seeder;

class WhitelistSeeder extends Seeder
{
    /**
     * Data whitelist demo untuk para advertiser.
     *
     * Catatan penting:
     * - 'email' di sini hanya penanda kepemilikan → di-resolve ke user_id saat insert.
     * - Kunci idempotensi = 'kode' (unik), jadi seeder aman dijalankan berulang kali.
     * - Butuh UserSeeder dijalankan dulu (advertiser dengan profil lengkap).
     */
    public function run(): void
    {
        // Resolve pemilik berdasarkan email yang SAMA dengan UserSeeder
        $advertisers = User::role('advertiser')
            ->whereIn('email', [
                'rendi@awanna.id',
                'yanca@awanna.id',
                'parhan@awanna.id',
                'rama@awanna.id',
            ])
            ->get()
            ->keyBy('email');

        if ($advertisers->isEmpty()) {
            $this->command->warn('⚠️ Tidak ada user advertiser ditemukan. Jalankan UserSeeder terlebih dahulu.');

            return;
        }

        $whitelists = [
            // ─── Rendi — brand skincare ─────────────────────────────
            [
                'nama' => 'Awanna Skincare FB 01',
                'kode' => 'WL-FB-001',
                'platform' => 'facebook',
                'email' => 'rendi@awanna.id',
                'tanggal' => '2025-06-10',
                'status' => 'aktif',
                'total_topup' => 25000000,
                'total_spending' => 18500000,
                'nominal_terakhir_topup' => 5000000,
                'catatan' => 'Akun utama campaign skincare',
            ],
            [
                'nama' => 'Awanna Skincare FB 02',
                'kode' => 'WL-FB-002',
                'platform' => 'facebook',
                'email' => 'rendi@awanna.id',
                'tanggal' => '2025-07-02',
                'status' => 'aktif',
                'total_topup' => 10000000,
                'total_spending' => 8200000,
                'nominal_terakhir_topup' => 3000000,
                'catatan' => 'Backup akun, dipakai saat FB 01 kena review',
            ],
            [
                'nama' => 'Awanna Skincare TikTok 01',
                'kode' => 'WL-TT-001',
                'platform' => 'tiktok',
                'email' => 'rendi@awanna.id',
                'tanggal' => '2025-08-15',
                'status' => 'aktif',
                'total_topup' => 15000000,
                'total_spending' => 9750000,
                'nominal_terakhir_topup' => 5000000,
                'catatan' => 'TikTok Shop campaign, fokus konten video pendek',
            ],

            // ─── Yanca — brand fashion ──────────────────────────────
            [
                'nama' => 'Awanna Fashion IG 01',
                'kode' => 'WL-IG-001',
                'platform' => 'instagram',
                'email' => 'yanca@awanna.id',
                'tanggal' => '2025-05-20',
                'status' => 'aktif',
                'total_topup' => 8000000,
                'total_spending' => 6100000,
                'nominal_terakhir_topup' => 2000000,
                'catatan' => 'Instagram Ads untuk koleksi fashion',
            ],
            [
                'nama' => 'Awanna Fashion TikTok 01',
                'kode' => 'WL-TT-002',
                'platform' => 'tiktok',
                'email' => 'yanca@awanna.id',
                'tanggal' => '2025-06-01',
                'status' => 'nonaktif',
                'total_topup' => 5000000,
                'total_spending' => 5000000,
                'nominal_terakhir_topup' => 2000000,
                'catatan' => 'Saldo habis, belum di-topup',
            ],
            [
                'nama' => 'Awanna Fashion Google 01',
                'kode' => 'WL-GG-001',
                'platform' => 'google',
                'email' => 'yanca@awanna.id',
                'tanggal' => '2025-09-05',
                'status' => 'aktif',
                'total_topup' => 12000000,
                'total_spending' => 7400000,
                'nominal_terakhir_topup' => 4000000,
                'catatan' => 'Google Ads / Search campaign',
            ],

            // ─── Parhan — brand suplemen/herbal ─────────────────────
            [
                'nama' => 'WL60 - prhn',
                'kode' => '22760',
                'platform' => 'facebook',
                'email' => 'parhan@awanna.id',
                'tanggal' => '2025-04-12',
                'status' => 'aktif',
                'total_topup' => 30000000,
                'total_spending' => 26800000,
                'nominal_terakhir_topup' => 10000000,
                'catatan' => 'Akun utama campaign suplemen',
            ],
            [
                'nama' => 'WL59 - prhn',
                'kode' => '22759',
                'platform' => 'tiktok',
                'email' => 'parhan@awanna.id',
                'tanggal' => '2025-07-18',
                'status' => 'aktif',
                'total_topup' => 20000000,
                'total_spending' => 12300000,
                'nominal_terakhir_topup' => 5000000,
                'catatan' => 'TikTok for Business, target audiens muda',
            ],
            [
                'nama' => 'WL58 - prhn',
                'kode' => '22758',
                'platform' => 'youtube',
                'email' => 'parhan@awanna.id',
                'tanggal' => '2025-10-01',
                'status' => 'nonaktif',
                'total_topup' => 4000000,
                'total_spending' => 4000000,
                'nominal_terakhir_topup' => 1000000,
                'catatan' => 'Uji coba YouTube Ads — dihentikan sementara',
            ],

            // ─── Rama — brand gadget/aksesoris ──────────────────────
            [
                'nama' => 'WL43 - prhn',
                'kode' => '23643',
                'platform' => 'facebook',
                'email' => 'parhan``````````@awanna.id',
                'tanggal' => '2025-03-08',
                'status' => 'aktif',
                'total_topup' => 18000000,
                'total_spending' => 15900000,
                'nominal_terakhir_topup' => 6000000,
                'catatan' => 'Campaign aksesoris gadget',
            ],
            [
                'nama' => 'Awanna Gadget TikTok 01',
                'kode' => 'WL-TT-004',
                'platform' => 'tiktok',
                'email' => 'rama@awanna.id',
                'tanggal' => '2025-08-22',
                'status' => 'aktif',
                'total_topup' => 22000000,
                'total_spending' => 14500000,
                'nominal_terakhir_topup' => 8000000,
                'catatan' => 'Live shopping TikTok, konversi bagus',
            ],
            [
                'nama' => 'Awanna Gadget Google 01',
                'kode' => 'WL-GG-002',
                'platform' => 'google',
                'email' => 'rama@awanna.id',
                'tanggal' => '2025-11-11',
                'status' => 'aktif',
                'total_topup' => 6000000,
                'total_spending' => 2100000,
                'nominal_terakhir_topup' => 2000000,
                'catatan' => 'Google Ads baru, masih tahap learning',
            ],
        ];

        $created = 0;
        foreach ($whitelists as $wl) {
            $owner = $advertisers->get($wl['email']);

            if (! $owner) {
                continue; // advertiser tsb tidak ada → lewati baris ini
            }

            unset($wl['email']);
            $wl['user_id'] = $owner->id;

            $created += (int) Whitelist::firstOrCreate(['kode' => $wl['kode']], $wl)->wasRecentlyCreated;
        }

        $this->command->info("✅ WhitelistSeeder selesai: {$created} whitelist baru (total saat ini: ".Whitelist::count().').');
    }
}
