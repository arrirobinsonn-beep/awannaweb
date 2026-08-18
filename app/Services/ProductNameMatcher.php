<?php

namespace App\Services;

use App\Models\Product;

/**
 * Mencocokkan nama produk dari CSV (shipment) dengan tabel products.
 *
 * Strategi berurutan:
 *  1. Exact (setelah normalisasi)
 *  2. Contains (salah satu substring dari yang lain)
 *  3. Levenshtein ≤ 2 (typo/perbedaan karakter kecil)
 *
 * Normalisasi membuang spasi berlebih, tanda baca, dan huruf besar/kecil agar
 * kebiasaan CSV (spasi hilang, typo kecil, beda karakter) tetap dianggap identik.
 */
class ProductNameMatcher
{
    /**
     * @return array<int, Product> produk yang sudah dinormalisasi (key: nama normal)
     */
    public function buildIndex(): array
    {
        $index = [];

        foreach (Product::query()->get(['id', 'name']) as $product) {
            $normal = $this->normalize($product->name);
            if ($normal !== '') {
                $index[$normal] = $product;
            }
        }

        return $index;
    }

    /**
     * Cari product yang paling cocok dengan nama CSV.
     *
     * @param  array<int, Product>  $index  hasil buildIndex()
     */
    public function match(string $rawName, array $index): ?Product
    {
        $normal = $this->normalize($rawName);
        if ($normal === '') {
            return null;
        }

        // 1. Exact
        if (isset($index[$normal])) {
            return $index[$normal];
        }

        // 2. Contains — cocokkan dengan setiap produk terindeks
        $best = null;
        $bestScore = 0;

        foreach ($index as $normalProduct => $product) {
            if (str_contains($normal, $normalProduct) || str_contains($normalProduct, $normal)) {
                $score = max(strlen($normal), strlen($normalProduct));

                if ($score > $bestScore) {
                    $best = $product;
                    $bestScore = $score;
                }
            }
        }

        if ($best !== null) {
            return $best;
        }

        // 3. Levenshtein ≤ 2
        foreach ($index as $normalProduct => $product) {
            if (abs(strlen($normal) - strlen($normalProduct)) > 2) {
                continue;
            }

            $distance = $this->levenshtein($normal, $normalProduct);
            if ($distance <= 2) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Normalisasi nama: lowercase, trim, buang spasi berlebih & tanda baca.
     */
    public function normalize(string $name): string
    {
        $name = mb_strtolower(trim($name));

        // Simbol "&" = "dan" — normalisasi sebelum buang tanda baca
        // agar "Kacamata & Aksesoris" cocok dengan "Kacamata dan Aksesoris"
        $name = str_replace('&', 'dan', $name);

        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    /**
     * Levenshtein distance sederhana (tanpa multibyte) untuk kata pendek.
     */
    protected function levenshtein(string $a, string $b): int
    {
        return levenshtein($a, $b);
    }
}
