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
                    <input type="text" name="code" value="{{ old('code',$product->code) }}" placeholder="PRD-001" class="clay-input @error('code') border-red-400 @enderror">
                    @error('code')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Nama Produk <span style="color:#f87171;">*</span></label>
                    <input type="text" name="name" value="{{ old('name',$product->name) }}" placeholder="Serum Vitamin C" class="clay-input @error('name') border-red-400 @enderror">
                    @error('name')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Kategori</label>
                    <input type="text" name="category" value="{{ old('category',$product->category) }}" placeholder="Skincare" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Harga Jual (Rp) <span style="color:#f87171;">*</span></label>
                    <input type="number" name="selling_price" min="0" step="500" value="{{ old('selling_price',$product->selling_price) }}" placeholder="129000" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Inventory (Gudang)</label>
                    <select name="inventory_id" class="clay-input">
                        <option value="">— Pilih Inventory —</option>
                        @foreach($inventories as $inv)
                        <option value="{{ $inv->id }}" {{ old('inventory_id',$product->inventory_id)==$inv->id?'selected':'' }}>{{ $inv->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">HPP / PCS</label>
                    <input type="number" name="purchase_price" min="0" step="100" value="{{ old('purchase_price',$product->purchase_price) }}" placeholder="70000" class="clay-input">
                    <div style="font-size:.7rem;color:#9ca3af;margin-top:4px;">HPP diperbarui otomatis dari Barang Masuk (rata-rata tertimbang).</div>
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Stok</label>
                    <div style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;font-weight:800;color:#374151;">
                        {{ number_format($product->stok) }} {{ $product->unit ?? 'pcs' }}
                    </div>
                    <div style="font-size:.7rem;color:#9ca3af;margin-top:4px;">Stok otomatis dari jurnal masuk/keluar per varian. Kelola lewat menu Barang Masuk.</div>
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Satuan</label>
                    <input type="text" name="unit" value="{{ old('unit',$product->unit ?? 'pcs') }}" placeholder="pcs" class="clay-input">
                </div>
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Status</label>
                    <select name="status" class="clay-input">
                        <option value="active"   {{ old('status',$product->status)==='active'   ?'selected':'' }}>Aktif</option>
                        <option value="inactive" {{ old('status',$product->status)==='inactive' ?'selected':'' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Deskripsi produk..." class="clay-input" style="resize:none;">{{ old('description',$product->description) }}</textarea>
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
