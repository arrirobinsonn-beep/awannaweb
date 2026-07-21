@extends('layouts.app')
@section('title','Edit Rincian Stok')
@section('page-title','✏️ Edit Rincian Stok')
@section('page-subtitle','Perbarui data stok harian')

@section('content')
<div style="max-width:100%;">
    <div class="clay-card" style="padding:24px;" data-reveal>
        <form method="POST" action="{{ route('gudang.stok-rincian.update',$item) }}">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">
                <div>
                    <label class="field-label">GUDANG</label>
                    <input type="text" name="gudang" class="clay-input" value="{{ $item->gudang }}" list="gudang-list" required>
                    <datalist id="gudang-list">
                        @foreach($gudangList as $g)
                        <option value="{{ $g }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="field-label">PRODUK</label>
                    <select name="product_id" required class="clay-input">
                        @foreach(\App\Models\Product::where('status','aktif')->orderBy('nama_produk')->get() as $p)
                        <option value="{{ $p->id }}" {{ $item->product_id==$p->id?'selected':'' }}>{{ $p->nama_produk }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">TANGGAL</label>
                    <input type="date" name="tanggal" required class="clay-input" value="{{ $item->tanggal->format('Y-m-d') }}">
                </div>
                <div>
                    <label class="field-label">BELANJA</label>
                    <input type="number" name="masuk_belanja" class="clay-input" min="0" value="{{ $item->masuk_belanja }}">
                </div>
                <div>
                    <label class="field-label">RTS</label>
                    <input type="number" name="masuk_rts" class="clay-input" min="0" value="{{ $item->masuk_rts }}">
                </div>
                <div>
                    <label class="field-label">REPAIR</label>
                    <input type="number" name="masuk_repair" class="clay-input" min="0" value="{{ $item->masuk_repair }}">
                </div>
                <div>
                    <label class="field-label">RUSAK</label>
                    <input type="number" name="barang_rusak" class="clay-input" min="0" value="{{ $item->barang_rusak }}">
                </div>
                <div>
                    <label class="field-label">KELUAR</label>
                    <input type="number" name="barang_keluar" class="clay-input" min="0" value="{{ $item->barang_keluar }}">
                </div>
                <div>
                    <label class="field-label">CATATAN</label>
                    <input type="text" name="catatan" class="clay-input" value="{{ $item->catatan }}" placeholder="(opsional)">
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary">💾 Update</button>
                <a href="{{ route('gudang.stok-rincian') }}" class="clay-btn clay-btn-outline">Batal</a>
            </div>
        </form>
    </div>
</div>

<style>
.field-label { display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151; }
</style>
@endsection
