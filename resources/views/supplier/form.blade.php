@extends('layouts.app')
@section('title', $mode==='create'?'Tambah Supplier':'Edit Supplier')
@section('page-title', $mode==='create'?'➕ Tambah Supplier':'✏️ Edit Supplier')
@section('page-subtitle', $mode==='create'?'Tambahkan supplier baru':'Perbarui data supplier')

@section('content')
<div style="max-width:680px;">
    <div class="clay-card" style="padding:24px;" data-reveal>
        <form method="POST" action="{{ $mode==='create'?route('supplier.store'):route('supplier.update',$supplier) }}">
            @csrf @if($mode==='edit') @method('PUT') @endif

            <div class="form-grid">
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Kode Supplier <span style="color:#f87171;">*</span></label>
                    <input type="text" name="kode_supplier" value="{{ old('kode_supplier',$supplier->kode_supplier) }}" placeholder="SUP-001" class="clay-input @error('kode_supplier') border-red-400 @enderror">
                    @error('kode_supplier')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Nama Supplier <span style="color:#f87171;">*</span></label>
                    <input type="text" name="nama_supplier" value="{{ old('nama_supplier',$supplier->nama_supplier) }}" placeholder="PT Maju Bersama" class="clay-input @error('nama_supplier') border-red-400 @enderror">
                    @error('nama_supplier')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Nama PIC</label>
                    <input type="text" name="pic_nama" value="{{ old('pic_nama',$supplier->pic_nama) }}" placeholder="Budi Santoso" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Telepon PIC</label>
                    <input type="text" name="pic_telepon" value="{{ old('pic_telepon',$supplier->pic_telepon) }}" placeholder="0812-XXXX-XXXX" class="clay-input">
                </div>
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Email</label>
                    <input type="email" name="email" value="{{ old('email',$supplier->email) }}" placeholder="supplier@email.com" class="clay-input">
                </div>
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Alamat</label>
                    <textarea name="alamat" rows="2" placeholder="Jl. Contoh No. 1" class="clay-input" style="resize:none;">{{ old('alamat',$supplier->alamat) }}</textarea>
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Kota</label>
                    <input type="text" name="kota" value="{{ old('kota',$supplier->kota) }}" placeholder="Jakarta Utara" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Provinsi</label>
                    <input type="text" name="provinsi" value="{{ old('provinsi',$supplier->provinsi) }}" placeholder="DKI Jakarta" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Status <span style="color:#f87171;">*</span></label>
                    <select name="status" class="clay-input">
                        <option value="aktif"    {{ old('status',$supplier->status)==='aktif'    ?'selected':'' }}>Aktif</option>
                        <option value="nonaktif" {{ old('status',$supplier->status)==='nonaktif' ?'selected':'' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Catatan</label>
                    <textarea name="catatan" rows="2" placeholder="Catatan tambahan..." class="clay-input" style="resize:none;">{{ old('catatan',$supplier->catatan) }}</textarea>
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:20px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary">{{ $mode==='create'?'＋ Simpan Supplier':'💾 Update Supplier' }}</button>
                <a href="{{ route('supplier.index') }}" class="clay-btn clay-btn-outline" data-page-link>Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
