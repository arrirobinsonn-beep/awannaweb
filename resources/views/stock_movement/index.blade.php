@extends('layouts.app')
@section('title','Jurnal Stok')
@section('page-title','📊 Jurnal Stok')
@section('page-subtitle','Ringkasan dan riwayat pergerakan stok (Masuk & Keluar)')

@push('styles')
<style>
.product-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
}
.product-card:hover {
    border-color: var(--color-primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transform: translateY(-2px);
}
.product-card.active {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px var(--color-primary-light);
    background: #f8fafc;
}
.pc-title { font-weight: 800; font-size: .95rem; color: #1f2937; margin-bottom: 4px; }
.pc-code { font-size: .75rem; color: #6b7280; margin-bottom: 12px; }
.pc-stats { display: flex; gap: 10px; border-top: 1px dashed #e5e7eb; padding-top: 10px; }
.pc-stat { flex: 1; }
.pc-stat-label { font-size: .65rem; color: #9ca3af; text-transform: uppercase; font-weight: 700; margin-bottom: 2px; }
.pc-stat-val { font-size: 1.1rem; font-weight: 900; }
.pc-stat-val.in { color: #10b981; }
.pc-stat-val.out { color: #ef4444; }
.pc-stat-val.stock { color: #3b82f6; }
.pc-stock-warn { background: #fef2f2; border-color: #fca5a5 !important; }
.pc-stock-warn .pc-stat-val.stock { color: #ef4444; }

.product-detail-section {
    display: none;
    margin-top: 24px;
    animation: fadeIn 0.3s ease;
}
.product-detail-section.active {
    display: block;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.variant-row { cursor: pointer; transition: background 0.15s; }
.variant-row:hover { background: #f9fafb; }
.variant-row.open { background: #f0fdf4; border-left: 3px solid #10b981; }
.detail-row { display: none; background: #fff; }
.detail-row.open { display: table-row; }
.chart-scroll-wrap { overflow-x:auto; padding-bottom:4px; cursor:grab; }
.chart-scroll-wrap:active { cursor:grabbing; }
.chart-scroll-wrap::-webkit-scrollbar { height:5px; }
.chart-scroll-wrap::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:4px; }
.chart-inner { min-width:100%; }
</style>
@endpush

@section('content')

{{-- Filter Form --}}
<div class="clay-card" style="padding:0;margin-bottom:20px;" data-reveal>
    <form method="GET" action="{{ route('stock-movement.index') }}" id="sm-drp-form"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:16px;">
        <x-date-range-picker :dari="$dari" :sampai="$sampai" form-id="sm-drp-form" />
        <button class="clay-btn clay-btn-primary" type="submit">🔍 Terapkan</button>
        <a href="{{ route('stock-movement.index') }}" class="clay-btn">Bulan Ini</a>
        <span style="font-size:.75rem;color:#9ca3af;margin-left:10px;">Periode: {{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</span>
    </form>
</div>

{{-- Grafik Tren Stok --}}
<div class="clay-card" style="padding:20px;margin-bottom:20px;" data-reveal>
    <div style="font-weight:800;font-size:1.05rem;color:#1e1b2e;margin-bottom:14px;">📈 Tren Pergerakan Stok</div>
    <div class="chart-scroll-wrap" id="chart-scroll-wrap">
        <div class="chart-inner" id="chart-inner">
            <canvas id="stockTrendChart" height="220"></canvas>
        </div>
    </div>
    <div style="font-size:.68rem;color:#c0c0c0;margin-top:8px;text-align:center;">
        💡 Geser grafik ke kiri/kanan untuk melihat rentang tanggal lebih banyak. Klik legend untuk sembunyikan/tampilkan.
    </div>
</div>


{{-- Grid Cards --}}
<div class="grid-stats" data-reveal>
    @foreach($productStats as $idx => $ps)
    @php
        $stockWarn = $ps->minStock > 0 && $ps->stock <= $ps->minStock;
    @endphp
    <div class="product-card{{ $stockWarn ? ' pc-stock-warn' : '' }}" onclick="showProductDetail({{ $ps->product->id }}, this)">
        <div class="pc-title">{{ $ps->product->name }}</div>
        <div class="pc-code">{{ $ps->product->code }}</div>
        @if($stockWarn)
            <div style="font-size:.65rem;color:#ef4444;font-weight:700;margin-bottom:8px;">⚠ Perlu Restock (min {{ number_format($ps->minStock,0,',','.') }})</div>
        @endif
        <div class="pc-stats">
            <div class="pc-stat">
                <div class="pc-stat-label">Stok</div>
                <div class="pc-stat-val stock">{{ number_format($ps->stock, 0, ',', '.') }}</div>
            </div>
            <div class="pc-stat">
                <div class="pc-stat-label">Masuk</div>
                <div class="pc-stat-val in">{{ number_format($ps->in, 0, ',', '.') }}</div>
            </div>
            <div class="pc-stat">
                <div class="pc-stat-label">Keluar</div>
                <div class="pc-stat-val out">{{ number_format($ps->out, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Detail Container --}}
<div id="details-container">
    @foreach($productStats as $ps)
    <div class="product-detail-section" id="detail-{{ $ps->product->id }}">
        <div class="clay-card" style="padding:0;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;background:#f8fafc;">
                <h3 style="margin:0;font-size:1.1rem;font-weight:800;color:#1f2937;">📝 Detail Varian: {{ $ps->product->name }}</h3>
                <div style="font-size:.75rem;color:#6b7280;margin-top:2px;">Pilih varian untuk melihat rincian pergerakan stok</div>
            </div>
            
            <div class="table-scroll">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Nama Varian</th>
                            <th>Kode</th>
                            <th style="text-align:right;">Stok</th>
                            <th style="text-align:right;">Masuk</th>
                            <th style="text-align:right;">Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ps->variantsData as $vd)
                        <tr class="variant-row" onclick="toggleDetail('var-{{ $vd->variant->id }}', this)">
                            <td style="text-align:center;color:#9ca3af;font-size:1.2rem;">▾</td>
                            <td style="font-weight:700;">
                                {{ $vd->variant->name }}
                                @if((float)$vd->variant->power > 0)
                                    <span style="color:#6b7280;font-weight:600;">(+{{ number_format($vd->variant->power,2,',','.') }})</span>
                                @endif
                            </td>
                            <td style="color:#6b7280;font-size:.8rem;">{{ $vd->variant->code }}</td>
                            <td style="text-align:right;font-weight:700;color:{{ $vd->stock > 0 ? '#3b82f6' : '#9ca3af' }};">{{ number_format($vd->stock, 0, ',', '.') }}</td>
                            <td style="text-align:right;color:#10b981;font-weight:700;">{{ number_format($vd->in, 0, ',', '.') }}</td>
                            <td style="text-align:right;color:#ef4444;font-weight:700;">{{ number_format($vd->out, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="detail-row" id="var-{{ $vd->variant->id }}">
                            <td colspan="6" style="padding:0;background:#f8fafc;">
                                @if($vd->movements->count())
                                <div style="padding:16px 20px 24px 40px;">
                                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;">
                                        <thead>
                                            <tr>
                                                <th style="text-align:left;padding-bottom:8px;border-bottom:2px solid #e5e7eb;color:#6b7280;">Tanggal</th>
                                                <th style="text-align:left;padding-bottom:8px;border-bottom:2px solid #e5e7eb;color:#6b7280;">Tipe</th>
                                                <th style="text-align:right;padding-bottom:8px;border-bottom:2px solid #e5e7eb;color:#6b7280;">Qty</th>
                                                <th style="text-align:left;padding-bottom:8px;border-bottom:2px solid #e5e7eb;color:#6b7280;padding-left:16px;">Sumber</th>
                                                <th style="text-align:left;padding-bottom:8px;border-bottom:2px solid #e5e7eb;color:#6b7280;">Keterangan</th>
                                                <th style="text-align:left;padding-bottom:8px;border-bottom:2px solid #e5e7eb;color:#6b7280;">Oleh</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($vd->movements as $m)
                                            <tr>
                                                <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;">{{ $m->date->format('d/m/Y') }}</td>
                                                <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;">
                                                    @if($m->type === 'in')
                                                        <span style="background:#dcfce7;color:#15803d;padding:2px 6px;border-radius:4px;font-weight:700;font-size:.65rem;">MASUK</span>
                                                    @else
                                                        <span style="background:#fee2e2;color:#b91c1c;padding:2px 6px;border-radius:4px;font-weight:700;font-size:.65rem;">KELUAR</span>
                                                    @endif
                                                </td>
                                                <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:right;font-weight:700;">{{ number_format($m->quantity, 0, ',', '.') }}</td>
                                                <td style="padding:8px 0 8px 16px;border-bottom:1px solid #e5e7eb;">{{ ucfirst($m->reference) }}</td>
                                                <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;color:#4b5563;">{{ $m->note ?? '-' }}</td>
                                                <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;">{{ $m->creator->nama ?? '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div style="padding:24px;text-align:center;color:#9ca3af;font-size:.85rem;">Tidak ada pergerakan stok untuk varian ini pada periode terpilih.</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const RAW = @json($chartData);
    
    // Setup Chart
    const wrap  = document.getElementById('chart-scroll-wrap');
    const inner = document.getElementById('chart-inner');
    const canvas = document.getElementById('stockTrendChart');
    if (wrap && canvas) {
        const MIN_CANVAS_W = Math.max(RAW.length * 56, wrap.clientWidth);
        inner.style.width = MIN_CANVAS_W + 'px';
        canvas.style.width  = '100%';
        canvas.style.height = '220px';

        const labels = RAW.map(r => r.date);
        const dIn = RAW.map(r => r.in);
        const dOut = RAW.map(r => r.out);

        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: dIn,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,.10)',
                        fill: true,
                        tension: .38,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        borderWidth: 2,
                    },
                    {
                        label: 'Barang Keluar',
                        data: dOut,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,.10)',
                        fill: true,
                        tension: .38,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        borderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, boxWidth: 8, font: {family: "'Nunito', sans-serif", weight: 'bold'} }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255,255,255,0.95)',
                        titleColor: '#1f2937',
                        bodyColor: '#4b5563',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        padding: 10,
                        titleFont: { size: 13, family: "'Nunito', sans-serif" },
                        bodyFont: { size: 12, family: "'Nunito', sans-serif", weight: 'bold' }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: '#f3f4f6', borderDash: [4, 4] } }
                }
            }
        });

        // Drag to scroll
        let isDown = false;
        let startX;
        let scrollLeft;
        wrap.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - wrap.offsetLeft;
            scrollLeft = wrap.scrollLeft;
        });
        wrap.addEventListener('mouseleave', () => { isDown = false; });
        wrap.addEventListener('mouseup', () => { isDown = false; });
        wrap.addEventListener('mousemove', (e) => {
            if(!isDown) return;
            e.preventDefault();
            const x = e.pageX - wrap.offsetLeft;
            const walk = (x - startX) * 2; // scroll-fast
            wrap.scrollLeft = scrollLeft - walk;
        });

        // Auto scroll to right
        wrap.scrollLeft = wrap.scrollWidth;
    }
});

function showProductDetail(prodId, cardEl) {
    // Sembunyikan semua detail
    document.querySelectorAll('.product-detail-section').forEach(el => {
        el.classList.remove('active');
    });
    // Hapus active dari semua card
    document.querySelectorAll('.product-card').forEach(el => {
        el.classList.remove('active');
    });

    // Tampilkan detail yang dipilih
    const target = document.getElementById('detail-' + prodId);
    if(target) {
        target.classList.add('active');
        // Scroll slightly if needed
        setTimeout(() => {
            target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
    
    // Tambahkan class active ke card
    if(cardEl) {
        cardEl.classList.add('active');
    }
}

function toggleDetail(varId, rowEl) {
    const detailRow = document.getElementById(varId);
    if(!detailRow) return;

    const isOpen = detailRow.classList.contains('open');
    
    // Tutup yang lain (opsional, jika ingin satu-satu terbuka)
    // document.querySelectorAll('.detail-row').forEach(el => el.classList.remove('open'));
    // document.querySelectorAll('.variant-row').forEach(el => {
    //    el.classList.remove('open');
    //    el.querySelector('td').innerText = '▾';
    // });

    if(isOpen) {
        detailRow.classList.remove('open');
        rowEl.classList.remove('open');
        rowEl.querySelector('td').innerText = '▾';
    } else {
        detailRow.classList.add('open');
        rowEl.classList.add('open');
        rowEl.querySelector('td').innerText = '▴';
    }
}
</script>
@endpush
