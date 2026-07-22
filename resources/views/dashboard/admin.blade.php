@extends('layouts.app')
@section('title','Dashboard Admin')
@section('page-title','📊 Dashboard Admin')
@section('page-subtitle','Overview paket & pengiriman')

@section('content')

{{-- Source tabs --}}
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;">
    @php
        $sources = ['all' => 'Semua', 'FLIK' => 'FLIK', 'SPX' => 'SPX', 'SICEPAT' => 'SICEPAT'];
    @endphp
    @foreach($sources as $val => $label)
    <a href="{{ route('dashboard', ['source' => $val]) }}"
       class="clay-btn {{ $source === $val ? 'clay-btn-primary' : 'clay-btn-outline' }}"
       style="font-size:.78rem;padding:6px 14px;">
        {{ $label }}
    </a>
    @endforeach
</div>

<div class="grid-stats" style="margin-bottom:20px;">
    @php
        $colors = ['#3b82f6','#f59e0b','#ef4444','#06b6d4','#10b981','#dc2626'];
        $idx = 0;
    @endphp
    @foreach($paketStats as $key => $stat)
    @php
        $c = $colors[$idx % count($colors)];
        $darker = '#'.dechex(max(0, hexdec(substr($c,1,2))-30)).dechex(max(0, hexdec(substr($c,3,2))-30)).dechex(max(0, hexdec(substr($c,5,2))-30));
        $idx++;
    @endphp
    <div class="stat-card" data-key="{{ $key }}"
         onclick="loadDetail('{{ $key }}')"
         style="cursor:pointer;background:linear-gradient(135deg,{{ $c }}dd,{{ $c }}88);box-shadow:4px 4px 0 {{ $darker }};border-color:{{ $darker }};color:#fff;transition:all .25s ease;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='6px 6px 0 {{ $darker }}';"
         onmouseout="if(!this.classList.contains('active-card')){this.style.transform='none';this.style.boxShadow='4px 4px 0 {{ $darker }}';}">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">{{ $stat['label'] }}</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stat['total'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">{{ $stat['icon'] }} Paket</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;pointer-events:none;">{{ $stat['icon'] }}</div>
    </div>
    @endforeach
</div>

{{-- Stok Kritis --}}
<div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="{{ route('gudang.stok') }}" class="clay-card-sm" style="padding:12px 18px;display:flex;align-items:center;gap:10px;text-decoration:none;background:linear-gradient(135deg,#fef2f2,#fee2e2);border:2px solid #fecaca;flex:1;">
        <span style="font-size:1.4rem;">⚠️</span>
        <div>
            <div style="font-size:.85rem;font-weight:800;color:#dc2626;">{{ $stokKritis }} Stok Kritis</div>
            <div style="font-size:.7rem;color:#9ca3af;">Produk dengan stok ≤ 10 — klik untuk kelola</div>
        </div>
    </a>
    <div class="clay-card-sm" style="padding:12px 18px;display:flex;align-items:center;gap:10px;background:#fff;border:2px dashed #d1d5db;min-width:200px;">
        <span style="font-size:1.4rem;">📥</span>
        <div>
            <div style="font-size:.8rem;font-weight:700;color:#6b7280;">Upload Excel Status</div>
            <div style="font-size:.68rem;color:#9ca3af;">Update status paket (coming soon)</div>
        </div>
    </div>
</div>

{{-- Detail container --}}
<div id="detail-container" style="display:none;"></div>

<style>
.stat-card.active-card {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
}
.stat-card.active-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 4px;
    background: rgba(255,255,255,0.5);
    border-radius: 0 0 20px 20px;
}
.detail-table { width:100%; border-collapse:collapse; font-size:.72rem; }
.detail-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#6b7280; font-weight:700; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
.detail-table td { padding:7px 10px; border-bottom:1px solid #f1f5f9; }
.detail-table tr:hover td { background:#f8fafc; }
</style>

<script>
var activeKategori = null;
var currentSource = '{{ $source }}';

function loadDetail(kategori) {
    if (activeKategori === kategori) {
        document.getElementById('detail-container').style.display = 'none';
        document.querySelectorAll('.stat-card').forEach(function(c) { c.classList.remove('active-card'); c.style.transform = 'none'; c.style.boxShadow = ''; });
        activeKategori = null;
        return;
    }

    activeKategori = kategori;
    var container = document.getElementById('detail-container');

    document.querySelectorAll('.stat-card').forEach(function(c) {
        c.classList.remove('active-card');
        c.style.transform = 'none';
        c.style.boxShadow = '';
        if (c.dataset.key === kategori) c.classList.add('active-card');
    });

    container.innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af;">⏳ Memuat data...</div>';
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });

    fetch('{{ route('dashboard.paket-detail') }}?kategori=' + kategori + '&source=' + currentSource)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success || !res.records.length) {
                container.innerHTML = '<div class="clay-card" style="padding:40px;text-align:center;color:#9ca3af;font-size:.8rem;">Tidak ada data untuk kategori ini.</div>';
                return;
            }

            var html = '<div class="clay-card" style="padding:16px;overflow-x:auto;">';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">';
            html += '    <div style="font-weight:800;font-size:.85rem;">📋 Detail ' + getLabel(kategori) + ' <span style="font-weight:400;color:#9ca3af;font-size:.7rem;">(' + res.total + ' record)</span></div>';
            html += '    <button class="clay-btn clay-btn-xs clay-btn-outline" onclick="closeDetail()">✕ Tutup</button>';
            html += '</div>';
            html += '<table class="detail-table">';
            html += '<thead><tr><th>AWB</th><th>Kurir</th><th>Status</th><th>Tanggal</th><th>Produk</th><th>Shopper</th><th>Kota</th><th class="text-right">Harga</th></tr></thead><tbody>';

            res.records.forEach(function(r) {
                html += '<tr><td><strong>' + (r.awb || '-') + '</strong></td><td>' + (r.kurir || '-') + '</td><td>' + (r.status || '-') + '</td><td>' + (r.tanggal || '-') + '</td><td>' + (r.nama_produk || '-') + '</td><td>' + (r.nama_shopper || '-') + '</td><td>' + (r.kota || '-') + '</td><td class="text-right">' + (r.harga ? 'Rp ' + numberFormat(r.harga) : '-') + '</td></tr>';
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        })
        .catch(function(err) {
            container.innerHTML = '<div class="clay-card" style="padding:40px;text-align:center;color:#dc2626;font-size:.8rem;">Gagal memuat data: ' + err.message + '</div>';
        });
}

function closeDetail() {
    document.getElementById('detail-container').style.display = 'none';
    document.querySelectorAll('.stat-card').forEach(function(c) { c.classList.remove('active-card'); c.style.transform = 'none'; c.style.boxShadow = ''; });
    activeKategori = null;
}

function getLabel(key) {
    var labels = { 'total_paket':'Total Paket', 'proses_retur':'Proses Retur', 'retur':'Retur', 'proses_kirim':'Proses Pengiriman', 'terkirim':'Terkirim', 'bermasalah':'Bermasalah' };
    return labels[key] || key;
}

function numberFormat(x) {
    if (x === null || x === undefined) return '0';
    return Math.round(x).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-counter]').forEach(function(el) {
        var target = parseInt(el.dataset.counter);
        var current = 0;
        var step = Math.max(1, Math.floor(target / 30));
        var interval = setInterval(function() {
            current += step;
            if (current >= target) { current = target; clearInterval(interval); }
            el.textContent = numberFormat(current);
        }, 30);
    });
});
</script>
@endsection