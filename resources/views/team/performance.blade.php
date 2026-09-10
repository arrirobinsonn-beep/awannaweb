@extends('layouts.app')
@section('title','Performa Tim')
@section('page-title','📊 Performa Tim')
@section('page-subtitle','Lead & Paid per CS — data dari import Regional (dipisah Running / Testing)')

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
@php
    // Tanggal unik dari kedua status (running + testing) — kartu "Total Hari"
    $perfAllDates = array_unique(array_merge(array_keys($byDateRunning), array_keys($byDateTesting)));
    $perfHasStats = count($perfAllDates) > 0;
@endphp

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

    {{-- Statistik ringkasan (Total Lead/Paid mengikuti tab aktif) --}}
    <div class="grid-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:0;" data-reveal>
        <div class="stat-card stat-card-1" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total Lead (CS)</div>
            <div style="font-size:1.5rem;font-weight:900;" id="perf-sum-lead"
                 data-run="{{ $runTotalLead }}" data-test="{{ $testTotalLead }}"
                 data-counter="{{ $runTotalLead }}">{{ $runTotalLead }}</div>
        </div>
        <div class="stat-card stat-card-2" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total Paid (CS)</div>
            <div style="font-size:1.5rem;font-weight:900;" id="perf-sum-paid"
                 data-run="{{ $runTotalPaid }}" data-test="{{ $testTotalPaid }}"
                 data-counter="{{ $runTotalPaid }}">{{ $runTotalPaid }}</div>
        </div>
        <div class="stat-card stat-card-3" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total CS</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ $mainMembers->count() + $guestMembers->count() }}">{{ $mainMembers->count() + $guestMembers->count() }}</div>
        </div>
        <div class="stat-card stat-card-4" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total Hari</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ count($perfAllDates) }}">{{ count($perfAllDates) }}</div>
        </div>
    </div>

    @if(! $perfHasStats)
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

        {{-- ═══════════════ TAB Running / Testing ═══════════════ --}}
        <div style="display:flex;gap:0;margin-bottom:-2px;position:relative;z-index:2;" data-reveal>
            <button onclick="switchPerfTab('running')" id="perftab-running"
                    style="padding:9px 18px 11px;border:2px solid rgba(255,107,107,.25);border-bottom:2px solid #fff;border-radius:14px 14px 0 0;background:#fff;font-family:inherit;font-size:.82rem;font-weight:700;color:var(--color-primary,#FF6B6B);cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;margin-right:4px;position:relative;z-index:3;">
                🟢 Running
                <span style="font-size:.7rem;font-weight:600;padding:1px 7px;border-radius:999px;background:rgba(255,107,107,.12);color:var(--color-primary);">{{ number_format($runTotalLead) }} lead</span>
            </button>
            <button onclick="switchPerfTab('testing')" id="perftab-testing"
                    style="padding:9px 18px 11px;border:2px solid rgba(0,0,0,.08);border-bottom:2px solid rgba(0,0,0,.08);border-radius:14px 14px 0 0;background:#f5f5f5;font-family:inherit;font-size:.82rem;font-weight:500;color:#6b7280;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;margin-right:4px;position:relative;z-index:1;">
                🔬 Testing
                <span style="font-size:.7rem;font-weight:600;padding:1px 7px;border-radius:999px;background:rgba(0,0,0,.06);color:#9ca3af;">{{ number_format($testTotalLead) }} lead</span>
            </button>
        </div>

        {{-- ═══════════════ TAB CONTENT: Running ═══════════════ --}}
        <div id="perftabcontent-running">
            @if($u->hasRole('cs'))
                {{-- SISI CS: satu tabel — tim di bawah advertiser tempat bernaung --}}
                <div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
                    <div class="perf-scroll-limit" style="overflow-x:auto;">
                        <table style="border-collapse:separate;border-spacing:0;width:100%;font-size:.78rem;white-space:nowrap;">
                            @include('team.partials.performa-head', ['allDates' => $allDates, 'csCount' => $mainMembers->count()])
                            <tbody>
                                @include('team.partials.performa-rows', [
                                    'csList' => $mainMembers,
                                    'byDate' => $byDateRunning,
                                    'allDates' => $allDates,
                                    'badge' => 'Utama',
                                ])
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                {{-- SISI ADVERTISER: tabel + diagram doughnut di samping --}}
                <div style="display:flex;gap:16px;align-items:stretch;flex-wrap:wrap;">
                    <div class="clay-card" style="padding:0;overflow:hidden;flex:1 1 520px;min-width:0;" data-reveal>
                        <div style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">👥 Performa Semua CS</span>
                            <span class="clay-badge clay-badge-green" style="font-size:.65rem;">🟢 Running · CS Utama paling atas · urut porsi penerimaan data</span>
                        </div>
                        <div class="perf-scroll-limit" style="overflow-x:auto;">
                            <table style="border-collapse:separate;border-spacing:0;width:100%;font-size:.78rem;white-space:nowrap;">
                                @include('team.partials.performa-head', ['allDates' => $allDates, 'csCount' => $members->count()])
                                <tbody>
                                    @include('team.partials.performa-rows', [
                                        'csList' => $members,
                                        'byDate' => $byDateRunning,
                                        'allDates' => $allDates,
                                    ])
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @include('team.partials.performa-donut', [
                        'chartData' => $chartDataRunning,
                        'groupKey' => 'running',
                        'badgeText' => '🟢 Running',
                        'badgeBg' => 'rgba(16,185,129,.12)',
                        'badgeColor' => '#065f46',
                    ])
                </div>
            @endif
        </div>

        {{-- ═══════════════ TAB CONTENT: Testing ═══════════════ --}}
        <div id="perftabcontent-testing" style="display:none;">
            @if($u->hasRole('cs'))
                {{-- SISI CS: satu tabel — tim di bawah advertiser tempat bernaung --}}
                <div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
                    <div class="perf-scroll-limit" style="overflow-x:auto;">
                        <table style="border-collapse:separate;border-spacing:0;width:100%;font-size:.78rem;white-space:nowrap;">
                            @include('team.partials.performa-head', ['allDates' => $allDates, 'csCount' => $mainMembers->count()])
                            <tbody>
                                @include('team.partials.performa-rows', [
                                    'csList' => $mainMembers,
                                    'byDate' => $byDateTesting,
                                    'allDates' => $allDates,
                                    'badge' => 'Utama',
                                ])
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                {{-- SISI ADVERTISER: tabel + diagram doughnut di samping --}}
                <div style="display:flex;gap:16px;align-items:stretch;flex-wrap:wrap;">
                    <div class="clay-card" style="padding:0;overflow:hidden;flex:1 1 520px;min-width:0;" data-reveal>
                        <div style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">👥 Performa Semua CS</span>
                            <span class="clay-badge" style="font-size:.65rem;background:#fffbeb;color:#92400e;">🔬 Testing · CS Utama paling atas · urut porsi penerimaan data</span>
                        </div>
                        <div class="perf-scroll-limit" style="overflow-x:auto;">
                            <table style="border-collapse:separate;border-spacing:0;width:100%;font-size:.78rem;white-space:nowrap;">
                                @include('team.partials.performa-head', ['allDates' => $allDates, 'csCount' => $members->count()])
                                <tbody>
                                    @include('team.partials.performa-rows', [
                                        'csList' => $members,
                                        'byDate' => $byDateTesting,
                                        'allDates' => $allDates,
                                    ])
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @include('team.partials.performa-donut', [
                        'chartData' => $chartDataTesting,
                        'groupKey' => 'testing',
                        'badgeText' => '🔬 Testing',
                        'badgeBg' => 'rgba(245,158,11,.14)',
                        'badgeColor' => '#92400e',
                    ])
                </div>
            @endif
        </div>

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
    // Tabel pada tab yang tersembunyi (display:none) tidak diukur — diukur saat tab dibuka.
    const MAX_ROWS = 7;

    function measure(table, dataRows, footerRow) {
        let h = table.tHead ? table.tHead.offsetHeight : 0;
        for (let i = 0; i < MAX_ROWS; i++) h += dataRows[i].offsetHeight;
        const ft = footerRow();
        if (ft) h += ft.offsetHeight;
        return h;
    }

    function apply(scrollEl) {
        const table = scrollEl.querySelector('table');
        if (!table || !table.tBodies.length) return;
        if (scrollEl.offsetParent === null) return; // tersembunyi (tab nonaktif) → nanti diukur saat dibuka

        const tbody = table.tBodies[0];

        // Baris data: tbody tanpa posisi sticky (grand total) & tanpa display:none
        const dataRows = Array.prototype.filter.call(tbody.rows, (r) => (
            r.style.position !== 'sticky' && r.style.display !== 'none'
        ));
        if (dataRows.length <= MAX_ROWS) { scrollEl.style.maxHeight = ''; return; }

        const footerRow = () => {
            for (let j = 0; j < tbody.rows.length; j++) {
                if (tbody.rows[j].style.position === 'sticky') return tbody.rows[j];
            }
            return null;
        };

        scrollEl.style.maxHeight = measure(table, dataRows, footerRow) + 'px';

        // Pass 2: scrollbar vertikal muncul → lebar konten menyusut → ukur ulang
        requestAnimationFrame(() => {
            scrollEl.style.maxHeight = measure(table, dataRows, footerRow) + 'px';
        });
    }

    // Dipakai switchPerfTab(): ukur ulang tabel pada tab yang baru ditampilkan
    window.reapplyPerfTableLimit = function() {
        document.querySelectorAll('.perf-scroll-limit').forEach((el) => apply(el));
    };

    document.querySelectorAll('.perf-scroll-limit').forEach((el) => apply(el));

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            document.querySelectorAll('.perf-scroll-limit').forEach((el) => apply(el));
        }, 150);
    });
})();
</script>
@endpush

@push('scripts')
<script>
// ── Tab Running / Testing (2 tabel performa team) ──────────────
function switchPerfTab(tab) {
    var running = document.getElementById('perftabcontent-running');
    var testing = document.getElementById('perftabcontent-testing');
    var btnRun  = document.getElementById('perftab-running');
    var btnTest = document.getElementById('perftab-testing');
    if (tab === 'running') {
        if (running) running.style.display = '';
        if (testing) testing.style.display = 'none';
        if (btnRun)  { btnRun.style.background = '#fff'; btnRun.style.color = 'var(--color-primary,#FF6B6B)'; btnRun.style.fontWeight = '700'; btnRun.style.borderColor = 'rgba(255,107,107,.25)'; btnRun.style.borderBottom = '2px solid #fff'; btnRun.style.zIndex = '3'; }
        if (btnTest) { btnTest.style.background = '#f5f5f5'; btnTest.style.color = '#6b7280'; btnTest.style.fontWeight = '500'; btnTest.style.borderColor = 'rgba(0,0,0,.08)'; btnTest.style.borderBottom = '2px solid rgba(0,0,0,.08)'; btnTest.style.zIndex = '1'; }
    } else {
        if (running) running.style.display = 'none';
        if (testing) testing.style.display = '';
        if (btnRun)  { btnRun.style.background = '#f5f5f5'; btnRun.style.color = '#6b7280'; btnRun.style.fontWeight = '500'; btnRun.style.borderColor = 'rgba(0,0,0,.08)'; btnRun.style.borderBottom = '2px solid rgba(0,0,0,.08)'; btnRun.style.zIndex = '1'; }
        if (btnTest) { btnTest.style.background = '#fff'; btnTest.style.color = '#92400e'; btnTest.style.fontWeight = '700'; btnTest.style.borderColor = 'rgba(245,158,11,.25)'; btnTest.style.borderBottom = '2px solid #fff'; btnTest.style.zIndex = '3'; }
    }

    // Kartu statistik (Total Lead/Paid CS) mengikuti tab aktif
    ['perf-sum-lead', 'perf-sum-paid'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = (tab === 'running') ? el.getAttribute('data-run') : el.getAttribute('data-test');
    });

    // Ukur ulang tinggi tabel pada tab yang baru ditampilkan
    if (window.reapplyPerfTableLimit) window.reapplyPerfTableLimit();
}
</script>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';
    // Hover doughnut — per grup (running & testing punya donut sendiri, keduanya
    // ada di DOM; scoping via [data-donut-group] agar tidak saling menimpa).
    document.querySelectorAll('[data-donut-group]').forEach(function(group) {
        const segs = group.querySelectorAll('.cs-donut-seg');
        const rows = group.querySelectorAll('[data-cs-legend]');
        if (!segs.length) return;

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
    });
})();
</script>
@endpush