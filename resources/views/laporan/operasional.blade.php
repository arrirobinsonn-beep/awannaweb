@extends('layouts.app')
@section('title','Laporan Operasional')
@section('page-title','📋 Laporan Operasional')
@section('page-subtitle','Barang keluar/masuk, resi & metode pembayaran per pengirim')

@section('content')

{{-- ── Kartu ringkasan PERIODE TERPILIH (mengikuti filter dari/sampai) ── --}}
@php
    $periodeLabel = $isToday ? 'Hari Ini' : 'Periode Terpilih';
    $periodeSub = $isToday
        ? '📅 '.\Carbon\Carbon::parse($dari)->translatedFormat('l, d M Y')
        : '📅 '.\Carbon\Carbon::parse($dari)->translatedFormat('d M Y').' — '.\Carbon\Carbon::parse($sampai)->translatedFormat('d M Y');
@endphp
<div class="grid-stats" style="margin-bottom:20px;">
    <div class="stat-card stat-card-1" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Barang Keluar {{ $periodeLabel }}</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stokPeriode->keluar ?? 0 }}">{{ $stokPeriode->keluar ?? 0 }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">📦 Keluar (jurnal stok) · {{ $periodeSub }}</div>
    </div>
    <div class="stat-card stat-card-2" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Barang Masuk {{ $periodeLabel }}</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stokPeriode->masuk ?? 0 }}">{{ $stokPeriode->masuk ?? 0 }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">📦 Masuk (jurnal stok) · {{ $periodeSub }}</div>
    </div>
    <div class="stat-card stat-card-3" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Resi {{ $periodeLabel }}</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $orderPeriode->resi ?? 0 }}">{{ $orderPeriode->resi ?? 0 }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">🧾 Order ber-resi · {{ $periodeSub }}</div>
    </div>
    <div class="stat-card stat-card-4" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Metode Pembayaran</div>
        <div style="font-size:1.15rem;font-weight:900;">💵 {{ $orderPeriode->cod ?? 0 }} COD · {{ $orderPeriode->bank_transfer ?? 0 }} TF</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">🧾 Order {{ strtolower($periodeLabel) }} · {{ $periodeSub }}</div>
    </div>
</div>

{{-- ── Filter rentang tanggal ────────────────────────────────── --}}
<div class="clay-card" style="padding:0;margin-bottom:20px;" data-reveal>
    <form method="GET" action="{{ route('operational-report.index') }}" id="drp-form"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:16px;">
        <x-date-range-picker :dari="$dari" :sampai="$sampai" form-id="drp-form" />
        <button class="clay-btn clay-btn-primary" type="submit">🔍 Terapkan</button>
        <a href="{{ route('operational-report.index') }}" class="clay-btn">Hari Ini</a>
        <span style="font-size:.75rem;color:#9ca3af;">Periode: {{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</span>
    </form>
</div>

{{-- ── Detail per pengirim ───────────────────────────────────── --}}
<div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
    <div style="padding:16px 20px;border-bottom:1px solid rgba(0,0,0,.06);">
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">🧾 Rincian per Pengirim (Import Batch)</div>
        <div style="font-size:.72rem;color:#9ca3af;">Total pengeluaran, resi & metode pembayaran menurut nama pengirim</div>
    </div>
    <div class="table-scroll">
        <table class="clay-table" style="min-width:860px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pengirim</th>
                    <th style="text-align:right;">Total Pengeluaran</th>
                    <th style="text-align:right;">Resi</th>
                    <th style="text-align:center;">COD</th>
                    <th style="text-align:center;">Bank Transfer</th>
                    <th style="text-align:right;">Uang Masuk</th>
                    <th style="text-align:right;">HPP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $r)
                    @php $detailUrl = route('operational-report.batch', ['batch' => $r->batch_id, 'dari' => $dari, 'sampai' => $sampai]); @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-weight:700;"><a href="{{ $detailUrl }}" class="link-report" data-page-link>{{ $r->sender ?? '(tanpa pengirim)' }}</a></td>
                        <td style="text-align:right;font-weight:700;color:var(--color-primary);"><a href="{{ $detailUrl }}" class="link-report" data-page-link>Rp {{ number_format((float)$r->uang_masuk,0,',','.') }}</a></td>
                        <td style="text-align:right;font-weight:600;">{{ number_format($r->resi,0,',','.') }} <span style="color:#9ca3af;font-size:.7rem;">/ {{ number_format($r->total_order,0,',','.') }}</span></td>
                        <td style="text-align:center;"><span class="clay-badge {{ $r->cod > 0 ? 'clay-badge-blue' : '' }}">{{ number_format($r->cod,0,',','.') }}</span></td>
                        <td style="text-align:center;"><span class="clay-badge {{ $r->bank_transfer > 0 ? 'clay-badge-green' : '' }}">{{ number_format($r->bank_transfer,0,',','.') }}</span></td>
                        <td style="text-align:right;">Rp {{ number_format((float)$r->uang_masuk,0,',','.') }}</td>
                        <td style="text-align:right;color:#6b7280;">Rp {{ number_format((float)$r->hpp,0,',','.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:48px;color:#9ca3af;">Tidak ada order pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot style="background:#FFF5F5;font-weight:800;">
                <tr>
                    <td colspan="2">TOTAL KESELURUHAN</td>
                    <td style="text-align:right;color:var(--color-primary);">Rp {{ number_format((float)$total->uang_masuk,0,',','.') }}</td>
                    <td style="text-align:right;">{{ number_format($total->resi,0,',','.') }} <span style="color:#9ca3af;font-size:.7rem;">/ {{ number_format($total->total_order,0,',','.') }}</span></td>
                    <td style="text-align:center;">{{ number_format($total->cod,0,',','.') }}</td>
                    <td style="text-align:center;">{{ number_format($total->bank_transfer,0,',','.') }}</td>
                    <td style="text-align:right;color:var(--color-secondary);">Rp {{ number_format((float)$total->uang_masuk,0,',','.') }}</td>
                    <td style="text-align:right;color:#6b7280;">Rp {{ number_format((float)$total->hpp,0,',','.') }}</td>
                </tr>
                <tr style="background:#F0FFFE;">
                    <td colspan="6" style="text-align:right;">💰 Uang Masuk vs HPP (Margin)</td>
                    <td style="text-align:right;color:var(--color-secondary);">Rp {{ number_format((float)$total->uang_masuk,0,',','.') }}</td>
                    <td style="text-align:right;color:var(--color-primary);">
                        @php $margin = $total->uang_masuk - $total->hpp; $persen = $total->uang_masuk > 0 ? round($margin / $total->uang_masuk * 100, 1) : 0; @endphp
                        Rp {{ number_format($margin,0,',','.') }} <span style="font-size:.7rem;">({{ $persen }}%)</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    .link-report { color: var(--color-primary,#FF6B6B); text-decoration:none; font-weight:700; }
    .link-report:hover { text-decoration:underline; }
</style>

<div style="font-size:.7rem;color:#9ca3af;margin-top:12px;">
    ⚠️ Resi = order yang sudah punya nomor resi (AWB). HPP dihitung dari <code>products.purchase_price × qty</code>.
    Total Pengeluaran = nilai order (gross revenue) per pengirim. Klik nama pengirim / total pengeluaran untuk detail barang & varian terjual.
</div>

@endsection
