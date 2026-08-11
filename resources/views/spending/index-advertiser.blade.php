@extends('layouts.app')
@section('title','Spending Harian')
@section('page-title','💸 Spending Harian Saya')
@section('page-subtitle', 'Data iklan harian — ' . $user->display_name)

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
    {{-- .filter-bar: di layar kecil rentang periodik + Reset + Input Spending tetap 1 jajar (CSS di bawah) --}}
    <form method="GET" action="{{ route('spending.index') }}" id="filter-form-adv" class="filter-bar"
          style="display:flex;gap:10px;align-items:flex-end;">

        <x-date-range-picker
            :dari="$dari"
            :sampai="$sampai"
            form-id="filter-form-adv"
            input-dari="dari"
            input-sampai="sampai"
        />

        <a href="{{ route('spending.index') }}" class="clay-btn clay-btn-outline">Reset</a>
        <a href="{{ route('spending.create') }}" class="clay-btn clay-btn-primary"
           style="margin-left:auto;" data-page-link @if(!$hasWhitelist) data-require-whitelist @endif>＋ Input Spending</a>
    </form>
</div>

{{-- ═══════════════ RINGKASAN PERIODE (4 kartu) ═══════════════ --}}
@php
    $pr = (float) ($summary['paid_ratio'] ?? 0);
    $prFill = $pr >= 75 ? 'linear-gradient(90deg,#22c55e,#16a34a)'
            : ($pr >= 50 ? 'linear-gradient(90deg,#fbbf24,#f59e0b)'
            : 'linear-gradient(90deg,#ef4444,#dc2626)');
    $periodeLabel = $dari === $sampai
        ? \Carbon\Carbon::parse($dari)->translatedFormat('d M Y')
        : \Carbon\Carbon::parse($dari)->translatedFormat('d M Y').' – '.\Carbon\Carbon::parse($sampai)->translatedFormat('d M Y');
@endphp
<div class="summary-grid" data-reveal>

    {{-- 1. Total Spending --}}
    <div class="summary-card" title="Total pengeluaran iklan · {{ $periodeLabel }}">
        <div class="summary-icon sc-primary">💰</div>
        <div class="summary-body">
            <div class="summary-label">Total Spending</div>
            <div class="summary-value">Rp {{ number_format($summary['spending'],0,',','.') }}</div>
            <div class="summary-sub">{{ count($summaries) }} hari berisi data · {{ $periodeLabel }}</div>
        </div>
    </div>

    {{-- 2. Total Lead / Paid --}}
    <div class="summary-card" title="Total lead dan pembayaran · {{ $periodeLabel }}">
        <div class="summary-icon sc-purple">👥</div>
        <div class="summary-body">
            <div class="summary-label">Total Lead / Paid</div>
            <div class="summary-value">
                <span class="sc-lead">{{ number_format($summary['lead']) }}</span>
                <span class="sc-sep">/</span>
                <span class="sc-paid">{{ number_format($summary['paid']) }}</span>
            </div>
            <div class="summary-sub">Konversi {{ $summary['lead'] > 0 ? round($summary['paid'] / $summary['lead'] * 100, 1) : 0 }}% dari lead</div>
        </div>
    </div>

    {{-- 3. CPA Lead / CPA Paid --}}
    <div class="summary-card" title="Biaya rata-rata per lead & per paid · {{ $periodeLabel }}">
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
    <div class="summary-card" title="Persentase lead yang berhasil membayar · {{ $periodeLabel }}">
        <div class="summary-icon sc-amber">🎯</div>
        <div class="summary-body">
            <div class="summary-label">Paid Ratio</div>
            <div class="summary-value">{{ number_format($pr) }}%</div>
            <div class="summary-ratio-track">
                <div class="summary-ratio-fill" style="width:{{ min(100, $pr) }}%;background:{{ $prFill }};"></div>
            </div>
        </div>
    </div>
</div>

{{-- ──────────────────────────────────────────────────────────────
     Struktur 3 level:
     Level 1 → Baris tanggal   (klik → expand)
     Level 2 → Sub-grup produk (di dalam tanggal)
     Level 3 → Baris whitelist (di dalam produk)
─────────────────────────────────────────────────────────────── --}}
<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll table-scroll-limit" id="spending-scroll">
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
                    {{-- Pensil khusus ubah tanggal — diletakkan tepat di samping tanggal --}}
                    <a href="javascript:void(0)" onclick="event.stopPropagation(); openDateChange('{{ $dateKey }}')"
                       title="Ubah tanggal data ini"
                       style="display:inline-flex;align-items:center;justify-content:center;
                              width:22px;height:22px;margin-left:6px;vertical-align:middle;
                              border-radius:7px;background:#f3f4f6;border:1px solid transparent;
                              font-size:.72rem;text-decoration:none;cursor:pointer;
                              transition:background .15s,border-color .15s;"
                       onmouseenter="this.style.background='#fef2f2';this.style.borderColor='rgba(255,107,107,.4)'"
                       onmouseleave="this.style.background='#f3f4f6';this.style.borderColor='transparent'">✏️</a>
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
                    <span class="clay-badge {{ $s['paid_ratio']>=75?'clay-badge-green':($s['paid_ratio']>=50?'clay-badge-yellow':'clay-badge-red') }}">
                        {{ round($s['paid_ratio']) }}%
                    </span>
                </td>
                <td style="text-align:right;font-size:.82rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($s['cpa_lead'],0,',','.') }}</td>
                <td style="text-align:right;font-size:.82rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($s['cpa_paid'],0,',','.') }}</td>
                <td style="text-align:right;" onclick="event.stopPropagation()">
                    <a href="{{ route('spending.create') }}?tanggal={{ $dateKey }}"
                       class="clay-btn clay-btn-primary" style="padding:4px 10px;font-size:.7rem;" data-page-link @if(!$hasWhitelist) data-require-whitelist @endif>＋</a>
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
                        <div class="lvl2-header" onclick="toggle('{{ $lvl2Id }}')"
                             style="display:flex;align-items:center;gap:12px;padding:10px 20px;
                                    cursor:pointer;transition:background .15s;"
                             onmouseenter="this.style.background='#f3f4f6'"
                             onmouseleave="this.style.background=''">

                            {{-- Select-all whitelist produk ini (bulk delete) --}}
                            <label style="display:flex;align-items:center;cursor:pointer;flex-shrink:0;"
                                   onclick="event.stopPropagation()"
                                   title="Pilih semua whitelist produk ini untuk dihapus">
                                <input type="checkbox" class="bd-check-all" data-prod="{{ $dateKey }}-{{ $prodId }}">
                            </label>

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
                                        {{ $prodData['product']->name ?? 'Tidak Diketahui' }}
                                    </span>
                                    <span style="font-size:.68rem;color:#9ca3af;">
                                        {{ $prodData['product']->code ?? '' }}
                                    </span>
                                </div>
                                <div class="lvl2-sub" style="font-size:.68rem;color:#9ca3af;margin-top:2px;margin-left:56px;">
                                    {{ count($prodData['whitelists']) }} whitelist mengiklankan produk ini
                                </div>
                            </div>

                            {{-- Summary produk --}}
                            <div class="lvl2-summary" style="display:flex;gap:16px;flex-shrink:0;align-items:center;">
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
                                <span class="clay-badge {{ $prodData['paid_ratio']>=75?'clay-badge-green':($prodData['paid_ratio']>=50?'clay-badge-yellow':'clay-badge-red') }}"
                                      style="font-size:.68rem;">
                                    {{ $prodData['paid_ratio'] }}%
                                </span>
                            </div>
                        </div>

                        {{-- ── LEVEL 3: Baris Whitelist ────────── --}}
                        <div id="{{ $lvl2Id }}" style="display:none;background:#fff;
                             border-top:1px dashed rgba(78,205,196,.2);padding:0 0 6px 0;">
                            <table class="lvl3" style="width:100%;">
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
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            {{-- Checkbox bulk delete --}}
                                            <input type="checkbox" class="bd-check"
                                                   data-id="{{ $item->id }}"
                                                   data-prod="{{ $dateKey }}-{{ $prodId }}"
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
                                                <div style="font-weight:600;font-size:.8rem;">
                                                    {{ $item->whitelist->nama ?? '-' }}
                                                </div>
                                                <div style="font-size:.66rem;color:#9ca3af;">
                                                    {{ $item->whitelist->kode ?? '' }}
                                                </div>
                                            </div>
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
                                        <span class="clay-badge {{ $item->paid_ratio>=75?'clay-badge-green':($item->paid_ratio>=50?'clay-badge-yellow':'clay-badge-red') }}"
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
                                            <a href="javascript:void(0)" onclick="event.stopPropagation(); openSpendingEdit(this)"
                                               class="clay-btn clay-btn-secondary"
                                               style="padding:3px 8px;font-size:.65rem;"
                                               title="Edit spending, lead & paid"
                                               data-url="{{ route('spending.update', $item) }}"
                                               data-id="{{ $item->id }}"
                                               data-tanggal="{{ $dateKey }}"
                                               data-wl-id="{{ $item->whitelist_id }}"
                                               data-wl-name="{{ $item->whitelist->nama ?? '' }}"
                                               data-wl-code="{{ $item->whitelist->kode ?? '' }}"
                                               data-product-id="{{ $item->product_id }}"
                                               data-product-name="{{ $item->product->name ?? '' }}"
                                               data-spending="{{ $item->spending }}"
                                               data-lead="{{ $item->lead }}"
                                               data-paid="{{ $item->paid }}">✏️</a>
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
                                        <span class="clay-badge {{ $prodData['paid_ratio']>=75?'clay-badge-green':($prodData['paid_ratio']>=50?'clay-badge-yellow':'clay-badge-red') }}"
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
                       style="margin-top:12px;" data-page-link @if(!$hasWhitelist) data-require-whitelist @endif>＋ Input Spending Pertama</a>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ═══════════════ MODAL UBAH TANGGAL ═══════════════ --}}
<div class="dc-modal" id="modal-date-change" role="dialog" aria-modal="true" aria-labelledby="dc-title">
    <div class="dc-backdrop" onclick="dcClose()"></div>
    <div class="dc-container">
        <div class="dc-header">
            <h2 id="dc-title">✏️ Ubah Tanggal</h2>
            <button class="dc-close" onclick="dcClose()" type="button">✕</button>
        </div>
        <div class="dc-body">
            <div class="dc-info" id="dc-info"></div>
            <div id="dc-calendar"></div>
            <div class="dc-hint">
                📅 Tanggal yang sudah memiliki data <strong>(whitelist + produk sama)</strong> otomatis non-aktif agar tidak dobel.
            </div>
        </div>
        <div class="dc-footer">
            <button class="clay-btn clay-btn-outline" onclick="dcClose()" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="dc-save" type="button" disabled>💾 Ubah Tanggal</button>
        </div>
    </div>
</div>

{{-- ═══════════════ MODAL EDIT SPENDING ═══════════════ --}}
<div class="dc-modal" id="modal-edit-spending" role="dialog" aria-modal="true" aria-labelledby="se-title">
    <div class="dc-backdrop" onclick="closeSpendingEdit()"></div>
    <div class="dc-container">
        <div class="dc-header">
            <h2 id="se-title">✏️ Edit Spending</h2>
            <button class="dc-close" onclick="closeSpendingEdit()" type="button">✕</button>
        </div>
        <div class="dc-body">
            {{-- Info konteks: tanggal, produk, whitelist --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;">
                <div style="background:#f9fafb;border-radius:10px;padding:8px 12px;">
                    <div style="font-size:.6rem;font-weight:700;text-transform:uppercase;color:#9ca3af;">Tanggal</div>
                    <div id="se-tanggal" style="font-size:.85rem;font-weight:700;color:#1e1b2e;"></div>
                </div>
                <div style="background:#f9fafb;border-radius:10px;padding:8px 12px;">
                    <div style="font-size:.6rem;font-weight:700;text-transform:uppercase;color:#9ca3af;">Produk</div>
                    <div id="se-product" style="font-size:.85rem;font-weight:700;color:#1e1b2e;"></div>
                </div>
                <div style="grid-column:span 2;background:#f9fafb;border-radius:10px;padding:8px 12px;">
                    <div style="font-size:.6rem;font-weight:700;text-transform:uppercase;color:#9ca3af;">Whitelist</div>
                    <div id="se-whitelist" style="font-size:.85rem;font-weight:700;color:#1e1b2e;"></div>
                </div>
            </div>

            {{-- Input: spending, lead, paid --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:12px;">
                <div>
                    <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;margin-bottom:4px;">💰 Spending (Rp)</label>
                    <input type="number" id="se-spending" class="clay-input" min="0" step="100"
                           style="font-size:.85rem;padding:7px 10px;">
                </div>
                <div>
                    <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;margin-bottom:4px;">👤 Lead</label>
                    <input type="number" id="se-lead" class="clay-input" min="0"
                           style="font-size:.85rem;padding:7px 10px;">
                </div>
                <div>
                    <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;margin-bottom:4px;">💳 Paid</label>
                    <input type="number" id="se-paid" class="clay-input" min="0"
                           style="font-size:.85rem;padding:7px 10px;">
                </div>
            </div>

            {{-- Live preview --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;background:#F0FFFE;border-radius:10px;padding:10px 14px;text-align:center;">
                <div>
                    <div id="se-preview-ratio" style="font-weight:800;font-size:.95rem;color:var(--color-secondary);">—</div>
                    <div style="font-size:.6rem;font-weight:600;text-transform:uppercase;color:#9ca3af;">Paid Ratio</div>
                </div>
                <div>
                    <div id="se-preview-cpa-lead" style="font-weight:800;font-size:.95rem;color:var(--color-purple);">—</div>
                    <div style="font-size:.6rem;font-weight:600;text-transform:uppercase;color:#9ca3af;">CPA Lead</div>
                </div>
                <div>
                    <div id="se-preview-cpa-paid" style="font-weight:800;font-size:.95rem;color:var(--color-orange);">—</div>
                    <div style="font-size:.6rem;font-weight:600;text-transform:uppercase;color:#9ca3af;">CPA Paid</div>
                </div>
            </div>

            <input type="hidden" id="se-tanggal-val">
            <input type="hidden" id="se-wl-id">
            <input type="hidden" id="se-product-id">
        </div>
        <div class="dc-footer">
            <button class="clay-btn clay-btn-outline" onclick="closeSpendingEdit()" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="se-save" type="button">💾 Simpan</button>
        </div>
    </div>
</div>

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
    /* ── Modal Ubah Tanggal ──────────────────────────── */
    .dc-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .dc-modal.active { display: flex; }
    .dc-modal .dc-backdrop {
        position: absolute; inset: 0;
        background: rgba(15,23,42,.55); backdrop-filter: blur(2px);
    }
    .dc-modal .dc-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 400px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: dcIn .22s ease;
    }
    @keyframes dcIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .dc-modal .dc-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .dc-modal .dc-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .dc-modal .dc-close {
        background: #f3f4f6; border: none; border-radius: 8px;
        width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280;
        transition: background .15s;
    }
    .dc-modal .dc-close:hover { background: #e5e7eb; }
    .dc-modal .dc-body { padding: 18px 20px 16px; }
    .dc-info { font-size: .82rem; color: #4b5563; font-weight: 600; margin-bottom: 12px; line-height: 1.5; }
    .dc-hint { font-size: .68rem; color: #9ca3af; margin-top: 10px; line-height: 1.5; }
    .dc-modal .dc-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
    }
    .dc-nav {
        width: 30px; height: 30px; border: 1px solid #e5e7eb; border-radius: 8px;
        background: #fff; cursor: pointer; font-size: 1rem; color: #6b7280;
        transition: all .15s; line-height: 1;
    }
    .dc-nav:hover { border-color: var(--color-primary, #FF6B6B); color: var(--color-primary, #FF6B6B); }
    .dc-title { font-size: .82rem; font-weight: 700; color: #1e1b2e; }
    .dc-day {
        height: 34px; display: flex; align-items: center; justify-content: center;
        border-radius: 9px; font-size: .8rem; cursor: pointer;
        transition: background .12s, color .12s;
    }
    .dc-day:hover:not(.dc-disabled) { background: rgba(255,107,107,.12); color: var(--color-primary, #FF6B6B); }
    .dc-day.dc-today { font-weight: 800; color: var(--color-primary, #FF6B6B); }
    .dc-day.dc-source { background: #fef2f2; color: #dc2626; font-weight: 700; }
    .dc-day.dc-selected {
        background: var(--color-primary, #FF6B6B) !important; color: #fff !important;
        font-weight: 800; box-shadow: 0 3px 0 #e05555;
    }
    .dc-day.dc-disabled {
        color: #d1d5db !important; cursor: not-allowed; pointer-events: none;
        text-decoration: line-through;
    }

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

    /* ── Ringkasan Periode (4 kartu di atas tabel) ──────────── */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0,1fr));
        gap: 14px;
        margin-bottom: 16px;
    }
    @media (max-width: 1100px) { .summary-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 560px)  { .summary-grid { grid-template-columns: 1fr; } }
    .summary-card {
        display: flex; align-items: center; gap: 14px;
        background: #fff; border-radius: 16px; padding: 16px 18px;
        border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        position: relative; overflow: hidden;
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
    .summary-body { min-width: 0; }
    .summary-label {
        font-size: .62rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .06em; color: #9ca3af;
    }
    .summary-value {
        font-size: 1.18rem; font-weight: 800; color: #1e1b2e;
        margin-top: 2px; white-space: nowrap; line-height: 1.2;
    }
    .summary-value .sc-lead { color: var(--color-purple, #8b5cf6); }
    .summary-value .sc-paid { color: var(--color-secondary, #4ECDC4); }
    .summary-value .sc-sep  { color: #d1d5db; font-weight: 600; margin: 0 2px; }
    .summary-sub { font-size: .66rem; color: #9ca3af; margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .summary-ratio-track {
        height: 6px; border-radius: 999px; background: #f3f4f6;
        margin-top: 8px; overflow: hidden; max-width: 170px;
    }
    .summary-ratio-fill {
        height: 100%; border-radius: 999px;
        transition: width .5s ease;
    }

    /* ── Batas tinggi tabel (±5 baris, sisanya scroll vertikal) ── */
    .table-scroll-limit { overflow-y: auto; overscroll-behavior: contain; }
    .table-scroll-limit::-webkit-scrollbar { width: 8px; }
    .table-scroll-limit::-webkit-scrollbar-track { background: transparent; }
    .table-scroll-limit::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 999px; }
    .table-scroll-limit::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
    .table-scroll-limit { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
    /* Header tetap terlihat saat scroll vertikal di dalam tabel */
    .table-scroll-limit thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fafafa; /* solid agar baris yang lewat tidak tembus */
        box-shadow: 0 2px 6px -3px rgba(0,0,0,.14);
    }

    /* ── Responsive: fleksibel di segala ukuran layar ────────────────── */
    .filter-bar { flex-wrap: wrap; } /* default: boleh wrap di layar lebar */

    @media (max-width: 640px) {
        /* Bar filter: rentang periodik + Reset + Input Spending TETAP 1 jajar */
        .filter-bar { flex-wrap: nowrap; gap: 6px; }
        .filter-bar .drp-trigger { flex: 1 1 auto; min-width: 0; }
        .filter-bar .clay-btn { padding: 8px 10px; font-size: .72rem; white-space: nowrap; }

        /* Kartu summary lebih padat */
        .summary-card { padding: 12px 14px; gap: 10px; }
        .summary-icon { width: 38px; height: 38px; font-size: 1rem; }
        .summary-value { font-size: 1rem; }
        .summary-label { font-size: .56rem; }

        /* Tabel utama: sel lebih ramping (menang atas rule mobile layout) */
        .table-scroll-limit table.clay-table thead th,
        .table-scroll-limit table.clay-table tbody td {
            padding: 7px 6px !important;
            font-size: .72rem !important;
        }

        /* Header produk (level 2): boleh wrap agar tak meluber */
        .lvl2-header { flex-wrap: wrap; gap: 8px !important; padding: 8px 12px !important; }
        .lvl2-sub { margin-left: 0 !important; }
        .lvl2-summary { flex-wrap: wrap; gap: 10px !important; }

        /* Tabel whitelist (level 3): sel lebih ramping */
        .lvl3 th, .lvl3 td { padding: 6px 8px !important; }
        .lvl3 tbody tr td:first-child { padding: 6px 10px 6px 12px !important; }

        /* FAB bulk delete: melayang penuh di bawah layar */
        .bulk-bar {
            left: 12px; right: 12px; bottom: 12px;
            justify-content: center; flex-wrap: wrap; gap: 8px;
        }
    }

    @media (max-width: 480px) {
        /* Modal jadi bottom-sheet agar jempol mudah menjangkau tombol */
        .dc-modal { padding: 10px; align-items: flex-end; }
        .be-modal { padding: 10px; align-items: flex-end; }
        .dc-modal .dc-container { border-radius: 16px 16px 0 0; }
        .be-modal .be-container { border-radius: 16px 16px 0 0; }

        /* Nilai kartu summary cukup ramping untuk HP kecil */
        .summary-value { font-size: .95rem; }

        /* Input modal edit: kompak */
        .be-row-inputs { gap: 6px; }
        .be-row-inputs .clay-input { padding: 6px 6px; font-size: .74rem; }
    }
</style>
@endpush

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

@push('scripts')
<script>
(function() {
    'use strict';

    // Tanggal yang wajib non-aktif per baris tanggal (dari server, whitelist+produk sama)
    var DATE_CHANGE_RESTRICTIONS = @json($dateChangeRestrictions);

    var MONTHS = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var DAYS = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];

    var modal = document.getElementById('modal-date-change');
    var calendarEl = document.getElementById('dc-calendar');
    var infoEl = document.getElementById('dc-info');
    var saveBtn = document.getElementById('dc-save');

    var st = { sumber: null, target: null, y: null, m: null };

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }
    function parse(s) { var p = s.split('-').map(Number); return new Date(p[0], p[1]-1, p[2]); }
    function fmtLabel(s) { var d = parse(s); return d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0,3) + ' ' + d.getFullYear(); }

    function dcRender() {
        if (!calendarEl) return;
        var first = new Date(st.y, st.m, 1);
        var dow = first.getDay(); dow = dow === 0 ? 6 : dow - 1;
        var dim = new Date(st.y, st.m + 1, 0).getDate();
        var today = new Date(); today.setHours(0,0,0,0);
        var disabled = (DATE_CHANGE_RESTRICTIONS[st.sumber] || []).slice();

        var html = '';
        html += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">';
        html += '<button type="button" class="dc-nav" onclick="dcPrevMonth()">‹</button>';
        html += '<div class="dc-title">' + MONTHS[st.m] + ' ' + st.y + '</div>';
        html += '<button type="button" class="dc-nav" onclick="dcNextMonth()">›</button>';
        html += '</div>';
        html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;">';
        DAYS.forEach(function(day, i) {
            var c = i === 6 ? '#ef4444' : (i === 5 ? '#3b82f6' : '#9ca3af');
            html += '<div style="text-align:center;font-size:.62rem;font-weight:700;color:' + c + ';padding:3px 0;">' + day + '</div>';
        });
        for (var i = 0; i < dow; i++) html += '<div></div>';
        for (var d = 1; d <= dim; d++) {
            var dt = new Date(st.y, st.m, d); dt.setHours(0,0,0,0);
            var ds = fmt(dt);
            var isFuture = dt > today;
            var isDisabled = disabled.indexOf(ds) !== -1;
            var isSource = ds === st.sumber;
            var isSelected = ds === st.target;
            var isToday = dt.getTime() === today.getTime();

            var cls = 'dc-day';
            var clickable = !isFuture && !isDisabled && !isSource;
            if (isFuture || isDisabled) cls += ' dc-disabled';
            if (isSource) cls += ' dc-source';
            if (isSelected) cls += ' dc-selected';
            else if (isToday && clickable) cls += ' dc-today';

            html += '<div class="' + cls + '"' + (isDisabled ? ' title="Sudah ada data (whitelist + produk sama)"' : (isSource ? ' title="Lokasi data saat ini"' : '')) +
                    (clickable ? ' onclick="dcPick(\'' + ds + '\')"' : '') + '>' + d + '</div>';
        }
        html += '</div>';
        calendarEl.innerHTML = html;
    }

    window.openDateChange = function(dateKey) {
        var d = parse(dateKey);
        st.sumber = dateKey;
        st.target = null;
        st.y = d.getFullYear();
        st.m = d.getMonth();
        infoEl.textContent = '📅 Memindahkan data tanggal ' + fmtLabel(dateKey) + ' — pilih tanggal baru:';
        saveBtn.disabled = true;
        dcRender();
        modal.classList.add('active');
    };

    window.dcClose = function() {
        modal.classList.remove('active');
        st.sumber = null; st.target = null;
    };

    window.dcPrevMonth = function() {
        st.m--;
        if (st.m < 0) { st.m = 11; st.y--; }
        dcRender();
    };

    window.dcNextMonth = function() {
        st.m++;
        if (st.m > 11) { st.m = 0; st.y++; }
        dcRender();
    };

    window.dcPick = function(ds) {
        st.target = (ds === st.target) ? null : ds;
        dcRender();
        saveBtn.disabled = !st.target;
    };

    // ── Simpan ────────────────────────────────────────
    saveBtn.addEventListener('click', function() {
        if (!st.sumber || !st.target) return;
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = 'Menyimpan...';

        fetch('{{ route('spending.change-date') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ sumber: st.sumber, target: st.target }),
        })
        .then(function(res) {
            if (!res.ok) return res.json().then(function(e) { throw new Error(e.message || 'Gagal'); });
            return res.json();
        })
        .then(function(json) {
            if (json.success) {
                window.location.reload();
            } else {
                alert('Gagal: ' + json.message);
                btn.disabled = false;
                btn.innerHTML = '💾 Ubah Tanggal';
            }
        })
        .catch(function(err) {
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '💾 Ubah Tanggal';
        });
    });

    // Tutup dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) window.dcClose();
    });
})();
</script>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';

    var modal = document.getElementById('modal-edit-spending');
    var saveBtn = document.getElementById('se-save');

    var elTanggal = document.getElementById('se-tanggal');
    var elWl = document.getElementById('se-whitelist');
    var elProd = document.getElementById('se-product');
    var inSpending = document.getElementById('se-spending');
    var inLead = document.getElementById('se-lead');
    var inPaid = document.getElementById('se-paid');

    var hidTanggal = document.getElementById('se-tanggal-val');
    var hidWl = document.getElementById('se-wl-id');
    var hidProd = document.getElementById('se-product-id');

    var pvRatio = document.getElementById('se-preview-ratio');
    var pvCpaLead = document.getElementById('se-preview-cpa-lead');
    var pvCpaPaid = document.getElementById('se-preview-cpa-paid');

    var editUrl = null;

    function fmtNum(n) { return Number(n).toLocaleString('id-ID'); }

    function fmtTanggal(ds) {
        var d = new Date(ds + 'T00:00:00');
        return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }

    function calcPreview() {
        var sp = parseFloat(inSpending.value) || 0;
        var lead = parseInt(inLead.value) || 0;
        var paid = parseInt(inPaid.value) || 0;
        pvRatio.textContent = lead > 0 ? (paid / lead * 100).toFixed(2) + '%' : '—';
        pvCpaLead.textContent = lead > 0 ? 'Rp ' + fmtNum(Math.round(sp / lead)) : '—';
        pvCpaPaid.textContent = paid > 0 ? 'Rp ' + fmtNum(Math.round(sp / paid)) : '—';
    }

    [inSpending, inLead, inPaid].forEach(function(inp) {
        inp.addEventListener('input', calcPreview);
    });

    window.openSpendingEdit = function(btn) {
        editUrl = btn.dataset.url;
        hidTanggal.value = btn.dataset.tanggal;
        hidWl.value = btn.dataset.wlId;
        hidProd.value = btn.dataset.productId;

        elTanggal.textContent = fmtTanggal(btn.dataset.tanggal);
        elWl.textContent = (btn.dataset.wlName || '-') + (btn.dataset.wlCode ? ' (' + btn.dataset.wlCode + ')' : '');
        elProd.textContent = btn.dataset.productName || '-';

        inSpending.value = btn.dataset.spending;
        inLead.value = btn.dataset.lead;
        inPaid.value = btn.dataset.paid;

        calcPreview();
        modal.classList.add('active');
    };

    window.closeSpendingEdit = function() {
        modal.classList.remove('active');
        editUrl = null;
    };

    saveBtn.addEventListener('click', function() {
        if (!editUrl) return;
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = 'Menyimpan...';

        fetch(editUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                tanggal: hidTanggal.value,
                whitelist_id: hidWl.value,
                product_id: hidProd.value,
                spending: inSpending.value || '0',
                lead: inLead.value || '0',
                paid: inPaid.value || '0',
            }),
        })
        .then(function(res) {
            if (!res.ok) return res.json().then(function(e) { throw new Error(e.message || 'Gagal'); });
            return res.json();
        })
        .then(function(json) {
            if (json.success) {
                window.location.reload();
            } else {
                alert('Gagal: ' + json.message);
                btn.disabled = false;
                btn.innerHTML = '💾 Simpan';
            }
        })
        .catch(function(err) {
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '💾 Simpan';
        });
    });

    // Tutup dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) window.closeSpendingEdit();
    });
})();
</script>
@endpush

@if(!$hasWhitelist)
{{-- ─── Modal: Belum punya whitelist ─────────────────────────────── --}}
@push('styles')
<style>
    .modal-wl {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .modal-wl.active { display: flex; }
    .modal-wl .modal-wl-backdrop {
        position: absolute; inset: 0;
        background: rgba(15,23,42,.55); backdrop-filter: blur(2px);
    }
    .modal-wl .modal-wl-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 440px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: modalWlIn .22s ease;
    }
    @keyframes modalWlIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .modal-wl .modal-wl-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .modal-wl .modal-wl-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .modal-wl .modal-wl-close {
        background: #f3f4f6; border: none; border-radius: 8px;
        width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280;
        transition: background .15s;
    }
    .modal-wl .modal-wl-close:hover { background: #e5e7eb; }
    .modal-wl .modal-wl-body { padding: 26px 24px 8px; text-align: center; }
    .modal-wl .modal-wl-icon { font-size: 2.4rem; margin-bottom: 12px; line-height: 1; }
    .modal-wl .modal-wl-body p {
        font-size: .9rem; color: #4b5563; line-height: 1.65; margin: 0 0 6px;
    }
    .modal-wl .modal-wl-link {
        color: #2563eb; font-weight: 700; text-decoration: underline;
        cursor: pointer; transition: color .15s;
    }
    .modal-wl .modal-wl-link:hover { color: #1d4ed8; }
    .modal-wl .modal-wl-footer { padding: 16px 24px 22px; text-align: center; }
</style>
@endpush

@push('body-end')
<div class="modal-wl" id="modal-perlu-whitelist" role="dialog" aria-modal="true" aria-labelledby="modal-wl-title">
    <div class="modal-wl-backdrop" onclick="tutupModalPerluWhitelist()"></div>
    <div class="modal-wl-container">
        <div class="modal-wl-header">
            <h2 id="modal-wl-title">Perhatian</h2>
            <button class="modal-wl-close" onclick="tutupModalPerluWhitelist()" type="button">✕</button>
        </div>
        <div class="modal-wl-body">
            <div class="modal-wl-icon">⚠️</div>
            <p>
                Ups, tampaknya kamu belum memiliki satupun akun WL.
                <a href="{{ route('whitelist.index') }}" class="modal-wl-link" data-page-link>Buat akun Whitelist sekarang?</a>
            </p>
        </div>
        <div class="modal-wl-footer">
            <button type="button" class="clay-btn clay-btn-outline" onclick="tutupModalPerluWhitelist()">Nanti saja</button>
        </div>
    </div>
</div>

<script>
(function() {
    // Lazy lookup: aman meski urutan render stack berubah
    function getModal() {
        return document.getElementById('modal-perlu-whitelist');
    }

    // Intersep semua tombol "+ Input Spending" saat user belum punya whitelist
    document.querySelectorAll('[data-require-whitelist]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // cegah PageLinkHandler global me-fade-out halaman
            var modal = getModal();
            if (modal) modal.classList.add('active');
        });
    });

    window.tutupModalPerluWhitelist = function() {
        var modal = getModal();
        if (modal) modal.classList.remove('active');
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') window.tutupModalPerluWhitelist();
    });
})();
</script>
@endpush
@endif

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

        // Update checkbox select-all per produk (tristate)
        document.querySelectorAll('.bd-check-all').forEach(function(cb) {
            var group = document.querySelectorAll('.bd-check[data-prod="' + cb.dataset.prod + '"]');
            if (!group.length) { cb.checked = false; cb.indeterminate = false; return; }
            var allChecked = Array.from(group).every(function(c) { return selected.has(c.dataset.id); });
            var anyChecked = Array.from(group).some(function(c) { return selected.has(c.dataset.id); });
            cb.checked = allChecked;
            cb.indeterminate = anyChecked && !allChecked;
        });

        // Highlight baris terpilih
        document.querySelectorAll('.bd-check').forEach(function(c) {
            var row = c.closest('tr');
            if (row) row.classList.toggle('bd-row-selected', selected.has(c.dataset.id));
        });
    }

    // Checkbox per baris whitelist
    document.querySelectorAll('.bd-check').forEach(function(cb) {
        cb.addEventListener('change', function() {
            if (cb.checked) selected.add(cb.dataset.id);
            else selected.delete(cb.dataset.id);
            updateUI();
        });
    });

    // Checkbox select-all per produk
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

    // Batal (bersihkan semua pilihan)
    var clearBtn = document.getElementById('bulk-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            selected.clear();
            document.querySelectorAll('.bd-check').forEach(function(c) { c.checked = false; });
            updateUI();
        });
    }

    // Konfirmasi & submit
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
{{-- ── Batas tinggi tabel: tampilkan ±5 baris, sisanya scroll vertikal ── --}}
(function() {
    'use strict';

    var MAX_ROWS = 5;
    var scrollEl = document.getElementById('spending-scroll');
    if (!scrollEl) return;

    var table = scrollEl.querySelector('table.clay-table');
    if (!table || !table.tBodies.length) return;

    var tbody = table.tBodies[0];
    // Baris yang terlihat = baris tanggal (level-1); baris expand awal punya display:none inline
    var visible = Array.prototype.filter.call(tbody.rows, function(r) {
        return r.style.display !== 'none';
    });

    // ≤ 5 baris → biarkan tinggi alami, tanpa scroll
    if (visible.length <= MAX_ROWS) return;

    function measure() {
        var head = table.tHead;
        var h = head ? head.offsetHeight : 0;
        for (var i = 0; i < MAX_ROWS; i++) h += visible[i].offsetHeight;
        return h;
    }

    scrollEl.style.maxHeight = measure() + 'px';

    // Pass 2: setelah scrollbar vertikal muncul, lebar konten menyusut & baris bisa
    // ikut berubah tinggi (reflow) → ukur sekali lagi agar tetap pas 5 baris.
    requestAnimationFrame(function() {
        scrollEl.style.maxHeight = measure() + 'px';
    });

    // Re-hitung saat layar berubah ukuran (teks bisa wrap ulang → baris lebih tinggi)
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            scrollEl.style.maxHeight = measure() + 'px';
        }, 150);
    });
})();
</script>
@endpush
@endsection
