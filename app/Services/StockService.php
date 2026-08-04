<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Catat barang masuk ke jurnal + tambah stok produk.
     */
    public function recordIn(
        int $productId,
        string $date,
        int $quantity,
        ?float $unitPrice = null,
        string $reference = 'adjustment',
        ?int $referenceId = null,
        ?string $note = null,
        ?int $createdBy = null
    ): StockMovement {
        return DB::transaction(function () use ($productId, $date, $quantity, $unitPrice, $reference, $referenceId, $note, $createdBy) {
            $movement = StockMovement::updateOrCreate(
                ['reference' => $reference, 'reference_id' => $referenceId, 'type' => 'in'],
                [
                    'product_id' => $productId,
                    'date' => $date,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'note' => $note,
                    'created_by' => $createdBy,
                ]
            );

            $this->recalculateStock($productId);

            return $movement;
        });
    }

    /**
     * Catat barang keluar ke jurnal + kurangi stok produk.
     *
     * @throws \RuntimeException jika stok tidak mencukupi
     */
    public function recordOut(
        int $productId,
        string $date,
        int $quantity,
        string $reference = 'adjustment',
        ?int $referenceId = null,
        ?string $note = null,
        ?int $createdBy = null
    ): StockMovement {
        return DB::transaction(function () use ($productId, $date, $quantity, $reference, $referenceId, $note, $createdBy) {
            $product = Product::findOrFail($productId);
            $current = $this->stockOf($productId);

            if ($quantity > $current) {
                throw new \RuntimeException(
                    "Stok tidak mencukupi: {$product->nama_produk} tersisa {$current}, keluar {$quantity}."
                );
            }

            $movement = StockMovement::updateOrCreate(
                ['reference' => $reference, 'reference_id' => $referenceId, 'type' => 'out'],
                [
                    'product_id' => $productId,
                    'date' => $date,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'note' => $note,
                    'created_by' => $createdBy,
                ]
            );

            $this->recalculateStock($productId);

            return $movement;
        });
    }

    /**
     * Hapus jurnal untuk reference tertentu lalu hitung ulang stok.
     */
    public function reverseReference(string $reference, ?int $referenceId = null): void
    {
        $productIds = StockMovement::where('reference', $reference)
            ->when($referenceId !== null, fn ($q) => $q->where('reference_id', $referenceId))
            ->pluck('product_id')
            ->unique();

        StockMovement::where('reference', $reference)
            ->when($referenceId !== null, fn ($q) => $q->where('reference_id', $referenceId))
            ->delete();

        foreach ($productIds as $id) {
            $this->recalculateStock($id);
        }
    }

    /**
     * Hitung ulang kolom stok produk dari kumpulan jurnal.
     */
    public function recalculateStock(int $productId): void
    {
        $total = StockMovement::where('product_id', $productId)
            ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE 0 END) as masuk, SUM(CASE WHEN type='out' THEN quantity ELSE 0 END) as keluar")
            ->first();

        $stock = ($total->masuk ?? 0) - ($total->keluar ?? 0);

        Product::where('id', $productId)->update(['stok' => max(0, $stock)]);
    }

    /**
     * Stok saat ini untuk produk (dari jurnal).
     */
    public function stockOf(int $productId): int
    {
        $total = StockMovement::where('product_id', $productId)
            ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE 0 END) as masuk, SUM(CASE WHEN type='out' THEN quantity ELSE 0 END) as keluar")
            ->first();

        return (int) (($total->masuk ?? 0) - ($total->keluar ?? 0));
    }

    /**
     * Hitung HPP rata-rata tertimbang setelah barang masuk.
     */
    public function hppRataRata(Product $product, int $quantity, float $unitPrice, float $shippingCost = 0): float
    {
        $oldStock = $product->stok ?? 0;
        $oldHpp = (float) ($product->harga_beli ?? 0);

        if ($oldStock + $quantity <= 0) {
            return round($unitPrice, 2);
        }

        $newHpp = (($oldStock * $oldHpp) + ($quantity * $unitPrice) + $shippingCost) / ($oldStock + $quantity);

        return round($newHpp, 2);
    }

    /**
     * Hitung ulang HPP produk dari rata-rata harga jurnal 'in' (bobot qty).
     */
    public function recalculateHpp(int $productId): void
    {
        $rows = StockMovement::where('product_id', $productId)
            ->where('type', 'in')
            ->whereNotNull('unit_price')
            ->selectRaw('SUM(quantity * unit_price) as total_cost, SUM(quantity) as total_qty')
            ->first();

        $hpp = ($rows->total_qty ?? 0) > 0
            ? round(($rows->total_cost ?? 0) / $rows->total_qty, 2)
            : 0;

        Product::where('id', $productId)->update(['harga_beli' => $hpp]);
    }

    /**
     * Recalculate HPP + stok semua produk dari jurnal (maintenance).
     */
    public function recalculateAll(): void
    {
        foreach (Product::pluck('id') as $id) {
            $this->recalculateStock($id);
            $this->recalculateHpp($id);
        }
    }
}
