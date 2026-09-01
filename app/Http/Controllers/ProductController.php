<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Halaman master Produk — satu-satunya tempat membuat/mengubah produk & varian.
 * Stok TIDAK dikelola di sini (stok per gudang dikelola di halaman Gudang /
 * Barang Masuk). Saat produk dibuat, varian default otomatis dibuat agar stok
 * bisa dicatat; produk belum terdaftar di gudang mana pun sampai admin
 * meng-attach-nya dari halaman Gudang.
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with(['variants', 'inventories', 'primaryInventory'])->latest('id');

        $query->when($request->filled('search'), fn (Builder $q) => $q->where(function (Builder $w) use ($request) {
            $w->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('code', 'like', '%'.$request->search.'%')
                ->orWhere('category', 'like', '%'.$request->search.'%');
        }))
            ->when($request->filled('goods_type'), fn (Builder $q) => $q->where('goods_type', $request->goods_type))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status));

        $products = $query->paginate(15)->withQueryString();

        return view('product.index', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);

        $product = DB::transaction(function () use ($data) {
            $product = Product::create($data);

            // Varian default otomatis (kode = kode produk, power 0) agar stok
            // bisa langsung dicatat per gudang nantinya.
            ProductVariant::create([
                'product_id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'jenis' => null,
                'power' => 0,
                'stock' => 0,
                'status' => 'active',
            ]);

            return $product;
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Produk '.$product->name.' berhasil ditambahkan (varian default dibuat).']);
        }

        return redirect()->route('product.index')
            ->with('success', 'Produk '.$product->name.' berhasil ditambahkan (varian default dibuat).');
    }

    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $data = $this->validateProduct($request, $product);

        $product->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Produk '.$product->name.' berhasil diperbarui.']);
        }

        return redirect()->route('product.index')
            ->with('success', 'Produk '.$product->name.' berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->delete();

        return redirect()->route('product.index')
            ->with('success', 'Produk '.$name.' berhasil dihapus.');
    }

    public function toggleStatus(Product $product): JsonResponse
    {
        $product->update([
            'status' => $product->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json(['success' => true, 'status' => $product->status]);
    }

    public function toggleAdStatus(Product $product): JsonResponse
    {
        $newStatus = $product->ad_status === Product::AD_STATUS_TESTING
            ? Product::AD_STATUS_RUNNING
            : Product::AD_STATUS_TESTING;

        $product->update(['ad_status' => $newStatus]);

        return response()->json(['success' => true, 'ad_status' => $newStatus]);
    }

    // ─── Varian Produk ─────────────────────────────────────────────────────

    public function variantStore(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_variants,code'],
            'name' => ['required', 'string', 'max:150'],
            'jenis' => ['nullable', 'string', 'max:80'],
            'power' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        ProductVariant::create($data + ['product_id' => $product->id, 'stock' => 0]);

        return response()->json(['success' => true, 'message' => 'Varian produk berhasil ditambahkan.']);
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

        return response()->json(['success' => true, 'message' => 'Varian produk berhasil diperbarui.']);
    }

    public function variantDestroy(ProductVariant $variant): JsonResponse
    {
        $variant->delete();

        return response()->json(['success' => true, 'message' => 'Varian berhasil dihapus.']);
    }

    public function toggleVariantStatus(ProductVariant $variant): JsonResponse
    {
        $variant->update([
            'status' => $variant->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json(['success' => true, 'status' => $variant->status]);
    }

    // ─── Helper ────────────────────────────────────────────────────────────

    protected function validateProduct(Request $request, ?Product $product = null): array
    {
        $uniqueCode = $product
            ? 'unique:products,code,'.$product->id
            : 'unique:products';

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', $uniqueCode],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:80'],
            'goods_type' => ['required', 'in:'.implode(',', Product::GOODS_TYPES)],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
            'ad_status' => ['nullable', 'in:'.implode(',', Product::AD_STATUSES)],
        ]);

        $data['min_stock'] = (int) ($data['min_stock'] ?? 0);

        // Default ad_status: testing (produk baru belum melalui fase testing)
        if (! $product) {
            $data['ad_status'] = $data['ad_status'] ?? Product::AD_STATUS_TESTING;
        } else {
            // Saat edit: ad_status diambil dari input (bisa diubah admin)
            $data['ad_status'] = $data['ad_status'] ?? $product->ad_status;
        }

        return $data;
    }
}
