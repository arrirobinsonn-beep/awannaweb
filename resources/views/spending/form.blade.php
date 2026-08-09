@extends('layouts.app')
@section('title', $mode==='edit' ? 'Edit Spending Harian' : 'Input Spending Harian')
@section('page-title', $mode==='edit' ? '✏️ Edit Spending Harian' : '➕ Input Spending Harian')
@section('page-subtitle', $mode==='edit'
    ? 'Edit data spending iklan harian — whitelist ' . ($spending->whitelist->nama ?? '') . ' (' . ($spending->whitelist->kode ?? '') . ')'
    : 'Catat spending, lead & paid — pilih tanggal, seret produk, centang whitelist')

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
    /* Produk yang sudah diseret ke area → hilang dari palette */
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
</style>
@endpush

@section('content')

<form method="POST" action="{{ $mode==='edit' ? route('spending.update', $spending) : route('spending.store') }}" id="spending-form" class="sp-full" novalidate>
    @csrf
    @if($mode==='edit')@method('PUT')@endif

    {{-- ═══ Langkah 1: Pilih Tanggal ═══════════════════════════ --}}
    <div class="clay-card" style="padding:20px;margin-bottom:14px;" data-reveal>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <span class="step-chip">1 · Pilih Tanggal</span>
            <span style="font-size:.72rem;color:#9ca3af;">Pilih tanggal terlebih dahulu — produk akan muncul setelahnya</span>
        </div>
        <input type="date" name="tanggal" id="input-tanggal"
               value="{{ old('tanggal', $mode==='edit' ? $spending->tanggal->format('Y-m-d') : ($tanggal ?? now()->format('Y-m-d'))) }}"
               class="clay-input" style="max-width:340px;font-size:1rem;font-weight:700;" required>
        @if($mode!=='edit')
        <p style="font-size:.72rem;color:#9ca3af;margin-top:10px;">
            📌 Langkah berikutnya: <strong>seret produk</strong> dari kotak produk ke area di bawah, centang whitelist yang mengiklankan produk itu, lalu klik <strong>"Catat Spending"</strong>.
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
                 data-nama="{{ $p->name }} ({{ $p->code }})"
                 data-search="{{ mb_strtolower($p->name) }} {{ mb_strtolower($p->code) }}">
                <span class="palette-icon">📦</span>
                <span style="min-width:0;flex:1;">
                    <span class="palette-nama">{{ $p->name }}</span>
                    <span class="palette-kode">{{ $p->code }}</span>
                </span>
                <span class="palette-add" title="Tambah ke area" role="button" tabindex="0"
                      aria-label="Tambah {{ $p->name }} ke area catat spending">＋</span>
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
                Produk maksimal 3 per baris — produk berikutnya otomatis ke baris baru. @endif
            </span>
            @if($mode!=='edit')
            <span style="margin-left:auto;font-size:.72rem;color:#9ca3af;" id="drop-count">0 produk</span>
            @endif
        </div>

        <div id="drop-zone" class="drop-zone">
            <div id="drop-placeholder" class="drop-placeholder">
                🖐️ <strong>Seret produk</strong> dari kotak di atas ke sini
                <div style="font-size:.68rem;font-weight:500;margin-top:4px;">Setelah produk masuk, centang whitelist-nya lalu klik "Catat Spending"</div>
            </div>
            <div id="product-cards" class="product-grid"></div>
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
        Semua data akan disimpan dalam 1 kali aksi. Pastikan data sudah benar sebelum menyimpan.
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
    "product_name": "{{ $spending->product?->name }} ({{ $spending->product?->code }})",
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
    var cardsEl       = document.getElementById('product-cards');
    var submitBtn     = document.getElementById('btn-submit');
    var dropCount     = document.getElementById('drop-count');

    var isEditMode = document.getElementById('edit-data') !== null;

    // Whitelist milik advertiser (id, nama, kode, platform)
    var WL_LIST = @json($whitelists);

    // state: productId -> { name, phase(1|2), checked:Set, values:{wlId:{spending,lead,paid}} }
    var state = new Map();

    function fmtNum(n) { return Number(n || 0).toLocaleString('id-ID'); }
    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function attr(s) { return esc(s); }

    // ─── Langkah 1: tampilkan palette setelah tanggal dipilih ──
    function showPalette() { if (paletteWrap) paletteWrap.style.display = ''; }
    function hidePalette() { if (paletteWrap) paletteWrap.style.display = 'none'; }

    if (tanggalInput) {
        tanggalInput.addEventListener('change', function() {
            if (this.value) showPalette();
        });
        if (tanggalInput.value) showPalette();
    }

    // ─── Filter produk di palette (produk yang sudah diseret tetap tersembunyi) ─
    if (paletteSearch) {
        paletteSearch.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            paletteEl.querySelectorAll('.palette-item').forEach(function(item) {
                if (item.classList.contains('is-dropped')) return;
                item.style.display = (q === '' || item.dataset.search.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    // ─── Drag & drop dari palette ─────────────────────────
    function makeDraggable() {
        if (!paletteEl) return;
        paletteEl.querySelectorAll('.palette-item').forEach(function(item) {
            if (item.dataset.dragBound) return;
            item.dataset.dragBound = '1';

            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', JSON.stringify({ id: item.dataset.id, nama: item.dataset.nama }));
                e.dataTransfer.effectAllowed = 'copy';
                item.classList.add('is-dragging');
            });
            item.addEventListener('dragend', function() {
                item.classList.remove('is-dragging');
                hideDropSlot();
            });
            item.addEventListener('click', function(e) {
                if (e.target.closest('.palette-add')) {
                    addProductCard(item.dataset.id, item.dataset.nama);
                }
            });
            var addChip = item.querySelector('.palette-add');
            if (addChip) {
                addChip.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        addProductCard(item.dataset.id, item.dataset.nama);
                    }
                });
            }
        });
    }

    // Slot indikator posisi drop — muncul saat drag, menempati sel berikutnya
    // di grid (otomatis turun ke baris baru jika 3 produk sudah penuh).
    var dropSlot = document.createElement('div');
    dropSlot.className = 'drop-slot';
    dropSlot.id = 'drop-slot';
    dropSlot.innerHTML = '<div style="display:flex;align-items:center;gap:8px;">'
        + '<span style="font-size:1.2rem;">⬇</span>'
        + '<span>Lepaskan di sini — produk berikutnya turun ke baris baru</span>'
        + '</div>';
    cardsEl.appendChild(dropSlot);

    function showDropSlot() {
        if (!dropSlot) return;
        if (isEditMode) return;
        if (cardsEl.querySelectorAll('.prod-card').length === 0) return;
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
            showDropSlot();
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
            if (!raw) return;
            try {
                var d = JSON.parse(raw);
                addProductCard(d.id, d.nama);
            } catch (err) { /* ignore */ }
        });
    }

    // ─── Kartu produk ─────────────────────────────────────
    function getCardNumber(pid) {
        var cards = Array.from(cardsEl.querySelectorAll('.prod-card'));
        var idx = cards.findIndex(function(c) { return c.dataset.pid === String(pid); });
        return idx + 1;
    }

    function updateCardNumbers() {
        Array.from(cardsEl.querySelectorAll('.prod-card')).forEach(function(card, i) {
            var b = card.querySelector('.prod-card-badge');
            if (b) b.textContent = '#' + (i + 1);
        });
    }

    function updatePlaceholder() {
        var has = cardsEl.querySelectorAll('.prod-card').length > 0;
        if (placeholderEl) placeholderEl.style.display = has ? 'none' : '';
    }

    function updateDropCount() {
        if (dropCount) dropCount.textContent = cardsEl.querySelectorAll('.prod-card').length + ' produk';
    }

    // Tampilkan/sembunyikan item produk di palette (true = muncul lagi)
    function setPaletteItemState(pid, visible) {
        if (!paletteEl) return;
        var item = paletteEl.querySelector('.palette-item[data-id="' + String(pid) + '"]');
        if (item) item.classList.toggle('is-dropped', !visible);
        updatePaletteEmptyHint();
    }

    function updatePaletteEmptyHint() {
        var hint = document.getElementById('palette-empty-hint');
        if (!hint || !paletteEl) return;
        var total = paletteEl.querySelectorAll('.palette-item').length;
        var dropped = paletteEl.querySelectorAll('.palette-item.is-dropped').length;
        hint.style.display = (total > 0 && dropped >= total) ? '' : 'none';
    }

    function addProductCard(pid, name) {
        if (!pid) return;
        if (state.has(String(pid))) {
            var ex = cardsEl.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
            if (ex) {
                ex.style.animation = 'none';
                ex.offsetHeight;
                ex.style.animation = 'cardIn .5s ease';
                setTimeout(function() { ex.style.animation = ''; }, 600);
            }
            return;
        }

        state.set(String(pid), { name: name || 'Produk', phase: 1, checked: new Set(), values: {} });

        var card = document.createElement('div');
        card.className = 'prod-card';
        card.dataset.pid = String(pid);
        cardsEl.appendChild(card);

        // Produk yang sudah diseret hilang dari pilihan palette
        setPaletteItemState(pid, false);

        updatePlaceholder();
        renderCard(String(pid));
    }

    function findWl(wid, fallback) {
        for (var i = 0; i < WL_LIST.length; i++) {
            if (String(WL_LIST[i].id) === String(wid)) return WL_LIST[i];
        }
        return fallback || null;
    }

    function renderCard(pid) {
        var st = state.get(pid);
        if (!st) return;
        var card = cardsEl.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
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
                '<span class="prod-card-badge">#' + getCardNumber(pid) + '</span>'
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
                '<span class="prod-card-badge">#' + getCardNumber(pid) + '</span>'
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

        bindCardEvents(pid);
        updateCardNumbers();
        updateDropCount();
        updateGrandTotals();
    }

    function updatePhase1UI(pid) {
        var st = state.get(pid);
        var card = cardsEl.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
        if (!st || !card) return;
        var countEl = card.querySelector('#wl-count-' + pid);
        if (countEl) countEl.textContent = '(' + st.checked.size + ' dipilih)';
        var btn = card.querySelector('.btn-catat');
        if (btn) btn.disabled = st.checked.size === 0;
    }

    function updateProductTotals(pid) {
        var card = cardsEl.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
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
        cardsEl.querySelectorAll('.wl-box').forEach(function(box) {
            sp += parseFloat(box.querySelector('.wl-spending').value) || 0;
            lead += parseInt(box.querySelector('.wl-lead').value) || 0;
            paid += parseInt(box.querySelector('.wl-paid').value) || 0;
            boxes++;
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

    function bindCardEvents(pid) {
        var st = state.get(pid);
        var card = cardsEl.querySelector('.prod-card[data-pid="' + String(pid) + '"]');
        if (!card || !st) return;

        // ── Hapus kartu produk ──
        var delBtn = card.querySelector('.btn-delete-prod');
        if (delBtn) {
            delBtn.addEventListener('click', function() {
                if (!confirm('Hapus produk "' + st.name + '" dari area?')) return;
                state.delete(pid);
                card.remove();
                // Produk kembali ke pilihan palette
                setPaletteItemState(pid, true);
                updatePlaceholder();
                updateCardNumbers();
                updateDropCount();
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
                    updatePhase1UI(pid);
                });
            });

            // ── Catat Spending → fase 2 ──
            var catat = card.querySelector('.btn-catat');
            if (catat) {
                catat.addEventListener('click', function() {
                    if (st.checked.size === 0) return;
                    st.phase = 2;
                    renderCard(pid);
                });
            }
        } else {
            // ── Kotak whitelist: input + preview + hapus ──
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
                    updateProductTotals(pid);
                    updateGrandTotals();
                };
                spInp.addEventListener('input', onInput);
                leadInp.addEventListener('input', onInput);
                paidInp.addEventListener('input', onInput);

                box.querySelector('.wl-box-remove').addEventListener('click', function() {
                    st.checked.delete(String(wid));
                    delete st.values[wid];
                    if (st.checked.size === 0) {
                        // Semua whitelist dihapus → kembali ke fase 1
                        st.phase = 1;
                        renderCard(pid);
                    } else {
                        renderCard(pid);
                    }
                });
            });

            // Tampilkan total & metrik segera (penting untuk prefill edit mode)
            updateProductTotals(pid);
            updateGrandTotals();

            // ── Kembali ke pilihan whitelist ──
            var back = card.querySelector('.btn-back-wl');
            if (back) {
                back.addEventListener('click', function() {
                    st.phase = 1;
                    renderCard(pid);
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
        var pid = String(data.product_id);
        var wid = String(data.whitelist_id);
        state.set(pid, {
            name: data.product_name || 'Produk',
            phase: 2,
            checked: new Set([wid]),
            values: {},
            wlFallback: { id: wid, nama: data.whitelist_name || 'Whitelist #' + wid, kode: data.whitelist_code || '' }
        });
        state.get(pid).values[wid] = {
            spending: data.spending,
            lead: data.lead,
            paid: data.paid
        };

        var card = document.createElement('div');
        card.className = 'prod-card';
        card.dataset.pid = pid;
        cardsEl.appendChild(card);
        updatePlaceholder();
        renderCard(pid);
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
            var tanggal = tanggalInput.value;
            if (!tanggal) {
                alert('Pilih tanggal terlebih dahulu.');
                tanggalInput.focus();
                return;
            }

            removeReindexInputs();

            if (!isEditMode) {
                // Create: kumpulkan items[] dari semua kartu
                var items = [];
                cardsEl.querySelectorAll('.prod-card').forEach(function(card) {
                    card.querySelectorAll('.wl-box').forEach(function(box) {
                        var sp = box.querySelector('.wl-spending').value;
                        var lead = box.querySelector('.wl-lead').value;
                        var paid = box.querySelector('.wl-paid').value;
                        items.push({
                            product_id: card.dataset.pid,
                            whitelist_id: box.dataset.wlid,
                            spending: sp === '' ? '0' : sp,
                            lead: lead === '' ? '0' : lead,
                            paid: paid === '' ? '0' : paid
                        });
                    });
                });

                if (items.length === 0) {
                    alert('Belum ada data spending. Seret produk, centang whitelist, lalu klik "Catat Spending".');
                    return;
                }

                items.forEach(function(it, idx) {
                    addHidden('items[' + idx + '][product_id]', it.product_id);
                    addHidden('items[' + idx + '][whitelist_id]', it.whitelist_id);
                    addHidden('items[' + idx + '][spending]', it.spending);
                    addHidden('items[' + idx + '][lead]', it.lead);
                    addHidden('items[' + idx + '][paid]', it.paid);
                });
            } else {
                // Edit: flat fields
                var card = cardsEl.querySelector('.prod-card');
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

    // ─── Init ───────────────────────────────────────────────
    makeDraggable();
    if (isEditMode) {
        initEditMode();
    }

})();
</script>
@endpush
