<?php

namespace App\Http\Controllers;

use App\Models\TopUpProposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        if ($user->hasRole(['super_admin', 'keuangan'])) {
            $advertisers = User::role('advertiser')->orderBy('nama')->get(['id', 'nama', 'panggilan', 'email', 'avatar']);
            $activeTab = $request->input('tab', 'all');

            $query = TopUpProposal::with('user', 'approver', 'items.whitelist');
            if ($activeTab !== 'all') {
                $query->where('user_id', $activeTab);
            }
            $topUpProposals = $query->latest()->paginate(15);

            // Batch summary per advertiser
            $summaryPerAdv = [];
            if ($advertisers->isNotEmpty()) {
                $batchSummary = TopUpProposal::whereIn('user_id', $advertisers->pluck('id'))
                    ->selectRaw("user_id,
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        COALESCE(SUM(total_nominal), 0) as total_nominal")
                    ->groupBy('user_id')
                    ->get()
                    ->keyBy('user_id');

                foreach ($advertisers as $adv) {
                    $s = $batchSummary->get($adv->id);
                    $summaryPerAdv[$adv->id] = [
                        'total' => $s ? (int) $s->total : 0,
                        'pending' => $s ? (int) $s->pending : 0,
                        'completed' => $s ? (int) $s->completed : 0,
                        'total_nominal' => $s ? (float) $s->total_nominal : 0,
                    ];
                }
            }
        } else {
            $advertisers = collect();
            $activeTab = 'all';
            $topUpProposals = TopUpProposal::with('approver', 'items.whitelist')
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(15);
            $summaryPerAdv = [];
        }

        return view('approval.index', compact(
            'topUpProposals', 'advertisers', 'activeTab', 'summaryPerAdv'
        ));
    }
}
