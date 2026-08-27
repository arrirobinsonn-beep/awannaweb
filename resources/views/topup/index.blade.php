@extends('layouts.app')
@section('title','Top Up')
@section('page-title','💰 Top Up')
@section('page-subtitle', auth()->user()->hasRole(['owner','super_admin','admin']) ? 'Pengajuan top up dari semua advertiser' : 'Riwayat pengajuan top up Anda')

@section('content')

@php
    $u = auth()->user();
    $isAdmin = $u->hasRole(['owner','super_admin','admin','keuangan']);
    $isAdvertiser = $u->hasRole('advertiser');
@endphp

{{-- ═══ HEADER: Tombol Ajukan + Refresh ═══ --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;" data-reveal>
    @if($isAdvertiser)
    <button type="button" class="clay-btn clay-btn-primary" onclick="openTopUpModal()">＋ Ajukan Top Up</button>
    @else
    <div></div>
    @endif
    <button onclick="window.location.reload()" class="clay-btn clay-btn-outline" style="padding:6px 14px;font-size:.78rem;">🔄 Refresh</button>
</div>

{{-- ═══ TAB PER ADVERTISER (admin only) ═══ --}}
@if($isAdmin && isset($advertisers) && $advertisers->count())
<div style="display:flex;flex-wrap:wrap;gap:0;align-items:flex-end;margin-bottom:-2px;position:relative;z-index:2;" data-reveal>
    <a href="{{ route('topup.index', ['tab' => 'all']) }}"
       style="padding:9px 18px 11px;text-decoration:none;border:2px solid {{ ($activeTab ?? 'all') === 'all' ? 'rgba(255,107,107,.25)' : 'rgba(0,0,0,.08)' }};border-bottom:2px solid {{ ($activeTab ?? 'all') === 'all' ? '#fff' : 'rgba(0,0,0,.08)' }};border-radius:14px 14px 0 0;background:{{ ($activeTab ?? 'all') === 'all' ? '#fff' : '#f5f5f5' }};font-family:inherit;font-size:.82rem;font-weight:{{ ($activeTab ?? 'all') === 'all' ? '700' : '500' }};color:{{ ($activeTab ?? 'all') === 'all' ? 'var(--color-primary,#FF6B6B)' : '#6b7280' }};cursor:pointer;transition:all .2s;margin-right:4px;position:relative;z-index:{{ ($activeTab ?? 'all') === 'all' ? 3 : 1 }};">
        📋 Semua
        <span style="font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;background:{{ ($activeTab ?? 'all') === 'all' ? 'rgba(255,107,107,.12)' : 'rgba(0,0,0,.06)' }};color:{{ ($activeTab ?? 'all') === 'all' ? 'var(--color-primary)' : '#9ca3af' }};">{{ $proposals->total() }}</span>
    </a>
    @foreach($advertisers as $adv)
    @php $isActive = ($activeTab == $adv->id); @endphp
    <a href="{{ route('topup.index', ['tab' => $adv->id]) }}"
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

    {{-- ═══ TABEL PROPOSAL ═══ --}}
    <div class="table-scroll">
        <table class="clay-table">
            <thead>
                <tr>
                    @if(!$isAdvertiser)
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
                    'pending'              => ['class'=>'clay-badge-yellow', 'label'=>'Menunggu'],
                    'approved'             => ['class'=>'clay-badge-green',  'label'=>'Disetujui'],
                    'revision_requested'   => ['class'=>'clay-badge-red',    'label'=>'Perlu Revisi'],
                    'payment_in_progress'  => ['class'=>'clay-badge-blue',   'label'=>'⏳ Menunggu VA'],
                    'declined'             => ['class'=>'clay-badge-red',    'label'=>'Ditolak'],
                    'completed'            => ['class'=>'clay-badge-green',  'label'=>'✅ Selesai'],
                ];
                $sm = $statusMap[$p->status] ?? ['class'=>'clay-badge-gray','label'=>$p->status];
                $totalItems = $p->items->count();
                $paidItems  = $p->items->where('payment_status','paid')->count();
            @endphp
            <tr>
                @if(!$isAdvertiser)
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
                        @if($isAdmin && $p->isPending())
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
                        @if($isAdvertiser)
                        <button type="button" class="clay-btn clay-btn-primary" style="margin-top:12px;" onclick="openTopUpModal()">＋ Ajukan Sekarang</button>
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

@if($isAdmin && isset($advertisers) && $advertisers->count())
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL PENGAJUAN TOP UP — Advertiser Only
     ═══════════════════════════════════════════════════════════════════════ --}}
@if($isAdvertiser)
<div id="topupModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px);" onclick="closeTopUpModal()"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:600px;max-height:90vh;box-shadow:0 20px 60px rgba(0,0,0,.25);display:flex;flex-direction:column;overflow:hidden;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #e5e7eb;flex-shrink:0;">
            <h2 id="tuModalTitle" style="margin:0;font-size:1.05rem;font-weight:800;color:#1e1b2e;">💰 Pilih Whitelist</h2>
            <button onclick="closeTopUpModal()" style="width:32px;height:32px;border-radius:50%;border:none;background:#f3f4f6;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">✕</button>
        </div>

        {{-- Step Indicator --}}
        <div style="display:flex;align-items:center;justify-content:center;gap:6px;padding:14px 24px;background:#f9fafb;border-bottom:1px solid #e5e7eb;flex-shrink:0;">
            <div class="tu-step-indicator active" data-step="1"><span class="tu-step-num">1</span> Pilih WL</div>
            <div style="color:#d1d5db;font-size:1.1rem;">›</div>
            <div class="tu-step-indicator" data-step="2"><span class="tu-step-num">2</span> Sisa Saldo</div>
            <div style="color:#d1d5db;font-size:1.1rem;">›</div>
            <div class="tu-step-indicator" data-step="3"><span class="tu-step-num">3</span> Performa</div>
            <div style="color:#d1d5db;font-size:1.1rem;">›</div>
            <div class="tu-step-indicator" data-step="4"><span class="tu-step-num">4</span> Nominal</div>
        </div>

        <form method="POST" action="{{ route('topup.store') }}" id="tuForm">
            @csrf

            {{-- ═══ STEP 1: Pilih Whitelist ═══ --}}
            <div class="tu-step-content active" data-step="1">
                <p style="font-size:.78rem;color:#6b7280;margin:16px 24px 10px;">Centang whitelist yang akan di-top up:</p>
                <div style="padding:0 24px 16px;display:flex;flex-direction:column;gap:8px;overflow-y:auto;max-height:40vh;">
                    @forelse($whitelists as $wl)
                    <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;border:1.5px solid #e5e7eb;cursor:pointer;transition:all .15s;" class="tu-wl-item" onmouseover="this.style.borderColor='#d1d5db';this.style.background='#f9fafb'" onmouseout="this.style.borderColor='#e5e7eb';this.style.background=''">
                        <input type="checkbox" class="tu-wl-check" value="{{ $wl->id }}" data-nama="{{ e($wl->nama) }}" data-kode="{{ e($wl->kode) }}" data-platform="{{ e(ucfirst($wl->platform)) }}" data-saldo="{{ $wl->sisa_saldo ?? 0 }}" onchange="toggleWlItem(this)">
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:.85rem;color:#1e1b2e;">{{ $wl->nama }}</div>
                            <div style="font-size:.68rem;color:#9ca3af;">{{ ucfirst($wl->platform) }} · {{ $wl->kode }}</div>
                        </div>
                    </label>
                    @empty
                    <div style="text-align:center;padding:32px 16px;color:#9ca3af;">
                        <p>Anda belum memiliki whitelist aktif.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- ═══ STEP 2: Sisa Saldo ═══ --}}
            <div class="tu-step-content" data-step="2" style="display:none;">
                <p style="font-size:.78rem;color:#6b7280;margin:16px 24px 10px;">Input sisa saldo saat ini untuk whitelist yang dipilih:</p>
                <div id="tuSaldoList" style="padding:0 24px 16px;display:flex;flex-direction:column;gap:10px;overflow-y:auto;max-height:40vh;"></div>
            </div>

            {{-- ═══ STEP 3: Data Performa ═══ --}}
            <div class="tu-step-content" data-step="3" style="display:none;">
                <p style="font-size:.78rem;color:#6b7280;margin:16px 24px 10px;">Isi data performa H-1 sebagai referensi approval:</p>
                <div style="padding:0 24px 16px;">
                    <div style="background:#FFF5F5;padding:12px;border-radius:12px;margin-bottom:10px;text-align:center;">
                        <div style="font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:4px;">Top Up Sebelumnya</div>
                        <div style="font-weight:800;font-size:.85rem;color:var(--color-primary);">Rp {{ number_format($previousTopupTotal, 0, ',', '.') }}</div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
                        <div style="padding:12px;border-radius:12px;background:#f9fafb;">
                            <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:4px;">Spending (Rp) *</label>
                            <input type="number" name="today_spending" style="width:100%;box-sizing:border-box;font-size:.83rem;padding:7px 10px;border:1px solid #d1d5db;border-radius:8px;" placeholder="0" min="0" step="1000" required oninput="calcCPA()">
                        </div>
                        <div style="padding:12px;border-radius:12px;background:#f9fafb;">
                            <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:4px;">Lead *</label>
                            <input type="number" name="today_lead" style="width:100%;box-sizing:border-box;font-size:.83rem;padding:7px 10px;border:1px solid #d1d5db;border-radius:8px;" placeholder="0" min="0" required oninput="calcCPA()">
                        </div>
                        <div style="padding:12px;border-radius:12px;background:#f9fafb;">
                            <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:4px;">Paid *</label>
                            <input type="number" name="today_paid" style="width:100%;box-sizing:border-box;font-size:.83rem;padding:7px 10px;border:1px solid #d1d5db;border-radius:8px;" placeholder="0" min="0" required oninput="calcCPA()">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;">
                        <div style="padding:10px 14px;border-radius:12px;text-align:center;background:linear-gradient(135deg,#FFF5F5,#fff);border:1.5px solid rgba(255,107,107,.15);">
                            <div style="font-size:.68rem;color:#6b7280;font-weight:600;">📊 CPA Lead</div>
                            <div id="cpaLead" style="font-weight:900;font-size:1.05rem;margin-top:2px;color:var(--color-primary);">Rp 0</div>
                        </div>
                        <div style="padding:10px 14px;border-radius:12px;text-align:center;background:linear-gradient(135deg,#F0FFFE,#fff);border:1.5px solid rgba(78,205,196,.2);">
                            <div style="font-size:.68rem;color:#6b7280;font-weight:600;">📊 CPA Paid</div>
                            <div id="cpaPaid" style="font-weight:900;font-size:1.05rem;margin-top:2px;color:var(--color-secondary);">Rp 0</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══ STEP 4: Nominal Top Up ═══ --}}
            <div class="tu-step-content" data-step="4" style="display:none;">
                <p style="font-size:.78rem;color:#6b7280;margin:16px 24px 10px;">Masukkan nominal top up untuk whitelist yang dipilih:</p>
                <div id="tuNominalList" style="padding:0 24px;display:flex;flex-direction:column;gap:10px;overflow-y:auto;max-height:35vh;"></div>
                <div id="tuTotalBar" style="display:none;justify-content:flex-end;align-items:center;gap:16px;margin:14px 24px;padding:14px 16px;border-radius:14px;background:#F0FFFE;border:1.5px solid rgba(78,205,196,.3);">
                    <span id="tuTotalLabel" style="font-size:.75rem;color:#0d9488;font-weight:600;">centang whitelist & isi nominal</span>
                    <span id="tuTotalDisplay" style="font-size:1.3rem;font-weight:900;color:var(--color-secondary);">Rp 0</span>
                </div>
            </div>

            {{-- Hidden Fields --}}
            <div id="tuHiddenFields"></div>

            {{-- Footer --}}
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid #e5e7eb;flex-shrink:0;">
                <button type="button" id="tuBtnBack" style="display:none;padding:8px 16px;border-radius:10px;border:1.5px solid #d1d5db;background:#fff;cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:600;color:#374151;" onclick="prevStep()">← Kembali</button>
                <button type="button" id="tuBtnNext" style="padding:8px 16px;border-radius:10px;border:none;background:var(--color-primary,#FF6B6B);color:#fff;cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:700;" onclick="nextStep()">Selanjutnya →</button>
                <button type="submit" id="tuBtnSubmit" style="display:none;padding:8px 16px;border-radius:10px;border:none;background:var(--color-primary,#FF6B6B);color:#fff;cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:700;">💰 Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>
@endif

<style>
.tu-step-indicator { display:flex;align-items:center;gap:6px;font-size:.75rem;font-weight:600;color:#9ca3af;transition:color .2s; }
.tu-step-indicator.active { color:var(--color-primary,#FF6B6B); }
.tu-step-indicator.done { color:#059669; }
.tu-step-num { width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;background:#e5e7eb;color:#9ca3af;transition:all .2s; }
.tu-step-indicator.active .tu-step-num { background:var(--color-primary,#FF6B6B);color:#fff; }
.tu-step-indicator.done .tu-step-num { background:#059669;color:#fff; }
@media (max-width: 640px) {
    .tu-step-indicator { font-size:.68rem; }
}
</style>

@if($isAdvertiser)
<script>
(function(){
    var currentStep = 1;
    var totalSteps = 4;
    var selectedWl = [];
    var modal = document.getElementById('topupModal');
    var form = document.getElementById('tuForm');

    // ── Global functions (onclick attributes need these) ──
    window.openTopUpModal = function() {
        currentStep = 1;
        selectedWl = [];
        // Uncheck all
        var checks = form.querySelectorAll('.tu-wl-check');
        for (var i = 0; i < checks.length; i++) {
            checks[i].checked = false;
            checks[i].removeAttribute('checked');
        }
        // Clear fields
        var sf = form.querySelector('[name=today_spending]');
        var lf = form.querySelector('[name=today_lead]');
        var pf = form.querySelector('[name=today_paid]');
        if (sf) sf.value = '';
        if (lf) lf.value = '';
        if (pf) pf.value = '';
        document.getElementById('tuSaldoList').innerHTML = '';
        document.getElementById('tuNominalList').innerHTML = '';
        document.getElementById('tuHiddenFields').innerHTML = '';
        document.getElementById('tuTotalBar').style.display = 'none';
        updateStepUI();
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeTopUpModal = function() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    window.toggleWlItem = function(cb) {
        cb.closest('label').style.borderColor = cb.checked ? 'rgba(255,107,107,.45)' : '#e5e7eb';
        cb.closest('label').style.background = cb.checked ? '#FFF8F8' : '';
    };

    window.calcCPA = function() {
        var sp = parseFloat(form.querySelector('[name=today_spending]').value) || 0;
        var ld = parseFloat(form.querySelector('[name=today_lead]').value) || 0;
        var pd = parseFloat(form.querySelector('[name=today_paid]').value) || 0;
        document.getElementById('cpaLead').textContent = 'Rp ' + (ld > 0 ? Math.round(sp / ld).toLocaleString('id-ID') : '0');
        document.getElementById('cpaPaid').textContent = 'Rp ' + (pd > 0 ? Math.round(sp / pd).toLocaleString('id-ID') : '0');
    };

    window.calcTotal = function() {
        var total = 0, count = 0;
        var inputs = document.querySelectorAll('.tu-nominal-input');
        for (var i = 0; i < inputs.length; i++) {
            var v = parseFloat(inputs[i].value) || 0;
            if (v > 0) count++;
            total += v;
        }
        document.getElementById('tuTotalDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
        document.getElementById('tuTotalLabel').textContent = count > 0 ? count + ' whitelist akan di-top up' : 'centang whitelist & isi nominal';
    };

    window.nextStep = function() {
        if (currentStep === 1) {
            selectedWl = [];
            var checks = form.querySelectorAll('.tu-wl-check:checked');
            if (checks.length === 0) { alert('Pilih minimal satu whitelist.'); return; }
            for (var i = 0; i < checks.length; i++) {
                selectedWl.push({
                    id: checks[i].value,
                    nama: checks[i].getAttribute('data-nama'),
                    kode: checks[i].getAttribute('data-kode'),
                    platform: checks[i].getAttribute('data-platform'),
                    saldo: parseFloat(checks[i].getAttribute('data-saldo')) || 0
                });
            }
            renderSaldoStep();
        } else if (currentStep === 2) {
            // copy saldo values to hidden
            var inputs = document.querySelectorAll('.tu-saldo-input');
            for (var i = 0; i < inputs.length; i++) {
                var hid = document.querySelector('.tu-hidden-saldo[data-wl-id="' + inputs[i].getAttribute('data-wl-id') + '"]');
                if (hid) hid.value = inputs[i].value || '0';
            }
        } else if (currentStep === 3) {
            var sp = form.querySelector('[name=today_spending]');
            var ld = form.querySelector('[name=today_lead]');
            var pd = form.querySelector('[name=today_paid]');
            if (!sp.value || !ld.value || !pd.value) {
                alert('Harap isi semua field performa (Spending, Lead, Paid).');
                return;
            }
            renderNominalStep();
        }
        if (currentStep < totalSteps) currentStep++;
        updateStepUI();
    };

    window.prevStep = function() {
        if (currentStep > 1) currentStep--;
        updateStepUI();
    };

    function updateStepUI() {
        // Step indicators
        var indicators = document.querySelectorAll('.tu-step-indicator');
        for (var i = 0; i < indicators.length; i++) {
            var s = parseInt(indicators[i].getAttribute('data-step'));
            indicators[i].classList.remove('active', 'done');
            if (s === currentStep) indicators[i].classList.add('active');
            else if (s < currentStep) indicators[i].classList.add('done');
        }
        // Step content
        var contents = document.querySelectorAll('.tu-step-content');
        for (var i = 0; i < contents.length; i++) {
            var s = parseInt(contents[i].getAttribute('data-step'));
            contents[i].style.display = (s === currentStep) ? 'block' : 'none';
        }
        // Buttons
        document.getElementById('tuBtnBack').style.display = currentStep === 1 ? 'none' : 'inline-flex';
        document.getElementById('tuBtnNext').style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
        document.getElementById('tuBtnSubmit').style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
        // Title
        var titles = {1:'💰 Pilih Whitelist', 2:'💳 Sisa Saldo', 3:'📊 Data Performa', 4:'💸 Nominal Top Up'};
        document.getElementById('tuModalTitle').textContent = titles[currentStep] || '💰 Ajukan Top Up';
    }

    function renderSaldoStep() {
        var html = '';
        for (var i = 0; i < selectedWl.length; i++) {
            var wl = selectedWl[i];
            html += '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-radius:12px;border:1.5px solid #e5e7eb;background:#f9fafb;">';
            html += '<div><strong style="font-size:.85rem;">' + esc(wl.nama) + '</strong><br><small style="font-size:.68rem;color:#9ca3af;">' + esc(wl.platform) + ' · ' + esc(wl.kode) + '</small></div>';
            html += '<div style="display:flex;align-items:center;gap:8px;"><span style="font-size:.75rem;color:#9ca3af;">Rp</span>';
            html += '<input type="number" class="tu-saldo-input clay-input" style="width:160px;text-align:right;font-weight:700;" min="0" step="1000" value="' + wl.saldo + '" data-wl-id="' + wl.id + '"></div>';
            html += '</div>';
        }
        document.getElementById('tuSaldoList').innerHTML = html;
    }

    function renderNominalStep() {
        var html = '';
        var hidden = '';
        for (var i = 0; i < selectedWl.length; i++) {
            var wl = selectedWl[i];
            html += '<div style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;background:#f9fafb;border:1px solid #e5e7eb;">';
            html += '<div style="flex:1;min-width:150px;"><strong style="font-size:.85rem;display:block;">' + esc(wl.nama) + '</strong><small style="font-size:.68rem;color:#9ca3af;">' + esc(wl.platform) + ' · ' + esc(wl.kode) + '</small></div>';
            html += '<input type="number" class="tu-nominal-input clay-input" style="width:160px;flex-shrink:0;font-size:.85rem;padding:7px 10px;" min="0" step="1000" placeholder="0" data-wl-id="' + wl.id + '" oninput="calcTotal()">';
            html += '</div>';
            hidden += '<input type="hidden" name="items[' + wl.id + '][whitelist_id]" value="' + wl.id + '">';
            hidden += '<input type="hidden" name="items[' + wl.id + '][sisa_saldo]" class="tu-hidden-saldo" data-wl-id="' + wl.id + '" value="0">';
            hidden += '<input type="hidden" name="items[' + wl.id + '][nominal]" class="tu-hidden-nominal" data-wl-id="' + wl.id + '">';
        }
        document.getElementById('tuNominalList').innerHTML = html;
        document.getElementById('tuHiddenFields').innerHTML = hidden;
        document.getElementById('tuTotalBar').style.display = 'flex';
        calcTotal();
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // ── Form submit: validate & copy values ──
    form.addEventListener('submit', function(e) {
        var empty = [];
        var inputs = document.querySelectorAll('.tu-nominal-input');
        for (var i = 0; i < inputs.length; i++) {
            var v = parseFloat(inputs[i].value) || 0;
            var hid = document.querySelector('.tu-hidden-nominal[data-wl-id="' + inputs[i].getAttribute('data-wl-id') + '"]');
            if (hid) hid.value = inputs[i].value || '0';
            if (v <= 0) {
                var label = inputs[i].closest('div').querySelector('strong');
                empty.push(label ? label.textContent : 'WL');
            }
        }
        if (empty.length > 0) {
            e.preventDefault();
            alert('Masih ada whitelist tanpa nominal:\n• ' + empty.join('\n• ') + '\n\nSilakan isi nominalnya.');
        }
    });

    // ── Close on Escape ──
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeTopUpModal();
        }
    });
})();
</script>
@endif

@endsection
