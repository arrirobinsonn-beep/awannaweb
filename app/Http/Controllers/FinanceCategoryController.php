<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Master kategori transaksi (mis. "Bank Transfer" in, "Biaya" out).
 * Hanya role keuangan/owner/super_admin/admin.
 */
class FinanceCategoryController extends Controller
{
    private function abortUnlessAllowed(): void
    {
        abort_unless(auth()->user()->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
    }

    public function index(): View
    {
        $this->abortUnlessAllowed();

        $categories = TransactionCategory::withCount('bankTransfers')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('finance.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:'.implode(',', TransactionCategory::TYPES)],
        ]);

        if ($this->duplicateExists($data)) {
            return back()->withErrors(['name' => 'Kategori dengan nama & tipe ini sudah ada.']);
        }

        TransactionCategory::create($data);

        return redirect()->route('finance.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, TransactionCategory $category): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:'.implode(',', TransactionCategory::TYPES)],
        ]);

        if ($this->duplicateExists($data, $category->id)) {
            return back()->withErrors(['name' => 'Kategori dengan nama & tipe ini sudah ada.']);
        }

        $category->update($data);

        return redirect()->route('finance.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(TransactionCategory $category): RedirectResponse
    {
        $this->abortUnlessAllowed();

        abort_if($category->bankTransfers()->exists(), 400, 'Kategori masih dipakai transaksi, tidak bisa dihapus.');

        $category->delete();

        return redirect()->route('finance.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    private function duplicateExists(array $data, ?int $ignoreId = null): bool
    {
        return TransactionCategory::where('name', $data['name'])
            ->where('type', $data['type'])
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
