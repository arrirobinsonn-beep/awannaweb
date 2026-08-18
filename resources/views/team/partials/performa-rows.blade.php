{{-- ── Baris performa per CS + grand total (dipakai oleh team/performance.blade.php) ── --}}
@php
    $csNames = $csList->pluck('panggilan')->filter()->values()->toArray();
    $grandByDate = [];
    $grandLead = 0;
    $grandPaid = 0;
@endphp
@forelse($csNames as $csIndex => $csName)
@php
    $csData = $csList->firstWhere('panggilan', $csName);
    $rowBg = $csIndex % 2 === 0 ? '#ffffff' : '#fcfcfc';
    $csTotalLead = 0;
    $csTotalPaid = 0;
@endphp
<tr style="transition:background .12s;background:{{ $rowBg }};"
    onmouseenter="this.style.background='#f3f7fe'"
    onmouseleave="this.style.background='{{ $rowBg }}'">
    <td class="cs-name-sticky" style="background:{{ $rowBg }};padding:6px 14px;font-weight:700;font-size:.8rem;color:#1e1b2e;border-bottom:1px solid rgba(0,0,0,.05);white-space:nowrap;">
        <div style="display:flex;align-items:center;gap:8px;">
            @if($csData && !empty($csData->avatar_url))
            <img src="{{ $csData->avatar_url }}" alt="avatar"
                 style="width:28px;height:28px;border-radius:8px;object-fit:cover;flex-shrink:0;">
            @endif
            <div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span>{{ $csData->display_name ?? $csName }}</span>
                    @if(!empty($badge) || (!empty($csData) && !empty($csData->is_utama)))
                    <span class="clay-badge clay-badge-green" style="font-size:.55rem;">{{ $badge ?? 'Utama' }}</span>
                    @endif
                </div>
                @if($csData && !empty($csData->subtitle))
                <div style="font-size:.6rem;color:#9ca3af;font-weight:400;">{{ $csData->subtitle }}</div>
                @endif
            </div>
        </div>
    </td>
    @foreach($allDates as $dateIndex => $date)
    @php
        $isAlt = $dateIndex % 2 === 0;
        $stripClass = 'cs-date-striped' . ($isAlt ? '' : ' cs-date-alt');

        $lead = 0;
        $paid = 0;
        if (isset($byDate[$date])) {
            foreach ($byDate[$date] as $stat) {
                // Prioritas: cocokkan FK cs_user_id (robust terhadap variasi nama),
                // fallback: cocokkan nama cs_panggilan.
                $isMatch = false;
                if ($csData && ! empty($csData->id) && ! empty($stat->cs_user_id)
                    && (int) $stat->cs_user_id === (int) $csData->id) {
                    $isMatch = true;
                } elseif (strtolower(trim((string) $stat->cs_panggilan)) === strtolower(trim((string) $csName))
                    || ($csData && strtolower(trim((string) $stat->cs_panggilan)) === strtolower(trim((string) ($csData->nama ?? ''))))) {
                    $isMatch = true;
                }
                if ($isMatch) {
                    $lead += (int) $stat->lead;
                    $paid += (int) $stat->paid;
                }
            }
        }
        $hasData = $lead > 0 || $paid > 0;
        $ratio = $lead > 0 ? round($paid / $lead * 100, 1) : 0;
        $csTotalLead += $lead;
        $csTotalPaid += $paid;
        $grandByDate[$date]['lead'] = ($grandByDate[$date]['lead'] ?? 0) + $lead;
        $grandByDate[$date]['paid'] = ($grandByDate[$date]['paid'] ?? 0) + $paid;
        $grandLead += $lead;
        $grandPaid += $paid;
    @endphp
    <td style="padding:8px 6px;text-align:center;font-weight:600;font-size:.82rem;border-bottom:1px solid rgba(0,0,0,.05);{{ $hasData ? 'color:#1e1b2e;' : 'color:#d1d5db;' }}"
        class="{{ $stripClass }}">
        {{ $hasData ? number_format($lead) : '0' }}
    </td>
    <td style="padding:8px 6px;text-align:center;font-weight:600;font-size:.82rem;border-bottom:1px solid rgba(0,0,0,.05);{{ $hasData ? 'color:#059669;' : 'color:#d1d5db;' }}"
        class="{{ $stripClass }}">
        {{ $hasData ? number_format($paid) : '0' }}
    </td>
    <td style="padding:8px 6px;text-align:center;font-size:.76rem;border-bottom:1px solid rgba(0,0,0,.05);{{ $hasData ? 'color:var(--color-primary);font-weight:700;' : 'color:#d1d5db;' }}"
        class="{{ $stripClass }}">
        @if($lead > 0)
            {{ number_format($ratio, 1) }}%
        @else
            0%
        @endif
    </td>
    @endforeach
    @php $totalRatio = $csTotalLead > 0 ? round($csTotalPaid / $csTotalLead * 100, 1) : 0; @endphp
    <td class="cs-total-lead" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#1e1b2e;border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;width:80px;min-width:80px;">
        {{ number_format($csTotalLead) }}
    </td>
    <td class="cs-total-paid" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#059669;border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;width:80px;min-width:80px;">
        {{ number_format($csTotalPaid) }}
    </td>
    <td class="cs-total-ratio" style="padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;color:var(--color-primary);border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;width:80px;min-width:80px;">
        {{ $totalRatio > 0 ? number_format($totalRatio, 1) . '%' : '0%' }}
    </td>
</tr>
@empty
<tr><td colspan="{{ count($allDates) * 3 + 3 }}" style="text-align:center;padding:48px 16px;">
    <p style="color:#9ca3af;">Tidak ada CS dalam kategori ini.</p>
</td></tr>
@endforelse

{{-- ── Grand Total per tabel ───────────────────────────────────── --}}
@if($csList->isNotEmpty())
@php
    $grandRatio = $grandLead > 0 ? round($grandPaid / $grandLead * 100, 1) : 0;
@endphp
<tr style="position:sticky;bottom:0;z-index:4;background:#F0FFFE;">
    <td class="cs-name-sticky" style="background:#F0FFFE;padding:8px 14px;font-weight:800;font-size:.82rem;color:#0d9488;border-top:2px solid #0d9488;">
        📊 GRAND TOTAL
    </td>
    @foreach($allDates as $dateIndex => $date)
        @php
            $dayLead = $grandByDate[$date]['lead'] ?? 0;
            $dayPaid = $grandByDate[$date]['paid'] ?? 0;
            $dayRatio = $dayLead > 0 ? round($dayPaid / $dayLead * 100, 1) : 0;
            $isAlt = $dateIndex % 2 === 0;
            $stripClass = 'cs-date-striped' . ($isAlt ? '' : ' cs-date-alt');
        @endphp
        <td style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#1e1b2e;border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ number_format($dayLead) }}</td>
        <td style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#059669;border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ number_format($dayPaid) }}</td>
        <td style="padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;color:var(--color-primary);border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ $dayRatio > 0 ? number_format($dayRatio, 1) . '%' : '0%' }}</td>
    @endforeach
    <td class="cs-total-lead" style="padding:8px 6px;text-align:center;font-weight:900;font-size:.9rem;color:#0d9488;border-top:2px solid #0d9488;background:#e6fffa;width:80px;min-width:80px;">{{ number_format($grandLead) }}</td>
    <td class="cs-total-paid" style="padding:8px 6px;text-align:center;font-weight:900;font-size:.9rem;color:#059669;border-top:2px solid #0d9488;background:#e6fffa;width:80px;min-width:80px;">{{ number_format($grandPaid) }}</td>
    <td class="cs-total-ratio" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:var(--color-primary);border-top:2px solid #0d9488;background:#e6fffa;width:80px;min-width:80px;">{{ $grandRatio > 0 ? number_format($grandRatio, 1) . '%' : '0%' }}</td>
</tr>
@endif
