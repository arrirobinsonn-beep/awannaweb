<?php

namespace App\Services;

use App\Models\WarehouseRule;

/**
 * Menentukan kode gudang (nama pengirim) untuk sebuah produk berdasarkan
 * tabel `warehouse_rules` — pengganti dinamis konstanta WAREHOUSE_BY_PRODUCT.
 *
 * - product_code di-normalisasi (uppercase, buang sufiks varian `+1.50`)
 * - rule pertama yang cocok (product_code sama) menang
 * - tidak ada rule cocok → null (pemanggil jatuh ke gudang utama produk / sender)
 */
class WarehouseRuleService
{
    /** @var array<string, string>|null cache per request: product_code → warehouse */
    private ?array $cache = null;

    /**
     * Kembalikan nama gudang untuk sebuah kode produk, atau null bila tidak ada rule.
     */
    public function resolve(?string $productCode): ?string
    {
        $code = $this->normalize($productCode);
        if ($code === '') {
            return null;
        }

        return $this->rules()[$code] ?? null;
    }

    /**
     * Index rule aktif by product_code (anti N+1, cache per instance).
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        if ($this->cache === null) {
            $this->cache = WarehouseRule::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn ($r) => [$this->normalize($r->product_code) => $r->warehouse])
                ->all();
        }

        return $this->cache;
    }

    protected function normalize(?string $productCode): string
    {
        $code = strtoupper(trim((string) $productCode));
        if ($code === '') {
            return '';
        }

        return explode('+', $code)[0];
    }
}
