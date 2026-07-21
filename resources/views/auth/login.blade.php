@extends('layouts.guest')
@section('title','Login')

@section('content')
<div class="login-wrap" data-animate-in>

    {{-- Logo --}}
    <div style="text-align:center;margin-bottom:28px;" data-stagger="1">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:72px;height:72px;
                    border-radius:24px;color:#fff;font-size:2rem;font-weight:900;margin-bottom:14px;
                    background:linear-gradient(135deg,#FF6B6B,#FF9A9A);
                    box-shadow:0 8px 0 #e05555;">W</div>
        <h1 style="font-size:1.7rem;font-weight:900;color:#1e1b2e;margin:0;">webAwanna</h1>
        <p style="color:#9ca3af;margin-top:6px;font-size:.875rem;">Masuk ke sistem manajemen Awanna</p>
    </div>

    {{-- Card --}}
    <div class="clay-card login-card" style="padding:28px;" data-stagger="2">

        @if($errors->any())
        <div class="clay-alert clay-alert-error" style="margin-bottom:18px;" data-flash>
            <span>❌</span>
            <div style="flex:1;font-size:.83rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="login-form">
            @csrf

            {{-- Email --}}
            <div style="margin-bottom:16px;">
                <label for="email" style="display:block;font-size:.83rem;font-weight:700;color:#374151;margin-bottom:6px;">
                    📧 Alamat Email
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       placeholder="nama@awanna.id" autocomplete="email" autofocus required
                       class="clay-input"
                       style="@error('email') border-color:#fca5a5; @enderror">
            </div>

            {{-- Password --}}
            <div style="margin-bottom:18px;">
                <label for="password" style="display:block;font-size:.83rem;font-weight:700;color:#374151;margin-bottom:6px;">
                    🔒 Password
                </label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password"
                           placeholder="••••••••" autocomplete="current-password" required
                           class="clay-input" style="padding-right:48px;">
                    <button type="button" onclick="togglePassword()" tabindex="-1"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                   background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1rem;line-height:1;padding:4px;">
                        <span id="eye-icon">👁</span>
                    </button>
                </div>
            </div>

            {{-- Remember --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                <input type="checkbox" id="remember" name="remember"
                       style="width:16px;height:16px;border-radius:4px;accent-color:var(--color-primary,#FF6B6B);cursor:pointer;">
                <label for="remember" style="font-size:.83rem;color:#6b7280;cursor:pointer;">Ingat saya</label>
            </div>

            {{-- Submit --}}
            <button type="submit" id="login-btn" class="clay-btn clay-btn-primary"
                    style="width:100%;justify-content:center;font-size:.95rem;padding:12px 20px;">
                <span id="login-text">Masuk Sekarang</span>
                <span id="login-spinner" class="hidden">⏳ Memproses...</span>
            </button>
        </form>

    </div>

    <p style="text-align:center;font-size:.72rem;color:#9ca3af;margin-top:18px;" data-stagger="3">
        Hubungi administrator jika tidak bisa masuk
    </p>

</div>

@push('scripts')
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.textContent = input.type === 'password' ? '👁' : '🙈';
}

document.getElementById('login-form').addEventListener('submit', function () {
    document.getElementById('login-text').classList.add('hidden');
    document.getElementById('login-spinner').classList.remove('hidden');
    document.getElementById('login-btn').disabled = true;
});
</script>
@endpush
@endsection
