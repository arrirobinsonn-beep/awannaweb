<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankTransfer;
use App\Models\TopUpPaymentBatch;
use App\Models\TopUpProposal;
use App\Models\TopUpProposalItem;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopUpApprovalTest extends TestCase
{
    private function makeUser(string $role, string $name = null): User
    {
        $user = User::create([
            'nama' => $name ?: ($role.' '.uniqid()),
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeAdvertiserWithWhitelist(): array
    {
        $adv = $this->makeUser('advertiser', 'Adv '.uniqid());
        $wl = Whitelist::create([
            'nama' => 'WL '.uniqid(),
            'kode' => 'WL-'.uniqid(),
            'platform' => 'facebook',
            'user_id' => $adv->id,
            'tanggal' => now()->toDateString(),
            'status' => 'aktif',
            'total_topup' => 0,
            'total_spending' => 0,
            'nominal_terakhir_topup' => 0,
        ]);

        return [$adv, $wl];
    }

    private function makeProposal(User $adv, Whitelist $wl, float $amount = 500000): TopUpProposal
    {
        $proposal = TopUpProposal::create([
            'user_id' => $adv->id,
            'status' => 'pending',
            'previous_topup_total' => 0,
            'today_lead' => 10,
            'today_paid' => 5,
            'today_spending' => 200000,
            'total_nominal' => $amount,
        ]);

        TopUpProposalItem::create([
            'proposal_id' => $proposal->id,
            'whitelist_id' => $wl->id,
            'nominal' => $amount,
            'payment_status' => 'pending',
        ]);

        return $proposal;
    }

    private function makeAccount(): Account
    {
        return Account::create([
            'name' => 'Test Account '.uniqid(),
            'type' => 'bank',
            'current_balance' => 10000000,
            'status' => 'active',
        ]);
    }

    public function test_approve_creates_batches_and_review_history(): void
    {
        [$adv, $wl] = $this->makeAdvertiserWithWhitelist();
        $approver = $this->makeUser('keuangan', 'Finance '.uniqid());
        $proposal = $this->makeProposal($adv, $wl, 750000);
        $account = $this->makeAccount();

        $this->actingAs($approver)
            ->patch(route('topup.approve', $proposal), [
                'payment_mode' => 'shared_va',
                'source_account_id' => $account->id,
            ])
            ->assertRedirect(route('topup.show', $proposal));

        $proposal = $proposal->fresh();

        $this->assertSame('approved', $proposal->status);
        $this->assertSame('shared_va', $proposal->payment_mode);
        $this->assertDatabaseHas('top_up_proposal_reviews', [
            'proposal_id' => $proposal->id,
            'reviewer_id' => $approver->id,
            'decision' => 'approved',
        ]);
        $this->assertDatabaseHas('top_up_payment_batches', [
            'proposal_id' => $proposal->id,
            'batch_no' => 1,
            'payment_mode' => 'shared_va',
            'nominal' => 750000,
        ]);
        $this->assertCount(1, $proposal->paymentBatches);
        $this->assertSame($proposal->items()->first()->payment_batch_id, $proposal->paymentBatches()->first()->id);
    }

    public function test_decline_requests_revision_and_stores_suggestion(): void
    {
        [$adv, $wl] = $this->makeAdvertiserWithWhitelist();
        $approver = $this->makeUser('keuangan', 'Finance '.uniqid());
        $proposal = $this->makeProposal($adv, $wl, 13000000);

        $this->actingAs($approver)
            ->patch(route('topup.decline', $proposal), [
                'decline_note' => 'Kurangi nominal dan fokus whitelist prioritas.',
                'suggested_total_nominal' => 8000000,
            ])
            ->assertRedirect(route('topup.show', $proposal));

        $proposal = $proposal->fresh();

        $this->assertSame('revision_requested', $proposal->status);
        $this->assertSame('Kurangi nominal dan fokus whitelist prioritas.', $proposal->decline_note);
        $this->assertSame('8000000.00', (string) $proposal->suggested_total_nominal);
        $this->assertDatabaseHas('top_up_proposal_reviews', [
            'proposal_id' => $proposal->id,
            'reviewer_id' => $approver->id,
            'decision' => 'revision_requested',
        ]);
    }

    public function test_payment_store_marks_batch_paid_and_maintains_totals(): void
    {
        [$adv, $wl] = $this->makeAdvertiserWithWhitelist();
        $approver = $this->makeUser('keuangan', 'Finance '.uniqid());
        $proposal = $this->makeProposal($adv, $wl, 600000);
        $account = $this->makeAccount();

        $this->actingAs($approver)->patch(route('topup.approve', $proposal), [
            'payment_mode' => 'shared_va',
            'source_account_id' => $account->id,
        ]);

        $proposal = $proposal->fresh(['paymentBatches', 'items']);
        $batch = $proposal->paymentBatches->first();

        $this->actingAs($adv)
            ->post(route('topup.payment.store', $proposal), [
                'batches' => [
                    ['batch_id' => $batch->id, 'va_number' => 'VA-123456'],
                ],
            ])
            ->assertRedirect(route('topup.show', $proposal));

        $batch = $batch->fresh();
        $proposal = $proposal->fresh(['items', 'paymentBatches']);

        $this->assertSame('va_submitted', $batch->status);
        $this->assertSame('payment_in_progress', $proposal->status);
        $this->assertSame('paid', $proposal->items->first()->payment_status);
        $this->assertSame('VA-123456', $proposal->items->first()->va_number);
    }

    public function test_mark_va_paid_posts_bank_transfer_out_for_each_unposted_batch(): void
    {
        [$adv, $wl] = $this->makeAdvertiserWithWhitelist();
        $approver = $this->makeUser('keuangan', 'Finance '.uniqid());
        $proposal = $this->makeProposal($adv, $wl, 400000);
        $account = $this->makeAccount();

        $this->actingAs($approver)->patch(route('topup.approve', $proposal), [
            'payment_mode' => 'shared_va',
            'source_account_id' => $account->id,
        ]);

        $account = Account::create([
            'name' => 'Top Up Account '.uniqid(),
            'type' => 'bank',
            'current_balance' => 1000000,
            'status' => 'active',
        ]);
        $category = TransactionCategory::create([
            'name' => 'Top Up Out '.uniqid(),
            'type' => 'out',
        ]);
        config([
            'finance.topup.account_id' => $account->id,
            'finance.topup.category_id' => $category->id,
        ]);

        $proposal = $proposal->fresh(['paymentBatches']);
        $batch = $proposal->paymentBatches->first();
        $this->actingAs($adv)->post(route('topup.payment.store', $proposal), [
            'batches' => [
                ['batch_id' => $batch->id, 'va_number' => 'VA-999'],
            ],
        ]);

        $this->actingAs($approver)
            ->patch(route('topup.va-paid', $proposal))
            ->assertRedirect(route('topup.show', $proposal));

        $proposal = $proposal->fresh(['paymentBatches']);
        $batch = $proposal->paymentBatches->first()->fresh();
        $account = $account->fresh();

        $this->assertNotNull($proposal->va_paid_at);
        $this->assertSame('paid', $batch->status);
        $this->assertNotNull($batch->bank_transfer_id);
        $this->assertDatabaseHas('bank_transfers', [
            'id' => $batch->bank_transfer_id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'out',
            'status' => 'approved',
            'source_type' => 'top_up_payment_batch',
            'source_id' => $batch->id,
        ]);
        $this->assertSame('600000.00', (string) $account->current_balance);
        $this->assertCount(1, BankTransfer::where('source_type', 'top_up_payment_batch')->where('source_id', $batch->id)->get());
    }
}
