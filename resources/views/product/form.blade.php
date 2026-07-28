@extends('layouts.app')
@section('title', $mode==='create'?'Tambah Produk':'Edit Produk')
@section('page-title', $mode==='create'?'➕ Tambah Produk':'✏️ Edit Produk')
@section('page-subtitle', $mode==='create'?'Tambahkan produk baru ke katalog':'Perbarui informasi produk')

@section('content')
<div style="max-width:680px;">
    <div class="clay-card" style="padding:24px;" data-reveal>
        <form method="POST" action="{{ $mode==='create'?route('product.store'):route('product.update',$product) }}">
            @csrf @if($mode==='edit') @method('PUT') @endif
            <div class="form-grid">
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Kode Produk <span style="color:#f87171;">*</span></label>
                    <input type="text" name="kode_produk" value="{{ old('kode_produk',$product->kode_produk) }}" placeholder="PRD-001" class="clay-input @error('kode_produk') border-red-400 @enderror">
                    @error('kode_produk')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Nama Produk <span style="color:#f87171;">*</span></label>
                    <input type="text" name="nama_produk" value="{{ old('nama_produk',$product->nama_produk) }}" placeholder="Serum Vitamin C" class="clay-input @error('nama_produk') border-red-400 @enderror">
                    @error('nama_produk')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Kategori</label>
                    <input type="text" name="kategori" value="{{ old('kategori',$product->kategori) }}" placeholder="Skincare" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Harga Jual (Rp) <span style="color:#f87171;">*</span></label>
                    <input type="number" name="harga_jual" min="0" step="500" value="{{ old('harga_jual',$product->harga_jual) }}" placeholder="129000" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Gudang</label>
                    <select name="gudang_id" class="clay-input">
                        <option value="">— Pilih Gudang —</option>
                        @foreach($gudangs as $g)
                        <option value="{{ $g->id }}" {{ old('gudang_id',$product->gudang_id)==$g->id?'selected':'' }}>{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Stok <span style="color:#f87171;">*</span></label>
                    <input type="number" name="stok" min="0" value="{{ old('stok',$product->stok) }}" placeholder="100" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Satuan</label>
                    <input type="text" name="satuan" value="{{ old('satuan',$product->satuan ?? 'pcs') }}" placeholder="pcs" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Status</label>
                    <select name="status" class="clay-input">
                        <option value="aktif"    {{ old('status',$product->status)==='aktif'    ?'selected':'' }}>Aktif</option>
                        <option value="nonaktif" {{ old('status',$product->status)==='nonaktif' ?'selected':'' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" placeholder="Deskripsi produk..." class="clay-input" style="resize:none;">{{ old('deskripsi',$product->deskripsi) }}</textarea>
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary">{{ $mode==='create'?'＋ Simpan Produk':'💾 Update Produk' }}</button>
                <a href="{{ route('product.index') }}" class="clay-btn clay-btn-outline" data-page-link>Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
