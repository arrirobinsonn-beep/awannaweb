<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->string('order_online_id', 100)->nullable()->after('product_id');
            $table->index('order_online_id');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->dropIndex(['order_online_id']);
            $table->dropColumn('order_online_id');
        });
    }
};