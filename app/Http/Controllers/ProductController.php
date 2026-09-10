<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
        $consumableProducts = $this->getFilteredProducts($request, 'consumable', 15);
        $coreProducts = $this->getFilteredProducts($request, 'core', 15);

        return view('product.index', compact('consumableProducts', 'coreProducts'))
            ->with('search', $request->input('search', ''))
            ->with('goodsType', $request->input('goods_type', ''))
            ->with('status', $request->input('status', ''))
            ->with('adStatus', $request->input('ad_status', ''));
    }

    public function filter(Request $request)
    {
        $goodsTypeFilter = $request->input('goods_type', '');

        $consumableHtml = '';
        $coreHtml = '';
        $consumableTotal = 0;
        $coreTotal = 0;
        $consumablePagination = '';
        $corePagination = '';

        if ($goodsTypeFilter === '' || $goodsTypeFilter === 'consumable') {
            $consumableProducts = $this->getFilteredProducts($request, 'consumable', 15);
            $consumableHtml = view('product._table', [
                'products' => $consumableProducts,
                'showAdColumn' => false,
                'tableId' => 'consumable-tbody',
                'emptyMessage' => 'Tidak ada produk pasti ditemukan',
            ])->render();
            $consumableTotal = $consumableProducts->total();
            $consumablePagination = $consumableProducts->links()->render();
        }

        if ($goodsTypeFilter === '' || $goodsTypeFilter !== 'consumable') {
            $coreProducts = $this->getFilteredProducts($request, 'core', 15);
            $coreHtml = view('product._table', [
                'products' => $coreProducts,
                'showAdColumn' => true,
                'tableId' => 'core-tbody',
                'emptyMessage' => 'Tidak ada produk inti ditemukan',
            ])->render();
            $coreTotal = $coreProducts->total();
            $corePagination = $coreProducts->links()->render();
        }

        return response()->json([
            'consumable_html' => $consumableHtml,
            'core_html' => $coreHtml,
            'consumable_total' => $consumableTotal,
            'core_total' => $coreTotal,
            'consumable_pagination' => $consumablePagination,
            'core_pagination' => $corePagination,
        ]);
    }

    private function getFilteredProducts(Request $request, string $goodsType, int $perPage = 15)
    {
        $query = Product::with(['variants', 'inventories', 'primaryInventory'])
            ->where('goods_type', $goodsType)
            ->latest('id');

        $query->when($request->filled('search'), fn (Builder $q) => $q->where(function (Builder $w) use ($request) {
            $w->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('code', 'like', '%'.$request->search.'%')
                ->orWhere('category', 'like', '%'.$request->search.'%');
        }))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->when($request->filled('ad_status') && $goodsType !== 'consumable', fn (Builder $q) => $q->where('ad_status', $request->ad_status));

        return $query->paginate($perPage)->withQueryString();
    }

    public function store(Request $request)
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

        return response()->json(['success' => true, 'message' => 'Produk '.$product->name.' berhasil ditambahkan (varian default dibuat).']);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product);

        $product->update($data);

        return response()->json(['success' => true, 'message' => 'Produk '.$product->name.' berhasil diperbarui.']);
    }

    public function destroy(Product $product)
    {
        $name = $product->name;
        $product->delete();

        return response()->json(['success' => true, 'message' => 'Produk '.$name.' berhasil dihapus.']);
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
        // Lifecycle SATU ARAH: testing → running (tidak bisa balik ke testing).
        // Saat running, start_running otomatis terisi (hari ini / tanggal yang
        // sudah di-set manual admin) → spending sebelum tanggal itu tetap Testing.
        if ($product->isRunning()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk yang sudah Running tidak bisa dikembalikan ke Testing.',
            ], 422);
        }

        $product->update([
            'ad_status' => Product::AD_STATUS_RUNNING,
            'start_running' => $product->start_running?->toDateString() ?? now()->toDateString(),
        ]);

        return response()->json(['success' => true, 'ad_status' => Product::AD_STATUS_RUNNING]);
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
            'start_testing' => ['nullable', 'date'],
            'start_running' => ['nullable', 'date'],
        ]);

        $data['min_stock'] = (int) ($data['min_stock'] ?? 0);

        // ── Barang Pasti (consumable) tidak punya status iklan ──
        if ($data['goods_type'] === 'consumable') {
            unset($data['ad_status'], $data['start_testing'], $data['start_running']);

            return $data;
        }

        // Default ad_status: testing (produk baru belum melalui fase testing)
        if (! $product) {
            $data['ad_status'] = $data['ad_status'] ?? Product::AD_STATUS_TESTING;
        } else {
            // Saat edit: ad_status diambil dari input (bisa diubah admin)
            $data['ad_status'] = $data['ad_status'] ?? $product->ad_status;
        }

        // ── Fase iklan berbasis tanggal ──────────────────────────
        // start_testing: otomatis tanggal hari ini saat produk dibuat
        // (bisa diedit manual di form). start_running: diisi saat toggle /
        // pilih running; kalau tidak dikirim, pertahankan nilai existing.
        $data['start_testing'] = $data['start_testing']
            ?? $product?->start_testing?->toDateString()
            ?? now()->toDateString();
        $data['start_running'] = $data['start_running']
            ?? $product?->start_running?->toDateString();
        if ($data['ad_status'] === Product::AD_STATUS_RUNNING && empty($data['start_running'])) {
            $data['start_running'] = now()->toDateString();
        }

        return $data;
    }
}
