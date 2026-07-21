<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('top_up_proposal_items', function (Blueprint $table) {
            $table->decimal('sisa_saldo_dilaporkan', 15, 2)->nullable()
                ->after('paid_at')
                ->comment('Sisa saldo whitelist yg dilaporkan advertiser saat konfirmasi VA dibayar');
        });
    }

    public function down(): void
    {
        Schema::table('top_up_proposal_items', function (Blueprint $table) {
            $table->dropColumn('sisa_saldo_dilaporkan');
        });
    }
};
