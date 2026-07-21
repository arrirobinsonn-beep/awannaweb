<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('top_up_proposals', function (Blueprint $table) {
            $table->timestamp('va_paid_at')->nullable()->after('completed_at');
            $table->foreignId('va_paid_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('va_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('top_up_proposals', function (Blueprint $table) {
            $table->dropForeign(['va_paid_by']);
            $table->dropColumn(['va_paid_at', 'va_paid_by']);
        });
    }
};
