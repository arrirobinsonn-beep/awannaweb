<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankTransfer;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceTest extends TestCase
{
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

    private function makeCategory(string $type = 'in'): TransactionCategory
    {
        return TransactionCategory::create([
            'name' => 'Kategori '.uniqid(),
            'type' => $type,
        ]);
    }

    // ─── Akun & Kategori ──────────────────────────────────────────

    public function test_keuangan_can_create_account_and_page_renders(): void
    {
        $keu = $this->makeUser('keuangan');
        $name = 'BCA '.uniqid();

        $this->actingAs($keu)
            ->post(route('finance.accounts.store'), [
                'name' => $name,
                'type' => 'bank',
                'current_balance' => 100000,
                'status' => 'active',
            ])
            ->assertRedirect(route('finance.accounts.index'));

        $this->assertDatabaseHas('accounts', ['name' => $name, 'type' => 'bank']);
        $this->assertSame('100000.00', (string) Account::where('name', $name)->first()->current_balance);

        $this->actingAs($keu)->get(route('finance.accounts.index'))->assertSee($name)->assertOk();
    }

    public function test_account_with_transactions_cannot_be_deleted(): void
    {
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(1000);

        BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $this->makeCategory()->id,
            'type' => 'in',
            'amount' => 100,
            'transaction_date' => now(),
            'created_by' => $keu->id,
            'status' => 'approved',
        ]);

        $this->actingAs($keu)
            ->from(route('finance.accounts.index'))
            ->delete(route('finance.accounts.destroy', $account))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    public function test_category_crud_and_duplicate_rejected(): void
    {
        $keu = $this->makeUser('keuangan');
        $name = 'Bank Transfer '.uniqid();

        $this->actingAs($keu)->post(route('finance.categories.store'), [
            'name' => $name, 'type' => 'in',
        ])->assertRedirect(route('finance.categories.index'));

        // Duplikat kombinasi nama+tipe ditolak
        $this->actingAs($keu)
            ->from(route('finance.categories.index'))
            ->post(route('finance.categories.store'), [
                'name' => $name, 'type' => 'in',
            ])
            ->assertSessionHasErrors('name');

        $cat = TransactionCategory::where('name', $name)->first();
        $this->actingAs($keu)->put(route('finance.categories.update', $cat), [
            'name' => 'Transfer Masuk '.uniqid(), 'type' => 'in',
        ])->assertRedirect();

        $this->assertStringStartsWith('Transfer Masuk', $cat->fresh()->name);
    }

    // ─── Rekening Koran ──────────────────────────────────────────

    public function test_rekening_koran_shows_mutasi_and_running_balance(): void
    {
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(1_000_000);
        $category = $this->makeCategory('in');
        $tanggal = '2026-07-05';

        // 2 mutasi approved: in 134.000 lalu out 6.000
        $in = BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 134000,
            'description' => 'PEND. TF AN MARIA MAGDALENA',
            'transaction_date' => $tanggal.' 10:00:00',
            'status' => 'approved',
            'created_by' => $keu->id,
        ]);
        $out = BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'out',
            'amount' => 6000,
            'description' => 'BIAYA ADMIN',
            'transaction_date' => $tanggal.' 11:00:00',
            'status' => 'approved',
            'created_by' => $keu->id,
        ]);
        app(\App\Services\FinanceService::class)->applyBankTransfer($in);
        app(\App\Services\FinanceService::class)->applyBankTransfer($out);

        $res = $this->actingAs($keu)->get(route('finance.bank-statement.index', [
            'account_id' => $account->id,
            'dari' => '2026-07-01',
            'sampai' => '2026-07-31',
        ]))->assertOk();

        $res->assertSee('REKENING KORAN');
        $res->assertSee('SALDO AWAL');
        // saldo awal = 1.000.000 (mutasi mulai 5 Juli, sebelum periode awal tidak ada)
        $res->assertSee('1.000.000,00');
        $res->assertSee('PEND. TF AN MARIA MAGDALENA');
        $res->assertSee('134.000,00');
        $res->assertSee('BIAYA ADMIN');
        $res->assertSee('6.000,00');
        // saldo berjalan: 1.000.000 + 134.000 = 1.134.000, lalu −6.000 = 1.128.000
        $res->assertSee('1.134.000,00');
        $res->assertSee('1.128.000,00');

        $in->delete();
        $out->delete();
        app(\App\Services\FinanceService::class)->reverseBankTransfer($in);
        app(\App\Services\FinanceService::class)->reverseBankTransfer($out);
        $account->delete();
        $category->delete();
    }

    public function test_rekening_koran_uses_saldo_awal_from_earlier_movements(): void
    {
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(500_000);
        $category = $this->makeCategory('in');

        // mutasi sebelum periode: in 200.000 → saldo sebelum periode = 700.000
        $early = BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 200000,
            'description' => 'SEBELUM PERIODE',
            'transaction_date' => '2026-06-10 10:00:00',
            'status' => 'approved',
            'created_by' => $keu->id,
        ]);
        app(\App\Services\FinanceService::class)->applyBankTransfer($early);

        // mutasi dalam periode: out 50.000
        $inPeriod = BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'out',
            'amount' => 50000,
            'description' => 'DALAM PERIODE',
            'transaction_date' => '2026-07-15 10:00:00',
            'status' => 'approved',
            'created_by' => $keu->id,
        ]);
        app(\App\Services\FinanceService::class)->applyBankTransfer($inPeriod);

        $res = $this->actingAs($keu)->get(route('finance.bank-statement.index', [
            'account_id' => $account->id,
            'dari' => '2026-07-01',
            'sampai' => '2026-07-31',
        ]))->assertOk();

        // saldo awal = 750.000 − 50.000 = 700.000
        $res->assertSee('700.000,00');
        $res->assertDontSee('SEBELUM PERIODE');
        $res->assertSee('DALAM PERIODE');
        // saldo akhir = 700.000 − 50.000 = 650.000
        $res->assertSee('650.000,00');

        $early->delete();
        $inPeriod->delete();
        app(\App\Services\FinanceService::class)->reverseBankTransfer($early);
        app(\App\Services\FinanceService::class)->reverseBankTransfer($inPeriod);
        $account->delete();
        $category->delete();
    }

    public function test_rekening_koran_includes_account_transfers(): void
    {
        $keu = $this->makeUser('keuangan');
        $a = $this->makeAccount(1_000_000);
        $b = $this->makeAccount(2_000_000);
        $transfer = \App\Models\AccountTransfer::create([
            'from_account_id' => $a->id,
            'to_account_id' => $b->id,
            'amount' => 300000,
            'transfer_date' => '2026-07-20 10:00:00',
            'description' => 'ALIHAN DANA KE BCA AGUS',
            'created_by' => $keu->id,
        ]);
        app(\App\Services\FinanceService::class)->applyAccountTransfer($transfer);

        $res = $this->actingAs($keu)->get(route('finance.bank-statement.index', [
            'account_id' => $a->id,
            'dari' => '2026-07-01',
            'sampai' => '2026-07-31',
        ]))->assertOk();

        $res->assertSee('ALIHAN DANA KE');
        // dari akun A: saldo awal 1.000.000, keluar 300.000 → 700.000
        $res->assertSee('700.000,00');

        $transfer->delete();
        app(\App\Services\FinanceService::class)->reverseAccountTransfer($transfer);
        $a->delete();
        $b->delete();
    }

    public function test_rekening_koran_guard_advertiser(): void
    {
        $advertiser = $this->makeUser('advertiser');
        $this->actingAs($advertiser)->get(route('finance.bank-statement.index'))->assertForbidden();
        $this->actingAs($advertiser)->get(route('finance.bank-statement.pdf'))->assertForbidden();
    }

    public function test_rekening_koran_pdf_download(): void
    {
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(500_000);
        $category = $this->makeCategory('in');

        $bt = BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 134000,
            'description' => 'PEND. TF AN MARIA MAGDALENA',
            'transaction_date' => '2026-07-05 10:00:00',
            'status' => 'approved',
            'created_by' => $keu->id,
        ]);
        app(\App\Services\FinanceService::class)->applyBankTransfer($bt);

        $res = $this->actingAs($keu)->get(route('finance.bank-statement.pdf', [
            'account_id' => $account->id,
            'dari' => '2026-07-01',
            'sampai' => '2026-07-31',
        ]));

        $res->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('content-type'));
        $res->assertDownload();

        // isi PDF memuat header & mutasi
        $content = $res->getContent();
        $this->assertStringContainsString('application/pdf', $res->headers->get('content-type'));
        $this->assertStringContainsString(mb_convert_encoding('Rekening Koran', 'UTF-16BE'), $content);

        $bt->delete();
        app(\App\Services\FinanceService::class)->reverseBankTransfer($bt);
        $account->delete();
        $category->delete();
    }

    public function test_rekening_koran_pdf_requires_valid_params(): void
    {
        $keu = $this->makeUser('keuangan');
        $this->actingAs($keu)->get(route('finance.bank-statement.pdf'))->assertRedirect();
    }

    // ─── Transfer Antar Akun ──────────────────────────────────────

    public function test_transfer_moves_balance_between_accounts(): void
    {
        $keu = $this->makeUser('keuangan');
        $from = $this->makeAccount(1000000);
        $to = $this->makeAccount(0);

        $this->actingAs($keu)->post(route('finance.transfers.store'), [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 400000,
            'transfer_date' => now()->format('Y-m-d'),
            'description' => 'pindah saldo',
        ])->assertRedirect(route('finance.transfers.index'))->assertSessionHas('success');

        $this->assertSame('600000.00', (string) $from->fresh()->current_balance);
        $this->assertSame('400000.00', (string) $to->fresh()->current_balance);
    }

    public function test_transfer_insufficient_balance_rejected(): void
    {
        $keu = $this->makeUser('keuangan');
        $from = $this->makeAccount(1000);
        $to = $this->makeAccount(0);

        $this->actingAs($keu)
            ->from(route('finance.transfers.index'))
            ->post(route('finance.transfers.store'), [
                'from_account_id' => $from->id,
                'to_account_id' => $to->id,
                'amount' => 5000,
                'transfer_date' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame('1000.00', (string) $from->fresh()->current_balance);
        $this->assertSame('0.00', (string) $to->fresh()->current_balance);
    }

    public function test_delete_transfer_reverses_balance(): void
    {
        $keu = $this->makeUser('keuangan');
        $from = $this->makeAccount(1000000);
        $to = $this->makeAccount(0);

        $this->actingAs($keu)->post(route('finance.transfers.store'), [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 250000,
            'transfer_date' => now()->format('Y-m-d'),
        ]);

        $transfer = \App\Models\AccountTransfer::firstWhere('from_account_id', $from->id);
        $this->actingAs($keu)->delete(route('finance.transfers.destroy', $transfer))->assertRedirect();

        $this->assertSame('1000000.00', (string) $from->fresh()->current_balance);
        $this->assertSame('0.00', (string) $to->fresh()->current_balance);
    }

    // ─── Bukti Transfer (CS upload → approve/reject) ─────────────

    public function test_cs_upload_pending_balance_unchanged(): void
    {
        Storage::fake('public');
        $cs = $this->makeUser('cs');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');

        $this->actingAs($cs)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 500000,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'TF pembeli',
            'image' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('finance.bank-transfers.index'))->assertSessionHas('success');

        $bt = BankTransfer::where('account_id', $account->id)->first();
        $this->assertSame('pending', $bt->status);
        $this->assertNotNull($bt->image_url);
        Storage::disk('public')->assertExists($bt->image_url);

        // Saldo belum berubah
        $this->assertSame('0.00', (string) $account->fresh()->current_balance);

        // CS hanya lihat uploadannya sendiri
        $this->actingAs($cs)->get(route('finance.bank-transfers.index'))->assertSee('TF pembeli')->assertOk();
    }

    public function test_cs_upload_notifies_all_approver_roles(): void
    {
        Storage::fake('public');
        $cs = $this->makeUser('cs');
        $keu = $this->makeUser('keuangan');
        $owner = $this->makeUser('owner');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');

        $this->actingAs($cs)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 250000,
            'transaction_date' => now()->format('Y-m-d'),
            'image' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect()->assertSessionHas('success');

        $notifs = \App\Models\Notification::where('type', 'bank_transfer_received')
            ->where('from_user_id', $cs->id)
            ->pluck('user_id')
            ->all();
        $this->assertContains($keu->id, $notifs);
        $this->assertContains($owner->id, $notifs);
        $this->assertNotContains($cs->id, $notifs);
    }

    public function test_download_bukti_ok_before_approve_404_after(): void
    {
        Storage::fake('public');
        $cs = $this->makeUser('cs');
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');

        $this->actingAs($cs)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 300000,
            'transaction_date' => now()->format('Y-m-d'),
            'image' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $bt = BankTransfer::where('account_id', $account->id)->first();

        $this->actingAs($keu)->get(route('finance.bank-transfers.download', $bt))->assertOk();

        $this->actingAs($keu)->post(route('finance.bank-transfers.approve', $bt));
        $this->actingAs($keu)->get(route('finance.bank-transfers.download', $bt))->assertNotFound();
    }

    public function test_approve_adds_balance_and_deletes_image(): void
    {
        Storage::fake('public');
        $cs = $this->makeUser('cs');
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');

        $this->actingAs($cs)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 750000,
            'transaction_date' => now()->format('Y-m-d'),
            'image' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $bt = BankTransfer::where('account_id', $account->id)->first();
        $this->actingAs($keu)
            ->from(route('finance.bank-transfers.index'))
            ->post(route('finance.bank-transfers.approve', $bt))
            ->assertRedirect()
            ->assertSessionHas('success');

        $bt->refresh();
        $this->assertSame('approved', $bt->status);
        $this->assertNull($bt->image_url);
        $this->assertSame('750000.00', (string) $account->fresh()->current_balance);
    }

    public function test_reject_saves_note_balance_unchanged_and_notifies_cs(): void
    {
        Storage::fake('public');
        $cs = $this->makeUser('cs');
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');

        $this->actingAs($cs)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 100000,
            'transaction_date' => now()->format('Y-m-d'),
            'image' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $bt = BankTransfer::where('account_id', $account->id)->first();

        // Reject tanpa catatan → error
        $this->actingAs($keu)
            ->from(route('finance.bank-transfers.index'))
            ->post(route('finance.bank-transfers.reject', $bt), [])
            ->assertSessionHasErrors('rejection_note');

        // Reject dengan catatan → feedback tersimpan + notif ke CS + balance tetap
        $this->actingAs($keu)
            ->from(route('finance.bank-transfers.index'))
            ->post(route('finance.bank-transfers.reject', $bt), [
                'rejection_note' => 'Nominal tidak sesuai bukti',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $bt->refresh();
        $this->assertSame('rejected', $bt->status);
        $this->assertSame('Nominal tidak sesuai bukti', $bt->rejection_note);
        $this->assertSame('0.00', (string) $account->fresh()->current_balance);
        $this->assertNotNull($bt->image_url); // gambar disimpan agar CS bisa lihat buktinya
        Storage::disk('public')->assertExists($bt->image_url);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $cs->id,
            'type' => 'bank_transfer_rejected',
        ]);

        // CS melihat feedback-nya
        $this->actingAs($cs)->get(route('finance.bank-transfers.index'))->assertSee('Nominal tidak sesuai bukti');
    }

    public function test_approver_out_decreases_balance_immediately(): void
    {
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(500000);
        $category = $this->makeCategory('out');

        $this->actingAs($keu)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'out',
            'amount' => 200000,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Biaya operasional',
        ])->assertRedirect()->assertSessionHas('success');

        $bt = BankTransfer::where('account_id', $account->id)->first();
        $this->assertSame('approved', $bt->status);
        $this->assertSame('300000.00', (string) $account->fresh()->current_balance);
    }

    public function test_long_chat_template_description_stored_fully_and_detail_modal_renders(): void
    {
        Storage::fake('public');
        $cs = $this->makeUser('cs');
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');

        $template = "Terima kasih, Kami sudah terima pesanan anda dengan rincian sebagai berikut\n"
            ."Produk: YB.1 Kacamata Sporty Photocromic Unisex - 23445\n"
            ."Harga: Rp119.000\nOngkir: Rp64.000\nTotal: Rp183.000\n\n"
            ."Dikirim ke:\nNama: Yoost Fanny Tololiu\nNo HP: +6281218607606\n"
            ."Alamat: Tk Lifan Mart Jl. Tololiu Supit No.40 Tingkulu\nKota: Kota Manado\n"
            ."Kecamatan: Wanea\nProvinsi: Sulawesi Utara\n\n"
            ."Silahkan Transfer Nomor Rekening Berikut :\nBCA\nNo. Rek: 7112903481\nAtas Nama: Siti Rosidah\n\n"
            ."Jika Sudah Transfer Mohon Konfirmasi Agar Bisa Segera Diproses\n\n"
            .rtrim(str_repeat('@~Rek BCA Mandiri Siti Rosidah ', 3));

        $this->assertGreaterThan(500, strlen($template));

        $this->actingAs($cs)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 183000,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => $template,
            'image' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect()->assertSessionHas('success');

        $bt = BankTransfer::where('account_id', $account->id)->first();
        $this->assertSame($template, $bt->description);

        // Modal detail tampil di halaman approver (klik keterangan/bukti)
        $this->actingAs($keu)
            ->get(route('finance.bank-transfers.index'))
            ->assertOk()
            ->assertSee('Detail Transaksi')
            ->assertSee('Download Bukti')
            ->assertSee('Salin Keterangan')
            ->assertSee('openBtDetail');
    }

    public function test_cs_upload_with_product_stored_and_shown(): void
    {
        Storage::fake('public');
        $cs = $this->makeUser('cs');
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');
        $product = \App\Models\Product::create([
            'code' => 'P'.strtoupper(substr(uniqid(), -5)),
            'name' => 'Kacamata Test '.uniqid(),
            'purchase_price' => 10000,
            'sell_price' => 10000,
            'unit' => 'pcs',
        ]);

        $orderId = 'CBC-'.rand(1000, 9999);
        $this->actingAs($cs)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'product_id' => $product->id,
            'order_online_id' => $orderId,
            'type' => 'in',
            'amount' => 183000,
            'transaction_date' => now()->format('Y-m-d'),
            'image' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect()->assertSessionHas('success');

        $bt = BankTransfer::where('account_id', $account->id)->first();
        $this->assertSame($product->id, $bt->product_id);
        $this->assertSame($orderId, $bt->order_online_id);

        $this->actingAs($keu)
            ->get(route('finance.bank-transfers.index', ['account_id' => $account->id]))
            ->assertOk()
            ->assertSee($product->code)
            ->assertSee($orderId);
    }

    public function test_index_filter_by_product(): void
    {
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');
        $productA = \App\Models\Product::create([
            'code' => 'PA'.strtoupper(substr(uniqid(), -5)),
            'name' => 'Kacamata A',
            'purchase_price' => 10000,
            'sell_price' => 10000,
            'unit' => 'pcs',
        ]);
        $productB = \App\Models\Product::create([
            'code' => 'PB'.strtoupper(substr(uniqid(), -5)),
            'name' => 'Kacamata B',
            'purchase_price' => 10000,
            'sell_price' => 10000,
            'unit' => 'pcs',
        ]);

        $btA = BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'product_id' => $productA->id,
            'type' => 'in',
            'amount' => 100000,
            'description' => 'order A',
            'transaction_date' => '2026-07-05 10:00:00',
            'status' => 'approved',
            'created_by' => $keu->id,
        ]);
        BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'product_id' => $productB->id,
            'type' => 'in',
            'amount' => 50000,
            'description' => 'order B',
            'transaction_date' => '2026-07-06 10:00:00',
            'status' => 'approved',
            'created_by' => $keu->id,
        ]);

        $this->actingAs($keu)
            ->get(route('finance.bank-transfers.index', ['account_id' => $account->id, 'product_id' => $productA->id]))
            ->assertOk()
            ->assertSee('order A')
            ->assertDontSee('order B');

        $btA->delete();
        BankTransfer::where('account_id', $account->id)->delete();
        $productA->delete();
        $productB->delete();
        $account->delete();
        $category->delete();
    }

    public function test_index_search_by_buyer_name_in_description(): void
    {
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');

        $buyer = 'Nur Hidayah '.uniqid();
        BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 100000,
            'description' => "Nama: {$buyer}\nAlamat: Jakarta",
            'transaction_date' => '2026-07-05 10:00:00',
            'status' => 'approved',
            'created_by' => $keu->id,
        ]);
        BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 50000,
            'description' => 'order lain',
            'transaction_date' => '2026-07-06 10:00:00',
            'status' => 'approved',
            'created_by' => $keu->id,
        ]);

        $this->actingAs($keu)
            ->get(route('finance.bank-transfers.index', ['search' => $buyer]))
            ->assertOk()
            ->assertSee($buyer)
            ->assertDontSee('order lain');

        BankTransfer::where('account_id', $account->id)->delete();
        $account->delete();
        $category->delete();
    }

    public function test_index_search_by_cs_name_and_order_id(): void
    {
        $keu = $this->makeUser('keuangan');
        $cs = $this->makeUser('cs');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');
        $orderId = 'CBC-SRCH-'.rand(1000, 9999);

        BankTransfer::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 100000,
            'description' => 'chat konfirmasi',
            'order_online_id' => $orderId,
            'transaction_date' => '2026-07-05 10:00:00',
            'status' => 'approved',
            'created_by' => $cs->id,
        ]);

        $this->actingAs($keu)
            ->get(route('finance.bank-transfers.index', ['search' => $cs->nama]))
            ->assertOk()
            ->assertSee($orderId);

        $this->actingAs($keu)
            ->get(route('finance.bank-transfers.index', ['search' => $orderId]))
            ->assertOk()
            ->assertSee($orderId);

        BankTransfer::where('account_id', $account->id)->delete();
        $account->delete();
        $category->delete();
    }

    public function test_cs_cannot_upload_out(): void
    {
        Storage::fake('public');
        $cs = $this->makeUser('cs');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('out');

        $this->actingAs($cs)
            ->from(route('finance.bank-transfers.index'))
            ->post(route('finance.bank-transfers.store'), [
                'account_id' => $account->id,
                'category_id' => $category->id,
                'type' => 'out',
                'amount' => 100000,
                'transaction_date' => now()->format('Y-m-d'),
                'image' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('bank_transfers', ['account_id' => $account->id]);
    }

    public function test_delete_bank_transfer_reverses_balance(): void
    {
        $keu = $this->makeUser('keuangan');
        $account = $this->makeAccount(1000000);
        $category = $this->makeCategory('in');

        // Keuangan catat langsung (approved)
        $this->actingAs($keu)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 300000,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $bt = BankTransfer::where('account_id', $account->id)->first();
        $this->assertSame('1300000.00', (string) $account->fresh()->current_balance);

        $this->actingAs($keu)
            ->from(route('finance.bank-transfers.index'))
            ->delete(route('finance.bank-transfers.destroy', $bt))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bank_transfers', ['id' => $bt->id]);
        $this->assertSame('1000000.00', (string) $account->fresh()->current_balance);
    }

    // ─── Role Guard ──────────────────────────────────────────────

    public function test_advertiser_cannot_access_finance_pages(): void
    {
        $adv = $this->makeUser('advertiser');

        $this->actingAs($adv)->get(route('finance.accounts.index'))->assertForbidden();
        $this->actingAs($adv)->get(route('finance.transfers.index'))->assertForbidden();
        $this->actingAs($adv)->get(route('finance.bank-transfers.index'))->assertForbidden();
    }

    public function test_approve_only_for_approver_roles(): void
    {
        Storage::fake('public');
        $cs = $this->makeUser('cs');
        $otherCs = $this->makeUser('cs');
        $account = $this->makeAccount(0);
        $category = $this->makeCategory('in');

        $this->actingAs($cs)->post(route('finance.bank-transfers.store'), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'in',
            'amount' => 100000,
            'transaction_date' => now()->format('Y-m-d'),
            'image' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $bt = BankTransfer::where('account_id', $account->id)->first();

        $this->actingAs($otherCs)->post(route('finance.bank-transfers.approve', $bt))->assertForbidden();
        $this->assertSame('pending', $bt->fresh()->status);
        $this->assertSame('0.00', (string) $account->fresh()->current_balance);
    }
}