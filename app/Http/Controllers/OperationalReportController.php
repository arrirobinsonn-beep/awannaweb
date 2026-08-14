<?php

namespace App\Http\Controllers;

use App\Models\OrderOnlineImportBatch;
use App\Models\ShippingOrder;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Laporan Operasional — ringkasan aktivitas gudang & pengiriman per hari.
 *
 * Dashboard menampilkan kartu "hari ini" (barang keluar/masuk, resi, metode
 * bayar); kartu tersebut menautkan ke halaman ini untuk detail per PENGIRIM
 * (kolom `order_online_import_batches.sender`) pada rentang tanggal terpilih,
 * plus total keseluruhan: uang masuk (gross revenue) vs HPP (purchase_price × qty).
 *
 * Semua angka dihitung dengan QUERY AGREGAT (GROUP BY / SUM di SQL) — tidak
 * ada query per baris/per pengirim di dalam loop (pola batch AGENTS.md):
 *   - stok hari ini : 1 aggregate `stock_movements` (index `date` + `type`)
 *   - laporan       : 1 aggregate `shipping_orders` JOIN batches JOIN products
 *                     (index `order_online_import_batch_id` + `created_at`)
 * Total keseluruhan dihitung dari hasil GROUP BY (sum kolom di collection).
 *
 * Filter tanggal memakai RANGE (`>=`/`<`) bukan `whereDate()` agar index
 * `created_at` tetap terpakai (whereDate → DATE() → index mati → full scan).
 */
class OperationalReportController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();

        $dari = $this->parseDate($request->input('dari'), $today);
        $sampai = $this->parseDate($request->input('sampai'), $today);
        if ($sampai->lt($dari)) {
            [$dari, $sampai] = [$sampai, $dari];
        }
        $sampaiEnd = $sampai->copy()->addDay(); // range eksklusif < besok

        // ── Kartu ringkasan PERIODE TERPILIH (mengikuti filter dari/sampai) ──
        // 2 aggregate; default filter = hari ini sehingga perilaku "hari ini"
        // tetap sama, tapi saat user ganti periode kartu ikut menyesuaikan.
        $dariStart = $dari->copy()->startOfDay();

        // `date` bertipe datetime → pakai range eksklusif `< besok` agar semua
        // movement pada hari `sampai` ikut terhitung (bukan hanya tengah malam).
        $stokPeriode = StockMovement::where('date', '>=', $dari->format('Y-m-d'))
            ->where('date', '<', $sampaiEnd->format('Y-m-d'))
            ->selectRaw("SUM(CASE WHEN type='in' THEN quantity ELSE 0 END) as masuk, SUM(CASE WHEN type='out' THEN quantity ELSE 0 END) as keluar")
            ->first();

        $orderPeriode = ShippingOrder::processed()
            ->where('created_at', '>=', $dariStart)
            ->where('created_at', '<', $sampaiEnd)
            ->selectRaw('COUNT(*) as total,
                SUM(CASE WHEN awb IS NOT NULL AND awb != \'\' THEN 1 ELSE 0 END) as resi,
                SUM(CASE WHEN payment_method = \'cod\' THEN 1 ELSE 0 END) as cod,
                SUM(CASE WHEN payment_method = \'bank_transfer\' THEN 1 ELSE 0 END) as bank_transfer')
            ->first();

        // ── Laporan per pengirim (rentang terpilih) — 1 aggregate ──
        $rows = ShippingOrder::processed()
            ->join('order_online_import_batches as b', 'b.id', '=', 'shipping_orders.order_online_import_batch_id')
            ->leftJoin('products', 'products.id', '=', 'shipping_orders.product_id')
            ->where('shipping_orders.created_at', '>=', $dari->copy()->startOfDay())
            ->where('shipping_orders.created_at', '<', $sampaiEnd)
            ->selectRaw('b.id as batch_id,
                b.sender as sender,
                COUNT(*) as total_order,
                SUM(CASE WHEN shipping_orders.awb IS NOT NULL AND shipping_orders.awb != \'\' THEN 1 ELSE 0 END) as resi,
                SUM(CASE WHEN shipping_orders.payment_method = \'cod\' THEN 1 ELSE 0 END) as cod,
                SUM(CASE WHEN shipping_orders.payment_method = \'bank_transfer\' THEN 1 ELSE 0 END) as bank_transfer,
                SUM(COALESCE(shipping_orders.amount, 0)) as uang_masuk,
                SUM(shipping_orders.quantity * COALESCE(products.purchase_price, 0)) as hpp')
            ->groupBy('b.id', 'b.sender')
            ->orderByDesc('uang_masuk')
            ->get();

        // Total keseluruhan = sum collection hasil GROUP BY (tanpa query tambahan)
        $total = (object) [
            'total_order' => $rows->sum('total_order'),
            'resi' => $rows->sum('resi'),
            'cod' => $rows->sum('cod'),
            'bank_transfer' => $rows->sum('bank_transfer'),
            'uang_masuk' => $rows->sum('uang_masuk'),
            'hpp' => $rows->sum('hpp'),
        ];

        return view('laporan.operasional', [
            'rows' => $rows,
            'total' => $total,
            'dari' => $dari->format('Y-m-d'),
            'sampai' => $sampai->format('Y-m-d'),
            'stokPeriode' => $stokPeriode,
            'orderPeriode' => $orderPeriode,
            'isToday' => $dari->isSameDay($today) && $sampai->isSameDay($today),
        ]);
    }

    /**
     * Detail per BATCH (satu baris "per pengirim" pada laporan = satu batch
     * import). Menampilkan barang yang terjual beserta RINCIAN VARIAN.
     *
     * Kunci: kacamata promo "Dapat N" disimpan sebagai `product_name = "... N pcs"`
     * dengan `quantity = N` — varian yang sama (mis. KMP+1.50) bisa terisi 2 pcs
     * atau 4 pcs. Karena itu GROUP BY menyertakan `product_name` + `quantity`
     * (qty per order) sehingga baris "2 pcs" dan "4 pcs" tampil TERPISAH.
     */
    public function show(OrderOnlineImportBatch $batch, Request $request): View
    {
        $today = Carbon::today();

        $dari = $this->parseDate($request->input('dari'), $today);
        $sampai = $this->parseDate($request->input('sampai'), $today);
        if ($sampai->lt($dari)) {
            [$dari, $sampai] = [$sampai, $dari];
        }
        $sampaiEnd = $sampai->copy()->addDay(); // range eksklusif < besok
        $dariStart = $dari->copy()->startOfDay();

        // ── Detail per produk + varian (1 aggregate; batch + index created_at) ──
        $rows = ShippingOrder::processed()
            ->where('order_online_import_batch_id', $batch->id)
            ->where('shipping_orders.created_at', '>=', $dariStart)
            ->where('shipping_orders.created_at', '<', $sampaiEnd)
            ->leftJoin('products', 'products.id', '=', 'shipping_orders.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'shipping_orders.product_variant_id')
            ->selectRaw("
                shipping_orders.product_id,
                shipping_orders.product_variant_id,
                shipping_orders.product_name as nama_terjual,
                shipping_orders.product_code as kode_terjual,
                shipping_orders.quantity as qty_per_order,
                COUNT(*) as total_order,
                SUM(shipping_orders.quantity) as qty,
                SUM(shipping_orders.amount) as uang_masuk,
                SUM(shipping_orders.quantity * COALESCE(products.purchase_price, 0)) as hpp,
                COALESCE(products.name, shipping_orders.product_name) as nama_master,
                COALESCE(products.code, shipping_orders.product_code) as kode_master,
                COALESCE(product_variants.power, 0) as power,
                COALESCE(product_variants.code, shipping_orders.product_code) as kode_varian
            ")
            ->groupBy(
                'shipping_orders.product_id',
                'shipping_orders.product_variant_id',
                'shipping_orders.product_name',
                'shipping_orders.product_code',
                'shipping_orders.quantity',
                'products.name',
                'products.code',
                'product_variants.power',
                'product_variants.code'
            )
            ->orderBy('kode_master')
            ->orderBy('power')
            ->orderByDesc('qty')
            ->get();

        // Ringkasan batch pada periode ini (total_order/qty/uang/HPP dari
        // collection; resi lewat 1 aggregate kecil)
        $summary = (object) [
            'total_order' => $rows->sum('total_order'),
            'qty' => $rows->sum('qty'),
            'uang_masuk' => $rows->sum('uang_masuk'),
            'hpp' => $rows->sum('hpp'),
            'resi' => ShippingOrder::processed()
                ->where('order_online_import_batch_id', $batch->id)
                ->where('created_at', '>=', $dariStart)
                ->where('created_at', '<', $sampaiEnd)
                ->whereNotNull('awb')
                ->where('awb', '!=', '')
                ->count(),
        ];

        return view('laporan.batch', [
            'batch' => $batch,
            'rows' => $rows,
            'summary' => $summary,
            'dari' => $dari->format('Y-m-d'),
            'sampai' => $sampai->format('Y-m-d'),
            'isToday' => $dari->isSameDay($today) && $sampai->isSameDay($today),
        ]);
    }

    /** Parse tanggal input (Y-m-d), fallback ke tanggal default. */
    protected function parseDate(?string $value, Carbon $fallback): Carbon
    {
        if ($value !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            } catch (\Throwable) {
                // fallthrough
            }
        }

        return $fallback->copy()->startOfDay();
    }
}
