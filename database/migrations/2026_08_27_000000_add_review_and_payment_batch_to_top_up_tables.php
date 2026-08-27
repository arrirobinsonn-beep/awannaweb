<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('top_up_proposals', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'revision_requested',
                'approved',
                'rejected',
                'payment_in_progress',
                'completed',
            ])->default('pending')->change();

            $table->enum('payment_mode', ['shared_va', 'single_va_per_wl'])->nullable()->after('total_nominal');
            $table->decimal('suggested_total_nominal', 15, 2)->nullable()->after('payment_mode');
            $table->foreignId('reviewed_by')->nullable()->after('declined_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        Schema::create('top_up_payment_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('top_up_proposals')->cascadeOnDelete();
            $table->unsignedInteger('batch_no');
            $table->enum('payment_mode', ['shared_va', 'single_va_per_wl']);
            $table->decimal('nominal', 15, 2)->default(0);
            $table->string('va_number')->nullable();
            $table->enum('status', ['waiting_va', 'va_submitted', 'paid', 'cancelled'])->default('waiting_va');
            $table->foreignId('bank_transfer_id')->nullable()->constrained('bank_transfers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['proposal_id', 'batch_no']);
            $table->index(['proposal_id', 'status']);
            $table->index(['status', 'payment_mode']);
        });

        Schema::table('top_up_proposal_items', function (Blueprint $table) {
            $table->foreignId('payment_batch_id')->nullable()->after('proposal_id')->constrained('top_up_payment_batches')->nullOnDelete();
            $table->decimal('approved_nominal', 15, 2)->nullable()->after('nominal');
        });

        Schema::create('top_up_proposal_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('top_up_proposals')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('decision', ['approved', 'revision_requested', 'rejected']);
            $table->decimal('suggested_total_nominal', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['proposal_id', 'created_at']);
            $table->index(['reviewer_id', 'created_at']);
        });


    }

    public function down(): void
    {
        Schema::table('top_up_proposal_items', function (Blueprint $table) {
            $table->dropColumn(['payment_batch_id', 'approved_nominal']);
        });

        Schema::dropIfExists('top_up_payment_batches');
        Schema::dropIfExists('top_up_proposal_reviews');

        Schema::table('top_up_proposals', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['payment_mode', 'suggested_total_nominal', 'reviewed_by', 'reviewed_at']);
            $table->enum('status', ['pending', 'approved', 'declined', 'completed'])->default('pending')->change();
        });
    }
};
