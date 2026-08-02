@extends('layouts.app')
@section('title','Spending Harian')
@section('page-title','💸 Spending Harian Saya')
@section('page-subtitle','Data iklan harian — {{ $user->display_name }}')

@section('content')

{{-- ⚠️ Alarm Banner --}}
@if($hasDiscrepancy)
<div class="clay-alert clay-alert-error" data-reveal style="margin-bottom:16px;">
    <span>🚨</span>        <div style="flex:1;font-size:.83rem;">
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
        </div>
</div>
@endif

@if($csDiscrepancy['has_discrepancy'] ?? false)
<div class="clay-alert clay-alert-warning" data-reveal style="margin-top:8px;margin-bottom:16px;">
    <span>🔔</span>        <div style="flex:1;font-size:.83rem;">
            <strong>Koreksi Data oleh CS!</strong> Tim CS telah menginput data yang berbeda untuk tanggal berikut:
            @if(count($csDiscrepancy['dates']) > 5)
            <div style="margin-top:6px;font-size:.7rem;color:#b45309;font-weight:600;">
                ⬇ Menampilkan 5 dari {{ count($csDiscrepancy['dates']) }} tanggal — scroll untuk melihat sisanya
            </div>
            @endif
            <div style="margin-top:4px;max-height:112px;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:#d1d5db transparent;padding-right:6px;">
                @foreach($csDiscrepancy['dates'] as $tgl => $d)
                <div style="margin-top:4px;font-size:.78rem;line-height:1.45;">
                    📅 {{ \Carbon\Carbon::parse($tgl)->translatedFormat('d M Y') }} —
                    Data CS: Lead {{ $d['cs_lead'] }}, Paid {{ $d['cs_paid'] }} |
                    Data Anda: Lead {{ $d['adv_lead'] }}, Paid {{ $d['adv_paid'] }}
                    @if($d['cs_lead'] != $d['adv_lead'] || $d['cs_paid'] != $d['adv_paid'])
                    <span style="color:#dc2626;font-weight:700;"> ⚠️ Ada selisih</span>
                    @endif
                </div>
                @endforeach
            </div>
            <div style="margin-top:6px;font-size:.75rem;color:#6b7280;">
                Silakan sesuaikan data Anda agar sesuai dengan data real.
            </div>
        </div>
</div>
@endif

{{-- Filter --}}
<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="GET" action="{{ route('spending.index') }}" id="filter-form-adv"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <x-date-range-picker
            :dari="$dari"
            :sampai="$sampai"
            form-id="filter-form-adv"
            input-dari="dari"
            input-sampai="sampai"
        />

        <a href="{{ route('spending.index') }}" class="clay-btn clay-btn-outline">Reset</a>
        <a href="{{ route('spending.create') }}" class="clay-btn clay-btn-primary"
           style="margin-left:auto;" data-page-link>＋ Input Spending</a>
    </form>
</div>

{{-- ──────────────────────────────────────────────────────────────
     Struktur 3 level:
     Level 1 → Baris tanggal   (klik → expand)
     Level 2 → Sub-grup produk (di dalam tanggal)
     Level 3 → Baris whitelist (di dalam produk)
─────────────────────────────────────────────────────────────── --}}
<div class="clay-card" style="overflow:hidden;" data-reveal>
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
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($summaries as $dateKey => $s)
            @php
                $lvl1Id = 'lvl1-' . str_replace('-','',$dateKey);
                $isDisc = isset($discrepantDates[$dateKey]);
            @endphp

            {{-- ── LEVEL 1: Baris Tanggal ──────────────────────── --}}
            <tr onclick="toggle('{{ $lvl1Id }}')" style="cursor:pointer;background:{{ $isDisc?'#fff0f0':'#fff' }};"
                onmouseenter="this.style.background='{{ $isDisc?'#ffe0e0':'#fffbfb' }}'"
                onmouseleave="this.style.background='{{ $isDisc?'#fff0f0':'#fff' }}'">
                <td style="text-align:center;padding:11px 8px;">
                    <span id="chev-{{ $lvl1Id }}"
                          style="display:inline-block;transition:transform .22s;color:#9ca3af;font-size:.78rem;">▶</span>
                </td>
                <td style="font-weight:700;font-size:.9rem;">
                    @if($isDisc)<span style="color:#ef4444;margin-right:6px;">⚠️</span>@endif
                    {{ $s['tanggal']->translatedFormat('l, d M Y') }}
                    @if($isDisc)
                    <span style="display:inline-block;background:#fef2f2;color:#dc2626;font-size:.6rem;font-weight:700;padding:0 6px;border-radius:999px;margin-left:6px;vertical-align:middle;">DATA TIDAK SESUAI</span>
                    @endif
                    <div style="font-size:.68rem;color:#9ca3af;font-weight:400;">
                        {{ $s['total_produk'] }} produk · {{ $s['by_product']->sum(fn($p)=>count($p['whitelists'])) }} whitelist
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
                <td style="text-align:right;" onclick="event.stopPropagation()">
                    <a href="{{ route('spending.create') }}?tanggal={{ $dateKey }}"
                       class="clay-btn clay-btn-primary" style="padding:4px 10px;font-size:.7rem;" data-page-link>＋</a>
                </td>
            </tr>

            {{-- ── LEVEL 1 Expand ──────────────────────────────── --}}
            <tr id="{{ $lvl1Id }}" style="display:none;">
                <td colspan="9" style="padding:0;background:#fafafa;border-top:2px dashed rgba(255,107,107,.12);">

                    @foreach($s['by_product'] as $prodId => $prodData)
                    @php $lvl2Id = 'lvl2-' . str_replace('-','',$dateKey) . '-' . $prodId; @endphp

                    {{-- ── LEVEL 2: Sub-grup Produk ─────────────── --}}
                    <div style="border-bottom:1px solid rgba(0,0,0,.05);background:#fafafa;">

                        {{-- Header produk (klik → expand whitelist) --}}
                        <div onclick="toggle('{{ $lvl2Id }}')"
                             style="display:flex;align-items:center;gap:12px;padding:10px 20px;
                                    cursor:pointer;transition:background .15s;"
                             onmouseenter="this.style.background='#f3f4f6'"
                             onmouseleave="this.style.background=''">

                            <span id="chev-{{ $lvl2Id }}"
                                  style="display:inline-block;transition:transform .22s;
                                         color:var(--color-secondary);font-size:.75rem;flex-shrink:0;">▶</span>

                            {{-- Label produk --}}
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="background:var(--color-secondary);color:#fff;
                                                 font-size:.65rem;font-weight:700;padding:2px 8px;
                                                 border-radius:999px;flex-shrink:0;">📦 Produk</span>
                                    <span style="font-weight:700;font-size:.85rem;color:#1e1b2e;">
                                        {{ $prodData['product']->nama_produk ?? 'Tidak Diketahui' }}
                                    </span>
                                    <span style="font-size:.68rem;color:#9ca3af;">
                                        {{ $prodData['product']->kode_produk ?? '' }}
                                    </span>
                                </div>
                                <div style="font-size:.68rem;color:#9ca3af;margin-top:2px;margin-left:56px;">
                                    {{ count($prodData['whitelists']) }} whitelist mengiklankan produk ini
                                </div>
                            </div>

                            {{-- Summary produk --}}
                            <div style="display:flex;gap:16px;flex-shrink:0;align-items:center;">
                                <div style="text-align:right;">
                                    <div style="font-size:.68rem;color:#9ca3af;">Spending</div>
                                    <div style="font-weight:700;font-size:.82rem;color:var(--color-primary);white-space:nowrap;">
                                        Rp {{ number_format($prodData['spending'],0,',','.') }}
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:.68rem;color:#9ca3af;">Lead / Paid</div>
                                    <div style="font-weight:700;font-size:.82rem;">
                                        <span style="color:var(--color-purple);">{{ $prodData['lead'] }}</span>
                                        <span style="color:#d1d5db;"> / </span>
                                        <span style="color:var(--color-secondary);">{{ $prodData['paid'] }}</span>
                                    </div>
                                </div>
                                <span class="clay-badge {{ $prodData['paid_ratio']>=30?'clay-badge-green':($prodData['paid_ratio']>=10?'clay-badge-yellow':'clay-badge-red') }}"
                                      style="font-size:.68rem;">
                                    {{ $prodData['paid_ratio'] }}%
                                </span>
                            </div>
                        </div>

                        {{-- ── LEVEL 3: Baris Whitelist ────────── --}}
                        <div id="{{ $lvl2Id }}" style="display:none;background:#fff;
                             border-top:1px dashed rgba(78,205,196,.2);padding:0 0 6px 0;">
                            <table style="width:100%;">
                                <thead>
                                    <tr style="background:#f9fefe;">
                                        @foreach(['Whitelist','Spending','Lead','Paid','Paid Ratio','CPA Lead','CPA Paid','Aksi'] as $h)
                                        <th style="padding:7px {{ in_array($h,['Whitelist']) ? '20px 7px 36px' : '10px' }};
                                                   font-size:.65rem;font-weight:700;color:#9ca3af;
                                                   text-transform:uppercase;letter-spacing:.05em;
                                                   text-align:{{ in_array($h,['Whitelist','Aksi']) ? ($h==='Whitelist'?'left':'right') : 'right' }};
                                                   border-bottom:1px solid rgba(0,0,0,.05);">{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($prodData['whitelists'] as $item)
                                <tr onmouseenter="this.style.background='#f0fffe'"
                                    onmouseleave="this.style.background=''">
                                    <td style="padding:8px 20px 8px 36px;">
                                        <div style="font-weight:600;font-size:.8rem;">
                                            {{ $item->whitelist->nama ?? '-' }}
                                        </div>
                                        <div style="font-size:.66rem;color:#9ca3af;">
                                            {{ $item->whitelist->kode ?? '' }}
                                        </div>
                                    </td>
                                    <td style="padding:8px 10px;text-align:right;font-weight:700;
                                               color:var(--color-primary);font-size:.8rem;white-space:nowrap;">
                                        Rp {{ number_format($item->spending,0,',','.') }}
                                    </td>
                                    <td style="padding:8px 10px;text-align:right;color:var(--color-purple);
                                               font-weight:700;font-size:.8rem;">{{ $item->lead }}</td>
                                    <td style="padding:8px 10px;text-align:right;color:var(--color-secondary);
                                               font-weight:700;font-size:.8rem;">{{ $item->paid }}</td>
                                    <td style="padding:8px 10px;text-align:right;">
                                        <span class="clay-badge {{ $item->paid_ratio>=30?'clay-badge-green':($item->paid_ratio>=10?'clay-badge-yellow':'clay-badge-red') }}"
                                              style="font-size:.65rem;">{{ round($item->paid_ratio) }}%</span>
                                    </td>
                                    <td style="padding:8px 10px;text-align:right;font-size:.75rem;color:#6b7280;white-space:nowrap;">
                                        Rp {{ number_format($item->cpa_lead,0,',','.') }}
                                    </td>
                                    <td style="padding:8px 10px;text-align:right;font-size:.75rem;color:#6b7280;white-space:nowrap;">
                                        Rp {{ number_format($item->cpa_paid,0,',','.') }}
                                    </td>
                                    <td style="padding:8px 10px;text-align:right;">
                                        <div style="display:flex;justify-content:flex-end;gap:4px;">
                                            <a href="{{ route('spending.edit',$item) }}"
                                               class="clay-btn clay-btn-secondary"
                                               style="padding:3px 8px;font-size:.65rem;" data-page-link>✏️</a>
                                            <form method="POST" action="{{ route('spending.destroy',$item) }}"
                                                  onsubmit="return confirm('Hapus data ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="clay-btn clay-btn-danger"
                                                        style="padding:3px 8px;font-size:.65rem;">🗑</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach

                                {{-- Total baris produk --}}
                                <tr style="background:#f0fffe;font-weight:700;">
                                    <td style="padding:7px 20px 7px 36px;font-size:.75rem;color:var(--color-secondary);">
                                        Total Produk
                                    </td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.8rem;color:var(--color-primary);white-space:nowrap;">
                                        Rp {{ number_format($prodData['spending'],0,',','.') }}
                                    </td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.8rem;color:var(--color-purple);">{{ $prodData['lead'] }}</td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.8rem;color:var(--color-secondary);">{{ $prodData['paid'] }}</td>
                                    <td style="padding:7px 10px;text-align:right;">
                                        <span class="clay-badge {{ $prodData['paid_ratio']>=30?'clay-badge-green':($prodData['paid_ratio']>=10?'clay-badge-yellow':'clay-badge-red') }}"
                                              style="font-size:.65rem;">{{ $prodData['paid_ratio'] }}%</span>
                                    </td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.75rem;color:#6b7280;white-space:nowrap;">
                                        Rp {{ number_format($prodData['cpa_lead'],0,',','.') }}
                                    </td>
                                    <td style="padding:7px 10px;text-align:right;font-size:.75rem;color:#6b7280;white-space:nowrap;">
                                        Rp {{ number_format($prodData['cpa_paid'],0,',','.') }}
                                    </td>
                                    <td></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>{{-- end produk block --}}
                    @endforeach

                </td>
            </tr>{{-- end level 1 expand --}}

            @empty
            <tr>
                <td colspan="9" style="text-align:center;padding:48px;">
                    <div style="font-size:2.5rem;margin-bottom:8px;">💸</div>
                    <p style="color:#9ca3af;">Belum ada data spending di periode ini</p>
                    <a href="{{ route('spending.create') }}" class="clay-btn clay-btn-primary"
                       style="margin-top:12px;" data-page-link>＋ Input Spending Pertama</a>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
var openRows = new Set();
function toggle(id) {
    var el   = document.getElementById(id);
    var chev = document.getElementById('chev-' + id);
    if (!el) return;
    var open = openRows.has(id);
    if (open) {
        el.style.display     = 'none';
        if (chev) chev.style.transform = 'rotate(0deg)';
        openRows.delete(id);
    } else {
        el.style.display     = id.startsWith('lvl1') ? 'table-row' : 'block';
        if (chev) chev.style.transform = 'rotate(90deg)';
        openRows.add(id);
    }
}
</script>
@endpush
@endsection
