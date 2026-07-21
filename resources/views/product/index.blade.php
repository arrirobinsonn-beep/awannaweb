@extends('layouts.app')
@section('title','Produk')
@section('page-title','📦 Data Produk')
@section('page-subtitle','Kelola semua produk Awanna')

@section('content')
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;justify-content:space-between;margin-bottom:18px;" data-reveal>
    <form method="GET" action="{{ route('product.index') }}" style="display:flex;flex-wrap:wrap;gap:8px;flex:1;min-width:0;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, kategori..." class="clay-input" style="flex:1;min-width:150px;max-width:260px;">
        <select name="kategori" class="clay-input" style="width:auto;min-width:120px;">
            <option value="">Semua Kategori</option>
            @foreach($kategoris as $k)<option value="{{ $k }}" {{ request('kategori')===$k?'selected':'' }}>{{ $k }}</option>@endforeach
        </select>
        <select name="status" class="clay-input" style="width:auto;min-width:110px;">
            <option value="">Semua Status</option>
            <option value="aktif"    {{ request('status')==='aktif'    ?'selected':'' }}>Aktif</option>
            <option value="nonaktif" {{ request('status')==='nonaktif' ?'selected':'' }}>Nonaktif</option>
        </select>
        <button type="submit" class="clay-btn clay-btn-secondary">🔍</button>
    </form>
    <a href="{{ route('product.create') }}" class="clay-btn clay-btn-primary" data-page-link>＋ Tambah</a>
</div>

<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th>Kode</th><th>Nama Produk</th><th>Supplier</th><th>Kategori</th>
                <th style="text-align:right;">Harga Jual</th><th style="text-align:right;">Stok</th>
                <th>Margin</th><th>Status</th><th style="text-align:right;">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse($products as $p)
            <tr>
                <td><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.72rem;">{{ $p->kode_produk }}</span></td>
                <td>
                    <div style="font-weight:700;font-size:.875rem;">{{ $p->nama_produk }}</div>
                    @if($p->deskripsi)<div style="font-size:.72rem;color:#9ca3af;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Str::limit($p->deskripsi,50) }}</div>@endif
                </td>
                <td style="font-size:.83rem;">{{ $p->supplier->nama_supplier ?? '-' }}</td>
                <td>@if($p->kategori)<span class="clay-badge clay-badge-purple" style="font-size:.72rem;">{{ $p->kategori }}</span>@else<span style="color:#d1d5db;">-</span>@endif</td>
                <td style="text-align:right;font-weight:700;color:var(--color-secondary);font-size:.83rem;">Rp {{ number_format($p->harga_jual,0,',','.') }}</td>
                <td style="text-align:right;"><span class="clay-badge {{ $p->stok>20?'clay-badge-green':($p->stok>0?'clay-badge-yellow':'clay-badge-red') }}">{{ number_format($p->stok) }} {{ $p->satuan }}</span></td>
                <td><span class="clay-badge clay-badge-blue">{{ $p->margin }}%</span></td>
                <td><span class="clay-badge {{ $p->status==='aktif'?'clay-badge-green':'clay-badge-red' }}">{{ ucfirst($p->status) }}</span></td>
                <td style="text-align:right;">
                    <div style="display:flex;justify-content:flex-end;gap:6px;">
                        <a href="{{ route('product.edit',$p) }}" class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;" data-page-link>✏️</a>
                        <form method="POST" action="{{ route('product.destroy',$p) }}" onsubmit="return confirm('Hapus {{ $p->nama_produk }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="clay-btn clay-btn-danger" style="padding:5px 10px;font-size:.72rem;">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">📦</div>
                <p style="color:#9ca3af;">Belum ada data produk</p>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())<div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $products->links() }}</div>@endif
</div>
@endsection
