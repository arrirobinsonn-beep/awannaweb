@extends('layouts.app')
@section('title','Laporan Operasional')
@section('page-title','Laporan Operasional')
@section('page-subtitle','Resi, COD & bank transfer per pengirim')

@section('content')

@php
    $periodeLabel = $isToday ? 'Hari Ini' : 'Periode Terpilih';
    $periodeSub = $isToday
        ? \Carbon\Carbon::parse($dari)->translatedFormat('l, d M Y')
        : \Carbon\Carbon::parse($dari)->translatedFormat('d M Y').' — '.\Carbon\Carbon::parse($sampai)->translatedFormat('d M Y');
@endphp

{{-- ── Summary Cards ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;" data-reveal>
    {{-- Card Stok --}}
    <div class="clay-card" style="padding:18px 22px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <span style="font-size:1.1rem;">📦</span>
            <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;opacity:.6;letter-spacing:.03em;">Stok {{ $periodeLabel }}</span>
        </div>
        <div style="display:flex;align-items:baseline;gap:16px;">
            <div>
                <span style="font-size:1.5rem;font-weight:900;color:#10b981;">+{{ number_format($stokPeriode->masuk ?? 0,0,',','.') }}</span>
                <span style="font-size:.7rem;color:#9ca3af;margin-left:2px;">masuk</span>
            </div>
            <div>
                <span style="font-size:1.5rem;font-weight:900;color:#ef4444;">-{{ number_format($stokPeriode->keluar ?? 0,0,',','.') }}</span>
                <span style="font-size:.7rem;color:#9ca3af;margin-left:2px;">keluar</span>
            </div>
        </div>
        <div style="font-size:.7rem;color:#9ca3af;margin-top:8px;">{{ $periodeSub }}</div>
    </div>

    {{-- Card Order --}}
    <div class="clay-card" style="padding:18px 22px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <span style="font-size:1.1rem;">🧾</span>
            <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;opacity:.6;letter-spacing:.03em;">Order {{ $periodeLabel }}</span>
        </div>
        <div style="font-size:1.8rem;font-weight:900;color:#1e1b2e;">{{ number_format($orderPeriode->total ?? 0,0,',','.') }}</div>
        <div style="display:flex;gap:12px;margin-top:6px;flex-wrap:wrap;">
            <span style="font-size:.72rem;color:#6b7280;">📝 {{ number_format($orderPeriode->resi ?? 0,0,',','.') }} ber-resi</span>
            <span style="font-size:.72rem;color:#3b82f6;font-weight:600;">💵 {{ number_format($orderPeriode->cod ?? 0,0,',','.') }} COD</span>
            <span style="font-size:.72rem;color:#10b981;font-weight:600;">🏦 {{ number_format($orderPeriode->bank_transfer ?? 0,0,',','.') }} TF</span>
        </div>
        <div style="font-size:.7rem;color:#9ca3af;margin-top:8px;">{{ $periodeSub }}</div>
    </div>
</div>

{{-- ── Filter rentang tanggal ────────────────────────────────── --}}
<div class="clay-card" style="padding:0;margin-bottom:20px;" data-reveal>
    <form method="GET" action="{{ route('operational-report.index') }}" id="drp-form"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:16px;">
        <x-date-range-picker :dari="$dari" :sampai="$sampai" form-id="drp-form" />
        <button class="clay-btn clay-btn-primary" type="submit">🔍 Terapkan</button>
        <a href="{{ route('operational-report.index') }}" class="clay-btn">Hari Ini</a>
        <span style="font-size:.75rem;color:#9ca3af;">{{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</span>
    </form>
</div>

{{-- ── Detail per pengirim ───────────────────────────────────── --}}
<div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
    <div style="padding:16px 20px;border-bottom:1px solid rgba(0,0,0,.06);">
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">Rincian per Pengirim</div>
        <div style="font-size:.72rem;color:#9ca3af;">Resi, COD & bank transfer menurut nama pengirim · klik nama untuk detail</div>
    </div>
    <div class="table-scroll">
        <table class="clay-table" style="min-width:640px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pengirim</th>
                    <th style="text-align:right;">Resi</th>
                    <th style="text-align:center;">COD</th>
                    <th style="text-align:center;">Bank Transfer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $r)
                    @php
                        $detailUrl = route('operational-report.batch', ['batch' => $r->batch_id, 'dari' => $dari, 'sampai' => $sampai]);
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-weight:700;"><a href="{{ $detailUrl }}" class="link-report" data-page-link>{{ $r->sender ?? '(tanpa pengirim)' }}</a></td>
                        <td style="text-align:right;font-weight:600;">{{ number_format($r->resi,0,',','.') }} <span style="color:#9ca3af;font-size:.7rem;">/ {{ number_format($r->total_order,0,',','.') }}</span></td>
                        <td style="text-align:center;"><span class="clay-badge {{ $r->cod > 0 ? 'clay-badge-blue' : '' }}">{{ number_format($r->cod,0,',','.') }}</span></td>
                        <td style="text-align:center;"><span class="clay-badge {{ $r->bank_transfer > 0 ? 'clay-badge-green' : '' }}">{{ number_format($r->bank_transfer,0,',','.') }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:48px;color:#9ca3af;">Tidak ada order pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot style="background:#FFF5F5;font-weight:800;">
                <tr>
                    <td colspan="2">TOTAL KESELURUHAN</td>
                    <td style="text-align:right;">{{ number_format($total->resi,0,',','.') }} <span style="color:#9ca3af;font-size:.7rem;">/ {{ number_format($total->total_order,0,',','.') }}</span></td>
                    <td style="text-align:center;">{{ number_format($total->cod,0,',','.') }}</td>
                    <td style="text-align:center;">{{ number_format($total->bank_transfer,0,',','.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    .link-report { color: var(--color-primary,#FF6B6B); text-decoration:none; font-weight:700; }
    .link-report:hover { text-decoration:underline; }
</style>

@endsection
