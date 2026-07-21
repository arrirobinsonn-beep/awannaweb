@extends('layouts.app')
@section('title','Ajukan Top Up')
@section('page-title','💰 Ajukan Top Up')
@section('page-subtitle','Rencanakan pengisian saldo untuk whitelist Anda')

@section('content')
<div style="max-width:720px;">
    <form method="POST" action="{{ route('topup.store') }}" id="topup-form">
        @csrf

        {{-- Info performa — diisi manual oleh advertiser (data H-1 yg valid) --}}
        <div class="clay-card" style="padding:18px;margin-bottom:16px;" data-reveal>
            <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:10px;">📊 Data Performa (Referensi Approval)</div>
            <div style="font-size:.75rem;color:#9ca3af;margin-bottom:12px;">
                Isi data performa H-1 secara manual — data akan diverifikasi oleh Super Admin.
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                <div class="clay-card-sm" style="padding:12px;text-align:center;background:#FFF5F5;">
                    <div style="font-size:.68rem;color:#6b7280;margin-bottom:4px;">Top Up Sebelumnya</div>
                    <div style="font-weight:800;font-size:.85rem;color:var(--color-primary);">
                        Rp {{ number_format($previousTopupTotal,0,',','.') }}
                    </div>
                </div>
                <div class="clay-card-sm" style="padding:12px;background:#FFF5F5;">
                    <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:3px;">Spending (Rp) *</label>
                    <input type="number" name="today_spending" id="inp-spending"
                           value="{{ old('today_spending') }}" min="0" step="1000" required
                           class="clay-input" style="font-size:.83rem;padding:7px 10px;"
                           placeholder="0" oninput="hitungCPA()">
                </div>
                <div class="clay-card-sm" style="padding:12px;background:#F5F0FF;">
                    <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:3px;">Lead *</label>
                    <input type="number" name="today_lead" id="inp-lead"
                           value="{{ old('today_lead') }}" min="0" required
                           class="clay-input" style="font-size:.83rem;padding:7px 10px;"
                           placeholder="0" oninput="hitungCPA()">
                </div>
                <div class="clay-card-sm" style="padding:12px;background:#F0FFFE;">
                    <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:3px;">Paid *</label>
                    <input type="number" name="today_paid" id="inp-paid"
                           value="{{ old('today_paid') }}" min="0" required
                           class="clay-input" style="font-size:.83rem;padding:7px 10px;"
                           placeholder="0" oninput="hitungCPA()">
                </div>
            </div>

            {{-- CPA auto-calc --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
                <div class="clay-card-sm" style="padding:10px 14px;text-align:center;background:linear-gradient(135deg,#FFF5F5,#fff);border:1.5px solid rgba(255,107,107,.15);">
                    <div style="font-size:.68rem;color:#6b7280;font-weight:600;">📊 CPA Lead</div>
                    <div id="cpa-lead-display" style="font-weight:900;font-size:1.05rem;color:var(--color-primary);margin-top:2px;">Rp 0</div>
                </div>
                <div class="clay-card-sm" style="padding:10px 14px;text-align:center;background:linear-gradient(135deg,#F0FFFE,#fff);border:1.5px solid rgba(78,205,196,.2);">
                    <div style="font-size:.68rem;color:#6b7280;font-weight:600;">📊 CPA Paid</div>
                    <div id="cpa-paid-display" style="font-weight:900;font-size:1.05rem;color:var(--color-secondary);margin-top:2px;">Rp 0</div>
                </div>
            </div>
        </div>

        {{-- Rencana Top Up --}}
        <div class="clay-card" style="padding:24px;" data-reveal>
            <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:14px;">
                📋 Rencana Top Up per Whitelist
            </div>

            @if($errors->any())
            <div class="clay-alert clay-alert-error" style="margin-bottom:16px;">
                <span>⚠️</span>
                <div style="flex:1;font-size:.83rem;">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            </div>
            @endif

            <div id="items-container" style="display:flex;flex-direction:column;gap:12px;">
                @foreach($whitelists as $wl)
                <div class="clay-card-sm item-row" style="padding:14px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:140px;">
                        <div style="font-weight:700;font-size:.85rem;">{{ $wl->nama }}</div>
                        <div style="font-size:.7rem;color:#9ca3af;">
                            {{ ucfirst($wl->platform) }} · {{ $wl->kode }}
                            @if($wl->sisa_saldo > 0)
                            · Sisa: Rp {{ number_format($wl->sisa_saldo,0,',','.') }}
                            @endif
                        </div>
                    </div>
                    <div style="min-width:180px;flex-shrink:0;">
                        <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:3px;">
                            Rencana Top Up (Rp)
                        </label>
                        <input type="number" name="items[{{ $wl->id }}][whitelist_id]" value="{{ $wl->id }}" hidden>
                        <input type="number" name="items[{{ $wl->id }}][nominal]" min="0" step="1000"
                               class="clay-input nominal-input"
                               style="font-size:.85rem;padding:8px 12px;"
                               placeholder="0"
                               data-id="{{ $wl->id }}"
                               oninput="hitungTotal()">
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Total --}}
            <div style="display:flex;justify-content:flex-end;align-items:center;gap:16px;
                        margin-top:18px;padding:16px 18px;border-radius:14px;
                        background:#F0FFFE;border:1.5px solid rgba(78,205,196,.3);">
                <div>
                    <div style="font-size:.75rem;color:#0d9488;font-weight:600;">Total Rencana Top Up</div>
                    <div id="total-label" style="font-size:.72rem;color:#9ca3af;">isi nominal terlebih dahulu</div>
                </div>
                <div style="font-size:1.4rem;font-weight:900;color:var(--color-secondary);" id="total-display">Rp 0</div>
            </div>

            {{-- Submit --}}
            <div style="display:flex;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary" style="flex:1;justify-content:center;">
                    💰 Kirim Pengajuan
                </button>
                <a href="{{ route('topup.index') }}" class="clay-btn clay-btn-outline" data-page-link>Batal</a>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function hitungTotal() {
    var inputs = document.querySelectorAll('.nominal-input');
    var total = 0;
    var count = 0;
    inputs.forEach(function(inp) {
        var v = parseFloat(inp.value) || 0;
        if (v > 0) count++;
        total += v;
    });
    document.getElementById('total-display').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('total-label').textContent = count + ' whitelist akan di-top up';
}

function hitungCPA() {
    var spending = parseFloat(document.getElementById('inp-spending').value) || 0;
    var lead     = parseFloat(document.getElementById('inp-lead').value) || 0;
    var paid     = parseFloat(document.getElementById('inp-paid').value) || 0;

    var cpaLead = lead > 0 ? spending / lead : 0;
    var cpaPaid = paid > 0 ? spending / paid : 0;

    document.getElementById('cpa-lead-display').textContent = 'Rp ' + Math.round(cpaLead).toLocaleString('id-ID');
    document.getElementById('cpa-paid-display').textContent = 'Rp ' + Math.round(cpaPaid).toLocaleString('id-ID');
}

hitungTotal();
hitungCPA();
</script>
@endpush
@endsection
