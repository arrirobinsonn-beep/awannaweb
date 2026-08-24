@extends('layouts.app')
@section('title','Top Up')
@section('page-title','💰 Top Up')
@section('page-subtitle', auth()->user()->hasRole(['owner','super_admin','admin']) ? 'Pengajuan top up dari semua advertiser' : 'Riwayat pengajuan top up Anda')

@section('content')

{{-- Tombol Ajukan (hanya advertiser) + Refresh --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;" data-reveal>
    @if(auth()->user()->hasRole('advertiser'))
    <button type="button" class="clay-btn clay-btn-primary" id="btn-open-modal-topup">＋ Ajukan Top Up</button>
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
                        <button type="button" class="clay-btn clay-btn-primary" style="margin-top:12px;" onclick="document.getElementById('btn-open-modal-topup').click()">＋ Ajukan Sekarang</button>
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

{{-- ═══════════════ MODAL PENGAJUAN TOP UP (3-STEP) ═══════════════ --}}
@if($u->hasRole('advertiser'))
<div class="tu-modal" id="tu-modal">
    <div class="tu-modal-backdrop" id="tu-modal-backdrop"></div>
    <div class="tu-modal-container">
        {{-- Header --}}
        <div class="tu-modal-header">
            <h2 id="tu-modal-title">💰 Ajukan Top Up</h2>
            <button class="tu-modal-close" id="tu-modal-close" type="button">✕</button>
        </div>

        {{-- Step indicator --}}
        <div class="tu-steps">
            <div class="tu-step active" data-step="1"><span class="tu-step-num">1</span> Pilih Whitelist</div>
            <div class="tu-step-arrow">›</div>
            <div class="tu-step" data-step="2"><span class="tu-step-num">2</span> Sisa Saldo</div>
            <div class="tu-step-arrow">›</div>
            <div class="tu-step" data-step="3"><span class="tu-step-num">3</span> Data Performa</div>
            <div class="tu-step-arrow">›</div>
            <div class="tu-step" data-step="4"><span class="tu-step-num">4</span> Nominal Top Up</div>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('topup.store') }}" id="tu-form" novalidate>
            @csrf

            {{-- ═══ STEP 1: Pilih Whitelist ═══ --}}
            <div class="tu-step-content active" data-step="1">
                <div class="tu-step-desc">Centang whitelist yang akan di-top up:</div>
                <div class="tu-wl-list">
                    @foreach($whitelists as $wl)
                    <label class="tu-wl-item" data-id="{{ $wl->id }}">
                        <input type="checkbox" class="tu-wl-check" value="{{ $wl->id }}">
                        <div class="tu-wl-info">
                            <div class="tu-wl-name">{{ $wl->nama }}</div>
                            <div class="tu-wl-meta">{{ ucfirst($wl->platform) }} · {{ $wl->kode }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- ═══ STEP 2: Sisa Saldo ═══ --}}
            <div class="tu-step-content" data-step="2">
                <div class="tu-step-desc">Input sisa saldo saat ini untuk whitelist yang dipilih:</div>
                <div class="tu-saldo-list" id="tu-saldo-list"></div>
            </div>

            {{-- ═══ STEP 3: Data Performa ═══ --}}
            <div class="tu-step-content" data-step="3">
                <div class="tu-step-desc">Isi data performa H-1 sebagai referensi approval:</div>
                <div class="tu-perf-grid">
                    <div class="tu-perf-card" style="background:#FFF5F5;">
                        <div class="tu-perf-label">Top Up Sebelumnya</div>
                        <div class="tu-perf-val" style="color:var(--color-primary);">Rp {{ number_format($previousTopupTotal, 0, ',', '.') }}</div>
                    </div>
                    <div class="tu-perf-card">
                        <label class="tu-perf-label">Spending (Rp) *</label>
                        <input type="number" name="today_spending" class="clay-input tu-perf-input" placeholder="0" min="0" step="1000" required oninput="tuHitungCPA()">
                    </div>
                    <div class="tu-perf-card">
                        <label class="tu-perf-label">Lead *</label>
                        <input type="number" name="today_lead" class="clay-input tu-perf-input" placeholder="0" min="0" required oninput="tuHitungCPA()">
                    </div>
                    <div class="tu-perf-card">
                        <label class="tu-perf-label">Paid *</label>
                        <input type="number" name="today_paid" class="clay-input tu-perf-input" placeholder="0" min="0" required oninput="tuHitungCPA()">
                    </div>
                </div>
                <div class="tu-cpa-row">
                    <div class="tu-cpa-card" style="background:linear-gradient(135deg,#FFF5F5,#fff);border:1.5px solid rgba(255,107,107,.15);">
                        <div class="tu-cpa-label">📊 CPA Lead</div>
                        <div class="tu-cpa-val" id="tu-cpa-lead">Rp 0</div>
                    </div>
                    <div class="tu-cpa-card" style="background:linear-gradient(135deg,#F0FFFE,#fff);border:1.5px solid rgba(78,205,196,.2);">
                        <div class="tu-cpa-label">📊 CPA Paid</div>
                        <div class="tu-cpa-val" id="tu-cpa-paid">Rp 0</div>
                    </div>
                </div>
            </div>

            {{-- ═══ STEP 4: Nominal Top Up ═══ --}}
            <div class="tu-step-content" data-step="4">
                <div class="tu-step-desc">Masukkan nominal top up untuk whitelist yang dipilih:</div>
                <div class="tu-nominal-list" id="tu-nominal-list"></div>
                <div class="tu-total-bar">
                    <div>
                        <div class="tu-total-label" id="tu-total-label">centang whitelist & isi nominal</div>
                    </div>
                    <div class="tu-total-val" id="tu-total-display">Rp 0</div>
                </div>
            </div>

            {{-- Hidden: whitelist_id yang dipilih --}}
            <div id="tu-hidden-fields"></div>

            {{-- Footer --}}
            <div class="tu-modal-footer">
                <button type="button" class="clay-btn clay-btn-outline" id="tu-btn-back">← Kembali</button>
                <button type="button" class="clay-btn clay-btn-primary" id="tu-btn-next">Selanjutnya →</button>
                <button type="submit" class="clay-btn clay-btn-primary" id="tu-btn-submit" style="display:none;">💰 Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('styles')
<style>
/* ── Modal Top Up 3-Step ─────────────────── */
.tu-modal {
    position: fixed; inset: 0; z-index: 9999;
    display: none; align-items: center; justify-content: center; padding: 16px;
}
.tu-modal.active { display: flex; }
.tu-modal-backdrop {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.5); backdrop-filter: blur(2px);
}
.tu-modal-container {
    position: relative; background: #fff; border-radius: 20px;
    width: 100%; max-width: 600px; max-height: 90vh;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    animation: tuModalIn .25s ease;
    display: flex; flex-direction: column; overflow: hidden;
}
@keyframes tuModalIn {
    from { opacity: 0; transform: scale(.96) translateY(12px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.tu-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 24px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;
}
.tu-modal-header h2 { margin: 0; font-size: 1.05rem; font-weight: 800; color: #1e1b2e; }
.tu-modal-close {
    width: 32px; height: 32px; border-radius: 50%; border: none;
    background: #f3f4f6; cursor: pointer; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center; transition: background .15s;
}
.tu-modal-close:hover { background: #e5e7eb; }

/* Steps indicator */
.tu-steps {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 14px 24px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
}
.tu-step {
    display: flex; align-items: center; gap: 6px;
    font-size: .75rem; font-weight: 600; color: #9ca3af;
    transition: color .2s;
}
.tu-step.active { color: var(--color-primary, #FF6B6B); }
.tu-step.done { color: #059669; }
.tu-step-num {
    width: 22px; height: 22px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .68rem; font-weight: 800;
    background: #e5e7eb; color: #9ca3af; transition: all .2s;
}
.tu-step.active .tu-step-num { background: var(--color-primary, #FF6B6B); color: #fff; }
.tu-step.done .tu-step-num { background: #059669; color: #fff; }
.tu-step-arrow { color: #d1d5db; font-size: 1.1rem; }

/* Step content */
.tu-step-content { display: none; padding: 20px 24px; overflow-y: auto; flex: 1; min-height: 0; }
.tu-step-content.active { display: block; }
.tu-step-desc { font-size: .78rem; color: #6b7280; margin-bottom: 14px; }

/* Whitelist items */
.tu-wl-list { display: flex; flex-direction: column; gap: 8px; }
.tu-wl-item {
    display: flex; align-items: center; gap: 12px; padding: 12px 14px;
    border-radius: 12px; border: 1.5px solid #e5e7eb;
    cursor: pointer; transition: all .15s;
}
.tu-wl-item:hover { border-color: #d1d5db; background: #f9fafb; }
.tu-wl-item.selected { border-color: rgba(255,107,107,.45); background: #FFF8F8; }
.tu-wl-check { width: 16px; height: 16px; accent-color: var(--color-primary, #FF6B6B); flex-shrink: 0; }
.tu-wl-info { flex: 1; min-width: 0; }
.tu-wl-name { font-weight: 700; font-size: .85rem; color: #1e1b2e; }
.tu-wl-meta { font-size: .68rem; color: #9ca3af; }

/* Sisa saldo list (Step 2) */
.tu-saldo-list { display: flex; flex-direction: column; gap: 10px; }
.tu-saldo-item {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 12px 14px; border-radius: 12px; border: 1.5px solid #e5e7eb;
    background: #f9fafb;
}
.tu-saldo-item .clay-input { width: 160px; text-align: right; font-weight: 700; }

/* Performance grid */
.tu-perf-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 12px; }
.tu-perf-card { padding: 12px; border-radius: 12px; background: #f9fafb; }
.tu-perf-label { font-size: .68rem; font-weight: 700; color: #6b7280; margin-bottom: 4px; }
.tu-perf-val { font-weight: 800; font-size: .85rem; }
.tu-perf-input { font-size: .83rem; padding: 7px 10px; width: 100%; box-sizing: border-box; }
.tu-cpa-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.tu-cpa-card { padding: 10px 14px; border-radius: 12px; text-align: center; }
.tu-cpa-label { font-size: .68rem; color: #6b7280; font-weight: 600; }
.tu-cpa-val { font-weight: 900; font-size: 1.05rem; margin-top: 2px; }

/* Nominal list */
.tu-nominal-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }
.tu-nominal-item {
    display: flex; align-items: center; gap: 12px; padding: 12px 14px;
    border-radius: 12px; background: #f9fafb; border: 1px solid #e5e7eb;
}
.tu-nominal-name { flex: 1; min-width: 150px; }
.tu-nominal-name strong { font-size: .85rem; display: block; }
.tu-nominal-name small { font-size: .68rem; color: #9ca3af; }
.tu-nominal-input { width: 160px; flex-shrink: 0; font-size: .85rem; padding: 7px 10px; }
.tu-total-bar {
    display: flex; justify-content: flex-end; align-items: center; gap: 16px;
    padding: 14px 16px; border-radius: 14px;
    background: #F0FFFE; border: 1.5px solid rgba(78,205,196,.3);
}
.tu-total-label { font-size: .75rem; color: #0d9488; font-weight: 600; }
.tu-total-val { font-size: 1.3rem; font-weight: 900; color: var(--color-secondary); }

/* Footer */
.tu-modal-footer {
    display: flex; align-items: center; justify-content: flex-end; gap: 10px;
    padding: 14px 24px; border-top: 1px solid #e5e7eb; flex-shrink: 0;
}
.tu-modal-footer .clay-btn-outline { order: -1; }

/* Responsive */
@media (max-width: 640px) {
    .tu-modal-container { max-width: 100%; border-radius: 16px; }
    .tu-perf-grid { grid-template-columns: 1fr 1fr; }
    .tu-steps { padding: 10px 16px; gap: 4px; }
    .tu-step { font-size: .68rem; }
    .tu-step-content { padding: 16px; }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';

    var modal = document.getElementById('tu-modal');
    if (!modal) return;

    var btnOpen = document.getElementById('btn-open-modal-topup');
    var btnClose = document.getElementById('tu-modal-close');
    var backdrop = document.getElementById('tu-modal-backdrop');
    var btnNext = document.getElementById('tu-btn-next');
    var btnBack = document.getElementById('tu-btn-back');
    var btnSubmit = document.getElementById('tu-btn-submit');
    var form = document.getElementById('tu-form');
    var title = document.getElementById('tu-modal-title');

    var currentStep = 1;
    var totalSteps = 4;
    var selectedWlIds = [];

    var wlData = {!! $wlDataJson !!};

    // ── Open / Close ──
    if (btnOpen) {
        btnOpen.addEventListener('click', function() {
            resetModal();
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    // ── Step navigation ──
    function goToStep(step) {
        currentStep = step;
        // Update step indicator
        document.querySelectorAll('.tu-step').forEach(function(el) {
            var s = parseInt(el.dataset.step);
            el.classList.remove('active', 'done');
            if (s === step) el.classList.add('active');
            else if (s < step) el.classList.add('done');
        });
        // Show/hide content
        document.querySelectorAll('.tu-step-content').forEach(function(el) {
            el.classList.toggle('active', parseInt(el.dataset.step) === step);
        });
        // Button visibility
        btnBack.style.display = step === 1 ? 'none' : 'inline-flex';
        btnNext.style.display = step === totalSteps ? 'none' : 'inline-flex';
        btnSubmit.style.display = step === totalSteps ? 'inline-flex' : 'none';
        // Title
        var titles = { 1: '💰 Pilih Whitelist', 2: '💳 Sisa Saldo', 3: '📊 Data Performa', 4: '💸 Nominal Top Up' };
        title.textContent = titles[step] || '💰 Ajukan Top Up';
    }

    btnNext.addEventListener('click', function() {
        if (currentStep === 1) {
            selectedWlIds = [];
            document.querySelectorAll('.tu-wl-check:checked').forEach(function(cb) {
                selectedWlIds.push(cb.value);
            });
            if (selectedWlIds.length === 0) {
                alert('Pilih minimal satu whitelist.');
                return;
            }
            renderSaldoInputs();
            goToStep(2);
        } else if (currentStep === 2) {
            copySaldoToHidden();
            goToStep(3);
        } else if (currentStep === 3) {
            var spending = form.querySelector('[name=today_spending]');
            var lead = form.querySelector('[name=today_lead]');
            var paid = form.querySelector('[name=today_paid]');
            if (!spending.value || !lead.value || !paid.value) {
                alert('Harap isi semua field performa (Spending, Lead, Paid).');
                return;
            }
            renderNominalInputs();
            goToStep(4);
        }
    });

    btnBack.addEventListener('click', function() {
        if (currentStep === 2) {
            goToStep(1);
        } else if (currentStep === 3) {
            goToStep(2);
        } else if (currentStep === 4) {
            goToStep(3);
        }
    });

    // ── Whitelist checkbox toggle ──
    document.querySelectorAll('.tu-wl-check').forEach(function(cb) {
        cb.addEventListener('change', function() {
            cb.closest('.tu-wl-item').classList.toggle('selected', cb.checked);
        });
    });

    // ── Render sisa saldo inputs for selected whitelists (Step 2) ──
    function renderSaldoInputs() {
        var list = document.getElementById('tu-saldo-list');
        list.innerHTML = '';

        selectedWlIds.forEach(function(id) {
            var wl = wlData.find(function(w) { return w.id == id; });
            if (!wl) return;

            var div = document.createElement('div');
            div.className = 'tu-saldo-item';
            div.innerHTML = '<div class="tu-nominal-name"><strong>' + escHtml(wl.nama) + '</strong><small>' + escHtml(wl.platform) + ' · ' + escHtml(wl.kode) + '</small></div>' +
                '<div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:.75rem;color:#9ca3af;">Rp</span>' +
                    '<input type="number" class="clay-input tu-saldo-input" min="0" step="1000" value="' + (wl.sisa_saldo || 0) + '" data-wl-id="' + wl.id + '" oninput="tuCopySaldoToHidden()">' +
                '</div>';
            list.appendChild(div);
        });

        copySaldoToHidden();
    }

    window.tuCopySaldoToHidden = function() {
        document.querySelectorAll('.tu-saldo-input').forEach(function(inp) {
            var hidden = document.querySelector('.tu-hidden-saldo[data-wl-id="' + inp.dataset.wlId + '"]');
            if (hidden) hidden.value = inp.value || '0';
        });
    };
    var copySaldoToHidden = window.tuCopySaldoToHidden;

    // ── Render nominal inputs for selected whitelists (Step 4) ──
    function renderNominalInputs() {
        var list = document.getElementById('tu-nominal-list');
        var hidden = document.getElementById('tu-hidden-fields');
        list.innerHTML = '';
        hidden.innerHTML = '';

        selectedWlIds.forEach(function(id) {
            var wl = wlData.find(function(w) { return w.id == id; });
            if (!wl) return;

            // Visible input
            var div = document.createElement('div');
            div.className = 'tu-nominal-item';
            div.innerHTML = '<div class="tu-nominal-name"><strong>' + escHtml(wl.nama) + '</strong><small>' + escHtml(wl.platform) + ' · ' + escHtml(wl.kode) + '</small></div>' +
                '<input type="number" class="clay-input tu-nominal-input" min="0" step="1000" placeholder="0" data-wl-id="' + wl.id + '" oninput="tuHitungTotal()">';
            list.appendChild(div);

            // Hidden fields
            hidden.innerHTML += '<input type="number" name="items[' + wl.id + '][whitelist_id]" value="' + wl.id + '" hidden>';
            hidden.innerHTML += '<input type="number" name="items[' + wl.id + '][sisa_saldo]" class="tu-hidden-saldo" data-wl-id="' + wl.id + '" hidden>';
            hidden.innerHTML += '<input type="number" name="items[' + wl.id + '][nominal]" class="tu-hidden-nominal" data-wl-id="' + wl.id + '" hidden>';
        });
    }

    // ── Submit: copy visible inputs to hidden ──
    form.addEventListener('submit', function(e) {
        var empty = [];
        document.querySelectorAll('.tu-nominal-input').forEach(function(inp) {
            var v = parseFloat(inp.value) || 0;
            var hidden = document.querySelector('.tu-hidden-nominal[data-wl-id="' + inp.dataset.wlId + '"]');
            if (hidden) hidden.value = inp.value || '0';
            if (v <= 0) empty.push(inp.closest('.tu-nominal-item').querySelector('strong').textContent);
        });
        if (empty.length > 0) {
            e.preventDefault();
            alert('Masih ada whitelist tanpa nominal:\n• ' + empty.join('\n• ') + '\n\nSilakan isi nominalnya.');
        }
    });

    // ── Helpers ──
    function resetModal() {
        goToStep(1);
        // Uncheck all
        document.querySelectorAll('.tu-wl-check').forEach(function(cb) {
            cb.checked = false;
            cb.closest('.tu-wl-item').classList.remove('selected');
        });
        // Clear saldo & performance
        var saldoList = document.getElementById('tu-saldo-list');
        if (saldoList) saldoList.innerHTML = '';
        form.querySelectorAll('[name=today_spending],[name=today_lead],[name=today_paid]').forEach(function(inp) { inp.value = ''; });
        tuHitungCPA();
    }

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    // ── Global functions for oninput ──
    window.tuHitungCPA = function() {
        var sp = parseFloat(form.querySelector('[name=today_spending]').value) || 0;
        var ld = parseFloat(form.querySelector('[name=today_lead]').value) || 0;
        var pd = parseFloat(form.querySelector('[name=today_paid]').value) || 0;
        document.getElementById('tu-cpa-lead').textContent = 'Rp ' + (ld > 0 ? Math.round(sp / ld).toLocaleString('id-ID') : '0');
        document.getElementById('tu-cpa-paid').textContent = 'Rp ' + (pd > 0 ? Math.round(sp / pd).toLocaleString('id-ID') : '0');
    };

    window.tuHitungTotal = function() {
        var total = 0, count = 0;
        document.querySelectorAll('.tu-nominal-input').forEach(function(inp) {
            var v = parseFloat(inp.value) || 0;
            if (v > 0) count++;
            total += v;
        });
        document.getElementById('tu-total-display').textContent = 'Rp ' + total.toLocaleString('id-ID');
        document.getElementById('tu-total-label').textContent = count > 0
            ? count + ' whitelist akan di-top up'
            : 'centang whitelist & isi nominal';
    };
})();
</script>
@endpush

@endsection
