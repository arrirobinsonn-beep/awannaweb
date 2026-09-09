{{-- ── Diagram doughnut porsi lead per CS (dipakai 2× di team/performance: running & testing) ── --}}
{{-- Param: $chartData (array [label, lead, paid, is_utama]), $groupKey (id unik per donut), $badgeText, $badgeBg --}}
@php
    $chartData = $chartData ?? [];
    $groupKey = $groupKey ?? 'donut';
    $badgeText = $badgeText ?? '';
    $badgeBg = $badgeBg ?? 'rgba(0,0,0,.06)';
    $badgeColor = $badgeColor ?? '#9ca3af';
@endphp
<div class="clay-card" style="padding:16px;width:320px;flex:0 0 320px;display:flex;flex-direction:column;" data-donut-group="{{ $groupKey }}" data-reveal data-reveal-delay="120">
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🍩 Porsi Lead per CS</span>
        @if($badgeText)
        <span style="font-size:.62rem;font-weight:700;padding:2px 8px;border-radius:999px;background:{{ $badgeBg }};color:{{ $badgeColor }};">{{ $badgeText }}</span>
        @endif
    </div>
    <div style="font-size:.68rem;color:#9ca3af;margin-top:2px;margin-bottom:10px;">
        Hanya CS yang menerima lead pada rentang tanggal ini
    </div>

    @if(empty($chartData))
        <div style="flex:1;display:flex;align-items:center;justify-content:center;text-align:center;padding:28px 8px;color:#9ca3af;font-size:.78rem;">
            Belum ada CS dengan data pada rentang tanggal ini.
        </div>
    @else
        @php
            $palette = ['#FF6B6B', '#4ECDC4', '#FFD93D', '#6BCB77', '#4D96FF', '#9B5DE5', '#FF8FA3', '#00BBF9', '#FEE440', '#F15BB5'];
            // Satu peta warna per label → chart & legend dijamin selalu serasi
            $colorMap = [];
            foreach ($chartData as $i => $d) {
                $colorMap[$d['label']] = $palette[$i % count($palette)];
            }
            $cx = 110; $cy = 110;
            $rOuter = 92; $rInner = 60;   // satu ring doughnut (lead)
            $gap = 0.035;                 // celah antar segmen (radian)
            $leadSum = array_sum(array_column($chartData, 'lead'));
            $arc = function (float $rOut, float $rIn, float $a1, float $a2) use ($cx, $cy): string {
                if ($a2 - $a1 < 0.001) {
                    return '';
                }
                $p1 = [$cx + $rOut * sin($a1), $cy - $rOut * cos($a1)];
                $p2 = [$cx + $rOut * sin($a2), $cy - $rOut * cos($a2)];
                $q1 = [$cx + $rIn * sin($a1),  $cy - $rIn * cos($a1)];
                $q2 = [$cx + $rIn * sin($a2),  $cy - $rIn * cos($a2)];
                $large = ($a2 - $a1) > M_PI ? 1 : 0;

                return sprintf('M%.2f %.2f A%.2f %.2f 0 %d 1 %.2f %.2f L%.2f %.2f A%.2f %.2f 0 %d 0 %.2f %.2f Z',
                    $p1[0], $p1[1], $rOut, $rOut, $large, $p2[0], $p2[1],
                    $q2[0], $q2[1], $rIn, $rIn, $large, $q1[0], $q1[1]);
            };
            // Bangun segmen sekali → chart & legend baca array yang sama (pasti 1:1)
            $donutSegments = [];
            $aLead = -M_PI / 2;
            foreach ($chartData as $d) {
                $leadFrac = $leadSum > 0 ? $d['lead'] / $leadSum : 0;
                $l1 = $aLead + $gap / 2;
                $l2 = $aLead + max($leadFrac * 2 * M_PI - $gap / 2, $l1 + 0.001);
                $aLead += $leadFrac * 2 * M_PI;
                // Semua CS dengan lead > 0 pasti dapat segmen (yang super kecil jadi sliver tipis)
                $dPath = $arc($rOuter, $rInner, $l1, $l2);
                if ($dPath !== '') {
                    $donutSegments[] = [
                        'label' => $d['label'],
                        'color' => $colorMap[$d['label']],
                        'lead' => $d['lead'],
                        'paid' => $d['paid'],
                        'is_utama' => $d['is_utama'],
                        'pct' => $leadSum > 0 ? round($leadFrac * 100, 1) : 0,
                        'd' => $dPath,
                    ];
                }
            }
        @endphp

        <div style="display:flex;justify-content:center;flex-shrink:0;" class="cs-donut-pop">
            <svg id="cs-donut-{{ $groupKey }}" class="cs-donut-svg" viewBox="0 0 220 220" width="184" height="184" role="img" aria-label="Diagram porsi lead per CS" data-ver="4">
                @foreach($donutSegments as $seg)
                <path class="cs-donut-seg" d="{{ $seg['d'] }}" fill="{{ $seg['color'] }}" opacity="1" stroke="#fff" stroke-width="1" data-cs="{{ $seg['label'] }}" data-kind="lead">
                    <title>{{ $seg['label'] }} — Lead {{ number_format($seg['lead']) }} ({{ $seg['pct'] }}%)</title>
                </path>
                @endforeach
                <text x="110" y="108" text-anchor="middle" font-size="19" font-weight="900" fill="#1e1b2e">{{ number_format($leadSum) }}</text>
                <text x="110" y="121" text-anchor="middle" font-size="7.5" font-weight="700" fill="#9ca3af" letter-spacing="2">LEAD</text>
            </svg>
        </div>

        {{-- Legend interaktif — mengisi sisa tinggi panel --}}
        <div style="margin-top:12px;flex:1;min-height:0;display:flex;flex-direction:column;gap:4px;overflow-y:auto;padding-right:4px;scrollbar-width:thin;">
            @foreach($donutSegments as $seg)
            <div data-cs-legend="{{ $seg['label'] }}" style="display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:8px;cursor:pointer;transition:background .15s;"
                 onmouseenter="this.style.background='#f3f4f6'" onmouseleave="this.style.background=''">
                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:{{ $seg['color'] }};flex-shrink:0;"></span>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:.74rem;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $seg['label'] }} @if($seg['is_utama'])<span title="CS Utama">⭐</span>@endif
                    </div>
                    <div style="font-size:.62rem;color:#9ca3af;">Lead {{ number_format($seg['lead']) }} · Paid {{ number_format($seg['paid']) }}</div>
                </div>
                <span style="font-weight:800;font-size:.72rem;color:#1e1b2e;flex-shrink:0;">{{ $seg['pct'] }}%</span>
            </div>
            @endforeach
        </div>
    @endif
</div>