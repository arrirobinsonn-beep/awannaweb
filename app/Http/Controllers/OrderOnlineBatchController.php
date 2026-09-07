<?php

namespace App\Http\Controllers;

use App\Models\OrderOnlineImportBatch;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderOnlineBatchController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function index(Request $request): View
    {
        $batches = OrderOnlineImportBatch::query()
            ->withCount('shippingOrders')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('order.batch-index', compact('batches'));
    }

    public function destroy(OrderOnlineImportBatch $batch)
    {
        $filename = $batch->original_filename;
        $orderCount = $batch->shippingOrders()->count();

        // 1) Reverse stock movements for all orders in this batch
        $orderIds = $batch->shippingOrders()->pluck('id')->toArray();

        if (! empty($orderIds)) {
            $this->stock->reverseReferences('order_online', $orderIds);
        }

        // 2) Explicitly delete shipping_orders (don't rely on cascade)
        \Illuminate\Support\Facades\DB::table('shipping_orders')
            ->where('order_online_import_batch_id', $batch->id)
            ->delete();

        // 3) Delete the batch itself
        \Illuminate\Support\Facades\DB::table('order_online_import_batches')
            ->where('id', $batch->id)
            ->delete();

        return back()->with('success', "Batch \"{$filename}\" & {$orderCount} order terkait berhasil dihapus.");
    }
}
