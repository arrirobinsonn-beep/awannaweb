<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paket_trackings', function (Blueprint $table) {
            $table->string('handle_by')->nullable()->after('no_telp');
            $table->index('awb', 'idx_pt_awb');
            $table->index('handle_by', 'idx_pt_handle_by');
        });
    }

    public function down(): void
    {
        Schema::table('paket_trackings', function (Blueprint $table) {
            $table->dropIndex('idx_pt_handle_by');
            $table->dropIndex('idx_pt_awb');
            $table->dropColumn('handle_by');
        });
    }
};
