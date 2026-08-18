<?php

namespace App\Http\Controllers;

use App\Models\WarehouseRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola aturan dinamis kode produk → gudang (tabel `warehouse_rules`),
 * pengganti konstanta WAREHOUSE_BY_PRODUCT (SH→GTM, KSP→Aurora).
 *
 * Satu kode produk = satu rule. Rule aktif menang atas gudang utama produk
 * pada kolom "Kode Warehouse" saat export template aggregator.
 */
class WarehouseRuleController extends Controller
{
    public function index(): View
    {
        $rules = WarehouseRule::orderBy('product_code')->get();

        return view('warehouse_rule.index', [
            'rules' => $rules,
            'inventories' => \App\Models\Inventory::orderBy('name')->get(['id', 'name']),
            'productCodes' => \App\Models\Product::query()->pluck('code')->sort()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->normalize($request->validate([
            'product_code' => ['required', 'string', 'max:50'],
            'warehouse' => ['required', 'string', 'max:191'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        if (WarehouseRule::where('product_code', $data['product_code'])->exists()) {
            return back()->withErrors([
                'rule' => "Kode produk {$data['product_code']} sudah punya aturan. Ubah/hapus aturan lama dulu.",
            ]);
        }

        WarehouseRule::create($data);

        return redirect()->route('warehouse-rule.index')->with('success', 'Aturan gudang berhasil ditambahkan.');
    }

    public function update(Request $request, WarehouseRule $warehouseRule): RedirectResponse
    {
        $data = $this->normalize($request->validate([
            'product_code' => ['required', 'string', 'max:50'],
            'warehouse' => ['required', 'string', 'max:191'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        if (WarehouseRule::where('product_code', $data['product_code'])
            ->where('id', '!=', $warehouseRule->id)
            ->exists()) {
            return back()->withErrors([
                'rule' => "Kode produk {$data['product_code']} sudah punya aturan lain. Ubah/hapus aturan lama dulu.",
            ]);
        }

        $warehouseRule->update($data);

        return redirect()->route('warehouse-rule.index')->with('success', 'Aturan gudang berhasil diperbarui.');
    }

    public function destroy(WarehouseRule $warehouseRule): RedirectResponse
    {
        $warehouseRule->delete();

        return redirect()->route('warehouse-rule.index')->with('success', 'Aturan gudang berhasil dihapus.');
    }

    public function toggle(WarehouseRule $warehouseRule): RedirectResponse
    {
        $warehouseRule->update(['is_active' => ! $warehouseRule->is_active]);

        return back()->with('success', $warehouseRule->is_active
            ? 'Aturan gudang diaktifkan.'
            : 'Aturan gudang dinonaktifkan.');
    }

    private function normalize(array $data): array
    {
        $data['product_code'] = strtoupper(explode('+', trim($data['product_code']))[0]);
        $data['warehouse'] = trim($data['warehouse']);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
