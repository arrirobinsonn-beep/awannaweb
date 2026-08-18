<?php

namespace App\Http\Controllers;

use App\Models\ShippingOrder;
use App\Models\TrackingStatusRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kelola aturan dinamis raw status dashboard aggregator → aggregator_status
 * (tabel `tracking_status_rules`), pengganti map hardcoded di service import.
 *
 * Rules dievaluasi per sumber berurutan dari `sort_order` terkecil; rule
 * pertama yang cocok menang. Rule dengan problem_mode='required' hanya cocok
 * bila kolom masalah file terpenuhi (FLIK 3PL / SPX OnHold reason).
 *
 * Konvensi nilai:
 *  - raw_status disimpan lowercase (dicocokkan ke status file yang di-lowercase)
 *  - problem_keyword null/'' = kolom masalah cukup tidak kosong
 *  - status hanya boleh salah satu dari ShippingOrder::TRACKING_STATUSES
 */
class TrackingStatusRuleController extends Controller
{
    public function index(): View
    {
        $rules = TrackingStatusRule::orderBy('source')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('tracking_status_rule.index', [
            'rules' => $rules,
            'sources' => TrackingStatusRule::SOURCES,
            'matchTypes' => TrackingStatusRule::MATCH_TYPES,
            'problemModes' => TrackingStatusRule::PROBLEM_MODES,
            'statuses' => ShippingOrder::TRACKING_STATUSES,
            'nextOrder' => ($rules->max('sort_order') ?? 0) + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->normalize($request->validate($this->rules()));

        if ($this->duplicateExists($data)) {
            return back()->withErrors([
                'rule' => 'Aturan dengan kombinasi sumber + raw status + jenis ini sudah ada. Ubah/hapus aturan lama dulu.',
            ]);
        }

        TrackingStatusRule::create($data);

        return redirect()->route('tracking-status-rule.index')->with('success', 'Aturan status berhasil ditambahkan.');
    }

    public function update(Request $request, TrackingStatusRule $trackingStatusRule): RedirectResponse
    {
        $data = $this->normalize($request->validate($this->rules()));

        if ($this->duplicateExists($data, $trackingStatusRule->id)) {
            return back()->withErrors([
                'rule' => 'Aturan dengan kombinasi sumber + raw status + jenis ini sudah ada. Ubah/hapus aturan lama dulu.',
            ]);
        }

        $trackingStatusRule->update($data);

        return redirect()->route('tracking-status-rule.index')->with('success', 'Aturan status berhasil diperbarui.');
    }

    public function destroy(TrackingStatusRule $trackingStatusRule): RedirectResponse
    {
        $trackingStatusRule->delete();

        return redirect()->route('tracking-status-rule.index')->with('success', 'Aturan status berhasil dihapus.');
    }

    public function toggle(TrackingStatusRule $trackingStatusRule): RedirectResponse
    {
        $trackingStatusRule->update(['is_active' => ! $trackingStatusRule->is_active]);

        return back()->with('success', $trackingStatusRule->is_active
            ? 'Aturan status diaktifkan.'
            : 'Aturan status dinonaktifkan.');
    }

    /** Naik/turunkan prioritas (sort_order) dengan menukar rule tetangga. */
    public function move(TrackingStatusRule $trackingStatusRule, string $direction): RedirectResponse
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            abort(404);
        }

        $rules = TrackingStatusRule::orderBy('source')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $index = $rules->search(fn ($r) => $r->id === $trackingStatusRule->id);
        $swap = $index === false
            ? null
            : ($direction === 'up' ? $rules->get($index - 1) : $rules->get($index + 1));

        if ($swap) {
            DB::transaction(function () use ($trackingStatusRule, $swap) {
                $tmp = $trackingStatusRule->sort_order;
                $trackingStatusRule->update(['sort_order' => $swap->sort_order]);
                $swap->update(['sort_order' => $tmp]);
            });
        }

        return back();
    }

    private function rules(): array
    {
        return [
            'source' => ['required', 'string', 'in:'.implode(',', TrackingStatusRule::SOURCES)],
            'raw_status' => ['required', 'string', 'max:191'],
            'match_type' => ['required', 'string', 'in:'.implode(',', TrackingStatusRule::MATCH_TYPES)],
            'status' => ['required', 'string', 'in:'.implode(',', ShippingOrder::TRACKING_STATUSES)],
            'problem_mode' => ['required', 'string', 'in:'.implode(',', TrackingStatusRule::PROBLEM_MODES)],
            'problem_keyword' => ['nullable', 'string', 'max:191'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function normalize(array $data): array
    {
        $data['source'] = strtolower(trim($data['source']));
        $data['raw_status'] = strtolower(trim($data['raw_status']));
        $data['match_type'] = strtolower(trim($data['match_type']));
        $data['status'] = trim($data['status']);
        $data['problem_mode'] = strtolower(trim($data['problem_mode']));
        $data['problem_keyword'] = ! empty($data['problem_keyword'])
            ? trim($data['problem_keyword'])
            : null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function duplicateExists(array $data, ?int $ignoreId = null): bool
    {
        return TrackingStatusRule::where('source', $data['source'])
            ->where('raw_status', $data['raw_status'])
            ->where('match_type', $data['match_type'])
            ->where('problem_mode', $data['problem_mode'])
            ->where('status', $data['status'])
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
