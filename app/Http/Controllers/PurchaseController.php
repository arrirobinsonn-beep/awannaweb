<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    // ─── Index ─────────────────────────────────────────────────

    public function index(Request $request): View
    {
        return view('purchase.index', $this->viewData($request));
    }

    // ─── AJAX Filter ───────────────────────────────────────────

    public function filter(Request $request): JsonResponse
    {
        $data = $this->viewData($request);

        return response()->json([
            'html' => view('purchase._table', [
                'purchases' => $data['purchases'],
            ])->render(),
            'total' => $data['purchases']->total(),
        ]);
    }

    // ─── Store (new purchase, status = in_transit) ──────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date'                => ['required', 'date'],
            'supplier_id'         => ['nullable', 'exists:suppliers,id'],
            'product_variant_id'  => ['required', 'exists:product_variants,id'],
            'inventory_id'        => ['required', 'exists:inventories,id'],
            'quantity'            => ['required', 'integer', 'min:1'],
            'unit_price'          => ['required', 'numeric', 'min:0'],
            'shipping_cost'       => ['nullable', 'numeric', 'min:0'],
            'note'                => ['nullable', 'string', 'max:255'],
        ]);

        $data['quantity']      = (int) $data['quantity'];
        $data['unit_price']    = (float) $data['unit_price'];
        $data['shipping_cost'] = (float) ($data['shipping_cost'] ?? 0);
        $data['created_by']    = auth()->id();
        $data['status']        = Purchase::STATUS_IN_TRANSIT;

        Purchase::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Pembelian berhasil ditambahkan (belum masuk).',
        ]);
    }

    // ─── Receive (in_transit → received) ────────────────────────

    public function receive(Request $request, Purchase $purchase): JsonResponse
    {
        abort_unless($purchase->isInTransit(), 400, 'Barang sudah diterima.');

        $data = $request->validate([
            'received_qty'  => ['nullable', 'integer', 'min:1'],
            'received_note' => ['nullable', 'string', 'max:500'],
        ]);

        $qty = (int) ($data['received_qty'] ?? $purchase->quantity);

        $note = $data['received_note'] ?? null;

        DB::transaction(function () use ($purchase, $qty, $note) {
            $purchase->update([
                'status'        => Purchase::STATUS_RECEIVED,
                'received_qty'  => $qty,
                'received_note' => $note,
                'received_at'   => now(),
                'received_by'   => auth()->id(),
            ]);

            // Record stock + update HPP
            $variant  = $purchase->variant;
            $product  = $variant->product;
            $hpp = $this->stock->hppRataRata($product, $qty, (float) $purchase->unit_price, (float) $purchase->shipping_cost);
            $product->update(['purchase_price' => $hpp]);

            $this->stock->recordIn(
                $variant->id,
                $purchase->date->format('Y-m-d'),
                $qty,
                (float) $purchase->unit_price,
                'purchase',
                $purchase->id,
                'Barang diterima: '.($purchase->supplier?->nama_supplier ?? '-').
                    ' → '.($purchase->inventory?->name ?? '-').
                    ($note ? ' — '.$note : ''),
                auth()->id(),
                (int) $purchase->inventory_id,
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Barang diterima. Qty: '.$qty.', stok sudah masuk.',
        ]);
    }

    // ─── Delete ────────────────────────────────────────────────

    public function destroy(Purchase $purchase): JsonResponse
    {
        if ($purchase->isReceived()) {
            $productId = $purchase->variant?->product_id;
            $this->stock->reverseReference('purchase', $purchase->id);
            $purchase->delete();
            if ($productId) {
                $this->stock->recalculateHpp($productId);
            }
            return response()->json(['success' => true, 'message' => 'Pembelian dihapus. Stok & HPP dikembalikan.']);
        }

        $purchase->delete();

        return response()->json(['success' => true, 'message' => 'Pembelian dihapus.']);
    }

    // ─── Helpers ───────────────────────────────────────────────

    private function viewData(Request $request): array
    {
        $query = Purchase::query()
            ->with(['variant.product', 'supplier', 'creator', 'inventory']);

        if ($request->filled('variant_id')) {
            $query->where('product_variant_id', $request->variant_id);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('inventory_id')) {
            $query->where('inventory_id', $request->inventory_id);
        }
        if ($request->filled('bulan')) {
            $query->where('date', 'like', $request->bulan.'-%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        $products   = Product::aktif()->with('variants')->orderBy('name')->get();
        $suppliers  = Supplier::orderBy('nama_supplier')->get();
        $inventories = Inventory::orderBy('name')->get();
        $monthList  = Purchase::selectRaw("DATE_FORMAT(date, '%Y-%m') as bulan")
            ->distinct()
            ->orderByDesc('bulan')
            ->pluck('bulan');

        return compact('purchases', 'products', 'suppliers', 'inventories', 'monthList');
    }
}
