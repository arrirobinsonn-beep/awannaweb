<?php

namespace App\Http\Controllers;

use App\Services\OrderOnlineImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderOnlineController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $service = app(OrderOnlineImportService::class);
            $result = $service->parseExcel($request->file('file')->getPathname());

            $csNames = array_unique(array_filter(array_column($result['data'], 'cs_name')));

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $result['total'],
                    'errors' => $result['errors'],
                    'rows' => $result['data'],
                    'unique_cs' => array_values($csNames),
                    'unique_cs_count' => count($csNames),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $user = auth()->user();

        try {
            $service = app(OrderOnlineImportService::class);
            $result = $service->parseExcel($request->file('file')->getPathname());

            if (! empty($result['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terdapat ' . count($result['errors']) . ' error. Perbaiki dan upload ulang.',
                    'errors' => $result['errors'],
                ], 422);
            }

            if (empty($result['data'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data valid untuk diimport.',
                ], 422);
            }

            $service->resetByAdvertiser($user->id);

            $imported = $service->import($user->id, $result['data']);

            return response()->json([
                'success' => true,
                'imported' => $imported['inserted'],
                'message' => 'Berhasil import ' . $imported['inserted'] . ' kontak Order Online.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal import: ' . $e->getMessage(),
            ], 500);
        }
    }
}
