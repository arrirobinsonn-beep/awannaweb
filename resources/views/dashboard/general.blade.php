@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','📊 Dashboard')
@section('page-subtitle','Overview operasional admin')

@section('content')

@php
    $revTotal = $revenueBulan->total ?? 0;
    $revJml   = $revenueBulan->jumlah ?? 0;

    // COD vs BT totals for pie
    $codTotal = $orderPerPayment->where('payment_method', 'cod')->sum('jumlah');
    $btTotal  = $orderPerPayment->where('payment_method', 'bank_transfer')->sum('jumlah');

    $chartColors = ['#FF6B6B','#4ECDC4','#A78BFA','#FB923C','#34D399','#F472B6','#60A5FA','#FBBF24','#8B5CF6','#EC4899'];
@endphp

{{-- ═══════════════════════════════════════════════════════════
     ROW 1: 5 SUMMARY CARDS — Operasional
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-summary-row" data-reveal>
    {{-- Order Hari Ini --}}
    <div class="db-card db-card-green">
        <div class="db-card-header">
            <span class="db-card-icon">📦</span>
            <span class="db-card-label">Order Hari Ini</span>
        </div>
        <div class="db-card-value">{{ $opsHariIni['total'] }}</div>
        <div class="db-card-sub">🚚 {{ $opsHariIni['resi'] }} ber-resi</div>
    </div>

    {{-- Revenue Bulan Ini --}}
    <div class="db-card db-card-blue">
        <div class="db-card-header">
            <span class="db-card-icon">💰</span>
            <span class="db-card-label">Revenue {{ now()->translatedFormat('M') }}</span>
        </div>
        <div class="db-card-value">Rp {{ number_format($revTotal, 0, ',', '.') }}</div>
        <div class="db-card-sub">{{ $revJml }} order</div>
    </div>

    {{-- Stok Masuk Hari Ini --}}
    <a href="{{ route('stock-movement.index') }}" class="db-card db-card-teal" style="text-decoration:none;color:inherit;">
        <div class="db-card-header">
            <span class="db-card-icon">📥</span>
            <span class="db-card-label">Stok Hari Ini</span>
        </div>
        <div class="db-card-value">+{{ $opsHariIni['masuk'] }} · -{{ $opsHariIni['keluar'] }}</div>
        <div class="db-card-sub">masuk / keluar</div>
    </a>

    {{-- Pending Pembelian --}}
    <a href="{{ route('purchase.index') }}" class="db-card db-card-amber {{ $pendingApproval === 0 ? 'db-card-muted' : '' }}" style="text-decoration:none;color:inherit;">
        <div class="db-card-header">
            <span class="db-card-icon">⏳</span>
            <span class="db-card-label">Pengajuan Pending</span>
        </div>
        <div class="db-card-value">{{ $pendingApproval }}</div>
        <div class="db-card-sub">Menunggu acc</div>
    </a>

    {{-- Produk & Supplier --}}
    <div class="db-card db-card-purple">
        <div class="db-card-header">
            <span class="db-card-icon">🏭</span>
            <span class="db-card-label">Master Data</span>
        </div>
        <div class="db-card-value">{{ $stats['total_produk'] }} Produk</div>
        <div class="db-card-sub">{{ $stats['total_supplier'] }} Supplier</div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ROW 2: Revenue Harian (30 Hari) + Order per Kurir
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-grid-2" data-reveal>
    <div class="clay-card" style="padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <div>
                <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">💰 Revenue Harian (30 Hari)</div>
                <div style="font-size:.72rem;color:#9ca3af;">Total revenue dari order</div>
            </div>
            <span class="clay-badge clay-badge-green">Harian</span>
        </div>
        <div style="height:150px;margin-top:8px;position:relative;"><canvas id="chartRevenue"></canvas></div>
    </div>

    <div class="clay-card" style="padding:20px;">
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:4px;">📦 Order per Kurir</div>
        <div style="font-size:.72rem;color:#9ca3af;margin-bottom:10px;">Bulan ini</div>
        <div style="height:170px;position:relative;"><canvas id="chartCourier"></canvas></div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ROW 3: Stok In/Out (14 Hari) + COD vs BT
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-grid-2" data-reveal>
    <div class="clay-card" style="padding:20px;">
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:4px;">📊 Stok In/Out (14 Hari)</div>
        <div style="font-size:.72rem;color:#9ca3af;margin-bottom:10px;">Jurnal stok harian</div>
        <div style="height:170px;position:relative;"><canvas id="chartStock"></canvas></div>
    </div>

    <div class="clay-card" style="padding:20px;">
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:4px;">💳 COD vs Bank Transfer</div>
        <div style="font-size:.72rem;color:#9ca3af;margin-bottom:10px;">Bulan ini</div>
        <div style="height:170px;position:relative;"><canvas id="chartPayment"></canvas></div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ROW 4: Order Terbaru + Pengiriman Terakhir
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-grid-2" data-reveal>
    {{-- Recent Orders --}}
    <div class="clay-card" style="padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div>
                <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">📋 Order Terbaru (7 Hari)</div>
                <div style="font-size:.72rem;color:#9ca3af;">{{ $recentOrders->count() }} order</div>
            </div>
            <a href="{{ route('orders.index') }}" class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.75rem;">Lihat →</a>
        </div>
        <div class="table-scroll">
            <table class="clay-table">
                <thead><tr>
                    <th>Order ID</th><th>Penerima</th><th>Produk</th>
                    <th style="text-align:right;">Amount</th><th>Status</th><th>Kurir</th>
                </tr></thead>
                <tbody>
                @forelse($recentOrders as $o)
                <tr>
                    <td style="font-weight:600;font-size:.78rem;">{{ $o->order_id }}</td>
                    <td style="font-size:.82rem;">{{ Str::limit($o->customer_name ?? '-', 18) }}</td>
                    <td style="font-size:.82rem;">{{ Str::limit($o->product_name ?? '-', 18) }}</td>
                    <td style="text-align:right;font-weight:600;font-size:.82rem;color:var(--color-primary);">Rp {{ number_format($o->amount??0,0,',','.') }}</td>
                    <td>
                        @php
                            $statusClass = match($o->status) {
                                'real' => 'clay-badge-green',
                                'tembakan' => 'clay-badge-blue',
                                'cancel' => 'clay-badge-red',
                                default => 'clay-badge-gray',
                            };
                        @endphp
                        <span class="clay-badge {{ $statusClass }}" style="font-size:.68rem;">{{ ucfirst($o->status) }}</span>
                    </td>
                    <td style="font-size:.78rem;font-weight:600;">{{ strtoupper($o->courier ?? '-') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:20px;color:#9ca3af;">Belum ada order 7 hari terakhir</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pengiriman Terakhir --}}
    <div class="clay-card" style="padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div>
                <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">🚚 Pengiriman Terakhir</div>
                <div style="font-size:.72rem;color:#9ca3af;">7 hari terakhir</div>
            </div>
            <a href="{{ route('shipment.index') }}" class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.75rem;">Lihat →</a>
        </div>
        <div class="table-scroll">
            <table class="clay-table">
                <thead><tr>
                    <th>No Resi</th><th>Kurir</th><th>Penerima</th>
                    <th>Status</th><th>Tgl Kirim</th>
                </tr></thead>
                <tbody>
                @forelse($recentShipments as $s)
                <tr>
                    <td style="font-weight:600;font-size:.78rem;">{{ Str::limit($s->tracking_number, 18) }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ strtoupper($s->courier ?? '-') }}</td>
                    <td style="font-size:.82rem;">{{ Str::limit($s->recipient_name ?? '-', 18) }}</td>
                    <td>
                        @php
                            $sClass = match($s->status ?? '') {
                                'delivered' => 'clay-badge-green',
                                'in_transit' => 'clay-badge-blue',
                                'returned' => 'clay-badge-red',
                                default => 'clay-badge-gray',
                            };
                        @endphp
                        <span class="clay-badge {{ $sClass }}" style="font-size:.68rem;">{{ ucfirst($s->status ?? '-') }}</span>
                    </td>
                    <td style="font-size:.78rem;color:#6b7280;">{{ $s->pickup_date ? $s->pickup_date->format('d/m') : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:20px;color:#9ca3af;">Belum ada pengiriman</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ROW 5: Barang Masuk Terakhir + Stok Menipis
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-grid-2" data-reveal>
    {{-- Pembelian Terakhir --}}
    <div class="clay-card" style="padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div>
                <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">📥 Barang Masuk Terakhir</div>
                <div style="font-size:.72rem;color:#9ca3af;">Pembelian terbaru</div>
            </div>
            <a href="{{ route('purchase.index') }}" class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.75rem;">Lihat →</a>
        </div>
        @forelse($recentPurchases as $pu)
        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;margin-bottom:6px;background:#f0fdf4;">
            <span style="font-size:1.2rem;">📦</span>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.82rem;">{{ $pu->variant->product->name ?? '-' }} {{ $pu->variant->power ? '+'.$pu->variant->power : '' }}</div>
                <div style="font-size:.68rem;color:#9ca3af;">{{ $pu->quantity }} pcs · Rp {{ number_format($pu->unit_price??0,0,',','.') }} · {{ $pu->created_at->diffForHumans() }}</div>
            </div>
            @php
                $puClass = match($pu->status) {
                    'received' => 'clay-badge-green',
                    'approved' => 'clay-badge-blue',
                    'pending' => 'clay-badge-yellow',
                    'rejected' => 'clay-badge-red',
                    default => 'clay-badge-gray',
                };
                $puIcon = match($pu->status) {
                    'pending' => '⏳',
                    'approved' => '✅',
                    'received' => '📦',
                    'rejected' => '❌',
                    default => '-',
                };
            @endphp
            <span class="clay-badge {{ $puClass }}" style="font-size:.66rem;">{{ $puIcon }} {{ ucfirst($pu->status) }}</span>
        </div>
        @empty
        <div style="text-align:center;padding:24px 0;">
            <div style="font-size:2rem;margin-bottom:8px;">📥</div>
            <div style="font-size:.85rem;color:#9ca3af;">Belum ada pembelian</div>
        </div>
        @endforelse
    </div>

    {{-- Stok Menipis --}}
    <div class="clay-card" style="padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div>
                <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">⚠️ Stok Menipis</div>
                <div style="font-size:.72rem;color:#9ca3af;">Produk di bawah min. stok</div>
            </div>
            <a href="{{ route('gudang.index') }}" class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.75rem;">Gudang →</a>
        </div>
        @forelse($lowStockProducts as $ls)
        @php
            $totalStok = $ls->variants->sum('stock');
            $pct = $ls->min_stock > 0 ? round($totalStok / $ls->min_stock * 100) : 0;
            $barColor = $pct <= 30 ? '#dc2626' : ($pct <= 60 ? '#f59e0b' : '#059669');
        @endphp
        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;margin-bottom:6px;background:{{ $pct <= 30 ? '#fef2f2' : '#fffbeb' }};">
            <span style="font-size:1.2rem;">{{ $pct <= 30 ? '🔴' : '🟡' }}</span>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.82rem;">{{ $ls->name }} ({{ $ls->code }})</div>
                <div style="font-size:.68rem;color:#9ca3af;">Stok: {{ $totalStok }} / Min: {{ $ls->min_stock }}</div>
                <div style="height:4px;border-radius:999px;background:#e5e7eb;overflow:hidden;margin-top:4px;">
                    <div style="height:4px;border-radius:999px;background:{{ $barColor }};width:{{ min($pct, 100) }}%;"></div>
                </div>
            </div>
            <span style="font-size:.72rem;font-weight:700;color:{{ $barColor }};">{{ $pct }}%</span>
        </div>
        @empty
        <div style="text-align:center;padding:24px 0;">
            <div style="font-size:2rem;margin-bottom:8px;">✅</div>
            <div style="font-size:.85rem;color:#9ca3af;">Semua stok aman</div>
        </div>
        @endforelse
    </div>
</div>

@endsection

{{-- ═══════════════════════════════════════════════════════════
     STYLES
     ═══════════════════════════════════════════════════════════ --}}
@push('styles')
<style>
    .db-summary-row {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }
    @media (max-width: 1200px) { .db-summary-row { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 767px) { .db-summary-row { grid-template-columns: repeat(2, 1fr); gap: 10px; } }

    .db-card {
        border-radius: 16px;
        padding: 16px 18px;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .db-card:hover { transform: translateY(-2px); }
    .db-card-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .db-card-icon { font-size: 1.3rem; }
    .db-card-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; opacity: .8; letter-spacing: .03em; }
    .db-card-value { font-size: 1.25rem; font-weight: 900; line-height: 1.2; }
    .db-card-sub { font-size: .7rem; opacity: .75; margin-top: 4px; }

    .db-card-blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); box-shadow: 5px 5px 0 #2563eb; color: #fff; }
    .db-card-green { background: linear-gradient(135deg, #10b981, #34d399); box-shadow: 5px 5px 0 #059669; color: #fff; }
    .db-card-red { background: linear-gradient(135deg, #FF6B6B, #FF9A9A); box-shadow: 5px 5px 0 #e05555; color: #fff; }
    .db-card-amber { background: linear-gradient(135deg, #f59e0b, #fbbf24); box-shadow: 5px 5px 0 #d97706; color: #fff; }
    .db-card-purple { background: linear-gradient(135deg, #A78BFA, #C4B5FD); box-shadow: 5px 5px 0 #7c5cf5; color: #fff; }
    .db-card-teal { background: linear-gradient(135deg, #4ECDC4, #88DED8); box-shadow: 5px 5px 0 #3ab8b0; color: #fff; }
    .db-card-muted { opacity: .7; }
    .db-card-muted:hover { opacity: 1; }

    .db-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }
    @media (max-width: 767px) {
        .db-grid-2 { grid-template-columns: 1fr; gap: 12px; }
        .db-card-value { font-size: 1.05rem; }
    }
</style>
@endpush

{{-- ═══════════════════════════════════════════════════════════
     SCRIPTS: Chart.js
     ═══════════════════════════════════════════════════════════ --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyleWidth = 8;
    Chart.defaults.plugins.legend.labels.padding = 14;
    Chart.defaults.elements.point.radius = 2;
    Chart.defaults.elements.point.hoverRadius = 5;

    const colors = @json($chartColors);
    const fmtRp = v => 'Rp ' + Number(v).toLocaleString('id-ID');

    // ── 1) Revenue Harian (30 Hari) ──
    const revenueData = @json($chartRevenue30);
    const revenueCtx = document.getElementById('chartRevenue');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(d => {
                    const dt = new Date(d.tanggal);
                    return dt.getDate() + '/' + (dt.getMonth()+1);
                }),
                datasets: [{
                    label: 'Revenue',
                    data: revenueData.map(d => d.total),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.12)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#10b981',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => fmtRp(ctx.parsed.y) } } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                    y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => v >= 1000000 ? (v/1000000).toFixed(0)+'jt' : v >= 1000 ? (v/1000).toFixed(0)+'rb' : v } }
                }
            }
        });
    }

    // ── 2) Order per Courier (doughnut) ──
    const courierData = @json($orderPerCourier);
    const courierCtx = document.getElementById('chartCourier');
    if (courierCtx && courierData.length > 0) {
        new Chart(courierCtx, {
            type: 'doughnut',
            data: {
                labels: courierData.map(d => (d.courier || 'N/A').toUpperCase()),
                datasets: [{
                    data: courierData.map(d => d.jumlah),
                    backgroundColor: colors.slice(0, courierData.length),
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 10, font: { size: 10 } } },
                    tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' order' } }
                }
            }
        });
    }

    // ── 3) Order per Payment Method (doughnut) ──
    const paymentCtx = document.getElementById('chartPayment');
    if (paymentCtx) {
        const paymentLabels = [];
        const paymentValues = [];
        const paymentColors = [];
        const codVal = {{ $codTotal }};
        const btVal  = {{ $btTotal }};
        if (codVal > 0) { paymentLabels.push('COD'); paymentValues.push(codVal); paymentColors.push('#FB923C'); }
        if (btVal > 0) { paymentLabels.push('Bank Transfer'); paymentValues.push(btVal); paymentColors.push('#4ECDC4'); }

        if (paymentValues.length > 0) {
            new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: paymentLabels,
                    datasets: [{
                        data: paymentValues,
                        backgroundColor: paymentColors,
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 10, font: { size: 10 } } },
                        tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' order' } }
                    }
                }
            });
        }
    }

    // ── 4) Stok In/Out (14 Hari) ──
    const stockData = @json($chartStock14);
    const stockCtx = document.getElementById('chartStock');
    if (stockCtx) {
        new Chart(stockCtx, {
            type: 'bar',
            data: {
                labels: stockData.map(d => {
                    const dt = new Date(d.date);
                    return dt.getDate() + '/' + (dt.getMonth()+1);
                }),
                datasets: [
                    {
                        label: 'Masuk',
                        data: stockData.map(d => d.masuk),
                        backgroundColor: 'rgba(78,205,196,0.75)',
                        borderColor: '#3ab8b0',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Keluar',
                        data: stockData.map(d => d.keluar),
                        backgroundColor: 'rgba(255,107,107,0.75)',
                        borderColor: '#e05555',
                        borderWidth: 1,
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 10, font: { size: 10 } } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } },
                    y: { grid: { color: 'rgba(0,0,0,0.04)' }, beginAtZero: true }
                }
            }
        });
    }
});
</script>
@endpush
