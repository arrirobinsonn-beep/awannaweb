<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paket_trackings', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('kiriman_actual_id')
                ->constrained('products')->nullOnDelete();
            $table->index('product_id', 'idx_pt_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('paket_trackings', function (Blueprint $table) {
            $table->dropIndex('idx_pt_product_id');
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
