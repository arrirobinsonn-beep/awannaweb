@extends('layouts.app')
@section('title','Edit User')
@section('page-title','✏️ Edit User')
@section('page-subtitle','Ubah email, password, role, atau status user')

@section('content')
<div style="max-width:580px;">
    <div class="clay-card" style="padding:28px;" data-reveal>

        {{-- Info profil --}}
        <div style="display:flex;align-items:center;gap:14px;padding:14px;border-radius:14px;
                    background:#F0FFFE;border:1.5px solid rgba(78,205,196,.3);margin-bottom:22px;">
            <img src="{{ $user->avatar_url }}" alt="avatar"
                 style="width:44px;height:44px;border-radius:14px;object-fit:cover;border:2px solid rgba(78,205,196,.3);flex-shrink:0;">
            <div style="min-width:0;">
                <div style="font-weight:700;font-size:.95rem;color:#1e1b2e;">
                    {{ $user->display_name }}
                </div>
                <div style="font-size:.75rem;color:#9ca3af;">
                    {{ $user->email }}
                    @if(!$user->is_profile_complete)
                    <span class="clay-badge clay-badge-yellow" style="margin-left:6px;font-size:.65rem;">Profil belum lengkap</span>
                    @endif
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('user.update', $user) }}">
            @csrf @method('PUT')

            <div class="form-grid" style="gap:16px;">

                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                        📧 Email <span style="color:#f87171;">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="clay-input @error('email') border-red-400 @enderror">
                    @error('email')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                        🔒 Password Baru <span style="font-size:.72rem;font-weight:400;color:#9ca3af;">(kosongkan jika tidak diubah)</span>
                    </label>
                    <input type="password" name="password"
                           placeholder="••••••••"
                           class="clay-input @error('password') border-red-400 @enderror">
                    @error('password')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                @if(auth()->user()->canCreateUser())
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                        🎭 Role Sistem
                    </label>
                    <select name="role" id="role-select" class="clay-input" onchange="toggleAdvertiserField()">
                        <option value="">— Tidak diubah —</option>
                        @foreach($roles as $r)
                        <option value="{{ $r->name }}" {{ $user->hasRole($r->name) ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_',' ',$r->name)) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- CS Utama assignment (hanya untuk role CS) — rotasi bulanan --}}
                <div id="advertiser-field" style="display:none;">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                        ⭐ CS Utama untuk
                        <span style="font-size:.72rem;font-weight:400;color:#9ca3af;">(penempatan per bulan — rotasi CS bulanan)</span>
                    </label>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:150px;">
                            <label style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;color:#6b7280;">📅 Bulan Berlaku</label>
                            <input type="month" name="bulan" id="bulan-input"
                                   value="{{ old('bulan', now()->format('Y-m')) }}" class="clay-input">
                        </div>
                        <div style="flex:2;min-width:200px;">
                            <label style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;color:#6b7280;">Advertiser</label>
                            <select name="advertiser_id" id="advertiser-select" class="clay-input">
                                <option value="">— Pilih Advertiser —</option>
                                @foreach($advertisers as $adv)
                                <option value="{{ $adv->id }}" {{ $user->advertiser_id == $adv->id ? 'selected' : '' }}>
                                    {{ $adv->nama ?: $adv->panggilan ?: $adv->email }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($user->hasRole('cs') && $user->advertiser)
                    <div style="margin-top:8px;padding:8px 12px;border-radius:10px;background:#F0FFFE;border:1.5px solid rgba(78,205,196,.3);font-size:.78rem;color:#065f46;">
                        ⭐ Saat ini CS utama untuk <strong>{{ $user->advertiser->display_name }}</strong>
                    </div>
                    @endif
                </div>

                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                        Status Akun
                    </label>
                    <div style="display:flex;gap:10px;align-items:center;height:42px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ $user->is_active ? 'checked' : '' }}
                                   style="width:18px;height:18px;accent-color:var(--color-primary);cursor:pointer;">
                            <span style="font-size:.83rem;font-weight:600;">
                                {{ $user->is_active ? '✅ Aktif' : '❌ Nonaktif' }}
                            </span>
                        </label>
                    </div>
                </div>
                @endif

            </div>

            <div style="display:flex;gap:10px;margin-top:22px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan Perubahan</button>
                <a href="{{ route('user.index') }}" class="clay-btn clay-btn-outline" data-page-link>Batal</a>
            </div>
        </form>
    </div>

    @if($csAssignments->isNotEmpty())
    <div class="clay-card" style="padding:20px;margin-top:16px;" data-reveal>
        <h3 style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:12px;">🗂️ Riwayat Penempatan CS</h3>
        <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
            <thead>
                <tr style="background:#4472C4;color:#fff;">
                    <th style="padding:8px 12px;text-align:left;">Bulan Berlaku</th>
                    <th style="padding:8px 12px;text-align:left;">CS Utama untuk</th>
                </tr>
            </thead>
            <tbody>
                @foreach($csAssignments as $a)
                <tr style="border-bottom:1px solid rgba(0,0,0,.05);">
                    <td style="padding:8px 12px;font-weight:700;">
                        {{ \Carbon\Carbon::createFromFormat('Y-m', $a->bulan)->translatedFormat('F Y') }}
                        @if($a->bulan === now()->format('Y-m'))
                        <span class="clay-badge clay-badge-green" style="font-size:.55rem;">Berjalan</span>
                        @endif
                    </td>
                    <td style="padding:8px 12px;">{{ $a->advertiser?->display_name ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@push('scripts')
<script>
// Peta penempatan per bulan: { '2026-08': advertiserId, ... }
var csAssignmentMap = @json($csAssignments->pluck('advertiser_id', 'bulan'));
var bulanSekarang = '{{ now()->format('Y-m') }}';
var snapshotAdvertiserId = '{{ $user->advertiser_id }}';

function toggleAdvertiserField() {
    var roleSelect = document.getElementById('role-select');
    var advField = document.getElementById('advertiser-field');
    if (!advField) return;
    var selectedRole = roleSelect.value;
    // Tampilkan field advertiser jika role yang dipilih adalah 'cs'
    // atau jika user saat ini sudah punya role cs (biar tetap kelihatan)
    var isCs = (selectedRole === 'cs') || (selectedRole === '' && {{ $user->hasRole('cs') ? 'true' : 'false' }});
    advField.style.display = isCs ? 'block' : 'none';
}

// Sinkronkan pilihan advertiser dengan bulan yang dipilih
function syncAdvertiserSelect() {
    var bulan = document.getElementById('bulan-input')?.value;
    var select = document.getElementById('advertiser-select');
    if (!bulan || !select) return;
    if (csAssignmentMap[bulan]) {
        select.value = String(csAssignmentMap[bulan]);
    } else if (bulan === bulanSekarang && snapshotAdvertiserId) {
        select.value = String(snapshotAdvertiserId); // fallback snapshot utk bulan berjalan
    } else {
        select.value = '';
    }
}

// Jalankan saat halaman dimuat
document.addEventListener('DOMContentLoaded', function () {
    toggleAdvertiserField();
    syncAdvertiserSelect();
    document.getElementById('bulan-input')?.addEventListener('change', syncAdvertiserSelect);
});
</script>
@endpush
@endsection