<?php

namespace App\Services;

use App\Models\PackagingRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantInventory;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockService
{
    /** Produk kacamata — dipakai label export (nama + power + qty pcs). */
    public const KACAMATA_CODES = ['KMP', 'KSP', 'KBJ'];

    /** Kode produk pendamping KBJ yang selalu di-split (KBJ = ceil, KDF = floor). */
    public const PACK_KDF_CODE = 'KDF';

    private ?Collection $packagingRulesCache = null;

    private array $defaultVariantCache = [];

    /**
     * Catat barang masuk ke jurnal + tambah stok varian.
     * `inventoryId` opsional — bila diisi, pergerakan tercatat ke gudang tsb
     * sehingga stok bisa dilihat/dikelola per gudang.
     */
    public function recordIn(
        int $variantId,
        string $date,
        int $quantity,
        ?float $unitPrice = null,
        string $reference = 'adjustment',
        ?int $referenceId = null,
        ?string $note = null,
        ?int $createdBy = null,
        ?int $inventoryId = null
    ): StockMovement {
        return DB::transaction(function () use ($variantId, $date, $quantity, $unitPrice, $reference, $referenceId, $note, $createdBy, $inventoryId) {
            $inventoryId ??= ProductVariant::find($variantId)?->product?->primaryInventoryId();

            $movement = StockMovement::updateOrCreate(
                ['reference' => $reference, 'reference_id' => $referenceId, 'type' => 'in', 'product_variant_id' => $variantId],
                [
                    'product_variant_id' => $variantId,
                    'inventory_id' => $inventoryId,
                    'date' => $date,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'note' => $note,
                    'created_by' => $createdBy,
                ]
            );

            $this->recalculateStock($variantId);
            $this->syncVariantInventoryStocks($variantId);

            return $movement;
        });
    }

    /**
     * Catat barang keluar ke jurnal + kurangi stok varian.
     * `inventoryId` opsional — bila diisi, pergerakan tercatat ke gudang tsb.
     *
     * @throws \RuntimeException jika stok tidak mencukupi
     */
    public function recordOut(
        int $variantId,
        string $date,
        int $quantity,
        string $reference = 'adjustment',
        ?int $referenceId = null,
        ?string $note = null,
        ?int $createdBy = null,
        ?int $inventoryId = null
    ): StockMovement {
        return DB::transaction(function () use ($variantId, $date, $quantity, $reference, $referenceId, $note, $createdBy, $inventoryId) {
            $variant = ProductVariant::findOrFail($variantId);
            $inventoryId ??= $variant->product?->primaryInventoryId();
            $current = $this->stockOf($variantId);

            if ($quantity > $current) {
                throw new \RuntimeException(
                    "Stok tidak mencukupi: {$variant->name} tersisa {$current}, keluar {$quantity}."
                );
            }

            $existing = StockMovement::where('reference', $reference)
                ->where('reference_id', $referenceId)
                ->where('type', 'out')
                ->whereHas('variant', fn ($q) => $q->where('product_id', $variant->product_id))
                ->where('product_variant_id', '!=', $variantId)
                ->first();

            if ($existing && (int) $existing->product_variant_id !== $variantId) {
                $oldVariantId = (int) $existing->product_variant_id;
                $existing->delete();
                $this->recalculateStock($oldVariantId);
                $this->syncVariantInventoryStocks($oldVariantId);
            }

            $movement = StockMovement::updateOrCreate(
                ['reference' => $reference, 'reference_id' => $referenceId, 'type' => 'out', 'product_variant_id' => $variantId],
                [
                    'product_variant_id' => $variantId,
                    'inventory_id' => $inventoryId,
                    'date' => $date,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'note' => $note,
                    'created_by' => $createdBy,
                ]
            );

            $this->recalculateStock($variantId);
            $this->syncVariantInventoryStocks($variantId);

            return $movement;
        });
    }

    /**
     * Catat barang keluar sekaligus untuk produk pendamping sesuai aturan kemasan
     * (DB-driven via `packaging_rules`, per gudang):
     *  - `additional`: tiap `qty_per` unit barang inti → 1 unit additional ikut keluar
     *    (target pakai varian DEFAULT). Contoh: KMP → BOX (2:1).
     *  - `split`: barang inti dipecah — main keluar `ceil(qty/qty_per)`, target keluar
     *    `floor(qty/qty_per)` dengan varian target POWER SAMA (fallback power terkecil).
     *    Contoh promo "Beli 1 Dapat 2": KMP → KDF (2:1) → order qty 2 = 1 KMP + 1 KDF.
     *
     * Semua dicatat dalam satu transaksi; bila salah satu varian stoknya kurang,
     * RuntimeException dilempar dan seluruh jurnal dibatalkan (rollback).
     *
     * @throws \RuntimeException jika stok tidak mencukupi atau produk pendamping belum terdaftar
     */
    public function recordOutWithPackaging(
        int $variantId,
        string $date,
        int $quantity,
        string $reference = 'adjustment',
        ?int $referenceId = null,
        ?string $note = null,
        ?int $createdBy = null
    ): StockMovement {
        return DB::transaction(function () use ($variantId, $date, $quantity, $reference, $referenceId, $note, $createdBy) {
            $variant = ProductVariant::findOrFail($variantId);
            $baseCode = strtoupper(explode('+', (string) ($variant->product?->code ?? ''))[0]);
            $inventoryId = $variant->product?->primaryInventoryId();

            if ($baseCode === self::PACK_KDF_CODE) {
                throw new \RuntimeException('Produk '.self::PACK_KDF_CODE.' tidak dikirim sendiri.');
            }

            $rules = $this->packagingRulesFor($variant->product_id, $inventoryId);
            $splitRules = $rules->where('rule_type', PackagingRule::TYPE_SPLIT)->values();
            $additionalRules = $rules->where('rule_type', PackagingRule::TYPE_ADDITIONAL)->values();

            // Barang inti: bila ada aturan split, produk utama dipecah —
            // source = ceil(qty/qty_per), tiap target split = floor(qty/qty_per).
            $mainQty = $quantity;
            if ($splitRules->isNotEmpty()) {
                $mainQty = (int) ceil($quantity / max(1, (int) $splitRules->first()->qty_per));

                foreach ($splitRules as $rule) {
                    $splitQty = intdiv($quantity, max(1, (int) $rule->qty_per));
                    if ($splitQty <= 0) {
                        continue;
                    }

                    $targetVariant = $this->variantForPower((int) $rule->target_product_id, (float) $variant->power);
                    if ($targetVariant === null) {
                        throw new \RuntimeException('Produk '.$rule->targetProduct->code.' belum terdaftar.');
                    }
                    $this->recordOut($targetVariant->id, $date, $splitQty, $reference, $referenceId, $note, $createdBy, $inventoryId);
                }
            }

            if ($mainQty > 0) {
                $this->recordOut($variantId, $date, $mainQty, $reference, $referenceId, $note, $createdBy, $inventoryId);
            }

            // Barang additional mengikuti aturan kemasan dinamis dari DB (per gudang)
            foreach ($additionalRules as $rule) {
                $packQty = intdiv($quantity, max(1, (int) $rule->qty_per));
                if ($packQty <= 0) {
                    continue;
                }

                $targetVariant = $this->defaultVariantOf((int) $rule->target_product_id);
                if ($targetVariant === null) {
                    throw new \RuntimeException('Produk '.$rule->targetProduct->code.' belum terdaftar.');
                }
                $this->recordOut($targetVariant->id, $date, $packQty, $reference, $referenceId, $note, $createdBy, $inventoryId);
            }

            return StockMovement::where('reference', $reference)
                ->where('reference_id', $referenceId)
                ->where('type', 'out')
                ->where('product_variant_id', $variantId)
                ->firstOrFail();
        });
    }

    /**
     * Aturan kemasan aktif per produk inti + gudang, di-cache per instance (anti N+1).
     * Rule khusus gudang (inventory_id terisi) menimpa rule global (inventory_id null)
     * untuk kombinasi source→target yang sama.
     *
     * @return Collection<int, PackagingRule>
     */
    protected function packagingRulesFor(int $productId, ?int $inventoryId = null): Collection
    {
        if ($this->packagingRulesCache === null) {
            $this->packagingRulesCache = PackagingRule::with('targetProduct')
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($r) => $r->source_product_id.'|'.($r->inventory_id ?? ''));
        }

        $global = $this->packagingRulesCache->get($productId.'|', collect());
        $specific = $inventoryId !== null
            ? $this->packagingRulesCache->get($productId.'|'.$inventoryId, collect())
            : collect();

        return $specific->keyBy('target_product_id')
            ->union($global->keyBy('target_product_id'))
            ->values();
    }

    /**
     * Varian default aktif sebuah produk (fallback saat aturan kemasan tidak
     * menyebut ukuran/power), di-cache per instance.
     */
    protected function defaultVariantOf(int $productId): ?ProductVariant
    {
        if (! array_key_exists($productId, $this->defaultVariantCache)) {
            $this->defaultVariantCache[$productId] = ProductVariant::where('product_id', $productId)
                ->where('status', 'active')
                ->orderBy('power')
                ->orderBy('id')
                ->first();
        }

        return $this->defaultVariantCache[$productId];
    }

    /**
     * Varian aktif sebuah produk dengan power yang sama dengan varian sumber
     * (fallback: varian power terkecil) — dipakai aturan `split`.
     */
    protected function variantForPower(int $productId, float $power): ?ProductVariant
    {
        $variants = ProductVariant::where('product_id', $productId)
            ->where('status', 'active')
            ->orderBy('power')
            ->orderBy('id')
            ->get();

        if ($variants->isEmpty()) {
            return null;
        }

        return $variants->first(fn ($v) => abs((float) $v->power - $power) < 0.0001)
            ?? $variants->sortBy('power')->first();
    }

    /**
     * Hapus jurnal untuk reference tertentu lalu hitung ulang stok varian terkait.
     */
    public function reverseReference(string $reference, ?int $referenceId = null): void
    {
        $variantIds = StockMovement::where('reference', $reference)
            ->when($referenceId !== null, fn ($q) => $q->where('reference_id', $referenceId))
            ->pluck('product_variant_id')
            ->unique();

        StockMovement::where('reference', $reference)
            ->when($referenceId !== null, fn ($q) => $q->where('reference_id', $referenceId))
            ->delete();

        foreach ($variantIds as $id) {
            $this->recalculateStock($id);
            $this->syncVariantInventoryStocks($id);
        }
    }

    /**
     * Sinkronkan cache stok per (varian × gudang) dari jurnal
     * (tabel product_variant_inventory). Jurnal tetap sumber kebenaran.
     */
    public function syncVariantInventoryStocks(int $variantId): void
    {
        $rows = StockMovement::where('product_variant_id', $variantId)
            ->whereNotNull('inventory_id')
            ->selectRaw('inventory_id,
                         SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END) as masuk,
                         SUM(CASE WHEN type = "out" THEN quantity ELSE 0 END) as keluar')
            ->groupBy('inventory_id')
            ->get();

        foreach ($rows as $row) {
            ProductVariantInventory::updateOrCreate(
                ['product_variant_id' => $variantId, 'inventory_id' => $row->inventory_id],
                ['stock' => max(0, (int) $row->masuk - (int) $row->keluar)]
            );
        }
    }

    /**
     * Hitung ulang kolom stock varian dari kumpulan jurnal.
     */
    public function recalculateStock(int $variantId): void
    {
        $total = StockMovement::where('product_variant_id', $variantId)
            ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE 0 END) as masuk, SUM(CASE WHEN type='out' THEN quantity ELSE 0 END) as keluar")
            ->first();

        $stock = ($total->masuk ?? 0) - ($total->keluar ?? 0);

        ProductVariant::where('id', $variantId)->update(['stock' => max(0, $stock)]);
    }

    /**
     * Stok saat ini untuk varian (dari jurnal).
     * Bila `inventoryId` diberikan, stok dihitung khusus gudang tsb.
     */
    public function stockOf(int $variantId, ?int $inventoryId = null): int
    {
        $total = StockMovement::where('product_variant_id', $variantId)
            ->when($inventoryId !== null, fn ($q) => $q->where('inventory_id', $inventoryId))
            ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE 0 END) as masuk, SUM(CASE WHEN type='out' THEN quantity ELSE 0 END) as keluar")
            ->first();

        return (int) (($total->masuk ?? 0) - ($total->keluar ?? 0));
    }

    /**
     * Hitung HPP rata-rata tertimbang setelah barang masuk.
     */
    public function hppRataRata(Product $product, int $quantity, float $unitPrice, float $shippingCost = 0): float
    {
        $oldStock = $product->stok;
        $oldHpp = (float) $product->purchase_price;

        if ($oldStock + $quantity <= 0) {
            return round($unitPrice, 2);
        }

        $newHpp = (($oldStock * $oldHpp) + ($quantity * $unitPrice) + $shippingCost) / ($oldStock + $quantity);

        return round($newHpp, 2);
    }

    /**
     * Hitung ulang HPP produk dari rata-rata harga jurnal 'in' variannya (bobot qty).
     */
    public function recalculateHpp(int $productId): void
    {
        $rows = StockMovement::where('product_variant_id', $this->variantIdsOfProduct($productId))
            ->where('type', 'in')
            ->whereNotNull('unit_price')
            ->selectRaw('SUM(quantity * unit_price) as total_cost, SUM(quantity) as total_qty')
            ->first();

        $hpp = ($rows->total_qty ?? 0) > 0
            ? round(($rows->total_cost ?? 0) / $rows->total_qty, 2)
            : 0;

        Product::where('id', $productId)->update(['purchase_price' => $hpp]);
    }

    protected function variantIdsOfProduct(int $productId): array
    {
        return ProductVariant::where('product_id', $productId)->pluck('id')->all();
    }

    /**
     * Recalculate HPP + stok semua produk/varian dari jurnal (maintenance).
     */
    public function recalculateAll(): void
    {
        foreach (ProductVariant::pluck('id') as $variantId) {
            $this->recalculateStock($variantId);
            $this->syncVariantInventoryStocks($variantId);
        }

        foreach (Product::pluck('id') as $id) {
            $this->recalculateHpp($id);
        }
    }
}
