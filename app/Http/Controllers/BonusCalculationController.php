<?php

namespace App\Http\Controllers;

use App\Services\BonusCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BonusCalculationController extends Controller
{
    public function __construct(
        private BonusCalculationService $bonusService
    ) {}

    public function index(Request $request): View
    {
        $period = $request->input('period', now()->format('Y-m'));

        $bonuses = $this->bonusService->calculateRealtime($period)
            ->sortByDesc('potensi_bonus')
            ->values();

        $totals = [
            'spending' => $bonuses->sum('spending'),
            'lead' => $bonuses->sum('lead'),
            'paid' => $bonuses->sum('paid'),
            'potensi_bonus' => $bonuses->sum('potensi_bonus'),
        ];

        return view('bonus.index', compact('bonuses', 'totals', 'period'));
    }
}
