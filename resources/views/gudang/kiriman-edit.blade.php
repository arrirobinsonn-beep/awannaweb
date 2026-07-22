@extends('layouts.app')
@section('title','Edit Kiriman Actual')
@section('page-title','🚚 Edit Kiriman Actual')
@section('page-subtitle','Ubah data kiriman — stok akan otomatis diperbarui')

@section('content')
<div class="clay-card" style="max-width:700px;margin:0 auto;padding:24px;" data-reveal>
    <form method="POST" action="{{ route('gudang.kiriman.update',$kiriman) }}">
        @csrf @method('PUT')

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:16px;">
            <div>
                <label class="field-label">Tanggal</label>
                <input type="date" name="tanggal" required class="clay-input"
                       value="{{ $kiriman->tanggal->format('Y-m-d') }}">
            </div>
            <div>
                <label class="field-label">Jenis</label>
                <select name="jenis" required class="clay-input">
                    <option value="TF" {{ $kiriman->jenis==='TF'?'selected':'' }}>TF</option>
                    <option value="COD" {{ $kiriman->jenis==='COD'?'selected':'' }}>COD</option>
                </select>
            </div>
            <div>
                <label class="field-label">Dashboard</label>
                <select name="dashboard" required class="clay-input">
                    @foreach($dashboards as $db)
                    <option value="{{ $db }}" {{ $kiriman->dashboard===$db?'selected':'' }}>{{ $db }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Jumlah Resi</label>
                <input type="number" name="jumlah_resi" required class="clay-input" min="1"
                       value="{{ old('jumlah_resi',$kiriman->jumlah_resi) }}">
            </div>
            <div>
                <label class="field-label">Value Resi</label>
                <div style="padding:6px 10px;background:#f3f4f6;border-radius:6px;font-size:.8rem;font-weight:700;color:#374151;">
                    Rp {{ number_format($kiriman->value_resi, 0, ',', '.') }}
                </div>
                <input type="hidden" name="value_resi" value="0">
                <span style="display:block;font-size:.6rem;color:#6b7280;margin-top:2px;">Otomatis dari harga_jual × jumlah</span>
            </div>
        </div>

        <div style="margin-bottom:12px;">
            <label style="display:block;font-size:.7rem;font-weight:700;margin-bottom:6px;color:#374151;">PRODUK & JUMLAH</label>
            <div id="produk-container">
                @forelse($kiriman->products as $i => $kp)
                <div class="baris-produk" style="display:flex;gap:8px;margin-bottom:4px;align-items:flex-end;">
                    <div style="flex:1;">
                        <select name="products[{{ $i }}][product_id]" required class="clay-input" style="padding:4px 6px;font-size:.75rem;">
                            <option value="">— Pilih —</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ $kp->product_id==$p->id?'selected':'' }}>{{ $p->nama_produk }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex:0 0 100px;">
                        <input type="number" name="products[{{ $i }}][jumlah]" class="clay-input" min="1" value="{{ $kp->jumlah }}" style="padding:4px 6px;font-size:.75rem;">
                    </div>
                    <button type="button" class="clay-btn clay-btn-xs clay-btn-danger" onclick="this.closest('.baris-produk').remove()" style="font-size:.6rem;">✕</button>
                </div>
                @empty
                <div class="baris-produk" style="display:flex;gap:8px;margin-bottom:4px;align-items:flex-end;">
                    <div style="flex:1;">
                        <select name="products[0][product_id]" required class="clay-input" style="padding:4px 6px;font-size:.75rem;">
                            <option value="">— Pilih —</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->nama_produk }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex:0 0 100px;">
                        <input type="number" name="products[0][jumlah]" class="clay-input" min="1" value="1" style="padding:4px 6px;font-size:.75rem;">
                    </div>
                    <button type="button" class="clay-btn clay-btn-xs clay-btn-danger" onclick="this.closest('.baris-produk').remove()" style="font-size:.6rem;">✕</button>
                </div>
                @endforelse
            </div>
            <button type="button" class="clay-btn clay-btn-xs clay-btn-outline" onclick="tambahProduk()" style="font-size:.65rem;margin-top:4px;">+ Produk</button>
        </div>

        <div style="display:flex;gap:8px;padding-top:16px;border-top:1px solid rgba(0,0,0,.06);">
            <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan</button>
            <a href="{{ route('gudang.kiriman') }}" class="clay-btn clay-btn-outline">Batal</a>
        </div>
    </form>
</div>

<script>
var produkIdx = {{ max($kiriman->products->count(), 1) }};
function tambahProduk() {
    var container = document.getElementById('produk-container');
    var first = container.querySelector('.baris-produk');
    var clone = first.cloneNode(true);
    var idx = produkIdx++;
    clone.querySelectorAll('[name]').forEach(function(el) {
        el.name = el.name.replace(/^products\[\d+\]/, 'products['+idx+']');
    });
    clone.querySelectorAll('select').forEach(function(s) { s.selectedIndex = 0; });
    clone.querySelectorAll('input').forEach(function(inp) { inp.value = '1'; });
    container.appendChild(clone);
}
</script>

<style>
.field-label { display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151; }
</style>
@endsection
