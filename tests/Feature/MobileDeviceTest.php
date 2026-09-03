<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankTransfer;
use App\Models\MobileDevice;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileDeviceTest extends TestCase
{
    private array $plainTokens = [];

    private function makeUser(string $role): User
    {
        $user = User::create([
            'nama' => 'User '.uniqid(),
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeAccount(float $balance = 0): Account
    {
        return Account::create([
            'name' => 'Akun '.uniqid(),
            'type' => 'bank',
            'current_balance' => $balance,
            'status' => 'active',
        ]);
    }

    private function makeDevice(Account $account): MobileDevice
    {
        $plainToken = 'ak_live_'.Str::random(32);

        $device = MobileDevice::create([
            'account_id' => $account->id,
            'name'       => 'Device '.uniqid(),
            'token_hash' => hash('sha256', $plainToken),
            'status'     => 'active',
        ]);

        // Store plain token in test property (NOT on model to avoid DB leaks)
        $this->plainTokens[$device->id] = $plainToken;

        return $device;
    }

    private function getToken(MobileDevice $device): string
    {
        return $this->plainTokens[$device->id] ?? '';
    }

    private function makeCategory(string $type = 'in'): TransactionCategory
    {
        return TransactionCategory::create([
            'name' => 'Kategori '.uniqid(),
            'type' => $type,
        ]);
    }

    private function makeTransaction(Account $account, string $status = 'pending'): BankTransfer
    {
        return BankTransfer::create([
            'account_id'       => $account->id,
            'category_id'      => $this->makeCategory('in')->id,
            'type'             => 'in',
            'amount'           => 50000,
            'transaction_date' => now(),
            'status'           => $status,
            'created_by'       => null,
        ]);
    }

    // ─── Authentication ──────────────────────────────────────────

    public function test_token_valid_returns_transactions(): void
    {
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);
        $this->makeTransaction($account);

        $response = $this->getJson('/api/mobile/transactions', [
            'Authorization' => 'Bearer '.$this->getToken($device),
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'datas');
    }

    public function test_token_invalid_returns_401(): void
    {
        $response = $this->getJson('/api/mobile/transactions', [
            'Authorization' => 'Bearer invalid_token_xxxxx',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Token tidak valid atau device sudah dicabut.']);
    }

    public function test_no_token_returns_401(): void
    {
        $response = $this->getJson('/api/mobile/transactions');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Token tidak ditemukan.']);
    }

    public function test_revoked_device_returns_401(): void
    {
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);
        $device->update(['status' => 'revoked']);

        $response = $this->getJson('/api/mobile/transactions', [
            'Authorization' => 'Bearer '.$this->getToken($device),
        ]);

        $response->assertStatus(401);
    }

    // ─── Account Isolation ────────────────────────────────────────

    public function test_device_can_only_see_own_account_transactions(): void
    {
        $accountA = $this->makeAccount();
        $accountB = $this->makeAccount();
        $deviceA = $this->makeDevice($accountA);

        $txA = $this->makeTransaction($accountA);
        $txB = $this->makeTransaction($accountB);

        $response = $this->getJson('/api/mobile/transactions', [
            'Authorization' => 'Bearer '.$this->getToken($deviceA),
        ]);

        $response->assertOk();
        $ids = collect($response->json('datas'))->pluck('id')->toArray();
        $this->assertContains($txA->id, $ids);
        $this->assertNotContains($txB->id, $ids);
    }

    public function test_device_cannot_access_other_account_transaction_detail(): void
    {
        $accountA = $this->makeAccount();
        $accountB = $this->makeAccount();
        $deviceA = $this->makeDevice($accountA);
        $txB = $this->makeTransaction($accountB);

        $response = $this->getJson("/api/mobile/transactions/{$txB->id}", [
            'Authorization' => 'Bearer '.$this->getToken($deviceA),
        ]);

        $response->assertStatus(404);
    }

    public function test_device_can_access_own_transaction_detail(): void
    {
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);
        $tx = $this->makeTransaction($account);

        $response = $this->getJson("/api/mobile/transactions/{$tx->id}", [
            'Authorization' => 'Bearer '.$this->getToken($device),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $tx->id);
    }

    public function test_device_cannot_confirm_other_account_transaction(): void
    {
        $accountA = $this->makeAccount();
        $accountB = $this->makeAccount();
        $deviceA = $this->makeDevice($accountA);
        $txB = $this->makeTransaction($accountB);

        $response = $this->postJson("/api/mobile/transactions/{$txB->id}/confirm", [], [
            'Authorization' => 'Bearer '.$this->getToken($deviceA),
        ]);

        $response->assertStatus(404);
    }

    public function test_device_can_confirm_own_pending_transaction(): void
    {
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);
        $tx = $this->makeTransaction($account, 'pending');

        $response = $this->postJson("/api/mobile/transactions/{$tx->id}/confirm", [], [
            'Authorization' => 'Bearer '.$this->getToken($device),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('bank_transfers', [
            'id'     => $tx->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_device_cannot_confirm_already_confirmed_transaction(): void
    {
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);
        $tx = $this->makeTransaction($account, 'confirmed');

        $response = $this->postJson("/api/mobile/transactions/{$tx->id}/confirm", [], [
            'Authorization' => 'Bearer '.$this->getToken($device),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('current_status', 'confirmed');
    }

    // ─── Token Security ──────────────────────────────────────────

    public function test_plain_token_not_stored_in_database(): void
    {
        $account = $this->makeAccount();
        $plainToken = 'ak_live_'.Str::random(32);

        $device = MobileDevice::create([
            'account_id' => $account->id,
            'name'       => 'Device Security',
            'token_hash' => hash('sha256', $plainToken),
            'status'     => 'active',
        ]);

        $dbDevice = MobileDevice::find($device->id);
        // Token hash adalah hex string 64 karakter (sha256), bukan plain text
        $this->assertNotEquals($plainToken, $dbDevice->token_hash);
        $this->assertEquals(64, strlen($dbDevice->token_hash));
        // Verify hash matches
        $this->assertEquals(hash('sha256', $plainToken), $dbDevice->token_hash);
    }

    public function test_token_hash_in_hidden_attribute(): void
    {
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);

        // Token hash should not appear in JSON serialization
        $json = $device->toJson();
        $this->assertArrayNotHasKey('token_hash', json_decode($json, true));
    }

    // ─── Web CRUD ────────────────────────────────────────────────

    public function test_mobile_device_page_renders(): void
    {
        $user = $this->makeUser('owner');

        $this->actingAs($user)
            ->get(route('mobile-device.index'))
            ->assertOk()
            ->assertSee('Mobile Devices');
    }

    public function test_mobile_device_store_creates_device(): void
    {
        $user = $this->makeUser('owner');
        $account = $this->makeAccount();

        $this->actingAs($user)
            ->post(route('mobile-device.store'), [
                'account_id' => $account->id,
                'name'       => 'HP Test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mobile_devices', [
            'account_id' => $account->id,
            'name'       => 'HP Test',
            'status'     => 'active',
        ]);

        // Verify token_hash exists and is not plain text
        $device = MobileDevice::where('account_id', $account->id)->first();
        $this->assertNotNull($device->token_hash);
        $this->assertNotEquals('HP Test', $device->token_hash);
    }

    public function test_mobile_device_update(): void
    {
        $user = $this->makeUser('owner');
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);

        $this->actingAs($user)
            ->put(route('mobile-device.update', $device), [
                'name' => 'Device Updated',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mobile_devices', [
            'id'   => $device->id,
            'name' => 'Device Updated',
        ]);
    }

    public function test_mobile_device_toggle(): void
    {
        $user = $this->makeUser('owner');
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);

        $this->actingAs($user)
            ->patch(route('mobile-device.toggle', $device))
            ->assertRedirect();

        $this->assertDatabaseHas('mobile_devices', [
            'id'     => $device->id,
            'status' => 'revoked',
        ]);
    }

    public function test_mobile_device_delete(): void
    {
        $user = $this->makeUser('owner');
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);

        $this->actingAs($user)
            ->delete(route('mobile-device.destroy', $device))
            ->assertRedirect();

        $this->assertDatabaseMissing('mobile_devices', ['id' => $device->id]);
    }

    public function test_mobile_device_regenerate_token(): void
    {
        $user = $this->makeUser('owner');
        $account = $this->makeAccount();
        $device = $this->makeDevice($account);
        $oldHash = $device->token_hash;

        $this->actingAs($user)
            ->post(route('mobile-device.regenerate', $device))
            ->assertRedirect();

        $device->refresh();
        $this->assertNotEquals($oldHash, $device->token_hash);
    }

    public function test_unauthorized_user_cannot_access_mobile_devices(): void
    {
        $user = $this->makeUser('cs');

        $this->actingAs($user)
            ->get(route('mobile-device.index'))
            ->assertStatus(403);
    }
}
