<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL enum tidak bisa ditambah value lewat Blueprint → raw SQL
        DB::statement("ALTER TABLE bank_transfers MODIFY COLUMN status ENUM('pending','confirmed','approved','rejected') DEFAULT 'pending'");

        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->foreignId('confirmed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable()->after('confirmed_by');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['confirmed_by', 'confirmed_at']);
        });

        DB::statement("ALTER TABLE bank_transfers MODIFY COLUMN status ENUM('pending','approved','rejected') DEFAULT 'pending'");
    }
};
