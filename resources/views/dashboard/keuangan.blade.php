@extends('layouts.app')
@section('title','Dashboard Keuangan')
@section('page-title','💰 Dashboard Keuangan')
@section('page-subtitle','Laporan keuangan & performa iklan')

@section('content')

@php
    $tSpend  = $bulanIni->total_spending ?? 0;
    $tLead   = $bulanIni->total_lead    ?? 0;
    $tPaid   = $bulanIni->total_paid    ?? 0;
    $pr      = $tLead > 0 ? round($tPaid / $tLead * 100, 0) : 0;
    $cpaLead = $tLead > 0 ? round($tSpend / $tLead, 0) : 0;
    $cpaPaid = $tPaid > 0 ? round($tSpend / $tPaid, 0) : 0;

    $prevSpend = $bulanLalu->total_spending ?? 0;
    $growth    = $prevSpend > 0 ? round(($tSpend - $prevSpend) / $prevSpend * 100, 0) : 0;
@endphp

<div class="grid-stats" style="margin-bottom:20px;">
    <div class="stat-card stat-card-1" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-bottom:8px;">Total Spending</div>
        <div style="font-size:1.4rem;font-weight:900;">Rp {{ number_format($tSpend/1000000,1) }}jt</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">{{ $growth>=0?'▲':'▼' }} {{ abs($growth) }}% vs bln lalu</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;">💸</div>
    </div>
    <div class="stat-card stat-card-2" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-bottom:8px;">Total Lead</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $tLead }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">Bulan {{ now()->translatedFormat('F') }}</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;">📢</div>
    </div>
    <div class="stat-card stat-card-3" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-bottom:8px;">Total Paid</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $tPaid }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">Paid Ratio {{ $pr }}%</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;">✅</div>
    </div>
    <div class="stat-card stat-card-4" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-bottom:8px;">CPA Paid</div>
        <div style="font-size:1.4rem;font-weight:900;">Rp {{ number_format($cpaPaid,0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">CPA Lead Rp {{ number_format($cpaLead,0,',','.') }}</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;">📊</div>
    </div>
</div>

<div class="grid-2col">
    {{-- Top Whitelist --}}
    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:4px;">✅ Top Whitelist</div>
        <div style="font-size:.72rem;color:#9ca3af;margin-bottom:14px;">Bulan ini · berdasarkan spending</div>
        @php $totalWl = $spendingPerWhitelist->sum('total_spending') ?: 1; @endphp
        @forelse($spendingPerWhitelist as $w)
        @php
            $pct    = round(($w->total_spending / $totalWl) * 100);
            $wlPr   = ($w->total_lead??0) > 0 ? round(($w->total_paid??0)/($w->total_lead??0)*100,0) : 0;
        @endphp
        <div style="margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">
                <span style="font-size:.8rem;font-weight:600;max-width:60%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ $w->whitelist->nama ?? '-' }}
                </span>
                <span style="font-size:.72rem;font-weight:700;color:var(--color-primary);">{{ $pct }}%</span>
            </div>
            <div style="height:5px;border-radius:999px;background:#FFF5F5;overflow:hidden;">
                <div style="height:5px;border-radius:999px;background:var(--color-primary);opacity:.7;width:{{ $pct }}%;"></div>
            </div>
            <div style="display:flex;gap:10px;font-size:.67rem;color:#9ca3af;margin-top:2px;">
                <span>Rp {{ number_format($w->total_spending,0,',','.') }}</span>
                <span>Lead: {{ $w->total_lead }}</span>
                <span>Paid: {{ $w->total_paid }}</span>
                <span class="clay-badge {{ $wlPr>=20?'clay-badge-green':($wlPr>=10?'clay-badge-yellow':'clay-badge-red') }}" style="font-size:.62rem;padding:1px 6px;">{{ $wlPr }}%</span>
            </div>
        </div>
        @empty
        <p style="font-size:.82rem;color:#9ca3af;text-align:center;padding:16px 0;">Belum ada data</p>
        @endforelse
    </div>

    {{-- Top Advertiser --}}
    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:14px;">🏆 Top Advertiser</div>
        @php $medals = ['🥇','🥈','🥉','4️⃣','5️⃣']; @endphp
        @forelse($topAdvertiser as $idx => $adv)
        @php
            $advPr  = ($adv->total_lead??0) > 0 ? round(($adv->total_paid??0)/($adv->total_lead??0)*100,0) : 0;
            $advCpa = ($adv->total_paid??0) > 0 ? round(($adv->total_spending??0)/($adv->total_paid??0),0) : 0;
        @endphp
        <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:12px;margin-bottom:4px;"
             onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background=''">
            <span style="font-size:1.1rem;">{{ $medals[$idx]??($idx+1) }}</span>
            <img src="{{ $adv->user->avatar_url??'' }}" style="width:28px;height:28px;border-radius:8px;object-fit:cover;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $adv->user->display_name??'Unknown' }}</div>
                <div style="font-size:.68rem;color:#9ca3af;">Rp {{ number_format($adv->total_spending??0,0,',','.') }} · PR {{ $advPr }}%</div>
            </div>
            <span class="clay-badge {{ $advPr>=20?'clay-badge-green':($advPr>=10?'clay-badge-yellow':'clay-badge-red') }}" style="font-size:.68rem;">{{ $advPr }}%</span>
        </div>
        @empty
        <p style="font-size:.82rem;color:#9ca3af;text-align:center;padding:24px 0;">Belum ada data</p>
        @endforelse
    </div>
</div>
@endsection
