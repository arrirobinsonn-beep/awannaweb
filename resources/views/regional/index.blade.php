@extends('layouts.app')
@section('title','Detail Kiriman Per Daerah')
@section('page-title','🗺️ Detail Kiriman Per Daerah')
@section('page-subtitle','Lead & Paid per provinsi — upload file Excel untuk import data')
@push('styles')

<style>
    /* ── Scroll wrapper ──────────────────────────── */
    .reg-scroll-wrap {
        position: relative;
        overflow-x: auto;
        max-height: 75vh;
        overflow-y: auto;
    }

    /* ── Sticky columns ─────────────────────────── */
    .reg-sticky-left {
        position: sticky !important;
        left: 0;
        z-index: 4;
        background: #fff;
        background-clip: padding-box;
    }
    thead .reg-sticky-left { z-index: 6; top: 0; }
    thead tr:nth-child(2) .reg-sticky-left { top: var(--reg-head2, 38px); }
    tbody .reg-sticky-left { z-index: 2; }
    tbody tr:hover .reg-sticky-left { background: #f8fafc; }

    .reg-sticky-right {
        position: sticky !important;
        right: 0;
        z-index: 5;
        background-clip: padding-box;
    }
    /* ── Sub-kolom TOTAL: offset sticky per kolom (tiap kolom 80px; urut LEAD, RATIO, PAID) ── */
    .reg-total-lead  { right: 160px; }
    .reg-total-ratio { right: 80px;  }
    .reg-total-paid  { right: 0;     }
    thead .reg-sticky-right { z-index: 6; top: 0; }
    thead tr:nth-child(2) .reg-sticky-right { top: var(--reg-head2, 38px); }
    tbody .reg-sticky-right { z-index: 3; }

    /* ── Sticky header rows ─────────────────────── */
    thead .reg-head-row { position: sticky; top: 0; z-index: 7; }
    thead .reg-head-row-2 { top: var(--reg-head2, 38px); }

    /* Bayangan pseudo-element */
    .reg-sticky-left::after {
        content: '';
        position: absolute;
        right: 0; top: 0; bottom: 0;
        width: 8px;
        background: linear-gradient(to right, transparent, rgba(0,0,0,.06));
        pointer-events: none;
    }
    .reg-sticky-right::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 8px;
        background: linear-gradient(to left, transparent, rgba(0,0,0,.08));
        pointer-events: none;
    }

    /* ── Striped per tanggal ────────────────────── */
    .reg-date-striped { background: #f8fbff; }
    .reg-date-striped.reg-date-alt { background: #f0f4f8; }

    /* ── Modal Umum ──────────────────────────────── */
    .modal-regional {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .modal-regional.active { display: flex; }
    .modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,.5);
        backdrop-filter: blur(2px);
    }
    .modal-container {
        position: relative;
        background: #fff;
        border-radius: 20px;
        width: 100%;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
        animation: modalIn .25s ease;
        display: flex;
        flex-direction: column;
    }
    .modal-container-sm { max-width: 500px; }
    .modal-container-lg { max-width: 1080px; max-height: 90vh; }
    @keyframes modalIn {
        from { opacity: 0; transform: scale(.96) translateY(12px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 24px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;
    }
    .modal-header h2 { margin: 0; font-size: 1.05rem; font-weight: 800; color: #1e1b2e; }
    .modal-close {
        width: 32px; height: 32px; border-radius: 50%;
        border: none; background: #f3f4f6; cursor: pointer;
        font-size: 1.1rem; display: flex; align-items: center; justify-content: center;
        transition: background .15s;
    }
    .modal-close:hover { background: #e5e7eb; }
    .modal-body {
        flex: 1; overflow-y: auto; padding: 16px 24px 20px;
        min-height: 0;
    }
    .modal-footer {
        display: flex; align-items: center; justify-content: flex-end;
        gap: 10px; padding: 14px 24px; border-top: 1px solid #e5e7eb; flex-shrink: 0;
    }

    /* Dropzone styles — centralized in clay.css (clay-dropzone) */

    /* ── Upload error ────────────────────────────── */
    .upload-error {
        display: none; color: #ef4444; font-size: .78rem;
        background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
        padding: 10px 14px; margin-top: 10px;
    }
    .upload-error.show { display: block; }

    /* ── Processing overlay dalam dropzone ────────── */
    .dropzone-wrap { position: relative; }
    .processing-overlay {
        display: none; position: absolute; inset: 0;
        background: rgba(255,255,255,.88); border-radius: 14px;
        align-items: center; justify-content: center;
        flex-direction: column; gap: 8px; z-index: 5;
    }
    .processing-overlay.active { display: flex; }
    .spinner {
        width: 32px; height: 32px;
        border: 3px solid #e5e7eb; border-top-color: var(--color-primary,#FF6B6B);
        border-radius: 50%; animation: spin .7s linear infinite;
    }
    .spinner-sm {
        display: inline-block; width: 16px; height: 16px;
        border: 2px solid #e5e7eb; border-top-color: var(--color-primary,#FF6B6B);
        border-radius: 50%; animation: spin .7s linear infinite;
        vertical-align: middle; margin-right: 6px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    #file-input { display: none; }

    /* ── Preview table styles ────────────────────── */
    .modal-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
    .modal-table th {
        background: #4472C4; color: #fff; padding: 7px 10px; text-align: center;
        font-weight: 700; font-size: .72rem;
        border: 1px solid rgba(255,255,255,.15); white-space: nowrap;
    }
    .modal-table th:first-child { text-align: left; }
    .modal-table td {
        padding: 5px 8px; border-bottom: 1px solid rgba(0,0,0,.05); vertical-align: middle;
    }
    .modal-table td:first-child { font-weight: 600; color: #1e1b2e; white-space: nowrap; }
    .modal-table tr:hover td { background: #f8fafc; }
    .modal-table .prov-lead, .modal-table .prov-paid {
        width: 72px; text-align: center; border: 1px solid #d1d5db;
        border-radius: 6px; padding: 5px 4px; font-size: .78rem;
        font-weight: 600; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .modal-table .prov-lead:focus, .modal-table .prov-paid:focus {
        border-color: var(--color-primary,#FF6B6B);
        box-shadow: 0 0 0 2px rgba(255,107,107,.15);
    }
    .date-section {
        background: #f0f4ff; border-radius: 10px; padding: 10px 14px;
        margin: 16px 0 8px; font-weight: 700; font-size: .85rem;
        color: #1e40af; display: flex; align-items: center; gap: 8px;
    }
    .date-section:first-of-type { margin-top: 0; }
    .preview-stats {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 8px; margin-bottom: 14px;
    }
    .preview-stat {
        background: #f9fafb; border-radius: 10px; padding: 10px 12px; text-align: center;
    }
    .preview-stat .val { font-size: 1.3rem; font-weight: 900; color: #1e1b2e; }
    .preview-stat .lbl {
        font-size: .62rem; font-weight: 600; text-transform: uppercase;
        color: #9ca3af; margin-top: 2px;
    }
    .preview-testing-note {
        grid-column: 1 / -1; text-align: left;
        background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px;
        padding: 8px 12px; font-size: .74rem; font-weight: 600; color: #92400e;
    }
    .preview-alert {
        background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
        padding: 10px 14px; font-size: .78rem; color: #991b1b; margin-bottom: 12px;
    }

    /* ── Overview: Chart (kiri) + Summary 2×2 (kanan) ── */
    .reg-overview {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        align-items: stretch;
    }
    .reg-chart-card {
        background: #fff; border-radius: 16px; padding: 18px 20px 0;
        border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        display: flex; flex-direction: column;
        overflow: hidden;
    }
    .reg-chart-header {
        display: flex; align-items: baseline; gap: 10px; margin-bottom: 14px; flex-shrink: 0;
    }
    .reg-chart-title { font-weight: 800; font-size: .88rem; color: #1e1b2e; }
    .reg-chart-sub { font-size: .68rem; color: #9ca3af; }
    /* Scrollable bars area */
    .reg-chart-scroll {
        flex: 1; min-height: 0; overflow-y: auto;
        scrollbar-width: thin; scrollbar-color: #d1d5db transparent;
    }
    .reg-chart-scroll::-webkit-scrollbar { width: 5px; }
    .reg-chart-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 999px; }
    .reg-chart-scroll::-webkit-scrollbar-track { background: transparent; }
    .reg-chart-body { position: relative; width: 100%; }
    .reg-chart-body canvas { width: 100% !important; display: block; }

    .reg-summary-grid {
        display: grid; grid-template-columns: repeat(2, 1fr);
        grid-template-rows: 1fr 1fr; gap: 12px;
    }
    .reg-summary-card {
        display: flex; align-items: center; gap: 14px;
        background: #fff; border-radius: 16px; padding: 16px 18px;
        border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        position: relative; overflow: hidden;
    }
    .reg-summary-card::after {
        content: ''; position: absolute; right: -18px; top: -18px;
        width: 74px; height: 74px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255,107,107,.10), transparent 70%);
        opacity: 0; transition: opacity .2s ease;
    }
    .reg-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0,0,0,.09);
        border-color: rgba(255,107,107,.25);
    }
    .reg-summary-card:hover::after { opacity: 1; }
    .reg-sc-icon {
        width: 46px; height: 46px; border-radius: 13px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; box-shadow: 0 4px 10px rgba(0,0,0,.08);
    }
    .reg-sc-purple { background: linear-gradient(135deg, #a78bfa, #8b5cf6); }
    .reg-sc-teal   { background: linear-gradient(135deg, #4ECDC4, #2dd4bf); }
    .reg-sc-amber  { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .reg-sc-red    { background: linear-gradient(135deg, #FF6B6B, #ff9a9a); }
    .reg-sc-body { min-width: 0; }
    .reg-sc-label {
        font-size: .62rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .06em; color: #9ca3af;
    }
    .reg-sc-value {
        font-size: 1.18rem; font-weight: 800; color: #1e1b2e;
        margin-top: 2px; white-space: nowrap; line-height: 1.2;
    }
    .reg-sc-sub {
        font-size: .66rem; color: #9ca3af; margin-top: 3px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    @media (max-width: 900px) {
        .reg-overview { grid-template-columns: 1fr; }
        .reg-chart-card { order: -1; }
    }
    @media (max-width: 560px) {
        .reg-summary-grid { grid-template-columns: 1fr; }
    }

    /* ── FAB (horizontal pill bar) ──────────── */
    .reg-fab-container {
        position: fixed; bottom: 28px; right: 28px; z-index: 60;
        margin-bottom: 20px;
    }
    .reg-fab-group {
        display: flex; flex-direction: row; align-items: center; gap: 6px;
        background: #fff;
        border-radius: 999px; padding: 5px 6px;
        box-shadow: 0 4px 24px rgba(0,0,0,.12), 0 0 0 1px rgba(0,0,0,.05);
        animation: regFabIn .28s cubic-bezier(.4,0,.2,1);
    }
    @keyframes regFabIn {
        from { opacity: 0; transform: translateY(12px) scale(.92); }
        to   { opacity: 1; transform: none; }
    }
    .reg-fab-divider {
        width: 1px; height: 22px; background: rgba(0,0,0,.1); flex-shrink: 0;
    }
    .reg-fab {
        display: inline-flex; align-items: center; gap: 6px;
        border: none; border-radius: 999px; padding: 10px 16px;
        font-size: .78rem; font-weight: 700; cursor: pointer;
        transition: all .2s ease; white-space: nowrap;
        text-decoration: none; line-height: 1.2;
        color: #fff; flex-shrink: 0;
    }
    .reg-fab:hover { filter: brightness(1.1); transform: translateY(-1px); }
    .reg-fab:active { transform: translateY(0); filter: brightness(1); }
    .reg-fab-primary { background: linear-gradient(135deg, #FF6B6B, #ff9a9a); box-shadow: 0 2px 8px rgba(255,107,107,.3); }
    .reg-fab-icon { font-size: 1rem; line-height: 1; flex-shrink: 0; }
    .reg-fab-label { font-size: .72rem; letter-spacing: .02em; }
    /* DRP trigger inside FAB pill */
    .reg-fab-drp-wrap { margin: 0; flex-shrink: 0; }
    .reg-fab-drp-wrap form { margin: 0; padding: 0; }
    .reg-fab-drp-wrap .drp-trigger {
        border-radius: 999px !important; padding: 10px 16px !important;
        background: linear-gradient(135deg, #8b5cf6, #a78bfa) !important;
        color: #fff !important; border: none !important;
        box-shadow: 0 2px 8px rgba(139,92,246,.3) !important;
        gap: 6px !important; min-width: 0 !important;
        font-size: .78rem !important; font-weight: 700 !important;
        transition: all .2s ease !important; line-height: 1.2 !important;
    }
    .reg-fab-drp-wrap .drp-trigger:hover {
        filter: brightness(1.1) !important; transform: translateY(-1px);
    }
    .reg-fab-drp-wrap .drp-trigger .drp-label { color: #fff !important; font-size: .72rem !important; }
    .reg-fab-drp-wrap .drp-trigger span:last-child { color: rgba(255,255,255,.55) !important; }
    #main-content { padding-bottom: 90px !important; }

    /* ── Mobile ────────────────────── */
    @media (max-width: 640px) {
        .reg-fab-container { left: 12px; right: 12px; bottom: 12px; }
        .reg-fab-group { padding: 4px 5px; justify-content: center; }
        .reg-fab { padding: 8px 12px; font-size: .72rem; }
        .reg-fab-icon { font-size: .88rem; }
        .reg-fab-drp-wrap .drp-trigger { padding: 8px 12px !important; font-size: .72rem !important; }
        .reg-fab-divider { height: 18px; }
        .reg-fab-label { display: none; }
        .reg-chart-card { padding: 14px 14px 0; }
        .reg-chart-body { min-height: 170px; }
        .reg-summary-card { padding: 12px 14px; gap: 10px; }
        .reg-sc-icon { width: 38px; height: 38px; font-size: 1rem; }
        .reg-sc-value { font-size: 1rem; }
    }
</style>
@endpush

@section('content')
@php $user = auth()->user(); @endphp

<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- ⚠️ Alarm Banner --}}
    @if($hasDiscrepancy)
    <div class="clay-alert clay-alert-error" data-reveal>
        <span>🚨</span>
        <div style="flex:1;font-size:.83rem;">
            @if(count($discrepancies) > 0)
            <strong>Ketidaksesuaian Data Ditemukan!</strong> Total Lead/Paid Regional tidak sama dengan Spending Harian.
            @if(count($discrepancies) > 5)
            <div style="margin-top:6px;font-size:.7rem;color:#b91c1c;font-weight:600;">
                ⬇ Menampilkan 5 dari {{ count($discrepancies) }} tanggal — scroll untuk melihat sisanya
            </div>
            @endif
            <div style="margin-top:4px;max-height:112px;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:#d1d5db transparent;padding-right:6px;">
                @foreach($discrepancies as $tgl => $d)
                <div style="margin-top:4px;font-size:.78rem;line-height:1.45;">
                    📅 {{ \Carbon\Carbon::parse($tgl)->translatedFormat('d M') }} —
                    Regional: Lead {{ $d['regional_lead'] }}, Paid {{ $d['regional_paid'] }} |
                    Spending: Lead {{ $d['spending_lead'] }}, Paid {{ $d['spending_paid'] }}
                </div>
                @endforeach
            </div>
            @endif

            @if(count($missingSpendingDates) > 0)
            @if(count($discrepancies) > 0)
            <div style="border-top:1px dashed rgba(255,107,107,.35);margin-top:10px;padding-top:10px;"></div>
            @endif
            <strong>Data Belum Ditambahkan</strong>
            @if(count($missingSpendingDates) > 5)
            <div style="margin-top:6px;font-size:.7rem;color:#b91c1c;font-weight:600;">
                ⬇ Menampilkan 5 dari {{ count($missingSpendingDates) }} tanggal — scroll untuk melihat sisanya
            </div>
            @endif
            <div style="margin-top:4px;max-height:112px;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:#d1d5db transparent;padding-right:6px;">
                @foreach(array_keys($missingSpendingDates) as $tgl)
                @php
                    $tglLbl = (int) substr($tgl, 8, 2) . ' ' . ['1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April', '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus', '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'][(int) substr($tgl, 5, 2)] . ' ' . substr($tgl, 0, 4);
                @endphp
                <div style="margin-top:4px;font-size:.78rem;line-height:1.45;">
                    📅 {{ $tglLbl }} —
                    Belum mengisi data spending iklan tanggal {{ $tglLbl }}
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ─── Action Bar (hanya Advertiser select + Reset) ── --}}
    @if($advertisers->isNotEmpty())
    <div class="clay-card" style="padding:14px 16px;" data-reveal>
        <form method="GET" action="{{ route('regional.index') }}" id="filter-form-reg"
              style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <div style="display:flex;flex-direction:column;gap:2px;">
                <label style="font-size:.68rem;font-weight:600;color:#374151;">Pilih Advertiser</label>
                <select name="user_id" onchange="this.form.submit()"
                        style="padding:7px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;background:#fff;min-width:180px;">
                    @foreach($advertisers as $adv)
                    <option value="{{ $adv->id }}" {{ ($targetUserId ?? auth()->id()) == $adv->id ? 'selected' : '' }}>
                        {{ $adv->panggilan ?: $adv->nama ?: $adv->email }}
                    </option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('regional.index') }}" class="clay-btn clay-btn-outline">Reset</a>
        </form>
    </div>
    @endif

    {{-- ─── Ringkasan Total + Chart (2 kolom) ─────────── --}}
    @php
        // Data chart: total lead per provinsi (yang punya lead > 0)
        $provLeadTotals = [];
        foreach ($matrix as $prov => $dates) {
            $t = collect($dates)->sum('lead');
            if ($t > 0) $provLeadTotals[$prov] = $t;
        }
        arsort($provLeadTotals);
        // Chart hanya menampilkan 10 provinsi dengan total lead tertinggi.
        // Data tabel/rangkuman tetap menggunakan seluruh provinsi.
        $provLeadTotals = array_slice($provLeadTotals, 0, 10, true);
        $chartProvinces = array_keys($provLeadTotals);
        $chartLeads = array_values($provLeadTotals);
        $periodeLabel = \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') . ' – ' . \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y');
        if ($dari === $sampai) $periodeLabel = \Carbon\Carbon::parse($dari)->translatedFormat('d M Y');
    @endphp
    <div class="reg-overview" data-reveal>
        {{-- KIRI: Chart Bar Lead per Provinsi --}}
        <div class="reg-chart-card">
            <div class="reg-chart-header">
                <span class="reg-chart-title">📊 Top 10 Lead per Daerah</span>
                <span class="reg-chart-sub">{{ $periodeLabel }}</span>
            </div>
            <div class="reg-chart-scroll" id="reg-chart-scroll">
                <div class="reg-chart-body">
                    <canvas id="regionalChart"></canvas>
                </div>
            </div>
        </div>

        {{-- KANAN: 4 Kartu Summary (2×2) --}}
        <div class="reg-summary-grid">
            {{-- 1. Total Lead (Regional) --}}
            <div class="reg-summary-card" title="Total lead dari data regional">
                <div class="reg-sc-icon reg-sc-purple">👥</div>
                <div class="reg-sc-body">
                    <div class="reg-sc-label">Lead (Regional)</div>
                    <div class="reg-sc-value" data-counter="{{ $totalRegional['lead'] }}">{{ $totalRegional['lead'] }}</div>
                    <div class="reg-sc-sub">Dari file Excel regional</div>
                </div>
            </div>
            {{-- 2. Total Paid (Regional) --}}
            <div class="reg-summary-card" title="Total paid dari data regional">
                <div class="reg-sc-icon reg-sc-teal">✅</div>
                <div class="reg-sc-body">
                    <div class="reg-sc-label">Paid (Regional)</div>
                    <div class="reg-sc-value" style="color:#059669;" data-counter="{{ $totalRegional['paid'] }}">{{ $totalRegional['paid'] }}</div>
                    <div class="reg-sc-sub">Dari file Excel regional</div>
                </div>
            </div>
            {{-- 3. Total Lead (Spending) --}}
            <div class="reg-summary-card" title="Total lead dari spending harian">
                <div class="reg-sc-icon reg-sc-amber">📊</div>
                <div class="reg-sc-body">
                    <div class="reg-sc-label">Lead (Spending)</div>
                    <div class="reg-sc-value" data-counter="{{ $totalSpending['lead'] }}">{{ $totalSpending['lead'] }}</div>
                    <div class="reg-sc-sub">Dari data spending harian</div>
                </div>
            </div>
            {{-- 4. Total Paid (Spending) --}}
            <div class="reg-summary-card" title="Total paid dari spending harian">
                <div class="reg-sc-icon reg-sc-red">💰</div>
                <div class="reg-sc-body">
                    <div class="reg-sc-label">Paid (Spending)</div>
                    <div class="reg-sc-value" style="color:#ef4444;" data-counter="{{ $totalSpending['paid'] }}">{{ $totalSpending['paid'] }}</div>
                    <div class="reg-sc-sub">Dari data spending harian</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Tabel Utama ─────────────────────────────── --}}
    <div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
        <div class="reg-scroll-wrap">
            <table style="border-collapse:collapse;width:100%;font-size:.78rem;white-space:nowrap;">
                <thead>
                    <tr class="reg-head-row">
                        <th colspan="1" class="reg-sticky-left" style="background:#4472C4;color:#fff;padding:8px 14px;text-align:left;font-weight:700;font-size:.8rem;min-width:200px;border:1px solid rgba(255,255,255,.15);">
                            PROVINSI
                        </th>
                        @foreach($allDates as $date)
                        <th colspan="3" style="background:#4472C4;color:#fff;padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;border:1px solid rgba(255,255,255,.15);min-width:100px;">
                            {{ \Carbon\Carbon::parse($date)->format('d') }}
                            <span style="display:block;font-weight:400;font-size:.65rem;opacity:.8;">
                                {{ \Carbon\Carbon::parse($date)->translatedFormat('D') }}
                            </span>
                        </th>
                        @endforeach
                        {{-- TOTAL sticky kanan --}}
                        <th colspan="3" class="reg-sticky-right reg-total-paid" style="background:#0d9488;color:#fff;padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;border:1px solid rgba(255,255,255,.15);width:240px;min-width:240px;">
                            📊 TOTAL
                        </th>
                    </tr>
                    <tr class="reg-head-row reg-head-row-2">
                        <th class="reg-sticky-left" style="background:#5B9BD5;color:#fff;padding:6px 14px;text-align:left;font-weight:600;font-size:.72rem;border:1px solid rgba(255,255,255,.15);">
                            {{ count($masterProvinces) }} Provinsi
                        </th>
                        @foreach($allDates as $dateIndex => $date)
                            @php $isAlt = $dateIndex % 2 === 0; @endphp
                            <th style="background:#5B9BD5;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);">LEAD</th>
                            <th style="background:#5B9BD5;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);">RATIO</th>
                            <th style="background:#5B9BD5;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);">PAID</th>
                        @endforeach
                        <th class="reg-sticky-right reg-total-lead" style="background:#0d9488;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);width:80px;min-width:80px;">LEAD</th>
                        <th class="reg-sticky-right reg-total-ratio" style="background:#0d9488;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);width:80px;min-width:80px;">RATIO</th>
                        <th class="reg-sticky-right reg-total-paid" style="background:#0d9488;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);width:80px;min-width:80px;">PAID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($masterProvinces as $province)
                    @php
                        $provTotalLead = 0;
                        $provTotalPaid = 0;
                    @endphp
                    <tr style="transition:background .12s;"
                        onmouseenter="this.style.background='#f8fafc'"
                        onmouseleave="this.style.background=''">
                        <td class="reg-sticky-left" style="padding:6px 14px;font-weight:600;font-size:.78rem;color:#1e1b2e;border-bottom:1px solid rgba(0,0,0,.05);white-space:nowrap;">
                            {{ $province }}
                        </td>
                        @foreach($allDates as $dateIndex => $date)
                            @php
                                $isAlt = $dateIndex % 2 === 0;
                                $stripClass = 'reg-date-striped' . ($isAlt ? '' : ' reg-date-alt');
                                $cell = $matrix[$province][$date];
                                $hasData = $cell['lead'] > 0 || $cell['paid'] > 0;
                                $provTotalLead += $cell['lead'];
                                $provTotalPaid += $cell['paid'];
                            @endphp
                            <td style="padding:8px 6px;text-align:center;font-weight:600;font-size:.82rem;border-bottom:1px solid rgba(0,0,0,.05);{{ $hasData ? 'color:#1e1b2e;cursor:pointer;' : 'color:#d1d5db;' }}"
                                class="{{ $hasData ? 'cell-edit-trigger ' : '' }}{{ $stripClass }}"
                                @if($hasData)
                                data-id="{{ $cell['id'] }}"
                                data-tanggal="{{ $date }}"
                                data-province="{{ $province }}"
                                data-lead="{{ $cell['lead'] }}"
                                data-paid="{{ $cell['paid'] }}"
                                data-ratio="{{ $cell['ratio'] }}"
                                title="Klik untuk edit"
                                @endif>
                                {{ $hasData ? number_format($cell['lead']) : '0' }}
                            </td>
                            <td style="padding:8px 6px;text-align:center;font-size:.76rem;border-bottom:1px solid rgba(0,0,0,.05);{{ $hasData ? 'color:var(--color-primary);font-weight:700;' : 'color:#d1d5db;' }}"
                                class="{{ $stripClass }}">
                                @if($cell['lead'] > 0)
                                    {{ number_format($cell['ratio'], 1) }}%
                                @else
                                    0%
                                @endif
                            </td>
                            <td style="padding:8px 6px;text-align:center;font-weight:600;font-size:.82rem;border-bottom:1px solid rgba(0,0,0,.05);{{ $hasData ? 'color:#059669;' : 'color:#d1d5db;' }}"
                                class="{{ $stripClass }}">
                                {{ $hasData ? number_format($cell['paid']) : '0' }}
                            </td>
                        @endforeach
                        {{-- Total per provinsi (sticky kanan) --}}
                        @php $provRatio = $provTotalLead > 0 ? round($provTotalPaid / $provTotalLead * 100, 1) : 0; @endphp
                        <td class="reg-sticky-right reg-total-lead" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#1e1b2e;border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;width:80px;min-width:80px;">{{ number_format($provTotalLead) }}</td>
                        <td class="reg-sticky-right reg-total-ratio" style="padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;color:var(--color-primary);border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;width:80px;min-width:80px;">{{ $provRatio > 0 ? number_format($provRatio, 1) . '%' : '0%' }}</td>
                        <td class="reg-sticky-right reg-total-paid" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#059669;border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;width:80px;min-width:80px;">{{ number_format($provTotalPaid) }}</td>
                    </tr>
                    @endforeach
                    {{-- Grand Total Row (sticky bottom + sticky kanan) --}}
                    <tr style="position:sticky;bottom:0;z-index:4;background:#F0FFFE;">
                        <td class="reg-sticky-left" style="background:#F0FFFE;padding:8px 14px;font-weight:800;font-size:.82rem;color:#0d9488;border-top:2px solid #0d9488;">
                            📊 GRAND TOTAL
                        </td>
                        @foreach($allDates as $dateIndex => $date)
                            @php
                                $tot = $totalPerTanggal[$date];
                                $isAlt = $dateIndex % 2 === 0;
                                $stripClass = 'reg-date-striped' . ($isAlt ? '' : ' reg-date-alt');
                            @endphp
                            <td style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#1e1b2e;border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ number_format($tot['lead']) }}</td>
                            <td style="padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;color:var(--color-primary);border-top:2px solid #0d9488;" class="{{ $stripClass }}">
                                {{ $tot['lead'] > 0 ? number_format($tot['paid'] / $tot['lead'] * 100, 1) . '%' : '0%' }}
                            </td>
                            <td style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#059669;border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ number_format($tot['paid']) }}</td>
                        @endforeach
                        @php
                            $grandLead = collect($totalPerTanggal)->sum('lead');
                            $grandPaid = collect($totalPerTanggal)->sum('paid');
                            $grandRatio = $grandLead > 0 ? round($grandPaid / $grandLead * 100, 1) : 0;
                        @endphp
                        <td class="reg-sticky-right reg-total-lead" style="padding:8px 6px;text-align:center;font-weight:900;font-size:.9rem;color:#0d9488;border-top:2px solid #0d9488;background:#e6fffa;width:80px;min-width:80px;">{{ number_format($grandLead) }}</td>
                        <td class="reg-sticky-right reg-total-ratio" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:var(--color-primary);border-top:2px solid #0d9488;background:#e6fffa;width:80px;min-width:80px;">{{ $grandRatio > 0 ? number_format($grandRatio, 1) . '%' : '0%' }}</td>
                        <td class="reg-sticky-right reg-total-paid" style="padding:8px 6px;text-align:center;font-weight:900;font-size:.9rem;color:#059669;border-top:2px solid #0d9488;background:#e6fffa;width:80px;min-width:80px;">{{ number_format($grandPaid) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Info --}}
    <div style="display:flex;gap:10px;font-size:.72rem;color:#9ca3af;flex-wrap:wrap;">
        <span>📌 Kolom provinsi <strong>sticky</strong> — tetap terlihat saat scroll horizontal</span>
        <span>🔄 Gunakan FAB di kanan bawah untuk rentang tanggal & upload</span>
    </div>
</div>

{{-- ═══════════════ FAB Container ═══════════════ --}}
<div class="reg-fab-container" id="reg-fab-container">
    <div id="reg-fab-default" class="reg-fab-group">
        <button type="button" class="reg-fab reg-fab-primary" id="btn-upload-modal"
                title="Upload File Excel">
            <span class="reg-fab-icon">📤</span>
            <span class="reg-fab-label">Upload</span>
        </button>
        <div class="reg-fab-divider"></div>
        <div class="reg-fab-drp-wrap">
            <form method="GET" action="{{ route('regional.index') }}" id="filter-form-reg-fab">
                <x-date-range-picker
                    :dari="$dari"
                    :sampai="$sampai"
                    form-id="filter-form-reg-fab"
                    input-dari="dari"
                    input-sampai="sampai"
                />
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════ MODAL UPLOAD ═══════════════ --}}
<div class="modal-regional" id="modal-upload">
    <div class="modal-backdrop" id="upload-backdrop"></div>
    <div class="modal-container modal-container-sm">
        <div class="modal-header">
            <h2>📤 Upload File Excel</h2>
            <button class="modal-close" id="upload-close-btn" type="button">✕</button>
        </div>
        <div class="modal-body">
            <div style="font-size:.78rem;color:#6b7280;margin-bottom:12px;">
                Upload file Excel <strong>Regional</strong> (Lead/Paid per provinsi) — format otomatis dikenali.
            </div>

            <div class="dropzone-wrap">
                <div class="processing-overlay" id="upload-processing">
                    <div class="spinner"></div>
                    <div style="font-size:.82rem;font-weight:600;color:#374151;">Memproses file...</div>
                    <div style="font-size:.7rem;color:#9ca3af;">Mendeteksi format file...</div>
                </div>

                <div class="dropzone" id="upload-dropzone">
                    <span class="dropzone-icon" id="upload-icon">📂</span>
                    <div class="dropzone-title">Klik atau tarik file ke sini</div>
                    <div class="dropzone-hint" id="upload-hint">.xlsx, .xls, .csv — maks 10MB</div>
                    <div class="dropzone-file" id="upload-filename" style="display:none;"></div>
                    <input type="file" id="file-input" accept=".xlsx,.xls,.csv">
                </div>
            </div>

            <div class="upload-error" id="upload-error"></div>
        </div>
    </div>
</div>

{{-- ═══════════════ MODAL PREVIEW ═══════════════ --}}
<div class="modal-regional" id="modal-preview">
    <div class="modal-backdrop" id="preview-backdrop"></div>
    <div class="modal-container modal-container-lg">
        <div class="modal-header">
            <h2 id="preview-title">📊 Preview Data Regional</h2>
            <button class="modal-close" id="preview-close-btn" type="button">✕</button>
        </div>
        <div class="modal-body">
            <div class="preview-stats" id="preview-stats"></div>
            <div class="preview-alert" id="preview-errors" style="display:none;"></div>
            <div id="preview-tables"></div>
        </div>
        <div class="modal-footer">
            <button class="clay-btn clay-btn-outline" id="preview-cancel-btn" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="preview-save-btn" type="button">💾 Simpan Data</button>
        </div>
    </div>
</div>

{{-- ═══════════════ MODAL EDIT ═══════════════ --}}
<div class="modal-regional" id="modal-edit">
    <div class="modal-backdrop" id="edit-backdrop"></div>
    <div class="modal-container modal-container-sm">
        <div class="modal-header">
            <h2>✏️ Edit Data Regional</h2>
            <button class="modal-close" id="edit-close-btn" type="button">✕</button>
        </div>
        <div class="modal-body">
            {{-- Info provinsi & tanggal --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:4px;">Provinsi</label>
                    <div id="edit-province" style="font-size:.95rem;font-weight:700;color:#1e1b2e;padding:8px 0;"></div>
                </div>
                <div>
                    <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:4px;">Tanggal</label>
                    <div id="edit-tanggal" style="font-size:.95rem;font-weight:700;color:#1e1b2e;padding:8px 0;"></div>
                </div>
            </div>

            <div class="form-grid" style="gap:14px;">
                {{-- Lead --}}
                <div>
                    <label style="display:block;font-size:.78rem;font-weight:700;margin-bottom:5px;">Lead (Penanya)</label>
                    <input type="number" id="edit-lead" min="0" class="clay-input" style="font-size:.9rem;font-weight:600;">
                </div>
                {{-- Paid --}}
                <div>
                    <label style="display:block;font-size:.78rem;font-weight:700;margin-bottom:5px;">Paid (Pembeli)</label>
                    <input type="number" id="edit-paid" min="0" class="clay-input" style="font-size:.9rem;font-weight:600;">
                </div>
                {{-- Ratio preview --}}
                <div class="col-span-2">
                    <div style="background:#F0FFFE;border-radius:10px;padding:10px 14px;display:grid;grid-template-columns:1fr;gap:4px;text-align:center;">
                        <div style="font-size:.62rem;font-weight:600;text-transform:uppercase;color:#9ca3af;">Paid Ratio</div>
                        <div id="edit-ratio" style="font-weight:900;font-size:1.2rem;color:var(--color-secondary);">—</div>
                    </div>
                </div>
            </div>

            <input type="hidden" id="edit-id" value="">
        </div>
        <div class="modal-footer" style="justify-content:space-between;">
            <button class="clay-btn clay-btn-danger" id="edit-delete-btn" type="button">🗑 Hapus</button>
            <div style="display:flex;gap:10px;">
                <button class="clay-btn clay-btn-outline" id="edit-cancel-btn" type="button">Batal</button>
                <button class="clay-btn clay-btn-primary" id="edit-save-btn" type="button">💾 Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════ KONFIRMASI HAPUS ═══════════════ --}}
<div class="modal-regional" id="modal-delete-confirm">
    <div class="modal-backdrop" id="delete-confirm-backdrop"></div>
    <div class="modal-container modal-container-sm" style="max-width:400px;">
        <div class="modal-header">
            <h2>⚠️ Konfirmasi Hapus</h2>
            <button class="modal-close" id="delete-confirm-close" type="button">✕</button>
        </div>
        <div class="modal-body" style="text-align:center;padding:24px;">
            <div style="font-size:2.5rem;margin-bottom:10px;">🗑️</div>
            <p style="font-size:.9rem;color:#374151;font-weight:600;margin-bottom:4px;">Hapus data ini?</p>
            <p id="delete-confirm-info" style="font-size:.78rem;color:#9ca3af;"></p>
        </div>
        <div class="modal-footer" style="justify-content:center;gap:12px;">
            <button class="clay-btn clay-btn-outline" id="delete-confirm-cancel" type="button">Batal</button>
            <button class="clay-btn clay-btn-danger" id="delete-confirm-yes" type="button">🗑 Ya, Hapus</button>
        </div>
    </div>
</div>

{{-- ═══════════════ KONFIRMASI SIMPAN (cek tanggal existing) ═══════════════ --}}
<div class="modal-regional" id="modal-save-confirm">
    <div class="modal-backdrop" id="save-confirm-backdrop"></div>
    <div class="modal-container modal-container-sm" style="max-width:420px;">
        <div class="modal-header">
            <h2>💾 Konfirmasi Simpan</h2>
            <button class="modal-close" id="save-confirm-close" type="button">✕</button>
        </div>
        <div class="modal-body" style="padding:20px 22px;">
            <p style="font-size:.82rem;color:#374151;font-weight:600;margin:0 0 10px;">
                Data akan disimpan pada tanggal berikut:
            </p>
            <div id="save-confirm-dates" style="max-height:260px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;"></div>
            <div id="save-confirm-warning" style="display:none;margin-top:12px;padding:8px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:.72rem;color:#991b1b;font-weight:600;">
                ⚠️ Beberapa tanggal sudah memiliki data — isian lama akan <strong>DIGANTI</strong> dengan data dari file ini.
            </div>
        </div>
        <div class="modal-footer" style="justify-content:center;gap:12px;">
            <button class="clay-btn clay-btn-outline" id="save-confirm-cancel" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="save-confirm-yes" type="button">💾 Ya, Simpan</button>
        </div>
    </div>
</div>

{{-- ═══════════════ ALERT CS BELUM DITUGASKAN ═══════════════ --}}
<div class="modal-regional" id="modal-cs-alert">
    <div class="modal-backdrop" id="cs-alert-backdrop"></div>
    <div class="modal-container modal-container-sm" style="max-width:400px;">
        <div class="modal-header">
            <h2>👥 Belum Ada CS</h2>
            <button class="modal-close" id="cs-alert-close" type="button">✕</button>
        </div>
        <div class="modal-body" style="text-align:center;padding:28px 24px;">
            <div style="font-size:2.6rem;margin-bottom:12px;line-height:1;">🤝</div>
            <p style="font-size:.92rem;color:#374151;font-weight:600;line-height:1.65;margin:0;">
                Ups, tampaknya belum ada CS yang ditugaskan khusus untukmu.
                Segera hubungi <strong style="color:var(--color-primary,#FF6B6B);">Guru</strong> untuk menugaskan seorang CS untuk anda!
            </p>
        </div>
        <div class="modal-footer" style="justify-content:center;">
            <button class="clay-btn clay-btn-primary" id="cs-alert-ok" type="button">Mengerti</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // ── DOM refs ────────────────────────────────────
    const uploadBtn     = document.getElementById('btn-upload-modal');

    // Guard: advertiser wajib punya CS yang ditugaskan sebelum upload
    const hasAssignedCs = {{ ($hasAssignedCs ?? true) ? 'true' : 'false' }};

    const mCsAlert      = document.getElementById('modal-cs-alert');
    const csAlertClose  = document.getElementById('cs-alert-close');
    const csAlertBackdrop = document.getElementById('cs-alert-backdrop');
    const csAlertOk     = document.getElementById('cs-alert-ok');

    const mUpload       = document.getElementById('modal-upload');
    const uploadClose   = document.getElementById('upload-close-btn');
    const uploadBackdrop = document.getElementById('upload-backdrop');
    const uploadDropzone = document.getElementById('upload-dropzone');
    const uploadIcon     = document.getElementById('upload-icon');
    const uploadFilename = document.getElementById('upload-filename');
    const uploadHint     = document.getElementById('upload-hint');
    const uploadError    = document.getElementById('upload-error');
    const uploadProcessing = document.getElementById('upload-processing');
    const fileInput      = document.getElementById('file-input');

    const mPreview      = document.getElementById('modal-preview');
    const previewClose  = document.getElementById('preview-close-btn');
    const previewCancel = document.getElementById('preview-cancel-btn');
    const previewBackdrop = document.getElementById('preview-backdrop');
    const previewSave   = document.getElementById('preview-save-btn');
    const previewTitle  = document.getElementById('preview-title');
    const statsEl       = document.getElementById('preview-stats');
    const errorsEl      = document.getElementById('preview-errors');
    const tablesEl      = document.getElementById('preview-tables');

    let isProcessing = false;
    let previewData  = null;
    let previewErrors = [];
    let previewPhoneContacts = [];
    let previewCsStats = []; // CS stats terbaru setelah edit
    let previewSkippedTesting = 0; // lead produk Testing yang dilewati (tidak tampil di tabel)

    // ── Helpers ──────────────────────────────────────
    function formatNumber(n) { return n.toLocaleString('id-ID'); }

    function showUploadError(msg) {
        uploadError.textContent = msg;
        uploadError.classList.add('show');
    }
    function hideUploadError() { uploadError.classList.remove('show'); }

    function setProcessing(on) {
        isProcessing = on;
        uploadProcessing.classList.toggle('active', on);
        uploadDropzone.style.pointerEvents = on ? 'none' : '';
    }

    // ── Open/Close Upload Modal ─────────────────────
    uploadBtn.addEventListener('click', function() {
        // Belum ada CS yang ditugaskan → tampilkan alert, jangan buka modal upload
        if (!hasAssignedCs) {
            mCsAlert.classList.add('active');
            return;
        }
        mUpload.classList.add('active');
    });

    function closeUploadModal() {
        mUpload.classList.remove('active');
        resetDropzone();
        hideUploadError();
    }
    uploadClose.addEventListener('click', closeUploadModal);
    uploadBackdrop.addEventListener('click', closeUploadModal);

    // ── Open/Close CS Alert Modal ───────────────────
    function closeCsAlert() { mCsAlert.classList.remove('active'); }
    csAlertClose.addEventListener('click', closeCsAlert);
    csAlertBackdrop.addEventListener('click', closeCsAlert);
    csAlertOk.addEventListener('click', closeCsAlert);

    // ── Open/Close Preview Modal ────────────────────
    function closePreviewModal() {
        mPreview.classList.remove('active');
        document.body.style.overflow = '';
    }
    previewClose.addEventListener('click', closePreviewModal);
    previewCancel.addEventListener('click', function() {
        closePreviewModal();
        closeUploadModal();
    });
    previewBackdrop.addEventListener('click', closePreviewModal);

    // ── Dropzone ─────────────────────────────────────
    uploadDropzone.addEventListener('click', function() {
        if (!isProcessing) fileInput.click();
    });

    uploadDropzone.addEventListener('dragover', function(e) {
        e.preventDefault(); e.stopPropagation();
        if (!isProcessing) this.classList.add('drag-over');
    });

    uploadDropzone.addEventListener('dragleave', function(e) {
        e.preventDefault(); e.stopPropagation();
        this.classList.remove('drag-over');
    });

    uploadDropzone.addEventListener('drop', function(e) {
        e.preventDefault(); e.stopPropagation();
        this.classList.remove('drag-over');
        if (isProcessing) return;
        const files = e.dataTransfer.files;
        if (files.length > 0 && validateFile(files[0])) {
            updateDropzoneUI(files[0]);
            processFile(files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0 && validateFile(this.files[0])) {
            updateDropzoneUI(this.files[0]);
            processFile(this.files[0]);
        }
    });

    function validateFile(file) {
        const ext = '.' + file.name.split('.').pop().toLowerCase();
        if (!['.xlsx','.xls','.csv'].includes(ext)) {
            showUploadError('Format tidak didukung. Gunakan .xlsx, .xls, atau .csv.');
            return false;
        }
        if (file.size > 10 * 1024 * 1024) {
            showUploadError('File terlalu besar. Maksimal 10MB.');
            return false;
        }
        return true;
    }

    function updateDropzoneUI(file) {
        hideUploadError();
        uploadDropzone.classList.add('has-file');
        uploadFilename.textContent = '📄 ' + file.name + ' (' + formatFileSize(file.size) + ')';
        uploadFilename.style.display = 'block';
        uploadHint.textContent = 'Memproses...';
        uploadIcon.textContent = '⏳';
    }

    function resetDropzone() {
        uploadDropzone.classList.remove('has-file');
        uploadFilename.style.display = 'none';
        uploadHint.textContent = '.xlsx, .xls, .csv — maks 10MB';
        uploadIcon.textContent = '📂';
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    // ── Process file ─────────────────────────────────
    function processFile(file) {
        if (!file || isProcessing) return;
        hideUploadError();
        setProcessing(true);

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route('regional.preview') }}', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' },
        })
        .then(function(res) {
            if (!res.ok) return res.json().then(function(e) { throw new Error(e.message || 'Gagal'); });
            return res.json();
        })
        .then(function(json) {
            setProcessing(false);
            if (json.success) {
                previewData = json.data;
                previewErrors = json.errors || [];
                previewPhoneContacts = json.phone_contacts || [];
                previewSkippedTesting = json.skipped_testing || 0;
                // ─── Simpan CS stats dari preview ────
                previewCsStats = [];
                if (json.data.cs_by_date) {
                    var csDates = Object.keys(json.data.cs_by_date).sort();
                    csDates.forEach(function(tgl) {
                        json.data.cs_by_date[tgl].forEach(function(csItem) {
                            previewCsStats.push({
                                tanggal: csItem.tanggal,
                                cs_panggilan: csItem.cs_panggilan,
                                lead: csItem.lead,
                                paid: csItem.paid
                            });
                        });
                    });
                }
                uploadIcon.textContent = '✅';
                mUpload.classList.remove('active');
                // Gunakan setTimeout biar transisi modal keluar dulu
                setTimeout(function() { showPreviewModal(); }, 200);
            } else {
                showUploadError(json.message || 'Gagal memproses file');
                resetDropzone();
            }
        })
        .catch(function(err) {
            setProcessing(false);
            showUploadError(err.message || 'Terjadi kesalahan');
            resetDropzone();
        });
    }

    // ── Show Preview Modal ──────────────────────────
    function showPreviewModal() {
        previewSave.style.display = 'inline-flex';
        const data = previewData;
        const errors = previewErrors;

        const numDates = Object.keys(data.by_date).length;

        previewTitle.textContent = '📊 Preview — ' + numDates + ' Tanggal, ' + formatNumber(data.total_lead) + ' Lead';

        // Stats
        var pcCount = previewPhoneContacts.length;
        var uniqueCs = [...new Set(previewPhoneContacts.map(function(p) { return p.cs_name; }))];
        statsEl.innerHTML =
            '<div class="preview-stat"><div class="val">' + formatNumber(data.total_lead) + '</div><div class="lbl">Total Lead</div></div>' +
            '<div class="preview-stat"><div class="val">' + formatNumber(data.total_paid) + '</div><div class="lbl">Total Paid</div></div>' +
            '<div class="preview-stat"><div class="val">' + formatNumber(data.total_rows) + '</div><div class="lbl">Provinsi x Tgl</div></div>' +
            '<div class="preview-stat"><div class="val">' + numDates + '</div><div class="lbl">Tanggal</div></div>' +
            (pcCount ? '<div class="preview-stat" style="background:#f0fdf4;"><div class="val" style="color:#059669;">' + formatNumber(pcCount) + '</div><div class="lbl">No Telepon</div></div>' : '') +
            (uniqueCs.length ? '<div class="preview-stat" style="background:#f0fdf4;"><div class="val" style="color:#059669;">' + uniqueCs.length + '</div><div class="lbl">CS Unik</div></div>' : '');

        // Info produk Testing yang dilewati (tidak tampil di tabel regional)
        if (previewSkippedTesting > 0) {
            var skipNote = document.createElement('div');
            skipNote.className = 'preview-testing-note';
            skipNote.textContent = '🔬 ' + formatNumber(previewSkippedTesting) + ' lead produk Testing dilewati (tabel hanya menampilkan produk Running).';
            statsEl.appendChild(skipNote);
        }

        // Errors
        if (errors.length > 0) {
            var h = '<strong>⚠️ ' + errors.length + ' peringatan:</strong><ul style="margin:4px 0 0 16px;padding:0;">';
            for (var i = 0; i < Math.min(errors.length, 10); i++) h += '<li>' + escHtml(errors[i]) + '</li>';
            if (errors.length > 10) h += '<li>...dan ' + (errors.length - 10) + ' lainnya</li>';
            h += '</ul>';
            errorsEl.innerHTML = h;
            errorsEl.style.display = 'block';
        } else {
            errorsEl.style.display = 'none';
        }

        // Tables
        var dates = Object.keys(data.by_date).sort();
        var html = '';
        dates.forEach(function(tgl) {
            var items = data.by_date[tgl];
            var subLead = 0, subPaid = 0;
            items.forEach(function(it) { subLead += it.lead; subPaid += it.paid; });
            var subRatio = subLead > 0 ? (subPaid / subLead * 100) : 0;
            var lbl = new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

            html += '<div class="date-section">📅 ' + lbl +
                ' <span style="font-weight:400;font-size:.75rem;color:#6b7280;">— Lead: ' + formatNumber(subLead) +
                ', Paid: ' + formatNumber(subPaid) + ', Ratio: ' + subRatio.toFixed(1) + '%</span></div>';
            html += '<div style="overflow-x:auto;"><table class="modal-table" data-tanggal="' + tgl + '">';
            html += '<thead><tr><th style="width:38%;text-align:left;">PROVINSI</th><th style="width:19%;">LEAD</th><th style="width:19%;">PAID</th><th style="width:19%;">RATIO</th></tr></thead><tbody>';
            items.forEach(function(item) {
                var ratio = item.lead > 0 ? (item.paid / item.lead * 100) : 0;
                html += '<tr><td>' + item.province + '</td>' +
                    '<td style="text-align:center;"><input type="number" min="0" class="prov-lead" value="' + item.lead + '" data-tanggal="' + tgl + '" data-province="' + escHtml(item.province) + '"></td>' +
                    '<td style="text-align:center;"><input type="number" min="0" class="prov-paid" value="' + item.paid + '" data-tanggal="' + tgl + '" data-province="' + escHtml(item.province) + '"></td>' +
                    '<td style="text-align:center;font-weight:700;" class="ratio-cell">' + ratio.toFixed(1) + '%</td></tr>';
            });
            html += '</tbody></table></div>';
        });

        // ─── Phone contacts table ──────────────────────
        if (previewPhoneContacts.length > 0) {
            var phoneLimit = 50;
            var shownPhones = previewPhoneContacts.slice(0, phoneLimit);
            html += '<div class="date-section">📞 Mapping No Telepon → CS (' + previewPhoneContacts.length + ' kontak)</div>';
            html += '<div style="max-height:300px;overflow-y:auto;"><table class="modal-table" style="font-size:.72rem;">';
            html += '<thead><tr><th style="text-align:left;">No Telepon</th><th style="text-align:left;">CS</th></tr></thead><tbody>';
            shownPhones.forEach(function(p) {
                html += '<tr><td>' + escHtml(p.phone_normalized) + '</td><td><strong>' + escHtml(p.cs_name) + '</strong></td></tr>';
            });
            html += '</tbody></table></div>';
            if (previewPhoneContacts.length > phoneLimit) {
                html += '<div style="font-size:.68rem;color:#9ca3af;margin-top:4px;">...dan ' + (previewPhoneContacts.length - phoneLimit) + ' lainnya</div>';
            }
        }

        tablesEl.innerHTML = html;

        tablesEl.querySelectorAll('.prov-lead, .prov-paid').forEach(function(inp) {
            inp.addEventListener('input', function() { recalcRow(this); });
        });

        mPreview.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function recalcRow(input) {
        var tr = input.closest('tr');
        var lead = parseInt(tr.querySelector('.prov-lead').value) || 0;
        var paid = parseInt(tr.querySelector('.prov-paid').value) || 0;
        tr.querySelector('.ratio-cell').textContent = (lead > 0 ? (paid / lead * 100) : 0).toFixed(1) + '%';
    }

    function escHtml(str) {
        var d = document.createElement('div'); d.textContent = str; return d.innerHTML;
    }

    // ── Edit Cell ────────────────────────────────────
    const mEdit         = document.getElementById('modal-edit');
    const editClose     = document.getElementById('edit-close-btn');
    const editBackdrop  = document.getElementById('edit-backdrop');
    const editCancel    = document.getElementById('edit-cancel-btn');
    const editSave      = document.getElementById('edit-save-btn');
    const editDelete    = document.getElementById('edit-delete-btn');
    const editId        = document.getElementById('edit-id');
    const editProvince  = document.getElementById('edit-province');
    const editTanggal   = document.getElementById('edit-tanggal');
    const editLead      = document.getElementById('edit-lead');
    const editPaid      = document.getElementById('edit-paid');
    const editRatio     = document.getElementById('edit-ratio');

    const mDelConfirm      = document.getElementById('modal-delete-confirm');
    const delConfirmClose  = document.getElementById('delete-confirm-close');
    const delConfirmBackdrop = document.getElementById('delete-confirm-backdrop');
    const delConfirmCancel = document.getElementById('delete-confirm-cancel');
    const delConfirmYes    = document.getElementById('delete-confirm-yes');
    const delConfirmInfo   = document.getElementById('delete-confirm-info');

    // ── Konfirmasi Simpan (cek tanggal existing sebelum save) ──
    const mSaveConfirm     = document.getElementById('modal-save-confirm');
    const saveConfirmClose = document.getElementById('save-confirm-close');
    const saveConfirmBackdrop = document.getElementById('save-confirm-backdrop');
    const saveConfirmCancel = document.getElementById('save-confirm-cancel');
    const saveConfirmYes   = document.getElementById('save-confirm-yes');
    const saveConfirmDates = document.getElementById('save-confirm-dates');
    const saveConfirmWarning = document.getElementById('save-confirm-warning');

    let pendingSave = null; // { items, csStatsPayload, phoneContacts }

    let pendingDeleteId = null;

    // Click cell → open edit modal
    document.querySelectorAll('.cell-edit-trigger').forEach(function(cell) {
        cell.addEventListener('click', function() {
            editId.value       = this.dataset.id;
            editProvince.textContent = this.dataset.province;
            // Format tanggal
            var d = new Date(this.dataset.tanggal + 'T00:00:00');
            editTanggal.textContent = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            editLead.value     = this.dataset.lead;
            editPaid.value     = this.dataset.paid;
            updateEditRatio();
            mEdit.classList.add('active');
        });
    });

    function updateEditRatio() {
        var lead = parseInt(editLead.value) || 0;
        var paid = parseInt(editPaid.value) || 0;
        editRatio.textContent = lead > 0 ? (paid / lead * 100).toFixed(2) + '%' : '—';
    }

    editLead.addEventListener('input', updateEditRatio);
    editPaid.addEventListener('input', updateEditRatio);

    function closeEditModal() {
        mEdit.classList.remove('active');
        document.body.style.overflow = '';
    }
    editClose.addEventListener('click', closeEditModal);
    editBackdrop.addEventListener('click', closeEditModal);
    editCancel.addEventListener('click', closeEditModal);

    // Save edit
    editSave.addEventListener('click', function() {
        var id   = editId.value;
        var lead = parseInt(editLead.value) || 0;
        var paid = parseInt(editPaid.value) || 0;

        if (!id) return;

        editSave.disabled = true;
        editSave.innerHTML = '<span class="spinner-sm"></span> Menyimpan...';

        fetch('{{ route('regional.update-cell') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ id: id, lead: lead, paid: paid }),
        })
        .then(function(res) {
            if (!res.ok) return res.json().then(function(e) { throw new Error(e.message || 'Gagal'); });
            return res.json();
        })
        .then(function(json) {
            if (json.success) {
                closeEditModal();
                window.location.href = '{{ route('regional.index') }}';
            } else {
                alert('Gagal: ' + json.message);
            }
        })
        .catch(function(err) { alert('Error: ' + err.message); })
        .finally(function() {
            editSave.disabled = false;
            editSave.innerHTML = '💾 Simpan';
        });
    });

    // Delete button → confirmation modal
    editDelete.addEventListener('click', function() {
        var id = editId.value;
        if (!id) return;

        pendingDeleteId = id;
        delConfirmInfo.textContent = 'Hapus data ' + editProvince.textContent + ' — ' + editTanggal.textContent + '?';
        closeEditModal();
        setTimeout(function() { mDelConfirm.classList.add('active'); }, 200);
    });

    function closeDeleteConfirm() {
        mDelConfirm.classList.remove('active');
        pendingDeleteId = null;
    }
    delConfirmClose.addEventListener('click', closeDeleteConfirm);
    delConfirmBackdrop.addEventListener('click', closeDeleteConfirm);
    delConfirmCancel.addEventListener('click', closeDeleteConfirm);

    // Confirm delete
    delConfirmYes.addEventListener('click', function() {
        if (!pendingDeleteId) return;

        delConfirmYes.disabled = true;
        delConfirmYes.innerHTML = '<span class="spinner-sm"></span> Menghapus...';

        fetch('{{ route('regional.delete-cell') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ id: pendingDeleteId }),
        })
        .then(function(res) {
            if (!res.ok) return res.json().then(function(e) { throw new Error(e.message || 'Gagal'); });
            return res.json();
        })
        .then(function(json) {
            if (json.success) {
                closeDeleteConfirm();
                window.location.href = '{{ route('regional.index') }}';
            } else {
                alert('Gagal: ' + json.message);
            }
        })
        .catch(function(err) { alert('Error: ' + err.message); })
        .finally(function() {
            delConfirmYes.disabled = false;
            delConfirmYes.innerHTML = '🗑 Ya, Hapus';
        });
    });

    // ── Save Preview ─────────────────────────────────
    previewSave.addEventListener('click', function() {
        if (!previewData) return;

        previewSave.disabled = true;
        previewSave.innerHTML = '<span class="spinner-sm"></span> Memeriksa...';

        // Collect items & unique dates
        var items = [];
        var dateSet = {};
        tablesEl.querySelectorAll('.modal-table[data-tanggal]').forEach(function(table) {
            var tanggal = table.getAttribute('data-tanggal');
            table.querySelectorAll('tbody tr').forEach(function(tr) {
                var province = tr.querySelector('td:first-child').textContent.trim();
                var lead = parseInt(tr.querySelector('.prov-lead').value) || 0;
                var paid = parseInt(tr.querySelector('.prov-paid').value) || 0;
                if (lead > 0 || paid > 0) {
                    items.push({ tanggal: tanggal, province: province, lead: lead, paid: paid });
                    dateSet[tanggal] = true;
                }
            });
        });

        if (items.length === 0) {
            previewSave.disabled = false;
            previewSave.innerHTML = '💾 Simpan Data';
            return;
        }

        // ─── Kumpulkan CS stats ─────────────────────
        var csStatsPayload = [];
        previewCsStats.forEach(function(cs) {
            csStatsPayload.push({
                tanggal: cs.tanggal,
                cs_panggilan: cs.cs_panggilan,
                lead: cs.lead,
                paid: cs.paid
            });
        });

        // ─── Cek tanggal yang SUDAH ada datanya → konfirmasi ──
        // Tanggal yang sudah ada isian akan DIGANTI; yang baru akan DITAMBAH.
        // (Pencegahan kasus "data nyasar ke tanggal lain": tanggal simpan
        //  diambil persis dari tanggal yang terbaca di file preview.)
        var checkData = { dates: Object.keys(dateSet) };
        var advSelect = document.querySelector('select[name="user_id"]');
        if (advSelect) checkData.user_id = advSelect.value;

        fetch('{{ route('regional.check-existing') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(checkData),
        })
        .then(function(res) {
            if (!res.ok) return res.json().then(function(e) { throw new Error(e.message || 'Gagal'); });
            return res.json();
        })
        .then(function(json) {
            previewSave.disabled = false;
            previewSave.innerHTML = '💾 Simpan Data';

            var existing = json.existing_dates || [];
            var existingSet = {};
            existing.forEach(function(d) { existingSet[d] = true; });

            var dates = Object.keys(dateSet).sort();
            var html = '';
            var hasExisting = false;
            dates.forEach(function(tgl) {
                var lbl = new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                var isExisting = !!existingSet[tgl];
                if (isExisting) hasExisting = true;
                html += '<div style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;font-size:.78rem;font-weight:600;'
                     + (isExisting ? 'background:#fef2f2;color:#991b1b;' : 'background:#f0fdf4;color:#065f46;') + '">'
                     + '📅 ' + lbl
                     + '<span style="margin-left:auto;font-size:.64rem;font-weight:800;">'
                     + (isExisting ? 'SUDAH ADA → AKAN DIGANTI' : 'BARU → AKAN DITAMBAH')
                     + '</span></div>';
            });
            saveConfirmDates.innerHTML = html;
            saveConfirmWarning.style.display = hasExisting ? '' : 'none';

            pendingSave = { items: items, csStatsPayload: csStatsPayload, phoneContacts: previewPhoneContacts };
            mSaveConfirm.classList.add('active');
        })
        .catch(function(err) {
            previewSave.disabled = false;
            previewSave.innerHTML = '💾 Simpan Data';
            alert('Gagal memeriksa tanggal: ' + err.message);
        });
    });

    // ── Konfirmasi Simpan ───────────────────────────
    function closeSaveConfirm() {
        mSaveConfirm.classList.remove('active');
        pendingSave = null;
    }
    saveConfirmClose.addEventListener('click', closeSaveConfirm);
    saveConfirmBackdrop.addEventListener('click', closeSaveConfirm);
    saveConfirmCancel.addEventListener('click', closeSaveConfirm);

    saveConfirmYes.addEventListener('click', function() {
        if (!pendingSave) return;
        var p = pendingSave;
        pendingSave = null;
        closeSaveConfirm();
        doSave(p.items, p.csStatsPayload, p.phoneContacts);
    });

    function doSave(items, csStatsPayload, phoneContacts) {
        previewSave.disabled = true;
        previewSave.innerHTML = '<span class="spinner-sm"></span> Menyimpan...';

        var saveData = { items: items, cs_stats: csStatsPayload, phone_contacts: phoneContacts };

        // Kirim target_user_id jika ada selector advertiser
        var advSelect = document.querySelector('select[name="user_id"]');
        if (advSelect) {
            saveData.target_user_id = advSelect.value;
        }

        fetch('{{ route('regional.save') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(saveData),
        })
        .then(function(res) {
            if (!res.ok) return res.json().then(function(e) { throw new Error(e.message || 'Gagal'); });
            return res.json();
        })
        .then(function(json) {
            if (json.success) {
                closePreviewModal();
                window.location.href = '{{ route('regional.index') }}';
            } else {
                alert('Gagal: ' + json.message);
            }
        })
        .catch(function(err) { alert('Error: ' + err.message); })
        .finally(function() {
            previewSave.disabled = false;
            previewSave.innerHTML = '💾 Simpan Data';
        });
    }

})();

// ── Sticky header offset: ukur tinggi header row 1 supaya header row 2
//    tetap menempel rapi tanpa celah/susun saat scroll vertikal. ──
(function fixRegStickyHead() {
    function apply() {
        var wrap = document.querySelector('.reg-scroll-wrap');
        if (!wrap) return;
        var rows = wrap.querySelectorAll('thead tr');
        if (rows.length < 2) return;
        var h1 = rows[0].getBoundingClientRect().height;
        wrap.style.setProperty('--reg-head2', h1 + 'px');
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', apply);
    } else {
        apply();
    }
    window.addEventListener('load', apply);
    window.addEventListener('resize', apply);
})();

</script>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
{{-- ── Chart Bar Horizontal: Lead per Daerah ── --}}
(function() {
    'use strict';
    var canvas = document.getElementById('regionalChart');
    var scrollWrap = document.getElementById('reg-chart-scroll');
    if (!canvas) return;

    var labels = @json($chartProvinces);
    var leadData = @json($chartLeads);

    if (!labels.length) return;

    // Hitung tinggi canvas dinamis: 32px per bar + padding atas-bawah
    var barHeight = 32;
    var topPad = 10;
    var bottomPad = 5;
    var neededHeight = labels.length * barHeight + topPad + bottomPad;
    canvas.parentElement.style.height = neededHeight + 'px';

    var sharedScaleConfig = {
        x: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: { font: { size: 10 }, color: '#9ca3af', callback: function(v) { return v.toLocaleString('id-ID'); } }
        },
        y: {
            grid: { display: false },
            ticks: { font: { size: 10, weight: '600' }, color: '#1e1b2e', padding: 4 }
        }
    };

    // 1) Main chart — bars only (x-axis hidden)
    var mainChart = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Lead',
                data: leadData,
                backgroundColor: 'rgba(139,92,246,0.7)',
                borderColor: '#8b5cf6',
                borderWidth: 1,
                borderRadius: 4,
                maxBarThickness: 24
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e1b2e',
                    titleFont: { size: 12, weight: '700' },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) { return ' Lead: ' + ctx.parsed.x.toLocaleString('id-ID'); }
                    }
                }
            },
            scales: {
                x: { ...sharedScaleConfig.x, display: false },
                y: sharedScaleConfig.y
            }
        }
    });
})();
</script>
@endpush