@extends('layouts.app')
@section('title','Barang Masuk')
@section('page-title','📥 Barang Masuk (Pembelian)')
@section('page-subtitle','Catat stok masuk & perbarui HPP produk')

@section('content')
@if(session('success'))
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#d1fae5;color:#065f46;font-weight:600;border-radius:8px;">
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#fee2e2;color:#991b1b;font-weight:600;border-radius:8px;">
    @foreach($errors->all() as $e)<div>⚠ {{ $e }}</div>@endforeach
</div>
@endif

{{-- Form barang masuk --}}
<div class="clay-card" style="padding:20px 24px;margin-bottom:20px;" data-reveal>
    <h2 style="margin:0 0 4px;font-size:1.05rem;font-weight:800;">➕ Ajukan Pembelian</h2>
    <div style="font-size:.75rem;color:#9ca3af;margin-bottom:14px;">Pengajuan akan dikirim ke tim keuangan untuk disetujui. Stok & HPP diperbarui setelah disetujui.</div>

    <form method="POST" action="{{ route('purchase.store') }}">
        @csrf
        <div class="form-grid">
            <div>
                <label class="field-label">TANGGAL</label>
                <input type="date" name="date" required value="{{ old('date', now()->format('Y-m-d')) }}" class="clay-input">
            </div>
            <div>
                <label class="field-label">SUPPLIER</label>
                <select name="supplier_id" class="clay-input">
                    <option value="">— Pilih Supplier —</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->nama_supplier }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column: span 2;">
                <label class="field-label">VARIAN PRODUK</label>
                <select name="product_variant_id" id="purchase-variant" required class="clay-input">
                    <option value="">— Pilih Produk / Varian —</option>
                    @foreach($products as $p)
                        <optgroup label="{{ $p->name }}" data-primary-wh="{{ $p->primaryInventoryId() ?? '' }}">
                            @foreach($p->variants as $v)
                                <option value="{{ $v->id }}" @selected(old('product_variant_id') == $v->id)>
                                    {{ $v->name }} {{ (float)$v->power > 0 ? '(+'.number_format($v->power,2,',','.').')' : '' }} — stok {{ $v->stock }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div style="grid-column: span 2;">
                <label class="field-label">GUDANG TUJUAN</label>
                <select name="inventory_id" id="purchase-inventory" required class="clay-input">
                    <option value="">— Pilih Gudang —</option>
                    @foreach($inventories as $inv)
                        <option value="{{ $inv->id }}" @selected(old('inventory_id') == $inv->id)>{{ $inv->name }}</option>
                    @endforeach
                </select>
                <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Stok masuk dicatat ke gudang ini (stok per gudang).</div>
            </div>
            <div>
                <label class="field-label">JUMLAH (QTY)</label>
                <input type="number" name="quantity" required min="1" value="{{ old('quantity') }}" class="clay-input">
            </div>
            <div>
                <label class="field-label">HARGA SATUAN</label>
                <input type="number" name="unit_price" required min="0" step="0.01" value="{{ old('unit_price') }}" class="clay-input">
            </div>
            <div>
                <label class="field-label">ONGKIR</label>
                <input type="number" name="shipping_cost" min="0" step="0.01" value="{{ old('shipping_cost', 0) }}" class="clay-input">
            </div>
            <div style="grid-column: span 2;">
                <label class="field-label">KETERANGAN</label>
                <input type="text" name="note" maxlength="255" value="{{ old('note') }}" class="clay-input" placeholder="Opsional">
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" class="clay-btn clay-btn-primary">📤 Kirim Pengajuan</button>
        </div>
    </form>
</div>

{{-- Filter --}}
<div class="clay-card" style="padding:0;margin-bottom:20px;" data-reveal>
    <form method="GET" action="{{ route('purchase.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:16px;">
        <select name="variant_id" class="clay-input" style="min-width:200px;flex:1;">
            <option value="">Semua Produk / Varian</option>
            @foreach($products as $p)
                <optgroup label="{{ $p->name }}">
                    @foreach($p->variants as $v)
                        <option value="{{ $v->id }}" @selected(request('variant_id') == $v->id)>{{ $v->name }} {{ (float)$v->power > 0 ? '(+'.number_format($v->power,2,',','.').')' : '' }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <select name="supplier_id" class="clay-input" style="min-width:180px;">
            <option value="">Semua Supplier</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->nama_supplier }}</option>
            @endforeach
        </select>
        <select name="inventory_id" class="clay-input" style="min-width:160px;">
            <option value="">Semua Gudang</option>
            @foreach($inventories as $inv)
                <option value="{{ $inv->id }}" @selected(request('inventory_id') == $inv->id)>{{ $inv->name }}</option>
            @endforeach
        </select>
        <select name="bulan" class="clay-input">
            <option value="">Semua Bulan</option>
            @foreach($monthList as $b)
                <option value="{{ $b }}" @selected(request('bulan') === $b)>{{ $b }}</option>
            @endforeach
        </select>
        <select name="status" class="clay-input">
            <option value="">Semua Status</option>
            <option value="pending" @selected(request('status') === 'pending')">⏳ Menunggu</option>
            <option value="approved" @selected(request('status') === 'approved')">✅ Disetujui</option>
            <option value="rejected" @selected(request('status') === 'rejected')">❌ Ditolak</option>
        </select>
        <button class="clay-btn clay-btn-primary" type="submit">🔍 Filter</button>
        <a href="{{ route('purchase.index') }}" class="clay-btn">Reset</a>
    </form>
</div>

{{-- Tabel riwayat --}}
<div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table" style="min-width:1000px;">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Produk / Varian</th>
                    <th>Gudang</th>
                    <th>Supplier</th>
                    <th>Qty</th>
                    <th>Harga Satuan</th>
                    <th>Total</th>
                    <th style="text-align:center;">Status</th>
                    <th>Keterangan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $pu)
                    @php
                        $total = $pu->quantity * $pu->unit_price + $pu->shipping_cost;
                        $puStatusMap = [
                            'pending'  => ['class' => 'clay-badge-yellow', 'label' => '⏳ Menunggu'],
                            'approved' => ['class' => 'clay-badge-green',  'label' => '✅ Disetujui'],
                            'rejected' => ['class' => 'clay-badge-red',    'label' => '❌ Ditolak'],
                        ];
                        $ps = $puStatusMap[$pu->status] ?? ['class' => 'clay-badge-gray', 'label' => $pu->status];
                    @endphp
                    <tr style="{{ $pu->status === 'pending' ? 'background:#fffbeb;' : ($pu->status === 'rejected' ? 'opacity:.7;' : '') }}">
                        <td class="sel-nowrap">{{ $pu->date->format('d/m/Y') }}</td>
                        <td style="font-weight:600;">
                            {{ $pu->variant?->product?->name ?? '-' }}
                            <div style="font-size:.72rem;color:#9ca3af;">{{ $pu->variant?->name }} {{ (float)($pu->variant?->power ?? 0) > 0 ? '(+'.number_format($pu->variant->power,2,',','.').')' : '' }}</div>
                        </td>
                        <td>
                            @if($pu->inventory)
                                <span class="clay-badge clay-badge-blue">🏭 {{ $pu->inventory->name }}</span>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td>{{ $pu->supplier->nama_supplier ?? '-' }}</td>
                        <td>{{ number_format($pu->quantity,0,',','.') }}</td>
                        <td>Rp {{ number_format((float)$pu->unit_price,0,',','.') }}</td>
                        <td style="font-weight:700;">Rp {{ number_format($total,0,',','.') }}</td>
                        <td style="text-align:center;">
                            <span class="clay-badge {{ $ps['class'] }}" style="font-size:.7rem;">{{ $ps['label'] }}</span>
                            @if($pu->rejection_note)
                            <div style="font-size:.68rem;color:#991b1b;margin-top:3px;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ e($pu->rejection_note) }}">
                                {{ Str::limit($pu->rejection_note, 35) }}
                            </div>
                            @endif
                        </td>
                        <td style="font-size:.8rem;">{{ $pu->note ?? '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('purchase.destroy',$pu) }}"
                                  onsubmit="return confirm('Hapus pembelian ini?{{ $pu->status === 'approved' ? ' Stok & HPP akan dikembalikan.' : '' }}')">
                                @csrf @method('DELETE')
                                <button class="clay-btn clay-btn-sm clay-btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:48px;color:#9ca3af;">Belum ada barang masuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px;">{{ $purchases->links() }}</div>

<style>
.field-label { display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151; }
</style>
@endsection

@push('scripts')
<script>
// Pilih varian → isi otomatis GUDANG TUJUAN dengan gudang utama produknya
document.getElementById('purchase-variant').addEventListener('change', function() {
    var sel = this;
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    var group = opt.parentElement;
    var primary = group ? (group.getAttribute('data-primary-wh') || '') : '';
    var inv = document.getElementById('purchase-inventory');
    if (primary) {
        inv.value = primary;
    }
});
</script>
@endpush
