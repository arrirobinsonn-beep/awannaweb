<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7); // YYYY-MM
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('spending', 15, 2)->default(0);
            $table->unsignedInteger('lead')->default(0);
            $table->unsignedInteger('paid')->default(0);
            $table->decimal('paid_ratio', 8, 4)->default(0);
            $table->decimal('adjustment', 8, 4)->default(0);
            $table->decimal('cpa_paid', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->decimal('pengali', 15, 2)->default(0);
            $table->decimal('potensi_bonus', 15, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft/approved/disbursed
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('approved_at')->nullable();
            $table->datetime('disbursed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['period', 'user_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_calculations');
    }
};
