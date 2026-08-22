<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekening Koran — {{ $account->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px; color: #111; margin: 24px;
        }
        .head { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
        .head h1 { font-size: 16px; margin: 0 0 6px; letter-spacing: 2px; }
        .head p { margin: 0; line-height: 1.8; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #666; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #eee; font-size: 10px; letter-spacing: 1px; }
        .num { text-align: right; white-space: nowrap; }
        .saldo { font-weight: bold; }
        .awal { background: #fdf3cd; font-weight: bold; }
        .total { background: #e4e9f8; font-weight: bold; }
        .foot { margin-top: 14px; font-size: 10px; color: #555; text-align: right; }
    </style>
</head>
<body>

    <div class="head">
        <h1>REKENING KORAN</h1>
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
                <th style="width:14%;">TANGGAL</th>
                <th>KETERANGAN</th>
                <th style="width:17%;" class="num">DEBIT</th>
                <th style="width:17%;" class="num">KREDIT</th>
                <th style="width:19%;" class="num">SALDO</th>
            </tr>
        </thead>
        <tbody>
            @php $saldo = (float) ($saldoAwal ?? 0); @endphp
            <tr class="awal">
                <td>—</td>
                <td>SALDO AWAL</td>
                <td></td>
                <td></td>
                <td class="num saldo">{{ number_format($saldo, 2, ',', '.') }}</td>
            </tr>

            @forelse($rows as $row)
                @php $saldo += $row['kredit'] - $row['debet']; @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('j/n/Y') }}</td>
                    <td>{{ $row['keterangan'] }}</td>
                    <td class="num">{{ $row['debet'] ? number_format($row['debet'], 2, ',', '.') : '' }}</td>
                    <td class="num">{{ $row['kredit'] ? number_format($row['kredit'], 2, ',', '.') : '' }}</td>
                    <td class="num saldo">{{ number_format($saldo, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;">Tidak ada mutasi pada periode ini.</td>
                </tr>
            @endforelse

            @if(count($rows))
            <tr class="total">
                <td colspan="2">TOTAL PERIODE</td>
                <td class="num">{{ $totalDebet ? number_format($totalDebet, 2, ',', '.') : '' }}</td>
                <td class="num">{{ $totalKredit ? number_format($totalKredit, 2, ',', '.') : '' }}</td>
                <td class="num">SALDO AKHIR: {{ number_format($saldo, 2, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="foot">Dicetak {{ now()->translatedFormat('d M Y H:i') }} — Sistem Keuangan Awanna</div>

</body>
</html>