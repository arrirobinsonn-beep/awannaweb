@extends('layouts.app')
@section('title','Spending Harian')
@section('page-title','💸 Spending Harian')
@section('page-subtitle','Data pengeluaran iklan semua advertiser')

@section('content')

{{-- Filter --}}
<div class="clay-card" style="padding:16px;margin-bottom:20px;" data-reveal>
    <form method="GET" action="{{ route('spending.index') }}" id="filter-form-gen"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <x-date-range-picker
            :dari="$dari"
            :sampai="$sampai"
            form-id="filter-form-gen"
            input-dari="dari"
            input-sampai="sampai"
            extra-inputs="<input type='hidden' name='tab' id='hidden-tab' value='{{ $activeTab }}'>"
        />

        <a href="{{ route('spending.index') }}" class="clay-btn clay-btn-outline">Reset</a>
    </form>
</div>

@if($advertisers->isEmpty())
<div class="clay-card" style="padding:48px;text-align:center;" data-reveal>
    <div style="font-size:2.5rem;margin-bottom:8px;">💸</div>
    <p style="color:#9ca3af;">Belum ada data spending di periode ini.</p>
</div>
@else

{{-- ── Folder Tabs ─────────────────────────────────────────────── --}}
<div style="display:flex;flex-wrap:wrap;gap:0;align-items:flex-end;
            margin-bottom:-2px;position:relative;z-index:2;" data-reveal>
    @foreach($advertisers as $adv)
    @php $isActive = ($activeTab == $adv->id); @endphp
    <button onclick="switchFolder({{ $adv->id }})"
            id="folder-tab-{{ $adv->id }}"
            style="padding:9px 18px 11px;
                   border:2px solid {{ $isActive?'rgba(255,107,107,.25)':'rgba(0,0,0,.08)' }};
                   border-bottom:2px solid {{ $isActive?'#fff':'rgba(0,0,0,.08)' }};
                   border-radius:14px 14px 0 0;
                   background:{{ $isActive?'#fff':'#f5f5f5' }};
                   font-family:inherit;font-size:.82rem;
                   font-weight:{{ $isActive?'700':'500' }};
                   color:{{ $isActive?'var(--color-primary,#FF6B6B)':'#6b7280' }};
                   cursor:pointer;transition:all .2s;
                   display:flex;align-items:center;gap:8px;
                   margin-right:4px;position:relative;z-index:{{ $isActive?3:1 }};">
        <img src="{{ $adv->avatar_url }}"
             style="width:22px;height:22px;border-radius:6px;object-fit:cover;flex-shrink:0;
                    border:{{ $isActive?'1.5px solid rgba(255,107,107,.3)':'1.5px solid #ddd' }};">
        {{ $adv->display_name }}
        @php $advTotal = $dataPerAdvertiser[$adv->id]['summaries']->sum('spending'); @endphp
        <span style="font-size:.7rem;font-weight:600;padding:1px 7px;border-radius:999px;
                     background:{{ $isActive?'rgba(255,107,107,.12)':'rgba(0,0,0,.06)' }};
                     color:{{ $isActive?'var(--color-primary)':'#9ca3af' }};">
            Rp {{ number_format($advTotal/1000,0,',','.') }}k
        </span>
    </button>
    @endforeach
</div>

{{-- ── Konten Folder per Advertiser ────────────────────────────── --}}
@foreach($advertisers as $adv)
@php $data = $dataPerAdvertiser[$adv->id]; @endphp

<div id="folder-content-{{ $adv->id }}"
     style="display:{{ $activeTab==$adv->id?'block':'none' }};
            border:2px solid rgba(255,107,107,.18);border-radius:0 16px 16px 16px;
            background:#fff;overflow:hidden;position:relative;z-index:1;">


    {{-- ⚠️ Alarm Banner per Advertiser --}}
    @if($data['has_discrepancy'])
    <div class="clay-alert clay-alert-error" style="margin:12px 16px;" data-reveal>
        <span>🚨</span>
        <div style="flex:1;font-size:.78rem;">
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
        </div>
    </div>
    @endif

    <div class="table-scroll">
        <table class="clay-table">
            <thead>
                <tr>
                    <th style="width:28px;"></th>
                    <th>Tanggal</th>
                    <th style="text-align:right;">Total Spending</th>
                    <th style="text-align:right;">Lead</th>
                    <th style="text-align:right;">Paid</th>
                    <th style="text-align:right;">Paid Ratio</th>
                    <th style="text-align:right;">CPA Lead</th>
                    <th style="text-align:right;">CPA Paid</th>
                </tr>
            </thead>
            <tbody>
            @if($data['summaries']->isEmpty())
            {{-- Empty state untuk advertiser tanpa data --}}
            <tr>
                <td colspan="8" style="text-align:center;padding:48px 16px;">
                    <div style="font-size:2.5rem;margin-bottom:8px;">💸</div>
                    <p style="color:#9ca3af;">Advertiser ini belum mencantumkan spending iklan apapun</p>
                </td>
            </tr>
            @else

            @foreach($data['summaries'] as $dateKey => $s)
            @php
                $lvl1 = 'g1-'.$adv->id.'-'.str_replace('-','',$dateKey);
                $isDisc = isset($data['discrepant_dates'][$dateKey]);
            @endphp

            {{-- ── LEVEL 1: Baris Tanggal ──────────────────────── --}}
            <tr onclick="tog('{{ $lvl1 }}')" style="cursor:pointer;background:{{ $isDisc?'#fff0f0':'' }};"
                onmouseenter="this.style.background='{{ $isDisc?'#ffe0e0':'#fffbfb' }}'"
                onmouseleave="this.style.background='{{ $isDisc?'#fff0f0':'' }}'">
                <td style="text-align:center;padding:11px 8px;">
                    <span id="chev-{{ $lvl1 }}"
                          style="display:inline-block;transition:transform .22s;
                                 color:#9ca3af;font-size:.78rem;">▶</span>
                </td>
                <td style="font-weight:700;font-size:.88rem;">
                    @if($isDisc)<span style="color:#ef4444;margin-right:6px;">⚠️</span>@endif
                    {{ $s['tanggal']->translatedFormat('l, d M Y') }}
                    @if($isDisc)
                    <span style="display:inline-block;background:#fef2f2;color:#dc2626;font-size:.6rem;font-weight:700;padding:0 6px;border-radius:999px;margin-left:6px;vertical-align:middle;">DATA TIDAK SESUAI</span>
                    @endif
                    <div style="font-size:.68rem;color:#9ca3af;font-weight:400;">
                        {{ $s['total_produk'] }} produk diiklankan
                    </div>
                </td>
                <td style="text-align:right;font-weight:800;color:var(--color-primary);white-space:nowrap;">
                    Rp {{ number_format($s['spending'],0,',','.') }}
                </td>
                <td style="text-align:right;font-weight:700;color:var(--color-purple);">{{ number_format($s['lead']) }}</td>
                <td style="text-align:right;font-weight:700;color:var(--color-secondary);">{{ number_format($s['paid']) }}</td>
                <td style="text-align:right;">
                    <span class="clay-badge {{ $s['paid_ratio']>=75?'clay-badge-green':($s['paid_ratio']>=50?'clay-badge-yellow':'clay-badge-red') }}">
                        {{ round($s['paid_ratio']) }}%
                    </span>
                </td>
                <td style="text-align:right;font-size:.82rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($s['cpa_lead'],0,',','.') }}</td>
                <td style="text-align:right;font-size:.82rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($s['cpa_paid'],0,',','.') }}</td>
            </tr>

            {{-- ── LEVEL 1 Expand ──────────────────────────────── --}}
            <tr id="{{ $lvl1 }}" style="display:none;">
                <td colspan="8" style="padding:0;background:#fafafa;
                    border-top:2px dashed rgba(255,107,107,.1);">

                    @foreach($s['by_product'] as $prodId => $prodData)
                    @php $lvl2 = 'g2-'.$adv->id.'-'.str_replace('-','',$dateKey).'-'.$prodId; @endphp

                    {{-- ── LEVEL 2: Header Produk ──────────────── --}}
                    <div style="border-bottom:1px solid rgba(0,0,0,.05);">
                        <div onclick="tog('{{ $lvl2 }}')"
                             style="display:flex;align-items:center;gap:12px;
                                    padding:10px 20px;cursor:pointer;transition:background .15s;"
                             onmouseenter="this.style.background='#f3f4f6'"
                             onmouseleave="this.style.background=''">

                            {{-- Select-all whitelist produk ini (bulk delete) --}}
                            <label style="display:flex;align-items:center;cursor:pointer;flex-shrink:0;"
                                   onclick="event.stopPropagation()"
                                   title="Pilih semua whitelist produk ini untuk dihapus">
                                <input type="checkbox" class="bd-check-all" data-prod="{{ $adv->id }}-{{ $dateKey }}-{{ $prodId }}">
                            </label>

                            <span id="chev-{{ $lvl2 }}"
                                  style="display:inline-block;transition:transform .22s;
                                         color:var(--color-secondary);font-size:.72rem;flex-shrink:0;">▶</span>

                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span style="background:var(--color-secondary);color:#fff;
                                                 font-size:.62rem;font-weight:700;padding:2px 8px;
                                                 border-radius:999px;flex-shrink:0;">📦 Produk</span>
                                    <span style="font-weight:700;font-size:.85rem;color:#1e1b2e;">
                                        {{ $prodData['product']->name ?? 'Tidak Diketahui' }}
                                    </span>
                                    <span style="font-size:.68rem;color:#9ca3af;">
                                        {{ $prodData['product']->code ?? '' }}
                                    </span>
                                    @if(($prodData['product']->ad_status ?? 'running') === 'testing')
                                    <span style="display:inline-block;font-size:.58rem;font-weight:700;padding:1px 6px;border-radius:999px;background:#fef3c7;color:#92400e;">🔬 Testing</span>
                                    @endif
                                </div>
                                <div style="font-size:.67rem;color:#9ca3af;margin-top:2px;padding-left:52px;">
                                    {{ count($prodData['whitelists']) }} whitelist mengiklankan produk ini
                                </div>
                            </div>

                            <div style="display:flex;gap:14px;flex-shrink:0;align-items:center;">
                                <div style="text-align:right;">
                                    <div style="font-size:.66rem;color:#9ca3af;">Spending</div>
                                    <div style="font-weight:700;font-size:.82rem;color:var(--color-primary);white-space:nowrap;">
                                        Rp {{ number_format($prodData['spending'],0,',','.') }}
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:.66rem;color:#9ca3af;">Lead / Paid</div>
                                    <div style="font-weight:700;font-size:.82rem;">
                                        <span style="color:var(--color-purple);">{{ $prodData['lead'] }}</span>
                                        <span style="color:#d1d5db;"> / </span>
                                        <span style="color:var(--color-secondary);">{{ $prodData['paid'] }}</span>
                                    </div>
                                </div>
                                <span class="clay-badge {{ $prodData['paid_ratio']>=75?'clay-badge-green':($prodData['paid_ratio']>=50?'clay-badge-yellow':'clay-badge-red') }}"
                                      style="font-size:.67rem;">{{ round($prodData['paid_ratio']) }}%</span>
                            </div>
                        </div>

                        {{-- ── LEVEL 3: Whitelist rows ──────────── --}}
                        <div id="{{ $lvl2 }}"
                             style="display:none;background:#fff;
                                    border-top:1px dashed rgba(78,205,196,.2);">
                            <table style="width:100%;">
                                <thead>
                                    <tr style="background:#f9fefe;">
                                        <th style="padding:6px 20px 6px 36px;font-size:.64rem;font-weight:700;
                                                   color:#9ca3af;text-transform:uppercase;text-align:left;
                                                   border-bottom:1px solid rgba(0,0,0,.05);">Whitelist</th>
                                        @foreach(['Spending','Lead','Paid','Paid Ratio','CPA Lead','CPA Paid'] as $h)
                                        <th style="padding:6px 10px;font-size:.64rem;font-weight:700;
                                                   color:#9ca3af;text-transform:uppercase;text-align:right;
                                                   border-bottom:1px solid rgba(0,0,0,.05);">{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($prodData['whitelists'] as $item)
                                <tr onmouseenter="this.style.background='#f0fffe'"
                                    onmouseleave="this.style.background=''">
                                    <td style="padding:7px 20px 7px 36px;">
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            {{-- Checkbox bulk delete --}}
                                            <input type="checkbox" class="bd-check"
                                                   data-id="{{ $item->id }}"
                                                   data-prod="{{ $adv->id }}-{{ $dateKey }}-{{ $prodId }}"
                                                   data-tanggal="{{ $dateKey }}"
                                                   data-product-id="{{ $prodId }}"
                                                   data-product-name="{{ $prodData['product']->name ?? '' }}"
                                                   data-product-code="{{ $prodData['product']->code ?? '' }}"
                                                   data-whitelist-name="{{ $item->whitelist->nama ?? '' }}"
                                                   data-whitelist-code="{{ $item->whitelist->kode ?? '' }}"
                                                   data-spending="{{ $item->spending }}"
                                                   data-lead="{{ $item->lead }}"
                                                   data-paid="{{ $item->paid }}"
                                                   title="Pilih untuk dihapus"
                                                   style="flex-shrink:0;">
                                            <div style="min-width:0;">
                                                <div style="font-weight:600;font-size:.8rem;">{{ $item->whitelist->nama ?? '-' }}</div>
                                                <div style="font-size:.65rem;color:#9ca3af;">{{ $item->whitelist->kode ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:7px 10px;text-align:right;font-weight:700;
                                               color:var(--color-primary);font-size:.78rem;white-space:nowrap;">
                                        Rp {{ number_format($item->spending,0,',','.') }}
                                    </td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.78rem;color:var(--color-purple);font-weight:700;">{{ $item->lead }}</td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.78rem;color:var(--color-secondary);font-weight:700;">{{ $item->paid }}</td>
                                    <td style="padding:7px 10px;text-align:right;">
                                        <span class="clay-badge {{ $item->paid_ratio>=75?'clay-badge-green':($item->paid_ratio>=50?'clay-badge-yellow':'clay-badge-red') }}"
                                              style="font-size:.64rem;">{{ round($item->paid_ratio) }}%</span>
                                    </td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.74rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($item->cpa_lead,0,',','.') }}</td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.74rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($item->cpa_paid,0,',','.') }}</td>
                                </tr>
                                @endforeach
                                {{-- Total baris produk --}}
                                <tr style="background:#f0fffe;font-weight:700;">
                                    <td style="padding:6px 20px 6px 36px;font-size:.74rem;color:var(--color-secondary);">Total</td>
                                    <td style="padding:6px 10px;text-align:right;font-size:.78rem;color:var(--color-primary);white-space:nowrap;">Rp {{ number_format($prodData['spending'],0,',','.') }}</td>
                                    <td style="padding:6px 10px;text-align:right;font-size:.78rem;color:var(--color-purple);">{{ $prodData['lead'] }}</td>
                                    <td style="padding:6px 10px;text-align:right;font-size:.78rem;color:var(--color-secondary);">{{ $prodData['paid'] }}</td>
                                    <td style="padding:6px 10px;text-align:right;">
                                        <span class="clay-badge {{ $prodData['paid_ratio']>=75?'clay-badge-green':($prodData['paid_ratio']>=50?'clay-badge-yellow':'clay-badge-red') }}"
                                              style="font-size:.64rem;">{{ $prodData['paid_ratio'] }}%</span>
                                    </td>
                                    <td style="padding:6px 10px;text-align:right;font-size:.74rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($prodData['cpa_lead'],0,',','.') }}</td>
                                    <td style="padding:6px 10px;text-align:right;font-size:.74rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($prodData['cpa_paid'],0,',','.') }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>{{-- end produk --}}
                    @endforeach

                </td>
            </tr>{{-- end lvl1 expand --}}

            @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>
@endforeach

@endif

{{-- ═══════════════ BULK DELETE (centang produk/whitelist) ═══════════════ --}}
<form method="POST" action="{{ route('spending.bulk-destroy') }}" id="bulk-delete-form">
    @csrf
    <div id="bulk-ids"></div>
</form>
<div class="bulk-bar" id="bulk-bar">
    <span style="font-size:.8rem;font-weight:700;color:#b91c1c;">🗑 <span id="bulk-count">0</span> data terpilih</span>
    <button type="button" id="bulk-edit" class="clay-btn clay-btn-secondary" style="padding:6px 14px;font-size:.75rem;">✏️ Edit</button>
    <button type="button" id="bulk-clear" class="clay-btn clay-btn-outline" style="padding:6px 14px;font-size:.75rem;">Batal</button>
    <button type="button" id="bulk-confirm" class="clay-btn clay-btn-danger" style="padding:6px 14px;font-size:.75rem;">Hapus Terpilih</button>
</div>

{{-- ═══════════════ MODAL BULK EDIT (spending/lead/paid) ═══════════════ --}}
<div class="be-modal" id="bulk-edit-modal" role="dialog" aria-modal="true" aria-labelledby="be-title">
    <div class="be-backdrop" onclick="closeBulkEdit()"></div>
    <div class="be-container">
        <div class="be-header">
            <h2 id="be-title">✏️ Edit Data Terpilih</h2>
            <button class="be-close" onclick="closeBulkEdit()" type="button">✕</button>
        </div>
        <form method="POST" action="{{ route('spending.bulk-update') }}" id="bulk-edit-form">
            @csrf
            <div class="be-body">
                <div class="be-info" id="be-info"></div>
                <div class="be-groups" id="be-groups"></div>
            </div>
            <div class="be-footer">
                <button type="button" class="clay-btn clay-btn-outline" onclick="closeBulkEdit()">Batal</button>
                <button type="button" class="clay-btn clay-btn-primary" id="be-save">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    /* ── Bulk delete ────────────────────────────────────── */
    .bulk-bar {
        position: fixed; bottom: 20px; right: 24px; z-index: 60;
        display: none; align-items: center; gap: 10px;
        background: #fff; border: 1px solid #fecaca;
        border-radius: 16px; padding: 10px 14px;
        box-shadow: 0 12px 32px rgba(220,38,38,.18);
        animation: bulkIn .25s ease;
    }
    @keyframes bulkIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: none; }
    }
    .bd-check { width: 16px; height: 16px; accent-color: var(--color-primary, #FF6B6B); cursor: pointer; }
    .bd-check-all { width: 15px; height: 15px; accent-color: var(--color-primary, #FF6B6B); cursor: pointer; }
    tr.bd-row-selected { background: #fff0f0 !important; }

    /* ── Modal Bulk Edit (spending/lead/paid) ───────────── */
    .be-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .be-modal.active { display: flex; }
    .be-modal .be-backdrop {
        position: absolute; inset: 0;
        background: rgba(15,23,42,.55); backdrop-filter: blur(2px);
    }
    .be-modal .be-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 540px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: beIn .22s ease;
    }
    @keyframes beIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .be-modal .be-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .be-modal .be-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .be-modal .be-close {
        background: #f3f4f6; border: none; border-radius: 8px;
        width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280;
        transition: background .15s;
    }
    .be-modal .be-close:hover { background: #e5e7eb; }
    .be-modal .be-body { padding: 14px 20px 8px; }
    .be-info { font-size: .82rem; color: #4b5563; font-weight: 600; margin-bottom: 10px; line-height: 1.5; }
    .be-groups {
        max-height: 48vh; overflow-y: auto; margin-bottom: 10px; padding-right: 4px;
        scrollbar-width: thin; scrollbar-color: #d1d5db transparent;
    }
    .be-date { font-size: .75rem; font-weight: 800; color: var(--color-secondary, #4ECDC4); margin: 10px 0 5px; }
    .be-prod { border: 1px solid rgba(0,0,0,.07); border-radius: 12px; margin-bottom: 8px; overflow: hidden; background: #fff; }
    .be-prod-name {
        display: flex; align-items: center; gap: 6px; padding: 7px 12px;
        background: #f9fefe; font-size: .75rem; font-weight: 700; color: #1e1b2e;
    }
    .be-code { font-size: .62rem; color: #9ca3af; font-weight: 600; }
    .be-row { padding: 8px 12px 10px; border-top: 1px dashed rgba(0,0,0,.06); }
    .be-row-meta { font-size: .72rem; color: #374151; margin-bottom: 6px; }
    .be-old { font-size: .62rem; color: #9ca3af; margin-top: 2px; }
    .be-row-inputs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .be-row-inputs label {
        display: block; font-size: .6rem; font-weight: 700; color: #6b7280;
        margin-bottom: 2px; text-transform: uppercase; letter-spacing: .03em;
    }
    .be-row-inputs .clay-input { font-size: .78rem; padding: 6px 8px; }
    .be-modal .be-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
    }
</style>
@endpush

@push('scripts')
<script>
{{-- ── Bulk Delete (centang produk & whitelist) ─────────────────────────── --}}
(function() {
    'use strict';

    var form = document.getElementById('bulk-delete-form');
    if (!form) return;

    var bar     = document.getElementById('bulk-bar');
    var countEl = document.getElementById('bulk-count');
    var selected = new Set();

    function updateUI() {
        var n = selected.size;
        if (bar) bar.style.display = n ? 'flex' : 'none';
        if (countEl) countEl.textContent = n;

        document.querySelectorAll('.bd-check-all').forEach(function(cb) {
            var group = document.querySelectorAll('.bd-check[data-prod="' + cb.dataset.prod + '"]');
            if (!group.length) { cb.checked = false; cb.indeterminate = false; return; }
            var allChecked = Array.from(group).every(function(c) { return selected.has(c.dataset.id); });
            var anyChecked = Array.from(group).some(function(c) { return selected.has(c.dataset.id); });
            cb.checked = allChecked;
            cb.indeterminate = anyChecked && !allChecked;
        });

        document.querySelectorAll('.bd-check').forEach(function(c) {
            var row = c.closest('tr');
            if (row) row.classList.toggle('bd-row-selected', selected.has(c.dataset.id));
        });
    }

    document.querySelectorAll('.bd-check').forEach(function(cb) {
        cb.addEventListener('change', function() {
            if (cb.checked) selected.add(cb.dataset.id);
            else selected.delete(cb.dataset.id);
            updateUI();
        });
    });

    document.querySelectorAll('.bd-check-all').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var group = document.querySelectorAll('.bd-check[data-prod="' + cb.dataset.prod + '"]');
            group.forEach(function(c) {
                if (cb.checked) selected.add(c.dataset.id);
                else selected.delete(c.dataset.id);
                c.checked = cb.checked;
            });
            updateUI();
        });
    });

    var clearBtn = document.getElementById('bulk-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            selected.clear();
            document.querySelectorAll('.bd-check').forEach(function(c) { c.checked = false; });
            updateUI();
        });
    }

    var confirmBtn = document.getElementById('bulk-confirm');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (!selected.size) return;
            if (!confirm('Hapus ' + selected.size + ' data spending yang terpilih? Tindakan ini tidak dapat dibatalkan.')) return;
            var box = document.getElementById('bulk-ids');
            box.innerHTML = '';
            selected.forEach(function(id) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                box.appendChild(inp);
            });
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-sm"></span> Menghapus...';
            form.submit();
        });
    }

    // ── Bulk Edit (modal grup per tanggal & produk, edit per baris) ──
    var beModal  = document.getElementById('bulk-edit-modal');
    var beInfo   = document.getElementById('be-info');
    var beGroups = document.getElementById('be-groups');
    var beForm   = document.getElementById('bulk-edit-form');

    var BE_MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    function beEsc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function beFmtTanggal(s) {
        if (!s || s === '?') return s;
        var p = s.split('-').map(Number);
        return p[2] + ' ' + BE_MONTHS[(p[1] || 1) - 1] + ' ' + p[0];
    }

    window.openBulkEdit = function() {
        if (!selected.size || !beGroups) return;

        // ── Kumpulkan baris terpilih → grup (tanggal → produk) ──
        var byDate = {};
        var dateKeys = [];

        document.querySelectorAll('.bd-check:checked').forEach(function(c) {
            var tanggal = c.dataset.tanggal || '?';
            var prodKey = c.dataset.productId || '0';
            if (!byDate[tanggal]) {
                byDate[tanggal] = {};
                dateKeys.push(tanggal);
            }
            if (!byDate[tanggal][prodKey]) {
                byDate[tanggal][prodKey] = {
                    name: c.dataset.productName || 'Tidak Diketahui',
                    code: c.dataset.productCode || '',
                    rows: [],
                };
            }
            byDate[tanggal][prodKey].rows.push({
                id: c.dataset.id,
                wl: c.dataset.whitelistName || '-',
                wlCode: c.dataset.whitelistCode || '',
                spending: c.dataset.spending,
                lead: c.dataset.lead,
                paid: c.dataset.paid,
            });
        });

        dateKeys.sort().reverse(); // tanggal terbaru di atas
        var multiDate = dateKeys.length > 1;
        var prodTotal = 0;
        dateKeys.forEach(function(t) { prodTotal += Object.keys(byDate[t]).length; });

        beInfo.textContent = 'Mengedit ' + selected.size + ' data terpilih — ' +
            dateKeys.length + ' tanggal · ' + prodTotal + ' produk. Nilai diisi per baris.';

        var html = '';
        dateKeys.forEach(function(tanggal) {
            var prods = byDate[tanggal];
            if (multiDate) {
                html += '<div class="be-date">📅 ' + beEsc(beFmtTanggal(tanggal)) + '</div>';
            }
            Object.keys(prods).sort(function(a, b) {
                return (prods[a].name || '').localeCompare(prods[b].name || '');
            }).forEach(function(pk) {
                var p = prods[pk];
                html += '<div class="be-prod">';
                html += '<div class="be-prod-name">📦 ' + beEsc(p.name) +
                        (p.code ? ' <span class="be-code">' + beEsc(p.code) + '</span>' : '') + '</div>';
                p.rows.forEach(function(r) {
                    html += '<div class="be-row">';
                    html += '<div class="be-row-meta"><strong>' + beEsc(r.wl) + '</strong>' +
                            (r.wlCode ? ' <span class="be-code">' + beEsc(r.wlCode) + '</span>' : '') +
                            '<div class="be-old">sebelum: Rp ' + Number(r.spending).toLocaleString('id-ID') +
                            ' · lead ' + r.lead + ' · paid ' + r.paid + '</div></div>';
                    html += '<div class="be-row-inputs">';
                    html += '<input type="hidden" name="items[' + r.id + '][id]" value="' + r.id + '">';
                    html += '<div><label>Spending (Rp)</label>' +
                            '<input type="number" class="clay-input" name="items[' + r.id + '][spending]" value="' + r.spending + '" min="0" step="any" required></div>';
                    html += '<div><label>Lead</label>' +
                            '<input type="number" class="clay-input" name="items[' + r.id + '][lead]" value="' + r.lead + '" min="0" required></div>';
                    html += '<div><label>Paid</label>' +
                            '<input type="number" class="clay-input" name="items[' + r.id + '][paid]" value="' + r.paid + '" min="0" required></div>';
                    html += '</div></div>';
                });
                html += '</div>';
            });
        });
        beGroups.innerHTML = html;
        beModal.classList.add('active');
    };

    window.closeBulkEdit = function() {
        beModal.classList.remove('active');
    };

    var editBtn = document.getElementById('bulk-edit');
    if (editBtn) {
        editBtn.addEventListener('click', function() {
            if (!selected.size) return;
            window.openBulkEdit();
        });
    }

    // Simpan: validasi native dulu (bubble field kosong), lalu submit form
    // (input per baris sudah dirender di dalam form → ikut terkirim)
    var beSave = document.getElementById('be-save');
    if (beSave && beForm) {
        beSave.addEventListener('click', function() {
            if (!beForm.reportValidity()) return;
            beSave.disabled = true;
            beSave.innerHTML = '<span class="spinner-sm"></span> Menyimpan...';
            beForm.submit();
        });
    }

    // Tutup dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && beModal && beModal.classList.contains('active')) window.closeBulkEdit();
    });
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

function switchFolder(id) {
    document.querySelectorAll('[id^="folder-content-"]').forEach(function(el) {
        el.style.display = 'none';
    });
    document.querySelectorAll('[id^="folder-tab-"]').forEach(function(btn) {
        btn.style.background   = '#f5f5f5';
        btn.style.color        = '#6b7280';
        btn.style.fontWeight   = '500';
        btn.style.borderColor  = 'rgba(0,0,0,.08)';
        btn.style.borderBottom = '2px solid rgba(0,0,0,.08)';
        btn.style.zIndex       = '1';
        var img   = btn.querySelector('img');
        var badge = btn.querySelector('span');
        if (img)   img.style.borderColor    = '#ddd';
        if (badge) { badge.style.background = 'rgba(0,0,0,.06)'; badge.style.color = '#9ca3af'; }
    });
    var content = document.getElementById('folder-content-' + id);
    var tab     = document.getElementById('folder-tab-'     + id);
    if (content) content.style.display = 'block';
    if (tab) {
        tab.style.background   = '#fff';
        tab.style.color        = 'var(--color-primary,#FF6B6B)';
        tab.style.fontWeight   = '700';
        tab.style.borderColor  = 'rgba(255,107,107,.25)';
        tab.style.borderBottom = '2px solid #fff';
        tab.style.zIndex       = '3';
        var img   = tab.querySelector('img');
        var badge = tab.querySelector('span');
        if (img)   img.style.borderColor    = 'rgba(255,107,107,.3)';
        if (badge) { badge.style.background = 'rgba(255,107,107,.12)'; badge.style.color = 'var(--color-primary)'; }
    }
    var hiddenTab = document.getElementById('hidden-tab');
    if (hiddenTab) hiddenTab.value = id;
}
</script>
@endpush
@endsection
