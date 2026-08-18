<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\ShipmentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentImportService $import) {}

    public function index(Request $request): View
    {
        $query = Shipment::query();

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('status')) {
            $query->where('status', 'like', '%'.$request->status.'%');
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('tracking_number', 'like', '%'.$request->search.'%')
                    ->orWhere('recipient_name', 'like', '%'.$request->search.'%')
                    ->orWhere('phone', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->filled('bulan')) {
            $query->where('created_date', 'like', $request->bulan.'-%');
        }

        $shipments = $query->with('statusHistories')->orderByDesc('created_date')->paginate(25)->withQueryString();
        $sourceList = Shipment::select('source')->distinct()->pluck('source');
        $statusList = Shipment::select('status')->distinct()->whereNotNull('status')->pluck('status');
        $monthList = Shipment::selectRaw("DATE_FORMAT(created_date, '%Y-%m') as bulan")
            ->whereNotNull('created_date')
            ->distinct()
            ->orderByDesc('bulan')
            ->pluck('bulan');

        return view('shipment.index', compact('shipments', 'sourceList', 'statusList', 'monthList'));
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/csv', 'max:10240'],
        ]);

        try {
            $result = $this->import->parse($request->file('file')->getPathname());
            $match = $this->import->matchReport($result['data']);

            return response()->json([
                'success' => true,
                'source' => $result['data']->first()['source'] ?? null,
                'total' => $result['total'],
                'sampel' => $result['data']->take(5)->values(),
                'errors' => $result['skips'],
                'matched' => $match['matched'],
                'unmatched' => $match['unmatched'],
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
            'file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/csv', 'max:10240'],
        ]);

        try {
            $result = $this->import->import($request->file('file')->getPathname());

            $message = 'Sumber: '.strtoupper($result['source'] ?? '-')
                ." | Insert: {$result['inserted']} | Update: {$result['updated']} | Tanpa Perubahan: {$result['unchanged']}";

            if (! empty($result['unmatched'])) {
                $message .= ' | Tidak Cocok: '.count($result['unmatched']).' (tidak disimpan)';
            }

            return response()->json([
                'success' => true,
                'total' => $result['inserted'] + $result['updated'],
                'message' => $message,
                'inserted' => $result['inserted'],
                'updated' => $result['updated'],
                'unchanged' => $result['unchanged'],
                'matched' => $result['matched'],
                'unmatched' => $result['unmatched'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal import: '.$e->getMessage(),
            ], 500);
        }
    }
}
