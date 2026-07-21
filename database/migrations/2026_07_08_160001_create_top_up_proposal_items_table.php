<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_up_proposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')
                ->constrained('top_up_proposals')
                ->cascadeOnDelete();
            $table->foreignId('whitelist_id')
                ->constrained('whitelists')
                ->cascadeOnDelete();
            $table->decimal('nominal', 15, 2)->default(0);                // nominal yg diajukan

            // ── Payment (diisi setelah approval) ─────────────
            $table->string('va_number')->nullable();                      // nomor VA untuk top up
            $table->enum('payment_status', ['pending', 'paid'])
                ->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_up_proposal_items');
    }
};
