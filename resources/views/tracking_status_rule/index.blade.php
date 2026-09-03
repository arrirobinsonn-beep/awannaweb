@extends('layouts.app')

@section('title', 'Aturan Status Aggregator')
@section('page-title', '📡 Aturan Status Aggregator')
@section('page-subtitle', 'Kelola mapping per dashboard — sumber dari export_templates, masing-masing punya header CSV & status sendiri')

@push('styles')
<style>
    .ts-info { font-size: .75rem; color: #4b5563; line-height: 1.6; }
    .ts-info b { color: #1e1b2e; }
    .ts-info code {
        background: #f3f4f6; padding: 1px 6px; border-radius: 5px;
        font-size: .7rem; color: #6d28d9; font-weight: 700;
    }

    /* ── Kartu dashboard ─────────────────────────── */
    .ts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }
    .ts-card {
        position: relative; border-radius: 18px; overflow: hidden;
        background: #fff; border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 2px 10px rgba(0,0,0,.05);
        transition: transform .18s ease, box-shadow .18s ease;
        display: flex; flex-direction: column;
    }
    .ts-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,0,0,.1); }
    .ts-card .ts-card-head {
        padding: 18px 20px 14px; color: #fff;
        display: flex; align-items: center; gap: 12px;
    }
    .ts-card .ts-card-icon {
        width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
        background: rgba(255,255,255,.22); display: flex; align-items: center;
        justify-content: center; font-size: 1.35rem;
    }
    .ts-card .ts-card-title { font-size: 1.05rem; font-weight: 900; letter-spacing: .3px; }
    .ts-card .ts-card-sub { font-size: .68rem; opacity: .85; font-weight: 600; margin-top: 2px; }
    .ts-card .ts-card-body { padding: 14px 20px 18px; flex: 1; }
    .ts-stat {
        display: flex; align-items: center; justify-content: space-between;
        padding: 9px 0; border-bottom: 1px dashed rgba(0,0,0,.08); font-size: .8rem;
    }
    .ts-stat:last-of-type { border-bottom: none; }
    .ts-stat .lbl { color: #6b7280; font-weight: 600; }
    .ts-stat .val { font-weight: 800; color: #1e1b2e; font-size: .95rem; }
    .ts-card-edit {
        display: block; text-align: center; margin-top: 6px; padding: 10px;
        border-radius: 12px; font-weight: 800; font-size: .82rem; text-decoration: none;
        transition: all .15s ease;
    }
    .ts-card-edit:hover { filter: brightness(1.05); transform: translateY(-1px); }
</style>
@endpush

@section('content')

{{-- Info cara kerja --}}
<div class="clay-card" style="padding:14px 18px;margin-bottom:16px;background:linear-gradient(135deg,#FFF7F7,#fff);" data-reveal>
    <div class="ts-info">
        💡 <b>Cara kerja:</b> tiap ekspedisi (FLIK / SiCepat / SPX) punya nama kolom & isi file yang berbeda-beda — jadi diatur
        <b>per dashboard</b>. Pilih ekspedisi → upload file dashboard aslinya → untuk tiap <b>kolom database</b> (tetap, kiri)
        pilih <b>header CSV</b> yang mengisinya (resi, HP, nama, status, kolom masalah, dst), lalu kelola <b>aturan status</b>
        (teks status file → status sistem). Perubahan langsung berlaku untuk import status berikutnya — tanpa ubah kode.
    </div>
</div>

<div class="ts-grid">
    @foreach($sources as $s)
        @php
            // Meta kartu: cari di export_templates atau fallback ke hardcoded
            $tpl = $templateMap[$s] ?? null;
            $tplName = $tpl?->name ?? strtoupper($s);
            $tplCouriers = $tpl?->couriers ?? [];
            $icon = match(true) {
                str_contains($s, 'flik') => '📦',
                str_contains($s, 'sicepat') => '🚚',
                str_contains($s, 'spx') => '🛵',
                default => '📋',
            };
            $colors = [
                'flik' => 'linear-gradient(135deg,#4F46E5,#7C3AED)',
                'sicepat' => 'linear-gradient(135deg,#EA580C,#F97316)',
                'spx' => 'linear-gradient(135deg,#0F766E,#14B8A6)',
            ];
            $color = $colors[$s] ?? 'linear-gradient(135deg,#6B7280,#9CA3AF)';
            $hCount = $headerCounts[$s] ?? 0;
            $rCount = $ruleCounts[$s] ?? 0;
        @endphp
        <div class="ts-card" data-reveal>
            <div class="ts-card-head" style="background:{{ $color }};">
                <div class="ts-card-icon">{{ $icon }}</div>
                <div>
                    <div class="ts-card-title">{{ $tplName }}</div>
                    <div class="ts-card-sub">Source: {{ strtoupper($s) }}{{ $tpl ? ' — Template Export' : '' }}</div>
                </div>
            </div>
            <div class="ts-card-body">
                @if($tpl)
                    <div class="ts-stat">
                        <span class="lbl">📦 Template Export</span>
                        <span class="val" style="font-size:.8rem;">{{ $tpl->name }}</span>
                    </div>
                    <div class="ts-stat">
                        <span class="lbl">🚚 Courier</span>
                        <span class="val" style="font-size:.75rem;">{{ implode(', ', $tplCouriers) }}</span>
                    </div>
                @else
                    <div class="ts-stat">
                        <span class="lbl" style="color:#dc2626;">⚠️ Tidak ada template export</span>
                        <span class="val" style="font-size:.75rem;color:#dc2626;">Buat dulu di Aturan Export</span>
                    </div>
                @endif
                <div class="ts-stat">
                    <span class="lbl">🧩 Mapping header CSV</span>
                    <span class="val">{{ $hCount }} kolom</span>
                </div>
                <div class="ts-stat">
                    <span class="lbl">🗂 Aturan status</span>
                    <span class="val">{{ $rCount }} rule</span>
                </div>
                <a href="{{ route('tracking-status-rule.edit', $s) }}" class="ts-card-edit"
                   style="color:#fff;background:{{ $color }};" data-page-link>✏️ Edit {{ strtoupper($s) }}</a>
            </div>
        </div>
    @endforeach
</div>

{{-- Tambah source baru dari export template --}}
@php
    $existingSources = collect($sources);
    $newTemplates = $exportTemplates->filter(fn ($t) => ! $existingSources->contains($t->key));
@endphp
@if($newTemplates->isNotEmpty())
<div class="clay-card" style="padding:14px 18px;margin-top:16px;background:linear-gradient(135deg,#F0FDF4,#fff);" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <div style="font-size:.85rem;font-weight:800;">➕ Tambah Dashboard Baru</div>
            <div style="font-size:.72rem;color:#6b7280;margin-top:2px;">Template export yang belum punya aturan status — klik untuk mulai konfigurasi</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @foreach($newTemplates as $nt)
                <a href="{{ route('tracking-status-rule.edit', $nt->key) }}" class="clay-btn clay-btn-outline"
                   style="padding:6px 14px;font-size:.78rem;" data-page-link>📋 {{ $nt->name }} ({{ strtoupper($nt->key) }})</a>
            @endforeach
        </div>
    </div>
</div>
@endif

@endsection
