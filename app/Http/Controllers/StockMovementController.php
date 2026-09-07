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
        $dari = $request->input('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = $request->input('sampai', now()->format('Y-m-d'));

        // Ambil semua produk beserta variannya
        $products = Product::with('variants')->orderBy('name')->get();

        // Ambil pergerakan stok dalam rentang tanggal
        $movements = StockMovement::with(['creator'])
            ->whereBetween('date', [$dari, $sampai])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $movementsByVariant = $movements->groupBy('product_variant_id');

        // Bentuk data untuk View: per produk -> per varian -> list pergerakan
        $productStats = [];

        foreach ($products as $prod) {
            $prodIn = 0;
            $prodOut = 0;
            $prodStock = 0;
            $variantsData = [];

            foreach ($prod->variants as $var) {
                $varMovements = $movementsByVariant->get($var->id, collect());

                $varIn = $varMovements->where('type', 'in')->sum('quantity');
                $varOut = $varMovements->where('type', 'out')->sum('quantity');

                $prodIn += $varIn;
                $prodOut += $varOut;
                $prodStock += $var->stock;

                $variantsData[] = (object)[
                    'variant' => $var,
                    'in' => $varIn,
                    'out' => $varOut,
                    'stock' => $var->stock,
                    'movements' => $varMovements
                ];
            }

            $productStats[] = (object)[
                'product' => $prod,
                'in' => $prodIn,
                'out' => $prodOut,
                'stock' => $prodStock,
                'minStock' => $prod->min_stock ?? 0,
                'variantsData' => $variantsData
            ];
        }

        // --- Data untuk Diagram Garis ---
        $chartData = collect();
        $movementsByDate = $movements->groupBy(fn($m) => $m->date->format('Y-m-d'));
        
        $period = \Carbon\CarbonPeriod::create($dari, $sampai);
        foreach ($period as $dateObj) {
            $dateStr = $dateObj->format('Y-m-d');
            $dayMovements = $movementsByDate->get($dateStr, collect());
            
            $chartData->push([
                'date' => $dateObj->format('d M'),
                'in' => $dayMovements->where('type', 'in')->sum('quantity'),
                'out' => $dayMovements->where('type', 'out')->sum('quantity'),
            ]);
        }

        return view('stock_movement.index', compact('productStats', 'dari', 'sampai', 'chartData'));
    }
}
