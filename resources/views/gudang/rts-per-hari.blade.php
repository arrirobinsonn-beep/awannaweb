@extends('layouts.app')
@section('title','REKAP RTS PER HARI')
@section('page-title','📊 REKAP RTS PER HARI')
@section('page-subtitle','Rekap Return to Supplier per hari — pivot table')

@section('content')

<p style="font-size:.75rem;color:#6b7280;margin-bottom:12px;">
    Periode: {{ \Carbon\Carbon::parse($bulan.'-01')->translatedFormat('F Y') }}
</p>

<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="GET" action="{{ route('gudang.rts-per-hari') }}"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div>
            <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151;">Bulan</label>
            <input type="month" name="bulan" value="{{ $bulan }}" class="clay-input" style="padding:6px 10px;">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary">Filter</button>
        <a href="{{ route('gudang.rts-per-hari') }}" class="clay-btn clay-btn-outline">Reset</a>

    </form>
</div>

<div class="clay-card" style="padding:0;overflow-x:auto;" data-reveal>
    <table class="clay-table" style="font-size:.7rem;">
        <thead>
            <tr>
                <th rowspan="2" style="min-width:30px;">NO</th>
                <th rowspan="2" style="min-width:200px;text-align:left;">NAMA BARANG</th>
                <th rowspan="2" style="min-width:70px;">HPP</th>
                @for ($d = 1; $d <= $maxDay; $d++)
                <th colspan="2" style="min-width:60px;">{{ $d }}</th>
                @endfor
                <th colspan="2" style="min-width:80px;color:var(--color-primary,#FF6B6B);">TOTAL</th>
            </tr>
            <tr>
                @for ($d = 1; $d <= $maxDay; $d++)
                <th style="font-size:.6rem;color:#6b7280;">JML</th>
                <th style="font-size:.6rem;color:#6b7280;">VALUE</th>
                @endfor
                <th style="font-size:.6rem;color:var(--color-primary,#FF6B6B);">JML</th>
                <th style="font-size:.6rem;color:var(--color-primary,#FF6B6B);">VALUE</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="text-align:left;font-weight:600;">{{ $item['product']->nama_produk }}</td>
                <td>{{ $item['hpp'] > 0 ? number_format($item['hpp'],0,',','.') : '-' }}</td>
                @for ($d = 1; $d <= $maxDay; $d++)
                <td class="text-right">{{ $item['daily'][$d]['qty'] > 0 ? number_format($item['daily'][$d]['qty'],0,',','.') : '' }}</td>
                <td class="text-right">{{ $item['daily'][$d]['value'] > 0 ? number_format($item['daily'][$d]['value'],0,',','.') : '' }}</td>
                @endfor
                <td class="text-right" style="font-weight:700;color:var(--color-primary,#FF6B6B);">
                    {{ $item['total_qty'] > 0 ? number_format($item['total_qty'],0,',','.') : '' }}
                </td>
                <td class="text-right" style="font-weight:700;color:var(--color-primary,#FF6B6B);">
                    {{ $item['total_value'] > 0 ? number_format($item['total_value'],0,',','.') : '' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ 3 + $maxDay * 2 + 2 }}" style="text-align:center;padding:32px;color:#9ca3af;">Belum ada data produk aktif.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background:#f8fafc;font-weight:800;border-top:2px solid #e5e7eb;">
                <td colspan="2" style="text-align:right;font-size:.75rem;">TOTAL</td>
                <td></td>
                @for ($d = 1; $d <= $maxDay; $d++)
                <td class="text-right" style="border-left:1px solid #f1f5f9;">
                    {{ $grandDaily[$d]['qty'] > 0 ? number_format($grandDaily[$d]['qty'],0,',','.') : '' }}
                </td>
                <td class="text-right">
                    {{ $grandDaily[$d]['value'] > 0 ? number_format($grandDaily[$d]['value'],0,',','.') : '' }}
                </td>
                @endfor
                <td class="text-right" style="color:var(--color-primary,#FF6B6B);font-size:.8rem;">
                    {{ number_format($grandTotalQty,0,',','.') }}
                </td>
                <td class="text-right" style="color:var(--color-primary,#FF6B6B);font-size:.8rem;">
                    {{ number_format($grandTotalValue,0,',','.') }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<style>
.text-right { text-align:right; }
.clay-table th, .clay-table td { white-space:nowrap; padding:4px 6px; }
.clay-table thead th { background:#f8fafc; position:sticky; top:0; z-index:1; }
.clay-table tfoot td { font-size:.7rem; }
</style>
@endsection