<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spending_harians', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();                     // advertiser pemilik data
            $table->foreignId('whitelist_id')
                ->constrained('whitelists')
                ->cascadeOnDelete();                     // akun iklan yang dipakai
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();                     // produk yang diiklankan
            $table->decimal('spending', 15, 2)->default(0);   // biaya iklan
            $table->integer('lead')->default(0);               // total penanya
            $table->integer('paid')->default(0);               // total yang membeli
            $table->decimal('paid_ratio', 8, 4)->default(0);   // paid / lead * 100 (%)
            $table->decimal('cpa_lead', 15, 2)->default(0);    // spending / lead
            $table->decimal('cpa_paid', 15, 2)->default(0);    // spending / paid
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spending_harians');
    }
};
