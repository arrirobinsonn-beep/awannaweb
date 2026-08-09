<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /** Produk kacamata yang memicu pengurangan BOX/LAP otomatis saat pengiriman. */
    public const KACAMATA_CODES = ['KMP', 'KSP', 'KBJ'];

    /** Kode produk pendamping kemasan (BOX/LAP) & pendamping KBJ (KDF). */
    public const PACK_BOX_CODE = 'BOX';

    public const PACK_LAP_CODE = 'LAP';

    public const PACK_KDF_CODE = 'KDF';

    /** Setiap berapa qty kacamata sebuah BOX + LAP ikut berkurang (floor). */
    public const PACK_QTY_PER = 2;

    private ?array $packagingCache = null;

    /**
     * Catat barang masuk ke jurnal + tambah stok varian.
     */
    public function recordIn(
        int $variantId,
        string $date,
        int $quantity,
        ?float $unitPrice = null,
        string $reference = 'adjustment',
        ?int $referenceId = null,
        ?string $note = null,
        ?int $createdBy = null
    ): StockMovement {
        return DB::transaction(function () use ($variantId, $date, $quantity, $unitPrice, $reference, $referenceId, $note, $createdBy) {
            $movement = StockMovement::updateOrCreate(
                ['reference' => $reference, 'reference_id' => $referenceId, 'type' => 'in', 'product_variant_id' => $variantId],
                [
                    'product_variant_id' => $variantId,
                    'date' => $date,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'note' => $note,
                    'created_by' => $createdBy,
                ]
            );

            $this->recalculateStock($variantId);

            return $movement;
        });
    }

    /**
     * Catat barang keluar ke jurnal + kurangi stok varian.
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
        ?int $createdBy = null
    ): StockMovement {
        return DB::transaction(function () use ($variantId, $date, $quantity, $reference, $referenceId, $note, $createdBy) {
            $variant = ProductVariant::findOrFail($variantId);
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
            }

            $movement = StockMovement::updateOrCreate(
                ['reference' => $reference, 'reference_id' => $referenceId, 'type' => 'out', 'product_variant_id' => $variantId],
                [
                    'product_variant_id' => $variantId,
                    'date' => $date,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'note' => $note,
                    'created_by' => $createdBy,
                ]
            );

            $this->recalculateStock($variantId);

            return $movement;
        });
    }

    /**
     * Catat barang keluar sekaligus untuk produk pendamping sesuai aturan kemasan:
     *  - KMP/KSP: stok produk berkurang `qty`, plus BOX + LAP sebesar floor(qty/2).
     *  - KBJ: produk berkurang ceil(qty/2) + KDF floor(qty/2), plus BOX + LAP floor(qty/2).
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

            if (! in_array($baseCode, self::KACAMATA_CODES)) {
                return $this->recordOut($variantId, $date, $quantity, $reference, $referenceId, $note, $createdBy);
            }

            $packaging = $this->packagingVariants();
            $packQty = intdiv($quantity, self::PACK_QTY_PER);

            if ($baseCode === self::PACK_KDF_CODE) {
                throw new \RuntimeException('Produk '.self::PACK_KDF_CODE.' tidak dikirim sendiri.');
            }

            if ($baseCode === 'KBJ') {
                $mainQty = (int) ceil($quantity / 2);
                $kdfQty = intdiv($quantity, 2);

                if ($kdfQty > 0) {
                    $kdfVariant = $this->kdfVariantFor($variant, $packaging);
                    if ($kdfVariant === null) {
                        throw new \RuntimeException('Produk '.self::PACK_KDF_CODE.' belum terdaftar, tidak bisa mengirim KBJ.');
                    }
                    $this->recordOut($kdfVariant->id, $date, $kdfQty, $reference, $referenceId, $note, $createdBy);
                }
            } else {
                $mainQty = $quantity;
            }

            if ($mainQty > 0) {
                $this->recordOut($variantId, $date, $mainQty, $reference, $referenceId, $note, $createdBy);
            }

            if ($packQty > 0) {
                if ($packaging['box'] === null) {
                    throw new \RuntimeException('Produk '.self::PACK_BOX_CODE.' belum terdaftar.');
                }
                $this->recordOut($packaging['box']->id, $date, $packQty, $reference, $referenceId, $note, $createdBy);

                if ($packaging['lap'] === null) {
                    throw new \RuntimeException('Produk '.self::PACK_LAP_CODE.' belum terdaftar.');
                }
                $this->recordOut($packaging['lap']->id, $date, $packQty, $reference, $referenceId, $note, $createdBy);
            }

            return StockMovement::where('reference', $reference)
                ->where('reference_id', $referenceId)
                ->where('type', 'out')
                ->where('product_variant_id', $variantId)
                ->firstOrFail();
        });
    }

    /**
     * Varian pendukung kemasan (BOX, LAP) & seluruh varian KDF, di-cache per instance.
     *
     * @return array{box:?ProductVariant,lap:?ProductVariant,kdf:Illuminate\Support\Collection}
     */
    protected function packagingVariants(): array
    {
        if ($this->packagingCache !== null) {
            return $this->packagingCache;
        }

        $defaultVariant = function (string $code): ?ProductVariant {
            return ProductVariant::where('status', 'active')
                ->whereHas('product', fn ($q) => $q->where('code', $code))
                ->orderBy('power')
                ->orderBy('id')
                ->first();
        };

        return $this->packagingCache = [
            'box' => $defaultVariant(self::PACK_BOX_CODE),
            'lap' => $defaultVariant(self::PACK_LAP_CODE),
            'kdf' => ProductVariant::where('status', 'active')
                ->whereHas('product', fn ($q) => $q->where('code', self::PACK_KDF_CODE))
                ->get(),
        ];
    }

    /**
     * Varian KDF dengan power yang sama dengan varian KBJ (fallback: power terkecil).
     */
    protected function kdfVariantFor(ProductVariant $kacamataVariant, array $packaging): ?ProductVariant
    {
        if ($packaging['kdf']->isEmpty()) {
            return null;
        }

        $power = (float) $kacamataVariant->power;

        return $packaging['kdf']
            ->first(fn ($v) => abs((float) $v->power - $power) < 0.0001)
            ?? $packaging['kdf']->sortBy('power')->first();
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
     */
    public function stockOf(int $variantId): int
    {
        $total = StockMovement::where('product_variant_id', $variantId)
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
        }

        foreach (Product::pluck('id') as $id) {
            $this->recalculateHpp($id);
        }
    }
}
