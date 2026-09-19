<?php

namespace Tests\Feature;

use App\Models\CsAssignment;
use App\Models\RegionalCsStat;
use App\Models\User;
use Tests\TestCase;

class TeamPerformanceDualPhaseTest extends TestCase
{
    private function makeUser(string $role, string $prefix = 'User'): User
    {
        $user = User::create([
            'nama' => $prefix.' '.uniqid(),
            'panggilan' => $prefix.substr(uniqid(), -4),
            'email' => 'teamdual-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Lead/paid Regional CS running & testing terpisah sempurna di halaman
     * Performa Team: tab Running berisi running saja, tab Testing berisi
     * testing saja (kartu, tabel, donut) — tidak campur lagi.
     */
    public function test_team_performance_splits_cs_stats_per_ad_phase(): void
    {
        $adv = $this->makeUser('advertiser', 'Adv');
        $cs = $this->makeUser('cs', 'CSutama');
        $tamu = $this->makeUser('cs', 'CStamu');

        CsAssignment::create([
            'cs_user_id' => $cs->id,
            'advertiser_id' => $adv->id,
            'bulan' => '2026-01',
            'created_by' => $adv->id,
        ]);

        RegionalCsStat::create([
            'tanggal' => '2026-01-15',
            'user_id' => $adv->id,
            'cs_panggilan' => $cs->panggilan,
            'cs_user_id' => $cs->id,
            'lead' => 7,
            'paid' => 3,
            'ad_phase' => 'running',
        ]);
        RegionalCsStat::create([
            'tanggal' => '2026-01-15',
            'user_id' => $adv->id,
            'cs_panggilan' => $cs->panggilan,
            'cs_user_id' => $cs->id,
            'lead' => 5,
            'paid' => 2,
            'ad_phase' => 'testing',
        ]);
        RegionalCsStat::create([
            'tanggal' => '2026-01-15',
            'user_id' => $adv->id,
            'cs_panggilan' => $tamu->panggilan,
            'cs_user_id' => $tamu->id,
            'lead' => 4,
            'paid' => 1,
            'ad_phase' => 'testing',
        ]);

        try {
            $resp = $this->actingAs($adv)
                ->get(route('team.performance', ['dari' => '2026-01-01', 'sampai' => '2026-01-31']))
                ->assertOk();

            $html = $resp->getContent();
            $has = fn (string $needle) => str_contains($html, $needle);

            // ── Kartu: run 7/3 (CS utama) vs testing 9/3 (utama 5/2 + tamu 4/1) ──
            $this->assertTrue(
                $has('data-run="7"') && $has('data-test="9"'),
                'Kartu lead: run7='.var_export($has('data-run="7"'), true)
                .' test9='.var_export($has('data-test="9"'), true)
            );
            $this->assertTrue(
                $has('data-run="3"') && $has('data-test="3"'),
                'Kartu paid: run3='.var_export($has('data-run="3"'), true)
                .' test3='.var_export($has('data-test="3"'), true)
            );

            // ── Tab & panel testing ada ──
            $this->assertTrue($has('id="teamtabcontent-testing"'), 'Panel testing tidak dirender');

            // ── Tabel testing memuat CS utama (total 5) & tamu (total 4) — testing saja ──
            // (nilai sel diapit newline di template → cocokkan dengan regex)
            $this->assertTrue($has('🔬 Performa Semua CS — Produk Testing'), 'Header tabel testing tidak ada');
            $this->assertTrue(
                (bool) preg_match('/cs-total-lead[^>]*>\s*5\s*<\/td>/', $html),
                'Total lead CS utama (5) tidak tampil di tabel testing'
            );
            $this->assertTrue(
                (bool) preg_match('/cs-total-lead[^>]*>\s*4\s*<\/td>/', $html),
                'Total lead CS tamu (4) tidak tampil di tabel testing'
            );

            // ── Donut testing dirender dgn data testing (5+4=9) ──
            $this->assertTrue($has('cs-donut-testing'), 'Donut testing tidak dirender');
            $this->assertTrue($has('"9'), 'leadSum donut testing (9) tidak ada');
        } finally {
            RegionalCsStat::where('user_id', $adv->id)->delete();
            CsAssignment::where('advertiser_id', $adv->id)->delete();
            User::whereIn('id', [$adv->id, $cs->id, $tamu->id])->delete();
        }
    }
}
