@extends('layouts.app')
@section('title','Pengajuan')
@section('page-title','📋 Pengajuan')
@section('page-subtitle','Review & approve pengajuan top up')

@section('content')
@php
    $u = auth()->user();
    $isAdmin = $u->hasRole(['super_admin','keuangan']);
    $canVerify = $u->hasRole(['owner','super_admin','admin']);
@endphp

@if(session('success'))
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#d1fae5;color:#065f46;font-weight:600;border-radius:8px;">
    {{ session('success') }}
</div>
@endif

@php
    $pendingTopUp = \App\Models\TopUpProposal::where('status','pending')->count();
    $defaultTab = 'topup';
@endphp

{{-- ═══ INFO BOX ═══ --}}
<div style="background:linear-gradient(135deg,#FFF5F5,#fff);border:1.5px solid rgba(255,107,107,.15);border-radius:14px;padding:16px 20px;margin-bottom:20px;" data-reveal>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        <span style="font-size:1.3rem;">💡</span>
        <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">Cara Kerja Pengajuan</span>
    </div>
    <div style="font-size:.78rem;color:#6b7280;line-height:1.6;">
        <strong>💰 Top Up:</strong> Advertiser ajukan → Anda acc/tolak → Advertiser input VA → Anda tandai VA dibayar → Selesai.<br>

    </div>
</div>



        <div style="flex:1;min-width:200px;max-width:360px;">
            <select id="sourceAccount" class="clay-input" style="width:100%;" onchange="updateApproveButtons()">
                <option value="">— Pilih Akun Sumber Dana —</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type_label }}) — Rp {{ number_format((float)$acc->current_balance,0,',','.') }}</option>
                @endforeach
            </select>
        </div>
        <div id="sourceAccountWarning" style="display:none;font-size:.75rem;color:#dc2626;font-weight:600;background:#fef2f2;padding:4px 10px;border-radius:8px;">
            ⚠️ Pilih sumber dana terlebih dahulu untuk bisa menyetujui pengajuan
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     TAB SWITCHER
     ═══════════════════════════════════════════════════════════════════════ --}}
<div style="display:flex;gap:8px;margin-bottom:20px;" data-reveal>
    <button onclick="showTab('topup')" id="tab-btn-topup"
            class="approval-tab {{ $defaultTab === 'topup' ? 'active' : '' }}"
            style="padding:10px 20px;border-radius:12px;border:2px solid {{ $defaultTab === 'topup' ? 'var(--color-primary)' : '#e5e7eb' }};background:{{ $defaultTab === 'topup' ? 'var(--color-primary)' : '#f9fafb' }};color:{{ $defaultTab === 'topup' ? '#fff' : '#6b7280' }};font-weight:{{ $defaultTab === 'topup' ? '700' : '600' }};font-size:.85rem;cursor:pointer;font-family:inherit;transition:all .2s;">
        💰 Top Up
        @if($pendingTopUp > 0)
        <span style="background:{{ $defaultTab === 'topup' ? 'rgba(255,255,255,.3)' : 'rgba(255,107,107,.12)' }};color:{{ $defaultTab === 'topup' ? '#fff' : 'var(--color-primary)' }};padding:1px 8px;border-radius:999px;font-size:.7rem;margin-left:6px;">{{ $pendingTopUp }} pending</span>
        @endif
    </button>

</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     TAB: TOP UP
     ═══════════════════════════════════════════════════════════════════════ --}}
<div id="tab-topup" class="approval-panel" style="display:{{ $defaultTab === 'topup' ? 'block' : 'none' }};">

    {{-- Tab per advertiser --}}
    @if($isAdmin && $advertisers->count())
    <div style="display:flex;flex-wrap:wrap;gap:0;align-items:flex-end;margin-bottom:-2px;position:relative;z-index:2;" data-reveal>
        <a href="{{ route('approval.index', ['tab' => 'all']) }}"
           style="padding:9px 18px 11px;text-decoration:none;border:2px solid {{ ($activeTab ?? 'all') === 'all' ? 'rgba(255,107,107,.25)' : 'rgba(0,0,0,.08)' }};border-bottom:2px solid {{ ($activeTab ?? 'all') === 'all' ? '#fff' : 'rgba(0,0,0,.08)' }};border-radius:14px 14px 0 0;background:{{ ($activeTab ?? 'all') === 'all' ? '#fff' : '#f5f5f5' }};font-family:inherit;font-size:.82rem;font-weight:{{ ($activeTab ?? 'all') === 'all' ? '700' : '500' }};color:{{ ($activeTab ?? 'all') === 'all' ? 'var(--color-primary,#FF6B6B)' : '#6b7280' }};cursor:pointer;transition:all .2s;margin-right:4px;position:relative;z-index:{{ ($activeTab ?? 'all') === 'all' ? 3 : 1 }};">
            📋 Semua
            <span style="font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;background:{{ ($activeTab ?? 'all') === 'all' ? 'rgba(255,107,107,.12)' : 'rgba(0,0,0,.06)' }};color:{{ ($activeTab ?? 'all') === 'all' ? 'var(--color-primary)' : '#9ca3af' }};">{{ $topUpProposals->total() }}</span>
        </a>
        @foreach($advertisers as $adv)
        @php $isActive = ($activeTab == $adv->id); @endphp
        <a href="{{ route('approval.index', ['tab' => $adv->id]) }}"
           style="padding:9px 18px 11px;text-decoration:none;border:2px solid {{ $isActive ? 'rgba(255,107,107,.25)' : 'rgba(0,0,0,.08)' }};border-bottom:2px solid {{ $isActive ? '#fff' : 'rgba(0,0,0,.08)' }};border-radius:14px 14px 0 0;background:{{ $isActive ? '#fff' : '#f5f5f5' }};font-family:inherit;font-size:.82rem;font-weight:{{ $isActive ? '700' : '500' }};color:{{ $isActive ? 'var(--color-primary,#FF6B6B)' : '#6b7280' }};cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:6px;margin-right:4px;position:relative;z-index:{{ $isActive ? 3 : 1 }};">
            <img src="{{ $adv->avatar_url }}" style="width:22px;height:22px;border-radius:6px;object-fit:cover;flex-shrink:0;border:{{ $isActive ? '1.5px solid rgba(255,107,107,.3)' : '1.5px solid #ddd' }};">
            {{ $adv->display_name }}
            @if(isset($summaryPerAdv[$adv->id]))
            <span style="font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;background:{{ $isActive ? 'rgba(255,107,107,.12)' : 'rgba(0,0,0,.06)' }};color:{{ $isActive ? 'var(--color-primary)' : '#9ca3af' }};">{{ $summaryPerAdv[$adv->id]['total'] }}</span>
            @endif
        </a>
        @endforeach
    </div>
    <div style="border:2px solid rgba(255,107,107,.18);border-radius:0 16px 16px 16px;background:#fff;overflow:hidden;position:relative;z-index:1;" data-reveal>
    @else
    <div data-reveal>
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
                @forelse($topUpProposals as $p)
                @php
                    $statusMap = [
                        'pending'              => ['class'=>'clay-badge-yellow', 'label'=>'⏳ Menunggu'],
                        'approved'             => ['class'=>'clay-badge-green',  'label'=>'✅ Disetujui'],
                        'revision_requested'   => ['class'=>'clay-badge-red',    'label'=>'Perlu Revisi'],
                        'payment_in_progress'  => ['class'=>'clay-badge-blue',   'label'=>'💳 Menunggu VA'],
                        'declined'             => ['class'=>'clay-badge-red',    'label'=>'Ditolak'],
                        'completed'            => ['class'=>'clay-badge-green',  'label'=>'✅ Selesai'],
                    ];
                    $sm = $statusMap[$p->status] ?? ['class'=>'clay-badge-gray','label'=>$p->status];
                    $totalItems = $p->items->count();
                    $paidItems  = $p->items->where('payment_status','paid')->count();
                @endphp
                <tr style="{{ $p->status === 'pending' ? 'background:#fffbeb;' : '' }}">
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
                        @if($p->payment_mode)
                        <div style="font-size:.6rem;color:#6b7280;font-weight:600;margin-top:2px;">{{ strtoupper($p->payment_mode) }}</div>
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
                        @if($p->status === 'pending' && $isAdmin)
                            <a href="{{ route('topup.show',$p) }}" class="clay-btn clay-btn-primary" style="padding:5px 12px;font-size:.72rem;">
                                🔍 Review
                            </a>
                        @else
                            <a href="{{ route('topup.show',$p) }}" class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.72rem;">
                                👁 Detail
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:48px 16px;">
                        <div style="font-size:2.5rem;margin-bottom:8px;">💰</div>
                        <p style="color:#9ca3af;">Belum ada pengajuan top up</p>
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($topUpProposals->hasPages())
        <div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">
            {{ $topUpProposals->appends(['tab' => $activeTab])->links() }}
        </div>
        @endif

    @if($isAdmin && $advertisers->count())
    </div>
    @endif
</div>
@endsection

