<?php

namespace App\Services;

use App\Models\CourierRule;
use App\Models\ShippingOrder;

/**
 * Menentukan courier untuk sebuah order berdasarkan tabel `courier_rules`.
 *
 * Hierarki (rule pertama yang cocok menang):
 *  - payment_method = bank_transfer  → courier `flix-tf`
 *  - payment_method = cod            → cocokkan provinsi:
 *      flix-idx     (Sumatera tertentu)
 *      sicepat      (seluruh Jawa + Bali)
 *      flix-spx     (pulau lainnya)
 *      spx          (fallback bila tidak ada rule yang cocok)
 *
 * `flix-sicepat` TIDAK lagi dipilih otomatis (rule Jawa+Bali kini langsung
 * `sicepat`, template SiCepat); `flix-sicepat` tetap valid sebagai override
 * manual bila SiCepat bermasalah.
 *
 * Admin dapat meng-override manual per order via `ShippingOrder::update()`
 * (mis. set `courier = 'undeliverable'` bila paket tidak dapat terkirim).
 */
class CourierRuleService
{
    public const COURIERS = [
        'flix-tf',
        'flix-idx',
        'flix-sicepat',
        'flix-spx',
        'spx',
        'sicepat',
        'undeliverable',
    ];

    public const FALLBACK_COURIER = 'spx';

    /** @var array<int, CourierRule>|null cache per request */
    private ?array $cache = null;

    /**
     * Evaluasi rules dan kembalikan courier untuk sebuah order.
     * Jika payment_method tidak dikenali, fallback ke spx.
     */
    public function resolve(?string $paymentMethod, ?string $province): string
    {
        $paymentMethod = strtolower(trim((string) $paymentMethod));
        $province = strtoupper(trim((string) $province));

        foreach ($this->rules() as $rule) {
            // payment_method rule = null artinya berlaku untuk semua
            if ($rule->payment_method !== null
                && strtolower($rule->payment_method) !== $paymentMethod) {
                continue;
            }

            // province rule = null artinya berlaku untuk semua
            if ($rule->province !== null
                && strtoupper($rule->province) !== $province) {
                continue;
            }

            return $rule->courier;
        }

        return self::FALLBACK_COURIER;
    }

    /**
     * Kembalikan rules aktif, urut dari prioritas tertinggi.
     *
     * @return array<int, CourierRule>
     */
    public function rules(): array
    {
        if ($this->cache === null) {
            $this->cache = CourierRule::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->all();
        }

        return $this->cache;
    }

    /**
     * Courier default untuk template FLIK (semua courier berawalan flix-*).
     */
    public function isFlikCourier(?string $courier): bool
    {
        return is_string($courier) && str_starts_with($courier, 'flix-');
    }

    /**
     * Apakah order tidak dapat dikirim (label khusus).
     */
    public function isUndeliverable(?string $courier): bool
    {
        return $courier === 'undeliverable';
    }
}
