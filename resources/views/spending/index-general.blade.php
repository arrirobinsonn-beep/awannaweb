@extends('layouts.app')
@section('title','Spending Harian')
@section('page-title','💸 Spending Harian')
@section('page-subtitle','Data pengeluaran iklan semua advertiser')

@section('content')

@if($advertisers->isEmpty())
<div class="clay-card" style="padding:48px;text-align:center;" data-reveal>
    <div style="font-size:2.5rem;margin-bottom:8px;">💸</div>
    <p style="color:#9ca3af;">Belum ada data spending di periode ini.</p>
</div>
@else

@php
    // ── Persiapan kartu summary utk tab aktif ──
    $pr = (float) ($summary['paid_ratio'] ?? 0);
    $prFill = $pr >= 75 ? 'linear-gradient(90deg,#22c55e,#16a34a)'
            : ($pr >= 50 ? 'linear-gradient(90deg,#fbbf24,#f59e0b)'
            : 'linear-gradient(90deg,#ef4444,#dc2626)');
    $periodeLabel = $dari === $sampai
        ? \Carbon\Carbon::parse($dari)->translatedFormat('d M Y')
        : \Carbon\Carbon::parse($dari)->translatedFormat('d M Y').' – '.\Carbon\Carbon::parse($sampai)->translatedFormat('d M Y');
    $daysCount = $tabSummaries->count();
    $daysLabel = $daysCount.' hari berisi data · '.$periodeLabel;
    $conv = ($summary['lead'] ?? 0) > 0 ? round(($summary['paid'] ?? 0) / $summary['lead'] * 100, 1) : 0;

    $tabKey = $activeTab === 'all' ? 'all' : (int) $activeTab;

    // Link tab: pertahankan filter dari/sampai
    $tabHref = function ($tab) {
        $q = request()->query();
        $q['tab'] = $tab;
        return url()->current().'?'.http_build_query($q);
    };
@endphp

{{-- ═══════════════ RINGKASAN PERIODE: CHART + 4 KARTU (ikut tab aktif) ═══════════════ --}}
<div class="summary-overview" data-reveal>

    {{-- KIRI: Chart Line Lead & Paid per Tanggal --}}
    <div class="chart-card">
        <div class="chart-header">
            <span class="chart-title">📊 Tren Lead & Paid</span>
            <span class="chart-sub">{{ $periodeLabel }} · Lead/Paid Running & Testing</span>
        </div>
        <div class="chart-body">
            <canvas id="spendingChartGeneral"></canvas>
        </div>
    </div>

    {{-- KANAN: 4 Card Summary (2×2) — data tab aktif --}}
    <div class="summary-grid">
        {{-- 1. Total Spending --}}
        <div class="summary-card">
            <div class="summary-icon sc-primary">💰</div>
            <div class="summary-body">
                <div class="summary-label">Total Spending</div>
                <div class="summary-value">Rp {{ number_format($summary['spending'],0,',','.') }}</div>
                <div class="summary-sub">{{ $daysLabel }}</div>
            </div>
        </div>

        {{-- 2. Total Lead / Paid --}}
        <div class="summary-card">
            <div class="summary-icon sc-purple">👥</div>
            <div class="summary-body">
                <div class="summary-label">Total Lead / Paid</div>
                <div class="summary-value">
                    <span class="sc-lead">{{ number_format($summary['lead']) }}</span>
                    <span class="sc-sep">/</span>
                    <span class="sc-paid">{{ number_format($summary['paid']) }}</span>
                </div>
                <div class="summary-sub">Konversi {{ $conv }}% dari lead</div>
            </div>
        </div>

        {{-- 3. CPA Lead / CPA Paid --}}
        <div class="summary-card">
            <div class="summary-icon sc-teal">📈</div>
            <div class="summary-body">
                <div class="summary-label">CPA Lead / Paid</div>
                <div class="summary-value">
                    <span class="sc-lead">Rp {{ number_format($summary['cpa_lead'],0,',','.') }}</span>
                    <span class="sc-sep">/</span>
                    <span class="sc-paid">Rp {{ number_format($summary['cpa_paid'],0,',','.') }}</span>
                </div>
                <div class="summary-sub">Biaya per lead & per pembayaran</div>
            </div>
        </div>

        {{-- 4. Paid Ratio --}}
        <div class="summary-card">
            <div class="summary-icon sc-amber">🎯</div>
            <div class="summary-body">
                <div class="summary-label">Paid Ratio</div>
                <div class="summary-value">{{ number_format($pr) }}%</div>
                <div class="summary-ratio-track">
                    <div class="summary-ratio-fill" style="{{ $prFill }}"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Folder Tabs: "Semua spending" (paling kiri) + per advertiser ───────── --}}
{{-- Carousel horizontal: tab lebih dari lebar tabel disembunyikan, geser untuk melihat --}}
<div class="tab-scroll"
     style="display:flex;gap:0;align-items:flex-end;
            margin-bottom:-2px;position:relative;z-index:2;" data-reveal>

    <a href="{{ $tabHref('all') }}"
       style="padding:9px 18px 11px;text-decoration:none;flex-shrink:0;white-space:nowrap;
              border:2px solid {{ $activeTab === 'all' ? 'rgba(255,107,107,.25)' : 'rgba(0,0,0,.08)' }};
              border-bottom:2px solid {{ $activeTab === 'all' ? '#fff' : 'rgba(0,0,0,.08)' }};
              border-radius:14px 14px 0 0;
              background:{{ $activeTab === 'all' ? '#fff' : '#f5f5f5' }};
              font-family:inherit;font-size:.82rem;
              font-weight:{{ $activeTab === 'all' ? '700' : '500' }};
              color:{{ $activeTab === 'all' ? 'var(--color-primary,#FF6B6B)' : '#6b7280' }};
              cursor:pointer;transition:all .2s;
              display:flex;align-items:center;gap:8px;
              margin-right:4px;position:relative;z-index:{{ $activeTab === 'all' ? 3 : 1 }};">
        📋 Semua spending
        <span style="font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;
                     background:{{ $activeTab === 'all' ? 'rgba(255,107,107,.12)' : 'rgba(0,0,0,.06)' }};
                     color:{{ $activeTab === 'all' ? 'var(--color-primary)' : '#9ca3af' }};">
            Rp {{ number_format($allTotalSpending/1000,0,',','.') }}k
        </span>
    </a>

    @foreach($advertisers as $adv)
    @php
        $isActive = ($activeTab == $adv->id);
        // Tab ber-warning merah bila advertiser ini punya ketidaksesuaian data
        $hasDisc = ($dataPerAdvertiser[$adv->id]['has_discrepancy'] ?? false);
        $tabBorder = $isActive
            ? ($hasDisc ? 'rgba(239,68,68,.5)' : 'rgba(255,107,107,.25)')
            : ($hasDisc ? 'rgba(239,68,68,.4)' : 'rgba(0,0,0,.08)');
    @endphp
    <a href="{{ $tabHref($adv->id) }}"
       style="padding:9px 18px 11px;text-decoration:none;flex-shrink:0;white-space:nowrap;
              border:2px solid {{ $tabBorder }};
              border-bottom:2px solid {{ $isActive ? '#fff' : $tabBorder }};
              border-radius:14px 14px 0 0;
              background:{{ $isActive ? '#fff' : ($hasDisc ? '#fef2f2' : '#f5f5f5') }};
              font-family:inherit;font-size:.82rem;
              font-weight:{{ ($isActive || $hasDisc) ? '700' : '500' }};
              color:{{ $hasDisc ? '#dc2626' : ($isActive ? 'var(--color-primary,#FF6B6B)' : '#6b7280') }};
              cursor:pointer;transition:all .2s;
              display:flex;align-items:center;gap:8px;
              margin-right:4px;position:relative;z-index:{{ $isActive ? 3 : 1 }};">
        @if($hasDisc)
        <span style="font-size:.8rem;flex-shrink:0;" title="Ada ketidaksesuaian data">⚠️</span>
        @endif
        <img src="{{ $adv->avatar_url }}"
             style="width:22px;height:22px;border-radius:6px;object-fit:cover;flex-shrink:0;
                    border:{{ $isActive ? '1.5px solid '.($hasDisc ? 'rgba(239,68,68,.5)' : 'rgba(255,107,107,.3)') : '1.5px solid #ddd' }};">
        {{ $adv->display_name }}
        <span style="font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;
                     background:{{ $hasDisc ? 'rgba(239,68,68,.12)' : ($isActive ? 'rgba(255,107,107,.12)' : 'rgba(0,0,0,.06)') }};
                     color:{{ $hasDisc ? '#dc2626' : ($isActive ? 'var(--color-primary)' : '#9ca3af') }};">
            Rp {{ number_format(($totalPerAdv[$adv->id] ?? 0)/1000,0,',','.') }}k
        </span>
    </a>
    @endforeach
</div>

<div style="border:2px solid rgba(255,107,107,.18);border-radius:0 16px 16px 16px;
            background:#fff;overflow:hidden;position:relative;z-index:1;" data-reveal>

    {{-- ⚠️ Banner discrepancy HANYA di tab advertiser (tab "Semua spending" cukup warning di tab) --}}
    @if($activeTab !== 'all' && ($dataPerAdvertiser[(int) $activeTab]['has_discrepancy'] ?? false))
    @php $data = $dataPerAdvertiser[(int) $activeTab]; @endphp
    <div class="clay-alert clay-alert-error" style="margin:12px 16px;" data-reveal>
        <span>🚨</span>
        <div style="flex:1;font-size:.78rem;">
            @if(count($data['discrepancies']) > 0)
            <strong>Ketidaksesuaian Data!</strong> Lead/Paid Regional tidak sama dengan Spending Harian.
            @if(count($data['discrepancies']) > 5)
            <div style="margin-top:5px;font-size:.68rem;color:#b91c1c;font-weight:600;">
                ⬇ Menampilkan 5 dari {{ count($data['discrepancies']) }} tanggal — scroll untuk melihat sisanya
            </div>
            @endif
            <div style="margin-top:3px;max-height:102px;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:#d1d5db transparent;padding-right:6px;">
                @foreach($data['discrepancies'] as $tgl => $d)
                <div style="margin-top:3px;font-size:.74rem;line-height:1.45;">
                    📅 {{ \Carbon\Carbon::parse($tgl)->translatedFormat('d M') }} —
                    Regional: Lead {{ $d['regional_lead'] }}, Paid {{ $d['regional_paid'] }} |
                    Spending: Lead {{ $d['spending_lead'] }}, Paid {{ $d['spending_paid'] }}
                </div>
                @endforeach
            </div>
            @endif

            @php
                $mSpend = collect($data['missing_spending_dates'] ?? [])->map(fn() => 'spending');
                $mReg = collect($data['missing_regional_dates'] ?? [])->map(fn() => 'regional');
                $allMissing = $mSpend->merge($mReg)->sortKeys()->all();
                $totalMissing = count($allMissing);
            @endphp
            @if($totalMissing > 0)
            @if(count($data['discrepancies']) > 0)
            <div style="border-top:1px dashed rgba(255,107,107,.35);margin-top:8px;padding-top:8px;"></div>
            @endif
            <strong>Data Belum Ditambahkan</strong>
            @if($totalMissing > 5)
            <div style="margin-top:5px;font-size:.68rem;color:#b91c1c;font-weight:600;">
                ⬇ Menampilkan 5 dari {{ $totalMissing }} tanggal — scroll untuk melihat sisanya
            </div>
            @endif
            <div style="margin-top:3px;max-height:102px;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:#d1d5db transparent;padding-right:6px;">
                @foreach(array_keys($allMissing) as $tgl)
                @php
                    $tglLbl = (int) substr($tgl, 8, 2) . ' ' . ['1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April', '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus', '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'][(int) substr($tgl, 5, 2)] . ' ' . substr($tgl, 0, 4);
                    $src = $allMissing[$tgl];
                @endphp
                <div style="margin-top:3px;font-size:.74rem;line-height:1.45;">
                    📅 {{ $tglLbl }} —
                    @if($src === 'spending')
                    Belum mengisi data spending iklan tanggal {{ $tglLbl }}
                    @else
                    Data regional belum diisi untuk tanggal {{ $tglLbl }}
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Sub-tab Running/Testing (lebar penuh, dibagi 2) di dalam kartu tabel ── --}}
    <div class="table-subtabs">
        <button type="button" id="subtab-running" class="table-subtab active" onclick="switchSubTab('running')">
            <span class="ts-ico">🟢</span>
            <span class="ts-label">Running</span>
            <span class="ts-count">{{ $runningSummaries->count() }}</span>
        </button>
        <button type="button" id="subtab-testing" class="table-subtab" onclick="switchSubTab('testing')">
            <span class="ts-ico">🔬</span>
            <span class="ts-label">Testing</span>
            <span class="ts-count">{{ $testingSummaries->count() }}</span>
        </button>
    </div>

    {{-- Tabel per sub-tab (Running default tampil, Testing disembunyikan) --}}
    @include('spending._table_general', ['summaries' => $runningSummaries, 'tabKey' => $tabKey, 'tabDisc' => $tabDisc, 'activeTab' => $activeTab, 'emptyText' => 'Tidak ada spending produk running di periode ini', 'wrapperId' => 'spending-general-running'])
    @include('spending._table_general', ['summaries' => $testingSummaries, 'tabKey' => $tabKey, 'tabDisc' => $tabDisc, 'activeTab' => $activeTab, 'emptyText' => 'Tidak ada spending produk testing di periode ini', 'wrapperId' => 'spending-general-testing', 'hidden' => true])
</div>

@endif

{{-- ═══════════════ FAB: Filter Rentang Waktu (tanpa tombol input — admin tidak input spending) ═══════════════ --}}
<div class="fab-container" id="fab-container">
    <div class="fab-group">
        <div class="fab-drp-wrap">
            <form method="GET" action="{{ route('spending.index') }}" id="filter-form-gen-fab">
                <x-date-range-picker
                    :dari="$dari"
                    :sampai="$sampai"
                    form-id="filter-form-gen-fab"
                    input-dari="dari"
                    input-sampai="sampai"
                    extra-inputs="<input type='hidden' name='tab' id='hidden-tab-fab' value='{{ $activeTab ?? 'all' }}'>"
                />
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* ── FAB: Filter rentang waktu (pill putih mengambang kanan-bawah) ── */
    .fab-container {
        position: fixed; bottom: 28px; right: 28px; z-index: 60;
        margin-bottom: 20px;
        transition: bottom .2s ease;
    }
    .fab-group {
        display: flex; flex-direction: row; align-items: center; gap: 6px;
        background: #fff;
        border-radius: 999px; padding: 5px 6px;
        box-shadow: 0 4px 24px rgba(0,0,0,.12), 0 0 0 1px rgba(0,0,0,.05);
        animation: fabIn .28s cubic-bezier(.4,0,.2,1);
    }
    @keyframes fabIn {
        from { opacity: 0; transform: translateY(12px) scale(.92); }
        to   { opacity: 1; transform: none; }
    }
    .fab-drp-wrap { margin: 0; flex-shrink: 0; }
    .fab-drp-wrap form { margin: 0; padding: 0; }
    .fab-drp-wrap .drp-trigger {
        border-radius: 999px !important; padding: 10px 16px !important;
        background: linear-gradient(135deg, #8b5cf6, #a78bfa) !important;
        color: #fff !important; border: none !important;
        box-shadow: 0 2px 8px rgba(139,92,246,.3) !important;
        gap: 6px !important; min-width: 0 !important;
        font-size: .78rem !important; font-weight: 700 !important;
        transition: all .2s ease !important; line-height: 1.2 !important;
    }
    .fab-drp-wrap .drp-trigger:hover {
        filter: brightness(1.1) !important; transform: translateY(-1px);
    }
    .fab-drp-wrap .drp-trigger .drp-label { color: #fff !important; font-size: .72rem !important; }
    .fab-drp-wrap .drp-trigger span:last-child { color: rgba(255,255,255,.55) !important; }

    /* ── Tab carousel: tab lebih dari lebar konten disembunyikan, geser utk melihat ── */
    .tab-scroll {
        overflow-x: auto;
        flex-wrap: nowrap;
        scrollbar-width: none;   /* Firefox */
        -ms-overflow-style: none;
        overscroll-behavior-x: contain;
    }
    .tab-scroll::-webkit-scrollbar { display: none; }
    .tab-scroll > * { flex-shrink: 0; }

    /* ── Ringkasan Periode: Chart (kiri) + 4 Kartu (kanan 2×2) ── */
    .summary-overview {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
        align-items: stretch;
    }
    .chart-card {
        background: #fff; border-radius: 16px; padding: 18px 20px;
        border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        display: flex; flex-direction: column;
        height: 100%;
    }
    .chart-header {
        display: flex; align-items: baseline; gap: 10px; margin-bottom: 14px;
        flex-shrink: 0;
    }
    .chart-title {
        font-weight: 800; font-size: .88rem; color: #1e1b2e;
    }
    .chart-sub {
        font-size: .68rem; color: #9ca3af;
    }
    .chart-body {
        position: relative;
        flex: 1;
        min-height: 0;
    }
    .chart-body canvas {
        width: 100% !important;
        height: 100% !important;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0,1fr));
        grid-template-rows: 1fr 1fr;
        gap: 14px;
        height: 100%;
    }
    @media (max-width: 900px) {
        .summary-overview { grid-template-columns: 1fr; }
        .chart-card { order: -1; }
    }
    @media (max-width: 560px) {
        .summary-grid { grid-template-columns: 1fr; }
    }
    .summary-card {
        display: flex; align-items: center; gap: 14px;
        background: #fff; border-radius: 16px; padding: 16px 18px;
        border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        position: relative; overflow: hidden;
        min-width: 0;
    }
    .summary-card::after {
        content: ''; position: absolute; right: -18px; top: -18px;
        width: 74px; height: 74px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255,107,107,.10), transparent 70%);
        opacity: 0; transition: opacity .2s ease;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0,0,0,.09);
        border-color: rgba(255,107,107,.25);
    }
    .summary-card:hover::after { opacity: 1; }
    .summary-icon {
        width: 46px; height: 46px; border-radius: 13px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; box-shadow: 0 4px 10px rgba(0,0,0,.08);
    }
    .sc-primary { background: linear-gradient(135deg,#FF6B6B,#ff9a9a); }
    .sc-purple  { background: linear-gradient(135deg,#a78bfa,#8b5cf6); }
    .sc-teal    { background: linear-gradient(135deg,#4ECDC4,#2dd4bf); }
    .sc-amber   { background: linear-gradient(135deg,#f59e0b,#fbbf24); }
    .summary-body { min-width: 0; flex: 1; overflow: hidden; }
    .summary-label {
        font-size: .62rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .06em; color: #9ca3af;
    }
    .summary-value {
        font-size: 1.18rem; font-weight: 800; color: #1e1b2e;
        margin-top: 2px; line-height: 1.2;
        overflow-wrap: break-word; word-break: break-word;
    }
    .summary-value .sc-lead { color: var(--color-purple, #8b5cf6); }
    .summary-value .sc-paid { color: var(--color-secondary, #4ECDC4); }
    .summary-value .sc-sep  { color: #d1d5db; font-weight: 600; margin: 0 2px; }
    .summary-sub { font-size: .66rem; color: #9ca3af; margin-top: 3px; overflow-wrap: break-word; word-break: break-word; }
    .summary-ratio-track {
        height: 6px; border-radius: 999px; background: #f3f4f6;
        margin-top: 8px; overflow: hidden; max-width: 170px; flex-shrink: 1; min-width: 60px;
    }
    .summary-ratio-fill {
        height: 100%; border-radius: 999px;
        transition: width .5s ease;
    }

    /* ── Batas tinggi tabel utama (maks 5 baris data, sisanya scroll vertikal) ── */
    .table-scroll-limit { overflow-y: auto; overscroll-behavior: contain; }
    .table-scroll-limit::-webkit-scrollbar { width: 8px; }
    .table-scroll-limit::-webkit-scrollbar-track { background: transparent; }
    .table-scroll-limit::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 999px; }
    .table-scroll-limit::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
    .table-scroll-limit { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
    .table-scroll-maxrows { max-height: calc(5 * 56px + 56px); }

    /* ── Sub-tab Running/Testing (lebar penuh, dibagi 2) di dalam kartu tabel ── */
    .table-subtabs {
        display: flex;
        width: 100%;
        background: #fafafa;
        border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .table-subtab {
        flex: 1 1 50%;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 13px 10px;
        border: none; background: transparent;
        font-family: inherit; font-size: .84rem; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: background .15s, color .15s;
    }
    .table-subtab + .table-subtab { border-left: 1px solid rgba(0,0,0,.06); }
    .table-subtab:hover { background: #f3f4f6; }
    .table-subtab.active {
        background: #fff; color: #1e1b2e; font-weight: 800;
        box-shadow: inset 0 -3px 0 var(--color-primary, #FF6B6B);
    }
    #subtab-testing.active { box-shadow: inset 0 -3px 0 #f59e0b; }
    .ts-ico { font-size: .95rem; line-height: 1; }
    .ts-count {
        font-size: .68rem; font-weight: 700; padding: 1px 8px; border-radius: 999px;
        background: rgba(0,0,0,.06); color: #9ca3af; min-width: 24px; text-align: center;
    }
    #subtab-running.active .ts-count { background: rgba(255,107,107,.12); color: var(--color-primary, #FF6B6B); }
    #subtab-testing.active .ts-count { background: rgba(245,158,11,.12); color: #b45309; }
    /* Header tetap terlihat saat scroll vertikal di dalam tabel
       (spesifisitas table.clay-table agar menang atas media query layout) */
    .table-scroll-limit table.clay-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fafafa; /* solid agar baris yang lewat tidak tembus */
        box-shadow: 0 2px 6px -3px rgba(0,0,0,.14);
    }

    @media (max-width: 640px) {
        /* Tabel utama: sel lebih ramping agar 5 baris tetap muat di layar kecil */
        .table-scroll-limit table.clay-table thead th,
        .table-scroll-limit table.clay-table tbody td {
            padding: 7px 6px !important;
            font-size: .72rem !important;
        }
        /* Sub-tab lebih padat di layar kecil */
        .table-subtab { padding: 11px 8px; font-size: .78rem; gap: 6px; }
    }
</style>
@endpush

@push('scripts')
<script>
// ── Tab carousel: roda mouse / trackpad menggeser tab secara horizontal ──
(function() {
    var el = document.querySelector('.tab-scroll');
    if (!el) return;

    el.addEventListener('wheel', function(e) {
        if (e.deltaY !== 0) {
            el.scrollLeft += e.deltaY;
            e.preventDefault();
        }
    }, { passive: false });
})();
</script>
@endpush

@push('scripts')
<script>
var openRows = new Set();

function tog(id) {
    var el   = document.getElementById(id);
    var chev = document.getElementById('chev-' + id);
    if (!el) return;
    var isOpen = openRows.has(id);
    if (isOpen) {
        el.style.display = 'none';
        if (chev) chev.style.transform = 'rotate(0deg)';
        openRows.delete(id);
    } else {
        // level-1 (g1-) adalah table-row, level-2 (g2-) adalah block div
        el.style.display = id.startsWith('g1-') ? 'table-row' : 'block';
        if (chev) chev.style.transform = 'rotate(90deg)';
        openRows.add(id);
    }
}

{{-- ── Sub-tab tabel: Running / Testing ── --}}
function switchSubTab(which) {
    var running = document.getElementById('spending-general-running');
    var testing = document.getElementById('spending-general-testing');
    var tabRun  = document.getElementById('subtab-running');
    var tabTest = document.getElementById('subtab-testing');
    if (!running || !testing || !tabRun || !tabTest) return;

    var showTesting = which === 'testing';
    testing.style.display = showTesting ? '' : 'none';
    running.style.display = showTesting ? 'none' : '';
    tabTest.classList.toggle('active', showTesting);
    tabRun.classList.toggle('active', !showTesting);

    // Tabel yang baru tampil perlu diukur ulang (saat tersembunyi offsetHeight = 0)
    if (window.reapplySpendingTableLimit) {
        window.reapplySpendingTableLimit(showTesting ? 'spending-general-testing' : 'spending-general-running');
    }
}
</script>
@endpush

@push('scripts')
<script>
{{-- ── Batas tinggi tabel utama: maksimal 5 baris data, sisanya scroll vertikal ──
     Berlaku utk KEDUA tabel sub-tab (Running & Testing). Tabel yang sedang
     disembunyikan (sub-tab nonaktif) tidak diukur — diukur saat dibuka. --}}
(function() {
    'use strict';

    var MAX_ROWS = 5;

    function measure(table, visible) {
        var head = table.tHead;
        var h = head ? head.offsetHeight : 0;
        for (var i = 0; i < MAX_ROWS; i++) h += visible[i].offsetHeight;
        return h;
    }

    function apply(scrollEl) {
        var table = scrollEl.querySelector('table.clay-table');
        if (!table || !table.tBodies.length) return;
        if (scrollEl.offsetParent === null) return; // tersembunyi (sub-tab lain) → nanti diukur saat dibuka

        var tbody = table.tBodies[0];
        // Baris terlihat = baris tanggal (level-1); baris expand awal punya display:none inline
        var visible = Array.prototype.filter.call(tbody.rows, function(r) {
            return r.style.display !== 'none';
        });

        // ≤ 5 baris → biarkan tinggi alami, tanpa scroll
        if (visible.length <= MAX_ROWS) { scrollEl.style.maxHeight = ''; return; }

        scrollEl.style.maxHeight = measure(table, visible) + 'px';

        // Pass 2: setelah scrollbar vertikal muncul, lebar konten menyusut & baris bisa
        // ikut berubah tinggi (reflow) → ukur sekali lagi agar tetap pas 5 baris.
        requestAnimationFrame(function() {
            scrollEl.style.maxHeight = measure(table, visible) + 'px';
        });
    }

    // Dipakai switchSubTab(): ukur ulang tabel yang baru ditampilkan
    window.reapplySpendingTableLimit = function(id) {
        var el = document.getElementById(id);
        if (el) apply(el);
    };

    var els = document.querySelectorAll('.table-scroll-maxrows');
    Array.prototype.forEach.call(els, function(el) { apply(el); });

    // Re-hitung saat layar berubah ukuran (teks bisa wrap ulang → baris lebih tinggi)
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            Array.prototype.forEach.call(els, function(el) { apply(el); });
        }, 150);
    });
})();
</script>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
{{-- ── Chart Line Lead & Paid per Tanggal (data tab aktif) ── --}}
(function() {
    'use strict';
    var canvas = document.getElementById('spendingChartGeneral');
    if (!canvas) return;

    var labels = @json($chartDates->map(fn($d) => \Carbon\Carbon::parse($d)->translatedFormat('d M')));
    var runLead = @json($chartRunLead->toArray());
    var runPaid = @json($chartRunPaid->toArray());
    var testLead = @json($chartTestLead->toArray());
    var testPaid = @json($chartTestPaid->toArray());

    var ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Lead (Running)',
                    data: runLead,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139,92,246,0.08)',
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1.5,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Paid (Running)',
                    data: runPaid,
                    borderColor: '#4ECDC4',
                    backgroundColor: 'rgba(78,205,196,0.08)',
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointBackgroundColor: '#4ECDC4',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1.5,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Lead (Testing)',
                    data: testLead,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249,115,22,0.06)',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 3,
                    pointBackgroundColor: '#f97316',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1.5,
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'Paid (Testing)',
                    data: testPaid,
                    borderColor: '#fbbf24',
                    backgroundColor: 'rgba(251,191,36,0.06)',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 3,
                    pointBackgroundColor: '#fbbf24',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1.5,
                    tension: 0.3,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, boxHeight: 12, padding: 16, font: { size: 11, weight: '600' } }
                },
                tooltip: {
                    backgroundColor: '#1e1b2e',
                    titleFont: { size: 12, weight: '700' },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 }, color: '#9ca3af' }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { font: { size: 10 }, color: '#9ca3af', callback: function(v) { return v.toLocaleString('id-ID'); } }
                }
            }
        }
    });
})();
</script>
@endpush
@endsection