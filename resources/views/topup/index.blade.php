@extends('layouts.app')
@section('title','Top Up')
@section('page-title','💰 Top Up')
@section('page-subtitle', auth()->user()->hasRole(['owner','super_admin','admin']) ? 'Pengajuan top up dari semua advertiser' : 'Riwayat pengajuan top up Anda')

@section('content')

{{-- Tombol Ajukan (hanya advertiser) + Refresh --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;" data-reveal>
    @if(auth()->user()->hasRole('advertiser'))
    <a href="{{ route('topup.create') }}" class="clay-btn clay-btn-primary" data-page-link>＋ Ajukan Top Up</a>
    @else
    <div></div>
    @endif
    <button onclick="window.location.reload()" class="clay-btn clay-btn-outline" style="padding:6px 14px;font-size:.78rem;">
        🔄 Refresh
    </button>
</div>

@php $u = auth()->user(); @endphp

@if($u->hasRole(['owner','super_admin','admin']))
    {{-- ── Folder Tabs per Advertiser ──────────────────────────── --}}
    <div style="display:flex;flex-wrap:wrap;gap:0;align-items:flex-end;
                margin-bottom:-2px;position:relative;z-index:2;" data-reveal>

        {{-- Tab: Semua --}}
        <a href="{{ route('topup.index', ['tab' => 'all']) }}"
           id="folder-tab-all"
           style="padding:9px 18px 11px;text-decoration:none;
                  border:2px solid {{ ($activeTab ?? 'all') === 'all' ? 'rgba(255,107,107,.25)' : 'rgba(0,0,0,.08)' }};
                  border-bottom:2px solid {{ ($activeTab ?? 'all') === 'all' ? '#fff' : 'rgba(0,0,0,.08)' }};
                  border-radius:14px 14px 0 0;
                  background:{{ ($activeTab ?? 'all') === 'all' ? '#fff' : '#f5f5f5' }};
                  font-family:inherit;font-size:.82rem;
                  font-weight:{{ ($activeTab ?? 'all') === 'all' ? '700' : '500' }};
                  color:{{ ($activeTab ?? 'all') === 'all' ? 'var(--color-primary,#FF6B6B)' : '#6b7280' }};
                  cursor:pointer;transition:all .2s;
                  margin-right:4px;position:relative;z-index:{{ ($activeTab ?? 'all') === 'all' ? 3 : 1 }};">
            📋 Semua
            <span style="font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;
                         background:{{ ($activeTab ?? 'all') === 'all' ? 'rgba(255,107,107,.12)' : 'rgba(0,0,0,.06)' }};
                         color:{{ ($activeTab ?? 'all') === 'all' ? 'var(--color-primary)' : '#9ca3af' }};">
                {{ $proposals->total() }}
            </span>
        </a>

        @foreach($advertisers as $adv)
        @php $isActive = ($activeTab == $adv->id); @endphp
        <a href="{{ route('topup.index', ['tab' => $adv->id]) }}"
           id="folder-tab-{{ $adv->id }}"
           style="padding:9px 18px 11px;text-decoration:none;
                  border:2px solid {{ $isActive ? 'rgba(255,107,107,.25)' : 'rgba(0,0,0,.08)' }};
                  border-bottom:2px solid {{ $isActive ? '#fff' : 'rgba(0,0,0,.08)' }};
                  border-radius:14px 14px 0 0;
                  background:{{ $isActive ? '#fff' : '#f5f5f5' }};
                  font-family:inherit;font-size:.82rem;
                  font-weight:{{ $isActive ? '700' : '500' }};
                  color:{{ $isActive ? 'var(--color-primary,#FF6B6B)' : '#6b7280' }};
                  cursor:pointer;transition:all .2s;
                  display:flex;align-items:center;gap:6px;
                  margin-right:4px;position:relative;z-index:{{ $isActive ? 3 : 1 }};">
            <img src="{{ $adv->avatar_url }}"
                 style="width:22px;height:22px;border-radius:6px;object-fit:cover;flex-shrink:0;
                        border:{{ $isActive ? '1.5px solid rgba(255,107,107,.3)' : '1.5px solid #ddd' }};">
            {{ $adv->display_name }}
            @if(isset($summaryPerAdv[$adv->id]))
            <span style="font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;
                         background:{{ $isActive ? 'rgba(255,107,107,.12)' : 'rgba(0,0,0,.06)' }};
                         color:{{ $isActive ? 'var(--color-primary)' : '#9ca3af' }};">
                {{ $summaryPerAdv[$adv->id]['total'] }}
            </span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Konten --}}
    <div style="border:2px solid rgba(255,107,107,.18);border-radius:0 16px 16px 16px;
                background:#fff;overflow:hidden;position:relative;z-index:1;" data-reveal>
@endif

    <div class="table-scroll">
        <table class="clay-table">
            <thead>
                <tr>
                    @if(!$u->hasRole('advertiser'))
                    <th>Advertiser</th>
                    @endif
                    <th>Tanggal</th>
                    <th style="text-align:right;">Total Nominal</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Item</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($proposals as $p)
            @php
                $statusMap = [
                    'pending'             => ['class'=>'clay-badge-yellow', 'label'=>'Menunggu'],
                    'approved'            => ['class'=>'clay-badge-green',  'label'=>'Disetujui'],
                    'declined'            => ['class'=>'clay-badge-red',    'label'=>'Ditolak'],
                    'menunggu_pembayaran' => ['class'=>'clay-badge-blue',   'label'=>'⏳ Menunggu VA'],
                    'completed'           => ['class'=>'clay-badge-green',  'label'=>'✅ Selesai'],
                ];
                $sm = $statusMap[$p->status] ?? ['class'=>'clay-badge-gray','label'=>$p->status];
                $totalItems = $p->items->count();
                $paidItems  = $p->items->where('payment_status','paid')->count();
            @endphp
            <tr>
                @if(!$u->hasRole('advertiser'))
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <img src="{{ $p->user->avatar_url ?? '' }}" style="width:28px;height:28px;border-radius:8px;object-fit:cover;">
                        <span style="font-weight:600;font-size:.83rem;">{{ $p->user->display_name ?? '-' }}</span>
                    </div>
                </td>
                @endif
                <td style="font-size:.82rem;white-space:nowrap;">{{ $p->created_at->translatedFormat('d M Y H:i') }}</td>
                <td style="text-align:right;font-weight:700;color:var(--color-primary);white-space:nowrap;">
                    Rp {{ number_format($p->total_nominal,0,',','.') }}
                </td>
                <td style="text-align:center;">
                    <span class="clay-badge {{ $sm['class'] }}" style="font-size:.7rem;">{{ $sm['label'] }}</span>
                    @if($p->isVaPaid())
                    <div style="font-size:.6rem;color:var(--color-green);font-weight:600;margin-top:2px;">✅ VA Dibayar</div>
                    @endif
                </td>
                <td style="text-align:center;font-size:.8rem;">
                    @if($paidItems > 0)
                        <span style="color:var(--color-green);font-weight:600;">{{ $paidItems }}/{{ $totalItems }}</span>
                    @else
                        <span style="color:#9ca3af;">{{ $totalItems }}</span>
                    @endif
                </td>
                <td style="text-align:right;">
                    <a href="{{ route('topup.show',$p) }}" class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.72rem;" data-page-link>
                        @if($u->hasRole(['owner','super_admin','admin']) && $p->isPending())
                            🔍 Review
                        @else
                            👁 Detail
                        @endif
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:48px 16px;">
                    <div style="font-size:2.5rem;margin-bottom:8px;">💰</div>
                    @if(isset($activeTab) && $activeTab !== 'all' && isset($advertisers))
                        <p style="color:#9ca3af;">Advertiser ini belum mengajukan top up</p>
                    @else
                        <p style="color:#9ca3af;">Belum ada pengajuan top up</p>
                        @if($u->hasRole('advertiser'))
                        <a href="{{ route('topup.create') }}" class="clay-btn clay-btn-primary" style="margin-top:12px;" data-page-link>＋ Ajukan Sekarang</a>
                        @endif
                    @endif
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($proposals->hasPages())
    <div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $proposals->links() }}</div>
    @endif

@if($u->hasRole(['owner','super_admin','admin']))
    </div>
@endif

@endsection
