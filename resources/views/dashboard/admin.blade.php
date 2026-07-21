@extends('layouts.app')
@section('title','Dashboard Admin')
@section('page-title','🛡 Dashboard Admin')
@section('page-subtitle','Overview operasional sistem')

@section('content')

<div class="grid-stats" style="margin-bottom:20px;">
    <div class="stat-card stat-card-1" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-bottom:8px;">Total Supplier</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stats['total_supplier'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">🏭 Aktif</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;">🏭</div>
    </div>
    <div class="stat-card stat-card-2" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-bottom:8px;">Total Produk</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stats['total_produk'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">📦 Aktif</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;">📦</div>
    </div>
    <div class="stat-card stat-card-3" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-bottom:8px;">Total User</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stats['total_user'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">👥 Aktif</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;">👥</div>
    </div>
    <div class="stat-card stat-card-4" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-bottom:8px;">Stok Kritis</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stats['stok_kritis'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">⚠️ Stok ≤ 10</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;">⚠️</div>
    </div>
</div>

{{-- Summary spending bulan ini --}}
@php
    $tLead = $spendingBulanIni->total_lead ?? 0;
    $tPaid = $spendingBulanIni->total_paid ?? 0;
    $pr    = $tLead > 0 ? round($tPaid / $tLead * 100, 0) : 0;
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;" data-reveal>
    <div class="clay-card-sm" style="padding:16px;text-align:center;background:#FFF5F5;">
        <div style="font-size:.72rem;color:#6b7280;margin-bottom:4px;">Total Spending Bulan Ini</div>
        <div style="font-weight:800;font-size:1.1rem;color:var(--color-primary);">Rp {{ number_format($spendingBulanIni->total_spending??0,0,',','.') }}</div>
    </div>
    <div class="clay-card-sm" style="padding:16px;text-align:center;background:#F5F0FF;">
        <div style="font-size:.72rem;color:#6b7280;margin-bottom:4px;">Total Lead</div>
        <div style="font-weight:800;font-size:1.1rem;color:var(--color-purple);">{{ number_format($tLead) }}</div>
    </div>
    <div class="clay-card-sm" style="padding:16px;text-align:center;background:#F0FFFE;">
        <div style="font-size:.72rem;color:#6b7280;margin-bottom:4px;">Paid Ratio</div>
        <div style="font-weight:800;font-size:1.1rem;color:var(--color-secondary);">{{ $pr }}%</div>
    </div>
</div>

<div class="grid-2col">
    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div>
                <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">⚠️ Stok Kritis</div>
                <div style="font-size:.72rem;color:#9ca3af;">Produk dengan stok ≤ 10</div>
            </div>
            <a href="{{ route('gudang.stok') }}" class="clay-btn clay-btn-outline" style="font-size:.75rem;padding:6px 12px;" data-page-link>Lihat Semua</a>
        </div>
        @forelse($produkStokRendah as $p)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.04);">
            <div>
                <div style="font-size:.83rem;font-weight:700;">{{ $p->nama_produk }}</div>
                <div style="font-size:.7rem;color:#9ca3af;">{{ $p->supplier->nama_supplier ?? '-' }}</div>
            </div>
            <span class="clay-badge {{ $p->stok<=0?'clay-badge-red':'clay-badge-yellow' }}">{{ $p->stok }} {{ $p->satuan }}</span>
        </div>
        @empty
        <div style="text-align:center;padding:24px 0;">
            <div style="font-size:2rem;margin-bottom:8px;">✅</div>
            <p style="font-size:.82rem;color:#9ca3af;">Semua stok aman</p>
        </div>
        @endforelse
    </div>

    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">👥 User Terbaru</div>
            <a href="{{ route('user.index') }}" class="clay-btn clay-btn-outline" style="font-size:.75rem;padding:6px 12px;" data-page-link>Kelola</a>
        </div>
        @foreach($recentUsers as $u)
        @php $rn = $u->getRoleNames()->first() ?? '-'; @endphp
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.04);">
            <img src="{{ $u->avatar_url }}" style="width:30px;height:30px;border-radius:8px;object-fit:cover;flex-shrink:0;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $u->display_name }}</div>
                <div style="font-size:.7rem;color:#9ca3af;">{{ $u->email }}</div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px;">
                <span class="clay-badge clay-badge-blue" style="font-size:.65rem;">{{ ucfirst(str_replace('_',' ',$rn)) }}</span>
                <span class="clay-badge {{ $u->is_profile_complete?'clay-badge-green':'clay-badge-yellow' }}" style="font-size:.65rem;">
                    {{ $u->is_profile_complete?'Lengkap':'Belum' }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
