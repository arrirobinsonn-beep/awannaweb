<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly StockService $stock = new StockService,
    ) {}

    public function index(Request $request): View
    {
        // variants ikut di-eager load agar accessor stok induk (gabungan varian) tidak memicu N+1.
        $query = Product::with('variants', 'inventory')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%')
                    ->orWhere('category', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kategori')) {
            $query->where('category', $request->kategori);
        }

        $products = $query->paginate(10)->withQueryString();
        $kategoris = Product::distinct()->pluck('category')->filter()->sort()->values();

        // Data modal varian (hpp & url store per produk) — dihitung di sini agar @json di view
        // tidak perlu expression ber-koma (directive @json memecah argumen berdasarkan koma).
        $pvProducts = $products->map(fn ($p) => [
            'id' => $p->id,
            'hpp' => (float) $p->purchase_price,
            'store_url' => route('product.variant.store', $p),
        ])->keyBy('id');

        return view('product.index', compact('products', 'kategoris', 'pvProducts'));
    }

    public function create(): View
    {
        $inventories = Inventory::orderBy('name')->get();

        return view('product.form', ['product' => new Product, 'inventories' => $inventories, 'mode' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:products'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'inventory_id' => ['nullable', 'exists:inventories,id'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        Product::create($data);

        return redirect()->route('product.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        $inventories = Inventory::orderBy('name')->get();

        return view('product.form', ['product' => $product, 'inventories' => $inventories, 'mode' => 'edit']);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:products,code,'.$product->id],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'inventory_id' => ['nullable', 'exists:inventories,id'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
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

    // ─── Varian Produk (ukuran / power) ─────────────────────────

    public function variantStore(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_variants,code'],
            'name' => ['required', 'string', 'max:150'],
            'jenis' => ['nullable', 'string', 'max:80'],
            'power' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'stock_awal' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['product_id'] = $product->id;
        $data['stock'] = 0;

        $variant = ProductVariant::create($data);

        if (($data['stock_awal'] ?? 0) > 0) {
            $this->stock->recordIn(
                $variant->id,
                now()->format('Y-m-d'),
                (int) $data['stock_awal'],
                (float) $product->purchase_price ?: null,
                'adjustment',
                $variant->id,
                'Stok awal varian',
                auth()->id(),
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Varian produk berhasil ditambahkan.',
        ]);
    }

    public function variantUpdate(Request $request, ProductVariant $variant): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_variants,code,'.$variant->id],
            'name' => ['required', 'string', 'max:150'],
            'jenis' => ['nullable', 'string', 'max:80'],
            'power' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $variant->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Varian produk berhasil diperbarui.',
        ]);
    }

    public function variantDestroy(ProductVariant $variant): JsonResponse
    {
        $variant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Varian berhasil dihapus.',
        ]);
    }

    public function toggleStatus(Product $product): JsonResponse
    {
        $product->update([
            'status' => $product->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'success' => true,
            'status' => $product->status,
        ]);
    }

    public function toggleVariantStatus(ProductVariant $variant): JsonResponse
    {
        $variant->update([
            'status' => $variant->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'success' => true,
            'status' => $variant->status,
        ]);
    }
}
