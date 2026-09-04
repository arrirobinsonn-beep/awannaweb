<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new columns if they don't exist
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'status')) {
                $table->string('status', 20)->default('in_transit')->after('created_by');
            }
            if (! Schema::hasColumn('purchases', 'received_qty')) {
                $table->unsignedInteger('received_qty')->nullable()->after('status');
            }
            if (! Schema::hasColumn('purchases', 'received_note')) {
                $table->text('received_note')->nullable()->after('received_qty');
            }
            if (! Schema::hasColumn('purchases', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('received_note');
            }
            if (! Schema::hasColumn('purchases', 'received_by')) {
                $table->foreignId('received_by')->nullable()->after('received_at');
            }
        });

        // Add index if missing
        if (! DB::getSchemaBuilder()->hasIndex('purchases', 'purchases_status_index')) {
            Schema::table('purchases', fn (Blueprint $t) => $t->index('status'));
        }

        // Migrate existing data
        DB::table('purchases')->whereIn('status', ['pending', 'approved', 'rejected'])->update(['status' => 'in_transit']);

        // Drop finance columns if they exist
        if (Schema::hasColumn('purchases', 'source_account_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                // Drop FK constraint first
                $name = $this->getFkName('purchases', 'source_account_id', 'accounts');
                try { $table->dropForeign($name); } catch (\Throwable $e) {}
                $table->dropColumn(['approved_by', 'approved_at', 'rejection_note', 'source_account_id']);
            });
        } elseif (Schema::hasColumn('purchases', 'approved_by')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn(['approved_by', 'approved_at', 'rejection_note']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('rejection_note')->nullable()->after('approved_at');
            $table->foreignId('source_account_id')->nullable()->after('rejection_note');
            $table->dropColumn(['received_qty', 'received_note', 'received_at', 'received_by']);
        });
    }

    private function getFkName(string $table, string $column, string $referenced): string
    {
        return $table . '_' . $column . '_foreign';
    }
};
