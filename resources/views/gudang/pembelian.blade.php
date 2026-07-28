@extends('layouts.app')
@section('title','Master Pembelian Barang')
@section('page-title','📦 Master Pembelian Barang')
@section('page-subtitle','Catat pembelian barang — stok masuk atau baru pesan')

@section('content')

{{-- Filter --}}
<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="GET" action="{{ route('gudang.pembelian') }}"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div>
            <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151;">Bulan</label>
            <input type="month" name="bulan" value="{{ request('bulan') }}"
                   class="clay-input" style="padding:6px 10px;">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary">Filter</button>
        <a href="{{ route('gudang.pembelian') }}" class="clay-btn clay-btn-outline">Reset</a>
        <button type="button" class="clay-btn clay-btn-success" style="margin-left:auto;"
                onclick="bukaModalProduk()">+ Tambah</button>
    </form>
</div>

{{-- Modal Pilih Produk --}}
<div id="modal-produk" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:100;display:none;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:24px;width:90%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h4 style="font-weight:800;margin-bottom:12px;">Pilih Produk</h4>
        <input type="text" id="cari-produk" class="clay-input" placeholder="Cari produk..."
               style="margin-bottom:12px;" oninput="filterProduk()">
        <div id="daftar-produk" style="max-height:300px;overflow-y:auto;display:grid;gap:4px;">
            @foreach($products as $p)
            <button type="button" class="pilih-produk-btn"
                    data-id="{{ $p->id }}" data-nama="{{ $p->nama_produk }}"
                    data-supplier="{{ $p->supplier?->nama_supplier ?? '' }}"
                    data-supplier-id="{{ $p->supplier_id ?? '' }}"
                    onclick="pilihProduk(this)">
                {{ $p->nama_produk }}
            </button>
            @endforeach
        </div>
        <button type="button" class="clay-btn clay-btn-outline" style="margin-top:12px;width:100%;"
                onclick="tutupModalProduk()">Batal</button>
    </div>
</div>

{{-- Form Tambah --}}
<div id="form-tambah" class="hidden" data-reveal>
    <form method="POST" action="{{ route('gudang.pembelian.store') }}"
          class="clay-card" style="padding:16px;margin-bottom:16px;">
        @csrf
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <h4 style="font-weight:700;margin:0;">
                📦 <span id="form-produk-nama" style="color:var(--color-primary,#FF6B6B);">-</span>
            </h4>
            <button type="button" class="clay-btn clay-btn-sm clay-btn-outline" onclick="tutupForm()">✕ Tutup</button>
        </div>
        <input type="hidden" name="product_id" id="form-produk-id">

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">
            <div>
                <label class="field-label">TANGGAL</label>
                <input type="date" name="tanggal" required class="clay-input" value="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="field-label">SUPPLIER</label>
                <select name="supplier_id" id="supplier_id" class="clay-input">
                    <option value="">— Pilih —</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">SUMBER PRODUK</label>
                <input type="text" name="sumber_produk" class="clay-input" placeholder="SHOPEE, TOKOPEDIA, dll">
            </div>
            <div>
                <label class="field-label">QTY</label>
                <input type="number" name="qty" id="qty" required class="clay-input" min="0" value="0" oninput="hitungDariHarga()">
            </div>
            <div>
                <label class="field-label">HARGA SATUAN</label>
                <input type="number" name="harga_satuan" id="harga_satuan" class="clay-input" min="0" step="0.01" value="0" oninput="hitungDariHarga()">
            </div>
            <div>
                <label class="field-label">TOTAL BELANJA</label>
                <input type="number" name="total_belanja" id="total_belanja" class="clay-input" min="0" step="0.01" value="0" oninput="hitungDariTotal()">
            </div>
            <div>
                <label class="field-label">ONGKIR</label>
                <input type="number" name="ongkir" class="clay-input" min="0" step="0.01" value="0">
            </div>
            <div>
                <label class="field-label">KETERANGAN</label>
                <input type="hidden" name="keterangan" id="keterangan" value="MASUK STOK">
                <div class="toggle-group">
                    <button type="button" class="toggle-opt active" data-value="MASUK STOK" onclick="pilihKeterangan(this)">MASUK STOK</button>
                    <button type="button" class="toggle-opt" data-value="BARU PESAN" onclick="pilihKeterangan(this)">BARU PESAN</button>
                </div>
            </div>
            <div style="display:flex;align-items:flex-end;">
                <button type="submit" class="clay-btn clay-btn-primary">Simpan</button>
            </div>
        </div>
    </form>
</div>

{{-- Tabel per Produk --}}
@forelse($produkList as $produk)
<div class="clay-card" style="padding:0;overflow-x:auto;margin-bottom:4px;" data-reveal>
    <div style="padding:10px 14px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e5e7eb;font-weight:800;font-size:.85rem;color:#1e1b2e;display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;"
         onclick="toggleGroup('grp-{{ $produk->id }}', this)">
        <span style="display:flex;align-items:center;gap:8px;">
            <span class="grp-arrow" style="font-size:.65rem;color:#9ca3af;">▶</span>
            📦 {{ $produk->nama_produk }}
        </span>
        <button type="button" class="clay-btn clay-btn-xs clay-btn-success" style="font-size:.65rem;"
                data-id="{{ $produk->id }}" data-nama="{{ $produk->nama_produk }}"
                data-supplier="{{ $produk->supplier?->nama_supplier ?? '' }}"
                data-supplier-id="{{ $produk->supplier_id ?? '' }}"
                onclick="event.stopPropagation();bukaFormProduk(this)">+ Tambah</button>
    </div>
    <div id="grp-{{ $produk->id }}" style="display:none;">
    <table class="clay-table">
        <thead>
            <tr>
                <th>TANGGAL</th>
                <th>SUPPLIER</th>
                <th>SUMBER PRODUK</th>
                <th>QTY</th>
                <th>HARGA SATUAN</th>
                <th>TOTAL BELANJA</th>
                <th>ONGKIR</th>
                <th>AKUMULASI QTY</th>
                <th>AKUMULASI NILAI</th>
                <th>HPP RATA-RATA</th>
                <th>KETERANGAN</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($produk->pembelianBarangs as $item)
            <tr>
                <td>{{ $item->tanggal ? $item->tanggal->format('d/m/Y') : '-' }}</td>
                <td>{{ $item->supplier_name }}</td>
                <td>{{ $item->sumber_produk ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->qty,0,',','.') }}</td>
                <td class="text-right">{{ number_format($item->harga_satuan,2,',','.') }}</td>
                <td class="text-right">{{ number_format($item->total_belanja,0,',','.') }}</td>
                <td class="text-right">{{ number_format($item->ongkir,0,',','.') }}</td>
                <td class="text-right">{{ number_format($item->akumulasi_qty,0,',','.') }}</td>
                <td class="text-right">{{ number_format($item->akumulasi_nilai,0,',','.') }}</td>
                <td class="text-right">{{ number_format($item->hpp_rata_rata,0,',','.') }}</td>
                <td><span class="badge {{ $item->keterangan === 'MASUK STOK' ? 'badge-success' : 'badge-warning' }}">{{ $item->keterangan }}</span></td>
                <td>
                    <a href="{{ route('gudang.pembelian.edit',$item) }}" class="clay-btn clay-btn-sm clay-btn-outline">Edit</a>
                    <form method="POST" action="{{ route('gudang.pembelian.destroy',$item) }}"
                          style="display:inline" onsubmit="return confirm('Hapus data ini?')">
                        @csrf @method('DELETE')
                        <button class="clay-btn clay-btn-sm clay-btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#f8fafc;font-weight:700;">
                <td colspan="3" style="text-align:right;color:#6b7280;font-size:.7rem;">TOTAL →</td>
                <td class="text-right">{{ number_format($produk->sum_qty,0,',','.') }}</td>
                <td class="text-right">{{ number_format($produk->sum_harga_satuan,2,',','.') }}</td>
                <td class="text-right">{{ number_format($produk->sum_total_belanja,0,',','.') }}</td>
                <td class="text-right">{{ number_format($produk->sum_ongkir,0,',','.') }}</td>
                <td colspan="2"></td>
                <td class="text-right" style="color:var(--color-primary,#FF6B6B);">{{ number_format($produk->hpp_akhir,0,',','.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
@empty
<div class="clay-card" style="padding:48px;text-align:center;" data-reveal>
    <div style="font-size:2rem;margin-bottom:8px;">📭</div>
    <p style="color:#9ca3af;">Belum ada data pembelian.</p>
</div>
@endforelse

<div class="pagination-wrapper" style="margin-top:16px;">
    {{ $produkList->links() }}
</div>

<style>
.hidden { display:none; }
.field-label { display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151; }
.text-right { text-align:right; }
.clay-table th, .clay-table td { white-space:nowrap; }
.badge { display:inline-block;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:700; }
.badge-success { background:#d1fae5;color:#065f46; }
.badge-warning { background:#fef3c7;color:#92400e; }
tfoot td { border-top:2px solid #e5e7eb;font-size:.75rem; }
.clay-btn-xs { padding:2px 8px !important;font-size:.65rem !important;min-height:0 !important; }
.pilih-produk-btn {
    display:block;width:100%;text-align:left;padding:8px 12px;border:1px solid #e5e7eb;
    border-radius:8px;background:#fff;cursor:pointer;font-size:.8rem;font-weight:600;
    transition:all .15s;
}
.pilih-produk-btn:hover { background:#FFF5F5;border-color:var(--color-primary,#FF6B6B);color:var(--color-primary,#FF6B6B); }
.toggle-group { display:flex;border:1.5px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#f3f4f6; }
.toggle-opt { flex:1;padding:6px 6px;border:none;background:transparent;cursor:pointer;font-size:.72rem;font-weight:700;color:#6b7280;transition:all .15s;letter-spacing:.3px; }
.toggle-opt.active[data-value="MASUK STOK"] { background:#d1fae5;color:#065f46;box-shadow:0 1px 3px rgba(0,0,0,.12);font-weight:800; }
.toggle-opt.active[data-value="BARU PESAN"] { background:#fef3c7;color:#92400e;box-shadow:0 1px 3px rgba(0,0,0,.12);font-weight:800; }
.toggle-opt:not(.active):hover { color:#374151;background:#e5e7eb; }
</style>

<script>
function toggleGroup(id, headerEl) {
    var el = document.getElementById(id);
    var arrow = headerEl.querySelector('.grp-arrow');
    if (el.style.display === 'none') {
        el.style.display = 'block';
        arrow.textContent = '▼';
    } else {
        el.style.display = 'none';
        arrow.textContent = '▶';
    }
}

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

function bukaModalProduk() {
    document.getElementById('modal-produk').style.display = 'flex';
    document.getElementById('cari-produk').value = '';
    document.getElementById('cari-produk').focus();
    filterProduk();
}

function tutupModalProduk() {
    document.getElementById('modal-produk').style.display = 'none';
}

function filterProduk() {
    var q = document.getElementById('cari-produk').value.toLowerCase();
    document.querySelectorAll('.pilih-produk-btn').forEach(function(btn) {
        btn.style.display = btn.getAttribute('data-nama').toLowerCase().includes(q) ? 'block' : 'none';
    });
}

function pilihProduk(btn) {
    tutupModalProduk();
    bukaFormProduk(btn);
}

function bukaFormProduk(btn) {
    document.getElementById('form-produk-id').value = btn.dataset.id;
    document.getElementById('form-produk-nama').textContent = btn.dataset.nama;
    document.getElementById('form-tambah').classList.remove('hidden');
    document.getElementById('form-tambah').scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.getElementById('supplier_id').value = btn.dataset.supplierId || '';
    hitungDariHarga();
    document.querySelector('input[name="qty"]').focus();
}

function pilihKeterangan(btn) {
    document.querySelectorAll('.toggle-opt').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('keterangan').value = btn.dataset.value;
}

function tutupForm() {
    document.getElementById('form-tambah').classList.add('hidden');
}


</script>
@endsection
