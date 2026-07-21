@extends('layouts.app')
@section('title','Profil Saya')
@section('page-title','👤 Profil Saya')
@section('page-subtitle','Kelola data pribadi dan keamanan akun')

@section('content')
@php $activeTab = session('tab', 'profil'); @endphp

<div style="max-width:720px;">

    {{-- ── Header Kartu Profil ───────────────────────────────── --}}
    <div class="clay-card" style="padding:24px;margin-bottom:20px;display:flex;align-items:center;gap:20px;" data-reveal>
        <div style="position:relative;flex-shrink:0;">
            <img src="{{ $user->avatar_url }}" alt="avatar"
                 style="width:72px;height:72px;border-radius:20px;object-fit:cover;
                        border:3px solid rgba(255,107,107,.2);box-shadow:0 4px 0 rgba(255,107,107,.15);">
            {{-- Badge role --}}
            <div style="position:absolute;bottom:-6px;left:50%;transform:translateX(-50%);
                        background:var(--color-primary,#FF6B6B);color:#fff;
                        font-size:.62rem;font-weight:700;padding:2px 8px;
                        border-radius:999px;white-space:nowrap;border:2px solid #fff;">
                {{ ucfirst($user->getRoleNames()->first() ?? 'user') }}
            </div>
        </div>
        <div style="min-width:0;flex:1;">
            <div style="font-weight:800;font-size:1.2rem;color:#1e1b2e;">
                {{ $user->nama ?? $user->display_name }}
            </div>
            <div style="font-size:.82rem;color:#9ca3af;margin-top:2px;">{{ $user->email }}</div>
            @if($user->role)
            <div style="font-size:.78rem;color:#6b7280;margin-top:4px;">
                📌 {{ $user->role }}
                @if(!$user->is_profile_complete)
                <span class="clay-badge clay-badge-yellow" style="margin-left:8px;font-size:.65rem;">Profil belum lengkap</span>
                @endif
            </div>
            @endif
        </div>
        <div style="text-align:right;flex-shrink:0;">
            <div class="clay-badge {{ $user->is_active ? 'clay-badge-green' : 'clay-badge-red' }}">
                {{ $user->is_active ? '✅ Aktif' : '❌ Nonaktif' }}
            </div>
            <div style="font-size:.7rem;color:#9ca3af;margin-top:6px;">
                Bergabung {{ $user->created_at->translatedFormat('M Y') }}
            </div>
        </div>
    </div>

    {{-- ── Tab Navigation ─────────────────────────────────────── --}}
    <div style="display:flex;gap:4px;margin-bottom:16px;background:rgba(0,0,0,.04);
                padding:4px;border-radius:16px;width:fit-content;" data-reveal>
        <button onclick="switchTab('profil')" id="tab-btn-profil"
                class="tab-btn {{ $activeTab === 'profil' ? 'active' : '' }}"
                style="padding:8px 18px;border-radius:12px;border:none;cursor:pointer;
                       font-size:.83rem;font-weight:600;font-family:inherit;
                       transition:all .2s;">
            📋 Data Pribadi
        </button>
        <button onclick="switchTab('password')" id="tab-btn-password"
                class="tab-btn {{ $activeTab === 'password' ? 'active' : '' }}"
                style="padding:8px 18px;border-radius:12px;border:none;cursor:pointer;
                       font-size:.83rem;font-weight:600;font-family:inherit;
                       transition:all .2s;">
            🔒 Ganti Password
        </button>
    </div>

    {{-- ── Tab: Data Pribadi ──────────────────────────────────── --}}
    <div id="tab-profil" style="display:{{ $activeTab === 'profil' ? 'block' : 'none' }};">
        <div class="clay-card" style="padding:28px;" data-reveal>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PUT')

                <div class="form-grid" style="gap:16px;">

                    <div class="col-span-2">
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Nama Lengkap <span style="color:#f87171;">*</span>
                        </label>
                        <input type="text" name="nama" value="{{ old('nama', $user->nama) }}"
                               placeholder="Nama lengkap Anda"
                               class="clay-input @error('nama') border-red-400 @enderror">
                        @error('nama')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Nama Panggilan <span style="color:#f87171;">*</span>
                        </label>
                        <input type="text" name="panggilan" value="{{ old('panggilan', $user->panggilan) }}"
                               placeholder="Nama panggilan"
                               class="clay-input @error('panggilan') border-red-400 @enderror">
                        @error('panggilan')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Nomor HP <span style="color:#f87171;">*</span>
                        </label>
                        <input type="tel" name="nohp" value="{{ old('nohp', $user->nohp) }}"
                               placeholder="08xx-xxxx-xxxx"
                               class="clay-input @error('nohp') border-red-400 @enderror">
                        @error('nohp')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>

                    {{-- Email — readonly --}}
                    <div>
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Email
                        </label>
                        <input type="email" value="{{ $user->email }}" disabled
                               class="clay-input" style="opacity:.6;cursor:not-allowed;">
                        <p style="font-size:.7rem;color:#9ca3af;margin-top:3px;">Email tidak dapat diubah sendiri.</p>
                    </div>

                    {{-- Posisi — readonly --}}
                    <div>
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Posisi / Divisi
                        </label>
                        <input type="text" value="{{ $user->role ?? '-' }}" disabled
                               class="clay-input" style="opacity:.6;cursor:not-allowed;">
                        <p style="font-size:.7rem;color:#9ca3af;margin-top:3px;">Diatur oleh admin.</p>
                    </div>

                    <div>
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Bank <span style="color:#f87171;">*</span>
                        </label>
                        <select name="bank" class="clay-input @error('bank') border-red-400 @enderror">
                            <option value="">— Pilih Bank —</option>
                            @foreach(['BCA','BRI','BNI','Mandiri','BSI','DANA','OVO','GoPay','ShopeePay','Lainnya'] as $b)
                            <option value="{{ $b }}" {{ old('bank', $user->bank) === $b ? 'selected' : '' }}>{{ $b }}</option>
                            @endforeach
                        </select>
                        @error('bank')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Nomor Rekening / Akun <span style="color:#f87171;">*</span>
                        </label>
                        <input type="text" name="norek" value="{{ old('norek', $user->norek) }}"
                               placeholder="No. rekening / akun e-wallet"
                               class="clay-input @error('norek') border-red-400 @enderror"
                               style="font-family:monospace;">
                        @error('norek')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>

                    <div class="col-span-2">
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Alamat Lengkap <span style="color:#f87171;">*</span>
                        </label>
                        <textarea name="alamat" rows="3"
                                  placeholder="Alamat lengkap Anda..."
                                  class="clay-input @error('alamat') border-red-400 @enderror"
                                  style="resize:none;">{{ old('alamat', $user->alamat) }}</textarea>
                        @error('alamat')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>

                </div>

                <div style="display:flex;gap:10px;margin-top:20px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                    <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan Perubahan</button>
                    <a href="{{ route('dashboard') }}" class="clay-btn clay-btn-outline" data-page-link>Batal</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Tab: Ganti Password ─────────────────────────────────── --}}
    <div id="tab-password" style="display:{{ $activeTab === 'password' ? 'block' : 'none' }};">
        <div class="clay-card" style="padding:28px;" data-reveal>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PUT')

                <div style="display:flex;flex-direction:column;gap:16px;">

                    <div>
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Password Lama <span style="color:#f87171;">*</span>
                        </label>
                        <input type="password" name="password_lama" placeholder="Password saat ini"
                               class="clay-input @error('password_lama') border-red-400 @enderror">
                        @error('password_lama')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Password Baru <span style="color:#f87171;">*</span>
                        </label>
                        <input type="password" name="password_baru" placeholder="Min. 8 karakter, huruf & angka"
                               class="clay-input @error('password_baru') border-red-400 @enderror">
                        @error('password_baru')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                            Konfirmasi Password Baru <span style="color:#f87171;">*</span>
                        </label>
                        <input type="password" name="password_baru_confirmation"
                               placeholder="Ulangi password baru"
                               class="clay-input">
                    </div>

                    {{-- Tips keamanan --}}
                    <div style="padding:12px 14px;border-radius:12px;background:#F0FFFE;
                                border:1.5px solid rgba(78,205,196,.3);font-size:.78rem;color:#0d9488;">
                        💡 <strong>Tips keamanan:</strong> Gunakan minimal 8 karakter, kombinasi huruf besar, huruf kecil, dan angka.
                    </div>

                </div>

                <div style="display:flex;gap:10px;margin-top:20px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                    <button type="submit" class="clay-btn clay-btn-primary">🔒 Ubah Password</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<style>
.tab-btn { background: transparent; color: #6b7280; }
.tab-btn.active { background: #fff; color: var(--color-primary, #FF6B6B);
                  box-shadow: 0 2px 8px rgba(0,0,0,.08); }
</style>
<script>
function switchTab(name) {
    ['profil','password'].forEach(t => {
        document.getElementById('tab-' + t).style.display = t === name ? 'block' : 'none';
        document.getElementById('tab-btn-' + t).classList.toggle('active', t === name);
    });
}
</script>
@endpush
@endsection
