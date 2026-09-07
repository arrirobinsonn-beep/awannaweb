@extends('layouts.app')

@section('title', 'Alokasi Bonus')
@section('page-title', '💎 Alokasi Bonus')
@section('page-subtitle', 'Distribusi potensi bonus per tim advertiser')

@push('styles')
<style>
    .ba-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .ba-period { display:flex; align-items:center; gap:8px; }
    .ba-period select { font-size:.8rem; padding:6px 10px; border:1.5px solid #e5e7eb; border-radius:10px; background:#fff; }

    .ba-summary { display:flex; align-items:center; gap:12px; background:#fff; border:1.5px solid #f0e9e4; border-radius:16px; padding:14px 16px; margin-bottom:16px; }
    .ba-summary-ic { width:42px; height:42px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.15rem; }
    .ba-summary b { display:block; font-size:1rem; color:#1e1b2e; line-height:1.2; }
    .ba-summary small { font-size:.66rem; color:#9ca3af; font-weight:600; }

    .ba-team { margin-bottom:20px; }
    .ba-team-head { background:linear-gradient(135deg,#1e3a5f 0%,#2d5a8e 100%); color:#fff; padding:12px 16px; border-radius:14px 14px 0 0; display:flex; justify-content:space-between; align-items:center; }
    .ba-team-head h3 { margin:0; font-size:.85rem; font-weight:800; }
    .ba-team-head small { font-size:.7rem; opacity:.8; }
    .ba-team-body { background:#fff; border:1.5px solid #f0e9e4; border-top:none; border-radius:0 0 14px 14px; overflow:hidden; }

    .ba-table { width:100%; border-collapse:collapse; font-size:.75rem; }
    .ba-table th { background:#f8f5f1; padding:8px 10px; font-weight:700; text-align:left; font-size:.7rem; color:#6b7280; border-bottom:1.5px solid #e5e7eb; white-space:nowrap; }
    .ba-table th.num, .ba-table td.num { text-align:right; }
    .ba-table td { padding:7px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
    .ba-table tr:last-child td { border-bottom:none; }
    .ba-table tr.total-row { background:#f9fafb; font-weight:800; }
    .ba-table tr.total-row td { border-top:2px solid #e5e7eb; padding:10px; }

    .ba-badge { display:inline-block; padding:2px 8px; border-radius:8px; font-size:.65rem; font-weight:700; }
    .ba-badge-ads { background:#dbeafe; color:#1e40af; }
    .ba-badge-cs { background:#d1fae5; color:#065f46; }
    .ba-badge-keu { background:#fef3c7; color:#b45309; }
    .ba-badge-admin { background:#ede9fe; color:#6d28d9; }
    .ba-badge-utama { background:#d1fae5; color:#065f46; }
    .ba-badge-guest { background:#f3f4f6; color:#6b7280; }

    .ba-pct-input { width:52px; padding:4px 6px; border:1.5px solid #d1d5db; border-radius:8px; font-size:.75rem; font-weight:700; text-align:center; background:#fffbe6; }
    .ba-pct-input:focus { border-color:#3b82f6; outline:none; box-shadow:0 0 0 2px rgba(59,130,246,.15); }

    .ba-save-btn { display:none; padding:6px 16px; background:#2563eb; color:#fff; border:none; border-radius:10px; font-size:.75rem; font-weight:700; cursor:pointer; }
    .ba-save-btn:hover { background:#1d4ed8; }
    .ba-save-btn.show { display:inline-flex; }

    .ba-empty { text-align:center; padding:48px 24px; color:#9ca3af; }
    .ba-empty-icon { font-size:2rem; margin-bottom:10px; }

    .ba-grand-total { background:linear-gradient(135deg,#065f46,#059669); color:#fff; padding:16px 20px; border-radius:14px; display:flex; justify-content:space-between; align-items:center; margin-top:16px; }
    .ba-grand-total h3 { margin:0; font-size:.9rem; font-weight:800; }
    .ba-grand-total .amount { font-size:1.3rem; font-weight:900; }
</style>
@endpush

@section('content')

{{-- Period selector + global settings --}}
<div class="clay-card" style="padding:16px;" data-reveal>
    <div class="ba-header">
        <div>
            <div style="font-size:1rem;font-weight:800;color:#1e1b2e;">💎 Alokasi Bonus</div>
            <div style="font-size:.7rem;color:#9ca3af;margin-top:2px;">Distribusi potensi bonus per tim advertiser.</div>
        </div>
        <div class="ba-period">
            <input type="text" id="baSearch" placeholder="🔍 Cari tim..." style="width:180px;padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:.8rem;">
            <form method="GET" action="{{ route('finance.bonus-allocation.index') }}">
                <select name="period" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        @php $p = now()->year.'-'.str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                        <option value="{{ $p }}" {{ $period === $p ? 'selected' : '' }}>
                            {{ now()->setMonth($m)->translatedFormat('F') }} {{ now()->year }}
                        </option>
                    @endfor
                </select>
            </form>
        </div>
    </div>
</div>

{{-- Global settings: all 4 roles --}}
<div class="clay-card" style="padding:14px 16px;margin-bottom:16px;" data-reveal>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <span style="font-size:.75rem;font-weight:700;color:#6b7280;">Global %:</span>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:.72rem;color:#4b5563;">Advertiser</span>
            <input type="number" class="ba-pct-input" id="globalAdv" value="{{ $advPctDefault ?? 36 }}" min="0" max="100" step="1">
            <span style="font-size:.72rem;color:#9ca3af;">%</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:.72rem;color:#4b5563;">CS</span>
            <input type="number" class="ba-pct-input" id="globalCs" value="{{ $csPctDefault ?? 48 }}" min="0" max="100" step="1">
            <span style="font-size:.72rem;color:#9ca3af;">%</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:.72rem;color:#4b5563;">Keuangan</span>
            <input type="number" class="ba-pct-input" id="globalKeuangan" value="{{ $keuanganPct }}" min="0" max="100" step="0.5">
            <span style="font-size:.72rem;color:#9ca3af;">%</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:.72rem;color:#4b5563;">Admin</span>
            <input type="number" class="ba-pct-input" id="globalAdmin" value="{{ $adminPct }}" min="0" max="100" step="0.5">
            <span style="font-size:.72rem;color:#9ca3af;">%</span>
        </div>
        <button class="ba-save-btn" id="saveGlobalBtn" onclick="saveGlobalSettings()">💾 Simpan</button>
    </div>
</div>

@if($teams->isEmpty())
<div class="clay-card" data-reveal>
    <div class="ba-empty">
        <div class="ba-empty-icon">📭</div>
        <div>Belum ada data advertiser untuk periode ini.</div>
    </div>
</div>
@else

{{-- Teams --}}
@foreach($teams as $team)
<div class="ba-team" data-reveal data-team-name="{{ $team->advertiser->nama ?? '' }}">
    <div class="ba-team-head">
        <div>
            <h3>ALOKASI BONUS : TIM {{ strtoupper($team->advertiser->panggilan ?? $team->advertiser->nama) }}</h3>
            <small>FORMASI : {{ $team->formasi }}</small>
        </div>
        <div style="text-align:right;">
            <div style="font-size:.65rem;opacity:.8;">Potensi Bonus</div>
            <div style="font-size:1rem;font-weight:900;">Rp {{ number_format($team->potensi_bonus, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="ba-team-body">
        <table class="ba-table">
            <thead>
                <tr>
                    <th style="width:36px;text-align:center;">NO</th>
                    <th>BAGIAN</th>
                    <th class="num">% BONUS</th>
                    <th class="num">POTENSI BONUS</th>
                    <th class="num">JUMLAH PAID</th>
                    <th>PENERIMA</th>
                    <th>KETERANGAN</th>
                    <th class="num">NOMINAL</th>
                    <th class="num">% PEMBAGIAN</th>
                    <th class="num">PAYMENT</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($team->members as $m)
                <tr>
                    <td style="text-align:center;font-size:.7rem;color:#9ca3af;">{{ $no++ }}</td>
                    <td>
                        <span class="ba-badge ba-badge-{{ $m->role }}">
                            {{ strtoupper($m->role) }}
                        </span>
                    </td>
                    <td class="num" style="font-weight:700;">
                        @if($m->role === 'advertiser')
                            {{ number_format($team->ads_pct, 0) }}%
                        @elseif($m->role === 'cs')
                            {{ number_format($team->cs_pct, 0) }}%
                        @elseif($m->role === 'keuangan')
                            {{ number_format($keuanganPct, 0) }}%
                        @else
                            {{ number_format($adminPct, 0) }}%
                        @endif
                    </td>
                    <td class="num" style="font-weight:700;">
                        @if($m->role === 'advertiser' || ($m->role === 'cs' && $loop->first) || ($m->role === 'keuangan') || ($m->role === 'admin'))
                            Rp {{ number_format($team->potensi_bonus, 0, ',', '.') }}
                        @endif
                    </td>
                    <td class="num" style="font-weight:700;">
                        {{ $m->paid > 0 ? number_format($m->paid) : '0' }}
                    </td>
                    <td>
                        <span style="font-weight:700;font-size:.78rem;">{{ strtoupper($m->name) }}</span>
                    </td>
                    <td>
                        @if($m->keterangan === 'CS UTAMA' || $m->keterangan === 'ADS UTAMA')
                            <span class="ba-badge ba-badge-utama">{{ $m->keterangan }}</span>
                        @else
                            <span class="ba-badge ba-badge-guest">{{ $m->keterangan }}</span>
                        @endif
                    </td>
                    <td class="num" style="font-weight:800;color:#065f46;">
                        Rp {{ number_format($m->payment, 0, ',', '.') }}
                    </td>
                    <td class="num">
                        {{ number_format($m->pembagian, 0) }}%
                    </td>
                    <td class="num" style="font-weight:800;color:#1e1b2e;font-size:.82rem;">
                        Rp {{ number_format($m->payment, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" style="font-size:.78rem;">TOTAL</td>
                    <td class="num" style="font-size:.78rem;">100%</td>
                    <td class="num" style="font-size:.78rem;">Rp {{ number_format($team->potensi_bonus, 0, ',', '.') }}</td>
                    <td class="num" style="font-size:.78rem;">{{ number_format($team->total_paid_team) }}</td>
                    <td colspan="2"></td>
                    <td class="num" style="font-size:.78rem;">Rp {{ number_format($team->total_payment, 0, ',', '.') }}</td>
                    <td class="num" style="font-size:.78rem;">100%</td>
                    <td class="num" style="font-size:.82rem;">Rp {{ number_format($team->total_payment, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endforeach

{{-- Grand total --}}
<div class="ba-grand-total" data-reveal>
    <h3>TOTAL KESELURUHAN ({{ $teams->count() }} Tim)</h3>
    <div class="amount">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
</div>

@endif

@endsection

@push('scripts')
<script>
(function() {
    const origAdv = {{ $advPctDefault ?? 36 }};
    const origCs = {{ $csPctDefault ?? 48 }};
    const origKeuangan = {{ $keuanganPct }};
    const origAdmin = {{ $adminPct }};
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.getElementById('baSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.ba-team').forEach(el => {
            const name = (el.dataset.teamName || '').toLowerCase();
            el.style.display = (!q || name.includes(q)) ? '' : 'none';
        });
    });

    ['globalAdv','globalCs','globalKeuangan','globalAdmin'].forEach(id => {
        document.getElementById(id).addEventListener('input', checkDirty);
    });

    function checkDirty() {
        const adv = parseFloat(document.getElementById('globalAdv').value) || 0;
        const cs = parseFloat(document.getElementById('globalCs').value) || 0;
        const keu = parseFloat(document.getElementById('globalKeuangan').value) || 0;
        const adm = parseFloat(document.getElementById('globalAdmin').value) || 0;
        const btn = document.getElementById('saveGlobalBtn');
        if (adv !== origAdv || cs !== origCs || keu !== origKeuangan || adm !== origAdmin) {
            btn.classList.add('show');
        } else {
            btn.classList.remove('show');
        }
    }

    window.saveGlobalSettings = function() {
        const adv = parseFloat(document.getElementById('globalAdv').value) || 0;
        const cs = parseFloat(document.getElementById('globalCs').value) || 0;
        const keu = parseFloat(document.getElementById('globalKeuangan').value) || 0;
        const adm = parseFloat(document.getElementById('globalAdmin').value) || 0;
        const btn = document.getElementById('saveGlobalBtn');

        btn.textContent = '⏳...';
        btn.disabled = true;

        fetch('{{ route("finance.bonus-allocation.settings") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify({ ads_pct: adv, cs_pct: cs, keuangan_pct: keu, admin_pct: adm }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { btn.textContent = '✅'; setTimeout(() => window.location.reload(), 500); }
            else { btn.textContent = '❌'; btn.disabled = false; }
        })
        .catch(() => { btn.textContent = '❌'; btn.disabled = false; });
    };
})();
</script>
@endpush
