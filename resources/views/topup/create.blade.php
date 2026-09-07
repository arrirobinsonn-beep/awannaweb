@extends('layouts.app')
@section('title','Ajukan Top Up')
@section('page-title','💰 Ajukan Top Up')
@section('page-subtitle','Rencanakan pengisian saldo untuk whitelist Anda')

@section('content')
{{-- Form selebar penuh halaman (sebelumnya dibatasi 720px) --}}
<div style="width:100%;">
    <form method="POST" action="{{ route('topup.store') }}" id="topup-form" novalidate>
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

        @if($errors->any())
        <div class="clay-alert clay-alert-error" style="margin-bottom:16px;">
            <span>⚠️</span>
            <div style="flex:1;font-size:.83rem;">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        </div>
        @endif

        {{-- Dua area berdampingan: Rencana Top Up + Input Sisa Saldo --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:stretch;" class="topup-split">

            {{-- Rencana Top Up per Whitelist (centang whitelist yang di-top up) --}}
            <div class="clay-card" style="padding:20px;height:100%;display:flex;flex-direction:column;" data-reveal>
                <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:4px;">
                    📋 Rencana Top Up per Whitelist
                </div>
                <div style="font-size:.72rem;color:#9ca3af;margin-bottom:14px;">
                    Centang whitelist yang akan di-top up, lalu isi nominalnya.
                </div>

                <div id="items-container" style="display:flex;flex-direction:column;gap:10px;flex:1;">
                    @foreach($whitelists as $wl)
                    <div class="clay-card-sm item-row" data-id="{{ $wl->id }}" style="padding:12px 14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;border:1.5px solid transparent;transition:border-color .15s ease, background .15s ease;">
                        <label class="wl-check" style="display:flex;align-items:center;gap:10px;flex:1;min-width:170px;cursor:pointer;">
                            <input type="checkbox" class="wl-select" data-id="{{ $wl->id }}" style="width:16px;height:16px;accent-color:var(--color-primary,#FF6B6B);cursor:pointer;flex-shrink:0;"
                                   @if(old('items.'.$wl->id.'.nominal')) checked @endif>
                            <span>
                                <span style="font-weight:700;font-size:.85rem;display:block;">{{ $wl->nama }}</span>
                                <span style="font-size:.68rem;color:#9ca3af;">
                                    {{ ucfirst($wl->platform) }} · {{ $wl->kode }}
                                    @if($wl->sisa_saldo > 0)
                                    · Sisa: Rp {{ number_format($wl->sisa_saldo,0,',','.') }}
                                    @endif
                                </span>
                            </span>
                        </label>
                        <div style="min-width:160px;flex-shrink:0;">
                            <label style="display:block;font-size:.66rem;font-weight:700;color:#6b7280;margin-bottom:3px;">
                                Rencana Top Up (Rp)
                            </label>
                            <input type="number" name="items[{{ $wl->id }}][whitelist_id]" value="{{ $wl->id }}" hidden>
                            <input type="number" name="items[{{ $wl->id }}][nominal]" min="0" step="1000"
                                   class="clay-input nominal-input"
                                   style="font-size:.85rem;padding:7px 10px;"
                                   placeholder="0"
                                   data-id="{{ $wl->id }}"
                                   value="{{ old('items.'.$wl->id.'.nominal') }}"
                                   @if(old('items.'.$wl->id.'.nominal'))
                                   {{-- Pulihkan centang & aktifkan input saat validasi gagal --}}
                                   data-was-filled="1"
                                   @endif
                                   required
                                   disabled
                                   oninput="hitungTotal()">
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Total --}}
                <div style="display:flex;justify-content:flex-end;align-items:center;gap:16px;
                            margin-top:16px;padding:14px 16px;border-radius:14px;
                            background:#F0FFFE;border:1.5px solid rgba(78,205,196,.3);">
                    <div>
                        <div style="font-size:.75rem;color:#0d9488;font-weight:600;">Total Rencana Top Up</div>
                        <div id="total-label" style="font-size:.7rem;color:#9ca3af;">centang whitelist & isi nominal</div>
                    </div>
                    <div style="font-size:1.3rem;font-weight:900;color:var(--color-secondary);" id="total-display">Rp 0</div>
                </div>
            </div>

            {{-- Input Sisa Saldo per Whitelist (otomatis: whitelist ber-spending kemarin) --}}
            <div class="clay-card" style="padding:20px;height:100%;display:flex;flex-direction:column;" data-reveal>
                <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:4px;">
                    💳 Input Sisa Saldo per Whitelist
                </div>
                <div style="font-size:.72rem;color:#9ca3af;margin-bottom:14px;">
                    Otomatis menampilkan whitelist yang melakukan spending <b>kemarin ({{ \Carbon\Carbon::yesterday()->format('d/m/Y') }})</b>. Input sisa saldo sebelum mengajukan top up.
                    <span style="display:block;margin-top:4px;color:#f59e0b;">ℹ️ Nilai sisa saldo akan disimpan saat tahap berikutnya.</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:10px;flex:1;">
                    @forelse($sisaSaldoWhitelists as $wl)
                    <div class="clay-card-sm" style="padding:12px 14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:150px;">
                            <div style="font-weight:700;font-size:.85rem;">{{ $wl->nama }}</div>
                            <div style="font-size:.68rem;color:#9ca3af;">
                                {{ ucfirst($wl->platform) }} · {{ $wl->kode }}
                                · Spending: Rp {{ number_format($wl->spending_kemarin,0,',','.') }}
                            </div>
                        </div>
                        <div style="min-width:170px;flex-shrink:0;">
                            <label style="display:block;font-size:.66rem;font-weight:700;color:#6b7280;margin-bottom:3px;">
                                Sisa Saldo Sekarang (Rp)
                            </label>
                            <input type="number" name="sisa_saldo[{{ $wl->id }}]" min="0" step="1000"
                                   class="clay-input sisa-input"
                                   style="font-size:.85rem;padding:7px 10px;"
                                   placeholder="0"
                                   value="{{ $wl->sisa_saldo > 0 ? $wl->sisa_saldo : '' }}">
                        </div>
                    </div>
                    @empty
                    <div style="padding:22px;text-align:center;color:#9ca3af;font-size:.8rem;border:1.5px dashed #e5e7eb;border-radius:14px;flex:1;display:flex;align-items:center;justify-content:center;">
                        Belum ada whitelist dengan spending kemarin.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div style="display:flex;gap:10px;margin-top:18px;padding:18px;border-radius:16px;background:#fff;border:1px solid rgba(0,0,0,.06);">
            <button type="submit" class="clay-btn clay-btn-primary" style="flex:1;justify-content:center;">
                💰 Kirim Pengajuan
            </button>
            <a href="{{ route('topup.index') }}" class="clay-btn clay-btn-outline" data-page-link>Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<style>
/* Dua area (Rencana Top Up + Sisa Saldo) bertumpuk di layar kecil */
@media (max-width: 900px) {
    .topup-split { grid-template-columns: 1fr !important; }
}
</style>
<script>
// Baris Rencana Top Up yang tercentang diberi highlight
function syncRowState(cb) {
    var row = cb.closest('.item-row');
    if (!row) return;
    var on = cb.checked;
    var inp = row.querySelector('.nominal-input');
    var hid = row.querySelector('input[type="hidden"]');
    row.style.borderColor = on ? 'rgba(255,107,107,.45)' : 'transparent';
    row.style.background = on ? '#FFF8F8' : '';
    inp.disabled = !on;
    if (hid) hid.disabled = !on; // hidden whitelist_id ikut nonaktif agar tak terkirim saat tidak dicentang
    if (!on) inp.value = '';
    hitungTotal();
}

// ── Inisialisasi: pulihkan state baris yang sudah terisi (saat validasi gagal) ──
function restoreRowState(cb) {
    var row = cb.closest('.item-row');
    if (!row) return;
    var inp = row.querySelector('.nominal-input');
    var hid = row.querySelector('input[type="hidden"]');
    var on = cb.checked;
    inp.disabled = !on;
    if (hid) hid.disabled = !on;
    row.style.borderColor = on ? 'rgba(255,107,107,.45)' : 'transparent';
    row.style.background = on ? '#FFF8F8' : '';
}

document.querySelectorAll('.wl-select').forEach(function(cb) {
    // Baris yang terisi nominal (old value) otomatis dicentang & diaktifkan
    var inp = cb.closest('.item-row').querySelector('.nominal-input');
    if (inp && inp.dataset.wasFilled) {
        cb.checked = true;
        restoreRowState(cb);
    }
    cb.addEventListener('change', function() { syncRowState(cb); });
});

// ── Guard submit: cegah nominal kosong pada whitelist tercentang ──
var form = document.getElementById('topup-form');
if (form) {
    form.addEventListener('submit', function(e) {
        var empty = [];
        document.querySelectorAll('.wl-select:checked').forEach(function(cb) {
            var row = cb.closest('.item-row');
            var inp = row ? row.querySelector('.nominal-input') : null;
            var v = inp ? (inp.value || '').trim() : '';
            if (v === '' || parseFloat(v) < 0) {
                empty.push(cb.closest('.item-row').dataset.id);
                if (row) row.style.borderColor = '#ef4444';
            }
        });
        if (empty.length > 0) {
            e.preventDefault();
            var nama = WL_NAMES[empty[0]] || ('whitelist #'+empty[0]);
            alert('Masih ada whitelist tercentang tanpa nominal top up.\n\n• '+nama+(empty.length > 1 ? ' (+'+ (empty.length-1) +' lainnya)' : '')+'\n\nSilakan isi nominalnya lalu kirim kembali.');
            var first = document.querySelector('.item-row[data-id="'+empty[0]+'"]');
            if (first && first.scrollIntoView) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
}

function hitungTotal() {
    var inputs = document.querySelectorAll('.nominal-input');
    var total = 0;
    var count = 0;
    inputs.forEach(function(inp) {
        if (inp.disabled) return;
        var v = parseFloat(inp.value) || 0;
        if (v > 0) count++;
        total += v;
    });
    document.getElementById('total-display').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('total-label').textContent = count > 0
        ? count + ' whitelist akan di-top up'
        : 'centang whitelist & isi nominal';
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

var WL_NAMES = @json($whitelists->pluck('nama', 'id')->map(fn ($n) => (string) $n)->all());

hitungTotal();
hitungCPA();
</script>
@endpush
@endsection
