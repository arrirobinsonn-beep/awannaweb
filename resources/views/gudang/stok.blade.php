@extends('layouts.app')
@section('title','Stok Gudang')
@section('page-title','📦 Stok Gudang')
@section('page-subtitle','Monitoring stok produk secara realtime')

@section('content')
<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th>Kode</th><th>Nama Produk</th><th>Supplier</th><th>Satuan</th>
                <th style="text-align:right;">Harga Beli</th>
                <th style="text-align:right;">Harga Jual</th>
                <th style="text-align:right;">Stok</th>
                <th>Status Stok</th>
            </tr></thead>
            <tbody>
            @forelse($products as $p)
            @php
                $stokClass = $p->stok <= 0 ? 'clay-badge-red' : ($p->stok <= 10 ? 'clay-badge-yellow' : 'clay-badge-green');
                $stokLabel = $p->stok <= 0 ? 'Habis' : ($p->stok <= 10 ? 'Hampir Habis' : 'Tersedia');
            @endphp
            <tr>
                <td><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.72rem;">{{ $p->kode_produk }}</span></td>
                <td style="font-weight:700;font-size:.875rem;">{{ $p->nama_produk }}</td>
                <td style="font-size:.83rem;">{{ $p->supplier->nama_supplier ?? '-' }}</td>
                <td style="font-size:.83rem;">{{ $p->satuan }}</td>
                <td style="text-align:right;font-size:.83rem;">Rp {{ number_format($p->harga_beli,0,',','.') }}</td>
                <td style="text-align:right;font-size:.83rem;font-weight:700;color:var(--color-secondary);">Rp {{ number_format($p->harga_jual,0,',','.') }}</td>
                <td style="text-align:right;font-weight:700;font-size:.95rem;">{{ number_format($p->stok) }}</td>
                <td><span class="clay-badge {{ $stokClass }}">{{ $stokLabel }}</span></td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;padding:48px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">📦</div>
                <p style="color:#9ca3af;">Belum ada data produk</p>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
    <div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $products->links() }}</div>
    @endif
</div>
@endsection
