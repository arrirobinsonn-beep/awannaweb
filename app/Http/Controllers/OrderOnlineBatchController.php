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
        // Reverse stock movements for all orders in this batch before cascade delete
        $orderIds = $batch->shippingOrders()->pluck('id')->toArray();

        if (! empty($orderIds)) {
            $this->stock->reverseReferences('order_online', $orderIds);
        }

        $filename = $batch->original_filename;
        $batch->delete(); // cascade deletes shipping_orders

        return back()->with('success', "Batch \"{$filename}\" & semua order terkait berhasil dihapus.");
    }
}
