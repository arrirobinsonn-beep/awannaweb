@extends('layouts.app')
@section('title','Whitelist')
@section('page-title','✅ Whitelist Akun Iklan')
@section('page-subtitle','Kelola akun iklan yang sudah diwhitelist')

@section('content')

@php
    $isAdvertiser = auth()->user()->hasRole('advertiser');
    $hasTabs = (isset($advertisers) && $advertisers->isNotEmpty());

    // Link tab: pertahankan filter search/platform/status, reset halaman ke 1
    $tabHref = function ($tab) {
        $q = request()->query();
        unset($q['page']);
        $q['tab'] = $tab;
        return url()->current().'?'.http_build_query($q);
    };
@endphp

{{-- Toolbar: pencarian + filter + aksi dalam satu card --}}
<div class="clay-card" style="padding:14px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;" data-reveal>
    <form method="GET" action="{{ route('whitelist.index') }}"
          style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;flex-wrap:nowrap;">
        <input type="hidden" name="tab" value="{{ $activeTab ?? 'all' }}">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari nama atau kode..."
               class="clay-input" style="flex:1;min-width:0;">
        <select name="platform" class="clay-input" style="width:auto;flex-shrink:0;">
            <option value="">Semua Platform</option>
            @foreach($platforms as $p)
            <option value="{{ $p }}" {{ request('platform')===$p?'selected':'' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
        <select name="status" class="clay-input" style="width:auto;flex-shrink:0;">
            <option value="">Semua Status</option>
            <option value="aktif"    {{ request('status')==='aktif'   ?'selected':'' }}>Aktif</option>
            <option value="nonaktif" {{ request('status')==='nonaktif'?'selected':'' }}>Nonaktif</option>
        </select>
        <button type="submit" class="clay-btn clay-btn-secondary" style="flex-shrink:0;">🔍</button>
        @if(request()->hasAny(['search','platform','status']))
        <a href="{{ route('whitelist.index', ['tab' => $activeTab ?? 'all']) }}" class="clay-btn clay-btn-outline" style="flex-shrink:0;">Reset</a>
        @endif
    </form>

    @if($isAdvertiser)
    <a href="{{ route('whitelist.create') }}" class="clay-btn clay-btn-primary" style="flex-shrink:0;" data-page-link>
        ＋ Tambah Whitelist
    </a>
    @endif
</div>

{{-- ── Folder Tabs per Advertiser ("Semua whitelist" paling kiri) ───────── --}}
@if($hasTabs)
<div style="display:flex;flex-wrap:wrap;gap:0;align-items:flex-end;
            margin-bottom:-2px;position:relative;z-index:2;" data-reveal>

    {{-- Tab kiri: Semua whitelist --}}
    <a href="{{ $tabHref('all') }}"
       style="padding:9px 18px 11px;text-decoration:none;
              border:2px solid {{ ($activeTab ?? 'all') === 'all' ? 'rgba(255,107,107,.25)' : 'rgba(0,0,0,.08)' }};
              border-bottom:2px solid {{ ($activeTab ?? 'all') === 'all' ? '#fff' : 'rgba(0,0,0,.08)' }};
              border-radius:14px 14px 0 0;
              background:{{ ($activeTab ?? 'all') === 'all' ? '#fff' : '#f5f5f5' }};
              font-family:inherit;font-size:.82rem;
              font-weight:{{ ($activeTab ?? 'all') === 'all' ? '700' : '500' }};
              color:{{ ($activeTab ?? 'all') === 'all' ? 'var(--color-primary,#FF6B6B)' : '#6b7280' }};
              cursor:pointer;transition:all .2s;
              display:flex;align-items:center;gap:8px;
              margin-right:4px;position:relative;z-index:{{ ($activeTab ?? 'all') === 'all' ? 3 : 1 }};">
        📋 Semua whitelist
        <span style="font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;
                     background:{{ ($activeTab ?? 'all') === 'all' ? 'rgba(255,107,107,.12)' : 'rgba(0,0,0,.06)' }};
                     color:{{ ($activeTab ?? 'all') === 'all' ? 'var(--color-primary)' : '#9ca3af' }};">
            {{ $allCount ?? 0 }}
        </span>
    </a>

    {{-- Tab per advertiser pemilik --}}
    @foreach($advertisers as $adv)
    @php $isActive = ($activeTab == $adv->id); @endphp
    <a href="{{ $tabHref($adv->id) }}"
       style="padding:9px 18px 11px;text-decoration:none;
              border:2px solid {{ $isActive ? 'rgba(255,107,107,.25)' : 'rgba(0,0,0,.08)' }};
              border-bottom:2px solid {{ $isActive ? '#fff' : 'rgba(0,0,0,.08)' }};
              border-radius:14px 14px 0 0;
              background:{{ $isActive ? '#fff' : '#f5f5f5' }};
              font-family:inherit;font-size:.82rem;
              font-weight:{{ $isActive ? '700' : '500' }};
              color:{{ $isActive ? 'var(--color-primary,#FF6B6B)' : '#6b7280' }};
              cursor:pointer;transition:all .2s;
              display:flex;align-items:center;gap:8px;
              margin-right:4px;position:relative;z-index:{{ $isActive ? 3 : 1 }};">
        <img src="{{ $adv->avatar_url }}"
             style="width:22px;height:22px;border-radius:6px;object-fit:cover;flex-shrink:0;
                    border:{{ $isActive ? '1.5px solid rgba(255,107,107,.3)' : '1.5px solid #ddd' }};">
        {{ $adv->display_name }}
        <span style="font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;
                     background:{{ $isActive ? 'rgba(255,107,107,.12)' : 'rgba(0,0,0,.06)' }};
                     color:{{ $isActive ? 'var(--color-primary)' : '#9ca3af' }};">
            {{ $countPerAdv[$adv->id] ?? 0 }}
        </span>
    </a>
    @endforeach
</div>
<div style="border:2px solid rgba(255,107,107,.18);border-radius:0 16px 16px 16px;
            background:#fff;overflow:hidden;position:relative;z-index:1;" data-reveal>
@else
<div class="clay-card" style="overflow:hidden;" data-reveal>
@endif

{{-- Tabel dengan expand row --}}
@include('whitelist._table')

@if($hasTabs)
</div>
@else
</div>
@endif

@push('styles')
<style>
    /* ── Batas tinggi tabel (maks 5 baris data, sisanya scroll vertikal) ── */
    .table-scroll-limit { overflow-y: auto; overscroll-behavior: contain; }
    .table-scroll-limit::-webkit-scrollbar { width: 8px; }
    .table-scroll-limit::-webkit-scrollbar-track { background: transparent; }
    .table-scroll-limit::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 999px; }
    .table-scroll-limit::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
    .table-scroll-limit { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
    .table-scroll-maxrows { max-height: calc(5 * 56px + 56px); }
    /* Header tetap terlihat saat scroll vertikal di dalam tabel
       (spesifisitas table.clay-table agar menang atas media query layout) */
    .table-scroll-limit table.clay-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fafafa; /* solid agar baris yang lewat tidak tembus */
        box-shadow: 0 2px 6px -3px rgba(0,0,0,.14);
    }
</style>
@endpush

@push('scripts')
<script>
var openRows = new Set();

function toggleRow(id) {
    var detail  = document.getElementById('detail-' + id);
    var chevron = document.getElementById('chevron-' + id);
    var isOpen  = openRows.has(id);

    if (isOpen) {
        detail.style.display  = 'none';
        chevron.style.transform = 'rotate(0deg)';
        openRows.delete(id);
    } else {
        detail.style.display  = 'table-row';
        chevron.style.transform = 'rotate(90deg)';
        openRows.add(id);
    }
}
</script>
@endpush

@push('scripts')
<script>
{{-- ── Batas tinggi tabel: tampilkan maksimal 5 baris data, sisanya scroll vertikal ── --}}
(function() {
    'use strict';

    var MAX_ROWS = 5;
    var scrollEl = document.querySelector('.table-scroll-maxrows');
    if (!scrollEl) return;

    var table = scrollEl.querySelector('table.clay-table');
    if (!table || !table.tBodies.length) return;

    var tbody = table.tBodies[0];
    // Baris terlihat = baris utama; baris detail (expand) awalnya display:none inline
    var visible = Array.prototype.filter.call(tbody.rows, function(r) {
        return r.style.display !== 'none';
    });

    // ≤ 5 baris → biarkan tinggi alami, tanpa scroll
    if (visible.length <= MAX_ROWS) return;

    function measure() {
        var head = table.tHead;
        var h = head ? head.offsetHeight : 0;
        for (var i = 0; i < MAX_ROWS; i++) h += visible[i].offsetHeight;
        return h;
    }

    scrollEl.style.maxHeight = measure() + 'px';

    // Pass 2: setelah scrollbar vertikal muncul, lebar konten menyusut & baris bisa
    // ikut berubah tinggi (reflow) → ukur sekali lagi agar tetap pas 5 baris.
    requestAnimationFrame(function() {
        scrollEl.style.maxHeight = measure() + 'px';
    });

    // Re-hitung saat layar berubah ukuran (teks bisa wrap ulang → baris lebih tinggi)
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            scrollEl.style.maxHeight = measure() + 'px';
        }, 150);
    });
})();
</script>
@endpush
@endsection