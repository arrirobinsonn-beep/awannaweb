<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with('supplier')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_produk', 'like', '%'.$request->search.'%')
                    ->orWhere('kode_produk', 'like', '%'.$request->search.'%')
                    ->orWhere('kategori', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $products = $query->paginate(10)->withQueryString();
        $kategoris = Product::distinct()->pluck('kategori')->filter()->sort()->values();

        return view('product.index', compact('products', 'kategoris'));
    }

    public function create(): View
    {
        $suppliers = Supplier::aktif()->pluck('nama_supplier', 'id');

        return view('product.form', ['product' => new Product, 'suppliers' => $suppliers, 'mode' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode_produk' => ['required', 'string', 'max:20', 'unique:products'],
            'nama_produk' => ['required', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:80'],
            'deskripsi' => ['nullable', 'string'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'harga_beli' => ['required', 'numeric', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        Product::create($data);

        return redirect()->route('product.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): View
    {
        $product->load('supplier', 'whitelists', 'spendingHarians');

        return view('product.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $suppliers = Supplier::aktif()->pluck('nama_supplier', 'id');

        return view('product.form', ['product' => $product, 'suppliers' => $suppliers, 'mode' => 'edit']);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'kode_produk' => ['required', 'string', 'max:20', 'unique:products,kode_produk,'.$product->id],
            'nama_produk' => ['required', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:80'],
            'deskripsi' => ['nullable', 'string'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'harga_beli' => ['required', 'numeric', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        $product->update($data);

        return redirect()->route('product.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('product.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
