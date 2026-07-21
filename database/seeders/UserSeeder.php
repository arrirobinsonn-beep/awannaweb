<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // ─── Owner (level tertinggi, bisa buat akun baru) ──────────
            [
                'email' => 'owner@awanna.id',
                'password' => Hash::make('password'),
                'nama' => 'Pemilik Awanna',
                'panggilan' => 'Owner',
                'role' => 'Owner',
                'nohp' => '0811-0000-0000',
                'alamat' => 'Kantor Awanna, Jakarta',
                'bank' => 'BCA',
                'norek' => '1234567890',
                'is_active' => true,
                'is_profile_complete' => true,
                'spatie_role' => 'owner',
            ],

            // ─── Super Admin ────────────────────────────────────────────
            [
                'email' => 'superadmin@awanna.id',
                'password' => Hash::make('password'),
                'nama' => 'Super Administrator',
                'panggilan' => 'SuperAdmin',
                'role' => 'Admin',
                'nohp' => '0811-0000-0001',
                'alamat' => 'Jl. Contoh No. 1, Jakarta',
                'bank' => 'BRI',
                'norek' => '0987654321',
                'is_active' => true,
                'is_profile_complete' => true,
                'spatie_role' => 'super_admin',
            ],

            // ─── Admin ──────────────────────────────────────────────────
            [
                'email' => 'admin@awanna.id',
                'password' => Hash::make('password'),
                'nama' => 'Ahmad Fauzi',
                'panggilan' => 'Fauzi',
                'role' => 'Admin',
                'nohp' => '0811-0000-0002',
                'alamat' => 'Jl. Sudirman No. 5, Jakarta',
                'bank' => 'Mandiri',
                'norek' => '1122334455',
                'is_active' => true,
                'is_profile_complete' => true,
                'spatie_role' => 'admin',
            ],

            // ─── Advertiser (profil lengkap) ────────────────────────────
            [
                'email' => 'advertiser1@awanna.id',
                'password' => Hash::make('password'),
                'nama' => 'Rizky Pratama',
                'panggilan' => 'Rizky',
                'role' => 'Advertiser',
                'nohp' => '0812-1111-0001',
                'alamat' => 'Jl. Gatot Subroto No. 12, Jakarta',
                'bank' => 'BCA',
                'norek' => '5566778899',
                'is_active' => true,
                'is_profile_complete' => true,
                'spatie_role' => 'advertiser',
            ],
            [
                'email' => 'advertiser2@awanna.id',
                'password' => Hash::make('password'),
                'nama' => 'Nadia Aulia',
                'panggilan' => 'Nadia',
                'role' => 'Advertiser',
                'nohp' => '0812-1111-0002',
                'alamat' => 'Jl. Thamrin No. 8, Jakarta',
                'bank' => 'DANA',
                'norek' => '08121111002',
                'is_active' => true,
                'is_profile_complete' => true,
                'spatie_role' => 'advertiser',
            ],
            [
                'email' => 'advertiser3@awanna.id',
                'password' => Hash::make('password'),
                'nama' => 'Dian Permata',
                'panggilan' => 'Dian',
                'role' => 'Advertiser',
                'nohp' => '0812-1111-0003',
                'alamat' => 'Jl. Kuningan No. 3, Jakarta',
                'bank' => 'BNI',
                'norek' => '9988776655',
                'is_active' => true,
                'is_profile_complete' => true,
                'spatie_role' => 'advertiser',
            ],

            // ─── Mentor ─────────────────────────────────────────────────
            [
                'email' => 'mentor@awanna.id',
                'password' => Hash::make('password'),
                'nama' => 'Bowo Susanto',
                'panggilan' => 'Pak Bowo',
                'role' => 'Mentor',
                'nohp' => '0813-2222-0001',
                'alamat' => 'Jl. Senayan No. 20, Jakarta',
                'bank' => 'BSI',
                'norek' => '7766554433',
                'is_active' => true,
                'is_profile_complete' => true,
                'spatie_role' => 'mentor',
            ],

            // ─── Keuangan ────────────────────────────────────────────────
            [
                'email' => 'keuangan@awanna.id',
                'password' => Hash::make('password'),
                'nama' => 'Siska Rahayu',
                'panggilan' => 'Siska',
                'role' => 'Keuangan',
                'nohp' => '0814-3333-0001',
                'alamat' => 'Jl. Casablanca No. 15, Jakarta',
                'bank' => 'BCA',
                'norek' => '3344556677',
                'is_active' => true,
                'is_profile_complete' => true,
                'spatie_role' => 'keuangan',
            ],

            // ─── CS ──────────────────────────────────────────────────────
            [
                'email' => 'cs@awanna.id',
                'password' => Hash::make('password'),
                'nama' => 'Tono Wibowo',
                'panggilan' => 'Tono',
                'role' => 'CS',
                'nohp' => '0815-4444-0001',
                'alamat' => 'Jl. Mangga Dua No. 7, Jakarta',
                'bank' => 'OVO',
                'norek' => '08154444001',
                'is_active' => true,
                'is_profile_complete' => true,
                'spatie_role' => 'cs',
            ],

            // ─── Contoh user baru (profil BELUM lengkap) ─────────────────
            [
                'email' => 'newuser@awanna.id',
                'password' => Hash::make('password'),
                'nama' => null,
                'panggilan' => null,
                'role' => null,
                'nohp' => null,
                'alamat' => null,
                'bank' => null,
                'norek' => null,
                'is_active' => true,
                'is_profile_complete' => false,  // belum lengkap = akan dipaksa isi saat login
                'spatie_role' => 'advertiser',
            ],
        ];

        foreach ($users as $data) {
            $spatieRole = $data['spatie_role'];
            unset($data['spatie_role']);

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            $user->syncRoles([$spatieRole]);
        }
    }
}
