<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('ad_status', 10)->default('testing')->after('status');
            $table->index('ad_status');
        });

        // Semua produk yang sudah ada dianggap sudah melalui fase testing
        DB::table('products')->whereNull('ad_status')->update(['ad_status' => 'running']);
        DB::table('products')->where('ad_status', '')->update(['ad_status' => 'running']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['ad_status']);
            $table->dropColumn('ad_status');
        });
    }
};
