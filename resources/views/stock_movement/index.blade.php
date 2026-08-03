@extends('layouts.app')
@section('title','Jurnal Stok')
@section('page-title','📊 Jurnal Stok')
@section('page-subtitle','Riwayat semua pergerakan stok masuk & keluar')

@section('content')

{{-- Filter --}}
<div class="clay-card" style="padding:0;margin-bottom:20px;" data-reveal>
    <form method="GET" action="{{ route('stock-movement.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:16px;">
        <select name="product_id" class="clay-input" style="min-width:200px;flex:1;">
            <option value="">Semua Produk</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->nama_produk }}</option>
            @endforeach
        </select>
        <select name="type" class="clay-input">
            <option value="">Semua Tipe</option>
            <option value="in" @selected(request('type') === 'in')>Masuk (In)</option>
            <option value="out" @selected(request('type') === 'out')>Keluar (Out)</option>
        </select>
        <select name="bulan" class="clay-input">
            <option value="">Semua Bulan</option>
            @foreach($monthList as $b)
                <option value="{{ $b }}" @selected(request('bulan') === $b)>{{ $b }}</option>
            @endforeach
        </select>
        <button class="clay-btn clay-btn-primary" type="submit">🔍 Filter</button>
        <a href="{{ route('stock-movement.index') }}" class="clay-btn">Reset</a>
    </form>
</div>

{{-- Tabel jurnal --}}
<div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table" style="min-width:900px;">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Tipe</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Sumber</th>
                    <th>Keterangan</th>
                    <th>Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    @php
                        $badge = $m->type === 'in'
                            ? 'background:#dcfce7;color:#15803d;'
                            : 'background:#fee2e2;color:#b91c1c;';
                    @endphp
                    <tr>
                        <td class="sel-nowrap">{{ $m->date->format('d/m/Y') }}</td>
                        <td style="font-weight:600;">{{ $m->product->nama_produk }}</td>
                        <td>
                            <span style="font-weight:800;font-size:.72rem;padding:2px 10px;border-radius:8px;{{ $badge }}">
                                {{ $m->type === 'in' ? 'MASUK' : 'KELUAR' }}
                            </span>
                        </td>
                        <td>{{ number_format($m->quantity,0,',','.') }}</td>
                        <td>{{ $m->unit_price !== null ? 'Rp '.number_format((float)$m->unit_price,0,',','.') : '-' }}</td>
                        <td>{{ ucfirst($m->reference) }}</td>
                        <td style="font-size:.8rem;">{{ $m->note ?? '-' }}</td>
                        <td>{{ $m->creator->nama ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:48px;color:#9ca3af;">Belum ada jurnal stok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px;">{{ $movements->links() }}</div>

@endsection
