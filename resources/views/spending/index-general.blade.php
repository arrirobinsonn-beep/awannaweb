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
            @foreach($data['discrepancies'] as $tgl => $d)
            <div style="margin-top:3px;font-size:.74rem;">
                📅 {{ \Carbon\Carbon::parse($tgl)->translatedFormat('d M') }} —
                Regional: Lead {{ $d['regional_lead'] }}, Paid {{ $d['regional_paid'] }} |
                Spending: Lead {{ $d['spending_lead'] }}, Paid {{ $d['spending_paid'] }}
            </div>
            @endforeach
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
                    <span class="clay-badge {{ $s['paid_ratio']>=30?'clay-badge-green':($s['paid_ratio']>=10?'clay-badge-yellow':'clay-badge-red') }}">
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

                            <span id="chev-{{ $lvl2 }}"
                                  style="display:inline-block;transition:transform .22s;
                                         color:var(--color-secondary);font-size:.72rem;flex-shrink:0;">▶</span>

                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span style="background:var(--color-secondary);color:#fff;
                                                 font-size:.62rem;font-weight:700;padding:2px 8px;
                                                 border-radius:999px;flex-shrink:0;">📦 Produk</span>
                                    <span style="font-weight:700;font-size:.85rem;color:#1e1b2e;">
                                        {{ $prodData['product']->nama_produk ?? 'Tidak Diketahui' }}
                                    </span>
                                    <span style="font-size:.68rem;color:#9ca3af;">
                                        {{ $prodData['product']->kode_produk ?? '' }}
                                    </span>
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
                                <span class="clay-badge {{ $prodData['paid_ratio']>=30?'clay-badge-green':($prodData['paid_ratio']>=10?'clay-badge-yellow':'clay-badge-red') }}"
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
                                        <div style="font-weight:600;font-size:.8rem;">{{ $item->whitelist->nama ?? '-' }}</div>
                                        <div style="font-size:.65rem;color:#9ca3af;">{{ $item->whitelist->kode ?? '' }}</div>
                                    </td>
                                    <td style="padding:7px 10px;text-align:right;font-weight:700;
                                               color:var(--color-primary);font-size:.78rem;white-space:nowrap;">
                                        Rp {{ number_format($item->spending,0,',','.') }}
                                    </td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.78rem;color:var(--color-purple);font-weight:700;">{{ $item->lead }}</td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.78rem;color:var(--color-secondary);font-weight:700;">{{ $item->paid }}</td>
                                    <td style="padding:7px 10px;text-align:right;">
                                        <span class="clay-badge {{ $item->paid_ratio>=30?'clay-badge-green':($item->paid_ratio>=10?'clay-badge-yellow':'clay-badge-red') }}"
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
                                        <span class="clay-badge {{ $prodData['paid_ratio']>=30?'clay-badge-green':($prodData['paid_ratio']>=10?'clay-badge-yellow':'clay-badge-red') }}"
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
