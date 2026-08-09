<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /** Master Inventory (gudang) */
    public function master(): View
    {
        $inventories = Inventory::with('products')->orderBy('name')->get();

        return view('inventory.master', compact('inventories'));
    }

    public function masterStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:inventories,name']);

        Inventory::create($data);

        return redirect()->route('inventory.master')->with('success', 'Inventory berhasil ditambahkan.');
    }

    public function masterDestroy(Inventory $inventory): RedirectResponse
    {
        DB::transaction(function () use ($inventory) {
            if ($inventory->products()->exists()) {
                abort(422, 'Inventory masih terhubung ke produk, tidak dapat dihapus.');
            }
            $inventory->delete();
        });

        return redirect()->route('inventory.master')->with('success', 'Inventory berhasil dihapus.');
    }
}
