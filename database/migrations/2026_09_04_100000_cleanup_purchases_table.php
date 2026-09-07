<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Drop orphan 'receive_note' column (superseded by 'received_note')
            if (Schema::hasColumn('purchases', 'receive_note')) {
                $table->dropColumn('receive_note');
            }

            // Drop legacy approval/finance columns if still present
            $columnsToDrop = [];
            foreach (['approved_by', 'approved_at', 'rejection_note', 'source_account_id'] as $col) {
                if (Schema::hasColumn('purchases', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (! empty($columnsToDrop)) {
                // Drop FK constraint first for source_account_id
                try {
                    $table->dropForeign(['source_account_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn($columnsToDrop);
            }
        });

        // Fix any old status values that slipped through
        DB::table('purchases')
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->update(['status' => 'in_transit']);
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->text('receive_note')->nullable()->after('received_by');
            $table->foreignId('source_account_id')->nullable()->after('receive_note');
            $table->unsignedBigInteger('approved_by')->nullable()->after('source_account_id');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_note')->nullable()->after('approved_at');
        });
    }
};
