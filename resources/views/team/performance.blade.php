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
                <div style="overflow-x:auto;">
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
            {{-- ═══════ SISI ADVERTISER: 2 tabel — CS Utama & CS Tamu ═══════ --}}
            <div class="clay-card" style="padding:0;overflow:hidden;margin-bottom:18px;" data-reveal>
                <div style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">⭐ CS Utama</span>
                    <span class="clay-badge clay-badge-green" style="font-size:.65rem;">CS yang dikhususkan untuk tim Anda</span>
                </div>
                <div style="overflow-x:auto;">
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

            <div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
                <div style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,.05);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🤝 CS Tamu</span>
                    <span class="clay-badge clay-badge-blue" style="font-size:.65rem;">CS lain yang menangani order — rotasi bulanan</span>
                </div>
                <div style="overflow-x:auto;">
                    <table style="border-collapse:separate;border-spacing:0;width:100%;font-size:.78rem;white-space:nowrap;">
                        @include('team.partials.performa-head', ['allDates' => $allDates, 'csCount' => $guestMembers->count()])
                        <tbody>
                            @include('team.partials.performa-rows', [
                                'csList' => $guestMembers,
                                'byDate' => $byDate,
                                'allDates' => $allDates,
                            ])
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    @endif

</div>
@endsection
