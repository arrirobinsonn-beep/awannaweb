@extends('layouts.guest')
@section('title','Lengkapi Profil')

@section('content')
<div class="login-wrap" style="max-width:500px;" data-animate-in>

    {{-- Header --}}
    <div style="text-align:center;margin-bottom:24px;" data-stagger="1">
        <div style="display:inline-flex;align-items:center;justify-content:center;
                    width:64px;height:64px;border-radius:20px;color:#fff;
                    font-size:1.8rem;font-weight:900;margin-bottom:12px;
                    background:linear-gradient(135deg,#FF6B6B,#FF9A9A);
                    box-shadow:0 6px 0 #e05555;">W</div>
        <h1 style="font-size:1.4rem;font-weight:900;color:#1e1b2e;margin:0;">
            Lengkapi Profil Anda
        </h1>
        <p style="color:#9ca3af;margin-top:6px;font-size:.85rem;line-height:1.5;">
            Halo, <strong style="color:var(--color-primary,#FF6B6B);">{{ $user->email }}</strong>!<br>
            Sebelum memulai, isi data berikut terlebih dahulu.
        </p>
    </div>

    {{-- Info flash --}}
    @if(session('info'))
    <div class="clay-alert clay-alert-warning" style="margin-bottom:16px;">
        <span>ℹ️</span>
        <span style="flex:1;font-size:.83rem;">{{ session('info') }}</span>
    </div>
    @endif

    {{-- Card Form --}}
    <div class="clay-card login-card" style="padding:28px;" data-stagger="2">
        <form method="POST" action="{{ route('profile.complete.store') }}">
            @csrf

            {{-- Progress hint --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;
                        padding:10px 14px;border-radius:12px;background:#FFF5F5;
                        border:1.5px solid rgba(255,107,107,.15);">
                <span style="font-size:1.1rem;">📋</span>
                <span style="font-size:.78rem;color:#6b7280;">
                    Semua field bertanda <span style="color:#f87171;font-weight:700;">*</span> wajib diisi
                </span>
            </div>

            <div class="form-grid" style="gap:14px;">

                {{-- Nama Lengkap --}}
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:5px;color:#374151;">
                        Nama Lengkap <span style="color:#f87171;">*</span>
                    </label>
                    <input type="text" name="nama" value="{{ old('nama') }}"
                           placeholder="Contoh: Ahmad Fauzi Pratama"
                           class="clay-input @error('nama') border-red-400 @enderror"
                           autocomplete="name">
                    @error('nama')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Nama Panggilan --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:5px;color:#374151;">
                        Nama Panggilan <span style="color:#f87171;">*</span>
                    </label>
                    <input type="text" name="panggilan" value="{{ old('panggilan') }}"
                           placeholder="Contoh: Fauzi"
                           class="clay-input @error('panggilan') border-red-400 @enderror">
                    @error('panggilan')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Posisi / Role --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:5px;color:#374151;">
                        Posisi / Divisi <span style="color:#f87171;">*</span>
                    </label>
                    <select name="role" class="clay-input @error('role') border-red-400 @enderror">
                        <option value="">— Pilih Posisi —</option>
                        @foreach(['Advertiser','Mentor','CS','Keuangan','Admin','Supervisor'] as $r)
                        <option value="{{ $r }}" {{ old('role') === $r ? 'selected' : '' }}>{{ $r }}</option>
                        @endforeach
                    </select>
                    @error('role')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- No HP --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:5px;color:#374151;">
                        Nomor HP <span style="color:#f87171;">*</span>
                    </label>
                    <input type="tel" name="nohp" value="{{ old('nohp') }}"
                           placeholder="08xx-xxxx-xxxx"
                           class="clay-input @error('nohp') border-red-400 @enderror">
                    @error('nohp')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Bank --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:5px;color:#374151;">
                        Nama Bank <span style="color:#f87171;">*</span>
                    </label>
                    <select name="bank" class="clay-input @error('bank') border-red-400 @enderror">
                        <option value="">— Pilih Bank —</option>
                        @foreach(['BCA','BRI','BNI','Mandiri','BSI','DANA','OVO','GoPay','ShopeePay','Lainnya'] as $b)
                        <option value="{{ $b }}" {{ old('bank') === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                    @error('bank')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- No Rekening --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:5px;color:#374151;">
                        Nomor Rekening / Akun <span style="color:#f87171;">*</span>
                    </label>
                    <input type="text" name="norek" value="{{ old('norek') }}"
                           placeholder="Contoh: 1234567890"
                           class="clay-input @error('norek') border-red-400 @enderror"
                           style="font-family:monospace;">
                    @error('norek')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Alamat --}}
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:5px;color:#374151;">
                        Alamat Lengkap <span style="color:#f87171;">*</span>
                    </label>
                    <textarea name="alamat" rows="3"
                              placeholder="Jl. Contoh No. 1, Kel. ..., Kec. ..., Kota ..."
                              class="clay-input @error('alamat') border-red-400 @enderror"
                              style="resize:none;">{{ old('alamat') }}</textarea>
                    @error('alamat')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

            </div>

            {{-- Submit --}}
            <button type="submit" id="submit-btn"
                    class="clay-btn clay-btn-primary"
                    style="width:100%;justify-content:center;font-size:.95rem;padding:12px 20px;margin-top:20px;">
                <span id="submit-text">✅ Simpan & Mulai</span>
                <span id="submit-spinner" style="display:none;">⏳ Menyimpan...</span>
            </button>

        </form>
    </div>

    {{-- Logout link --}}
    <div style="text-align:center;margin-top:16px;">
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit"
                    style="background:none;border:none;cursor:pointer;
                           font-size:.75rem;color:#9ca3af;text-decoration:underline;">
                Keluar dari akun ini
            </button>
        </form>
    </div>

</div>

@push('scripts')
<script>
document.querySelector('form').addEventListener('submit', function () {
    document.getElementById('submit-text').style.display    = 'none';
    document.getElementById('submit-spinner').style.display = 'inline';
    document.getElementById('submit-btn').disabled = true;
});
</script>
@endpush
@endsection
