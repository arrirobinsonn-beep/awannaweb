<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SpendingHarian;
use Illuminate\Support\Facades\DB;

/**
 * Hard delete produk + pembersihan data terkait.
 *
 * Kebijakan (keputusan user, 17 September 2026):
 * - Produk dihapus PERMANEN dari DB (tanpa soft delete) sehingga kode produk
 *   langsung bisa dipakai produk baru.
 * - Data KEUANGAN (spending iklan seluruh advertiser) TIDAK ikut dihapus:
 *   tautan product_id dilepas, lalu label baris diisi "{nama}(Produk dihapus)"
 *   agar histori tetap terbaca tanpa memengaruhi perhitungan produk lain.
 * - Data operasional (varian, jurnal stok, stok per gudang, pembelian,
 *   keanggotaan gudang, aturan kemasan) ikut hilang bersama produk via FK
 *   cascade — memang bagian dari produk itu.
 * - Order online & bukti transfer tetap ada; tautan produknya dilepas
 *   (nullOnDelete) — nominal transaksi tidak boleh berubah.
 */
class ProductDeletionService
{
    /**
     * Hapus produk secara permanen + bersihkan data terkait.
     *
     * @return array{product_name: string, spending_relabelled: int}
     */
    public function delete(Product $product): array
    {
        return DB::transaction(function () use ($product) {
            $productId = $product->id;
            $productName = $product->name;

            // ── 1. Arsipkan label spending SEBELUM baris produk hilang ──
            // FK nullOnDelete yang melepas product_id saat delete di bawah;
            // label di-set dulu lewat 1 UPDATE batch (anti N+1).
            $label = $productName.'(Produk dihapus)';
            $spendingRelabelled = SpendingHarian::where('product_id', $productId)
                ->update(['product_label' => $label]);

            // ── 2. Aturan kemasan: produk sebagai TARGET tidak ter-cascade ──
            // FK cascade hanya untuk source_product_id; kombinasi (source,
            // target) bisa multi-gudang → hapus semua baris target ini.
            DB::table('packaging_rules')
                ->where('target_product_id', $productId)
                ->delete();

            // ── 3. Hapus produk (cascade via FK) ──
            // Terhapus berantai: product_variants → stock_movements,
            // product_variant_inventory, product_variant_items, purchases;
            // pivot product_inventory; packaging_rules (sebagai source).
            // Tautan dilepas (nullOnDelete): shipping_orders.product_id /
            // product_variant_id, bank_transfers.product_id,
            // spending_harians.product_id.
            $product->delete();

            return [
                'product_name' => $productName,
                'spending_relabelled' => $spendingRelabelled,
            ];
        });
    }
}
