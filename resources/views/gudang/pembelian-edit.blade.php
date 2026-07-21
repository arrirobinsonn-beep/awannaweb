@extends('layouts.app')
@section('title','Edit Pembelian Barang')
@section('page-title','📦 Edit Pembelian Barang')
@section('page-subtitle','Ubah data pembelian')

@section('content')
<div class="clay-card" style="max-width:800px;margin:0 auto;padding:24px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h4 style="font-weight:700;margin:0;">
            📦 <span style="color:var(--color-primary,#FF6B6B);">{{ $pembelian->product?->nama_produk ?? 'Produk' }}</span>
        </h4>
    </div>

    <form method="POST" action="{{ route('gudang.pembelian.update',$pembelian) }}"
          style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;">
        @csrf @method('PUT')
        <input type="hidden" name="product_id" value="{{ $pembelian->product_id }}">

        <div>
            <label class="field-label">TANGGAL</label>
            <input type="date" name="tanggal" required class="clay-input"
                   value="{{ $pembelian->tanggal->format('Y-m-d') }}">
        </div>
        <div>
            <label class="field-label">SUPPLIER</label>
            <input type="text" name="supplier" class="clay-input" list="supplier-list"
                   value="{{ old('supplier', $pembelian->supplier ?? $pembelian->supplierRel?->nama_supplier ?? '') }}">
            <input type="hidden" name="supplier_id" value="{{ $pembelian->supplier_id }}">
            <datalist id="supplier-list">
                @foreach($suppliers as $s)
                <option value="{{ $s->nama_supplier }}" data-id="{{ $s->id }}">
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="field-label">SUMBER PRODUK</label>
            <input type="text" name="sumber_produk" class="clay-input" placeholder="SHOPEE, TOKOPEDIA, dll"
                   value="{{ old('sumber_produk',$pembelian->sumber_produk) }}">
        </div>
        <div>
            <label class="field-label">QTY</label>
            <input type="number" name="qty" id="qty" required class="clay-input" min="0"
                   value="{{ old('qty',$pembelian->qty) }}" oninput="hitungDariHarga()">
        </div>
        <div>
            <label class="field-label">HARGA SATUAN</label>
            <input type="number" name="harga_satuan" id="harga_satuan" class="clay-input" min="0" step="0.01"
                   value="{{ old('harga_satuan',$pembelian->harga_satuan) }}" oninput="hitungDariHarga()">
        </div>
        <div>
            <label class="field-label">TOTAL BELANJA</label>
            <input type="number" name="total_belanja" id="total_belanja" class="clay-input" min="0" step="0.01"
                   value="{{ old('total_belanja',$pembelian->total_belanja) }}" oninput="hitungDariTotal()">
        </div>
        <div>
            <label class="field-label">ONGKIR</label>
            <input type="number" name="ongkir" class="clay-input" min="0" step="0.01"
                   value="{{ old('ongkir',$pembelian->ongkir) }}">
        </div>
        <div>
            <label class="field-label">KETERANGAN</label>
            <select name="keterangan" required class="clay-input">
                <option value="MASUK STOK" {{ $pembelian->keterangan==='MASUK STOK'?'selected':'' }}>MASUK STOK</option>
                <option value="BARU PESAN" {{ $pembelian->keterangan==='BARU PESAN'?'selected':'' }}>BARU PESAN</option>
            </select>
        </div>
        <div style="display:flex;align-items:flex-end;gap:8px;">
            <button type="submit" class="clay-btn clay-btn-primary">Simpan</button>
            <a href="{{ route('gudang.pembelian') }}" class="clay-btn clay-btn-outline">Batal</a>
        </div>
    </form>
</div>

<script>
function hitungDariHarga() {
    var qty = parseFloat(document.getElementById('qty').value) || 0;
    var harga = parseFloat(document.getElementById('harga_satuan').value) || 0;
    document.getElementById('total_belanja').value = (qty * harga).toFixed(2);
}

function hitungDariTotal() {
    var qty = parseFloat(document.getElementById('qty').value) || 0;
    var total = parseFloat(document.getElementById('total_belanja').value) || 0;
    if (qty > 0) {
        document.getElementById('harga_satuan').value = (total / qty).toFixed(2);
    }
}
</script>

<style>
.field-label { display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151; }
</style>
@endsection
