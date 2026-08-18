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

<<<<<<< HEAD
    public function pembelianStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'sumber_produk' => 'nullable|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'qty' => 'required|integer|min:0',
            'harga_satuan' => 'nullable|numeric|min:0',
            'total_belanja' => 'nullable|numeric|min:0',
            'ongkir' => 'nullable|numeric|min:0',
            'keterangan' => 'required|in:MASUK STOK,BARU PESAN',
        ]);

        $data['harga_satuan'] ??= 0;
        if ($data['harga_satuan'] == 0 && ($data['total_belanja'] ?? 0) > 0 && $data['qty'] > 0) {
            $data['harga_satuan'] = round($data['total_belanja'] / $data['qty'], 2);
        }
        $data['total_belanja'] = $data['qty'] * $data['harga_satuan'];
        $data['ongkir'] ??= 0;

        $pembelian = PembelianBarang::create($data);
        $this->applyPembelianStok($data['keterangan'], $data['qty'], $data['product_id'], $data['tanggal']);

        return redirect()->route('gudang.pembelian')->with('success', 'Data pembelian berhasil ditambahkan.');
    }

    private function applyPembelianStok(string $keterangan, int $qty, ?int $productId, string $tanggal): void
    {
        if ($keterangan !== 'MASUK STOK' || $qty === 0 || ! $productId) {
            return;
        }

        $product = Product::find($productId);
        $sm = StockMovement::firstOrNew([
            'product_id' => $productId,
            'gudang' => $product?->gudang?->nama ?? '',
            'tanggal' => $tanggal,
        ]);
        if (! $sm->exists) {
            $sm->masuk_belanja = 0;
            $sm->masuk_rts = 0;
            $sm->masuk_repair = 0;
            $sm->barang_rusak = 0;
            $sm->barang_keluar = 0;
            $sm->catatan = 'Pembelian masuk stok';
        }
        $sm->masuk_belanja += $qty;
        $sm->save();

        Product::where('id', $productId)->increment('stok', $qty);
    }

    public function pembelianEdit(PembelianBarang $pembelian): View
    {
        $suppliers = Supplier::orderBy('nama_supplier')->get();
        $products = Product::orderBy('nama_produk')->get();

        return view('gudang.pembelian-edit', compact('pembelian', 'suppliers', 'products'));
    }

    public function pembelianUpdate(Request $request, PembelianBarang $pembelian): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'sumber_produk' => 'nullable|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'qty' => 'required|integer|min:0',
            'harga_satuan' => 'nullable|numeric|min:0',
            'total_belanja' => 'nullable|numeric|min:0',
            'ongkir' => 'nullable|numeric|min:0',
            'keterangan' => 'required|in:MASUK STOK,BARU PESAN',
        ]);

        $data['harga_satuan'] ??= 0;
        if ($data['harga_satuan'] == 0 && ($data['total_belanja'] ?? 0) > 0 && $data['qty'] > 0) {
            $data['harga_satuan'] = round($data['total_belanja'] / $data['qty'], 2);
        }
        $data['total_belanja'] = $data['qty'] * $data['harga_satuan'];
        $data['ongkir'] ??= 0;

        $this->applyPembelianStok($pembelian->keterangan, -$pembelian->qty, $pembelian->product_id, $pembelian->tanggal->format('Y-m-d'));
        $pembelian->update($data);
        $this->applyPembelianStok($data['keterangan'], $data['qty'], $data['product_id'], $data['tanggal']);

        return redirect()->route('gudang.pembelian')->with('success', 'Data pembelian berhasil diperbarui.');
    }

    public function pembelianDestroy(PembelianBarang $pembelian): RedirectResponse
    {
        $this->applyPembelianStok($pembelian->keterangan, -$pembelian->qty, $pembelian->product_id, $pembelian->tanggal->format('Y-m-d'));
        $pembelian->delete();

        return redirect()->route('gudang.pembelian')->with('success', 'Data pembelian berhasil dihapus.');
    }

    // ─── Helper: HPP Weighted Average ─────────────────────────────

    private function hppRataRata(string $bulan, int $productId): ?float
    {
        $row = PembelianBarang::where('product_id', $productId)
            ->where('keterangan', 'MASUK STOK')
            ->whereYear('tanggal', substr($bulan, 0, 4))
            ->whereMonth('tanggal', substr($bulan, 5, 2))
            ->selectRaw('(COALESCE(SUM(total_belanja + ongkir), 0) / NULLIF(SUM(qty), 0)) as hpp')
            ->first();

        return $row?->hpp ? round((float) $row->hpp, 2) : null;
    }

    // ─── RTS per Hari (Pivot Table) ─────────────────────────────

    public function rtsPerHari(Request $request): View
    {
        $bulan = $request->filled('bulan') ? $request->bulan : date('Y-m');
        $year = substr($bulan, 0, 4);
        $month = substr($bulan, 5, 2);

        $products = Product::where('status', 'aktif')->orderBy('nama_produk')->get();

        // RTS qty dari stock_movements per produk per hari
        $rtsByProduct = StockMovement::where('masuk_rts', '>', 0)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->selectRaw('product_id, DAY(tanggal) as day, SUM(masuk_rts) as total_qty')
            ->groupBy('product_id', 'day')
            ->get()
            ->groupBy('product_id');

        $data = [];
        $grandDaily = [];
        for ($d = 1; $d <= 31; $d++) {
            $grandDaily[$d] = ['qty' => 0, 'value' => 0];
        }
        $grandTotalQty = 0;
        $grandTotalValue = 0;

        foreach ($products as $product) {
            $hpp = $this->hppRataRata($bulan, $product->id);

            $daily = [];
            $totalQty = 0;
            $totalValue = 0;

            for ($d = 1; $d <= 31; $d++) {
                $qty = (int) ($rtsByProduct->get($product->id)?->firstWhere('day', $d)?->total_qty ?? 0);
                $value = $hpp !== null ? round($hpp * $qty, 2) : 0;
                $daily[$d] = ['qty' => $qty, 'value' => $value];
                $totalQty += $qty;
                $totalValue += $value;
                $grandDaily[$d]['qty'] += $qty;
                if ($hpp !== null) {
                    $grandDaily[$d]['value'] += $value;
                }
            }

            $data[] = [
                'product' => $product,
                'hpp' => $hpp,
                'daily' => $daily,
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
            ];

            $grandTotalQty += $totalQty;
            if ($hpp !== null) {
                $grandTotalValue += $totalValue;
            }
        }

        $maxDay = (int) date('t', strtotime($bulan.'-01'));

        return view('gudang.rts-per-hari', compact('data', 'bulan', 'grandDaily', 'grandTotalQty', 'grandTotalValue', 'maxDay'));
    }

    // ─── Helper ──────────────────────────────────────────────────

    private function getDashboards(): array
    {
        return Dashboard::orderBy('name')->pluck('name')->toArray();
    }

    // ─── Kiriman Actual ──────────────────────────────────────────

    public function kiriman(Request $request): View
    {
        $bulan = $request->filled('bulan') ? $request->bulan : date('Y-m');
        $year = substr($bulan, 0, 4);
        $month = substr($bulan, 5, 2);
        $dashboards = $this->getDashboards();
        $selectedDashboard = $request->filled('dashboard') ? $request->dashboard : null;

        // Barang (product qty) totals per kiriman — load once
        $allKiriman = KirimanActual::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->with('products')
            ->get();

        $recapRaw = $allKiriman->groupBy(fn ($k) => $k->tanggal->format('Y-m-d'));

        $recapByDashboard = [];
        $grandTotalResi = 0;
        $grandTotalBarang = 0;
        $grandTotalValue = 0;

        if ($selectedDashboard) {
            $targetDashboards = [$selectedDashboard];
        } else {
            $targetDashboards = $dashboards;
        }

        foreach ($targetDashboards as $db) {
            $dbKiriman = $allKiriman->where('dashboard', $db);
            $grouped = $dbKiriman->groupBy(fn ($item) => $item->tanggal->format('Y-m-d'));
            $daily = [];
            $dbTotals = ['tf_resi' => 0, 'tf_barang' => 0, 'tf_value' => 0, 'cod_resi' => 0, 'cod_barang' => 0, 'cod_value' => 0];
            $maxDay = (int) date('t', strtotime($bulan.'-01'));

            for ($d = 1; $d <= $maxDay; $d++) {
                $dateStr = sprintf('%s-%02d', $bulan, $d);
                $byDate = $grouped->get($dateStr, collect());
                $tf = $byDate->where('jenis', 'TF');
                $cod = $byDate->where('jenis', 'COD');
                $tfResi = $tf->sum('jumlah_resi');
                $tfValue = $tf->sum('value_resi');
                $tfBarang = $tf->flatMap->products->sum('jumlah');
                $codResi = $cod->sum('jumlah_resi');
                $codValue = $cod->sum('value_resi');
                $codBarang = $cod->flatMap->products->sum('jumlah');
                $day = [
                    'date' => $dateStr,
                    'tf_resi' => $tfResi,
                    'tf_barang' => $tfBarang,
                    'tf_value' => $tfValue,
                    'cod_resi' => $codResi,
                    'cod_barang' => $codBarang,
                    'cod_value' => $codValue,
                ];
                $day['total_resi'] = $tfResi + $codResi;
                $day['total_barang'] = $tfBarang + $codBarang;
                $day['total_value'] = $tfValue + $codValue;
                $daily[] = $day;
                $dbTotals['tf_resi'] += $tfResi;
                $dbTotals['tf_barang'] += $tfBarang;
                $dbTotals['tf_value'] += $tfValue;
                $dbTotals['cod_resi'] += $codResi;
                $dbTotals['cod_barang'] += $codBarang;
                $dbTotals['cod_value'] += $codValue;
            }

            $recapByDashboard[] = [
                'dashboard' => $db,
                'daily' => $daily,
                'tf_resi' => $dbTotals['tf_resi'],
                'tf_barang' => $dbTotals['tf_barang'],
                'tf_value' => $dbTotals['tf_value'],
                'cod_resi' => $dbTotals['cod_resi'],
                'cod_barang' => $dbTotals['cod_barang'],
                'cod_value' => $dbTotals['cod_value'],
                'total_resi' => $dbTotals['tf_resi'] + $dbTotals['cod_resi'],
                'total_barang' => $dbTotals['tf_barang'] + $dbTotals['cod_barang'],
                'total_value' => $dbTotals['tf_value'] + $dbTotals['cod_value'],
            ];
            $grandTotalResi += $dbTotals['tf_resi'] + $dbTotals['cod_resi'];
            $grandTotalBarang += $dbTotals['tf_barang'] + $dbTotals['cod_barang'];
            $grandTotalValue += $dbTotals['tf_value'] + $dbTotals['cod_value'];
        }

        $allDashboards = Dashboard::orderBy('name')->get();
        $products = Product::orderBy('nama_produk')->get();

        return view('gudang.kiriman', compact('recapByDashboard', 'grandTotalResi', 'grandTotalBarang', 'grandTotalValue', 'bulan', 'allDashboards', 'selectedDashboard', 'dashboards', 'products'));
    }

    public function kirimanStore(Request $request): RedirectResponse
    {
        $dashboards = $this->getDashboards();
        $inDashboards = 'in:'.implode(',', $dashboards);

        $data = $request->validate([
            'tanggal' => 'required|date',
            'rows' => 'required|array|min:1',
            'rows.*.jenis' => 'required|in:COD,TF',
            'rows.*.dashboard' => 'required|'.$inDashboards,
            'rows.*.jumlah_resi' => 'required|integer|min:1',
            'rows.*.products' => 'required|array|min:1',
            'rows.*.products.*.product_id' => 'required|exists:products,id',
            'rows.*.products.*.jumlah' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($data) {
            $allProductIds = collect($data['rows'])
                ->flatMap(fn ($row) => collect($row['products'])->pluck('product_id'))
                ->unique()->values()->toArray();

            $products = Product::whereIn('id', $allProductIds)->get()->keyBy('id');

            foreach ($data['rows'] as $row) {
                $totalValue = 0;

                foreach ($row['products'] as $prod) {
                    $product = $products->get($prod['product_id']);
                    $totalValue += $product->harga_jual * $prod['jumlah'];
                }

                $kiriman = KirimanActual::create([
                    'tanggal' => $data['tanggal'],
                    'jenis' => $row['jenis'],
                    'dashboard' => $row['dashboard'],
                    'jumlah_resi' => $row['jumlah_resi'],
                    'value_resi' => $totalValue,
                ]);

                foreach ($row['products'] as $prod) {
                    $product = $products->get($prod['product_id']);

                    $kiriman->products()->create([
                        'product_id' => $prod['product_id'],
                        'jumlah' => $prod['jumlah'],
                    ]);
                }

                $this->createKirimanStok($kiriman);
            }
        });

        return redirect()->route('gudang.kiriman')->with('success', 'Data kiriman berhasil ditambahkan.');
    }

    private function createKirimanStok(KirimanActual $kiriman): void
    {
        foreach ($kiriman->products as $kp) {
            $product = $kp->product;
            $sm = StockMovement::firstOrNew([
                'product_id' => $kp->product_id,
                'gudang' => $product?->gudang?->nama ?? '',
                'tanggal' => $kiriman->tanggal->format('Y-m-d'),
            ]);
            $sm->barang_keluar = ($sm->barang_keluar ?? 0) + $kp->jumlah;
            $newCatatan = 'Kiriman '.$kiriman->jenis.' '.$kiriman->dashboard;
            if ($sm->exists && ! str_contains($sm->catatan ?? '', $newCatatan)) {
                $sm->catatan = ($sm->catatan ?? '').'; '.$newCatatan;
            } elseif (! $sm->exists) {
                $sm->catatan = $newCatatan;
                $sm->masuk_belanja = 0;
                $sm->masuk_rts = 0;
                $sm->masuk_repair = 0;
                $sm->barang_rusak = 0;
            }
            $sm->save();

            Product::where('id', $kp->product_id)->decrement('stok', $kp->jumlah);
        }
    }

    private function reverseKirimanStok(KirimanActual $kiriman): void
    {
        $tanggal = $kiriman->tanggal->format('Y-m-d');

        foreach ($kiriman->products as $kp) {
            $rows = StockMovement::where('product_id', $kp->product_id)
                ->whereDate('tanggal', $tanggal)
                ->where('barang_keluar', '>', 0)
                ->orderBy('id')
                ->get();

            $remaining = $kp->jumlah;
            foreach ($rows as $row) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min($remaining, $row->barang_keluar);
                $row->barang_keluar -= $take;
                $row->save();
                $remaining -= $take;
            }

            $reversed = $kp->jumlah - $remaining;
            if ($reversed > 0) {
                Product::where('id', $kp->product_id)->increment('stok', $reversed);
            }
        }
    }

    public function kirimanEdit(KirimanActual $kiriman): View
    {
        $dashboards = $this->getDashboards();
        $kiriman->load(['products.product', 'stockMovements']);
        $products = Product::orderBy('nama_produk')->get();

        return view('gudang.kiriman-edit', compact('kiriman', 'dashboards', 'products'));
    }

    public function kirimanUpdate(Request $request, KirimanActual $kiriman): RedirectResponse
    {
        $inDashboards = 'in:'.implode(',', $this->getDashboards());

        $data = $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:COD,TF',
            'dashboard' => 'required|'.$inDashboards,
            'jumlah_resi' => 'required|integer|min:1',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.jumlah' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($data, $kiriman) {
            // Reverse old stock movements (berlaku juga untuk movement hasil import Excel)
            $this->reverseKirimanStok($kiriman);

            // Load products for pricing
            $allProductIds = collect($data['products'])->pluck('product_id')->unique()->values()->toArray();
            $products = Product::whereIn('id', $allProductIds)->get()->keyBy('id');

            $totalValue = 0;
            foreach ($data['products'] as $prod) {
                $product = $products->get($prod['product_id']);
                $totalValue += $product->harga_jual * $prod['jumlah'];
            }

            $kiriman->update([
                'tanggal' => $data['tanggal'],
                'jenis' => $data['jenis'],
                'dashboard' => $data['dashboard'],
                'jumlah_resi' => $data['jumlah_resi'],
                'value_resi' => $totalValue,
            ]);

            // Replace products
            $kiriman->products()->delete();
            foreach ($data['products'] as $prod) {
                $product = $products->get($prod['product_id']);

                $kiriman->products()->create([
                    'product_id' => $prod['product_id'],
                    'jumlah' => $prod['jumlah'],
                ]);
            }

            $this->createKirimanStok($kiriman);
        });

        return redirect()->route('gudang.kiriman')->with('success', 'Data kiriman berhasil diperbarui.');
    }

    public function kirimanDestroy(KirimanActual $kiriman): RedirectResponse
    {
        DB::transaction(function () use ($kiriman) {
            $this->reverseKirimanStok($kiriman);
            $kiriman->delete();
        });

        return redirect()->route('gudang.kiriman')->with('success', 'Data kiriman berhasil dihapus.');
    }

    public function kirimanDashboardStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:dashboards,name',
        ]);

        Dashboard::create($data);

        return redirect()->route('gudang.kiriman')->with('success', 'Dashboard "'.$data['name'].'" berhasil ditambahkan.');
    }

    public function kirimanDashboardDestroy(Dashboard $dashboard): RedirectResponse
    {
        $dashboard->delete();

        return redirect()->route('gudang.kiriman')->with('success', 'Dashboard berhasil dihapus.');
    }

    // ─── Kiriman Actual — Excel Import ────────────────────────────

    public function kirimanExcelPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain,application/csv', 'max:10240'],
            'tanggal' => ['nullable', 'date'],
        ]);

        try {
            $importService = app(KirimanImportService::class);
            $result = $importService->parseExcel($request->file('file')->getPathname());

            if ($request->filled('tanggal')) {
                $tgl = $request->input('tanggal');
                foreach ($result['data'] as &$row) {
                    $row['tanggal'] = $tgl;
                    $row['detail']['tanggal_pembuatan'] = $tgl;
                }
                foreach ($result['groups'] as &$group) {
                    $group['tanggal'] = $tgl;
                }
            }

            if ($result['total'] === 0 && ! empty($request->file('file'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang bisa dibaca. Periksa apakah file memiliki kolom AWB/Resi, Tanggal, dan Nama Produk.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'groups' => $result['groups'],
                    'total' => $result['total'],
                ],
                'errors' => $result['errors'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file: '.$e->getMessage(),
            ], 422);
        }
    }

    public function kirimanExcelImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain,application/csv'],
            'tanggal' => ['nullable', 'date'],
        ]);

        try {
            $importService = app(KirimanImportService::class);
            $result = $importService->parseExcel($request->file('file')->getPathname());

            if ($request->filled('tanggal')) {
                $tgl = $request->input('tanggal');
                foreach ($result['data'] as &$row) {
                    $row['tanggal'] = $tgl;
                }
                foreach ($result['groups'] as &$group) {
                    $group['tanggal'] = $tgl;
                }
            }

            $errorCount = count($result['errors']);
            if ($errorCount > 0) {
                \Illuminate\Support\Facades\Log::warning('[KirimanImport] '.$errorCount.' rows skipped (product not found)', $result['errors']);
            }

            if (empty($result['groups'])) {
                $msg = 'Tidak ada data valid untuk diimport.';
                if ($errorCount > 0) $msg .= ' '.$errorCount.' baris dilewati karena produk tidak ditemukan.';
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            $products = $result['matched_products'];

            // ─── Filter duplicate AWB ────────────────────────────────
            $allAwbs = collect($result['data'])->pluck('awb')->filter()->values()->toArray();
            $existingAwbs = [];
            if (! empty($allAwbs)) {
                $existingAwbs = \App\Models\PaketTracking::whereIn('awb', $allAwbs)
                    ->pluck('awb')
                    ->mapWithKeys(fn ($awb) => [$awb => true])
                    ->toArray();
            }

            $filteredData = array_values(array_filter($result['data'], fn ($r) => empty($existingAwbs[$r['awb']])));
            $skipped = count($result['data']) - count($filteredData);

            if (empty($filteredData)) {
                return response()->json([
                    'success' => true,
                    'imported' => 0,
                    'skipped' => $skipped,
                    'message' => 'Semua data sudah ada ('.$skipped.' AWB skipped).',
                ]);
            }

            $regrouped = app(\App\Services\KirimanImportService::class)->groupData($filteredData);

            DB::transaction(function () use ($filteredData, $regrouped, $products) {
                $phoneCsMap = collect();
                $contacts = \App\Models\OrderOnlineContact::all();
                foreach ($contacts as $contact) {
                    if (! empty($contact->phone_normalized) && ! empty($contact->cs_name)) {
                        $phoneCsMap[$contact->phone_normalized] = $contact->cs_name;
                    }
                }

                foreach ($regrouped as $group) {
                    $kiriman = KirimanActual::create([
                        'tanggal' => $group['tanggal'],
                        'jenis' => $group['jenis'],
                        'dashboard' => $group['dashboard'],
                        'jumlah_resi' => $group['jumlah_resi'],
                        'value_resi' => $group['total_value'],
                    ]);

                    foreach ($group['products'] as $prod) {
                        $product = $products[$prod['product_id']] ?? null;
                        if (! $product) {
                            continue;
                        }

                        $kiriman->products()->create([
                            'product_id' => $prod['product_id'],
                            'jumlah' => $prod['jumlah'],
                        ]);

                        $sm = StockMovement::firstOrNew([
                            'product_id' => $prod['product_id'],
                            'gudang' => $product->gudang?->nama ?? '',
                            'tanggal' => $group['tanggal'],
                        ]);
                        $sm->barang_keluar = ($sm->barang_keluar ?? 0) + $prod['jumlah'];
                        $newCatatan = 'Kiriman '.$group['jenis'].' '.$group['dashboard'];
                        if ($sm->exists && ! str_contains($sm->catatan ?? '', $newCatatan)) {
                            $sm->catatan = ($sm->catatan ?? '').'; '.$newCatatan;
                        } elseif (! $sm->exists) {
                            $sm->catatan = $newCatatan;
                            $sm->masuk_belanja = 0;
                            $sm->masuk_rts = 0;
                            $sm->masuk_repair = 0;
                            $sm->barang_rusak = 0;
                        }
                        $sm->save();

                        Product::where('id', $prod['product_id'])->decrement('stok', $prod['jumlah']);
                    }

                    foreach ($filteredData as $row) {
                        if ($row['tanggal'] !== $group['tanggal']
                            || $row['dashboard'] !== $group['dashboard']
                            || $row['kurir'] !== $group['kurir']
                            || $row['jenis'] !== $group['jenis']) {
                            continue;
                        }

                        $noTelp = trim((string) ($row['detail']['no_telp'] ?? ''));
                        $handleBy = null;
                        if (! empty($noTelp)) {
                            $normalizedPhone = \App\Services\OrderOnlineImportService::normalizePhone($noTelp);
                            $handleBy = $phoneCsMap[$normalizedPhone] ?? null;
                        }

                        PaketTracking::create(array_merge(
                            $row['detail'],
                            [
                                'kiriman_actual_id' => $kiriman->id,
                                'product_id' => $row['product_id'],
                                'handle_by' => $handleBy,
                            ]
                        ));
                    }
                }
            });

            $msg = 'Berhasil import '.count($filteredData).' data kiriman.';
            if ($skipped > 0) $msg .= ' '.$skipped.' AWB skipped (already exist).';
            if ($errorCount > 0) $msg .= ' '.$errorCount.' baris dilewati (produk tidak ditemukan).';

            return response()->json([
                'success' => true,
                'imported' => count($filteredData),
                'skipped' => $skipped + $errorCount,
                'message' => $msg,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal import: '.$e->getMessage(),
            ], 500);
        }
    }

    public function backfillHandleBy(): JsonResponse
    {
        $updated = 0;
        $phoneCsMap = collect();
        $contacts = \App\Models\OrderOnlineContact::whereNotNull('phone_normalized')->where('phone_normalized', '!', '')->get();
        foreach ($contacts as $contact) {
            if (! empty($contact->phone_normalized) && ! empty($contact->cs_name)) {
                $phoneCsMap[$contact->phone_normalized] = $contact->cs_name;
            }
        }

        $affected = PaketTracking::where(function ($q) {
            $q->whereNull('handle_by')->orWhere('handle_by', '');
        })
            ->whereNotNull('no_telp')
            ->where('no_telp', '!=', '')
=======
        $consumables = Product::aktif()->tap($with)
            ->where('goods_type', Product::GOODS_CONSUMABLE)
            ->tap($scoped)
            ->orderBy('code')
            ->get();

        $cores = Product::aktif()->tap($with)
            ->where('goods_type', Product::GOODS_CORE)
            ->tap($scoped)
            ->orderBy('code')
>>>>>>> 31116a421615ff596ca544b8bd2f45c31d785e57
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

<<<<<<< HEAD
        DB::transaction(function () use ($productId, $data, $tanggal, $bulan) {
            $movements = StockMovement::where('product_id', $productId)
                ->where('gudang', $data['gudang'])
                ->whereDate('tanggal', $tanggal)
                ->get();

            if ($movements->isEmpty()) {
                return;
            }

            foreach ($movements as $m) {
                $delta = -$this->movementDelta($m->toArray());
                Product::where('id', $productId)->increment('stok', $delta);
                $m->delete();
            }

            $kirimanList = KirimanActual::where('tanggal', $tanggal)->get();
            foreach ($kirimanList as $kiriman) {
                $deleted = PaketTracking::where('kiriman_actual_id', $kiriman->id)
                    ->where('product_id', $productId)
                    ->delete();

                if ($deleted > 0) {
                    $kiriman->decrement('jumlah_resi', $deleted);
                }

                $remaining = PaketTracking::where('kiriman_actual_id', $kiriman->id)->count();
                if ($remaining === 0) {
                    $kiriman->products()->delete();
                    $kiriman->delete();
                }
            }
        });

        return redirect()->route('gudang.stok-rincian', ['bulan' => $bulan])
            ->with('success', 'Data stok tanggal ' . $tanggal . ' berhasil dihapus.');
    }

    // ─── Master Gudang ───────────────────────────────────────────

    public function gudangMaster(): View
    {
        $gudangs = Gudang::with('products')->orderBy('nama')->get();

        return view('gudang.master', compact('gudangs'));
    }

    public function gudangMasterStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['nama' => 'required|string|max:255|unique:gudangs,nama']);

        Gudang::create($data);

        return redirect()->route('gudang.master')->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function gudangMasterDestroy(Gudang $gudang): RedirectResponse
    {
        $gudang->delete();

        return redirect()->route('gudang.master')->with('success', 'Gudang berhasil dihapus.');
    }

    // ─── Rincian Stok ────────────────────────────────────────────

    public function stokRincian(Request $request): View
    {
        $bulan = $request->filled('bulan') ? $request->bulan : date('Y-m');
        $bulanStart = $bulan.'-01';

        if (! Gudang::count()) {
            $existing = StockMovement::select('gudang')->distinct()->pluck('gudang')->filter();
            foreach ($existing as $nama) {
                Gudang::create(['nama' => $nama]);
=======
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
>>>>>>> 31116a421615ff596ca544b8bd2f45c31d785e57
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
<<<<<<< HEAD
        return ($data['masuk_belanja'] + $data['masuk_rts'] + $data['masuk_repair'])
            - ($data['barang_rusak'] + $data['barang_keluar']);
    }

    public function stokRincianStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'gudang' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'masuk_belanja' => 'nullable|integer|min:0',
            'masuk_rts' => 'nullable|integer|min:0',
            'masuk_repair' => 'nullable|integer|min:0',
            'barang_rusak' => 'nullable|integer|min:0',
            'barang_keluar' => 'nullable|integer|min:0',
            'catatan' => 'nullable|string|max:255',
        ]);

        foreach (['masuk_belanja', 'masuk_rts', 'masuk_repair', 'barang_rusak', 'barang_keluar'] as $f) {
            $data[$f] = $data[$f] ?? 0;
        }

        StockMovement::create($data);
        Product::where('id', $data['product_id'])->increment('stok', $this->movementDelta($data));

        return redirect()->route('gudang.stok-rincian', ['bulan' => substr($data['tanggal'], 0, 7)])
            ->with('success', 'Data stok berhasil ditambahkan.');
    }

    public function stokRincianDestroy(StockMovement $stockMovement): RedirectResponse
    {
        $delta = -$this->movementDelta($stockMovement->toArray());
        Product::where('id', $stockMovement->product_id)->increment('stok', $delta);

        $bulan = substr($stockMovement->tanggal->format('Y-m'), 0, 7);
        $productId = $stockMovement->product_id;
        $tanggal = $stockMovement->tanggal->format('Y-m-d');
        $stockMovement->delete();

        $kirimanList = KirimanActual::whereDate('tanggal', $tanggal)->get();
        foreach ($kirimanList as $kiriman) {
            $deleted = PaketTracking::where('kiriman_actual_id', $kiriman->id)
                ->where('product_id', $productId)
                ->delete();
            if ($deleted > 0) {
                $kiriman->decrement('jumlah_resi', $deleted);
            }
            $remaining = PaketTracking::where('kiriman_actual_id', $kiriman->id)->count();
            if ($remaining === 0) {
                $kiriman->products()->delete();
                $kiriman->delete();
            }
        }

        return redirect()->route('gudang.stok-rincian', ['bulan' => $bulan])
            ->with('success', 'Data stok berhasil dihapus.');
    }

    public function stokRincianEdit(StockMovement $stockMovement): View
    {
        $gudangs = Gudang::orderBy('nama')->get();

        return view('gudang.stok-rincian-edit', ['item' => $stockMovement, 'gudangs' => $gudangs]);
    }

    public function stokRincianUpdate(Request $request, StockMovement $stockMovement): RedirectResponse
    {
        $data = $request->validate([
            'gudang' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'tanggal' => 'required|date',
            'masuk_belanja' => 'nullable|integer|min:0',
            'masuk_rts' => 'nullable|integer|min:0',
            'masuk_repair' => 'nullable|integer|min:0',
            'barang_rusak' => 'nullable|integer|min:0',
            'barang_keluar' => 'nullable|integer|min:0',
            'catatan' => 'nullable|string|max:255',
        ]);

        foreach (['masuk_belanja', 'masuk_rts', 'masuk_repair', 'barang_rusak', 'barang_keluar'] as $f) {
            $data[$f] = $data[$f] ?? 0;
        }

        $oldProductId = $stockMovement->product_id;
        $oldDelta = $this->movementDelta($stockMovement->toArray());
        $newDelta = $this->movementDelta($data);

        $stockMovement->update($data);

        if ($oldProductId === (int) $data['product_id']) {
            Product::where('id', $oldProductId)->increment('stok', $newDelta - $oldDelta);
        } else {
            Product::where('id', $oldProductId)->increment('stok', -$oldDelta);
            Product::where('id', (int) $data['product_id'])->increment('stok', $newDelta);
        }

        return redirect()->route('gudang.stok-rincian', ['bulan' => substr($data['tanggal'], 0, 7)])
            ->with('success', 'Data stok berhasil diperbarui.');
    }

    // ─── Rekap Stok Barang (GUDANG KUNINGAN) ─────────────────────

    public function rekapStok(Request $request): View
    {
        $bulan = $request->filled('bulan') ? $request->bulan : date('Y-m');
        $year = substr($bulan, 0, 4);
        $month = substr($bulan, 5, 2);
        $bulanStart = $bulan.'-01';

        $products = Product::where('status', 'aktif')
            ->with('supplier')
            ->orderBy('nama_produk')
            ->get();

        // Hitung net movement dari bulan ini sampai sekarang (untuk stok awal)
        $productIds = $products->pluck('id');
        $movementsFromMonth = collect();
        if ($productIds->isNotEmpty()) {
            $raw = DB::table('stock_movements')
                ->whereIn('product_id', $productIds)
                ->where('tanggal', '>=', $bulanStart)
                ->selectRaw('product_id, COALESCE(SUM(masuk_belanja + masuk_rts + masuk_repair - barang_rusak - barang_keluar), 0) as total')
                ->groupBy('product_id')
                ->get();
            foreach ($raw as $row) {
                $movementsFromMonth[$row->product_id] = (int) $row->total;
            }
        }

        // Ambil movement bulan ini per produk
        $thisMonthMovements = StockMovement::whereIn('product_id', $productIds)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->selectRaw('product_id,
                COALESCE(SUM(masuk_belanja),0) as total_in,
                COALESCE(SUM(masuk_rts),0) as total_rts,
                COALESCE(SUM(masuk_repair),0) as total_repair,
                COALESCE(SUM(barang_rusak),0) as total_rusak,
                COALESCE(SUM(barang_keluar),0) as total_out')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        // Ambil real_stok yang sudah diinput bulan ini
        $recaps = StockRecap::where('bulan', $bulan)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        // Hitung HPP exact (unrounded) untuk value columns
        $hppExact = collect();
        if ($productIds->isNotEmpty()) {
            $raw = PembelianBarang::whereIn('product_id', $productIds)
                ->where('keterangan', 'MASUK STOK')
                ->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->selectRaw('product_id, COALESCE(SUM(total_belanja + ongkir), 0) as total_nilai, COALESCE(SUM(qty), 0) as total_qty')
                ->groupBy('product_id')
                ->get();
            foreach ($raw as $row) {
                $hppExact[$row->product_id] = $row->total_qty > 0 ? $row->total_nilai / $row->total_qty : null;
            }
        }

        $data = [];
        $grandTotals = [
            'stok_awal' => 0, 'in' => 0, 'rts' => 0, 'repair' => 0,
            'rusak' => 0, 'out' => 0, 'stok_akhir' => 0,
            'real_stok' => 0, 'value_in' => 0, 'value_s_akhir' => 0, 'value_real' => 0,
=======
        $rules = [
            'inventory_ids' => ['required', 'array', 'min:1'],
            'inventory_ids.*' => ['integer', 'exists:inventories,id'],
>>>>>>> 31116a421615ff596ca544b8bd2f45c31d785e57
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
