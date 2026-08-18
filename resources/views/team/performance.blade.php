@extends('layouts.app')
@section('title','Performa Tim')
@section('page-title','📊 Performa Tim')
@section('page-subtitle','Lead & Paid per CS — data dari import Regional')

@push('styles')
<style>
/* ── Striped background per tanggal (setiap 3 kolom berselang) ── */
.cs-date-striped { background: #f8fbff; }
.cs-date-striped.cs-date-alt { background: #f0f4f8; }

/* ── Sticky TOTAL column group (3 sub-kolom dengan offset berbeda) ── */
.cs-total-lead,
.cs-total-paid,
.cs-total-ratio {
    position: sticky !important;
    background-clip: padding-box;
}
.cs-total-lead  { right: 160px; z-index: 5; }
.cs-total-paid  { right: 80px;  z-index: 6; }
.cs-total-ratio { right: 0;     z-index: 7; }
.cs-total-ratio::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 8px;
    background: linear-gradient(to left, transparent, rgba(0,0,0,.08));
    pointer-events: none;
}
thead .cs-total-lead,
thead .cs-total-paid,
thead .cs-total-ratio { top: 0; }
thead tr:nth-child(2) .cs-total-lead,
thead tr:nth-child(2) .cs-total-paid,
thead tr:nth-child(2) .cs-total-ratio { top: 38px; }
tbody .cs-total-lead { z-index: 3; }
tbody .cs-total-paid { z-index: 4; }
tbody .cs-total-ratio { z-index: 5; }

/* ── Sticky CS name column ── */
.cs-name-sticky {
    position: sticky !important;
    left: 0;
    z-index: 4;
    background-clip: padding-box;
}
thead .cs-name-sticky { z-index: 5; top: 0; }
thead tr:nth-child(2) .cs-name-sticky { top: 38px; }
tbody .cs-name-sticky { z-index: 2; }
.text-right { text-align:right; }
.cs-name-sticky::after {
    content: '';
    position: absolute;
    right: 0; top: 0; bottom: 0;
    width: 8px;
    background: linear-gradient(to right, transparent, rgba(0,0,0,.06));
    pointer-events: none;
}

/* ── Batas tinggi tabel performa (±7 baris data, sisanya scroll vertikal) ── */
.perf-scroll-limit { overflow-y: auto; overscroll-behavior: contain; }
.perf-scroll-limit::-webkit-scrollbar { width: 8px; }
.perf-scroll-limit::-webkit-scrollbar-track { background: transparent; }
.perf-scroll-limit::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 999px; }
.perf-scroll-limit::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
.perf-scroll-limit { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }

/* ── Sticky HEADER (2 baris) menempel utuh saat scroll vertikal di dalam container ──
   thead dibuat sticky sebagai satu kesatuan — ini pola kanonik yang andal lintas
   browser. Sticky horizontal kolom kiri (.cs-name-sticky) & kanan (.cs-total-*) tetap
   dipegang per-sel di bawah. z-index tinggi agar selalu di atas konten tbody. */
.perf-scroll-limit thead {
    position: sticky;
    top: 0;
    z-index: 8;
}

/* ── Doughnut porsi lead per CS ── */
.cs-donut-seg {
    transition: opacity .15s ease;
    cursor: pointer;
}
.cs-donut-pop {
    transform-origin: center;
    animation: csDonutPop .7s cubic-bezier(.34, 1.56, .64, 1) both;
}
@keyframes csDonutPop {
    from { transform: scale(.55); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
</style>
@endpush

@section('content')
@php $u = auth()->user(); @endphp

<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- Action bar — date picker --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <form method="GET" action="{{ route('team.performance') }}" id="filter-form-perf"
              style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <x-date-range-picker
                :dari="$dari"
                :sampai="$sampai"
                form-id="filter-form-perf"
                input-dari="dari"
                input-sampai="sampai"
            />
            <a href="{{ route('team.performance') }}" class="clay-btn clay-btn-outline">Reset</a>
        </form>
    </div>

    {{-- Statistik ringkasan --}}
    <div class="grid-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:0;" data-reveal>
        <div class="stat-card stat-card-1" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total Lead (CS)</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ collect($totalPerCs)->sum('lead') }}">0</div>
        </div>
        <div class="stat-card stat-card-2" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total Paid (CS)</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ collect($totalPerCs)->sum('paid') }}">0</div>
        </div>
        <div class="stat-card stat-card-3" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total CS</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ $mainMembers->count() + $guestMembers->count() }}">0</div>
        </div>
        <div class="stat-card stat-card-4" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total Hari</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ count($byDate) }}">0</div>
        </div>
    </div>

    @if(empty($byDate))
        {{-- Empty state --}}
        <div class="clay-card" style="padding:60px 20px;text-align:center;" data-reveal>
            <div style="font-size:3.5rem;margin-bottom:12px;">📊</div>
            <h3 style="font-weight:700;font-size:1.1rem;color:#1e1b2e;margin-bottom:6px;">Belum Ada Data Performa</h3>
            <p style="color:#9ca3af;font-size:.85rem;max-width:420px;margin:0 auto;">
                Data performa CS diambil dari file Excel yang Anda upload di halaman
                <strong>Detail Per Daerah</strong>.
                Pastikan file Excel Anda memiliki kolom <strong>handled_by</strong> yang berisi nama CS.
            </p>
            <a href="{{ route('regional.index') }}" class="clay-btn clay-btn-primary" style="margin-top:16px;" data-page-link>
                📤 Upload Data Regional
            </a>
        </div>
    @else

        @if($u->hasRole('cs'))
            {{-- ═══════ SISI CS: satu tabel — tim di bawah advertiser tempat bernaung ═══════ --}}
            <div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
                <div class="perf-scroll-limit" style="overflow-x:auto;">
                    <table style="border-collapse:separate;border-spacing:0;width:100%;font-size:.78rem;white-space:nowrap;">
                        @include('team.partials.performa-head', ['allDates' => $allDates, 'csCount' => $mainMembers->count()])
                        <tbody>
                            @include('team.partials.performa-rows', [
                                'csList' => $mainMembers,
                                'byDate' => $byDate,
                                'allDates' => $allDates,
                                'badge' => 'Utama',
                            ])
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            {{-- ═══════ SISI ADVERTISER: 1 tabel + diagram doughnut di samping ═══════ --}}
            <div style="display:flex;gap:16px;align-items:stretch;flex-wrap:wrap;">
                <div class="clay-card" style="padding:0;overflow:hidden;flex:1 1 520px;min-width:0;" data-reveal>
                    <div style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">👥 Performa Semua CS</span>
                        <span class="clay-badge clay-badge-green" style="font-size:.65rem;">CS Utama paling atas · urut porsi penerimaan data</span>
                    </div>
                    <div class="perf-scroll-limit" style="overflow-x:auto;">
                        <table style="border-collapse:separate;border-spacing:0;width:100%;font-size:.78rem;white-space:nowrap;">
                            @include('team.partials.performa-head', ['allDates' => $allDates, 'csCount' => $members->count()])
                            <tbody>
                                @include('team.partials.performa-rows', [
                                    'csList' => $members,
                                    'byDate' => $byDate,
                                    'allDates' => $allDates,
                                ])
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 🍩 Diagram doughnut: porsi lead per CS (termasuk CS tamu) --}}
                <div class="clay-card" style="padding:16px;width:320px;flex:0 0 320px;display:flex;flex-direction:column;" data-reveal data-reveal-delay="120">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🍩 Porsi Lead per CS</span>
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
                            <svg id="cs-donut" viewBox="0 0 220 220" width="184" height="184" role="img" aria-label="Diagram porsi lead per CS" data-ver="4">
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
            </div>
        @endif

    @endif

</div>
@endsection

@push('scripts')
<script>
(() => {
    'use strict';
    // ── Batas tinggi tabel: tampilkan ±7 baris data, sisanya scroll vertikal ──
    // Header sticky + kolom sticky + baris GRAND TOTAL (sticky bottom) tetap berfungsi
    // di dalam container scroll ini. Baris data = baris tbody non-sticky.
    // Catatan: deteksi baris total memakai inline position:sticky (baris GRAND TOTAL) —
    // jika nanti dipindah ke CSS class, sesuaikan filter di sini.
    const MAX_ROWS = 7;

    document.querySelectorAll('.perf-scroll-limit').forEach((scrollEl) => {
        const table = scrollEl.querySelector('table');
        if (!table || !table.tBodies.length) return;
        const tbody = table.tBodies[0];

        // Baris data: tbody tanpa posisi sticky (grand total) & tanpa display:none
        const dataRows = Array.prototype.filter.call(tbody.rows, (r) => (
            r.style.position !== 'sticky' && r.style.display !== 'none'
        ));
        if (dataRows.length <= MAX_ROWS) return;

        const footerRow = () => {
            for (let j = 0; j < tbody.rows.length; j++) {
                if (tbody.rows[j].style.position === 'sticky') return tbody.rows[j];
            }
            return null;
        };

        const measure = () => {
            let h = table.tHead ? table.tHead.offsetHeight : 0;
            for (let i = 0; i < MAX_ROWS; i++) h += dataRows[i].offsetHeight;
            // Grand total sticky-bottom ikut dihitung agar tidak menutupi baris ke-7
            const ft = footerRow();
            if (ft) h += ft.offsetHeight;
            return h;
        };

        scrollEl.style.maxHeight = measure() + 'px';

        // Pass 2: scrollbar vertikal muncul → lebar konten menyusut → ukur ulang
        requestAnimationFrame(() => {
            scrollEl.style.maxHeight = measure() + 'px';
        });

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                scrollEl.style.maxHeight = measure() + 'px';
            }, 150);
        });
    });
})();
</script>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';
    const svg = document.getElementById('cs-donut');
    if (!svg) return;
    const segs = svg.querySelectorAll('.cs-donut-seg');
    const rows = document.querySelectorAll('[data-cs-legend]');

    // Warna segmen selalu tampil penuh (opacity 1) — hover hanya meredupkan yang lain,
    // jadi warna kepingan di state diam = warna saat di-hover = warna swatch legend.
    function highlight(name) {
        segs.forEach(s => {
            s.style.opacity = (!name || s.getAttribute('data-cs') === name) ? '1' : '0.15';
        });
    }

    // State diam = sama persis dengan state hover: set semua segmen ke opacity penuh
    // lewat jalur inline-style yang identik (bukan atribut), biar render-nya dijamin sama.
    highlight(null);

    rows.forEach(row => {
        row.addEventListener('mouseenter', () => highlight(row.getAttribute('data-cs-legend')));
        row.addEventListener('mouseleave', () => highlight(null));
    });
    segs.forEach(seg => {
        seg.addEventListener('mouseenter', () => highlight(seg.getAttribute('data-cs')));
        seg.addEventListener('mouseleave', () => highlight(null));
    });
})();
</script>
@endpush
