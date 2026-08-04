<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        $query = Purchase::query()
            ->with(['product', 'supplier', 'creator']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('bulan')) {
            $query->where('date', 'like', $request->bulan.'-%');
        }

        $purchases = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        $products = Product::aktif()->orderBy('nama_produk')->get();
        $suppliers = Supplier::orderBy('nama_supplier')->get();
        $monthList = Purchase::selectRaw("DATE_FORMAT(date, '%Y-%m') as bulan")
            ->distinct()
            ->orderByDesc('bulan')
            ->pluck('bulan');

        return view('purchase.index', compact('purchases', 'products', 'suppliers', 'monthList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $data['quantity'] = (int) $data['quantity'];
        $data['unit_price'] = (float) $data['unit_price'];
        $data['shipping_cost'] = (float) ($data['shipping_cost'] ?? 0);
        $data['created_by'] = auth()->id();

        try {
            $purchase = Purchase::create($data);

            $product = Product::findOrFail($purchase->product_id);
            $hpp = $this->stock->hppRataRata($product, $purchase->quantity, $purchase->unit_price, $purchase->shipping_cost);

            $product->update(['harga_beli' => $hpp]);

            $this->stock->recordIn(
                $product->id,
                $purchase->date->format('Y-m-d'),
                $purchase->quantity,
                $purchase->unit_price,
                'purchase',
                $purchase->id,
                'Pembelian '.($purchase->supplier?->nama ?? '-'),
                auth()->id(),
            );

            return redirect()->route('purchase.index')
                ->with('success', 'Barang masuk berhasil disimpan. Stok & HPP diperbarui.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $purchase = Purchase::findOrFail($id);
        $productId = $purchase->product_id;

        $this->stock->reverseReference('purchase', $purchase->id);
        $purchase->delete();
        $this->stock->recalculateHpp($productId);

        return redirect()->route('purchase.index')
            ->with('success', 'Pembelian dihapus. Stok & HPP dikembalikan.');
    }
}
