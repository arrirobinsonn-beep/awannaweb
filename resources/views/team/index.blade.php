@extends('layouts.app')
@section('title','Tim Saya')
@section('page-title','👥 Tim Saya')
@section('page-subtitle', $user->hasRole('cs')
    ? (isset($advertiser) && $advertiser ? "Tim CS — bernaung di bawah {$advertiser->panggilan}" : 'Tim CS — bernaung di bawah advertiser Anda')
    : 'Daftar CS — CS Utama & CS Tamu (rotasi bulanan)')

@push('styles')
<style>
/* ── Modal Riwayat CS Utama ─────────────────────────── */
.modal-riwayat-cs { position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:16px; }
.modal-riwayat-cs.active { display:flex; }
.modal-riwayat-cs .modal-backdrop { position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(2px); }
.modal-riwayat-cs .modal-container { position:relative;background:#fff;border-radius:16px;width:100%;max-width:520px;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,.25); }
.modal-riwayat-cs .modal-header { display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(0,0,0,.06); }
.modal-riwayat-cs .modal-header h2 { margin:0;font-size:1rem;font-weight:800;color:#1e1b2e; }
.modal-riwayat-cs .modal-close { background:#f3f4f6;border:none;border-radius:8px;width:30px;height:30px;font-size:.85rem;cursor:pointer;color:#6b7280; }
.modal-riwayat-cs .modal-close:hover { background:#e5e7eb; }
.modal-riwayat-cs .modal-body { padding:16px 20px;overflow-y:auto; }
@keyframes modalIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
.modal-riwayat-cs .modal-container { animation:modalIn .22s ease; }
</style>
@endpush

@section('content')
@if($user->hasRole('cs'))
    {{-- ═══════════ SISI CS: lihat tim di bawah advertiser tempat bernaung ═══════════ --}}
    @if($team->isEmpty())
        {{-- Empty state --}}
        <div class="clay-card" style="padding:60px 20px;text-align:center;" data-reveal>
            <div style="font-size:3.5rem;margin-bottom:12px;">👥</div>
            <h3 style="font-weight:700;font-size:1.1rem;color:#1e1b2e;margin-bottom:6px;">Belum Ada Anggota Tim</h3>
            <p style="color:#9ca3af;font-size:.85rem;max-width:360px;margin:0 auto;">
                Saat ini belum ada CS (Customer Service) yang ditugaskan untuk tim Anda.
                Hubungi Super Admin untuk menambahkan anggota tim.
            </p>
        </div>
    @else
        {{-- Stats ringkasan --}}
        <div class="grid-stats"
             data-reveal
             style="width:100%;grid-template-columns:repeat(3,minmax(0,1fr));">
            <div class="clay-card" style="padding:18px;width:100%;box-sizing:border-box;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:#FFF0F0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">👥</div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:#1e1b2e;">{{ $team->count() }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;font-weight:600;">Total Anggota</div>
                    </div>
                </div>
            </div>
            <div class="clay-card" style="padding:18px;width:100%;box-sizing:border-box;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:#F0FFF4;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">✅</div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:#1e1b2e;">{{ $team->where('is_active', true)->count() }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;font-weight:600;">Aktif</div>
                    </div>
                </div>
            </div>
            <div class="clay-card" style="padding:18px;width:100%;box-sizing:border-box;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:#FFF5F0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📧</div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:#1e1b2e;">{{ $team->where('is_profile_complete', true)->count() }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;font-weight:600;">Profil Lengkap</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daftar Tim --}}
        <div class="clay-card" style="overflow:hidden;" data-reveal>
            <div class="table-scroll">
                <table class="clay-table">
                    <thead><tr>
                        <th>Anggota Tim</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Bergabung</th>
                    </tr></thead>
                    <tbody>
                    @foreach($team as $member)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="{{ $member->avatar_url }}" alt="avatar"
                                     style="width:38px;height:38px;border-radius:12px;object-fit:cover;border:2px solid #f3f4f6;flex-shrink:0;">
                                <div>
                                    <div style="font-weight:700;font-size:.875rem;">{{ $member->display_name }}</div>
                                    <div style="font-size:.7rem;color:#9ca3af;">
                                        {{ $member->nama ? $member->nama : '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:.8rem;color:#6b7280;">
                            <div>{{ $member->email }}</div>
                            @if($member->nohp)
                                <div style="font-size:.72rem;color:#9ca3af;">{{ $member->nohp }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="clay-badge {{ $member->is_active ? 'clay-badge-green' : 'clay-badge-red' }}">
                                {{ $member->is_active ? '✅ Aktif' : '❌ Nonaktif' }}
                            </span>
                            @if(!$member->is_profile_complete)
                            <span class="clay-badge clay-badge-yellow" style="margin-left:4px;">Profil ⏳</span>
                            @endif
                        </td>
                        <td style="font-size:.8rem;color:#9ca3af;">
                            {{ $member->created_at->translatedFormat('d M Y') }}
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@else
    {{-- ═══════════ SISI ADVERTISER: 2 tabel — CS Utama & CS Tamu ═══════════ --}}

    {{-- Aksi: riwayat CS Utama --}}
    <div style="display:flex;justify-content:flex-end;margin-bottom:14px;" data-reveal>
        <button type="button" class="clay-btn clay-btn-outline"
                onclick="document.getElementById('modal-riwayat-cs').classList.add('active')">
            🗂️ Riwayat CS Utama
        </button>
    </div>

    {{-- Stats ringkasan --}}
    <div class="grid-stats"
         data-reveal
         style="width:100%;grid-template-columns:repeat(3,minmax(0,1fr));">
        <div class="clay-card" style="padding:18px;width:100%;box-sizing:border-box;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:40px;height:40px;border-radius:12px;background:#F0FFF4;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">⭐</div>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:#1e1b2e;">{{ $mainCs->count() }}</div>
                    <div style="font-size:.72rem;color:#9ca3af;font-weight:600;">CS Utama</div>
                </div>
            </div>
        </div>
        <div class="clay-card" style="padding:18px;width:100%;box-sizing:border-box;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:40px;height:40px;border-radius:12px;background:#FFF0F0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🤝</div>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:#1e1b2e;">{{ $guestCs->count() }}</div>
                    <div style="font-size:.72rem;color:#9ca3af;font-weight:600;">CS Tamu</div>
                </div>
            </div>
        </div>
        <div class="clay-card" style="padding:18px;width:100%;box-sizing:border-box;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:40px;height:40px;border-radius:12px;background:#FFF5F0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">👥</div>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:#1e1b2e;">{{ $mainCs->count() + $guestCs->count() }}</div>
                    <div style="font-size:.72rem;color:#9ca3af;font-weight:600;">Total CS</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabel CS Utama ─────────────────────────────────────────── --}}
    <div class="clay-card" style="overflow:hidden;margin-bottom:18px;" data-reveal>
        <div style="padding:14px 18px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">⭐ CS Utama</span>
            <span class="clay-badge clay-badge-green" style="font-size:.65rem;">CS yang dikhususkan untuk tim Anda</span>
        </div>
        <div class="table-scroll">
            <table class="clay-table">
                <thead><tr>
                    <th>CS</th>
                    <th>Kontak</th>
                    <th>Status</th>
                    <th>Bergabung</th>
                </tr></thead>
                <tbody>
                @forelse($mainCs as $member)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="{{ $member->avatar_url }}" alt="avatar"
                                 style="width:38px;height:38px;border-radius:12px;object-fit:cover;border:2px solid rgba(16,185,129,.25);flex-shrink:0;">
                            <div>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="font-weight:700;font-size:.875rem;">{{ $member->display_name }}</span>
                                    <span class="clay-badge clay-badge-green" style="font-size:.6rem;">Utama</span>
                                </div>
                                <div style="font-size:.7rem;color:#9ca3af;">
                                    {{ $member->nama ? $member->nama : '—' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7280;">
                        <div>{{ $member->email }}</div>
                        @if($member->nohp)
                            <div style="font-size:.72rem;color:#9ca3af;">{{ $member->nohp }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="clay-badge {{ $member->is_active ? 'clay-badge-green' : 'clay-badge-red' }}">
                            {{ $member->is_active ? '✅ Aktif' : '❌ Nonaktif' }}
                        </span>
                        @if(!$member->is_profile_complete)
                        <span class="clay-badge clay-badge-yellow" style="margin-left:4px;">Profil ⏳</span>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:#9ca3af;">
                        {{ $member->created_at->translatedFormat('d M Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:40px 16px;">
                        <div style="font-size:2rem;margin-bottom:6px;">⭐</div>
                        <p style="color:#9ca3af;">Belum ada CS Utama untuk tim Anda. Hubungi admin untuk menetapkan CS utama.</p>
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Tabel CS Tamu ──────────────────────────────────────────── --}}
    <div class="clay-card" style="overflow:hidden;" data-reveal>
        <div style="padding:14px 18px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🤝 CS Tamu</span>
            <span class="clay-badge clay-badge-blue" style="font-size:.65rem;">Seluruh CS lain — rotasi bulanan</span>
        </div>
        <div class="table-scroll">
            <table class="clay-table">
                <thead><tr>
                    <th>CS</th>
                    <th>Kontak</th>
                    <th>CS Utama untuk</th>
                    <th>Status</th>
                    <th>Bergabung</th>
                </tr></thead>
                <tbody>
                @forelse($guestCs as $member)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="{{ $member->avatar_url }}" alt="avatar"
                                 style="width:38px;height:38px;border-radius:12px;object-fit:cover;border:2px solid #f3f4f6;flex-shrink:0;">
                            <div>
                                <div style="font-weight:700;font-size:.875rem;">{{ $member->display_name }}</div>
                                <div style="font-size:.7rem;color:#9ca3af;">
                                    {{ $member->nama ? $member->nama : '—' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7280;">
                        <div>{{ $member->email }}</div>
                        @if($member->nohp)
                            <div style="font-size:.72rem;color:#9ca3af;">{{ $member->nohp }}</div>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:#6b7280;">
                        @if($member->advertiser)
                            {{ $member->advertiser->display_name }}
                        @else
                            <span style="color:#9ca3af;">— Belum ditetapkan</span>
                        @endif
                    </td>
                    <td>
                        <span class="clay-badge {{ $member->is_active ? 'clay-badge-green' : 'clay-badge-red' }}">
                            {{ $member->is_active ? '✅ Aktif' : '❌ Nonaktif' }}
                        </span>
                        @if(!$member->is_profile_complete)
                        <span class="clay-badge clay-badge-yellow" style="margin-left:4px;">Profil ⏳</span>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:#9ca3af;">
                        {{ $member->created_at->translatedFormat('d M Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px 16px;">
                        <div style="font-size:2rem;margin-bottom:6px;">🤝</div>
                        <p style="color:#9ca3af;">Tidak ada CS tamu saat ini.</p>
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── Modal Riwayat CS Utama ─────────────────────────────────────── --}}
    <div class="modal-riwayat-cs" id="modal-riwayat-cs">
        <div class="modal-backdrop" onclick="closeModalRiwayat()"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h2>🗂️ Riwayat CS Utama</h2>
                <button class="modal-close" onclick="closeModalRiwayat()" type="button">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:.78rem;color:#9ca3af;margin:0 0 10px;">
                    Siapa CS utama tim Anda di tiap bulan (rotasi bulanan).
                </p>
                @forelse($csHistory as $h)
                <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;background:#f8fafc;border:1px solid rgba(0,0,0,.04);{{ $loop->first ? '' : 'margin-top:8px;' }}">
                    <img src="{{ $h->csUser?->avatar_url }}" alt="avatar"
                         style="width:38px;height:38px;border-radius:10px;object-fit:cover;flex-shrink:0;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:.85rem;color:#1e1b2e;">
                            {{ $h->csUser?->display_name ?: '—' }}
                        </div>
                        <div style="font-size:.72rem;color:#9ca3af;">
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $h->bulan)->translatedFormat('F Y') }}
                        </div>
                    </div>
                    @if($h->bulan === now()->format('Y-m'))
                    <span class="clay-badge clay-badge-green" style="font-size:.6rem;">Berjalan</span>
                    @endif
                </div>
                @empty
                <div style="text-align:center;padding:40px 16px;">
                    <div style="font-size:2.5rem;margin-bottom:8px;">🗂️</div>
                    <p style="color:#9ca3af;font-size:.85rem;">Belum ada riwayat penempatan CS Utama untuk tim Anda.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
function closeModalRiwayat() {
    var modal = document.getElementById('modal-riwayat-cs');
    if (modal) modal.classList.remove('active');
}
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modal-riwayat-cs');
    if (!modal) return;
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModalRiwayat();
    });
});
</script>
@endpush
@endsection