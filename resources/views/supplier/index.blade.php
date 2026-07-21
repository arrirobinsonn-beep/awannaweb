@extends('layouts.app')
@section('title','Supplier')
@section('page-title','🏭 Data Supplier')
@section('page-subtitle','Kelola semua supplier Awanna')

@section('content')

{{-- Toolbar --}}
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;justify-content:space-between;margin-bottom:18px;" data-reveal>
    <form method="GET" action="{{ route('supplier.index') }}" style="display:flex;flex-wrap:wrap;gap:8px;flex:1;min-width:0;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, kota..." class="clay-input" style="flex:1;min-width:160px;max-width:300px;">
        <select name="status" class="clay-input" style="width:auto;min-width:120px;">
            <option value="">Semua Status</option>
            <option value="aktif"    {{ request('status')==='aktif'    ?'selected':'' }}>Aktif</option>
            <option value="nonaktif" {{ request('status')==='nonaktif' ?'selected':'' }}>Nonaktif</option>
        </select>
        <button type="submit" class="clay-btn clay-btn-secondary">🔍</button>
    </form>
    <a href="{{ route('supplier.create') }}" class="clay-btn clay-btn-primary" data-page-link>＋ Tambah</a>
</div>

{{-- Tabel --}}
<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th>Kode</th><th>Nama Supplier</th><th>PIC</th><th>Kota</th><th>Produk</th><th>Status</th><th style="text-align:right;">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse($suppliers as $s)
            <tr>
                <td><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.72rem;">{{ $s->kode_supplier }}</span></td>
                <td>
                    <div style="font-weight:700;font-size:.875rem;">{{ $s->nama_supplier }}</div>
                    @if($s->email)<div style="font-size:.72rem;color:#9ca3af;">{{ $s->email }}</div>@endif
                </td>
                <td>
                    <div style="font-size:.83rem;">{{ $s->pic_nama ?? '-' }}</div>
                    @if($s->pic_telepon)<div style="font-size:.72rem;color:#9ca3af;">{{ $s->pic_telepon }}</div>@endif
                </td>
                <td style="font-size:.83rem;">{{ $s->kota ?? '-' }}</td>
                <td><span class="clay-badge clay-badge-blue">{{ $s->products_count ?? 0 }} produk</span></td>
                <td><span class="clay-badge {{ $s->status==='aktif'?'clay-badge-green':'clay-badge-red' }}">{{ ucfirst($s->status) }}</span></td>
                <td style="text-align:right;">
                    <div style="display:flex;justify-content:flex-end;gap:6px;">
                        <a href="{{ route('supplier.show',$s) }}" class="clay-btn clay-btn-outline" style="padding:5px 10px;font-size:.72rem;" data-page-link>👁</a>
                        <a href="{{ route('supplier.edit',$s) }}" class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;" data-page-link>✏️</a>
                        <form method="POST" action="{{ route('supplier.destroy',$s) }}" onsubmit="return confirm('Hapus {{ $s->nama_supplier }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="clay-btn clay-btn-danger" style="padding:5px 10px;font-size:.72rem;">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">🏭</div>
                <p style="color:#9ca3af;">Belum ada data supplier</p>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($suppliers->hasPages())
    <div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $suppliers->links() }}</div>
    @endif
</div>
@endsection
