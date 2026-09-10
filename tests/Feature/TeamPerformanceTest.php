<?php

namespace Tests\Feature;

use App\Models\RegionalCsStat;
use App\Models\User;
use Tests\TestCase;

class TeamPerformanceTest extends TestCase
{
    private function makeAdvertiser(): User
    {
        $user = User::create([
            'nama' => 'Advertiser '.uniqid(),
            'email' => 'adv-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
        $user->assignRole('advertiser');

        return $user;
    }

    private function makeCs(int $advertiserId): User
    {
        $cs = User::create([
            'nama' => 'CS Team '.uniqid(),
            'panggilan' => 'cs-team-'.uniqid(),
            'email' => 'cs-team-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
            'advertiser_id' => $advertiserId,
        ]);
        $cs->assignRole('cs');

        return $cs;
    }

    /**
     * Halaman performa team punya 2 tabel (tab Running/Testing): baris produk
     * testing yang dilewati di tabel regional TETAP tampil di sini, terpisah.
     */
    public function test_performance_page_shows_two_tables_running_and_testing(): void
    {
        $adv = $this->makeAdvertiser();
        $cs = $this->makeCs($adv->id);

        // Data CS stats: running 5/2, testing 3/1 di tanggal sama
        RegionalCsStat::create([
            'tanggal' => '2026-08-01',
            'user_id' => $adv->id,
            'cs_panggilan' => $cs->panggilan,
            'cs_user_id' => $cs->id,
            'lead' => 5,
            'paid' => 2,
            'product_status' => RegionalCsStat::STATUS_RUNNING,
        ]);
        RegionalCsStat::create([
            'tanggal' => '2026-08-01',
            'user_id' => $adv->id,
            'cs_panggilan' => $cs->panggilan,
            'cs_user_id' => $cs->id,
            'lead' => 3,
            'paid' => 1,
            'product_status' => RegionalCsStat::STATUS_TESTING,
        ]);

        try {
            $resp = $this->actingAs($adv)
                ->get(route('team.performance', ['dari' => '2026-08-01', 'sampai' => '2026-08-01']))
                ->assertOk();

            // Tab + konten 2 tabel
            $resp->assertSee('perftabcontent-running', false);
            $resp->assertSee('perftabcontent-testing', false);
            $resp->assertSee('switchPerfTab', false);

            // Kartu statistik: data-run (running) & data-test (testing)
            $resp->assertSee('data-run="5"', false);
            $resp->assertSee('data-test="3"', false);
            $resp->assertSee('data-run="2"', false);
            $resp->assertSee('data-test="1"', false);

            // Badge tab
            $resp->assertSee('🟢 Running', false);
            $resp->assertSee('🔬 Testing', false);

            // Dua doughnut per status (data-donut-group + id svg unik)
            $resp->assertSee('data-donut-group="running"', false);
            $resp->assertSee('data-donut-group="testing"', false);
            $resp->assertSee('cs-donut-running', false);
            $resp->assertSee('cs-donut-testing', false);
        } finally {
            RegionalCsStat::where('user_id', $adv->id)->delete();
            $cs->delete();
            $adv->delete();
        }
    }

    /**
     * Payload cs_stats TANPA product_status (client lama) → controller default ke
     * 'running' → tampil di tabel Running (backward-compatible sebelum fitur ini).
     */
    public function test_cs_stats_without_status_default_to_running(): void
    {
        $adv = $this->makeAdvertiser();
        $cs = $this->makeCs($adv->id);

        try {
            // Simpan seperti JS lama: cs_stats tanpa product_status
            $this->actingAs($adv)
                ->postJson(route('regional.save'), [
                    'items' => [['tanggal' => '2026-08-01', 'province' => 'JAWA BARAT', 'lead' => 4, 'paid' => 2]],
                    'cs_stats' => [[
                        'tanggal' => '2026-08-01',
                        'cs_panggilan' => $cs->panggilan,
                        'lead' => 4,
                        'paid' => 2,
                    ]],
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            $row = RegionalCsStat::where('user_id', $adv->id)
                ->where('cs_panggilan', $cs->panggilan)
                ->whereDate('tanggal', '2026-08-01')
                ->first();
            $this->assertNotNull($row, 'CS stats harus tersimpan');
            $this->assertSame('running', $row->product_status, 'Tanpa status → default running');

            // Tampil di tabel Running
            $resp = $this->actingAs($adv)
                ->get(route('team.performance', ['dari' => '2026-08-01', 'sampai' => '2026-08-01']))
                ->assertOk();

            $resp->assertSee('data-run="4"', false);
            $resp->assertSee('data-test="0"', false);
        } finally {
            RegionalCsStat::where('user_id', $adv->id)->delete();
            $cs->delete();
            $adv->delete();
        }
    }
}