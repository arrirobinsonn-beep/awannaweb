<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GudangController extends Controller
{
    /** Master Gudang (tempat gudang) */
    public function gudangMaster(): View
    {
        $gudangs = Gudang::with('products')->orderBy('nama')->get();

        return view('gudang.master', compact('gudangs'));
    }

    public function gudangMasterStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['nama' => 'required|string|max:255|unique:gudangs,nama']);

        Gudang::create($data);

        return redirect()->route('gudang.master')->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function gudangMasterDestroy(Gudang $gudang): RedirectResponse
    {
        DB::transaction(function () use ($gudang) {
            if ($gudang->products()->exists()) {
                abort(422, 'Gudang masih terhubung ke produk, tidak dapat dihapus.');
            }
            $gudang->delete();
        });

        return redirect()->route('gudang.master')->with('success', 'Gudang berhasil dihapus.');
    }
}
