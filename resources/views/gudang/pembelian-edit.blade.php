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
            <select name="supplier_id" class="clay-input">
                <option value="">— Pilih —</option>
                @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ $pembelian->supplier_id == $s->id ? 'selected' : '' }}>{{ $s->nama_supplier }}</option>
                @endforeach
            </select>
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
            <input type="hidden" name="keterangan" id="keterangan" value="{{ $pembelian->keterangan }}">
            <div class="toggle-group">
                <button type="button" class="toggle-opt {{ $pembelian->keterangan==='MASUK STOK'?'active':'' }}" data-value="MASUK STOK" onclick="pilihKeterangan(this)">MASUK STOK</button>
                <button type="button" class="toggle-opt {{ $pembelian->keterangan==='BARU PESAN'?'active':'' }}" data-value="BARU PESAN" onclick="pilihKeterangan(this)">BARU PESAN</button>
            </div>
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

function pilihKeterangan(btn) {
    document.querySelectorAll('.toggle-opt').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('keterangan').value = btn.dataset.value;
}
</script>

<style>
.field-label { display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151; }
.toggle-group { display:flex;border:1.5px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#f3f4f6; }
.toggle-opt { flex:1;padding:6px 6px;border:none;background:transparent;cursor:pointer;font-size:.72rem;font-weight:700;color:#6b7280;transition:all .15s;letter-spacing:.3px; }
.toggle-opt.active[data-value="MASUK STOK"] { background:#d1fae5;color:#065f46;box-shadow:0 1px 3px rgba(0,0,0,.12);font-weight:800; }
.toggle-opt.active[data-value="BARU PESAN"] { background:#fef3c7;color:#92400e;box-shadow:0 1px 3px rgba(0,0,0,.12);font-weight:800; }
.toggle-opt:not(.active):hover { color:#374151;background:#e5e7eb; }
</style>
@endsection
