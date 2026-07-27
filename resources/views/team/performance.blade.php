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
/* Semua class TOTAL sticky: position sticky + background-clip */
.cs-total-lead,
.cs-total-paid,
.cs-total-ratio {
    position: sticky !important;
    background-clip: padding-box;
}
/* LEAD (paling kiri dari grup TOTAL) — offset 2 kolom (2×80px) dari kanan */
.cs-total-lead  { right: 160px; z-index: 5; }
/* PAID (tengah) — offset 1 kolom (1×80px) dari kanan */
.cs-total-paid  { right: 80px;  z-index: 6; }
/* RATIO (paling kanan) — nempel di tepi */
.cs-total-ratio { right: 0;     z-index: 7; }
/* Bayangan hanya di sel paling kanan */
.cs-total-ratio::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 8px;
    background: linear-gradient(to left, transparent, rgba(0,0,0,.08));
    pointer-events: none;
}
/* Top sticky untuk header baris 1 (top:0) */
thead .cs-total-lead,
thead .cs-total-paid,
thead .cs-total-ratio {
    top: 0;
}
/* Top sticky untuk header baris 2 (top:38px) */
thead tr:nth-child(2) .cs-total-lead,
thead tr:nth-child(2) .cs-total-paid,
thead tr:nth-child(2) .cs-total-ratio {
    top: 38px;
}
/* Z-index di tbody */
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
thead .cs-name-sticky {
    z-index: 5;
    top: 0;
}
thead tr:nth-child(2) .cs-name-sticky {
    top: 38px;
}
tbody .cs-name-sticky { z-index: 2; }
.text-right { text-align:right; }
/* Bayangan */
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
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7;">Total CS Aktif</div>
            <div style="font-size:1.5rem;font-weight:900;" data-counter="{{ $teamMembers->count() }}">0</div>
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
        {{-- Tabel performa per hari --}}
        <div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
            <div style="overflow-x:auto;">
                <table style="border-collapse:separate;border-spacing:0;width:100%;font-size:.78rem;white-space:nowrap;">
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
                            <th colspan="3" class="cs-total-ratio" style="background:#0d9488;color:#fff;padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;min-width:80px;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">
                                📊 TOTAL
                            </th>
                        </tr>
                        <tr style="position:sticky;top:38px;z-index:3;">
                            <th class="cs-name-sticky" style="background:#5B9BD5;color:#fff;padding:6px 14px;text-align:left;font-weight:600;font-size:.72rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">
                                {{ $teamMembers->count() }} CS
                            </th>
                            @foreach($allDates as $date)
                            <th style="background:#5B9BD5;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">LEAD</th>
                            <th style="background:#5B9BD5;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">PAID</th>
                            <th style="background:#5B9BD5;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.68rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">RATIO</th>
                            @endforeach
                            <th class="cs-total-lead" style="background:#0d9488;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">LEAD</th>
                            <th class="cs-total-paid" style="background:#0d9488;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.7rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">PAID</th>
                            <th class="cs-total-ratio" style="background:#0d9488;color:#fff;padding:6px 4px;text-align:center;font-weight:600;font-size:.68rem;outline:1px solid rgba(255,255,255,.15);outline-offset:-1px;">RATIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $csNames = $teamMembers->pluck('panggilan')->toArray(); @endphp
                        @forelse($csNames as $csIndex => $csName)
                        @php
                            $csData = $teamMembers->firstWhere('panggilan', $csName);
                            $rowBg = $csIndex % 2 === 0 ? '#ffffff' : '#fcfcfc';
                        @endphp
                        <tr style="transition:background .12s;background:{{ $rowBg }};"
                            onmouseenter="this.style.background='#f3f7fe'"
                            onmouseleave="this.style.background='{{ $rowBg }}'">
                            <td class="cs-name-sticky" style="background:{{ $rowBg }};padding:6px 14px;font-weight:700;font-size:.8rem;color:#1e1b2e;border-bottom:1px solid rgba(0,0,0,.05);white-space:nowrap;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    @if($csData)
                                    <img src="{{ $csData->avatar_url }}" alt="avatar"
                                         style="width:28px;height:28px;border-radius:8px;object-fit:cover;flex-shrink:0;">
                                    @endif
                                    <span>{{ $csName }}</span>
                                </div>
                            </td>
                            @php
                                $csTotalLead = 0;
                                $csTotalPaid = 0;
                            @endphp
                            @foreach($allDates as $dateIndex => $date)
                                @php
                                    $isAlt = $dateIndex % 2 === 0;
                                    $stripClass = 'cs-date-striped' . ($isAlt ? '' : ' cs-date-alt');

                                    $found = null;
                                    if (isset($byDate[$date])) {
                                        foreach ($byDate[$date] as $stat) {
                                            $statPanggilan = $stat->cs_panggilan;
                                            if ($statPanggilan === $csName) {
                                                $found = $stat;
                                                break;
                                            }
                                        }
                                    }
                                    $hasData = $found && ($found->lead > 0 || $found->paid > 0);
                                    $lead = $hasData ? $found->lead : 0;
                                    $paid = $hasData ? $found->paid : 0;
                                    $ratio = $lead > 0 ? round($paid / $lead * 100, 1) : 0;
                                    $csTotalLead += $lead;
                                    $csTotalPaid += $paid;
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
                            {{-- Total per CS (sticky kanan dengan offset) --}}
                            @php $totalRatio = $csTotalLead > 0 ? round($csTotalPaid / $csTotalLead * 100, 1) : 0; @endphp
                            <td class="cs-total-lead" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#1e1b2e;border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;">
                                {{ number_format($csTotalLead) }}
                            </td>
                            <td class="cs-total-paid" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#059669;border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;">
                                {{ number_format($csTotalPaid) }}
                            </td>
                            <td class="cs-total-ratio" style="padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;color:var(--color-primary);border-bottom:1px solid rgba(0,0,0,.05);background:#f0fdfa;">
                                {{ $totalRatio > 0 ? number_format($totalRatio, 1) . '%' : '0%' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="{{ count($allDates) * 3 + 3 }}" style="text-align:center;padding:48px 16px;">
                            <p style="color:#9ca3af;">Tidak ada CS dalam tim</p>
                        </td></tr>
                        @endforelse

                        {{-- Baris GRAND TOTAL --}}
                        @php
                            $grandLead = 0;
                            $grandPaid = 0;
                        @endphp
                        @foreach($allDates as $date)
                            @php
                                $dayLead = 0;
                                $dayPaid = 0;
                                if (isset($byDate[$date])) {
                                    foreach ($byDate[$date] as $stat) {
                                        $dayLead += $stat->lead;
                                        $dayPaid += $stat->paid;
                                    }
                                }
                                $grandLead += $dayLead;
                                $grandPaid += $dayPaid;
                            @endphp
                        @endforeach
                        @php $grandRatio = $grandLead > 0 ? round($grandPaid / $grandLead * 100, 1) : 0; @endphp
                        <tr style="position:sticky;bottom:0;z-index:4;background:#F0FFFE;">
                            <td class="cs-name-sticky" style="background:#F0FFFE;padding:8px 14px;font-weight:800;font-size:.82rem;color:#0d9488;border-top:2px solid #0d9488;">
                                📊 GRAND TOTAL
                            </td>
                            @foreach($allDates as $dateIndex => $date)
                                @php
                                    $dayLead = 0;
                                    $dayPaid = 0;
                                    if (isset($byDate[$date])) {
                                        foreach ($byDate[$date] as $stat) {
                                            $dayLead += $stat->lead;
                                            $dayPaid += $stat->paid;
                                        }
                                    }
                                    $dayRatio = $dayLead > 0 ? round($dayPaid / $dayLead * 100, 1) : 0;
                                    $isAlt = $dateIndex % 2 === 0;
                                    $stripClass = 'cs-date-striped' . ($isAlt ? '' : ' cs-date-alt');
                                @endphp
                                <td style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#1e1b2e;border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ number_format($dayLead) }}</td>
                                <td style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:#059669;border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ number_format($dayPaid) }}</td>
                                <td style="padding:8px 6px;text-align:center;font-weight:700;font-size:.8rem;color:var(--color-primary);border-top:2px solid #0d9488;" class="{{ $stripClass }}">{{ $dayRatio > 0 ? number_format($dayRatio, 1) . '%' : '0%' }}</td>
                            @endforeach
                            <td class="cs-total-lead" style="padding:8px 6px;text-align:center;font-weight:900;font-size:.9rem;color:#0d9488;border-top:2px solid #0d9488;background:#e6fffa;">{{ number_format($grandLead) }}</td>
                            <td class="cs-total-paid" style="padding:8px 6px;text-align:center;font-weight:900;font-size:.9rem;color:#059669;border-top:2px solid #0d9488;background:#e6fffa;">{{ number_format($grandPaid) }}</td>
                            <td class="cs-total-ratio" style="padding:8px 6px;text-align:center;font-weight:800;font-size:.85rem;color:var(--color-primary);border-top:2px solid #0d9488;background:#e6fffa;">{{ $grandRatio > 0 ? number_format($grandRatio, 1) . '%' : '0%' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif


</div>
@endsection
