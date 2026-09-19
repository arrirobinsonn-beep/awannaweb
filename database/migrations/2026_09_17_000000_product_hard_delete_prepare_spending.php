<?php

use App\Models\Product;
use App\Services\ProductDeletionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Persiapan HARD DELETE produk:
 * - spending_harians.product_id NOT NULL → NULLABLE (nullOnDelete) + kolom
 *   `product_label` — saat produk dihapus permanen, spending tidak ikut hilang;
 *   tautannya dilepas dan label "{nama}(Produk dihapus)" dipertahankan.
 * - Purge produk yang ter-soft-delete lama (dulu hapus = soft delete) beserta
 *   varian/jurnal/pivot-nya, supaya tabel bersih sebelum kolom deleted_at
 *   dibuang dan kode produk lama benar-benar bebas dipakai ulang.
 * - Drop `products.deleted_at` — hapus produk kini beneran menghapus baris.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. spending_harians: product_id nullable + label arsip ──
        Schema::table('spending_harians', function (Blueprint $table) {
            $table->string('product_label')->nullable()->after('product_id');
        });

        // Backfill label awal dari produk saat ini (jaga-jaga data yatim lama).
        DB::statement(
            'UPDATE spending_harians sh
             JOIN products p ON p.id = sh.product_id
             SET sh.product_label = p.name'
        );

        // NOT NULL + cascade → nullable + nullOnDelete (spending selamat saat produk dihapus).
        Schema::table('spending_harians', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('spending_harians', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
        });
        Schema::table('spending_harians', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->nullOnDelete();
        });

        // ── 2. Purge produk lama yang masih ter-soft-delete ──
        // Eloquent cascade (bukan FK cascade) agar SpendingHarian di-backfill
        // dulu SEBELUM product_id di-null-kan oleh FK nullOnDelete.
        // Catatan: model Product TIDAK lagi memakai SoftDeletes, jadi query
        // biasa tetap membaca baris yang lama ter-soft-delete (kolom memang
        // belum di-drop di step ini).
        $trashedIds = DB::table('products')->whereNotNull('deleted_at')->pluck('id');
        foreach ($trashedIds as $id) {
            $product = Product::query()->find($id);
            if ($product) {
                app(ProductDeletionService::class)->delete($product, 'system');
            }
        }

        // ── 3. Hapus soft deletes di products (hapus = hapus permanen) ──
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    public function down(): void
    {
        // Kembalikan kolom deleted_at (tanpa menghidupkan kembali data lama).
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Label di-backfill ke null; product_id kembali NOT NULL bila tidak ada
        // baris yatim (baris yatim hasil hard-delete dibiarkan null).
        Schema::table('spending_harians', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        $orphan = DB::table('spending_harians')->whereNull('product_id')->exists();
        if (! $orphan) {
            Schema::table('spending_harians', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable(false)->change();
            });
        }
        Schema::table('spending_harians', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();
        });
        Schema::table('spending_harians', function (Blueprint $table) {
            $table->dropColumn('product_label');
        });
    }
};
