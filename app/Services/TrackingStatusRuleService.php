<?php

namespace App\Services;

use App\Models\TrackingStatusRule;

/**
 * Resolver dinamis raw status dashboard aggregator → aggregator_status,
 * pengganti map hardcoded di AggregatorTrackingImportService::mapStatus.
 *
 * Evaluasi (per sumber):
 *   1. Ambil semua rule aktif, urut dari `sort_order` terkecil (cache per request).
 *   2. Rule dengan problem_mode = 'required' HANYA cocok bila kolom masalah
 *      terpenuhi (problem_keyword null → kolom tidak kosong; terisi → mengandung
 *      keyword, case-insensitive). Bila tidak terpenuhi, rule dilewati.
 *   3. Rule pertama yang cocok (match_type exact/contains pada raw_status
 *      yang di-lowercase) menang → statusnya dikembalikan.
 *   4. Tidak ada yang cocok → null (raw status tak dikenal).
 */
class TrackingStatusRuleService
{
    /** @var array<string, array<int, TrackingStatusRule>>|null cache per request: source → rules */
    private ?array $cache = null;

    /**
     * Kembalikan aggregator_status untuk raw status sebuah sumber, atau null.
     */
    public function resolve(string $source, string $rawStatus, ?string $problemColumn = null): ?string
    {
        $raw = strtolower(trim($rawStatus));
        if ($raw === '') {
            return null;
        }

        foreach ($this->rules($source) as $rule) {
            if ($rule->problem_mode === 'required' && ! $this->problemColumnMatches($problemColumn, $rule->problem_keyword, $rule->problem_match_type ?? 'contains')) {
                continue;
            }

            if ($rule->match_type === 'contains') {
                if ($raw !== '' && str_contains($raw, $rule->raw_status)) {
                    return $rule->status;
                }
            } elseif ($raw === $rule->raw_status) {
                return $rule->status;
            }
        }

        return null;
    }

    /**
     * Cek apakah kolom masalah terpenuhi untuk sebuah rule.
     *
     * @param  string  $matchType  contains (mengandung keyword) / starts_with (diawali keyword).
     */
    protected function problemColumnMatches(?string $problemColumn, ?string $keyword, string $matchType = 'contains'): bool
    {
        $column = trim((string) $problemColumn);
        if ($column === '') {
            return false;
        }
        if ($keyword === null || trim($keyword) === '') {
            return true; // cukup tidak kosong (SPX: Delivery OnHold Reason berisi)
        }

        if ($matchType === 'starts_with') {
            return str_starts_with(mb_strtolower($column), mb_strtolower($keyword));
        }

        return stripos($column, $keyword) !== false;
    }

    /**
     * Index rule aktif by source (anti N+1, cache per instance).
     *
     * @return Collection<int, TrackingStatusRule>
     */
    protected function rules(string $source): \Illuminate\Support\Collection
    {
        if ($this->cache === null) {
            $this->cache = TrackingStatusRule::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('source')
                ->all();
        }

        return $this->cache[$source] ?? collect();
    }
}
