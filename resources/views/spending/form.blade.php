@extends('layouts.app')
@section('title', $mode==='edit' ? 'Edit Spending Harian' : 'Input Spending Harian')
@section('page-title', $mode==='edit' ? '✏️ Edit Spending Harian' : '➕ Input Spending Harian')
@section('page-subtitle', $mode==='edit'
    ? 'Edit data spending iklan harian — whitelist ' . ($spending->whitelist->nama ?? '') . ' (' . ($spending->whitelist->kode ?? '') . ')'
    : 'Catat spending, lead & paid — pilih tanggal, seret produk, centang whitelist, atau upload Excel Meta Ads')

@push('styles')
<style>
    /* ═══ Layout penuh ═══════════════════════════════════════ */
    .sp-full { width: 100%; }

    /* ── Step chip ─────────────────────────────────────────── */
    .step-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: #eef2ff; color: #4338ca;
        font-size: .62rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .06em; padding: 3px 10px; border-radius: 999px;
    }

    /* ── Palette produk (sumber drag) ─────────────────────── */
    .palette-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(165px, 1fr));
        gap: 10px;
        max-height: 240px; overflow-y: auto; padding-right: 4px;
    }
    .palette-item {
        position: relative;
        display: flex; align-items: center; gap: 8px;
        padding: 10px 12px;
        border: 2px dashed #e5e7eb; border-radius: 12px;
        background: #fff;
        cursor: grab; user-select: none;
        transition: all .16s ease;
    }
    .palette-item:hover {
        border-color: var(--color-primary, #FF6B6B);
        background: #fff7f7;
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(255,107,107,.14);
    }
    .palette-item:active { cursor: grabbing; }
    .palette-item.is-dragging { opacity: .45; }
    /* Produk yang sudah dipakai (di tanggal mana pun) → hilang dari palette */
    .palette-item.is-dropped { display: none !important; }
    .palette-item .palette-icon {
        width: 30px; height: 30px; flex-shrink: 0; border-radius: 9px;
        background: linear-gradient(135deg, #ffe4e6, #fff1f2);
        display: flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    .palette-item .palette-nama {
        font-size: .76rem; font-weight: 700; color: #1e1b2e; line-height: 1.25;
        overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    }
    .palette-item .palette-kode { font-size: .6rem; color: #9ca3af; font-weight: 600; }
    .palette-item .palette-add {
        margin-left: auto; flex-shrink: 0;
        width: 22px; height: 22px; border-radius: 7px;
        background: #f3f4f6; color: #6b7280;
        display: flex; align-items: center; justify-content: center;
        font-size: .78rem; font-weight: 800; cursor: pointer;
        transition: all .15s;
    }
    .palette-item .palette-add:hover {
        background: var(--color-primary, #FF6B6B); color: #fff;
        box-shadow: 0 3px 0 #e05555;
    }

    /* ── Drop zone ────────────────────────────────────────── */
    .drop-zone {
        position: relative;
        border-radius: 16px;
        transition: background .15s, box-shadow .15s;
    }
    .drop-zone.drop-active {
        background: #fff7f7;
        box-shadow: inset 0 0 0 2px rgba(255,107,107,.35);
    }
    .drop-placeholder {
        border: 2px dashed #e5e7eb; border-radius: 16px;
        padding: 38px 16px; text-align: center;
        color: #9ca3af; font-size: .88rem; font-weight: 600;
        transition: all .2s;
    }
    .drop-zone.drop-active .drop-placeholder {
        border-color: var(--color-primary, #FF6B6B);
        color: var(--color-primary, #FF6B6B);
        background: #fff7f7;
    }

    /* ── Group per tanggal (multi-tanggal) ────────────────── */
    .date-group { margin-bottom: 20px; }
    .date-group:last-child { margin-bottom: 0; }
    .date-group-head {
        display: flex; align-items: center; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;
    }
    .date-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: #1e1b2e; color: #fff;
        font-size: .72rem; font-weight: 800; padding: 5px 12px; border-radius: 999px;
    }
    .date-group-meta { font-size: .7rem; color: #9ca3af; font-weight: 600; }
    .date-group-remove {
        margin-left: auto; border: none; background: transparent; color: #9ca3af;
        font-size: .72rem; font-weight: 700; cursor: pointer;
        padding: 5px 10px; border-radius: 8px; transition: all .15s;
    }
    .date-group-remove:hover { background: #fef2f2; color: #ef4444; }

    /* ── Grid produk: maks 3 per baris ────────────────────── */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }
    @media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 760px)  { .product-grid { grid-template-columns: 1fr; } }

    /* ── Kartu produk ─────────────────────────────────────── */
    .prod-card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #f0ecec;
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(0,0,0,.05);
        overflow: hidden;
        animation: cardIn .25s ease;
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(-6px) scale(.98); }
        to   { opacity: 1; transform: none; }
    }
    .prod-card-head {
        display: flex; align-items: center; gap: 8px;
        padding: 12px 14px;
        border-bottom: 1px solid #f3f4f6;
        background: linear-gradient(135deg, #fff, #fff5f5);
    }
    .prod-card-title {
        font-size: .84rem; font-weight: 800; color: #1e1b2e;
        min-width: 0; flex: 1;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .prod-card-badge {
        flex-shrink: 0;
        background: #1e1b2e; color: #fff;
        font-size: .62rem; font-weight: 800; padding: 2px 8px; border-radius: 999px;
    }
    .btn-delete-prod {
        flex-shrink: 0;
        border: none; background: transparent; color: #9ca3af;
        font-size: .8rem; font-weight: 700; cursor: pointer;
        width: 28px; height: 28px; border-radius: 8px;
        transition: all .15s;
    }
    .btn-delete-prod:hover { background: #fef2f2; color: #ef4444; }
    /* Isi mengembang agar area rangkuman (footer) selalu rata di dasar kartu,
       sejajar antar produk dalam baris yang sama */
    .prod-card-body { flex: 1; padding: 14px; }
    .prod-card-foot {
        padding: 10px 14px;
        border-top: 1px solid #f3f4f6;
        background: #fafafa;
    }

    /* ── Daftar centang whitelist ─────────────────────────── */
    .wl-check-list {
        display: flex; flex-direction: column; gap: 8px;
        max-height: 268px; overflow-y: auto; padding-right: 4px;
    }
    .wl-check {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 12px;
        border: 2px solid #f3f4f6; border-radius: 12px;
        background: #fafafa; cursor: pointer; user-select: none;
        transition: all .15s;
    }
    .wl-check:hover { border-color: #e5e7eb; background: #fff; }
    .wl-check.checked { border-color: var(--color-primary, #FF6B6B); background: #fff5f5; }
    /* Visually-hidden tapi tetap di accessibility tree (bukan display:none) */
    .wl-check input {
        position: absolute; width: 1px; height: 1px;
        padding: 0; margin: -1px; overflow: hidden;
        clip: rect(0 0 0 0); clip-path: inset(50%);
        white-space: nowrap; border: 0;
    }
    .wl-check .wl-box-ind {
        width: 20px; height: 20px; flex-shrink: 0;
        border-radius: 7px; border: 2px solid #d1d5db; background: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .7rem; color: transparent; font-weight: 800;
        transition: all .15s;
    }
    .wl-check.checked .wl-box-ind {
        background: var(--color-primary, #FF6B6B);
        border-color: var(--color-primary, #FF6B6B);
        color: #fff;
    }

    /* ── Tombol Catat Spending ────────────────────────────── */
    .btn-catat { width: 100%; margin-top: 12px; }
    .btn-catat:disabled { opacity: .45; cursor: not-allowed; transform: none !important; box-shadow: 0 4px 0 #e05555 !important; }
    .btn-back-wl {
        border: none; background: transparent; color: #6b7280;
        font-size: .72rem; font-weight: 700; cursor: pointer;
        padding: 6px 10px; border-radius: 8px; transition: all .15s;
    }
    .btn-back-wl:hover { background: #f3f4f6; color: #1e1b2e; }

    /* ── Kotak whitelist di dalam kartu ──
       Kolom diatur inline oleh JS: 1 wl → penuh, 2 → dibagi 2, 3 → dibagi 3, 4+ → baris baru (maks 3) */
    .wl-boxes {
        display: grid;
        gap: 10px;
    }

    .wl-box {
        display: flex; flex-direction: column; gap: 8px;
        border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 12px; background: #fcfcfc;
        animation: cardIn .2s ease;
    }
    .wl-box-head { display: flex; align-items: center; gap: 6px; }
    .wl-box-name {
        font-size: .76rem; font-weight: 800; color: #1e1b2e;
        flex: 1; min-width: 0;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .wl-box-kode { font-size: .6rem; color: #9ca3af; font-weight: 700; }
    .wl-box-remove {
        flex-shrink: 0; border: none; background: #f3f4f6; color: #9ca3af;
        width: 22px; height: 22px; border-radius: 7px;
        font-size: .66rem; cursor: pointer; transition: all .15s;
    }
    .wl-box-remove:hover { background: #fef2f2; color: #ef4444; }
    .wl-field label {
        display: block; font-size: .6rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
        color: #9ca3af; margin-bottom: 3px;
    }
    .wl-field .clay-input { padding: 7px 10px; font-size: .85rem; }

    /* ── Ringkasan metrik per produk (spending/lead/paid + ratio/CPA) ─ */
    .prod-totals {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 6px;
    }
    .prod-totals .pt-cell {
        text-align: center;
        background: #F0FFFE; border-radius: 8px; padding: 6px 4px;
    }
    .prod-totals .pt-label {
        font-size: .55rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: #9ca3af;
    }
    .prod-totals .pt-value { font-weight: 800; font-size: .8rem; }

    /* ── Slot indikator saat drag (gestur turun ke baris baru) ─ */
    .drop-slot {
        display: none;
        align-items: center; justify-content: center;
        border: 2px dashed rgba(255,107,107,.55); border-radius: 16px;
        min-height: 112px; background: #fff7f7;
        color: var(--color-primary, #FF6B6B); font-size: .8rem; font-weight: 700;
        animation: slotPulse 1.1s ease-in-out infinite;
    }
    .drop-slot.active { display: flex; }
    @keyframes slotPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(255,107,107,.18); transform: scale(1); }
        50%      { box-shadow: 0 0 0 8px rgba(255,107,107,.06); transform: scale(1.015); }
    }

    /* ── Grand totals bar ─────────────────────────────────── */
    .grand-totals {
        display: flex; flex-wrap: wrap; gap: 18px; align-items: center;
        justify-content: flex-end;
        background: linear-gradient(135deg, #fff5f5, #fff);
        border: 1px solid #f0ecec; border-radius: 14px;
        padding: 12px 18px; font-size: .8rem;
    }
    .grand-totals .gt-item { text-align: right; }
    .grand-totals .gt-label {
        font-size: .6rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .05em; color: #9ca3af;
    }
    .grand-totals .gt-value { font-weight: 800; font-size: .95rem; }

    /* ── Tombol upload Excel ──────────────────────────────── */
    .btn-upload {
        display: inline-flex; align-items: center; gap: 8px;
        background: linear-gradient(135deg, #16a34a, #22c55e);
        color: #fff; border: none; border-radius: 12px;
        box-shadow: 0 4px 0 #15803d;
        padding: 10px 18px; font-size: .85rem; font-weight: 800; cursor: pointer;
        transition: all .15s; font-family: inherit;
    }
    .btn-upload:hover { transform: translateY(2px); box-shadow: 0 2px 0 #15803d; }
    .btn-upload:active { transform: translateY(3px); box-shadow: 0 1px 0 #15803d; }

    /* ── Modal upload Excel Meta Ads ──────────────────────── */
    #upload-modal {
        position: fixed; inset: 0; z-index: 1100;
        display: none; align-items: center; justify-content: center; padding: 20px;
    }
    #upload-modal.open { display: flex; }
    .up-backdrop {
        position: absolute; inset: 0;
        background: rgba(30,27,46,.5); backdrop-filter: blur(3px);
    }
    .up-panel {
        position: relative; width: 100%; max-width: 740px; max-height: 90vh;
        display: flex; flex-direction: column;
        background: #fff; border-radius: 20px;
        box-shadow: 0 24px 60px rgba(0,0,0,.28);
        overflow: hidden;
        animation: cardIn .22s ease;
    }
    .up-head {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 18px 22px; border-bottom: 1px solid #f3f4f6;
        background: linear-gradient(135deg, #fff5f5, #fff);
    }
    .up-x {
        margin-left: auto; border: none; background: #f3f4f6; color: #6b7280;
        width: 30px; height: 30px; border-radius: 10px; cursor: pointer;
        font-size: .8rem; font-weight: 800; transition: all .15s;
    }
    .up-x:hover { background: #fef2f2; color: #ef4444; }
    .up-body { flex: 1; overflow-y: auto; padding: 18px 22px; }
    .up-foot {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 22px; border-top: 1px solid #f3f4f6;
    }
    .up-format-hint {
        background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 10px 14px; font-size: .74rem; color: #6b7280; margin-bottom: 14px; line-height: 1.6;
    }

    /* ── Dua area upload bersampingan: Ads Manager + Regional ── */
    .up-dual {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        align-items: stretch;
    }
    @media (max-width: 640px) { .up-dual { grid-template-columns: 1fr; } }
    .up-col {
        display: flex; flex-direction: column;
        border: 1px solid #eef0f3; border-radius: 14px;
        background: #fcfcfd; padding: 12px;
    }
    .up-col-head {
        display: flex; align-items: center; gap: 6px;
        margin-bottom: 8px; font-size: .78rem; font-weight: 800; color: #1e1b2e;
    }
    .up-col-head .tag {
        background: #1e1b2e; color: #fff;
        font-size: .6rem; font-weight: 800; padding: 2px 7px; border-radius: 999px;
        flex-shrink: 0;
    }
    .up-col-head .sub {
        font-size: .62rem; font-weight: 600; color: #9ca3af;
        margin-left: auto; white-space: nowrap;
    }
    .up-col .up-dropzone {
        flex: 1;
        padding: 18px 12px;
    }
    .up-col .up-file-list { margin-top: 8px; }
    .up-format-hint code {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;
        padding: 1px 6px; font-size: .68rem; color: #4338ca;
    }
    .up-dropzone {
        border: 2px dashed #e5e7eb; border-radius: 16px; padding: 26px 16px;
        text-align: center; cursor: pointer; transition: all .15s;
    }
    .up-dropzone:hover, .up-dropzone.drag { border-color: var(--color-primary, #FF6B6B); background: #fff7f7; }
    .up-file-chip {
        display: flex; align-items: center; gap: 8px;
        background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;
        padding: 8px 12px; font-size: .76rem; margin-top: 8px;
    }
    .up-file-chip .x {
        margin-left: auto; border: none; background: transparent; color: #9ca3af;
        cursor: pointer; font-size: .8rem; font-weight: 800;
    }
    .up-file-chip .x:hover { color: #ef4444; }
    .up-summary {
        margin-top: 14px; font-size: .78rem; font-weight: 700; color: #166534;
        background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 9px 12px;
    }
    .up-result {
        border: 1px solid #e5e7eb; border-radius: 14px; margin-top: 12px; overflow: hidden;
    }
    .up-result-head {
        display: flex; align-items: center; gap: 8px;
        padding: 11px 14px; background: #fafafa; border-bottom: 1px solid #f3f4f6; flex-wrap: wrap;
    }
    .up-result-head .name {
        font-weight: 700; font-size: .78rem; color: #1e1b2e;
        min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .up-result-body { padding: 12px 14px; }
    .up-group { border-top: 1px solid #f3f4f6; padding: 12px 14px; }
    .up-group-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .up-group-check { display: flex; align-items: center; gap: 6px; font-size: .75rem; font-weight: 700; color: #1e1b2e; cursor: pointer; }
    .up-prods { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .up-prod-chip {
        background: #fff7f7; border: 1px solid #fecaca; color: #9a3412;
        border-radius: 8px; padding: 3px 10px; font-size: .7rem; font-weight: 600;
    }
    .up-err-msg { color: #dc2626; font-size: .78rem; padding: 12px 14px; }
    .up-warn {
        background: #fffbeb; border: 1px solid #fde68a; color: #92400e;
        font-size: .72rem; border-radius: 8px; padding: 8px 10px;
    }
    .up-warn code { background: #fff; border: 1px solid #fde68a; border-radius: 5px; padding: 0 4px; font-size: .66rem; }

    /* ── Toast (flash JS) ─────────────────────────────────── */
    .sp-toast {
        position: fixed; bottom: 26px; left: 50%;
        transform: translateX(-50%) translateY(24px);
        background: #1e1b2e; color: #fff; font-size: .8rem; font-weight: 600;
        padding: 13px 22px; border-radius: 14px;
        box-shadow: 0 12px 34px rgba(0,0,0,.3);
        opacity: 0; transition: all .25s; z-index: 1300;
        max-width: 92vw; text-align: center;
    }
    .sp-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
</style>
@endpush

@section('content')

<form method="POST" action="{{ $mode==='edit' ? route('spending.update', $spending) : route('spending.store') }}" id="spending-form" class="sp-full" novalidate>
    @csrf
    @if($mode==='edit')@method('PUT')@endif

    {{-- ═══ Langkah 1: Pilih Tanggal ═══════════════════════════ --}}
    <div class="clay-card" style="padding:20px;margin-bottom:14px;" data-reveal>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
            <span class="step-chip">1 · Pilih Tanggal</span>
            <span style="font-size:.72rem;color:#9ca3af;">Pilih tanggal untuk drag & drop manual — atau langsung upload Excel Meta Ads</span>
            @if($mode!=='edit')
            <span style="margin-left:auto;">
                <button type="button" id="btn-open-upload" class="btn-upload" title="Unggah export Meta Ads Manager (.xlsx)">
                    📤 Upload Excel Meta Ads
                </button>
            </span>
            @endif
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <input type="date" name="tanggal" id="input-tanggal"
                   value="{{ old('tanggal', $mode==='edit' ? $spending->tanggal->format('Y-m-d') : ($tanggal ?? now()->format('Y-m-d'))) }}"
                   class="clay-input" style="max-width:340px;font-size:1rem;font-weight:700;" required>
            @if($mode!=='edit')
            <span style="font-size:.7rem;color:#9ca3af;" id="drop-count">0 produk</span>
            @endif
        </div>
        @if($mode!=='edit')
        <p style="font-size:.72rem;color:#9ca3af;margin-top:10px;">
            📌 <strong>Manual:</strong> seret produk dari kotak di bawah ke area, centang whitelist, klik <strong>"Catat Spending"</strong>.
            &nbsp;·&nbsp; 📤 <strong>Upload:</strong> cukup pilih file export Meta Ads — produk, whitelist & spending terisi otomatis, tinggal isi lead & paid.
        </p>
        @endif
    </div>

    @if($mode!=='edit')
    {{-- ═══ Langkah 2: Palette Produk (drag & drop) ═════════════ --}}
    <div class="clay-card" id="product-palette-wrap" style="padding:20px;margin-bottom:14px;display:none;" data-reveal>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap;">
            <span class="step-chip">2 · Seret Produk</span>
            <span style="font-size:.72rem;color:#9ca3af;">Seret kotak produk ke bawah — atau klik <strong>＋</strong> untuk menambah</span>
            <input type="text" id="palette-search" class="clay-input" placeholder="🔍 Cari produk…"
                   style="max-width:240px;margin-left:auto;padding:7px 12px;font-size:.8rem;">
        </div>
        <div id="product-palette" class="palette-grid">
            @foreach($products as $p)
            <div class="palette-item" draggable="true" data-id="{{ $p->id }}"
                 data-nama="{{ $p->nama_produk }} ({{ $p->kode_produk }})"
                 data-search="{{ mb_strtolower($p->nama_produk) }} {{ mb_strtolower($p->kode_produk) }}">
                <span class="palette-icon">📦</span>
                <span style="min-width:0;flex:1;">
                    <span class="palette-nama">{{ $p->nama_produk }}</span>
                    <span class="palette-kode">{{ $p->kode_produk }}</span>
                </span>
                <span class="palette-add" title="Tambah ke area" role="button" tabindex="0"
                      aria-label="Tambah {{ $p->nama_produk }} ke area catat spending">＋</span>
            </div>
            @endforeach
            @if($products->isEmpty())
            <div style="grid-column:1/-1;text-align:center;color:#9ca3af;font-size:.8rem;padding:16px;">
                Belum ada produk aktif. Hubungi admin untuk menambahkan produk terlebih dahulu.
            </div>
            @endif
        </div>
        <div id="palette-empty-hint" style="display:none;margin-top:12px;text-align:center;font-size:.78rem;font-weight:700;color:var(--color-primary,#FF6B6B);background:#fff7f7;border:1px dashed rgba(255,107,107,.4);border-radius:12px;padding:10px;">
            ✅ Semua produk sudah dipilih — hapus kartu produk di area bawah untuk memilihnya kembali
        </div>
    </div>
    @endif

    {{-- ═══ Langkah 3: Area Catat Spending (drop zone) ═══════════ --}}
    <div class="clay-card" style="padding:20px;" data-reveal>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
            <span class="step-chip" style="background:#fff0f0;color:#dc2626;">3 · Catat Spending</span>
            <span style="font-size:.72rem;color:#9ca3af;">
                @if($mode==='edit') Ubah nilai spending, lead & paid di bawah ini. @else
                Data otomatis terpisah per tanggal — produk maksimal 3 per baris. @endif
            </span>
        </div>

        <div id="drop-zone" class="drop-zone">
            <div id="drop-placeholder" class="drop-placeholder">
                🖐️ <strong>Seret produk</strong> dari kotak di atas ke sini
                <div style="font-size:.68rem;font-weight:500;margin-top:4px;">
                    Setelah produk masuk, centang whitelist-nya lalu klik "Catat Spending" — atau gunakan 📤 Upload Excel Meta Ads
                </div>
            </div>
            <div id="date-groups"></div>
        </div>

        <div class="grand-totals" id="grand-totals" style="margin-top:16px;display:none;">
            <div class="gt-item">
                <div class="gt-label">Total Spending</div>
                <div class="gt-value" id="gt-spending" style="color:var(--color-primary);">Rp 0</div>
            </div>
            <div class="gt-item">
                <div class="gt-label">Lead</div>
                <div class="gt-value" id="gt-lead" style="color:var(--color-purple);">0</div>
            </div>
            <div class="gt-item">
                <div class="gt-label">Paid</div>
                <div class="gt-value" id="gt-paid" style="color:var(--color-secondary);">0</div>
            </div>
        </div>
    </div>

    {{-- ═══ Submit ═══════════════════════════════════════════════ --}}
    <div style="display:flex;gap:10px;margin-top:20px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
        <button type="button" class="clay-btn clay-btn-primary" id="btn-submit">
            {{ $mode==='edit' ? '💾 Simpan Perubahan' : '💾 Simpan Semua Spending' }}
        </button>
        <a href="{{ route('spending.index') }}" class="clay-btn clay-btn-outline" data-page-link>Batal</a>
    </div>
    <p style="font-size:.72rem;color:#9ca3af;margin-top:12px;text-align:center;">
        Semua data (termasuk beberapa tanggal sekaligus) akan disimpan dalam 1 kali aksi. Pastikan data sudah benar sebelum menyimpan.
    </p>
</form>
@endsection

@push('scripts')

{{-- ── Edit Mode: embed data spending untuk pre-fill ── --}}
@if($mode==='edit')
<script id="edit-data" type="application/json">
{
    "tanggal": "{{ $spending->tanggal->format('Y-m-d') }}",
    "product_id": {{ $spending->product_id }},
    "product_name": "{{ $spending->product?->nama_produk }} ({{ $spending->product?->kode_produk }})",
    "whitelist_id": {{ $spending->whitelist_id }},
    "whitelist_name": "{{ $spending->whitelist?->nama }}",
    "whitelist_code": "{{ $spending->whitelist?->kode }}",
    "spending": {{ $spending->spending }},
    "lead": {{ $spending->lead }},
    "paid": {{ $spending->paid }}
}
</script>
@endif

<script>
(function() {
    'use strict';

    // ─── Refs ──────────────────────────────────────────────
    var form          = document.getElementById('spending-form');
    var tanggalInput  = document.getElementById('input-tanggal');
    var paletteWrap   = document.getElementById('product-palette-wrap');
    var paletteEl     = document.getElementById('product-palette');
    var paletteSearch = document.getElementById('palette-search');
    var dropZone      = document.getElementById('drop-zone');
    var placeholderEl = document.getElementById('drop-placeholder');
    var groupsEl      = document.getElementById('date-groups');
    var submitBtn     = document.getElementById('btn-submit');
    var dropCount     = document.getElementById('drop-count');

    var isEditMode = document.getElementById('edit-data') !== null;

    // Whitelist milik advertiser (id, nama, kode, platform)
    var WL_LIST = @json($whitelists);

    // ─── State: group per tanggal ──────────────────────────
    // groups: Map<tanggal, { tanggal, wrap, head, grid, cards: Map<pid, st> }>
    // st = { name, phase(1|2), checked:Set, values:{wlId:{spending,lead,paid}}, wlFallback? }
    var groups = new Map();
    var dragTgl = null;

    var BULAN_INDO = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    function fmtNum(n) { return Number(n || 0).toLocaleString('id-ID'); }
    function fmtTgl(s) {
        if (!s) return '';
        var p = s.split('-');
        return parseInt(p[2], 10) + ' ' + BULAN_INDO[parseInt(p[1], 10) - 1] + ' ' + p[0];
    }
    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function attr(s) { return esc(s); }
    function showFlash(msg) {
        var t = document.createElement('div');
        t.className = 'sp-toast';
        t.textContent = msg;
        document.body.appendChild(t);
        requestAnimationFrame(function() { t.classList.add('show'); });
        setTimeout(function() { t.classList.remove('show'); }, 4200);
        setTimeout(function() { t.remove(); }, 4600);
    }

    // ─── Langkah 1: tampilkan palette setelah tanggal dipilih ──
    function showPalette() { if (paletteWrap) paletteWrap.style.display = ''; }
    function hidePalette() { if (paletteWrap) paletteWrap.style.display = 'none'; }

    if (tanggalInput) {
        tanggalInput.addEventListener('change', function() {
            if (this.value) showPalette();
        });
        if (tanggalInput.value) showPalette();
    }

    // ─── Filter produk di palette (produk yang sudah dipakai tetap tersembunyi) ─
    if (paletteSearch) {
        paletteSearch.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            paletteEl.querySelectorAll('.palette-item').forEach(function(item) {
                if (item.classList.contains('is-dropped')) return;
                item.style.display = (q === '' || item.dataset.search.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    // ─── Group per tanggal ─────────────────────────────────
    function getOrCreateGroup(tgl) {
        if (groups.has(tgl)) return groups.get(tgl);
        var g = { tanggal: tgl, cards: new Map() };

        var wrap = document.createElement('div');
        wrap.className = 'date-group';
        wrap.dataset.tgl = tgl;

        var head = document.createElement('div');
        head.className = 'date-group-head';
        head.innerHTML =
            '<span class="date-chip">📅 ' + fmtTgl(tgl) + '</span>'
            + '<span class="date-group-meta">0 produk</span>'
            + '<button type="button" class="date-group-remove" title="Hapus semua produk tanggal ini">🗑 Hapus tanggal</button>';

        var grid = document.createElement('div');
        grid.className = 'product-grid';

        wrap.appendChild(head);
        wrap.appendChild(grid);
        groupsEl.appendChild(wrap);

        g.wrap = wrap; g.head = head; g.grid = grid;
        groups.set(tgl, g);

        head.querySelector('.date-group-remove').addEventListener('click', function() {
            if (g.cards.size > 0 && !confirm('Hapus semua produk pada tanggal ' + fmtTgl(g.tanggal) + ' dari area?')) return;
            removeGroup(g.tanggal);
        });

        updatePlaceholder();
        return g;
    }

    function removeGroup(tgl) {
        var g = groups.get(tgl);
        if (!g) return;
        g.wrap.remove();
        groups.delete(tgl);
        refreshPalette();
        updatePlaceholder();
        updateDropCount();
        updateGroupMeta();
        updateGrandTotals();
    }

    function updateGroupMeta() {
        groups.forEach(function(g) {
            var cards = g.grid.querySelectorAll('.prod-card').length;
            var sp = 0;
            g.grid.querySelectorAll('.wl-spending').forEach(function(inp) { sp += parseFloat(inp.value) || 0; });
            var meta = g.wrap.querySelector('.date-group-meta');
            if (meta) meta.textContent = cards + ' produk · Rp ' + fmtNum(sp);
        });
    }

    function updatePlaceholder() {
        var hasAny = groups.size > 0 || dropSlot.classList.contains('active');
        if (placeholderEl) placeholderEl.style.display = hasAny ? 'none' : '';
    }

    function updateDropCount() {
        if (!dropCount) return;
        var total = 0;
        groups.forEach(function(g) { total += g.grid.querySelectorAll('.prod-card').length; });
        dropCount.textContent = total + ' produk';
    }

    // ─── Palette state (produk yang dipakai di tanggal mana pun → hilang) ─
    function isProductUsed(pid) {
        var used = false;
        groups.forEach(function(g) { if (g.cards.has(String(pid))) used = true; });
        return used;
    }
    function refreshPalette() {
        if (!paletteEl) return;
        paletteEl.querySelectorAll('.palette-item').forEach(function(item) {
            item.classList.toggle('is-dropped', isProductUsed(item.dataset.id));
        });
        updatePaletteEmptyHint();
    }
    function updatePaletteEmptyHint() {
        var hint = document.getElementById('palette-empty-hint');
        if (!hint || !paletteEl) return;
        var total = paletteEl.querySelectorAll('.palette-item').length;
        var used = paletteEl.querySelectorAll('.palette-item.is-dropped').length;
        hint.style.display = (total > 0 && used >= total) ? '' : 'none';
    }

    // ─── Drag & drop dari palette ─────────────────────────
    function makeDraggable() {
        if (!paletteEl) return;
        paletteEl.querySelectorAll('.palette-item').forEach(function(item) {
            if (item.dataset.dragBound) return;
            item.dataset.dragBound = '1';

            item.addEventListener('dragstart', function(e) {
                if (!tanggalInput || !tanggalInput.value) {
                    e.preventDefault();
                    alert('Pilih tanggal terlebih dahulu di Langkah 1.');
                    return;
                }
                dragTgl = tanggalInput.value;
                e.dataTransfer.setData('text/plain', JSON.stringify({ id: item.dataset.id, nama: item.dataset.nama }));
                e.dataTransfer.effectAllowed = 'copy';
                item.classList.add('is-dragging');
            });
            item.addEventListener('dragend', function() {
                item.classList.remove('is-dragging');
                hideDropSlot();
                if (dragTgl) {
                    var g = groups.get(dragTgl);
                    if (g && g.cards.size === 0) removeGroup(dragTgl);
                    dragTgl = null;
                }
            });
            item.addEventListener('click', function(e) {
                if (e.target.closest('.palette-add')) {
                    if (!tanggalInput || !tanggalInput.value) {
                        alert('Pilih tanggal terlebih dahulu di Langkah 1.');
                        return;
                    }
                    addProductCard(item.dataset.id, item.dataset.nama, tanggalInput.value);
                }
            });
            var addChip = item.querySelector('.palette-add');
            if (addChip) {
                addChip.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        if (!tanggalInput || !tanggalInput.value) {
                            alert('Pilih tanggal terlebih dahulu di Langkah 1.');
                            return;
                        }
                        addProductCard(item.dataset.id, item.dataset.nama, tanggalInput.value);
                    }
                });
            }
        });
    }

    // Slot indikator posisi drop — muncul di grid group tanggal aktif,
    // otomatis turun ke baris baru jika 3 produk sudah penuh.
    var dropSlot = document.createElement('div');
    dropSlot.className = 'drop-slot';
    dropSlot.id = 'drop-slot';
    dropSlot.innerHTML = '<div style="display:flex;align-items:center;gap:8px;">'
        + '<span style="font-size:1.2rem;">⬇</span>'
        + '<span>Lepaskan di sini — produk berikutnya turun ke baris baru</span>'
        + '</div>';

    function showDropSlot(g) {
        if (!dropSlot || isEditMode) return;
        g.grid.appendChild(dropSlot);
        dropSlot.classList.add('active');
    }
    function hideDropSlot() {
        if (dropSlot) dropSlot.classList.remove('active');
    }

    if (dropZone) {
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            dropZone.classList.add('drop-active');
            if (dragTgl) {
                var g = getOrCreateGroup(dragTgl);
                showDropSlot(g);
                updatePlaceholder();
            }
        });
        dropZone.addEventListener('dragleave', function(e) {
            if (!dropZone.contains(e.relatedTarget)) {
                dropZone.classList.remove('drop-active');
                hideDropSlot();
            }
        });
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.classList.remove('drop-active');
            hideDropSlot();
            var raw = e.dataTransfer.getData('text/plain');
            if (!raw || !dragTgl) return;
            try {
                var d = JSON.parse(raw);
                addProductCard(d.id, d.nama, dragTgl);
            } catch (err) { /* ignore */ }
        });
    }

    // ─── Kartu produk (per group) ──────────────────────────
    function getCardNumber(g, pid) {
        var cards = Array.from(g.grid.querySelectorAll('.prod-card'));
        var idx = cards.findIndex(function(c) { return c.dataset.pid === String(pid); });
        return idx + 1;
    }

    function updateCardNumbers(g) {
        Array.from(g.grid.querySelectorAll('.prod-card')).forEach(function(card, i) {
            var b = card.querySelector('.prod-card-badge');
            if (b) b.textContent = '#' + (i + 1);
        });
    }

    function addProductCard(pid, name, tgl) {
        if (!pid || !tgl) return;
        var g = getOrCreateGroup(tgl);
        if (g.cards.has(String(pid))) {
            var ex = g.grid.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
            if (ex) {
                ex.style.animation = 'none';
                ex.offsetHeight;
                ex.style.animation = 'cardIn .5s ease';
                setTimeout(function() { ex.style.animation = ''; }, 600);
            }
            return;
        }

        g.cards.set(String(pid), { name: name || 'Produk', phase: 1, checked: new Set(), values: {} });

        var card = document.createElement('div');
        card.className = 'prod-card';
        card.dataset.pid = String(pid);
        g.grid.appendChild(card);

        refreshPalette();
        updatePlaceholder();
        renderCard(g, String(pid));
    }

    // Tambah produk dari hasil upload Excel (langsung fase 2, whitelist dicentang + spending/lead/paid terisi)
    function addProductImport(pid, name, spending, wl, tgl, lead, paid) {
        if (!pid || !tgl) return;
        var g = getOrCreateGroup(tgl);
        var wid = String(wl.id);

        var st = g.cards.get(String(pid));
        if (st) {
            st.checked.add(wid);
            st.values[wid] = { spending: spending, lead: lead ?? '', paid: paid ?? '' };
            st.phase = 2;
            renderCard(g, String(pid));
            return;
        }

        st = {
            name: name || 'Produk',
            phase: 2,
            checked: new Set([wid]),
            values: {},
            wlFallback: { id: wl.id, nama: wl.nama || ('Whitelist #' + wl.id), kode: wl.kode || '' }
        };
        st.values[wid] = { spending: spending, lead: lead ?? '', paid: paid ?? '' };
        g.cards.set(String(pid), st);

        var card = document.createElement('div');
        card.className = 'prod-card';
        card.dataset.pid = String(pid);
        g.grid.appendChild(card);

        refreshPalette();
        updatePlaceholder();
        renderCard(g, String(pid));
    }

    function findWl(wid, fallback) {
        for (var i = 0; i < WL_LIST.length; i++) {
            if (String(WL_LIST[i].id) === String(wid)) return WL_LIST[i];
        }
        return fallback || null;
    }

    function renderCard(g, pid) {
        var st = g.cards.get(pid);
        if (!st) return;
        var card = g.grid.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
        if (!card) return;

        var headHtml, bodyHtml, footHtml;

        if (st.phase === 1) {
            // ── Fase 1: centang whitelist ──
            var checks = WL_LIST.map(function(w) {
                var isChecked = st.checked.has(String(w.id));
                return '<label class="wl-check' + (isChecked ? ' checked' : '') + '" data-wlid="' + w.id + '">'
                    + '<input type="checkbox"' + (isChecked ? ' checked' : '') + '>'
                    + '<span class="wl-box-ind">✓</span>'
                    + '<span style="flex:1;min-width:0;">'
                    +   '<span style="display:block;font-size:.78rem;font-weight:700;color:#1e1b2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + esc(w.nama) + '</span>'
                    +   '<span style="display:block;font-size:.62rem;color:#9ca3af;font-weight:600;">' + esc(w.kode || '') + (w.platform ? ' · ' + esc(w.platform) : '') + '</span>'
                    + '</span>'
                    + '</label>';
            }).join('');

            headHtml =
                '<span class="prod-card-badge">#' + getCardNumber(g, pid) + '</span>'
                + '<span class="prod-card-title">📦 ' + esc(st.name) + '</span>'
                + '<button type="button" class="btn-delete-prod" title="Hapus produk">✕</button>';

            bodyHtml =
                '<div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:8px;">'
                +   'Pilih whitelist yang mengiklankan produk ini <span id="wl-count-' + pid + '" style="color:var(--color-primary,#FF6B6B);">(0 dipilih)</span>'
                + '</div>'
                + '<div class="wl-check-list">' + (checks || '<div style="font-size:.75rem;color:#9ca3af;text-align:center;padding:10px;">Belum ada whitelist aktif.</div>') + '</div>'
                + '<button type="button" class="clay-btn clay-btn-primary btn-catat" disabled>✅ Catat Spending</button>';

            footHtml =
                '<div style="font-size:.7rem;color:#9ca3af;text-align:center;">Centang 1 atau lebih whitelist, lalu klik <strong>Catat Spending</strong>.</div>';
        } else {
            // ── Fase 2: kotak input whitelist terpilih ──
            var boxes = Array.from(st.checked).map(function(wid) {
                var fb = (st.wlFallback && String(st.wlFallback.id) === String(wid))
                    ? st.wlFallback
                    : { id: wid, nama: 'Whitelist #' + wid, kode: '' };
                var w = findWl(wid, fb);
                var v = st.values[wid] || { spending: '', lead: '', paid: '' };
                return '<div class="wl-box" data-wlid="' + wid + '">'
                    + '<div class="wl-box-head">'
                    +   '<span class="wl-box-name">' + esc(w.nama) + '</span>'
                    +   '<span class="wl-box-kode">' + esc(w.kode || '') + '</span>'
                    +   '<button type="button" class="wl-box-remove" title="Hapus whitelist ini">✕</button>'
                    + '</div>'
                    + '<div class="wl-field"><label>💰 Spending (Rp)</label>'
                    +   '<input type="number" class="clay-input wl-spending" min="0" step="any" placeholder="0" value="' + attr(v.spending) + '"></div>'
                    + '<div class="wl-field"><label>👤 Lead</label>'
                    +   '<input type="number" class="clay-input wl-lead" min="0" step="1" placeholder="0" value="' + attr(v.lead) + '"></div>'
                    + '<div class="wl-field"><label>💳 Paid</label>'
                    +   '<input type="number" class="clay-input wl-paid" min="0" step="1" placeholder="0" value="' + attr(v.paid) + '"></div>'
                    + '</div>';
            }).join('');

            headHtml =
                '<span class="prod-card-badge">#' + getCardNumber(g, pid) + '</span>'
                + '<span class="prod-card-title">📦 ' + esc(st.name) + '</span>'
                + '<span class="clay-badge clay-badge-green" style="flex-shrink:0;">✅ ' + st.checked.size + ' tercatat</span>'
                + '<button type="button" class="btn-delete-prod" title="Hapus produk">✕</button>';

            // Kolom kotak whitelist adaptif: 1 → penuh, 2 → dibagi 2, 3 → dibagi 3, 4+ → baris baru (maks 3)
            var nWl = st.checked.size;
            var cols = Math.min(Math.max(nWl, 1), 3);

            bodyHtml =
                '<div class="wl-boxes" style="grid-template-columns:repeat(' + cols + ', minmax(0, 1fr));">' + (boxes || '') + '</div>'
                + '<button type="button" class="btn-back-wl">← Ubah pilihan whitelist</button>';

            footHtml =
                '<div class="prod-totals">'
                + '<div class="pt-cell"><div class="pt-label">💰 Spending</div><div class="pt-value t-spending" style="color:var(--color-primary);">Rp 0</div></div>'
                + '<div class="pt-cell"><div class="pt-label">👤 Lead</div><div class="pt-value t-lead" style="color:var(--color-purple);">0</div></div>'
                + '<div class="pt-cell"><div class="pt-label">💳 Paid</div><div class="pt-value t-paid" style="color:var(--color-secondary);">0</div></div>'
                + '<div class="pt-cell"><div class="pt-label">Paid Ratio</div><div class="pt-value t-ratio" style="color:var(--color-orange);">—</div></div>'
                + '<div class="pt-cell"><div class="pt-label">CPA Lead</div><div class="pt-value t-cpa-lead" style="color:var(--color-purple);">—</div></div>'
                + '<div class="pt-cell"><div class="pt-label">CPA Paid</div><div class="pt-value t-cpa-paid" style="color:var(--color-orange);">—</div></div>'
                + '</div>';
        }

        card.innerHTML =
            '<div class="prod-card-head">' + headHtml + '</div>'
            + '<div class="prod-card-body">' + bodyHtml + '</div>'
            + (footHtml ? '<div class="prod-card-foot">' + footHtml + '</div>' : '');

        bindCardEvents(g, pid);
        updateCardNumbers(g);
        updateDropCount();
        updateGroupMeta();
        updateGrandTotals();
    }

    function updatePhase1UI(g, pid) {
        var st = g.cards.get(pid);
        var card = g.grid.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
        if (!st || !card) return;
        var countEl = card.querySelector('#wl-count-' + pid);
        if (countEl) countEl.textContent = '(' + st.checked.size + ' dipilih)';
        var btn = card.querySelector('.btn-catat');
        if (btn) btn.disabled = st.checked.size === 0;
    }

    function updateProductTotals(g, pid) {
        var card = g.grid.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
        if (!card) return;
        var sp = 0, lead = 0, paid = 0;
        card.querySelectorAll('.wl-box').forEach(function(box) {
            sp += parseFloat(box.querySelector('.wl-spending').value) || 0;
            lead += parseInt(box.querySelector('.wl-lead').value) || 0;
            paid += parseInt(box.querySelector('.wl-paid').value) || 0;
        });
        var ratio   = lead > 0 ? Math.round(paid / lead * 100) + '%' : '—';
        var cpaLead = lead > 0 ? 'Rp ' + fmtNum(Math.round(sp / lead)) : '—';
        var cpaPaid = paid > 0 ? 'Rp ' + fmtNum(Math.round(sp / paid)) : '—';

        var t = card.querySelector('.t-spending');
        if (t) t.textContent = 'Rp ' + fmtNum(sp);
        var tl = card.querySelector('.t-lead');
        if (tl) tl.textContent = fmtNum(lead);
        var tp = card.querySelector('.t-paid');
        if (tp) tp.textContent = fmtNum(paid);
        var tr = card.querySelector('.t-ratio');
        if (tr) tr.textContent = ratio;
        var cl = card.querySelector('.t-cpa-lead');
        if (cl) cl.textContent = cpaLead;
        var cp = card.querySelector('.t-cpa-paid');
        if (cp) cp.textContent = cpaPaid;
    }

    function updateGrandTotals() {
        var sp = 0, lead = 0, paid = 0, boxes = 0;
        groups.forEach(function(g) {
            g.grid.querySelectorAll('.wl-box').forEach(function(box) {
                sp += parseFloat(box.querySelector('.wl-spending').value) || 0;
                lead += parseInt(box.querySelector('.wl-lead').value) || 0;
                paid += parseInt(box.querySelector('.wl-paid').value) || 0;
                boxes++;
            });
        });
        var gt = document.getElementById('grand-totals');
        if (gt) {
            gt.style.display = boxes > 0 ? '' : 'none';
            var s = document.getElementById('gt-spending');
            var l = document.getElementById('gt-lead');
            var p = document.getElementById('gt-paid');
            if (s) s.textContent = 'Rp ' + fmtNum(sp);
            if (l) l.textContent = fmtNum(lead);
            if (p) p.textContent = fmtNum(paid);
        }
    }

    function bindCardEvents(g, pid) {
        var st = g.cards.get(pid);
        var card = g.grid.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
        if (!card || !st) return;

        // ── Hapus kartu produk ──
        var delBtn = card.querySelector('.btn-delete-prod');
        if (delBtn) {
            delBtn.addEventListener('click', function() {
                if (!confirm('Hapus produk "' + st.name + '" dari area?')) return;
                g.cards.delete(pid);
                card.remove();
                refreshPalette();
                if (g.cards.size === 0) {
                    removeGroup(g.tanggal);
                    return;
                }
                updatePlaceholder();
                updateCardNumbers(g);
                updateDropCount();
                updateGroupMeta();
                updateGrandTotals();
            });
        }

        if (st.phase === 1) {
            // ── Checkbox whitelist ──
            card.querySelectorAll('.wl-check input').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    var wid = cb.closest('.wl-check').dataset.wlid;
                    if (cb.checked) st.checked.add(String(wid));
                    else st.checked.delete(String(wid));
                    cb.closest('.wl-check').classList.toggle('checked', cb.checked);
                    updatePhase1UI(g, pid);
                });
            });

            // ── Catat Spending → fase 2 ──
            var catat = card.querySelector('.btn-catat');
            if (catat) {
                catat.addEventListener('click', function() {
                    if (st.checked.size === 0) return;
                    st.phase = 2;
                    renderCard(g, pid);
                });
            }
        } else {
            // ── Kotak whitelist: input + hapus ──
            card.querySelectorAll('.wl-box').forEach(function(box) {
                var wid = box.dataset.wlid;
                var spInp = box.querySelector('.wl-spending');
                var leadInp = box.querySelector('.wl-lead');
                var paidInp = box.querySelector('.wl-paid');

                var onInput = function() {
                    if (!st.values[wid]) st.values[wid] = {};
                    st.values[wid].spending = spInp.value;
                    st.values[wid].lead = leadInp.value;
                    st.values[wid].paid = paidInp.value;
                    updateProductTotals(g, pid);
                    updateGroupMeta();
                    updateGrandTotals();
                };
                spInp.addEventListener('input', onInput);
                leadInp.addEventListener('input', onInput);
                paidInp.addEventListener('input', onInput);

                box.querySelector('.wl-box-remove').addEventListener('click', function() {
                    st.checked.delete(String(wid));
                    delete st.values[wid];
                    if (st.checked.size === 0) {
                        st.phase = 1;
                        renderCard(g, pid);
                    } else {
                        renderCard(g, pid);
                    }
                });
            });

            // Tampilkan total & metrik segera (penting untuk prefill edit mode / import)
            updateProductTotals(g, pid);
            updateGroupMeta();
            updateGrandTotals();

            // ── Kembali ke pilihan whitelist ──
            var back = card.querySelector('.btn-back-wl');
            if (back) {
                back.addEventListener('click', function() {
                    st.phase = 1;
                    renderCard(g, pid);
                });
            }
        }
    }

    // ─── Edit mode: pre-fill 1 produk + 1 whitelist ─────────
    function initEditMode() {
        var el = document.getElementById('edit-data');
        if (!el) return;
        var data;
        try { data = JSON.parse(el.textContent); } catch (e) { return; }

        if (tanggalInput) {
            tanggalInput.value = data.tanggal;
            tanggalInput.disabled = true;
            tanggalInput.style.opacity = '0.7';
            tanggalInput.title = 'Tanggal tidak dapat diubah';
        }

        // Tanggal dikirim via hidden input (karena input date di-disable)
        var h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'tanggal';
        h.value = data.tanggal;
        form.appendChild(h);

        // Palette disembunyikan permanen di edit mode
        hidePalette();

        // Buat kartu produk langsung ke fase 2
        var g = getOrCreateGroup(data.tanggal);
        var pid = String(data.product_id);
        var wid = String(data.whitelist_id);
        g.cards.set(pid, {
            name: data.product_name || 'Produk',
            phase: 2,
            checked: new Set([wid]),
            values: {},
            wlFallback: { id: wid, nama: data.whitelist_name || 'Whitelist #' + wid, kode: data.whitelist_code || '' }
        });
        g.cards.get(pid).values[wid] = {
            spending: data.spending,
            lead: data.lead,
            paid: data.paid
        };

        var card = document.createElement('div');
        card.className = 'prod-card';
        card.dataset.pid = pid;
        g.grid.appendChild(card);

        // Tidak boleh hapus satu-satunya tanggal di edit mode
        var rm = g.wrap.querySelector('.date-group-remove');
        if (rm) rm.style.display = 'none';

        updatePlaceholder();
        renderCard(g, pid);
    }

    // ─── Submit ─────────────────────────────────────────────
    function removeReindexInputs() {
        form.querySelectorAll('[data-reindex]').forEach(function(el) { el.remove(); });
    }
    function addHidden(name, value) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = name;
        inp.value = value;
        inp.setAttribute('data-reindex', '1');
        form.appendChild(inp);
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            removeReindexInputs();

            if (!isEditMode) {
                // Create: kumpulkan items[] dari semua group tanggal
                var items = [];
                groups.forEach(function(g) {
                    g.grid.querySelectorAll('.prod-card').forEach(function(card) {
                        card.querySelectorAll('.wl-box').forEach(function(box) {
                            items.push({
                                tanggal: g.tanggal,
                                product_id: card.dataset.pid,
                                whitelist_id: box.dataset.wlid,
                                spending: box.querySelector('.wl-spending').value === '' ? '0' : box.querySelector('.wl-spending').value,
                                lead: box.querySelector('.wl-lead').value === '' ? '0' : box.querySelector('.wl-lead').value,
                                paid: box.querySelector('.wl-paid').value === '' ? '0' : box.querySelector('.wl-paid').value
                            });
                        });
                    });
                });

                if (items.length === 0) {
                    alert('Belum ada data spending. Seret produk / upload Excel, centang whitelist, lalu klik "Catat Spending".');
                    return;
                }

                items.forEach(function(it, idx) {
                    addHidden('items[' + idx + '][tanggal]', it.tanggal);
                    addHidden('items[' + idx + '][product_id]', it.product_id);
                    addHidden('items[' + idx + '][whitelist_id]', it.whitelist_id);
                    addHidden('items[' + idx + '][spending]', it.spending);
                    addHidden('items[' + idx + '][lead]', it.lead);
                    addHidden('items[' + idx + '][paid]', it.paid);
                });
            } else {
                // Edit: flat fields (tanggal dari hidden input)
                var card = null;
                groups.forEach(function(g) { if (!card) card = g.grid.querySelector('.prod-card'); });
                var box = card ? card.querySelector('.wl-box') : null;
                if (!card || !box) {
                    alert('Data tidak ditemukan.');
                    return;
                }
                addHidden('product_id', card.dataset.pid);
                addHidden('whitelist_id', box.dataset.wlid);
                addHidden('spending', box.querySelector('.wl-spending').value || '0');
                addHidden('lead', box.querySelector('.wl-lead').value || '0');
                addHidden('paid', box.querySelector('.wl-paid').value || '0');
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-sm"></span> Menyimpan...';
            form.submit();
        });
    }

    // ─── Modal Upload Excel Meta Ads ────────────────────────
    var PARSE_URL = "{{ route('spending.parse-upload') }}";
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var CSRF = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var upFiles = [];      // File ads manager terpilih
    var regFiles = [];     // File regional terpilih (lead & paid)
    var upCombined = [];   // hasil gabungan dari server
    var upRegUnmatched = [];
    var upRegUnmatchedCount = 0;

    function initUploadModal() {
        var modal  = document.getElementById('upload-modal');
        if (!modal) return;

        var closeBtn  = document.getElementById('up-close');
        var cancelBtn = document.getElementById('up-cancel');
        var backdrop  = document.querySelector('#upload-modal .up-backdrop');
        var browseBtn = document.getElementById('up-browse');
        var fileInput = document.getElementById('up-file-input');
        var dz        = document.getElementById('up-dropzone');
        var regBrowseBtn = document.getElementById('reg-browse');
        var regFileInput = document.getElementById('reg-file-input');
        var regDz        = document.getElementById('reg-dropzone');
        var parseBtn  = document.getElementById('up-parse');
        var applyBtn  = document.getElementById('up-apply');

        function open() {
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function close() {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }

        var openBtn = document.getElementById('btn-open-upload');
        if (openBtn) openBtn.addEventListener('click', open); // tidak ada di mode edit
        if (closeBtn) closeBtn.addEventListener('click', close);
        if (cancelBtn) cancelBtn.addEventListener('click', close);
        if (backdrop) backdrop.addEventListener('click', close);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('open')) close();
        });

        if (browseBtn) browseBtn.addEventListener('click', function() { fileInput.click(); });
        if (dz) {
            dz.addEventListener('click', function(e) {
                if (e.target.closest('#up-browse')) return;
                fileInput.click();
            });
            ['dragenter', 'dragover'].forEach(function(ev) {
                dz.addEventListener(ev, function(e) { e.preventDefault(); dz.classList.add('drag'); });
            });
            ['dragleave', 'drop'].forEach(function(ev) {
                dz.addEventListener(ev, function(e) { e.preventDefault(); dz.classList.remove('drag'); });
            });
            dz.addEventListener('drop', function(e) {
                addFiles(Array.from(e.dataTransfer.files));
            });
        }
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                addFiles(Array.from(this.files));
                this.value = '';
            });
        }

        // ── Area regional ──
        if (regBrowseBtn) regBrowseBtn.addEventListener('click', function() { regFileInput.click(); });
        if (regDz) {
            regDz.addEventListener('click', function(e) {
                if (e.target.closest('#reg-browse')) return;
                regFileInput.click();
            });
            ['dragenter', 'dragover'].forEach(function(ev) {
                regDz.addEventListener(ev, function(e) { e.preventDefault(); regDz.classList.add('drag'); });
            });
            ['dragleave', 'drop'].forEach(function(ev) {
                regDz.addEventListener(ev, function(e) { e.preventDefault(); regDz.classList.remove('drag'); });
            });
            regDz.addEventListener('drop', function(e) {
                addRegFiles(Array.from(e.dataTransfer.files));
            });
        }
        if (regFileInput) {
            regFileInput.addEventListener('change', function() {
                addRegFiles(Array.from(this.files));
                this.value = '';
            });
        }

        if (parseBtn) parseBtn.addEventListener('click', parseFiles);
        if (applyBtn) applyBtn.addEventListener('click', applyResults);
    }

    function addFiles(list) {
        list.forEach(function(f) {
            if (!/\.(xlsx|xls|csv)$/i.test(f.name)) {
                showFlash('⚠️ "' + f.name + '" bukan file Excel (.xlsx/.xls/.csv) — dilewati.');
                return;
            }
            if (f.size > 10 * 1024 * 1024) {
                showFlash('⚠️ "' + f.name + '" lebih dari 10MB — dilewati.');
                return;
            }
            upFiles.push(f);
        });
        renderFileList();
        // Hasil lama tidak relevan lagi
        upCombined = [];
        renderResults();
        var applyBtn = document.getElementById('up-apply');
        if (applyBtn) applyBtn.disabled = true;
    }

    function addRegFiles(list) {
        list.forEach(function(f) {
            if (!/\.(xlsx|xls|csv)$/i.test(f.name)) {
                showFlash('⚠️ "' + f.name + '" bukan file Excel (.xlsx/.xls/.csv) — dilewati.');
                return;
            }
            if (f.size > 10 * 1024 * 1024) {
                showFlash('⚠️ "' + f.name + '" lebih dari 10MB — dilewati.');
                return;
            }
            regFiles.push(f);
        });
        renderRegFileList();
        upCombined = [];
        renderResults();
        var applyBtn = document.getElementById('up-apply');
        if (applyBtn) applyBtn.disabled = true;
    }

    function renderFileList() {
        var wrap = document.getElementById('up-file-list');
        if (!wrap) return;
        wrap.innerHTML = '';
        upFiles.forEach(function(f, i) {
            var chip = document.createElement('div');
            chip.className = 'up-file-chip';
            chip.innerHTML = '<span>📄</span><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'
                + esc(f.name) + '</span><span style="font-size:.68rem;color:#9ca3af;">(' + Math.round(f.size / 1024) + ' KB)</span>'
                + '<button type="button" class="x" title="Hapus file">✕</button>';
            chip.querySelector('.x').addEventListener('click', function() {
                upFiles.splice(i, 1);
                renderFileList();
                upCombined = [];
                renderResults();
                var applyBtn = document.getElementById('up-apply');
                if (applyBtn) applyBtn.disabled = true;
            });
            wrap.appendChild(chip);
        });
    }

    function renderRegFileList() {
        var wrap = document.getElementById('reg-file-list');
        if (!wrap) return;
        wrap.innerHTML = '';
        regFiles.forEach(function(f, i) {
            var chip = document.createElement('div');
            chip.className = 'up-file-chip';
            chip.innerHTML = '<span>🗺️</span><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'
                + esc(f.name) + '</span><span style="font-size:.68rem;color:#9ca3af;">(' + Math.round(f.size / 1024) + ' KB)</span>'
                + '<button type="button" class="x" title="Hapus file">✕</button>';
            chip.querySelector('.x').addEventListener('click', function() {
                regFiles.splice(i, 1);
                renderRegFileList();
                upCombined = [];
                renderResults();
                var applyBtn = document.getElementById('up-apply');
                if (applyBtn) applyBtn.disabled = true;
            });
            wrap.appendChild(chip);
        });
        var actions = document.getElementById('up-actions');
        if (actions) actions.style.display = (upFiles.length || regFiles.length) ? '' : 'none';
    }

    function setParseState(loading) {
        var parseBtn = document.getElementById('up-parse');
        if (parseBtn) {
            parseBtn.disabled = loading;
            parseBtn.innerHTML = loading ? '<span class="spinner-sm"></span> Memproses file…' : '🔍 Proses & Parsing';
        }
    }

    function parseFiles() {
        if (!upFiles.length || !regFiles.length) {
            showFlash('⚠️ Unggah minimal 1 file Ads Manager DAN 1 file Regional di kedua area.');
            return;
        }
        var fd = new FormData();
        upFiles.forEach(function(f) { fd.append('files[]', f); });
        regFiles.forEach(function(f) { fd.append('regional[]', f); });

        setParseState(true);
        fetch(PARSE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd
        })
        .then(function(r) {
            return r.json().then(function(d) { return { ok: r.ok, d: d }; });
        })
        .then(function(res) {
            setParseState(false);
            if (!res.ok) {
                showFlash('❌ Gagal memproses file: ' + (res.d.message || 'terjadi kesalahan server.'));
                return;
            }
            upCombined = res.d.combined || [];
            upRegUnmatched = res.d.regional_unmatched || [];
            upRegUnmatchedCount = res.d.regional_unmatched_count || 0;
            renderResults();
        })
        .catch(function() {
            setParseState(false);
            showFlash('❌ Koneksi gagal. Coba lagi.');
        });
    }

    function renderResults() {
        var wrap = document.getElementById('up-results');
        if (!wrap) return;
        wrap.innerHTML = '';

        var totalDates = upCombined.length;
        var totalProducts = 0, totalSpending = 0, totalLead = 0, totalPaid = 0;
        upCombined.forEach(function(dt) {
            dt.whitelists.forEach(function(w) {
                totalProducts += w.products.length;
                totalSpending += w.total_spending;
                totalLead += w.total_lead;
                totalPaid += w.total_paid;
            });
        });

        if (totalDates) {
            var summary = document.createElement('div');
            summary.className = 'up-summary';
            summary.textContent = totalDates + ' tanggal · ' + totalProducts + ' produk siap dimuat — 💸 Rp ' + fmtNum(totalSpending)
                + ' · 👤 ' + fmtNum(totalLead) + ' lead · 💳 ' + fmtNum(totalPaid) + ' paid';
            wrap.appendChild(summary);
        }

        // Peringatan baris regional yang tidak bisa dipetakan
        if (upRegUnmatchedCount > 0) {
            var warn = document.createElement('div');
            warn.className = 'up-warn';
            warn.style.marginTop = '10px';
            warn.innerHTML = '⚠️ <strong>' + upRegUnmatchedCount + ' baris regional</strong> tidak bisa dipetakan (whitelist/produk tak dikenal) — detail di bawah.';
            wrap.appendChild(warn);
        }

        // Per tanggal → per whitelist → produk (spending + lead + paid)
        upCombined.forEach(function(dt, di) {
            var box = document.createElement('div');
            box.className = 'up-result';

            var head = document.createElement('div');
            head.className = 'up-result-head';
            var dtTotal = 0;
            dt.whitelists.forEach(function(w) { dtTotal += w.total_spending; });
            head.innerHTML =
                '<span style="font-size:1rem;">📅</span>'
                + '<span class="name">' + fmtTgl(dt.tanggal) + '</span>'
                + '<span class="clay-badge clay-badge-green" style="margin-left:auto;">Rp ' + fmtNum(dtTotal) + '</span>';
            box.appendChild(head);

            dt.whitelists.forEach(function(w, wi) {
                var wlEl = document.createElement('div');
                wlEl.className = 'up-group';
                wlEl.dataset.di = di;
                wlEl.dataset.wi = wi;

                var chips = w.products.map(function(p) {
                    return '<span class="up-prod-chip" style="display:inline-flex;flex-direction:column;align-items:flex-start;gap:2px;">'
                        + '<span>📦 ' + esc(p.product_name) + '</span>'
                        + '<span style="font-weight:800;">💸 Rp ' + fmtNum(p.spending) + '</span>'
                        + '<span style="font-size:.64rem;color:#6b7280;">👤 ' + fmtNum(p.lead) + ' lead · 💳 ' + fmtNum(p.paid) + ' paid</span>'
                        + '</span>';
                }).join('');

                wlEl.innerHTML =
                    '<div class="up-group-row">'
                    +   '<label class="up-group-check">'
                    +       '<input type="checkbox" checked> Terapkan'
                    +   '</label>'
                    +   '<span class="clay-badge clay-badge-purple" style="flex-shrink:0;">WL ' + esc(w.whitelist.kode) + '</span>'
                    +   '<strong style="font-size:.78rem;color:#1e1b2e;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + esc(w.whitelist.nama) + '</strong>'
                    +   '<span style="margin-left:auto;font-size:.7rem;color:#6b7280;white-space:nowrap;">Rp ' + fmtNum(w.total_spending) + ' · 👤 ' + fmtNum(w.total_lead) + ' · 💳 ' + fmtNum(w.total_paid) + '</span>'
                    + '</div>'
                    + '<div class="up-prods">' + chips + '</div>';
                box.appendChild(wlEl);
            });

            wrap.appendChild(box);
        });

        // Detail baris regional yang tidak dipetakan (jika ada)
        try {
            if (upRegUnmatched && upRegUnmatched.length) {
                var um = document.createElement('div');
                um.className = 'up-warn';
                um.style.marginTop = '10px';
                um.innerHTML = '⚠️ Baris regional tak dikenal (tidak dimuat):<ul style="margin:4px 0 0 16px;padding:0;">'
                    + upRegUnmatched.slice(0, 8).map(function(u) { return '<li>' + esc(u) + '</li>'; }).join('')
                    + (upRegUnmatched.length > 8 ? '<li>…dan ' + (upRegUnmatched.length - 8) + ' lainnya</li>' : '')
                    + '</ul>';
                wrap.appendChild(um);
            }
        } catch (e) {}

        var applyBtn = document.getElementById('up-apply');
        if (applyBtn) {
            applyBtn.disabled = totalDates === 0;
            applyBtn.innerHTML = totalDates ? ('✅ Terapkan ke Form (' + totalProducts + ' produk)') : '✅ Terapkan ke Form';
        }
    }

    function applyResults() {
        var applied = 0, groupsApplied = 0, firstTgl = null;

        upCombined.forEach(function(dt, di) {
            var anyWlApplied = false;
            dt.whitelists.forEach(function(w, wi) {
                var row = document.querySelector('#up-results .up-group[data-di="' + di + '"][data-wi="' + wi + '"]');
                if (!row) return;
                var cb = row.querySelector('.up-group-check input');
                if (cb && !cb.checked) return;
                if (!firstTgl) firstTgl = dt.tanggal;
                w.products.forEach(function(p) {
                    addProductImport(String(p.product_id), p.product_name, p.spending, w.whitelist, dt.tanggal, p.lead, p.paid);
                    applied++;
                });
                anyWlApplied = true;
            });
            if (anyWlApplied) groupsApplied++;
        });

        if (applied === 0) {
            showFlash('⚠️ Tidak ada data yang diterapkan. Centang minimal 1 baris whitelist.');
            return;
        }

        // Sinkronkan input tanggal global dengan tanggal pertama yang dimuat
        if (tanggalInput && !isEditMode) {
            tanggalInput.value = firstTgl;
            showPalette();
        }

        var modal = document.getElementById('upload-modal');
        if (modal) modal.classList.remove('open');
        document.body.style.overflow = '';

        var dz = document.getElementById('drop-zone');
        if (dz) dz.scrollIntoView({ behavior: 'smooth', block: 'start' });

        refreshPalette();
        updateDropCount();
        updateGroupMeta();
        updateGrandTotals();
        showFlash('✅ ' + applied + ' produk dari ' + groupsApplied + ' tanggal berhasil dimuat — spending, lead & paid terisi otomatis. Tinjau lalu klik Simpan.');
    }

    // ─── Init ───────────────────────────────────────────────
    makeDraggable();
    if (isEditMode) {
        initEditMode();
    }

    // Elemen modal dirender di bagian akhir halaman → tunggu DOM siap
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUploadModal);
    } else {
        initUploadModal();
    }

})();
</script>
@endpush

@push('body-end')

{{-- ─── Modal Upload Excel Meta Ads ─────────────────────────────── --}}
<div id="upload-modal" role="dialog" aria-modal="true" aria-labelledby="up-title">
    <div class="up-backdrop"></div>
    <div class="up-panel">
        <div class="up-head">
            <div>
                <div id="up-title" style="font-weight:800;font-size:1rem;color:#1e1b2e;">📤 Upload Excel Meta Ads</div>
                <div style="font-size:.72rem;color:#9ca3af;">Bulk upload — 1 file = 1 akun whitelist untuk satu periode laporan</div>
            </div>
            <button type="button" class="up-x" id="up-close" title="Tutup">✕</button>
        </div>

        <div class="up-body">
            <div class="up-format-hint">
                <strong>2 file wajib unggah:</strong><br>
                <strong>1 · Excel Ads Manager</strong> → spending. Nama file memuat kode whitelist + rentang tanggal
                (<code>OO---13722---...---5-Agu-2026---5-Agu-2026.xlsx</code>); nama kampanye diawali kode produk
                (<code>KSP - tes konten - 7/8/26</code>); spending dari kolom <em>"Jumlah yang dibelanjakan (IDR)"</em>.<br>
                <strong>2 · Excel Regional</strong> → lead & paid. Kolom <em>"product"</em> berbentuk
                <code>P.1 - Kacamata ... - 22760</code> (kode teritorial - nama produk - kode whitelist), kolom
                <em>"payment_status"</em> (baris = lead, "paid" = paid), kolom <em>"created_at"</em> (tanggal).
            </div>

            <div class="up-dual">
                {{-- Kolom 1: Ads Manager --}}
                <div class="up-col">
                    <div class="up-col-head">
                        <span class="tag">1</span>
                        <span>Ads Manager</span>
                        <span class="sub">💸 Spending</span>
                    </div>
                    <div id="up-dropzone" class="up-dropzone">
                        <div style="font-size:1.4rem;">📁</div>
                        <div style="font-weight:700;color:#1e1b2e;font-size:.82rem;">Seret file export Ads Manager</div>
                        <div style="font-size:.7rem;color:#9ca3af;">atau</div>
                        <button type="button" class="clay-btn clay-btn-outline" id="up-browse" style="margin-top:6px;padding:6px 14px;font-size:.75rem;">Pilih File…</button>
                        <div style="font-size:.62rem;color:#9ca3af;margin-top:6px;">Bisa banyak file (maks 20 · .xlsx/.xls/.csv · 10MB/file)</div>
                    </div>
                    <input type="file" id="up-file-input" multiple accept=".xlsx,.xls,.csv" hidden>
                    <div id="up-file-list" class="up-file-list"></div>
                </div>

                {{-- Kolom 2: Regional (lead & paid) --}}
                <div class="up-col">
                    <div class="up-col-head">
                        <span class="tag">2</span>
                        <span>Regional</span>
                        <span class="sub">👤 Lead & 💳 Paid</span>
                    </div>
                    <div id="reg-dropzone" class="up-dropzone">
                        <div style="font-size:1.4rem;">🗺️</div>
                        <div style="font-weight:700;color:#1e1b2e;font-size:.82rem;">Seret file export regional</div>
                        <div style="font-size:.7rem;color:#9ca3af;">atau</div>
                        <button type="button" class="clay-btn clay-btn-outline" id="reg-browse" style="margin-top:6px;padding:6px 14px;font-size:.75rem;">Pilih File…</button>
                        <div style="font-size:.62rem;color:#9ca3af;margin-top:6px;">Kolom product / payment_status / created_at</div>
                    </div>
                    <input type="file" id="reg-file-input" multiple accept=".xlsx,.xls,.csv" hidden>
                    <div id="reg-file-list" class="up-file-list"></div>
                </div>
            </div>

            <div id="up-actions" style="display:none;margin-top:14px;">
                <button type="button" class="clay-btn clay-btn-primary" id="up-parse" style="width:100%;justify-content:center;">🔍 Proses & Parsing</button>
            </div>

            <div id="up-results"></div>
        </div>

        <div class="up-foot">
            <button type="button" class="clay-btn clay-btn-outline" id="up-cancel">Batal</button>
            <button type="button" class="clay-btn clay-btn-primary" id="up-apply" disabled>✅ Terapkan ke Form</button>
        </div>
    </div>
</div>

@endpush
