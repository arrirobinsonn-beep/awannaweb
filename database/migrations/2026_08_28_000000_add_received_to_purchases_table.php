<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('rejection_note');
            $table->unsignedBigInteger('received_by')->nullable()->after('received_at');
            $table->text('receive_note')->nullable()->after('received_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['received_at', 'received_by', 'receive_note']);
        });
    }
};
