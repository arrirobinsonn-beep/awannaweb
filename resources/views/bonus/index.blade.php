@extends('layouts.app')

@section('title', 'Bonus Penjualan')
@section('page-title', '🎁 Rekap Bonus Penjualan')
@section('page-subtitle', 'Monitoring bonus advertiser real-time dari data spending')

@push('styles')
<style>
    .bc-header {
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .bc-period { display: flex; align-items: center; gap: 8px; }
    .bc-period select { font-size: .8rem; padding: 6px 10px; border: 1.5px solid #e5e7eb; border-radius: 10px; background: #fff; }

    .bc-stats {
        margin-bottom: 16px;
    }
    .bc-stat {
        display: flex; align-items: center; gap: 12px;
        background: #fff; border: 1.5px solid #f0e9e4; border-radius: 16px;
        padding: 14px 16px;
    }
    .bc-stat-ic {
        width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
    }
    .bc-stat b { display: block; font-size: 1rem; color: #1e1b2e; line-height: 1.2; }
    .bc-stat small { font-size: .66rem; color: #9ca3af; font-weight: 600; }

    .bc-empty { text-align: center; padding: 56px 24px; color: #9ca3af; }
    .bc-empty-icon { font-size: 2rem; margin-bottom: 10px; }

    .bc-table-wrap { overflow-x: auto; }
    .bc-table-wrap .clay-table { min-width: 1050px; }

    .bc-amount-in { color: #15803d; font-weight: 800; }
    .bc-amount-zero { color: #9ca3af; }
    .bc-disbursed-note { font-size: .64rem; color: #065f46; margin-top: 2px; }
</style>
@endpush

@section('content')

{{-- Period selector --}}
<div class="clay-card" style="padding:16px;" data-reveal>
    <div class="bc-header">
        <div>
            <div style="font-size:1rem;font-weight:800;color:#1e1b2e;">📋 Rekap Bonus Penjualan</div>
            <div style="font-size:.7rem;color:#9ca3af;margin-top:2px;">Data real-time dari spending_harians per advertiser.</div>
        </div>
        <div class="bc-period">
            <form method="GET" action="{{ route('finance.bonus.index') }}">
                <select name="period" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        @php $p = now()->year.'-'.str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                        <option value="{{ $p }}" {{ $period === $p ? 'selected' : '' }}>
                            {{ now()->setMonth($m)->translatedFormat('F') }} {{ now()->year }}
                        </option>
                    @endfor
                </select>
            </form>
        </div>
    </div>
</div>

{{-- Summary cards --}}
@if($bonuses->isNotEmpty())
<div class="bc-stats" data-reveal>
    <div class="bc-stat">
        <span class="bc-stat-ic" style="background:#fef3c7;color:#b45309;">🧮</span>
        <div>
            <b>Rp {{ number_format((float) $totals['potensi_bonus'], 0, ',', '.') }}</b>
            <small>Total Potensi Bonus</small>
        </div>
    </div>
</div>
@endif

{{-- Table --}}
<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div style="padding:12px 18px;font-weight:800;font-size:.9rem;border-bottom:1px solid rgba(0,0,0,.06);">
        📊 Rekap Bonus — {{ now()->createFromFormat('Y-m', $period)->translatedFormat('F Y') }}
    </div>

    @if($bonuses->isEmpty())
    <div class="bc-empty">
        <div class="bc-empty-icon">📭</div>
        <div>Belum ada data advertiser untuk periode ini.</div>
    </div>
    @else
    <div class="bc-table-wrap">
        <table class="clay-table">
            <thead>
                <tr>
                    <th style="text-align:center;">No</th>
                    <th>Nama Advertiser</th>
                    <th style="text-align:right;">Spending</th>
                    <th style="text-align:right;">Lead</th>
                    <th style="text-align:right;">Paid</th>
                    <th style="text-align:right;">Paid Ratio</th>
                    <th style="text-align:right;">Adjustment</th>
                    <th style="text-align:right;">CPA Paid</th>
                    <th style="text-align:right;">Margin</th>
                    <th style="text-align:right;">Pengali</th>
                    <th style="text-align:right;">Potensi Bonus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bonuses as $i => $b)
                <tr>
                    <td style="text-align:center;font-size:.75rem;">{{ $i + 1 }}</td>
                    <td>
                        <b style="font-size:.78rem;">{{ $b->user->display_name }}</b>
                    </td>
                    <td style="text-align:right;font-size:.75rem;">{{ $b->spending > 0 ? 'Rp '.number_format((float) $b->spending, 0, ',', '.') : '—' }}</td>
                    <td style="text-align:right;font-size:.75rem;">{{ $b->lead > 0 ? number_format($b->lead) : '—' }}</td>
                    <td style="text-align:right;font-size:.75rem;font-weight:700;">{{ $b->paid > 0 ? number_format($b->paid) : '—' }}</td>
                    <td style="text-align:right;font-size:.75rem;">{{ $b->lead > 0 ? number_format($b->paid_ratio * 100, 2).'%' : '0.00%' }}</td>
                    <td style="text-align:right;font-size:.75rem;">{{ $b->lead > 0 ? number_format($b->adjustment * 100, 2).'%' : '0.00%' }}</td>
                    <td style="text-align:right;font-size:.75rem;">{{ $b->paid > 0 ? 'Rp '.number_format((float) $b->cpa_paid, 0, ',', '.') : '—' }}</td>
                    <td style="text-align:right;font-size:.75rem;">{{ $b->margin > 0 ? 'Rp '.number_format((float) $b->margin, 0, ',', '.') : '—' }}</td>
                    <td style="text-align:right;font-size:.75rem;">{{ $b->pengali > 0 ? number_format((float) $b->pengali, 0, ',', '.') : '—' }}</td>
                    <td style="text-align:right;font-weight:800;font-size:.82rem;" class="{{ $b->potensi_bonus > 0 ? 'bc-amount-in' : 'bc-amount-zero' }}">
                        {{ $b->potensi_bonus > 0 ? 'Rp '.number_format((float) $b->potensi_bonus, 0, ',', '.') : 'Rp 0' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f9fafb;font-weight:800;">
                    <td colspan="2" style="font-size:.78rem;">TOTAL</td>
                    <td style="text-align:right;font-size:.78rem;">Rp {{ number_format((float) $totals['spending'], 0, ',', '.') }}</td>
                    <td style="text-align:right;font-size:.78rem;">{{ number_format($totals['lead']) }}</td>
                    <td style="text-align:right;font-size:.78rem;">{{ number_format($totals['paid']) }}</td>
                    <td style="text-align:right;font-size:.78rem;">{{ $totals['lead'] > 0 ? number_format($totals['paid'] / $totals['lead'] * 100, 2).'%' : '0.00%' }}</td>
                    <td style="text-align:right;font-size:.78rem;">{{ $totals['lead'] > 0 ? number_format($totals['paid'] / $totals['lead'] * 100 * 7.5, 2).'%' : '0.00%' }}</td>
                    <td style="text-align:right;font-size:.78rem;">{{ $totals['paid'] > 0 ? 'Rp '.number_format((float) $totals['spending'] / $totals['paid'], 0, ',', '.') : '—' }}</td>
                    <td style="text-align:right;font-size:.78rem;">{{ '—' }}</td>
                    <td style="text-align:right;font-size:.78rem;">{{ '—' }}</td>
                    <td style="text-align:right;font-size:.82rem;" class="bc-amount-in">Rp {{ number_format((float) $totals['potensi_bonus'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

@endsection
