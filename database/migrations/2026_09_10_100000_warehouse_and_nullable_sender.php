<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_online_import_batches', function (Blueprint $table) {
            $table->string('sender')->nullable()->change();
        });

        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->string('warehouse')->nullable()->after('shipping_cost');
            $table->index('warehouse');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->dropIndex(['warehouse']);
            $table->dropColumn('warehouse');
        });

        Schema::table('order_online_import_batches', function (Blueprint $table) {
            $table->string('sender')->nullable(false)->change();
        });
    }
};
