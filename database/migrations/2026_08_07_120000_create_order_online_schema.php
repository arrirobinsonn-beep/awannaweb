<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_online_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('sender')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('aggregator_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('matched_rows')->default(0);
            $table->unsignedInteger('unmatched_rows')->default(0);
            $table->unsignedInteger('phone_mismatch_rows')->default(0);
            $table->unsignedInteger('status_updated_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('courier_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order');
            $table->string('payment_method')->nullable();
            $table->string('province')->nullable();
            $table->string('courier');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('sort_order');
            $table->index(['payment_method', 'province']);
        });

        Schema::create('shipping_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_online_import_batch_id')
                ->constrained('order_online_import_batches')
                ->cascadeOnDelete();

            $table->string('order_id')->nullable();
            $table->string('awb')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_normalized')->nullable();
            $table->text('address')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('subdistrict')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('payment_method')->nullable();
            $table->enum('status', ['real', 'tembakan', 'belum_diproses', 'cancel', 'duplikat'])->nullable();
            $table->string('handled_by')->nullable();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('courier')->nullable()->index();
            $table->string('courier_note')->nullable();

            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable()->index();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->text('stock_note')->nullable();

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('weight', 10, 3)->default(0);
            $table->decimal('product_price', 14, 2)->default(0);
            $table->boolean('is_cod')->default(false);
            $table->decimal('cod_amount', 14, 2)->default(0);

            $table->string('aggregator_status')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('awb');
            $table->index('phone');
            $table->index('phone_normalized');
            $table->index('status');
            $table->index('payment_method');
            $table->index('province');
            $table->index('handled_by');
            $table->index('product_variant_id');
            $table->index('order_online_import_batch_id');
            $table->index('last_synced_at');

            $table->index(['status', 'payment_method']);
            $table->index(['payment_method', 'province']);
            $table->index(['awb', 'phone']);

            $table->unique(['order_online_import_batch_id', 'order_id'], 'shipping_orders_batch_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_orders');
        Schema::dropIfExists('courier_rules');
        Schema::dropIfExists('aggregator_sync_batches');
        Schema::dropIfExists('order_online_import_batches');
    }
};
