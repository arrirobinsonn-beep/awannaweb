<?php

namespace App\Http\Controllers;

use App\Models\CourierRule;
use App\Models\ExportTemplate;
use App\Models\Product;
use App\Services\CourierRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kelola aturan auto-mapping courier (tabel `courier_rules`) secara dinamis.
 *
 * Rules dievaluasi berurutan dari `sort_order` terkecil; rule pertama yang
 * cocok (payment_method + province) menang. Karena rules tersimpan di DB,
 * admin bisa mengubah mapping tanpa menyentuh kode maupun seeder.
 *
 * Konvensi nilai:
 *  - payment_method null = berlaku untuk semua metode bayar (disimpan lowercase)
 *  - province null        = berlaku untuk semua provinsi (disimpan uppercase)
 *  - sort_order           = prioritas evaluasi (kecil = menang duluan)
 */
class CourierRuleController extends Controller
{
    public function index(): View
    {
        $rules = CourierRule::orderBy('sort_order')->orderBy('id')->get();

        return view('courier_rule.index', [
            'rules' => $rules,
            'couriers' => $this->allCouriers(),
            'provinces' => config('regional.master_provinces', []),
            'productCodes' => Product::query()->pluck('code')->sort()->values(),
        ]);
    }

    public function filter()
    {
        $rules = CourierRule::orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'html' => view('courier_rule._table', [
                'rules' => $rules,
                'couriers' => $this->allCouriers(),
            ])->render(),
            'total' => $rules->count(),
            'nextOrder' => ($rules->max('sort_order') ?? 0) + 1,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->normalize($request->validate([
            'payment_method' => ['nullable', 'string', 'max:50'],
            'province' => ['nullable', 'string', 'max:191'],
            'product_code' => ['nullable', 'string', 'max:50'],
            'courier' => ['required', 'string', 'in:'.implode(',', $this->allCouriers())],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        if ($this->duplicateExists($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Kombinasi metode bayar + provinsi ini sudah punya aturan.',
            ], 422);
        }

        // Auto-set sort_order to bottom
        $maxOrder = CourierRule::max('sort_order') ?? 0;
        $data['sort_order'] = $maxOrder + 1;

        CourierRule::create($data);

        return response()->json(['success' => true, 'message' => 'Aturan courier berhasil ditambahkan.']);
    }

    public function update(Request $request, CourierRule $courierRule)
    {
        $data = $this->normalize($request->validate([
            'sort_order' => ['required', 'integer', 'min:1', 'max:999999'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'province' => ['nullable', 'string', 'max:191'],
            'product_code' => ['nullable', 'string', 'max:50'],
            'courier' => ['required', 'string', 'in:'.implode(',', $this->allCouriers())],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        if ($this->duplicateExists($data, $courierRule->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Kombinasi metode bayar + provinsi ini sudah punya aturan lain.',
            ], 422);
        }

        $courierRule->update($data);

        return response()->json(['success' => true, 'message' => 'Aturan courier berhasil diperbarui.']);
    }

    public function destroy(CourierRule $courierRule)
    {
        $courierRule->delete();

        return response()->json(['success' => true, 'message' => 'Aturan courier berhasil dihapus.']);
    }

    public function toggle(CourierRule $courierRule)
    {
        $courierRule->update(['is_active' => ! $courierRule->is_active]);

        return response()->json(['success' => true, 'is_active' => $courierRule->is_active]);
    }

    /** Naik/turunkan prioritas (sort_order) dengan menukar rule tetangga. */
    public function move(CourierRule $courierRule, string $direction)
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            abort(404);
        }

        $rules = CourierRule::orderBy('sort_order')->orderBy('id')->get();
        $index = $rules->search(fn ($r) => $r->id === $courierRule->id);
        $swap = $index === false
            ? null
            : ($direction === 'up' ? $rules->get($index - 1) : $rules->get($index + 1));

        if ($swap) {
            DB::transaction(function () use ($courierRule, $swap) {
                $tmp = $courierRule->sort_order;
                $courierRule->update(['sort_order' => $swap->sort_order]);
                $swap->update(['sort_order' => $tmp]);
            });
        }

        $rules = CourierRule::orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'html' => view('courier_rule._table', [
                'rules' => $rules,
                'couriers' => $this->allCouriers(),
            ])->render(),
            'total' => $rules->count(),
        ]);
    }

    private function normalize(array $data): array
    {
        $data['payment_method'] = ! empty($data['payment_method'])
            ? strtolower(trim($data['payment_method']))
            : null;
        $data['province'] = ! empty($data['province'])
            ? strtoupper(trim($data['province']))
            : null;
        $data['product_code'] = ! empty($data['product_code'])
            ? strtoupper(explode('+', trim($data['product_code']))[0])
            : null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function duplicateExists(array $data, ?int $ignoreId = null): bool
    {
        return CourierRule::where('payment_method', $data['payment_method'])
            ->where('province', $data['province'])
            ->where('product_code', $data['product_code'])
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Courier dinamis dari export_templates + undeliverable.
     */
    private function allCouriers(): array
    {
        $fromTemplates = ExportTemplate::where('is_active', true)
            ->get()
            ->flatMap(fn ($t) => $t->couriers ?? [])
            ->unique()
            ->values();
        $fromTemplates->push('undeliverable');

        return $fromTemplates->sort()->values()->all();
    }
}
