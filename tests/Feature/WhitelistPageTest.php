<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Whitelist;
use Tests\TestCase;

class WhitelistPageTest extends TestCase
{
    private function makeUser(string $role, string $prefix = 'User'): User
    {
        $user = User::create([
            'nama' => $prefix.' '.uniqid(),
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeWhitelist(User $owner): Whitelist
    {
        return Whitelist::create([
            'nama' => 'WL '.$owner->nama.' '.uniqid(),
            'kode' => 'WL-'.uniqid(),
            'platform' => 'facebook',
            'user_id' => $owner->id,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'aktif',
            'total_topup' => 0,
            'total_spending' => 0,
            'nominal_terakhir_topup' => 0,
        ]);
    }

    public function test_admin_sees_tabs_grouped_by_advertiser_with_semua_whitelist_leftmost(): void
    {
        $advA = $this->makeUser('advertiser', 'AdvA');
        $advB = $this->makeUser('advertiser', 'AdvB');
        $wlA1 = $this->makeWhitelist($advA);
        $wlA2 = $this->makeWhitelist($advA);
        $wlB1 = $this->makeWhitelist($advB);
        $wlB2 = $this->makeWhitelist($advB);
        $admin = $this->makeUser('super_admin', 'Admin');

        try {
            $resp = $this->actingAs($admin)
                ->get(route('whitelist.index'))
                ->assertOk();

            // Tab paling kiri = "Semua whitelist" (dengan badge jumlah total)
            $resp->assertSee('📋 Semua whitelist', false)
                ->assertSee('4', false); // badge allCount = 4 whitelist

            // Tab per advertiser pemilik muncul (grup by advertiser)
            $resp->assertSee($advA->display_name)
                ->assertSee($advB->display_name);

            // Tab "Semua whitelist" menampilkan SEMUA whitelist + kolom Pemilik
            $resp->assertSee($wlA1->nama)
                ->assertSee($wlA2->nama)
                ->assertSee($wlB1->nama)
                ->assertSee($wlB2->nama)
                ->assertSee('>Pemilik<', false) // header kolom Pemilik tampil
                ->assertSee($advA->email)       // sel Pemilik advA
                ->assertSee($advB->email);      // sel Pemilik advB
        } finally {
            Whitelist::whereIn('id', [$wlA1->id, $wlA2->id, $wlB1->id, $wlB2->id])->delete();
            User::whereIn('id', [$advA->id, $advB->id, $admin->id])->delete();
        }
    }

    public function test_advertiser_tab_filters_whitelists_by_owner(): void
    {
        $advA = $this->makeUser('advertiser', 'AdvA');
        $advB = $this->makeUser('advertiser', 'AdvB');
        $wlA = $this->makeWhitelist($advA);
        $wlB = $this->makeWhitelist($advB);
        $admin = $this->makeUser('super_admin', 'Admin');

        try {
            $resp = $this->actingAs($admin)
                ->get(route('whitelist.index', ['tab' => $advA->id]))
                ->assertOk();

            // Hanya whitelist milik advertiser yang dipilih yang tampil
            $resp->assertSee($wlA->nama)
                ->assertDontSee($wlB->nama);

            // Pemilik sudah jelas dari tab yang dipilih → kolom Pemilik disembunyikan
            $resp->assertDontSee('>Pemilik<', false)
                ->assertDontSee($advA->email)
                ->assertDontSee($advB->email);

            // Tab lain tetap ada (semua advertiser di daftar tab)
            $resp->assertSee($advA->display_name)
                ->assertSee($advB->display_name);
        } finally {
            Whitelist::whereIn('id', [$wlA->id, $wlB->id])->delete();
            User::whereIn('id', [$advA->id, $advB->id, $admin->id])->delete();
        }
    }

    public function test_advertiser_role_sees_only_own_whitelists_without_tabs(): void
    {
        $owner = $this->makeUser('advertiser', 'Owner');
        $other = $this->makeUser('advertiser', 'Other');
        $wlMine = $this->makeWhitelist($owner);
        $wlOther = $this->makeWhitelist($other);

        try {
            $resp = $this->actingAs($owner)
                ->get(route('whitelist.index'))
                ->assertOk();

            // Hanya whitelist miliknya
            $resp->assertSee($wlMine->nama)
                ->assertDontSee($wlOther->nama);

            // Tanpa tab per advertiser (halaman advertiser = tanpa "Semua whitelist")
            $resp->assertDontSee('📋 Semua whitelist', false);

            // Advertiser punya akses kelola: tombol tambah + aksi edit/hapus tampil
            $resp->assertSee('＋ Tambah Whitelist', false)
                ->assertSee('✏️');
        } finally {
            Whitelist::whereIn('id', [$wlMine->id, $wlOther->id])->delete();
            User::whereIn('id', [$owner->id, $other->id])->delete();
        }
    }

    public function test_admin_cannot_create_edit_or_delete_whitelist(): void
    {
        $adv = $this->makeUser('advertiser', 'Owner');
        $wl = $this->makeWhitelist($adv);
        $admin = $this->makeUser('super_admin', 'Admin');

        try {
            // Halaman index admin: tombol kelola TIDAK tampil, info pengunci tampil
            $this->actingAs($admin)
                ->get(route('whitelist.index'))
                ->assertOk()
                ->assertDontSee('＋ Tambah Whitelist', false)
                ->assertDontSee(route('whitelist.edit', $wl))
                ->assertDontSee(route('whitelist.destroy', $wl))
                ->assertDontSee('<th style="text-align:right;">Aksi</th>')
                ->assertDontSee('Hanya role advertiser yang dapat menambah', false);

            // Akses langsung ke create/edit/update/destroy → 403
            $this->actingAs($admin)->get(route('whitelist.create'))->assertForbidden();
            $this->actingAs($admin)->get(route('whitelist.edit', $wl))->assertForbidden();
            $this->actingAs($admin)->delete(route('whitelist.destroy', $wl))->assertForbidden();

            $this->actingAs($admin)->post(route('whitelist.store'), [
                'nama' => 'WL Ditolak '.uniqid(),
                'kode' => 'WL-'.uniqid(),
                'platform' => 'facebook',
                'tanggal' => now()->format('Y-m-d'),
                'status' => 'aktif',
            ])->assertForbidden();

            // Whitelist milik advertiser tidak terhapus oleh admin
            $this->assertDatabaseHas('whitelists', ['id' => $wl->id]);
        } finally {
            Whitelist::whereIn('id', [$wl->id])->delete();
            User::whereIn('id', [$adv->id, $admin->id])->delete();
        }
    }

    public function test_advertiser_can_still_manage_own_whitelist(): void
    {
        $adv = $this->makeUser('advertiser', 'Owner');
        $wl = $this->makeWhitelist($adv);

        try {
            $this->actingAs($adv)->get(route('whitelist.create'))->assertOk();
            $this->actingAs($adv)->get(route('whitelist.edit', $wl))->assertOk();

            $updated = $this->actingAs($adv)->put(route('whitelist.update', $wl), [
                'nama' => 'WL Updated '.uniqid(),
                'kode' => $wl->kode,
                'platform' => 'tiktok',
                'tanggal' => now()->format('Y-m-d'),
                'status' => 'aktif',
            ])->assertRedirect(route('whitelist.index'));

            $this->assertSame('tiktok', $wl->fresh()->platform);
        } finally {
            Whitelist::whereIn('id', [$wl->id])->delete();
            User::whereIn('id', [$adv->id])->delete();
        }
    }
}