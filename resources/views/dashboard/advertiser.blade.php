@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','📊 Dashboard Saya')
@section('page-subtitle','Performa iklan pribadi')

@section('content')

{{-- Salam --}}
<div class="clay-card" style="padding:20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,#FFF5F5,#fff);" data-reveal>
    <img src="{{ $user->avatar_url }}" style="width:52px;height:52px;border-radius:16px;object-fit:cover;border:2px solid rgba(255,107,107,.2);flex-shrink:0;">
    <div>
        <div style="font-weight:800;font-size:1.1rem;color:#1e1b2e;">Halo, {{ $user->display_name }}! 👋</div>
        <div style="font-size:.82rem;color:#9ca3af;">Berikut ringkasan performa iklan Anda.</div>
    </div>
</div>

{{-- ═══ Date Range Picker ═══ --}}
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;" data-reveal>
    <form id="dash-drp-form" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
        <x-date-range-picker :dari="$dari" :sampai="$sampai" form-id="dash-drp-form" />
    </form>
</div>

{{-- Stats hari ini & bulan ini --}}
<div class="grid-2col">
    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:14px;">💸 Hari Ini</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#FFF5F5;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Spending</div>
                <div style="font-weight:800;font-size:.88rem;color:var(--color-primary);">Rp {{ number_format($spendingHariIni->total_spending??0,0,',','.') }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F5F0FF;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Lead</div>
                <div style="font-weight:800;font-size:.88rem;color:var(--color-purple);">{{ number_format($spendingHariIni->total_lead??0) }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F0FFFE;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Paid</div>
                <div style="font-weight:800;font-size:.88rem;color:var(--color-secondary);">{{ number_format($spendingHariIni->total_paid??0) }}</div>
            </div>
        </div>
    </div>

    <div class="clay-card" style="padding:20px;" data-reveal>
        @php
            $tLead = $spendingBulanIni->total_lead ?? 0;
            $tPaid = $spendingBulanIni->total_paid ?? 0;
            $pr    = $tLead > 0 ? round($tPaid / $tLead * 100, 0) : 0;
            $cpa   = $tPaid > 0 ? round(($spendingBulanIni->total_spending??0) / $tPaid, 0) : 0;
        @endphp
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">📅 Bulan Ini</div>
            <span class="clay-badge {{ $pr>=20?'clay-badge-green':($pr>=10?'clay-badge-yellow':'clay-badge-red') }}">{{ $pr }}% Paid</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#FFF5F5;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Spending</div>
                <div style="font-weight:800;font-size:.88rem;color:var(--color-primary);">Rp {{ number_format($spendingBulanIni->total_spending??0,0,',','.') }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F5F0FF;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Total Lead</div>
                <div style="font-weight:800;font-size:.88rem;color:var(--color-purple);">{{ number_format($tLead) }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F0FFFE;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Total Paid</div>
                <div style="font-weight:800;font-size:.88rem;color:var(--color-secondary);">{{ number_format($tPaid) }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#FFF8F0;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">CPA Paid</div>
                <div style="font-weight:800;font-size:.88rem;color:var(--color-orange);">Rp {{ number_format($cpa,0,',','.') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Chart + Whitelist --}}
<div class="grid-3col">
    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:4px;">📈 Trend 14 Hari</div>
        <div style="font-size:.72rem;color:#9ca3af;margin-bottom:14px;">Spending harian Anda</div>
        @php $maxVal = max($chartSpending->max('total_spending') ?? 1, 1); @endphp
        <div style="display:flex;align-items:flex-end;gap:3px;height:120px;">
            @foreach($chartSpending as $item)
            @php $h = round(($item->total_spending / $maxVal) * 100); @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:1px;">
                <div style="width:100%;display:flex;align-items:flex-end;height:95px;">
                    <div class="chart-bar" style="width:100%;border-radius:3px 3px 0 0;background:var(--color-primary);opacity:.85;height:{{ $h }}%;" data-height="{{ $h }}%"></div>
                </div>
                <span style="font-size:7px;color:#9ca3af;">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m') }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:14px;">✅ Whitelist Aktif</div>
        @forelse($myWhitelists as $wl)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.04);">
            <div>
                <div style="font-size:.83rem;font-weight:600;">{{ $wl->nama }}</div>
                <div style="font-size:.7rem;color:#9ca3af;">{{ ucfirst($wl->platform) }} · {{ $wl->kode }}</div>
            </div>
            @php $sisa = $wl->sisa_saldo; @endphp
            <div style="text-align:right;">
                <div style="font-size:.78rem;font-weight:700;color:{{ $sisa>=0?'var(--color-green)':'var(--color-primary)' }};">
                    Rp {{ number_format(abs($sisa),0,',','.') }}
                </div>
                <div style="font-size:.65rem;color:#9ca3af;">sisa saldo</div>
            </div>
        </div>
        @empty
        <p style="font-size:.82rem;color:#9ca3af;text-align:center;padding:16px 0;">Belum ada whitelist aktif</p>
        @endforelse
        <a href="{{ route('whitelist.index') }}" class="clay-btn clay-btn-outline" style="width:100%;justify-content:center;font-size:.78rem;margin-top:10px;" data-page-link>Lihat Semua →</a>
    </div>
</div>

{{-- Data terbaru --}}
@if($myRecent->isNotEmpty())
<div class="clay-card" style="padding:20px;" data-reveal>
    <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:14px;">🕐 Entri Terbaru</div>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th>Tanggal</th><th>Whitelist</th><th>Produk</th>
                <th style="text-align:right;">Spending</th>
                <th style="text-align:right;">Lead</th>
                <th style="text-align:right;">Paid</th>
                <th style="text-align:right;">Paid Ratio</th>
            </tr></thead>
            <tbody>
            @foreach($myRecent as $sp)
            <tr>
                <td style="font-size:.82rem;white-space:nowrap;">{{ $sp->tanggal->format('d M Y') }}</td>
                <td style="font-size:.8rem;">{{ $sp->whitelist->nama ?? '-' }}</td>
                <td style="font-size:.8rem;">{{ $sp->product->name ?? '-' }}</td>
                <td style="text-align:right;font-weight:700;color:var(--color-primary);font-size:.82rem;">Rp {{ number_format($sp->spending,0,',','.') }}</td>
                <td style="text-align:right;font-size:.82rem;color:var(--color-purple);font-weight:600;">{{ $sp->lead }}</td>
                <td style="text-align:right;font-size:.82rem;color:var(--color-secondary);font-weight:600;">{{ $sp->paid }}</td>
                <td style="text-align:right;">
                    <span class="clay-badge {{ $sp->paid_ratio>=20?'clay-badge-green':($sp->paid_ratio>=10?'clay-badge-yellow':'clay-badge-red') }}" style="font-size:.7rem;">
                        {{ round($sp->paid_ratio) }}%
                    </span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <a href="{{ route('spending.index') }}" class="clay-btn clay-btn-outline" style="margin-top:12px;font-size:.8rem;" data-page-link>Lihat Semua Spending →</a>
</div>
@endif

@endsection
