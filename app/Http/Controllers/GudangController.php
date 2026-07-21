<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Models\KirimanActual;
use App\Models\PembelianBarang;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockRecap;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GudangController extends Controller
{
    /** Stok Gudang — daftar stok produk terkini */
    public function stok(): View
    {
        $products = Product::with('supplier')
            ->orderBy('stok', 'asc')
            ->paginate(15);

        return view('gudang.stok', compact('products'));
    }

    // ─── Master Pembelian Barang ─────────────────────────────────

    public function pembelian(Request $request): View
    {
        $produkQuery = Product::with(['pembelianBarangs' => function ($q) use ($request) {
            $q->with('supplierRel')->orderBy('tanggal');
            if ($request->filled('bulan')) {
                $q->whereYear('tanggal', substr($request->bulan, 0, 4))
                    ->whereMonth('tanggal', substr($request->bulan, 5, 2));
            }
        }])
            ->whereHas('pembelianBarangs', function ($q) use ($request) {
                if ($request->filled('bulan')) {
                    $q->whereYear('tanggal', substr($request->bulan, 0, 4))
                        ->whereMonth('tanggal', substr($request->bulan, 5, 2));
                }
            })
            ->orderBy('nama_produk');

        $produkList = $produkQuery->paginate(10)->withQueryString();

        foreach ($produkList as $produk) {
            $runningQty = 0;
            $runningNilai = 0;
            $sumQty = 0;
            $sumTotalBelanja = 0;
            $sumOngkir = 0;
            $sumHargaSatuan = 0;
            foreach ($produk->pembelianBarangs as $pb) {
                $sumQty += $pb->qty;
                $sumTotalBelanja += $pb->total_belanja;
                $sumOngkir += $pb->ongkir;
                $sumHargaSatuan += $pb->harga_satuan;
                if ($pb->keterangan === 'MASUK STOK') {
                    $runningQty += $pb->qty;
                    $runningNilai += $pb->total_belanja + $pb->ongkir;
                }
                $pb->akumulasi_qty = $runningQty;
                $pb->akumulasi_nilai = $runningNilai;
                $pb->hpp_rata_rata = $runningQty > 0 ? round($runningNilai / $runningQty, 2) : 0;
            }
            $produk->total_qty = $runningQty;
            $produk->total_nilai = $runningNilai;
            $produk->hpp_akhir = $runningQty > 0 ? round($runningNilai / $runningQty, 2) : 0;
            $produk->sum_qty = $sumQty;
            $produk->sum_total_belanja = $sumTotalBelanja;
            $produk->sum_ongkir = $sumOngkir;
            $produk->sum_harga_satuan = $sumHargaSatuan;
        }

        $suppliers = Supplier::orderBy('nama_supplier')->get();
        $products = Product::orderBy('nama_produk')->get();

        return view('gudang.pembelian', compact('produkList', 'suppliers', 'products'));
    }

    public function pembelianStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'supplier' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
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

        if ($data['supplier_id'] && ! $data['supplier']) {
            $supplier = Supplier::find($data['supplier_id']);
            $data['supplier'] = $supplier?->nama_supplier ?? '';
        }
        if ($data['product_id'] && ! $data['sumber_produk']) {
            $product = Product::find($data['product_id']);
            $data['sumber_produk'] = $product?->nama_produk ?? '';
        }

        PembelianBarang::create($data);

        return redirect()->route('gudang.pembelian')->with('success', 'Data pembelian berhasil ditambahkan.');
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
            'supplier' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
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

        if ($data['supplier_id'] && ! $data['supplier']) {
            $supplier = Supplier::find($data['supplier_id']);
            $data['supplier'] = $supplier?->nama_supplier ?? '';
        }
        if ($data['product_id'] && ! $data['sumber_produk']) {
            $product = Product::find($data['product_id']);
            $data['sumber_produk'] = $product?->nama_produk ?? '';
        }

        $pembelian->update($data);

        return redirect()->route('gudang.pembelian')->with('success', 'Data pembelian berhasil diperbarui.');
    }

    public function pembelianDestroy(PembelianBarang $pembelian): RedirectResponse
    {
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

        // Daily recap — per-dashboard tables with TF/COD breakdown
        $recapRaw = KirimanActual::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->selectRaw('tanggal, dashboard, jenis, SUM(jumlah_resi) as total_resi, SUM(value_resi) as total_value')
            ->groupBy('tanggal', 'dashboard', 'jenis')
            ->orderBy('tanggal')
            ->get();

        $recapByDashboard = [];
        $grandTotalResi = 0;
        $grandTotalValue = 0;

        if ($selectedDashboard) {
            $targetDashboards = [$selectedDashboard];
        } else {
            $targetDashboards = $dashboards;
        }

        foreach ($targetDashboards as $db) {
            $rows = $recapRaw->where('dashboard', $db);
            $grouped = $rows->groupBy(fn ($item) => $item->tanggal instanceof Carbon ? $item->tanggal->format('Y-m-d') : $item->tanggal);
            $daily = [];
            $dbTotals = ['tf_resi' => 0, 'tf_value' => 0, 'cod_resi' => 0, 'cod_value' => 0];
            $maxDay = (int) date('t', strtotime($bulan.'-01'));

            for ($d = 1; $d <= $maxDay; $d++) {
                $dateStr = sprintf('%s-%02d', $bulan, $d);
                $byDate = $grouped->get($dateStr);
                $tf = $byDate?->firstWhere('jenis', 'TF');
                $cod = $byDate?->firstWhere('jenis', 'COD');
                $day = [
                    'date' => $dateStr,
                    'tf_resi' => $tf ? (int) $tf->total_resi : 0,
                    'tf_value' => $tf ? (float) $tf->total_value : 0,
                    'cod_resi' => $cod ? (int) $cod->total_resi : 0,
                    'cod_value' => $cod ? (float) $cod->total_value : 0,
                ];
                $day['total_resi'] = $day['tf_resi'] + $day['cod_resi'];
                $day['total_value'] = $day['tf_value'] + $day['cod_value'];
                $daily[] = $day;
                $dbTotals['tf_resi'] += $day['tf_resi'];
                $dbTotals['tf_value'] += $day['tf_value'];
                $dbTotals['cod_resi'] += $day['cod_resi'];
                $dbTotals['cod_value'] += $day['cod_value'];
            }

            $recapByDashboard[] = [
                'dashboard' => $db,
                'daily' => $daily,
                'tf_resi' => $dbTotals['tf_resi'],
                'tf_value' => $dbTotals['tf_value'],
                'cod_resi' => $dbTotals['cod_resi'],
                'cod_value' => $dbTotals['cod_value'],
                'total_resi' => $dbTotals['tf_resi'] + $dbTotals['cod_resi'],
                'total_value' => $dbTotals['tf_value'] + $dbTotals['cod_value'],
            ];
            $grandTotalResi += $dbTotals['tf_resi'] + $dbTotals['cod_resi'];
            $grandTotalValue += $dbTotals['tf_value'] + $dbTotals['cod_value'];
        }

        $allDashboards = Dashboard::orderBy('name')->get();

        return view('gudang.kiriman', compact('recapByDashboard', 'grandTotalResi', 'grandTotalValue', 'bulan', 'allDashboards', 'selectedDashboard', 'dashboards'));
    }

    public function kirimanStore(Request $request): RedirectResponse
    {
        $dashboards = $this->getDashboards();
        $inDashboards = 'in:'.implode(',', $dashboards);

        $data = $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|array',
            'jenis.*' => 'required|in:COD,TF',
            'dashboard' => 'required|array',
            'dashboard.*' => 'required|'.$inDashboards,
            'jumlah_resi' => 'required|array',
            'jumlah_resi.*' => 'required|integer|min:0',
            'value_resi' => 'required|array',
            'value_resi.*' => 'required|numeric|min:0',
        ]);

        $count = count($data['jenis']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($data['jenis'][$i]) || empty($data['dashboard'][$i])) {
                continue;
            }
            KirimanActual::create([
                'tanggal' => $data['tanggal'],
                'jenis' => $data['jenis'][$i],
                'dashboard' => $data['dashboard'][$i],
                'jumlah_resi' => (int) ($data['jumlah_resi'][$i] ?? 0),
                'value_resi' => (float) ($data['value_resi'][$i] ?? 0),
            ]);
        }

        return redirect()->route('gudang.kiriman')->with('success', 'Data kiriman berhasil ditambahkan.');
    }

    public function kirimanEdit(KirimanActual $kiriman): View
    {
        $dashboards = $this->getDashboards();

        return view('gudang.kiriman-edit', compact('kiriman', 'dashboards'));
    }

    public function kirimanUpdate(Request $request, KirimanActual $kiriman): RedirectResponse
    {
        $inDashboards = 'in:'.implode(',', $this->getDashboards());

        $data = $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:COD,TF',
            'dashboard' => 'required|'.$inDashboards,
            'jumlah_resi' => 'required|integer|min:0',
            'value_resi' => 'required|numeric|min:0',
        ]);

        $kiriman->update($data);

        return redirect()->route('gudang.kiriman')->with('success', 'Data kiriman berhasil diperbarui.');
    }

    public function kirimanDestroy(KirimanActual $kiriman): RedirectResponse
    {
        $kiriman->delete();

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

    // ─── Rincian Stok ────────────────────────────────────────────

    public function stokRincian(Request $request): View
    {
        $bulan = $request->filled('bulan') ? $request->bulan : date('Y-m');
        $bulanStart = $bulan.'-01';

        $gudangList = StockMovement::select('gudang')->distinct()->pluck('gudang')->sort()->values();

        // Ambil semua movement bulan ini per gudang
        $allMovements = StockMovement::with('product')
            ->whereYear('tanggal', substr($bulan, 0, 4))
            ->whereMonth('tanggal', substr($bulan, 5, 2))
            ->orderBy('gudang')
            ->orderBy('product_id')
            ->orderBy('tanggal')
            ->get()
            ->groupBy('gudang');

        // Kumpulkan semua kombinasi gudang+produk untuk ambil stok awal
        $pairs = collect();
        foreach ($allMovements as $gudang => $byGudang) {
            $byGudang->groupBy('product_id')->each(function ($movements, $productId) use ($gudang, $pairs) {
                $pairs->push(['gudang' => $gudang, 'product_id' => (int) $productId]);
            });
        }

        // Ambil total stok dari bulan-bulan sebelumnya per gudang+produk (1 query bulk)
        $priorTotals = collect();
        if ($pairs->isNotEmpty()) {
            $raw = DB::table('stock_movements')
                ->where('tanggal', '<', $bulanStart)
                ->selectRaw('gudang, product_id, COALESCE(SUM(masuk_belanja + masuk_rts + masuk_repair - barang_rusak - barang_keluar), 0) as total')
                ->groupBy('gudang', 'product_id')
                ->get();
            foreach ($raw as $row) {
                $priorTotals[$row->gudang][$row->product_id] = (int) $row->total;
            }
        }

        $gudangData = [];
        $seedCache = [];
        foreach ($allMovements as $gudang => $byGudang) {
            $produkGroups = $byGudang->groupBy('product_id');
            $produkData = [];
            foreach ($produkGroups as $productId => $movements) {
                $produk = $movements->first()->product;
                if (! $produk) {
                    continue;
                }
                $priorTotal = $priorTotals[$gudang][$productId] ?? 0;

                // Hitung selisih stok yang tidak tercatat di movement (stok seed/awal)
                $seedKey = 'seed_'.$productId;
                if (! isset($seedCache[$seedKey])) {
                    $totalAllGudang = (int) StockMovement::where('product_id', $productId)
                        ->sum(DB::raw('masuk_belanja + masuk_rts + masuk_repair - barang_rusak - barang_keluar'));
                    $seedCache[$seedKey] = max(0, $produk->stok - $totalAllGudang);
                }
                $runningStock = max(0, $priorTotal + $seedCache[$seedKey]);
                foreach ($movements as $m) {
                    $m->stock_awal_hari = $runningStock;
                    $totalMasuk = $m->masuk_belanja + $m->masuk_rts + $m->masuk_repair;
                    $totalKeluar = $m->barang_rusak + $m->barang_keluar;
                    $runningStock = $runningStock + $totalMasuk - $totalKeluar;
                    $m->stock_akhir_hari = $runningStock;
                }
                $produk->movements = $movements;
                $produk->stock_akhir_bulan = $runningStock;
                $produkData[] = $produk;
            }
            usort($produkData, fn ($a, $b) => $a->nama_produk <=> $b->nama_produk);
            $gudangData[$gudang] = $produkData;
        }

        return view('gudang.stok-rincian', compact('gudangData', 'gudangList', 'bulan'));
    }

    private function movementDelta(array $data): int
    {
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
        $stockMovement->delete();

        return redirect()->route('gudang.stok-rincian', ['bulan' => $bulan])
            ->with('success', 'Data stok berhasil dihapus.');
    }

    public function stokRincianEdit(StockMovement $stockMovement): View
    {
        $gudangList = StockMovement::select('gudang')->distinct()->pluck('gudang')->sort()->values();

        return view('gudang.stok-rincian-edit', ['item' => $stockMovement, 'gudangList' => $gudangList]);
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
        ];

        foreach ($products as $product) {
            $mov = $thisMonthMovements->get($product->id);
            $total_in = $mov ? (int) $mov->total_in : 0;
            $total_rts = $mov ? (int) $mov->total_rts : 0;
            $total_repair = $mov ? (int) $mov->total_repair : 0;
            $total_rusak = $mov ? (int) $mov->total_rusak : 0;
            $total_out = $mov ? (int) $mov->total_out : 0;

            $afterTotal = $movementsFromMonth[$product->id] ?? 0;
            $stok_awal = max(0, $product->stok - $afterTotal);
            $stok_akhir = $stok_awal + $total_in + $total_rts + $total_repair - $total_rusak - $total_out;

            $hpp = $hppExact->get($product->id);
            $hppDisplay = $hpp !== null ? round($hpp, 2) : null;
            $value_in = $hpp !== null ? round($hpp * $total_in) : 0;
            $value_s_akhir = $hpp !== null ? round($hpp * $stok_akhir) : 0;

            $recap = $recaps->get($product->id);
            $real_stok = $recap ? (int) $recap->real_stok : 0;
            $selisih = $real_stok > 0 ? $stok_akhir - $real_stok : 0;
            $value_real = $hpp !== null && $real_stok > 0 ? round($hpp * $real_stok) : 0;

            // Keterangan: DEAD STOK jika tidak ada movement sama sekali
            $keterangan = '';
            if ($total_in === 0 && $total_rts === 0 && $total_repair === 0 && $total_rusak === 0 && $total_out === 0) {
                $keterangan = 'DEAD STOK';
            }

            $data[] = [
                'product' => $product,
                'satuan' => $product->satuan,
                'stok_awal' => $stok_awal,
                'in' => $total_in,
                'rts' => $total_rts,
                'repair' => $total_repair,
                'rusak' => $total_rusak,
                'out' => $total_out,
                'stok_akhir' => $stok_akhir,
                'hpp' => $hppDisplay,
                'value_in' => $value_in,
                'value_s_akhir' => $value_s_akhir,
                'real_stok' => $real_stok,
                'selisih' => $selisih,
                'value_real' => $value_real,
                'keterangan' => $keterangan,
            ];

            $grandTotals['stok_awal'] += $stok_awal;
            $grandTotals['in'] += $total_in;
            $grandTotals['rts'] += $total_rts;
            $grandTotals['repair'] += $total_repair;
            $grandTotals['rusak'] += $total_rusak;
            $grandTotals['out'] += $total_out;
            $grandTotals['stok_akhir'] += $stok_akhir;
            $grandTotals['real_stok'] += $real_stok;
            $grandTotals['value_in'] += $value_in;
            $grandTotals['value_s_akhir'] += $value_s_akhir;
            $grandTotals['value_real'] += $value_real;
        }

        return view('gudang.rekap-stok', compact('data', 'bulan', 'grandTotals'));
    }

    public function rekapStokBulk(Request $request): RedirectResponse
    {
        $bulan = $request->filled('bulan') ? $request->bulan : date('Y-m');
        $year = substr($bulan, 0, 4);
        $month = substr($bulan, 5, 2);
        $bulanStart = $bulan.'-01';

        $data = $request->validate([
            'real_stok' => 'required|array',
            'real_stok.*' => 'nullable|integer|min:0',
        ]);

        $productIds = array_keys(array_filter($data['real_stok'], fn ($v) => $v !== null && $v !== ''));
        if (empty($productIds)) {
            return redirect()->route('gudang.rekap-stok', ['bulan' => $bulan])
                ->with('info', 'Tidak ada data real stok yang diinput.');
        }

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Hitung stok_akhir bulk
        $movements = StockMovement::whereIn('product_id', $productIds)
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

        $raw = DB::table('stock_movements')
            ->whereIn('product_id', $productIds)
            ->where('tanggal', '>=', $bulanStart)
            ->selectRaw('product_id, COALESCE(SUM(masuk_belanja + masuk_rts + masuk_repair - barang_rusak - barang_keluar), 0) as total')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        foreach ($data['real_stok'] as $pid => $realStok) {
            if ($realStok === null || $realStok === '') {
                continue;
            }
            $product = $products->get($pid);
            if (! $product) {
                continue;
            }

            $mov = $movements->get($pid);
            $netFromMonth = isset($raw[$pid]) ? (int) $raw[$pid]->total : 0;
            $stok_awal = max(0, $product->stok - $netFromMonth);
            $stok_akhir = $stok_awal
                + ($mov->total_in ?? 0) + ($mov->total_rts ?? 0) + ($mov->total_repair ?? 0)
                - ($mov->total_rusak ?? 0) - ($mov->total_out ?? 0);
            $selisih = $stok_akhir - (int) $realStok;

            StockRecap::updateOrCreate(
                ['product_id' => (int) $pid, 'bulan' => $bulan],
                ['real_stok' => (int) $realStok, 'selisih' => $selisih],
            );
        }

        return redirect()->route('gudang.rekap-stok', ['bulan' => $bulan])
            ->with('success', 'Real stok berhasil disimpan untuk '.count($productIds).' produk.');
    }
}
