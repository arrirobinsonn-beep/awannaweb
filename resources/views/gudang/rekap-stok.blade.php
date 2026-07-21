@extends('layouts.app')
@section('title','Rekap Stok Gudang')
@section('page-title','📊 REKAP STOK BARANG (GUDANG KUNINGAN)')
@section('page-subtitle','Input real stok fisik — selisih otomatis terhitung')

@section('content')

<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="GET" action="{{ route('gudang.rekap-stok') }}"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div>
            <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151;">Periode</label>
            <input type="month" name="bulan" value="{{ $bulan }}" class="clay-input" style="padding:6px 10px;">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary">Filter</button>
        <a href="{{ route('gudang.rekap-stok') }}" class="clay-btn clay-btn-outline">Reset</a>
    </form>
</div>

<div class="clay-card" style="padding:0;overflow-x:auto;" data-reveal>
    <form method="POST" action="{{ route('gudang.rekap-stok.bulk') }}">
        @csrf
        <input type="hidden" name="bulan" value="{{ $bulan }}">

        <table class="clay-table" style="font-size:.68rem;">
            <thead>
                <tr>
                    <th rowspan="2" style="min-width:30px;">NO</th>
                    <th rowspan="2" style="min-width:130px;">NAMA BARANG</th>
                    <th rowspan="2" style="min-width:45px;">SIZE</th>
                    <th rowspan="2" style="min-width:55px;">STOK AWAL</th>
                    <th colspan="5" style="min-width:40px;">MOVEMENT</th>
                    <th rowspan="2" style="min-width:65px;">STOCK AKHIR</th>
                    <th rowspan="2" style="min-width:65px;">REAL STOK</th>
                    <th rowspan="2" style="min-width:55px;">SELISIH</th>
                    <th rowspan="2" style="min-width:60px;">HPP NEW</th>
                    <th rowspan="2" style="min-width:70px;">VALUE IN</th>
                    <th rowspan="2" style="min-width:85px;">VALUE S. AKHIR</th>
                    <th rowspan="2" style="min-width:85px;">VALUE REAL STOK</th>
                    <th rowspan="2" style="min-width:80px;">KETERANGAN</th>
                </tr>
                <tr>
                    <th style="min-width:35px;color:#059669;">IN</th>
                    <th style="min-width:35px;color:#059669;">RTS</th>
                    <th style="min-width:40px;color:#059669;">REPAIR</th>
                    <th style="min-width:40px;color:#dc2626;">RUSAK</th>
                    <th style="min-width:35px;color:#dc2626;">OUT</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $i => $item)
                @php
                    $isDead = $item['keterangan'] === 'DEAD STOK';
                @endphp
                <tr style="{{ $isDead ? 'opacity:.7;' : '' }}">
                    <td style="text-align:center;">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;white-space:normal;word-break:break-word;">{{ $item['product']->nama_produk }}</td>
                    <td style="text-align:center;">{{ $item['satuan'] }}</td>
                    <td class="text-right">{{ number_format($item['stok_awal']) }}</td>
                    <td class="text-right" style="color:#059669;">{{ $item['in'] > 0 ? number_format($item['in']) : '-' }}</td>
                    <td class="text-right" style="color:#059669;">{{ $item['rts'] > 0 ? number_format($item['rts']) : '-' }}</td>
                    <td class="text-right" style="color:#059669;">{{ $item['repair'] > 0 ? number_format($item['repair']) : '-' }}</td>
                    <td class="text-right" style="color:#dc2626;">{{ $item['rusak'] > 0 ? '-'.number_format($item['rusak']) : '-' }}</td>
                    <td class="text-right" style="color:#dc2626;">{{ $item['out'] > 0 ? '-'.number_format($item['out']) : '-' }}</td>
                    <td class="text-right" style="font-weight:700;">{{ number_format($item['stok_akhir']) }}</td>
                    <td class="text-right" style="font-weight:600;">
                        <input type="number" name="real_stok[{{ $item['product']->id }}]"
                               class="clay-input real-stok-input"
                               value="{{ $item['real_stok'] > 0 ? $item['real_stok'] : '' }}"
                               min="0" style="width:65px;padding:2px 4px;font-size:.68rem;text-align:right;">
                    </td>
                    <td class="text-right" style="font-weight:700;color:{{ $item['selisih'] != 0 ? '#dc2626' : '#9ca3af' }};">
                        {{ $item['selisih'] != 0 ? number_format($item['selisih']) : '-' }}
                    </td>
                    <td class="text-right" style="font-weight:600;">
                        {{ $item['hpp'] !== null ? number_format($item['hpp'],0,',','.') : '-' }}
                    </td>
                    <td class="text-right" style="font-weight:600;">
                        {{ $item['value_in'] > 0 ? number_format($item['value_in'],0,',','.') : '-' }}
                    </td>
                    <td class="text-right" style="font-weight:600;">
                        {{ $item['value_s_akhir'] > 0 ? number_format($item['value_s_akhir'],0,',','.') : '-' }}
                    </td>
                    <td class="text-right" style="font-weight:600;">
                        {{ $item['value_real'] > 0 ? number_format($item['value_real'],0,',','.') : '-' }}
                    </td>
                    <td style="font-size:.65rem;{{ $isDead ? 'color:#9ca3af;' : '' }}">
                        {{ $item['keterangan'] }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="17" style="text-align:center;padding:32px;color:#9ca3af;">Belum ada produk aktif.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc;font-weight:800;border-top:2px solid #e5e7eb;font-size:.72rem;">
                    <td colspan="3" style="text-align:right;">TOTAL</td>
                    <td class="text-right">{{ number_format($grandTotals['stok_awal']) }}</td>
                    <td class="text-right" style="color:#059669;">{{ number_format($grandTotals['in']) }}</td>
                    <td class="text-right" style="color:#059669;">{{ number_format($grandTotals['rts']) }}</td>
                    <td class="text-right" style="color:#059669;">{{ number_format($grandTotals['repair']) }}</td>
                    <td class="text-right" style="color:#dc2626;">{{ number_format($grandTotals['rusak']) }}</td>
                    <td class="text-right" style="color:#dc2626;">{{ number_format($grandTotals['out']) }}</td>
                    <td class="text-right">{{ number_format($grandTotals['stok_akhir']) }}</td>
                    <td class="text-right">{{ number_format($grandTotals['real_stok']) }}</td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ number_format($grandTotals['value_in'],0,',','.') }}</td>
                    <td class="text-right">{{ number_format($grandTotals['value_s_akhir'],0,',','.') }}</td>
                    <td class="text-right">{{ number_format($grandTotals['value_real'],0,',','.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div style="padding:10px 14px;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:.7rem;color:#6b7280;">* Input REAL STOK sesuai stok fisik di gudang, SELISIH otomatis terhitung.</span>
            <button type="submit" class="clay-btn clay-btn-primary" style="font-size:.8rem;" id="btn-save-recap">
                💾 Simpan Real Stok
            </button>
        </div>
    </form>
</div>

<style>
.text-right { text-align:right; }
.clay-table th, .clay-table td { white-space:nowrap; }
.clay-table td { padding:3px 6px; }
tfoot td { border-top:2px solid #e5e7eb; }
.real-stok-input:focus { border-color:var(--color-primary,#FF6B6B);outline:none; }
</style>
@endSection
