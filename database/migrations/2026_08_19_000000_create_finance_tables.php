<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sektor Keuangan — 4 tabel inti (fitur U):
 *
 * - accounts               : sumber uang perusahaan (rekening, cash, aggregator,
 *                            ewallet, other) + current_balance (saldo terkini).
 * - transaction_categories : kategori transaksi bank_transfers (type in/out).
 * - account_transfers      : operan saldo antar akun (from → to, tanpa type).
 * - bank_transfers         : transaksi masuk/keluar per akun + bukti gambar
 *                            (CS upload → pending → approved/rejected).
 *
 * Aturan saldo:
 * - bank_transfers HANYA mengubah current_balance saat status = approved
 *   (in = +amount, out = -amount).
 * - account_transfers: from -= amount, to += amount (validasi saldo cukup).
 * - FK memakai RESTRICT default → penghapusan akun/kategori yang punya
 *   transaksi diblokir DB (controller memberikan pesan yang ramah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['cash', 'bank', 'aggregator', 'ewallet', 'other'])
                ->default('bank');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['in', 'out']);
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('accounts');
            $table->foreignId('to_account_id')->constrained('accounts');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->dateTime('transfer_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('from_account_id');
            $table->index('to_account_id');
            $table->index('transfer_date');
        });

        Schema::create('bank_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('category_id')->constrained('transaction_categories');
            $table->enum('type', ['in', 'out']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->dateTime('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('image_url', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_note')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transfers');
        Schema::dropIfExists('account_transfers');
        Schema::dropIfExists('transaction_categories');
        Schema::dropIfExists('accounts');
    }
};
