<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = $this->getFilteredSuppliers($request, 10);

        return view('supplier.index', compact('suppliers'));
    }

    public function filter(Request $request)
    {
        $suppliers = $this->getFilteredSuppliers($request, 10);

        return response()->json([
            'html' => view('supplier._table', compact('suppliers'))->render(),
            'pagination' => $suppliers->links()->render(),
            'total' => $suppliers->total(),
        ]);
    }

    private function getFilteredSuppliers(Request $request, int $perPage)
    {
        $query = Supplier::latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_supplier', 'like', '%'.$request->search.'%')
                    ->orWhere('kode_supplier', 'like', '%'.$request->search.'%')
                    ->orWhere('kota', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(): View
    {
        return view('supplier.form', ['supplier' => new Supplier, 'mode' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode_supplier' => ['required', 'string', 'max:20', 'unique:suppliers'],
            'nama_supplier' => ['required', 'string', 'max:150'],
            'pic_nama' => ['nullable', 'string', 'max:100'],
            'pic_telepon' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'catatan' => ['nullable', 'string'],
        ]);

        Supplier::create($data);

        return redirect()->route('supplier.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): View
    {
        return view('supplier.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('supplier.form', ['supplier' => $supplier, 'mode' => 'edit']);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'kode_supplier' => ['required', 'string', 'max:20', 'unique:suppliers,kode_supplier,'.$supplier->id],
            'nama_supplier' => ['required', 'string', 'max:150'],
            'pic_nama' => ['nullable', 'string', 'max:100'],
            'pic_telepon' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'catatan' => ['nullable', 'string'],
        ]);

        $supplier->update($data);

        return redirect()->route('supplier.index')
            ->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('supplier.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }
}
