<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DashboardKeuanganTest extends TestCase
{
    public function test_keuangan_dashboard_renders(): void
    {
        $keu = User::role('keuangan')->first()
            ?? tap(User::create([
                'nama' => 'Keuangan '.uniqid(),
                'email' => 'keu-'.uniqid().'@example.com',
                'password' => bcrypt('secret'),
                'is_profile_complete' => true,
                'is_active' => true,
            ]), fn ($u) => $u->assignRole('keuangan'));

        $this->actingAs($keu)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Top Advertiser')
            ->assertSee('Akun Keuangan')
            ->assertSee('Kategori Transaksi')
            ->assertSee('Transfer Antar Akun')
            ->assertSee('Bukti Transfer');
    }
}