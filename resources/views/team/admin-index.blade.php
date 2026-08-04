@extends('layouts.app')
@section('title','Mapping Tim CS')
@section('page-title','🗺️ Mapping Tim CS')
@section('page-subtitle','Lihat seluruh CS (Customer Service) dan advertiser yang menaunginya')

@push('styles')
<style>
/* ── Modal pilih bulan penugasan ─────────────────────────── */
.modal-penugasan { position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:16px; }
.modal-penugasan.active { display:flex; }
.modal-penugasan .modal-backdrop { position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(2px); }
.modal-penugasan .modal-container { position:relative;background:#fff;border-radius:16px;width:100%;max-width:420px;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalInPg .22s ease; }
.modal-penugasan .modal-header { display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(0,0,0,.06); }
.modal-penugasan .modal-header h2 { margin:0;font-size:1rem;font-weight:800;color:#1e1b2e; }
.modal-penugasan .modal-close { background:#f3f4f6;border:none;border-radius:8px;width:30px;height:30px;font-size:.85rem;cursor:pointer;color:#6b7280; }
.modal-penugasan .modal-close:hover { background:#e5e7eb; }
.modal-penugasan .modal-body { padding:16px 20px; }
@keyframes modalInPg { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
</style>
@endpush

@section('content')
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:18px;" data-reveal>
    <form method="GET" action="{{ route('team.admin-index') }}" style="display:flex;flex-wrap:wrap;gap:8px;flex:1;min-width:0;">
        <select name="advertiser_id" class="clay-input" style="width:auto;min-width:180px;">
            <option value="">Semua Advertiser</option>
            @foreach($advertisers as $adv)
            <option value="{{ $adv->id }}" {{ request('advertiser_id') == $adv->id ? 'selected' : '' }}>
                {{ $adv->nama ?: $adv->email }}
            </option>
            @endforeach
        </select>
        <button type="submit" class="clay-btn clay-btn-secondary">🔍 Filter</button>
    </form>
    <button type="button" class="clay-btn clay-btn-primary"
            onclick="document.getElementById('modal-bulan-penugasan').classList.add('active')">
        🎯 Buat Penugasan
    </button>
    @if(auth()->user()->canCreateUser())
    <a href="{{ route('user.create') }}" class="clay-btn clay-btn-outline" data-page-link>＋ Tambah CS</a>
    @endif
</div>

<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th>CS</th>
                <th>Email</th>
                <th>Advertiser (Tuan)</th>
                <th>Status</th>
                <th style="text-align:right;">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse($csUsers as $cs)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="{{ $cs->avatar_url }}" alt="avatar"
                             style="width:34px;height:34px;border-radius:10px;object-fit:cover;border:2px solid #f3f4f6;flex-shrink:0;">
                        <div>
                            <div style="font-weight:700;font-size:.875rem;">{{ $cs->display_name }}</div>
                            @if($cs->nama)<div style="font-size:.7rem;color:#9ca3af;">{{ $cs->nama }}</div>@endif
                        </div>
                    </div>
                </td>
                <td style="font-size:.82rem;color:#6b7280;">{{ $cs->email }}</td>
                <td>
                    @if($cs->advertiser)
                    <div style="display:flex;align-items:center;gap:6px;">
                        <img src="{{ $cs->advertiser->avatar_url }}" alt="avatar"
                             style="width:24px;height:24px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                        <span style="font-size:.82rem;font-weight:600;">{{ $cs->advertiser->display_name }}</span>
                    </div>
                    @else
                    <span style="font-size:.8rem;color:#9ca3af;">— Belum ditugaskan</span>
                    @endif
                </td>
                <td>
                    <span class="clay-badge {{ $cs->is_active ? 'clay-badge-green' : 'clay-badge-red' }}">
                        {{ $cs->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td style="text-align:right;">
                    @if(auth()->user()->canCreateUser())
                    <a href="{{ route('user.edit', $cs) }}" class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;" data-page-link>✏️</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">👥</div>
                <p style="color:#9ca3af;">Belum ada user CS</p>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($csUsers->hasPages())<div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $csUsers->links() }}</div>@endif
</div>

{{-- ── Matriks Riwayat Penempatan (rotasi bulanan) ─────────────────────── --}}
<div class="clay-card" style="overflow:hidden;margin-top:18px;" data-reveal>
    <div style="padding:14px 18px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🗂️ Riwayat Penempatan</span>
        <span class="clay-badge clay-badge-blue" style="font-size:.65rem;">Matriks CS × Bulan — rotasi bulanan</span>
    </div>
    <div class="table-scroll">
        @php
            // Map [cs_user_id][bulan] → nama advertiser
            $matriks = [];
            foreach ($assignmentRows as $r) {
                $matriks[$r->cs_user_id][$r->bulan] = $r->advertiser?->display_name ?: '—';
            }
        @endphp
        <table class="clay-table" style="font-size:.75rem;">
            <thead><tr>
                <th style="min-width:170px;">CS</th>
                @foreach($semuaBulan as $bulan)
                <th style="text-align:center;min-width:110px;{{ $bulan === $bulanBerjalan ? 'background:#0d9488;color:#fff;' : '' }}">
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $bulan)->translatedFormat('M Y') }}
                    @if($bulan === $bulanBerjalan)
                    <span style="display:block;font-size:.6rem;font-weight:400;opacity:.85;">Sekarang</span>
                    @endif
                </th>
                @endforeach
            </tr></thead>
            <tbody>
            @forelse($semuaCs as $cs)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <img src="{{ $cs->avatar_url }}" alt="avatar"
                             style="width:26px;height:26px;border-radius:8px;object-fit:cover;flex-shrink:0;">
                        <span style="font-weight:600;">{{ $cs->display_name }}</span>
                        @if(!$cs->is_active)
                        <span class="clay-badge clay-badge-red" style="font-size:.55rem;">Nonaktif</span>
                        @endif
                    </div>
                </td>
                @foreach($semuaBulan as $bulan)
                <td style="text-align:center;{{ $bulan === $bulanBerjalan ? 'background:#F0FFFE;' : '' }}">
                    @if(isset($matriks[$cs->id][$bulan]))
                        <span style="font-weight:600;color:#065f46;">{{ $matriks[$cs->id][$bulan] }}</span>
                    @else
                        <span style="color:#d1d5db;">—</span>
                    @endif
                </td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($semuaBulan) + 1 }}" style="text-align:center;padding:40px 16px;color:#9ca3af;">
                    Belum ada CS terdaftar.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ─── Modal Pilih Bulan Penugasan ─────────────────────────────────────── --}}
<div class="modal-penugasan" id="modal-bulan-penugasan">
    <div class="modal-backdrop" onclick="tutupModalBulanPenugasan()"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h2>📅 Pilih Bulan Penugasan</h2>
            <button class="modal-close" onclick="tutupModalBulanPenugasan()" type="button">✕</button>
        </div>
        <div class="modal-body">
            <p style="font-size:.78rem;color:#9ca3af;margin:0 0 12px;">
                Pilih bulan untuk penugasan, lalu susun CS ke advertiser dengan drag &amp; drop.
            </p>
            <form method="GET" action="{{ route('team.penugasan') }}">
                <input type="month" name="bulan" value="{{ now()->format('Y-m') }}" class="clay-input"
                       style="margin-bottom:12px;width:100%;">
                <button type="submit" class="clay-btn clay-btn-primary" style="width:100%;">🚀 Lanjut ke Penugasan</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function tutupModalBulanPenugasan() {
    var m = document.getElementById('modal-bulan-penugasan');
    if (m) m.classList.remove('active');
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') tutupModalBulanPenugasan();
});
</script>
@endpush
@endsection
