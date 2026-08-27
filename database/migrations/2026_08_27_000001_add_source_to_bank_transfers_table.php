<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('confirmed_at');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->index(['source_type', 'source_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id', 'type']);
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
