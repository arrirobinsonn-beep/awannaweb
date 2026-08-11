<?php

namespace App\Http\Controllers;

use App\Models\OrderOnlineImportBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingOrder;
use App\Models\StockMovement;
use App\Services\AggregatorTrackingImportService;
use App\Services\CourierRuleService;
use App\Services\OrderOnlineImportService;
use App\Services\OrderTemplateExportService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderOnlineController extends Controller
{
    public function __construct(
        private readonly OrderOnlineImportService $import,
        private readonly OrderTemplateExportService $export,
        private readonly CourierRuleService $couriers,
        private readonly StockService $stock,
        private readonly AggregatorTrackingImportService $tracking,
    ) {}

    public function index(Request $request): View
    {
        $batchId = $request->integer('batch');

        $batches = OrderOnlineImportBatch::query()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $orders = collect();
        $selectedBatch = null;

        if ($batchId) {
            $selectedBatch = OrderOnlineImportBatch::find($batchId);
            if ($selectedBatch) {
                $orders = ShippingOrder::where('order_online_import_batch_id', $selectedBatch->id)
                    ->when($request->filled('search'), fn ($q) => $q->where(function ($qq) use ($request) {
                        $qq->where('order_id', 'like', '%'.$request->search.'%')
                            ->orWhere('customer_name', 'like', '%'.$request->search.'%')
                            ->orWhere('phone', 'like', '%'.$request->search.'%');
                    }))
                    ->when($request->filled('courier'), fn ($q) => $q->where('courier', $request->courier))
                    ->when($request->filled('status'), fn ($q) => $q->where('status', 'like', '%'.$request->status.'%'))
                    ->orderByDesc('id')
                    ->paginate(25)
                    ->withQueryString();
            }
        }

        $courierList = ShippingOrder::select('courier')->distinct()->whereNotNull('courier')->pluck('courier');

        $products = Product::query()->orderBy('code')->with('variants')->get(['id', 'code', 'name']);

        $courierCounts = collect();
        if ($selectedBatch) {
            $courierCounts = ShippingOrder::where('order_online_import_batch_id', $selectedBatch->id)
                ->whereIn('status', ShippingOrder::EXPORTABLE_STATUSES)
                ->whereNotNull('courier')
                ->where(fn ($q) => $q->whereNull('awb')->orWhere('awb', ''))
                ->selectRaw('courier, COUNT(*) as total')
                ->groupBy('courier')
                ->pluck('total', 'courier');
        }

        return view('order.index', compact('batches', 'selectedBatch', 'orders', 'courierList', 'courierCounts', 'products'));
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/csv', 'max:10240'],
        ]);

        try {
            $result = $this->import->preview($request->file('file')->getPathname());

            return response()->json([
                'success' => true,
                'total' => $result['total'],
                'sampel' => $result['sample'],
                'errors' => $result['skips'],
                'unknown_cs' => $result['unknown_cs'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: '.$e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'sender' => ['required', 'string', 'max:191'],
            'file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/csv', 'max:10240'],
        ]);

        try {
            $path = $request->file('file')->store('order-online');
            $result = $this->import->import(Storage::path($path), $request->input('sender'));

            $message = "Import berhasil | Insert: {$result['inserted']} | Update: {$result['updated']}";

            if (($result['duplicates'] ?? 0) > 0) {
                $message .= ' | Duplikat: '.$result['duplicates'];
            }

            if (($result['deleted'] ?? 0) > 0) {
                $message .= ' | Baris belum diproses lama dihapus: '.$result['deleted'];
            }

            if (($result['double_real'] ?? 0) > 0) {
                $message .= ' | Real di-skip (sudah ada): '.$result['double_real'];
            }

            if (! empty($result['unknown_cs'])) {
                $message .= ' | CS Tak Dikenal: '.count($result['unknown_cs']).' ('.implode(', ', array_slice($result['unknown_cs'], 0, 5)).')';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'inserted' => $result['inserted'],
                'updated' => $result['updated'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal import: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, ShippingOrder $shippingOrder)
    {
        if (! empty($shippingOrder->awb)) {
            return back()->withErrors(['order' => 'Order sudah memiliki resi (AWB), tidak bisa diedit.']);
        }

        $request->validate([
            'courier' => ['nullable', 'in:'.implode(',', CourierRuleService::COURIERS)],
            'courier_note' => ['nullable', 'string', 'max:255'],
            'product_code' => ['nullable', 'string', 'max:191'],
        ]);

        $data = $request->only(['courier', 'courier_note']);

        $newProductCode = $request->filled('product_code') ? trim($request->input('product_code')) : null;
        $productChanged = $newProductCode !== null && $newProductCode !== $shippingOrder->product_code;

        if ($productChanged) {
            $hasReservation = StockMovement::where('reference', 'order_online')
                ->where('reference_id', $shippingOrder->id)
                ->where('type', 'out')
                ->exists();

            if ($hasReservation) {
                $this->stock->reverseReference('order_online', $shippingOrder->id);
            }

            $variant = ProductVariant::where('code', $newProductCode)->first();
            $product = $variant?->product;
            $data['product_code'] = $newProductCode;
            $data['product_id'] = $product?->id;
            $data['product_variant_id'] = $variant?->id;
            $data['product_name'] = ($product?->name ?? $shippingOrder->product_name)
                .($shippingOrder->quantity > 1 ? ' '.$shippingOrder->quantity.' pcs' : '');
            $data['stock_note'] = null;
        }

        $previousCourier = $shippingOrder->courier;
        $shippingOrder->update($data);

        if ($request->filled('courier') && $request->input('courier') === 'undeliverable' && $previousCourier !== 'undeliverable') {
            $this->stock->reverseReference('order_online', $shippingOrder->id);
        }

        return back()->with('success', 'Data courier berhasil diperbarui.');
    }

    public function trackingImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        try {
            $path = $request->file('file')->store('order-online/tracking');
            $result = $this->tracking->import(Storage::path($path));

            $message = 'Tracking import ('.($result['source'] ?? '-').') | Total: '.$result['total']
                .' | Terisi: '.$result['updated'];

            if (($result['stock_returned'] ?? 0) > 0) {
                $message .= ' | Stok dikembalikan: '.$result['stock_returned'];
            }
            if (! empty($result['ambiguous'])) {
                $message .= ' | Ambigu (tidak diisi): '.count($result['ambiguous']);
            }
            if (! empty($result['unmatched'])) {
                $message .= ' | Tak cocok: '.count($result['unmatched']);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'updated' => $result['updated'],
                'stock_returned' => $result['stock_returned'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal import tracking: '.$e->getMessage(),
            ], 500);
        }
    }

    public function export(OrderOnlineImportBatch $batch, string $template, ?string $courier = null): StreamedResponse
    {
        if (! in_array($template, OrderTemplateExportService::TEMPLATES)) {
            abort(404);
        }

        if ($template === OrderTemplateExportService::TEMPLATE_FLIK) {
            $courier = $courier ?: null;
            if (! in_array($courier, OrderTemplateExportService::FLIK_COURIERS)) {
                abort(404);
            }
        }

        return $this->export->download($batch, $template, $courier);
    }
}
