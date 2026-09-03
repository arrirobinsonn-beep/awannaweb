@extends('layouts.app')
@section('title','Pengajuan')
@section('page-title','📋 Pengajuan')
@section('page-subtitle','Review & approve pengajuan top up dan pembelian barang')

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
    $pendingPurchase = \App\Models\Purchase::where('status','pending')->count();
    $needsVerification = \App\Models\Purchase::where('status','approved')->count();
    $defaultTab = ($pendingPurchase > 0 && $pendingTopUp === 0) ? 'purchase' : 'topup';
@endphp

{{-- ═══ INFO BOX ═══ --}}
<div style="background:linear-gradient(135deg,#FFF5F5,#fff);border:1.5px solid rgba(255,107,107,.15);border-radius:14px;padding:16px 20px;margin-bottom:20px;" data-reveal>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        <span style="font-size:1.3rem;">💡</span>
        <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">Cara Kerja Pengajuan</span>
    </div>
    <div style="font-size:.78rem;color:#6b7280;line-height:1.6;">
        <strong>💰 Top Up:</strong> Advertiser ajukan → Anda acc/tolak → Advertiser input VA → Anda tandai VA dibayar → Selesai.<br>
        <strong>📥 Pembelian:</strong> Admin ajukan → Anda acc (Menunggu) → Admin verifikasi barang datang (isi qty aktual & harga) → Stok masuk.
    </div>
</div>

{{-- ═══ SUMBER DANA ═══ --}}
<div style="background:linear-gradient(135deg,#f0fdf4,#fff);border:1.5px solid rgba(16,185,129,.2);border-radius:14px;padding:14px 18px;margin-bottom:20px;" data-reveal>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:1.1rem;">🏦</span>
            <span style="font-weight:700;font-size:.85rem;color:#1e1b2e;">Sumber Dana</span>
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
    <button onclick="showTab('purchase')" id="tab-btn-purchase"
            class="approval-tab {{ $defaultTab === 'purchase' ? 'active' : '' }}"
            style="padding:10px 20px;border-radius:12px;border:2px solid {{ $defaultTab === 'purchase' ? 'var(--color-primary)' : '#e5e7eb' }};background:{{ $defaultTab === 'purchase' ? 'var(--color-primary)' : '#f9fafb' }};color:{{ $defaultTab === 'purchase' ? '#fff' : '#6b7280' }};font-weight:{{ $defaultTab === 'purchase' ? '700' : '600' }};font-size:.85rem;cursor:pointer;font-family:inherit;transition:all .2s;">
        📥 Pembelian Barang
        @if($pendingPurchase > 0 || $needsVerification > 0)
        <span style="background:{{ $defaultTab === 'purchase' ? 'rgba(255,255,255,.3)' : 'rgba(255,107,107,.12)' }};color:{{ $defaultTab === 'purchase' ? '#fff' : 'var(--color-primary)' }};padding:1px 8px;border-radius:999px;font-size:.7rem;margin-left:6px;">{{ $pendingPurchase + $needsVerification }} perlu diproses</span>
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

{{-- ═══════════════════════════════════════════════════════════════════════
     TAB: PEMBELIAN BARANG
     ═══════════════════════════════════════════════════════════════════════ --}}
<div id="tab-purchase" class="approval-panel" style="display:{{ $defaultTab === 'purchase' ? 'block' : 'none' }};">

    {{-- Filter Status --}}
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;" data-reveal>
        <a href="{{ route('approval.index', ['tab' => 'purchase']) }}"
           style="padding:7px 14px;border-radius:10px;border:1.5px solid {{ !$purchaseStatus ? 'var(--color-primary)' : '#e5e7eb' }};background:{{ !$purchaseStatus ? 'var(--color-primary)' : '#fff' }};color:{{ !$purchaseStatus ? '#fff' : '#6b7280' }};font-weight:600;font-size:.78rem;text-decoration:none;font-family:inherit;transition:all .15s;">
            Semua
        </a>
        <a href="{{ route('approval.index', ['tab' => 'purchase', 'purchase_status' => 'pending']) }}"
           style="padding:7px 14px;border-radius:10px;border:1.5px solid {{ ($purchaseStatus ?? '') === 'pending' ? '#f59e0b' : '#e5e7eb' }};background:{{ ($purchaseStatus ?? '') === 'pending' ? '#fef3c7' : '#fff' }};color:{{ ($purchaseStatus ?? '') === 'pending' ? '#92400e' : '#6b7280' }};font-weight:600;font-size:.78rem;text-decoration:none;font-family:inherit;transition:all .15s;">
            ⏳ Menunggu Acc
        </a>
        <a href="{{ route('approval.index', ['tab' => 'purchase', 'purchase_status' => 'approved']) }}"
           style="padding:7px 14px;border-radius:10px;border:1.5px solid {{ ($purchaseStatus ?? '') === 'approved' ? '#3b82f6' : '#e5e7eb' }};background:{{ ($purchaseStatus ?? '') === 'approved' ? '#dbeafe' : '#fff' }};color:{{ ($purchaseStatus ?? '') === 'approved' ? '#1e40af' : '#6b7280' }};font-weight:600;font-size:.78rem;text-decoration:none;font-family:inherit;transition:all .15s;">
            📦 Perlu Verifikasi
        </a>
        <a href="{{ route('approval.index', ['tab' => 'purchase', 'purchase_status' => 'received']) }}"
           style="padding:7px 14px;border-radius:10px;border:1.5px solid {{ ($purchaseStatus ?? '') === 'received' ? '#10b981' : '#e5e7eb' }};background:{{ ($purchaseStatus ?? '') === 'received' ? '#d1fae5' : '#fff' }};color:{{ ($purchaseStatus ?? '') === 'received' ? '#065f46' : '#6b7280' }};font-weight:600;font-size:.78rem;text-decoration:none;font-family:inherit;transition:all .15s;">
            ✅ Diterima
        </a>
        <a href="{{ route('approval.index', ['tab' => 'purchase', 'purchase_status' => 'rejected']) }}"
           style="padding:7px 14px;border-radius:10px;border:1.5px solid {{ ($purchaseStatus ?? '') === 'rejected' ? '#ef4444' : '#e5e7eb' }};background:{{ ($purchaseStatus ?? '') === 'rejected' ? '#fee2e2' : '#fff' }};color:{{ ($purchaseStatus ?? '') === 'rejected' ? '#991b1b' : '#6b7280' }};font-weight:600;font-size:.78rem;text-decoration:none;font-family:inherit;transition:all .15s;">
            ❌ Ditolak
        </a>
    </div>

    <div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
        <div class="table-scroll">
            <table class="clay-table" style="min-width:1100px;">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pengaju</th>
                        <th>Produk / Varian</th>
                        <th>Gudang</th>
                        <th>Qty</th>
                        <th>Harga Satuan</th>
                        <th>Total</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($purchaseProposals as $pu)
                @php
                    $total = $pu->quantity * $pu->unit_price + $pu->shipping_cost;
                    $puStatus = [
                        'pending'  => ['class' => 'clay-badge-yellow', 'label' => '⏳ Menunggu Acc'],
                        'approved' => ['class' => 'clay-badge-blue',   'label' => '📦 Perlu Verifikasi'],
                        'rejected' => ['class' => 'clay-badge-red',    'label' => '❌ Ditolak'],
                        'received' => ['class' => 'clay-badge-green',  'label' => '✅ Diterima'],
                    ];
                    $ps = $puStatus[$pu->status] ?? ['class' => 'clay-badge-gray', 'label' => $pu->status];
                @endphp
                <tr style="{{ $pu->status === 'pending' ? 'background:#fffbeb;' : ($pu->status === 'approved' ? 'background:#eff6ff;' : ($pu->status === 'rejected' ? 'opacity:.7;' : '')) }}">
                    <td class="sel-nowrap">{{ $pu->date->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <img src="{{ $pu->creator?->avatar_url ?? '' }}" style="width:24px;height:24px;border-radius:6px;object-fit:cover;">
                            <span style="font-size:.82rem;font-weight:600;">{{ $pu->creator?->display_name ?? '-' }}</span>
                        </div>
                    </td>
                    <td style="font-weight:600;">
                        {{ $pu->variant?->product?->name ?? '-' }}
                        <div style="font-size:.72rem;color:#9ca3af;">{{ $pu->variant?->name }} {{ (float)($pu->variant?->power ?? 0) > 0 ? '(+'.number_format($pu->variant->power,2,',','.').')' : '' }}</div>
                    </td>
                    <td>
                        @if($pu->inventory)
                            <span class="clay-badge clay-badge-blue">🏭 {{ $pu->inventory->name }}</span>
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>
                    <td>{{ number_format($pu->quantity,0,',','.') }}</td>
                    <td>Rp {{ number_format((float)$pu->unit_price,0,',','.') }}</td>
                    <td style="font-weight:700;">Rp {{ number_format($total,0,',','.') }}</td>
                    <td style="text-align:center;">
                        <span class="clay-badge {{ $ps['class'] }}" style="font-size:.7rem;">{{ $ps['label'] }}</span>
                        @if($pu->rejection_note)
                        <div style="font-size:.68rem;color:#991b1b;margin-top:3px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ e($pu->rejection_note) }}">
                            ❌ {{ Str::limit($pu->rejection_note, 40) }}
                        </div>
                        @endif
                        @if($pu->status === 'received' && $pu->receive_note)
                        <div style="font-size:.68rem;color:#065f46;margin-top:3px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ e($pu->receive_note) }}">
                            📝 {{ Str::limit($pu->receive_note, 40) }}
                        </div>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        {{-- PENDING: Tombol Acc / Tolak --}}
                        @if($pu->status === 'pending' && $isAdmin)
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <button type="button" class="clay-btn clay-btn-primary pm-approve-btn" style="padding:5px 10px;font-size:.72rem;opacity:.5;"
                                        onclick="approvePurchase({{ $pu->id }}, 'Rp {{ number_format($total,0,',','.') }}')">
                                    ✅ Acc
                                </button>
                                <button type="button" class="clay-btn clay-btn-danger" style="padding:5px 10px;font-size:.72rem;"
                                        onclick="rejectPurchase({{ $pu->id }}, 'Rp {{ number_format($total,0,',','.') }}')">
                                    ❌ Tolak
                                </button>
                            </div>

                        {{-- APPROVED: Tombol Verifikasi Barang Datang --}}
                        @elseif($pu->status === 'approved' && $canVerify)
                            <button type="button" class="clay-btn clay-btn-primary" style="padding:5px 10px;font-size:.72rem;background:#2563eb;"
                                    onclick="openVerifyModal({{ $pu->id }}, '{{ e($pu->variant?->product?->name ?? '-') }} {{ e($pu->variant?->name ?? '-') }}', {{ $pu->quantity }}, {{ (float)$pu->unit_price }}, 'Rp {{ number_format($total,0,',','.') }}')">
                                📦 Verifikasi Barang Datang
                            </button>

                        {{-- RECEIVED: Info --}}
                        @elseif($pu->status === 'received')
                            <div style="font-size:.72rem;color:#065f46;font-weight:600;">
                                {{ $pu->receiver?->display_name ?? '-' }}
                                <div style="font-size:.65rem;color:#9ca3af;">{{ $pu->received_at?->format('d/m/Y H:i') ?? '' }}</div>
                            </div>

                        @elseif($pu->status === 'approved')
                            <span style="font-size:.72rem;color:#3b82f6;font-weight:600;">Menunggu verifikasi</span>

                        @elseif($pu->status === 'rejected')
                            <span style="font-size:.72rem;color:#9ca3af;">{{ $pu->approver?->display_name ?? '-' }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:48px 16px;">
                        <div style="font-size:2.5rem;margin-bottom:8px;">📥</div>
                        @if(($purchaseStatus ?? '') === 'pending')
                            <p style="color:#9ca3af;">Tidak ada pengajuan yang menunggu acc.</p>
                            <p style="font-size:.78rem;color:#9ca3af;">Pengajuan baru muncul setelah admin mengajukan pembelian via halaman Barang Masuk.</p>
                        @elseif(($purchaseStatus ?? '') === 'approved')
                            <p style="color:#9ca3af;">Tidak ada pembelian yang perlu diverifikasi.</p>
                        @else
                            <p style="color:#9ca3af;">Belum ada pengajuan pembelian.</p>
                        @endif
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($purchaseProposals->hasPages())
        <div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">
            {{ $purchaseProposals->appends(['tab' => 'purchase', 'purchase_status' => $purchaseStatus])->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: Reject Purchase
     ═══════════════════════════════════════════════════════════════════════ --}}
<div id="rejectModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px);" onclick="closeRejectModal()"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #e5e7eb;">
            <h3 style="margin:0;font-size:1rem;font-weight:800;">❌ Tolak Pembelian</h3>
            <button onclick="closeRejectModal()" style="width:32px;height:32px;border-radius:50%;border:none;background:#f3f4f6;cursor:pointer;font-size:1.1rem;">✕</button>
        </div>
        <form id="rejectForm" method="POST" style="padding:20px 24px;">
            @csrf
            @method('PATCH')
            <div style="margin-bottom:12px;">
                <div style="font-size:.78rem;color:#6b7280;margin-bottom:4px;">Pengajuan:</div>
                <div id="rejectDetail" style="font-weight:700;font-size:.9rem;"></div>
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151;">Alasan Penolakan *</label>
                <textarea name="rejection_note" required rows="3" maxlength="500" class="clay-input" style="width:100%;box-sizing:border-box;resize:vertical;" placeholder="Contoh: Stok sudah cukup, harga tidak sesuai, dll."></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeRejectModal()" class="clay-btn clay-btn-outline" style="padding:8px 14px;font-size:.82rem;">Batal</button>
                <button type="submit" class="clay-btn clay-btn-danger" style="padding:8px 14px;font-size:.82rem;">❌ Tolak Pengajuan</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: Verifikasi Barang Datang
     ═══════════════════════════════════════════════════════════════════════ --}}
<div id="verifyModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px);" onclick="closeVerifyModal()"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #e5e7eb;">
            <div>
                <h3 style="margin:0;font-size:1rem;font-weight:800;">📦 Verifikasi Barang Datang</h3>
                <div style="font-size:.75rem;color:#6b7280;margin-top:2px;">Isi data barang aktual yang diterima</div>
            </div>
            <button onclick="closeVerifyModal()" style="width:32px;height:32px;border-radius:50%;border:none;background:#f3f4f6;cursor:pointer;font-size:1.1rem;">✕</button>
        </div>
        <form id="verifyForm" method="POST" style="padding:20px 24px;">
            @csrf
            @method('PATCH')
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
                <div style="font-size:.72rem;color:#0369a1;font-weight:600;margin-bottom:4px;">📋 Data Pengajuan</div>
                <div id="verifyProductName" style="font-weight:700;font-size:.9rem;color:#1e1b2e;"></div>
                <div style="font-size:.78rem;color:#6b7280;margin-top:2px;">Total pengajuan: <span id="verifyOriginalTotal" style="font-weight:700;color:var(--color-primary);"></span></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151;">Qty Aktual (diterima) *</label>
                    <input type="number" name="actual_quantity" id="verifyQty" required min="1" class="clay-input" placeholder="Jumlah barang yang benar-benar datang">
                </div>
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151;">Harga Satuan Aktual</label>
                    <input type="number" name="actual_unit_price" id="verifyPrice" min="0" step="0.01" class="clay-input" placeholder="Kosongkan jika sama dengan pengajuan">
                    <div style="font-size:.65rem;color:#9ca3af;margin-top:2px;">Kosongkan = pakai harga pengajuan</div>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151;">Catatan Penerimaan</label>
                <textarea name="receive_note" rows="3" maxlength="500" class="clay-input" style="width:100%;box-sizing:border-box;resize:vertical;" placeholder="Contoh: Barang lengkap, kemasan baik, dll."></textarea>
            </div>

            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;margin-bottom:16px;">
                <div style="font-size:.72rem;color:#92400e;line-height:1.5;">
                    ⚠️ <strong>Setelah diverifikasi:</strong> Stok & HPP akan diperbarui otomatis sesuai qty aktual yang diisi.
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeVerifyModal()" class="clay-btn clay-btn-outline" style="padding:8px 14px;font-size:.82rem;">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary" style="padding:8px 16px;font-size:.82rem;background:#2563eb;">📦 Konfirmasi Barang Diterima</button>
            </div>
        </form>
    </div>
</div>

<style>
.approval-tab { transition: all .2s; }
.approval-tab:not(.active):hover { border-color: #d1d5db; background: #f3f4f6; }
</style>

<script>
(function() {
    var currentTab = '{{ $defaultTab }}';

    window.showTab = function(tab) {
        currentTab = tab;
        document.querySelectorAll('.approval-panel').forEach(function(p) { p.style.display = 'none'; });
        var panel = document.getElementById('tab-' + tab);
        if (panel) panel.style.display = 'block';

        ['topup', 'purchase'].forEach(function(t) {
            var btn = document.getElementById('tab-btn-' + t);
            if (t === tab) {
                btn.style.background = 'var(--color-primary)';
                btn.style.color = '#fff';
                btn.style.borderColor = 'var(--color-primary)';
                btn.style.fontWeight = '700';
                btn.classList.add('active');
            } else {
                btn.style.background = '#f9fafb';
                btn.style.color = '#6b7280';
                btn.style.borderColor = '#e5e7eb';
                btn.style.fontWeight = '600';
                btn.classList.remove('active');
            }
        });
    };

    // ─── Source Account Guard ───
    window.updateApproveButtons = function() {
        var acc = document.getElementById('sourceAccount').value;
        var warn = document.getElementById('sourceAccountWarning');
        var btns = document.querySelectorAll('.pm-approve-btn');
        warn.style.display = acc ? 'none' : 'block';
        btns.forEach(function(btn) { btn.disabled = !acc; btn.style.opacity = acc ? '1' : '.5'; });
    };

    // ─── Approve Purchase ───
    window.approvePurchase = function(id, amount) {
        var accId = document.getElementById('sourceAccount').value;
        if (!accId) { alert('⚠️ Pilih sumber dana terlebih dahulu!'); return; }
        if (!confirm('✅ Setujui pembelian ' + amount + '?\n\nSaldo akan dikurangi dari akun yang dipilih.\nStok belum masuk — menunggu verifikasi barang.')) return;
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/approval/purchase/' + id + '/approve';
        form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
            + '<input type="hidden" name="_method" value="PATCH">'
            + '<input type="hidden" name="source_account_id" value="' + accId + '">';
        document.body.appendChild(form);
        form.submit();
    };

    // Persist source account to localStorage
    document.getElementById('sourceAccount').addEventListener('change', function() {
        localStorage.setItem('approval_source_account', this.value);
    });

    // Init on load — restore from localStorage
    document.addEventListener('DOMContentLoaded', function() {
        var saved = localStorage.getItem('approval_source_account');
        if (saved) {
            var sel = document.getElementById('sourceAccount');
            if (sel.querySelector('option[value="' + saved + '"]')) {
                sel.value = saved;
            }
        }
        updateApproveButtons();
    });

    // ─── Reject Purchase ───
    window.rejectPurchase = function(id, amount) {
        var modal = document.getElementById('rejectModal');
        document.getElementById('rejectDetail').textContent = 'Pembelian ' + amount;
        var form = document.getElementById('rejectForm');
        form.action = '/approval/purchase/' + id + '/reject';
        form.querySelector('[name=rejection_note]').value = '';
        modal.style.display = 'flex';
    };

    window.closeRejectModal = function() {
        document.getElementById('rejectModal').style.display = 'none';
    };

    // ─── Verify Arrival ───
    window.openVerifyModal = function(id, productName, qty, price, total) {
        var modal = document.getElementById('verifyModal');
        document.getElementById('verifyProductName').textContent = productName;
        document.getElementById('verifyOriginalTotal').textContent = total;
        document.getElementById('verifyQty').value = qty;
        document.getElementById('verifyQty').max = qty * 2; // allow slight over
        document.getElementById('verifyPrice').value = '';
        var form = document.getElementById('verifyForm');
        form.action = '/approval/purchase/' + id + '/verify';
        form.querySelector('[name=receive_note]').value = '';
        modal.style.display = 'flex';
    };

    window.closeVerifyModal = function() {
        document.getElementById('verifyModal').style.display = 'none';
    };

    // Escape key menutup modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRejectModal();
            closeVerifyModal();
        }
    });
})();
</script>

@endsection
