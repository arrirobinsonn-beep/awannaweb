<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('source_account_id')->nullable()->after('inventory_id')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['source_account_id']);
            $table->dropColumn('source_account_id');
        });
    }
};
