<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $query = StockMovement::query()
            ->with(['variant.product', 'inventory', 'creator']);

        if ($request->filled('variant_id')) {
            $query->where('product_variant_id', $request->variant_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('bulan')) {
            $query->where('date', 'like', $request->bulan.'-%');
        }

        $movements = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        $products = Product::with('variants')->orderBy('name')->get();
        $monthList = StockMovement::selectRaw("DATE_FORMAT(date, '%Y-%m') as bulan")
            ->distinct()
            ->orderByDesc('bulan')
            ->pluck('bulan');

        return view('stock_movement.index', compact('movements', 'products', 'monthList'));
    }
}
