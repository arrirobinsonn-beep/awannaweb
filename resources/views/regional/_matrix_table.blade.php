{{-- ═══════════════════════════════════════════════════════════════
    PARTIAL: Matriks Regional per fase (dipakai utk Running & Testing)
    Variabel: $tableTitle, $tableSubtitle, $matrix, $totalPerTanggal,
              $masterProvinces, $allDates, $accentHead, $accentHead2, $accentTotal
════════════════════════════════════════════════════════════════ --}}
<div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
    <div style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">{{ $tableTitle }}</span>
        <span class="clay-badge" style="font-size:.65rem;background:{{ $accentHead }};color:#fff;">{{ $tableSubtitle }}</span>
    </div>
    <div class="reg-scroll-wrap">
        <table style="border-collapse:collapse;width:100%;font-size:.78rem;white-space:nowrap;">
            <thead>
                <tr class="reg-head-row">
                    <th colspan="1" class="reg-sticky-left" style="background:{{ $accentHead }};color:#fff;padding:8px 14px;text-align:left;font-weight:700;font-size:.8rem;min-width:200px;border:1px solid rgba(255,255,255,.15);">
                        PROVINSI
                    </th>
                    @foreach($allDates as $date)
                    <th colspan="3" style="background:{{ $accentHead }};color:#fff;padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;border:1px solid rgba(255,255,255,.15);min-width:100px;">
                        {{ \Carbon\Carbon::parse($date)->format('d') }}
                        <span style="display:block;font-weight:400;font-size:.65rem;opacity:.8;">
                            {{ \Carbon\Carbon::parse($date)->translatedFormat('D') }}
                        </span>
                    </th>
                    @endforeach
                    {{-- TOTAL sticky kanan --}}
                    <th colspan="3" class="reg-sticky-right reg-total-paid" style="background:{{ $accentTotal }};color:#fff;padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;border:1px solid rgba(255,255,255,.15);width:240px;min-width:240px;">
                        📊 TOTAL
                    </th>
                </tr>
                <tr class="reg-head-row reg-head-row-2">
                    <th class="reg-sticky-left" style="background:{{ $accentHead2 }};color:#fff;padding:6px 14px;text-align:left;font-weight:600;font-size:.72rem;border:1px solid rgba(255,255,255,.15);">
                        {{ count($masterProvinces) }} Provinsi
                    </th>
                    @foreach($allDates as $dateIndex => $date)
                        @php $isAlt = $dateIndex % 2 === 0; @endphp
                        <th style="background:{{ $accentHead2 }};color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);">LEAD</th>
                        <th style="background:{{ $accentHead2 }};color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);">RATIO</th>
                        <th style="background:{{ $accentHead2 }};color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);">PAID</th>
                    @endforeach
                    <th class="reg-sticky-right reg-total-lead" style="background:{{ $accentTotal }};color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);width:80px;min-width:80px;">LEAD</th>
                    <th class="reg-sticky-right reg-total-ratio" style="background:{{ $accentTotal }};color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);width:80px;min-width:80px;">RATIO</th>
                    <th class="reg-sticky-right reg-total-paid" style="background:{{ $accentTotal }};color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;border:1px solid rgba(255,255,255,.15);width:80px;min-width:80px;">PAID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($masterProvinces as $province)
                @php
                    $provTotalLead = 0;
                    $provTotalPaid = 0;
                @endphp
                <tr style="transition:background .12s;"
                    onmouseenter="this.style.background='#f8fafc'"
                    onmouseleave="this.style.background=''">
                    <td class="reg-sticky-left" style="padding:6px 14px;font-weight:600;font-size:.78rem;color:#1e1b2e;border-bottom:1px solid rgba(0,0,0,.05);white-space:nowrap;">
                        {{ $province }}
                    </td>
                    @foreach($allDates as $dateIndex => $date)
                        @php
                            $isAlt = $dateIndex % 2 === 0;
                            $stripClass = 'reg-date-striped' . ($isAlt ? '' : ' reg-date-alt');
                            $cell = $matrix[$province][$date];
                            $hasData = $cell['lead'] > 0 || $cell['paid'] > 0;
                            $provTotalLead += $cell['lead'];
                            $provTotalPaid += $cell['paid'];
                        @endphp
                        <td style="padding:8px 6px;text-align:center;font-weight:600;font-size:.82rem;border-bottom:1px solid rgba(0,0,0,.05);{{ $hasData ? 'color:#1e1b2e;cursor:pointer;' : 'color:#d1d5db;' }}"
                            class="{{ $hasData ? 'cell-edit-trigger ' : '' }}{{ $stripClass }}"
                            @if($hasData)
                            data-id="{{ $cell['id'] }}"
                            data-tanggal="{{ $date }}"
                            data-province="{{ $province }}"
                            data-lead="{{ $cell['lead'] }}"
                            data-paid="{{ $cell['paid'] }}"
                            data-ratio="{{ $cell['ratio'] }}"
                            title="Klik untuk edit"
                            @endif>
                            {{ $hasData ? number_format($cell['lead']) : '0' }}
                        </td>
                        <td style="padding:8px 6px;text-align:center;font-size:.76rem;border-bottom:1px solid rgba(0,0,0,.05);{{ $hasData ? 'color:var(--color-primary);font-weight:700;' : 'color:#d1d5db;' }}"
                            class="{{ $stripClass }}">
                            @if($cell['lead'] > 0)
                                {{ number_format($cell['ratio'], 1) }}%
                            @else
                                0%
                            @endif
                        </td>
                        <td style="padding:8px 6px;text-align:center;font-weight:600;font-size:.82rem;border-bottom:1px solid rgba(0,0,0,.05);{{ $hasData ? 'color:#059669;' : 'color:#d1d5db;' }}"
                            class="{{ $stripClass }}">
                            {{ $hasData ? number_format($cell['paid']) : '0' }}
                        </td>
                    @endforeach
                    {{-- Total per provinsi (sticky kanan) --}}
                    @php $provRatio = $provTotalLead > 0 ? round($provTotalPaid / $provTotalLead * 100, 1) : 0; @endphp
                    <td class="reg-sticky-right reg-total-lead" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#1e1b2e;border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;width:80px;min-width:80px;">{{ number_format($provTotalLead) }}</td>
                    <td class="reg-sticky-right reg-total-ratio" style="padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;color:var(--color-primary);border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;width:80px;min-width:80px;">{{ $provRatio > 0 ? number_format($provRatio, 1) . '%' : '0%' }}</td>
                    <td class="reg-sticky-right reg-total-paid" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#059669;border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;width:80px;min-width:80px;">{{ number_format($provTotalPaid) }}</td>
                </tr>
                @endforeach
                {{-- Grand Total Row (sticky bottom + sticky kanan) --}}
                <tr style="position:sticky;bottom:0;z-index:4;background:#F0FFFE;">
                    <td class="reg-sticky-left" style="background:#F0FFFE;padding:8px 14px;font-weight:800;font-size:.82rem;color:#0d9488;border-top:2px solid #0d9488;">
                        📊 GRAND TOTAL
                    </td>
                    @foreach($allDates as $dateIndex => $date)
                        @php
                            $tot = $totalPerTanggal[$date];
                            $isAlt = $dateIndex % 2 === 0;
                            $stripClass = 'reg-date-striped' . ($isAlt ? '' : ' reg-date-alt');
                        @endphp
                        <td style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#1e1b2e;border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ number_format($tot['lead']) }}</td>
                        <td style="padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;color:var(--color-primary);border-top:2px solid #0d9488;" class="{{ $stripClass }}">
                            {{ $tot['lead'] > 0 ? number_format($tot['paid'] / $tot['lead'] * 100, 1) . '%' : '0%' }}
                        </td>
                        <td style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#059669;border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ number_format($tot['paid']) }}</td>
                    @endforeach
                    @php
                        $grandLead = collect($totalPerTanggal)->sum('lead');
                        $grandPaid = collect($totalPerTanggal)->sum('paid');
                        $grandRatio = $grandLead > 0 ? round($grandPaid / $grandLead * 100, 1) : 0;
                    @endphp
                    <td class="reg-sticky-right reg-total-lead" style="padding:8px 6px;text-align:center;font-weight:900;font-size:.9rem;color:#0d9488;border-top:2px solid #0d9488;background:#e6fffa;width:80px;min-width:80px;">{{ number_format($grandLead) }}</td>
                    <td class="reg-sticky-right reg-total-ratio" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:var(--color-primary);border-top:2px solid #0d9488;background:#e6fffa;width:80px;min-width:80px;">{{ $grandRatio > 0 ? number_format($grandRatio, 1) . '%' : '0%' }}</td>
                    <td class="reg-sticky-right reg-total-paid" style="padding:8px 6px;text-align:center;font-weight:900;font-size:.9rem;color:#059669;border-top:2px solid #0d9488;background:#e6fffa;width:80px;min-width:80px;">{{ number_format($grandPaid) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
