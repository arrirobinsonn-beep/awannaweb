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

                {{-- CS Utama assignment (hanya untuk role CS) --}}
                <div id="advertiser-field" style="display:none;">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                        ⭐ CS Utama untuk
                        <span style="font-size:.72rem;font-weight:400;color:#9ca3af;">(pilih advertiser yang menjadikan CS ini sebagai CS utama)</span>
                    </label>
                    <select name="advertiser_id" class="clay-input">
                        <option value="">— Pilih Advertiser —</option>
                        @foreach($advertisers as $adv)
                        <option value="{{ $adv->id }}" {{ $user->advertiser_id == $adv->id ? 'selected' : '' }}>
                            {{ $adv->nama ?: $adv->panggilan ?: $adv->email }}
                        </option>
                        @endforeach
                    </select>
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
</div>

@push('scripts')
<script>
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

// Jalankan saat halaman dimuat
document.addEventListener('DOMContentLoaded', toggleAdvertiserField);
</script>
@endpush
@endsection