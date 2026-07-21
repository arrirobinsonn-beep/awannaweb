@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','📊 Dashboard')
@section('page-subtitle','Overview lengkap semua divisi Awanna')

@section('content')

{{-- ── Stat Cards ──────────────────────────────────────────── --}}
<div class="grid-stats" style="margin-bottom:20px;">
    <div class="stat-card stat-card-1" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Total Supplier</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stats['total_supplier'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">🏭 Aktif</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;pointer-events:none;">🏭</div>
    </div>
    <div class="stat-card stat-card-2" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Total Produk</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stats['total_produk'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">📦 Aktif</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;pointer-events:none;">📦</div>
    </div>
    <div class="stat-card stat-card-3" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Total User</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stats['total_user'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">👥 Aktif</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;pointer-events:none;">👥</div>
    </div>
    <div class="stat-card stat-card-4" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Total Whitelist</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stats['total_whitelist'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">✅ Aktif</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;pointer-events:none;">✅</div>
    </div>
</div>

{{-- ── Spending Hari Ini + Bulan Ini ────────────────────────── --}}
<div class="grid-2col">
    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div>
                <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">💸 Hari Ini</div>
                <div style="font-size:.72rem;color:#9ca3af;">{{ now()->translatedFormat('l, d M Y') }}</div>
            </div>
            <span class="clay-badge clay-badge-blue">Live</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#FFF5F5;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Spending</div>
                <div style="font-weight:800;font-size:.9rem;color:var(--color-primary);">Rp {{ number_format($spendingHariIni->total_spending??0,0,',','.') }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F5F0FF;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Lead</div>
                <div style="font-weight:800;font-size:.9rem;color:var(--color-purple);">{{ number_format($spendingHariIni->total_lead??0) }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F0FFFE;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Paid</div>
                <div style="font-weight:800;font-size:.9rem;color:var(--color-secondary);">{{ number_format($spendingHariIni->total_paid??0) }}</div>
            </div>
        </div>
    </div>

    <div class="clay-card" style="padding:20px;" data-reveal>
        @php
            $totalLead = $spendingBulanIni->total_lead ?? 0;
            $totalPaid = $spendingBulanIni->total_paid ?? 0;
            $paidRatio = $totalLead > 0 ? round($totalPaid / $totalLead * 100, 0) : 0;
            $cpaPaid   = $totalPaid > 0 ? round(($spendingBulanIni->total_spending??0) / $totalPaid, 0) : 0;
        @endphp
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div>
                <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">📅 Bulan Ini</div>
                <div style="font-size:.72rem;color:#9ca3af;">{{ now()->translatedFormat('F Y') }}</div>
            </div>
            <span class="clay-badge {{ $paidRatio >= 20 ? 'clay-badge-green' : 'clay-badge-yellow' }}">
                Paid Ratio {{ $paidRatio }}%
            </span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#FFF5F5;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Total Spending</div>
                <div style="font-weight:800;font-size:.9rem;color:var(--color-primary);">Rp {{ number_format($spendingBulanIni->total_spending??0,0,',','.') }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F5F0FF;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Total Lead</div>
                <div style="font-weight:800;font-size:.9rem;color:var(--color-purple);">{{ number_format($totalLead) }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F0FFFE;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">Total Paid</div>
                <div style="font-weight:800;font-size:.9rem;color:var(--color-secondary);">{{ number_format($totalPaid) }}</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#FFF8F0;">
                <div style="font-size:.68rem;color:#6b7280;margin-bottom:3px;">CPA Paid</div>
                <div style="font-weight:800;font-size:.9rem;color:var(--color-orange);">Rp {{ number_format($cpaPaid,0,',','.') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Chart + Top Whitelist ────────────────────────────────── --}}
<div class="grid-3col">
    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:4px;">📈 Trend Spending (14 Hari)</div>
        <div style="font-size:.72rem;color:#9ca3af;margin-bottom:14px;">Total spending harian semua advertiser</div>
        @php
            $maxVal = max($chartSpending->max('total_spending') ?? 1, 1);
        @endphp
        <div style="display:flex;align-items:flex-end;gap:3px;height:130px;">
            @foreach($chartSpending as $item)
            @php $h = round(($item->total_spending / $maxVal) * 100); @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;"
                 data-tip="{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m') }}: Rp {{ number_format($item->total_spending,0,',','.') }}">
                <div style="width:100%;display:flex;align-items:flex-end;height:100px;">
                    <div class="chart-bar" style="width:100%;border-radius:4px 4px 0 0;background:var(--color-primary);opacity:.85;height:{{ $h }}%;" data-height="{{ $h }}%"></div>
                </div>
                <span style="font-size:8px;color:#9ca3af;">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m') }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:2px;">✅ Top Whitelist</div>
        <div style="font-size:.72rem;color:#9ca3af;margin-bottom:14px;">Bulan ini berdasarkan spending</div>
        @php $totalWl = $spendingPerWhitelist->sum('total_spending') ?: 1; @endphp
        @forelse($spendingPerWhitelist as $w)
        @php $pct = round(($w->total_spending / $totalWl) * 100); @endphp
        <div style="margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                <span style="font-size:.8rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:60%;">
                    {{ $w->whitelist->nama ?? 'Tidak diketahui' }}
                </span>
                <span style="font-size:.72rem;font-weight:700;color:var(--color-primary);">{{ $pct }}%</span>
            </div>
            <div style="height:5px;border-radius:999px;background:#FFF5F5;overflow:hidden;">
                <div style="height:5px;border-radius:999px;background:var(--color-primary);opacity:.7;width:{{ $pct }}%;"></div>
            </div>
            <div style="font-size:.67rem;color:#9ca3af;margin-top:1px;">
                Rp {{ number_format($w->total_spending,0,',','.') }} · {{ $w->total_lead }} lead · {{ $w->total_paid }} paid
            </div>
        </div>
        @empty
        <p style="font-size:.82rem;color:#9ca3af;text-align:center;padding:16px 0;">Belum ada data</p>
        @endforelse
    </div>
</div>

{{-- ── Top Advertiser ───────────────────────────────────────── --}}
<div class="clay-card" style="padding:20px;" data-reveal>
    <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;margin-bottom:14px;">🏆 Top Advertiser — {{ now()->translatedFormat('F Y') }}</div>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th>#</th><th>Advertiser</th>
                <th style="text-align:right;">Spending</th>
                <th style="text-align:right;">Lead</th>
                <th style="text-align:right;">Paid</th>
                <th style="text-align:right;">Paid Ratio</th>
                <th style="text-align:right;">CPA Paid</th>
            </tr></thead>
            <tbody>
            @php $medals = ['🥇','🥈','🥉','4️⃣','5️⃣']; @endphp
            @forelse($topAdvertiser as $idx => $adv)
            @php
                $pr   = ($adv->total_lead??0) > 0 ? round(($adv->total_paid??0)/($adv->total_lead??0)*100,0) : 0;
                $cpa  = ($adv->total_paid??0) > 0 ? round(($adv->total_spending??0)/($adv->total_paid??0),0) : 0;
            @endphp
            <tr>
                <td style="font-size:1.1rem;">{{ $medals[$idx] ?? ($idx+1) }}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <img src="{{ $adv->user->avatar_url ?? '' }}" style="width:28px;height:28px;border-radius:8px;object-fit:cover;">
                        <div style="font-weight:700;font-size:.83rem;">{{ $adv->user->display_name ?? 'Unknown' }}</div>
                    </div>
                </td>
                <td style="text-align:right;font-weight:700;color:var(--color-primary);font-size:.83rem;">Rp {{ number_format($adv->total_spending??0,0,',','.') }}</td>
                <td style="text-align:right;font-size:.83rem;color:var(--color-purple);font-weight:600;">{{ number_format($adv->total_lead??0) }}</td>
                <td style="text-align:right;font-size:.83rem;color:var(--color-secondary);font-weight:600;">{{ number_format($adv->total_paid??0) }}</td>
                <td style="text-align:right;">
                    <span class="clay-badge {{ $pr>=20?'clay-badge-green':($pr>=10?'clay-badge-yellow':'clay-badge-red') }}" style="font-size:.7rem;">{{ $pr }}%</span>
                </td>
                <td style="text-align:right;font-size:.82rem;color:#6b7280;">Rp {{ number_format($cpa,0,',','.') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af;">Belum ada data bulan ini</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
