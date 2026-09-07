<?php

namespace App\Services;

use App\Models\BonusCalculation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BonusCalculationService
{
    private const MARGIN_FLOOR = 85000;
    private const ADJUSTMENT_RATE = 0.075; // 7.5%
    private const PAID_THRESHOLD = 500;

    /**
     * Hitung bonus secara real-time.
     * - spending: dari spending_harians (ad spend dari Meta Ads)
     * - lead & paid: dari regional_cs_stats (data per daerah, sumber yang sama dgn alokasi bonus)
     */
    public function calculateRealtime(string $period): Collection
    {
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $advertisers = User::where('role', 'advertiser')->get(['id', 'nama', 'panggilan']);

        // Spending dari spending_harians (untuk CPA)
        $spendingTotals = DB::table('spending_harians')
            ->whereBetween('tanggal', [$start, $end])
            ->selectRaw('user_id, COALESCE(SUM(spending), 0) as spending')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // Lead & paid dari regional_cs_stats (sumber yang sama dgn alokasi bonus)
        $csTotals = DB::table('regional_cs_stats')
            ->whereBetween('tanggal', [$start, $end])
            ->selectRaw('user_id,
                COALESCE(SUM(`lead`), 0) as `lead`,
                COALESCE(SUM(paid), 0) as paid')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // Status disbursed yang sudah ada
        $disbursed = BonusCalculation::where('period', $period)
            ->where('status', 'disbursed')
            ->pluck('disbursed_at', 'user_id');

        $results = collect();

        foreach ($advertisers as $adv) {
            $spendingRow = $spendingTotals->get($adv->id);
            $csRow = $csTotals->get($adv->id);
            $spending = (float) ($spendingRow->spending ?? 0);
            $lead = (int) ($csRow->lead ?? 0);
            $paid = (int) ($csRow->paid ?? 0);

            $paidRatio = $lead > 0 ? $paid / $lead : 0;
            $adjustment = $paidRatio * self::ADJUSTMENT_RATE;
            $cpaPaid = $paid > 0 ? $spending / $paid : 0;
            $margin = max(0, self::MARGIN_FLOOR - $cpaPaid);
            $pengali = $margin * $adjustment;
            $potensiBonus = $paid > self::PAID_THRESHOLD ? $paid * $pengali : 0;

            $results[] = (object) [
                'user_id' => $adv->id,
                'user' => $adv,
                'spending' => $spending,
                'lead' => $lead,
                'paid' => $paid,
                'paid_ratio' => $paidRatio,
                'adjustment' => $adjustment,
                'cpa_paid' => $cpaPaid,
                'margin' => $margin,
                'pengali' => $pengali,
                'potensi_bonus' => $potensiBonus,
                'disbursed' => $disbursed->has($adv->id),
                'disbursed_at' => $disbursed->get($adv->id),
            ];
        }

        return $results;
    }

    /**
     * Tandai bonus sudah ditransfer.
     */
    public function disburse(string $period, int $userId): void
    {
        BonusCalculation::updateOrCreate(
            ['period' => $period, 'user_id' => $userId],
            ['status' => 'disbursed', 'disbursed_at' => now()]
        );
    }
}
