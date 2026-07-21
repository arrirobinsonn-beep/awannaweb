@extends('layouts.app')
@section('title', $mode==='edit' ? 'Edit Spending Harian' : 'Input Spending Harian')
@section('page-title', $mode==='edit' ? '✏️ Edit Spending Harian' : '➕ Input Spending Harian')
@section('page-subtitle', $mode==='edit'
    ? 'Edit data spending iklan harian — whitelist ' . ($spending->whitelist->nama ?? '') . ' (' . ($spending->whitelist->kode ?? '') . ')'
    : 'Catat spending, lead & paid — multiple produk & whitelist dalam 1 aksi')

@push('styles')
<style>
    /* ── Dashed trigger ───────────────────────────── */
    .dashed-trigger {
        border: 2px dashed #d1d5db;
        border-radius: 14px;
        padding: 18px 20px;
        text-align: center;
        cursor: pointer;
        transition: all .2s ease;
        background: transparent;
        font-size: .88rem;
        font-weight: 600;
        color: #6b7280;
    }
    .dashed-trigger:hover {
        border-color: var(--color-primary, #FF6B6B);
        color: var(--color-primary, #FF6B6B);
        background: #fef2f2;
    }

    /* ── Product block ────────────────────────────── */
    .prod-block {
        border-radius: 16px;
        padding: 0;
        background: #fff;
        transition: box-shadow .2s;
    }
    .prod-block-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
    }
    .prod-block-title {
        font-size: .85rem;
        font-weight: 800;
        color: #1e1b2e;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .prod-block-badge {
        display: inline-block;
        background: #eef2ff;
        color: #4338ca;
        font-size: .65rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
    }
    .prod-block-body {
        padding: 14px 18px 16px;
    }

    /* ── Product group divider ────────────────────── */
    .prod-group-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0;
        margin: 0;
    }
    .prod-group-divider .line {
        flex: 1;
        height: 2px;
        background: linear-gradient(to right, transparent, #c7d2fe, #a5b4fc, #c7d2fe, transparent);
        border-radius: 1px;
    }
    .prod-group-divider .label {
        font-size: .7rem;
        font-weight: 700;
        color: #6366f1;
        text-transform: uppercase;
        letter-spacing: .06em;
        white-space: nowrap;
        padding: 0 4px;
    }

    /* ── Whitelist entry ──────────────────────────── */
    .wl-entry {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 10px;
        background: #fafafa;
        transition: border-color .15s;
    }
    .wl-entry:focus-within {
        border-color: var(--color-primary, #FF6B6B);
    }
    .wl-entry-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .wl-entry-grid .col-span-2 { grid-column: span 2; }

    .wl-entry label {
        display: block;
        font-size: .72rem;
        font-weight: 700;
        color: #6b7280;
        margin-bottom: 3px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .wl-entry .clay-input {
        font-size: .85rem;
        padding: 7px 10px;
    }

    /* ── Preview mini ─────────────────────────────── */
    .wl-preview {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-top: 10px;
        padding: 10px 14px;
        background: #F0FFFE;
        border-radius: 10px;
    }
    .wl-preview > div {
        text-align: center;
    }
    .wl-preview .val {
        font-weight: 800;
        font-size: .95rem;
    }
    .wl-preview .lbl {
        font-size: .6rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #9ca3af;
        margin-top: 1px;
    }

    /* ── Delete buttons ───────────────────────────── */
    .btn-delete-wl {
        border: none;
        background: #fef2f2;
        color: #ef4444;
        cursor: pointer;
        font-size: .72rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        transition: background .15s;
    }
    .btn-delete-wl:hover { background: #fecaca; }
    .btn-delete-prod {
        border: none;
        background: transparent;
        color: #9ca3af;
        cursor: pointer;
        font-size: .78rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 8px;
        transition: all .15s;
    }
    .btn-delete-prod:hover {
        background: #fef2f2;
        color: #ef4444;
    }

    /* ── Animation ────────────────────────────────── */
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .wl-entry, .prod-block {
        animation: slideIn .2s ease;
    }

    /* ── Date card ────────────────────────────────── */
    .date-prod-card {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        align-items: end;
    }
    .date-prod-card label {
        display: block;
        font-size: .78rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: #374151;
    }
    .date-prod-card .required-star { color: #f87171; }

    /* ── Main product select highlight ────────────── */
    @keyframes pulse-select {
        0%, 100% { box-shadow: 0 0 0 0 rgba(99,102,241,.3); }
        50%      { box-shadow: 0 0 0 4px rgba(99,102,241,.15); }
    }
    .highlight-select {
        animation: pulse-select .6s ease 2;
    }
</style>
@endpush

@section('content')
<div style="max-width:700px;">
    <form method="POST" action="{{ $mode==='edit' ? route('spending.update', $spending) : route('spending.store') }}" id="spending-form" novalidate>
        @csrf
        @if($mode==='edit')@method('PUT')@endif

        {{-- ─── Card 1: Tanggal + Produk ─────────────────────── --}}
        <div class="clay-card" style="padding:20px;margin-bottom:14px;" data-reveal>
            <div class="date-prod-card" id="top-selector-area">
                <div>
                    <label>Tanggal <span class="required-star">*</span></label>
                    <input type="date" name="tanggal" id="input-tanggal"
                           value="{{ old('tanggal', $mode==='edit' ? $spending->tanggal->format('Y-m-d') : now()->format('Y-m-d')) }}"
                           class="clay-input" required>
                </div>
                <div>
                    <label>Produk <span class="required-star">*</span></label>
                    <select id="main-product-select" class="clay-input">
                        <option value="">— Pilih Produk —</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}"
                                data-nama="{{ $p->nama_produk }} ({{ $p->kode_produk }})">
                            {{ $p->nama_produk }} ({{ $p->kode_produk }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p style="font-size:.72rem;color:#9ca3af;margin-top:10px;">
                📌 Pilih tanggal dan produk, lalu klik <strong>"Catat spending whitelist"</strong>
            </p>
        </div>

        {{-- ─── Product Blocks Container ─────────────────────── --}}
        <div id="product-blocks"></div>

        {{-- ─── Dashed Trigger: Tambah Produk ────────────────── --}}
        <div class="dashed-trigger" id="add-prod-trigger" style="margin-top:14px;">
            ➕ Tambah produk lain
        </div>

        {{-- ─── Submit ───────────────────────────────────────── --}}
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
</div>

{{-- ─── Templates ──────────────────────────────────────────── --}}
<template id="prod-block-template">
    <div class="prod-block" data-product-id="">
        {{-- Header with product name only --}}
        <div class="prod-block-header">
            <span class="prod-block-title">
                📦 <span class="prod-name-display">Produk</span>
                <span class="prod-block-badge prod-badge">#1</span>
            </span>
            <button type="button" class="btn-delete-prod" title="Hapus produk">✕</button>
        </div>

        {{-- Whitelist entries area --}}
        <div class="prod-block-body">
            <div class="wl-entries"></div>

            {{-- Dashed trigger: add whitelist entry --}}
            <div class="dashed-trigger add-wl-trigger" style="padding:12px 14px;font-size:.82rem;">
                ➕ Catat spending whitelist
            </div>
        </div>
    </div>
</template>

<template id="wl-entry-template">
    <div class="wl-entry">
        <div class="wl-entry-grid">
            <div class="col-span-2">
                <label>🌐 Whitelist</label>
                <select class="clay-input wl-select" required>
                    <option value="">— Pilih Whitelist —</option>
                    @foreach($whitelists as $wl)
                    <option value="{{ $wl->id }}"
                            data-nama="{{ $wl->nama }} ({{ $wl->kode }})">
                        {{ $wl->nama }} ({{ $wl->kode }})
                        @if(!auth()->user()->hasRole('advertiser') && $wl->user)
                            — {{ $wl->user->nama }}
                        @endif
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>💰 Spending (Rp)</label>
                <input type="number" class="clay-input wl-spending" min="0" step="100"
                       placeholder="0" required>
            </div>
            <div style="display:flex;gap:8px;">
                <div style="flex:1;">
                    <label>👤 Lead</label>
                    <input type="number" class="clay-input wl-lead" min="0"
                           placeholder="0" required>
                </div>
                <div style="flex:1;">
                    <label>💳 Paid</label>
                    <input type="number" class="clay-input wl-paid" min="0"
                           placeholder="0" required>
                </div>
            </div>
        </div>

        {{-- Preview --}}
        <div class="wl-preview">
            <div>
                <div class="val preview-ratio" style="color:var(--color-secondary);">—</div>
                <div class="lbl">Paid Ratio</div>
            </div>
            <div>
                <div class="val preview-cpa-lead" style="color:var(--color-purple);">—</div>
                <div class="lbl">CPA Lead</div>
            </div>
            <div>
                <div class="val preview-cpa-paid" style="color:var(--color-orange);">—</div>
                <div class="lbl">CPA Paid</div>
            </div>
        </div>

        {{-- Delete --}}
        <div style="display:flex;justify-content:flex-end;margin-top:6px;">
            <button type="button" class="btn-delete-wl">🗑 Hapus</button>
        </div>
    </div>
</template>

{{-- Visual divider template between product groups --}}
<template id="divider-template">
    <div class="prod-group-divider">
        <span class="line"></span>
        <span class="label">Produk <span class="divider-num">2</span></span>
        <span class="line"></span>
    </div>
</template>
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
    "spending": {{ $spending->spending }},
    "lead": {{ $spending->lead }},
    "paid": {{ $spending->paid }}
}
</script>
@endif

<script>
(function() {
    'use strict';

    // ─── Refs ──────────────────────────────────────────
    const form          = document.getElementById('spending-form');
    const tanggalInput  = document.getElementById('input-tanggal');
    const mainProdSelect = document.getElementById('main-product-select');
    const blocksEl      = document.getElementById('product-blocks');
    const addProdTrigger = document.getElementById('add-prod-trigger');
    const submitBtn     = document.getElementById('btn-submit');

    const prodTemplate  = document.getElementById('prod-block-template');
    const wlTemplate    = document.getElementById('wl-entry-template');
    const divTemplate   = document.getElementById('divider-template');

    const isEditMode    = document.getElementById('edit-data') !== null;

    // ─── Format ────────────────────────────────────────
    function fmtNum(n) { return Number(n).toLocaleString('id-ID'); }

    // ─── Re-index items ───────────────────────────────
    function reindex() {
        // Hapus semua hidden input hasil reindex sebelumnya (ditandai dg data-reindex)
        form.querySelectorAll('[data-reindex]').forEach(function(el) { el.remove(); });
        var hasItems = false;
        var idx = 0;

        blocksEl.querySelectorAll('.prod-block').forEach(function(block) {
            var pid = block.dataset.productId;
            if (!pid) return;

            block.querySelectorAll('.wl-entry').forEach(function(entry) {
                var wlSelect  = entry.querySelector('.wl-select');
                var spending  = entry.querySelector('.wl-spending');
                var lead      = entry.querySelector('.wl-lead');
                var paid      = entry.querySelector('.wl-paid');

                var wid = wlSelect ? wlSelect.value : '';
                if (!wid) return;

                hasItems = true;

                if (isEditMode) {
                    // Edit mode: kirim flat fields (update hanya 1 item)
                    var flatFields = [
                        { name: 'product_id',   value: pid },
                        { name: 'whitelist_id', value: wid },
                        { name: 'spending',     value: spending.value || '0' },
                        { name: 'lead',         value: lead.value || '0' },
                        { name: 'paid',         value: paid.value || '0' },
                    ];
                    flatFields.forEach(function(f) {
                        var inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = f.name;
                        inp.value = f.value;
                        inp.setAttribute('data-reindex', '1');
                        entry.appendChild(inp);
                    });
                } else {
                    // Create mode: kirim items[] array
                    var h1 = document.createElement('input');
                    h1.type = 'hidden'; h1.name = 'items[' + idx + '][product_id]';
                    h1.value = pid; h1.setAttribute('data-reindex', '1'); entry.appendChild(h1);

                    var h2 = document.createElement('input');
                    h2.type = 'hidden'; h2.name = 'items[' + idx + '][whitelist_id]';
                    h2.value = wid; h2.setAttribute('data-reindex', '1'); entry.appendChild(h2);

                    var h3 = document.createElement('input');
                    h3.type = 'hidden'; h3.name = 'items[' + idx + '][spending]';
                    h3.value = spending.value || '0'; h3.setAttribute('data-reindex', '1'); entry.appendChild(h3);

                    var h4 = document.createElement('input');
                    h4.type = 'hidden'; h4.name = 'items[' + idx + '][lead]';
                    h4.value = lead.value || '0'; h4.setAttribute('data-reindex', '1'); entry.appendChild(h4);

                    var h5 = document.createElement('input');
                    h5.type = 'hidden'; h5.name = 'items[' + idx + '][paid]';
                    h5.value = paid.value || '0'; h5.setAttribute('data-reindex', '1'); entry.appendChild(h5);

                    idx++;
                }
            });
        });

        return hasItems;
    }

    // ─── Preview calculation ──────────────────────────
    function calcPreview(entry) {
        var sp   = parseFloat(entry.querySelector('.wl-spending').value) || 0;
        var lead = parseInt(entry.querySelector('.wl-lead').value) || 0;
        var paid = parseInt(entry.querySelector('.wl-paid').value) || 0;

        var ratio   = lead > 0 ? (paid / lead * 100).toFixed(2) + '%' : '—';
        var cpaLead = lead > 0 ? 'Rp ' + fmtNum(Math.round(sp / lead)) : '—';
        var cpaPaid = paid > 0 ? 'Rp ' + fmtNum(Math.round(sp / paid)) : '—';

        entry.querySelector('.preview-ratio').textContent    = ratio;
        entry.querySelector('.preview-cpa-lead').textContent = cpaLead;
        entry.querySelector('.preview-cpa-paid').textContent = cpaPaid;
    }

    // ─── Update badges + dividers ─────────────────────
    function updateBlockNumbering() {
        var blocks = blocksEl.querySelectorAll('.prod-block');
        var blockIdx = 0;

        blocks.forEach(function(block) {
            blockIdx++;
            var badge = block.querySelector('.prod-badge');
            if (badge) badge.textContent = '#' + blockIdx;
        });

        // Update divider labels
        blocksEl.querySelectorAll('.prod-group-divider').forEach(function(div) {
            var numEl = div.querySelector('.divider-num');
            if (numEl) {
                // Find which product this divider is before
                var nextBlock = div.nextElementSibling;
                while (nextBlock && !nextBlock.classList.contains('prod-block')) {
                    nextBlock = nextBlock.nextElementSibling;
                }
                if (nextBlock) {
                    var idx = Array.from(blocks).indexOf(nextBlock) + 1;
                    numEl.textContent = '#' + idx;
                }
            }
        });
    }

    // ─── Add whitelist entry ──────────────────────────
    function addWlEntry(prodBlock, prefillData) {
        var clone = document.importNode(wlTemplate.content, true);
        var entry = clone.querySelector('.wl-entry');

        entry.querySelectorAll('.wl-spending, .wl-lead, .wl-paid').forEach(function(inp) {
            inp.addEventListener('input', function() { calcPreview(entry); });
        });

        entry.querySelector('.btn-delete-wl').addEventListener('click', function() {
            entry.remove();
            reindex();
        });

        // Pre-fill jika ada data (edit mode)
        if (prefillData) {
            var wlSelect = entry.querySelector('.wl-select');
            if (wlSelect) {
                // Set nilai whitelist select
                for (var i = 0; i < wlSelect.options.length; i++) {
                    if (wlSelect.options[i].value == prefillData.whitelist_id) {
                        wlSelect.value = prefillData.whitelist_id;
                        break;
                    }
                }
            }
            entry.querySelector('.wl-spending').value = prefillData.spending;
            entry.querySelector('.wl-lead').value      = prefillData.lead;
            entry.querySelector('.wl-paid').value      = prefillData.paid;
            calcPreview(entry);
        }

        prodBlock.querySelector('.wl-entries').appendChild(entry);
        reindex();
    }

    // ─── Add product block ────────────────────────────
    function addProdBlock(productId, productName, prefillData) {
        var clone = document.importNode(prodTemplate.content, true);
        var block = clone.querySelector('.prod-block');

        block.dataset.productId = productId;
        block.querySelector('.prod-name-display').textContent = productName || 'Produk';

        // Add WL trigger
        block.querySelector('.add-wl-trigger').addEventListener('click', function() {
            addWlEntry(block);
        });

        // Delete product block — di edit mode, sembunyikan tombol hapus
        var delBtn = block.querySelector('.btn-delete-prod');
        delBtn.addEventListener('click', function() {
            if (isEditMode && !confirm('Hapus produk ini dari form? Data spending TIDAK akan terhapus.')) {
                return;
            }
            block.remove();

            // Hapus semua divider, lalu re-add di antara block yang tersisa
            blocksEl.querySelectorAll('.prod-group-divider').forEach(function(d) { d.remove(); });

            var remaining = Array.from(blocksEl.querySelectorAll('.prod-block'));
            for (var i = 0; i < remaining.length - 1; i++) {
                var divClone = document.importNode(divTemplate.content, true);
                remaining[i].after(divClone);
            }

            reindex();
            updateBlockNumbering();
        });
        if (isEditMode) {
            delBtn.title = 'Hapus dari form (data tidak terhapus)';
        }

        // Before appending, check if a divider is needed
        if (blocksEl.children.length > 0) {
            var divClone = document.importNode(divTemplate.content, true);
            blocksEl.appendChild(divClone);
        }

        blocksEl.appendChild(block);
        updateBlockNumbering();

        // Jika ada prefill data whitelist, langsung tambah WL entry
        if (prefillData) {
            addWlEntry(block, prefillData);
        }
    }

    // ─── Main product select → create block ──────────
    mainProdSelect.addEventListener('change', function() {
        var opt = mainProdSelect.options[mainProdSelect.selectedIndex];
        if (opt && opt.value) {
            // Check duplicate by data-product-id
            var exists = Array.from(blocksEl.querySelectorAll('.prod-block')).some(function(b) {
                return b.dataset.productId === opt.value;
            });
            if (!exists) {
                addProdBlock(opt.value, opt.dataset.nama);
            }
            // Always clear select
            mainProdSelect.value = '';
        }
    });

    // ─── Add product trigger ─────────────────────────
    addProdTrigger.addEventListener('click', function() {
        if (isEditMode) {
            // Di edit mode, hanya fokus ke product select
            mainProdSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            mainProdSelect.classList.add('highlight-select');
            setTimeout(function() {
                mainProdSelect.classList.remove('highlight-select');
            }, 1200);
            mainProdSelect.focus();
            return;
        }
        // Scroll to and highlight the main product select at top
        mainProdSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
        mainProdSelect.classList.add('highlight-select');
        setTimeout(function() {
            mainProdSelect.classList.remove('highlight-select');
        }, 1200);
        mainProdSelect.focus();
    });

    // ─── Inisialisasi Edit Mode ───────────────────────
    function initEditMode() {
        var editDataEl = document.getElementById('edit-data');
        if (!editDataEl) return;

        try {
            var data = JSON.parse(editDataEl.textContent);
        } catch(e) {
            return;
        }

        // Set tanggal
        if (data.tanggal) {
            tanggalInput.value = data.tanggal;
        }

        // Nonaktifkan date picker (tanggal tidak bisa diubah saat edit)
        tanggalInput.disabled = true;
        tanggalInput.style.opacity = '0.7';
        tanggalInput.title = 'Tanggal tidak dapat diubah';

        // Tambah hidden input untuk tetap mengirim tanggal
        var tanggalHidden = document.createElement('input');
        tanggalHidden.type = 'hidden';
        tanggalHidden.name = 'tanggal';
        tanggalHidden.value = data.tanggal;
        document.getElementById('top-selector-area').appendChild(tanggalHidden);

        // Buat product block dengan pre-fill whitelist
        addProdBlock(data.product_id, data.product_name, {
            whitelist_id: data.whitelist_id,
            spending:     data.spending,
            lead:         data.lead,
            paid:         data.paid
        });

        // Sembunyikan main product select karena sudah ada block-nya
        var prodSelectGroup = mainProdSelect.closest('div');
        if (prodSelectGroup) prodSelectGroup.style.display = 'none';

        // Sembunyikan trigger tambah produk lain
        addProdTrigger.style.display = 'none';

        // Hapus informasi dropdown
        document.querySelector('#top-selector-area + p')?.remove();
    }

    // ─── Submit handler ──────────────────────────────
    submitBtn.addEventListener('click', function() {
        var tanggal = tanggalInput.value;
        if (!tanggal) {
            alert('Pilih tanggal terlebih dahulu.');
            tanggalInput.focus();
            return;
        }

        var hasItems = reindex();

        if (!hasItems) {
            alert('Belum ada data spending. Pilih produk dan isi whitelist terlebih dahulu.');
            return;
        }

        var missing = false;
        blocksEl.querySelectorAll('.wl-entry').forEach(function(entry) {
            var wl = entry.querySelector('.wl-select');
            if (!wl.value) {
                if (!missing) wl.focus();
                missing = true;
            }
        });
        if (missing) {
            alert('Pastikan semua whitelist sudah dipilih.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-sm"></span> Menyimpan...';
        form.submit();
    });

    // ─── Jalankan inisialisasi edit mode jika perlu ──
    if (isEditMode) {
        initEditMode();
    }

})();
</script>
@endpush
