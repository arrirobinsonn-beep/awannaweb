<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_up_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();                                    // advertiser pengaju
            $table->enum('status', [
                'pending', 'approved', 'declined', 'completed',
            ])->default('pending');

            // ── Info performa (untuk referensi super admin) ──
            $table->decimal('previous_topup_total', 15, 2)->nullable();   // total top up sebelumnya
            $table->integer('today_lead')->nullable();                    // total lead hari ini
            $table->integer('today_paid')->nullable();                    // total paid hari ini
            $table->decimal('today_spending', 15, 2)->nullable();         // total spending hari ini
            $table->decimal('total_nominal', 15, 2)->default(0);          // total nominal yg diajukan

            // ── Approval ─────────────────────────────────────
            $table->foreignId('approver_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();                                      // siapa yg approve/decline
            $table->text('decline_note')->nullable();                    // alasan penolakan
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('declined_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_up_proposals');
    }
};
