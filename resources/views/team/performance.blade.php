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

    {{-- Statistik ringkasan — kartu Lead/Paid mengikuti tab Running/Testing --}}
    <div class="grid-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:0;" data-reveal>
        <div class="stat-card stat-card-1" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total Lead (CS)</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ collect($totalPerCs)->sum('lead') }}" data-run="{{ collect($totalPerCs)->sum('lead') }}" data-test="{{ collect($totalPerCsTesting ?? [])->sum('lead') }}">{{ collect($totalPerCs)->sum('lead') }}</div>
        </div>
        <div class="stat-card stat-card-2" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total Paid (CS)</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ collect($totalPerCs)->sum('paid') }}" data-run="{{ collect($totalPerCs)->sum('paid') }}" data-test="{{ collect($totalPerCsTesting ?? [])->sum('paid') }}">{{ collect($totalPerCs)->sum('paid') }}</div>
        </div>
        <div class="stat-card stat-card-3" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total CS</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ $mainMembers->count() + $guestMembers->count() }}">{{ $mainMembers->count() + $guestMembers->count() }}</div>
        </div>
        <div class="stat-card stat-card-4" style="padding:14px;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total Hari</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ count($byDate) }}">{{ count($byDate) }}</div>
        </div>
    </div>

    @if(empty($byDate) && empty($byDateTesting))
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

        {{-- ─── Tab fase: 🔵 Running / 🔬 Testing (DUAL FASE, 18 Sep) ─── --}}
        <div style="display:flex;gap:8px;margin-bottom:12px;" data-reveal>
            <button type="button" id="teamtab-btn-running" onclick="switchTeamTab('running')"
                    class="clay-btn clay-btn-primary" style="padding:7px 16px;font-size:.78rem;font-weight:700;">
                🔵 Running
            </button>
            <button type="button" id="teamtab-btn-testing" onclick="switchTeamTab('testing')"
                    class="clay-btn clay-btn-outline" style="padding:7px 16px;font-size:.78rem;font-weight:700;">
                🔬 Testing
            </button>
        </div>

        <div id="teamtabcontent-running">
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
                <div class="clay-card donut-panel" style="padding:16px;width:320px;flex:0 0 320px;display:flex;flex-direction:column;" data-reveal data-reveal-delay="120">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🍩 Porsi Lead per CS</span>
                    </div>
                    <div style="font-size:.68rem;color:#9ca3af;margin-top:2px;margin-bottom:10px;">
                        Hanya CS yang menerima lead pada rentang tanggal ini
                    </div>
                    @include('team.partials._donut', ['chartData' => $chartData, 'domId' => 'cs-donut'])
                </div>
            </div>
        @endif
        </div>{{{{-- /teamtabcontent-running --}}}

        <div id="teamtabcontent-testing" style="display:none;">
            @if($u->hasRole('cs'))
                {{-- ═══════ SISI CS — FASE TESTING ═══════ --}}
                <div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
                    <div style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🔬 Performa CS — Produk Testing</span>
                        <span class="clay-badge" style="font-size:.65rem;background:#B45309;color:#fff;">Regional Testing</span>
                    </div>
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
                {{-- ═══════ SISI ADVERTISER — FASE TESTING: tabel + donut testing ═══════ --}}
                <div style="display:flex;gap:16px;align-items:stretch;flex-wrap:wrap;">
                    <div class="clay-card" style="padding:0;overflow:hidden;flex:1 1 520px;min-width:0;" data-reveal>
                        <div style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🔬 Performa Semua CS — Produk Testing</span>
                            <span class="clay-badge" style="font-size:.65rem;background:#B45309;color:#fff;">Regional Testing</span>
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

                    <div class="clay-card donut-panel" style="padding:16px;width:320px;flex:0 0 320px;display:flex;flex-direction:column;" data-reveal data-reveal-delay="120">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🍩 Porsi Lead per CS (Testing)</span>
                        </div>
                        <div style="font-size:.68rem;color:#9ca3af;margin-top:2px;margin-bottom:10px;">
                            Hanya CS yang menerima lead TESTING pada rentang tanggal ini
                        </div>
                        @include('team.partials._donut', ['chartData' => $chartDataTesting, 'domId' => 'cs-donut-testing'])
                    </div>
                </div>
            @endif
        </div>{{{{-- /teamtabcontent-testing --}}}

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
    // Hover donut SCOPED PER PANEL — 2 donut (Running/Testing) masing-masing
    // punya svg + legend sendiri; jangan biarkan hover legend testing
    // me-highlight segmen donut running.
    document.querySelectorAll('.donut-panel').forEach((panel) => {
        const svg = panel.querySelector('svg');
        if (!svg) return;
        const segs = panel.querySelectorAll('.cs-donut-seg');
        const rows = panel.querySelectorAll('[data-cs-legend]');

        const highlight = (name) => {
            segs.forEach(s => {
                s.style.opacity = (!name || s.getAttribute('data-cs') === name) ? '1' : '0.15';
            });
        };

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

@push('scripts')
<script>
// ── Tab fase 🔵 Running / 🔬 Testing (DUAL FASE) ──
function switchTeamTab(tab) {
    const runBtn = document.getElementById('teamtab-btn-running');
    const testBtn = document.getElementById('teamtab-btn-testing');
    const runPanel = document.getElementById('teamtabcontent-running');
    const testPanel = document.getElementById('teamtabcontent-testing');
    if (!runBtn || !testBtn || !runPanel || !testPanel) return;

    const isRun = tab !== 'testing';
    runPanel.style.display = isRun ? '' : 'none';
    testPanel.style.display = isRun ? 'none' : '';
    runBtn.className = isRun ? 'clay-btn clay-btn-primary' : 'clay-btn clay-btn-outline';
    testBtn.className = isRun ? 'clay-btn clay-btn-outline' : 'clay-btn clay-btn-primary';

    // Kartu Total Lead/Paid (CS) ikut tab aktif (pola applySummary halaman Spending)
    document.querySelectorAll('[data-run][data-test]').forEach((el) => {
        const val = isRun ? el.getAttribute('data-run') : el.getAttribute('data-test');
        if (val !== null) el.textContent = val;
    });
}
</script>
@endpush
