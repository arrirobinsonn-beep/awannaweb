@extends('layouts.app')
@section('title', $mode==='create'?'Tambah User':'Edit User')
@section('page-title', $mode==='create'?'➕ Tambah User':'✏️ Edit User')
@section('page-subtitle', $mode==='create'?'Tambahkan pengguna baru ke sistem':'Perbarui data pengguna')

@section('content')
<div style="max-width:680px;">
    <div class="clay-card" style="padding:24px;" data-reveal>
        <form method="POST" action="{{ $mode==='create'?route('user.store'):route('user.update',$user) }}">
            @csrf @if($mode==='edit') @method('PUT') @endif

            <div class="form-grid">
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Nama Lengkap <span style="color:#f87171;">*</span></label>
                    <input type="text" name="name" value="{{ old('name',$user->name) }}" placeholder="Ahmad Fauzi" class="clay-input @error('name') border-red-400 @enderror">
                    @error('name')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Email <span style="color:#f87171;">*</span></label>
                    <input type="email" name="email" value="{{ old('email',$user->email) }}" placeholder="nama@awanna.id" class="clay-input @error('email') border-red-400 @enderror">
                    @error('email')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">
                        Password {{ $mode==='edit'?'(kosongkan jika tidak diubah)':'' }}
                        @if($mode==='create')<span style="color:#f87171;">*</span>@endif
                    </label>
                    <input type="password" name="password" placeholder="{{ $mode==='create'?'Min. 8 karakter':'••••••••' }}" class="clay-input @error('password') border-red-400 @enderror" {{ $mode==='create'?'required':'' }}>
                    @error('password')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Divisi</label>
                    <select name="divisi" class="clay-input">
                        <option value="">— Pilih Divisi —</option>
                        @foreach(['super_admin','admin','advertiser','mentor','keuangan','cs'] as $div)
                        <option value="{{ $div }}" {{ old('divisi',$user->divisi)===$div?'selected':'' }}>
                            {{ ucfirst(str_replace('_',' ',$div)) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Role <span style="color:#f87171;">*</span></label>
                    <select name="role" class="clay-input @error('role') border-red-400 @enderror">
                        <option value="">— Pilih Role —</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role',$user->getRoleNames()->first())===$role->name?'selected':'' }}>
                            {{ ucfirst(str_replace('_',' ',$role->name)) }}
                        </option>
                        @endforeach
                    </select>
                    @error('role')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Nomor Telepon</label>
                    <input type="text" name="telepon" value="{{ old('telepon',$user->telepon) }}" placeholder="0812-XXXX-XXXX" class="clay-input">
                </div>
                <div class="col-span-2">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active',$user->is_active ?? true)?'checked':'' }}
                               style="width:18px;height:18px;border-radius:5px;accent-color:var(--color-primary);cursor:pointer;">
                        <span style="font-size:.875rem;font-weight:700;">User Aktif</span>
                    </label>
                    <p style="font-size:.7rem;color:#9ca3af;margin-top:4px;margin-left:28px;">User nonaktif tidak bisa login ke sistem</p>
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:20px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary">{{ $mode==='create'?'＋ Simpan User':'💾 Update User' }}</button>
                <a href="{{ route('user.index') }}" class="clay-btn clay-btn-outline" data-page-link>Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
