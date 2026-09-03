@extends('layouts.app')

@section('title', 'Rekening Koran')
@section('page-title', '📒 Rekening Koran')
@section('page-subtitle', 'Mutasi + saldo berjalan per akun — seperti buku rekening bank')

@push('styles')
<style>
    .rk-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
    .rk-form .rk-field { margin-bottom: 0; }
    .rk-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .rk-form .clay-input, .rk-form select { width: 100%; font-size: .8rem; }
    @media (max-width: 767px) {
        .rk-form { flex-direction: column; align-items: stretch; }
        .rk-form .rk-field select { min-width: 0; }
    }

    .rk-statement { font-family: ui-monospace, Consolas, 'Courier New', monospace; }
    .rk-statement .rk-head {
        text-align: center; border-bottom: 3px double #1e1b2e; padding: 4px 0 10px; margin-bottom: 6px;
    }
    .rk-statement .rk-head h2 { margin: 0 0 4px; font-size: 1.05rem; font-weight: 800; letter-spacing: .1em; color: #1e1b2e; }
    .rk-statement .rk-head p { margin: 0; font-size: .78rem; color: #374151; line-height: 1.7; }
    .rk-statement table { width: 100%; border-collapse: collapse; font-size: .78rem; }
    .rk-statement th {
        border: 1px solid #9ca3af; background: #f3f4f6;
        padding: 5px 8px; font-size: .72rem; letter-spacing: .05em; color: #1e1b2e;
    }
    .rk-statement td { border: 1px solid #d1d5db; padding: 4px 8px; color: #1e1b2e; vertical-align: top; }
    .rk-statement .rk-num { text-align: right; white-space: nowrap; }
    .rk-statement .rk-saldo { font-weight: 700; }
    .rk-statement .rk-awal { background: #fef3c7; font-weight: 800; }
    .rk-statement .rk-total { background: #eef2ff; font-weight: 800; border-top: 2px solid #6366f1; }

    @media (max-width: 639px) {
        .rk-table-wrap { overflow-x: auto; }
        .rk-table-wrap table { min-width: 560px; }
    }
</style>
@endpush

@section('content')

{{-- ── Filter (di atas, satu baris) ─────────────────────────── --}}
<div class="clay-card" style="padding:16px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
        <h2 style="margin:0;font-size:1rem;font-weight:800;">🔎 Filter Rekening Koran</h2>
        @if($account)
        <a href="{{ route('finance.bank-statement.pdf', ['account_id' => $account->id, 'dari' => $dari, 'sampai' => $sampai]) }}"
           class="clay-btn clay-btn-primary" style="text-decoration:none;color:#fff;font-size:.78rem;">
            ⬇ Download PDF
        </a>
        @endif
    </div>
    <div style="font-size:.7rem;color:#9ca3af;margin-bottom:12px;">
        Pilih akun & periode. Mutasi yang tampil hanya transaksi <b>disetujui</b> (approved).
    </div>

    <form method="GET" action="{{ route('finance.bank-statement.index') }}" class="rk-form" id="rk-form">
        <div class="rk-field" style="flex:1;min-width:220px;">
            <label>Akun *</label>
            <select name="account_id" class="clay-input" required onchange="this.form.submit()">
                <option value="">— pilih akun —</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ $account && $account->id === $acc->id ? 'selected' : '' }}>
                        {{ $acc->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="rk-field">
            <label>Periode</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <x-date-range-picker :dari="$dari" :sampai="$sampai" form-id="rk-form" />
            </div>
        </div>
    </form>
</div>

{{-- ── Statement ─────────────────────────────────────────────── --}}
    <div class="clay-card" style="padding:18px;" data-reveal>
        @if(! $account)
            <div style="text-align:center;padding:56px 20px;color:#9ca3af;font-size:.85rem;">
                📒 Pilih akun dulu untuk melihat rekening korannya.
            </div>
        @else
        <div class="rk-table-wrap rk-statement">
            <div class="rk-head">
                <h2>REKENING KORAN</h2>
                <p>
                    <b>{{ strtoupper($account->name) }}</b>
                    @if($account->account_number)
                        ({{ $account->account_number }})
                    @endif
                    <br>
                    PERIODE : {{ strtoupper(\Carbon\Carbon::parse($dari)->translatedFormat('F Y')) }}
                    — {{ strtoupper(\Carbon\Carbon::parse($sampai)->translatedFormat('F Y')) }}
                </p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:90px;">TANGGAL</th>
                        <th>KETERANGAN</th>
                        <th style="width:120px;" class="rk-num">DEBIT</th>
                        <th style="width:120px;" class="rk-num">KREDIT</th>
                        <th style="width:130px;" class="rk-num">SALDO</th>
                    </tr>
                </thead>
                <tbody>
                    @php $saldo = (float) ($saldoAwal ?? 0); @endphp
                    <tr class="rk-awal">
                        <td>—</td>
                        <td>SALDO AWAL</td>
                        <td></td>
                        <td></td>
                        <td class="rk-num rk-saldo">{{ number_format($saldo, 2, ',', '.') }}</td>
                    </tr>

                    @forelse($rows as $row)
                        @php $saldo += $row['kredit'] - $row['debet']; @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row['date'])->format('j/n/Y') }}</td>
                            <td>{{ $row['keterangan'] }}</td>
                            <td class="rk-num">{{ $row['debet'] ? number_format($row['debet'], 2, ',', '.') : '' }}</td>
                            <td class="rk-num">{{ $row['kredit'] ? number_format($row['kredit'], 2, ',', '.') : '' }}</td>
                            <td class="rk-num rk-saldo">{{ number_format($saldo, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:28px;color:#9ca3af;">
                                Tidak ada mutasi pada periode ini.
                            </td>
                        </tr>
                    @endforelse

                    @if(count($rows))
                    <tr class="rk-total">
                        <td colspan="2">TOTAL PERIODE</td>
                        <td class="rk-num">{{ $totalDebet ? number_format($totalDebet, 2, ',', '.') : '' }}</td>
                        <td class="rk-num">{{ $totalKredit ? number_format($totalKredit, 2, ',', '.') : '' }}</td>
                        <td class="rk-num">SALDO AKHIR: {{ number_format($saldo, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <div style="font-size:.66rem;color:#9ca3af;margin-top:10px;">
                Saldo awal = saldo akun saat ini dikurangi seluruh mutasi (approved) sejak awal periode.
            </div>
        </div>
        @endif
    </div>

@endsection
