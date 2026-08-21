<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\PackagingRule;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductVariant;
use App\Models\ProductVariantInventory;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GudangController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Halaman Gudang — 1 GUDANG = 1 HALAMAN.
     * User memilih gudang (query `inventory_id`); halaman menampilkan isi gudang itu saja:
     *  - Barang Pasti (consumable): semua produk (ada di tiap gudang), stok per gudang ini
     *  - Barang Inti (core): produk yang gudang induknya = gudang ini (mis. SH → GTM)
     *  - Barang Additional: produk additional yang gudang induknya = gudang ini
     *  - Aturan Kemasan: rule global + rule khusus gudang ini
     */
    public function index(Request $request): View
    {
        $inventories = Inventory::orderBy('name')->get();
        $inventory = $request->filled('inventory_id') ? Inventory::find($request->integer('inventory_id')) : null;

        if ($inventory === null) {
            return view('gudang.index', [
                'groups' => collect(),
                'rules' => collect(),
                'inventories' => $inventories,
                'inventory' => null,
                'perVariant' => collect(),
            ]);
        }

        // Produk di gudang ini = produk yang TERDAFTAR di gudang tsb
        // (many-to-many via product_inventory). Barang Pasti di-seed ada di
        // semua gudang; inti/additional hanya di gudang tempatnya terdaftar.
        $scoped = fn ($query) => $query->whereHas('inventories', fn ($q) => $q->where('inventories.id', $inventory->id));

        $with = fn ($query) => $query->with(['variants', 'inventories']);

        $consumables = Product::aktif()->tap($with)
            ->where('goods_type', Product::GOODS_CONSUMABLE)
            ->tap($scoped)
            ->orderBy('code')
            ->get();

        $cores = Product::aktif()->tap($with)
            ->where('goods_type', Product::GOODS_CORE)
            ->tap($scoped)
            ->orderBy('code')
            ->get();

        $additionals = Product::aktif()->tap($with)
            ->where('goods_type', Product::GOODS_ADDITIONAL)
            ->tap($scoped)
            ->orderBy('code')
            ->get();

        $rules = PackagingRule::with(['sourceProduct', 'targetProduct', 'inventory'])
            ->where(fn ($q) => $q->whereNull('inventory_id')->orWhere('inventory_id', $inventory->id))
            ->orderBy('inventory_id')
            ->orderBy('source_product_id')
            ->orderBy('id')
            ->get();

        // Produk master yang BELUM terdaftar di gudang ini — untuk modal
        // "Tambah Produk ke Gudang" (produk dibuat di halaman Produk, bukan di sini).
        $attachedIds = ProductInventory::where('inventory_id', $inventory->id)->pluck('product_id');
        $availableProducts = Product::aktif()->with('variants')
            ->whereNotIn('id', $attachedIds)
            ->orderBy('code')
            ->get();

        return view('gudang.index', [
            'groups' => ['consumable' => $consumables, 'core' => $cores, 'additional' => $additionals],
            'rules' => $rules,
            'inventories' => $inventories,
            'inventory' => $inventory,
            'perVariant' => $this->perInventoryStockByVariant($inventory->id),
            'availableProducts' => $availableProducts,
        ]);
    }

    /**
     * Stok per gudang per varian: variantId → [inventoryId => stock].
     * Membaca cache `product_variant_inventory` (di-sync StockService dari jurnal)
     * — 1 query ringan, anti N+1. Bisa difilter satu gudang.
     *
     * @return Collection<int, array<int, int>>
     */
    protected function perInventoryStockByVariant(?int $inventoryId = null): Collection
    {
        return ProductVariantInventory::whereNotNull('inventory_id')
            ->when($inventoryId !== null, fn ($q) => $q->where('inventory_id', $inventoryId))
            ->get()
            ->groupBy('product_variant_id')
            ->map(fn ($rows) => $rows->mapWithKeys(
                fn ($r) => [(int) $r->inventory_id => (int) $r->stock]
            )->all());
    }

    /**
     * Penyesuaian stok manual (Barang Pasti) PER GUDANG:
     * tambah/kurang via jurnal reference 'manual' + inventory_id.
     */
    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'inventory_id' => ['nullable', 'exists:inventories,id'],
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $variant = ProductVariant::with('product')->findOrFail($data['product_variant_id']);
        $quantity = (int) $data['quantity'];
        $note = trim((string) ($data['note'] ?? '')) ?: 'Penyesuaian manual (halaman Gudang)';
        $inventoryId = ! empty($data['inventory_id']) ? (int) $data['inventory_id'] : null;
        $inventory = $inventoryId !== null ? Inventory::find($inventoryId) : null;

        try {
            if ($data['direction'] === 'in') {
                $this->stock->recordIn(
                    $variant->id,
                    now()->format('Y-m-d'),
                    $quantity,
                    null,
                    'manual',
                    random_int(100000000, 9999999999),
                    $note,
                    auth()->id(),
                    $inventoryId,
                );
            } else {
                $this->stock->recordOut(
                    $variant->id,
                    now()->format('Y-m-d'),
                    $quantity,
                    'manual',
                    random_int(100000000, 9999999999),
                    $note,
                    auth()->id(),
                    $inventoryId,
                );
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['adjust' => $e->getMessage()])->withInput();
        }

        $label = $inventory ? ' ('.$inventory->name.')' : '';
        $productName = $variant->product?->name ?? 'varian';

        return back()->with('success', 'Stok '.$productName.$label.' diperbarui.');
    }

    /** Tambah aturan kemasan baru (opsional per gudang; kosong = semua gudang). */
    public function packagingStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source_product_id' => ['required', 'exists:products,id'],
            'target_product_id' => ['required', 'exists:products,id', 'different:source_product_id'],
            'inventory_id' => ['nullable', 'exists:inventories,id'],
            'qty_per' => ['required', 'integer', 'min:1'],
            'rule_type' => ['nullable', 'in:'.implode(',', PackagingRule::TYPES)],
        ]);

        $data['inventory_id'] = ! empty($data['inventory_id']) ? (int) $data['inventory_id'] : null;
        $data['rule_type'] = $data['rule_type'] ?? PackagingRule::TYPE_ADDITIONAL;

        $exists = PackagingRule::where('source_product_id', $data['source_product_id'])
            ->where('target_product_id', $data['target_product_id'])
            ->where('inventory_id', $data['inventory_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['rule' => 'Aturan untuk kombinasi produk + gudang ini sudah ada.'])->withInput();
        }

        PackagingRule::create($data + ['is_active' => true]);

        return back()->with('success', 'Aturan kemasan ditambahkan.');
    }

    /** Ubah rasio (qty_per), jenis aturan & status aktif aturan kemasan. */
    public function packagingUpdate(Request $request, PackagingRule $packagingRule): RedirectResponse
    {
        $data = $request->validate([
            'qty_per' => ['required', 'integer', 'min:1'],
            'rule_type' => ['nullable', 'in:'.implode(',', PackagingRule::TYPES)],
            'is_active' => ['nullable'],
        ]);

        $packagingRule->update([
            'qty_per' => (int) $data['qty_per'],
            'rule_type' => $data['rule_type'] ?? $packagingRule->rule_type,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Aturan kemasan diperbarui.');
    }

    public function packagingDestroy(PackagingRule $packagingRule): RedirectResponse
    {
        $packagingRule->delete();

        return back()->with('success', 'Aturan kemasan dihapus.');
    }

    // ─── Produk di gudang (attach produk MASTER yang sudah ada) ────────────

    /**
     * Attach produk yang SUDAH ADA di halaman Produk ke gudang ini — produk &
     * variannya TIDAK dibuat di sini (master data terpusat di halaman Produk).
     * Bila produk belum punya gudang sama sekali, gudang ini otomatis jadi
     * gudang UTAMA. `stock_awal` opsional: stok awal varian default di gudang ini.
     */
    public function productAttach(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'inventory_id' => ['required', 'exists:inventories,id'],
            'is_primary' => ['nullable', 'boolean'],
            'stock_awal' => ['nullable', 'integer', 'min:0'],
        ]);

        $inventoryId = (int) $data['inventory_id'];
        $product = Product::findOrFail($data['product_id']);

        if (ProductInventory::where('product_id', $product->id)->where('inventory_id', $inventoryId)->exists()) {
            return back()->withErrors(['attach' => 'Produk '.$product->name.' sudah terdaftar di gudang ini.']);
        }

        // Gudang utama HANYA untuk Barang Inti (core) — Barang Pasti/Additional
        // tidak pernah jadi gudang utama (is_primary selalu false).
        $isPrimary = $product->goods_type === Product::GOODS_CORE
            && ($request->boolean('is_primary') || ProductInventory::where('product_id', $product->id)->doesntExist());

        try {
            DB::transaction(function () use ($product, $inventoryId, $isPrimary, $data, $request) {
                if ($isPrimary) {
                    ProductInventory::where('product_id', $product->id)->update(['is_primary' => false]);
                }

                ProductInventory::create([
                    'product_id' => $product->id,
                    'inventory_id' => $inventoryId,
                    'is_primary' => $isPrimary,
                ]);

                $stockAwal = (int) ($data['stock_awal'] ?? 0);
                if ($stockAwal > 0) {
                    $variant = $product->defaultVariant();
                    if ($variant === null) {
                        throw new \RuntimeException('Produk '.$product->name.' belum punya varian aktif.');
                    }
                    $this->stock->recordIn(
                        $variant->id,
                        now()->format('Y-m-d'),
                        $stockAwal,
                        $product->purchase_price ? (float) $product->purchase_price : null,
                        'adjustment',
                        random_int(100000000, 9999999999),
                        'Stok awal (attach ke gudang)',
                        auth()->id(),
                        $inventoryId,
                    );
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['attach' => $e->getMessage()])->withInput();
        }

        return redirect()->route('gudang.index', ['inventory_id' => $inventoryId])
            ->with('success', 'Produk '.$product->name.' ditambahkan ke gudang ini'.($isPrimary ? ' (gudang utama)' : '').'.');
    }

    /**
     * Kelola gudang produk (many-to-many): centang gudang tempat produk terdaftar
     * + pilih gudang UTAMA (is_primary). Gudang yang dicopot dihapus keanggotaannya;
     * stok cache per gudang tsb ikut dihapus (jurnal tetap tersimpan).
     */
    public function productWarehousesUpdate(Request $request, Product $product): RedirectResponse
    {
        $rules = [
            'inventory_ids' => ['required', 'array', 'min:1'],
            'inventory_ids.*' => ['integer', 'exists:inventories,id'],
        ];

        // Radio gudang utama hanya ada untuk Barang Inti (core).
        $isCore = $product->goods_type === Product::GOODS_CORE;
        if ($isCore) {
            $rules['primary_inventory_id'] = ['required', 'integer', 'exists:inventories,id'];
        }

        $wh = $request->validate($rules);

        $ids = array_values(array_unique(array_map('intval', $wh['inventory_ids'])));
        $primary = $isCore ? (int) $wh['primary_inventory_id'] : null;

        if ($isCore && ! in_array($primary, $ids, true)) {
            return back()->withErrors(['warehouse' => 'Gudang utama harus dipilih di daftar gudang produk.']);
        }

        DB::transaction(function () use ($product, $ids, $primary) {
            ProductInventory::where('product_id', $product->id)
                ->whereNotIn('inventory_id', $ids)
                ->delete();

            foreach ($ids as $inventoryId) {
                ProductInventory::updateOrCreate(
                    ['product_id' => $product->id, 'inventory_id' => $inventoryId],
                    ['is_primary' => $primary !== null && $inventoryId === $primary]
                );
            }

            ProductVariantInventory::whereIn('product_variant_id', $product->variants()->pluck('id'))
                ->whereNotIn('inventory_id', $ids)
                ->delete();
        });

        return redirect()->route('gudang.index', ['inventory_id' => $primary ?? $ids[0]])
            ->with('success', 'Gudang produk '.$product->name.' diperbarui.');
    }

    /**
     * Lepas produk dari gudang ini (BUKAN menghapus produk dari sistem — produk
     * & variannya tetap ada di halaman Produk). Keanggotaan & stok cache gudang
     * tsb dihapus; jurnal stok tetap tersimpan.
     */
    public function productDetach(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['inventory_id' => ['required', 'exists:inventories,id']]);
        $inventoryId = (int) $data['inventory_id'];

        DB::transaction(function () use ($product, $inventoryId) {
            ProductInventory::where('product_id', $product->id)
                ->where('inventory_id', $inventoryId)
                ->delete();

            ProductVariantInventory::whereIn('product_variant_id', $product->variants()->pluck('id'))
                ->where('inventory_id', $inventoryId)
                ->delete();
        });

        return redirect()->route('gudang.index', ['inventory_id' => $inventoryId])
            ->with('success', 'Produk '.$product->name.' dilepas dari gudang ini.');
    }
}
