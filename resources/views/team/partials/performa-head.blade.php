{{-- ── Header tabel performa (dipakai oleh team/performance.blade.php) ── --}}
<thead>
    <tr style="position:sticky;top:0;z-index:3;">
        <th class="cs-name-sticky" style="background:#4472C4;color:#fff;padding:8px 14px;text-align:left;font-weight:700;font-size:.8rem;min-width:160px;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">
            CS / TANGGAL
        </th>
        @foreach($allDates as $date)
        <th colspan="3" style="background:#4472C4;color:#fff;padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;min-width:80px;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">
            {{ \Carbon\Carbon::parse($date)->format('d') }}
            <span style="display:block;font-weight:400;font-size:.65rem;opacity:.8;">
                {{ \Carbon\Carbon::parse($date)->translatedFormat('D') }}
            </span>
        </th>
        @endforeach
        {{-- Total kolom sticky kanan (colspan=3 — nempel di tepi kanan) --}}
        <th colspan="3" class="cs-total-ratio" style="background:#0d9488;color:#fff;padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;width:240px;min-width:240px;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">
            📊 TOTAL
        </th>
    </tr>
    <tr style="position:sticky;top:38px;z-index:3;">
        <th class="cs-name-sticky" style="background:#5B9BD5;color:#fff;padding:6px 14px;text-align:left;font-weight:600;font-size:.72rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">
            {{ $csCount }} CS
        </th>
        @foreach($allDates as $date)
        <th style="background:#5B9BD5;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">LEAD</th>
        <th style="background:#5B9BD5;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">PAID</th>
        <th style="background:#5B9BD5;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.68rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">RATIO</th>
        @endforeach
        <th class="cs-total-lead" style="background:#0d9488;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;width:80px;min-width:80px;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">LEAD</th>
        <th class="cs-total-paid" style="background:#0d9488;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;width:80px;min-width:80px;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">PAID</th>
        <th class="cs-total-ratio" style="background:#0d9488;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.68rem;width:80px;min-width:80px;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">RATIO</th>
    </tr>
</thead>
