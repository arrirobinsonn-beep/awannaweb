@extends('layouts.app')
@section('title','Tambah User Baru')
@section('page-title','➕ Tambah User Baru')
@section('page-subtitle','Buat akun — user melengkapi profil saat login pertama')

@section('content')
<div style="max-width:480px;">
    <div class="clay-card" style="padding:28px;" data-reveal>

        {{-- Info box --}}
        <div style="display:flex;gap:10px;padding:14px;border-radius:14px;
                    background:#FFF8F0;border:1.5px solid #fed7aa;margin-bottom:22px;">
            <span style="font-size:1.2rem;flex-shrink:0;">💡</span>
            <div style="font-size:.8rem;color:#92400e;line-height:1.6;">
                <strong>Cara kerja:</strong><br>
                Anda hanya perlu isi <strong>email</strong>, <strong>password</strong>, dan <strong>role</strong>.<br>
                User akan diminta melengkapi profil (nama, HP, bank, dll) saat <strong>login pertama kali</strong>.
            </div>
        </div>

        <form method="POST" action="{{ route('user.store') }}">
            @csrf

            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Email --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                        📧 Email <span style="color:#f87171;">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="nama@awanna.id" required autofocus
                           class="clay-input @error('email') border-red-400 @enderror">
                    @error('email')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Password --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                        🔒 Password <span style="color:#f87171;">*</span>
                    </label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="pwd"
                               placeholder="Min. 8 karakter, huruf & angka" required
                               class="clay-input @error('password') border-red-400 @enderror"
                               style="padding-right:44px;">
                        <button type="button" onclick="togglePwd()"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                       background:none;border:none;cursor:pointer;font-size:.95rem;color:#9ca3af;">
                            <span id="pwd-icon">👁</span>
                        </button>
                    </div>
                    @error('password')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Role --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;color:#374151;">
                        🎭 Role Sistem <span style="color:#f87171;">*</span>
                    </label>
                    <select name="role" required
                            class="clay-input @error('role') border-red-400 @enderror">
                        <option value="">— Pilih Role —</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_',' ',$role->name)) }}
                        </option>
                        @endforeach
                    </select>
                    @error('role')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

            </div>

            <div style="display:flex;gap:10px;margin-top:22px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary">
                    ＋ Buat Akun
                </button>
                <a href="{{ route('user.index') }}" class="clay-btn clay-btn-outline" data-page-link>
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function togglePwd() {
    const i = document.getElementById('pwd');
    const ic = document.getElementById('pwd-icon');
    i.type = i.type === 'password' ? 'text' : 'password';
    ic.textContent = i.type === 'password' ? '👁' : '🙈';
}
</script>
@endpush
@endsection
