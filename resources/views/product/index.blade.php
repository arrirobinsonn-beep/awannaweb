@extends('layouts.app')
@section('title','Produk')
@section('page-title','📦 Produk')
@section('page-subtitle','Master produk & varian — stok per gudang dikelola di halaman Gudang')

@push('styles')
<style>
    .product-section-header {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 20px; margin-bottom: 0;
        border-bottom: 1px solid rgba(0,0,0,.05);
    }
    .product-section-header h3 {
        margin: 0; font-size: .95rem; font-weight: 700;
    }
    .product-section-header .section-count {
        font-size: .72rem; color: #9ca3af; font-weight: 400;
    }
    .product-section-wrap {
        overflow: hidden; margin-bottom: 20px;
    }
    .empty-table-note {
        text-align: center; padding: 36px 20px; color: #9ca3af; font-size: .82rem;
    }
</style>
@endpush

@section('content')
@if(session('success'))
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#d1fae5;color:#065f46;font-weight:600;border-radius:8px;">✅ {{ session('success') }}</div>
@endif
@if($errors->any())
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#fee2e2;color:#991b1b;font-weight:600;border-radius:8px;">
    @foreach($errors->all() as $e)<div>⚠ {{ $e }}</div>@endforeach
</div>
@endif

<div class="clay-card" style="padding:16px 20px;margin-bottom:16px;" data-reveal>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="flex:1;min-width:220px;display:flex;gap:8px;flex-wrap:wrap;">
            <input type="text" id="search-input" value="{{ $search }}" placeholder="Cari kode / nama / kategori…" class="clay-input" style="flex:1;min-width:160px;">
            <select id="goods-type-filter" class="clay-input" style="width:150px;">
                <option value="">Semua Tipe</option>
                <option value="consumable" {{ $goodsType === 'consumable' ? 'selected' : '' }}>Barang Pasti</option>
                <option value="core" {{ $goodsType === 'core' ? 'selected' : '' }}>Barang Inti</option>
                <option value="additional" {{ $goodsType === 'additional' ? 'selected' : '' }}>Barang Additional</option>
            </select>
            <select id="status-filter" class="clay-input" style="width:120px;">
                <option value="">Semua Status</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <select id="ad-status-filter" class="clay-input" style="width:130px;">
                <option value="">Semua Iklan</option>
                <option value="running" {{ $adStatus === 'running' ? 'selected' : '' }}>🟢 Running</option>
                <option value="testing" {{ $adStatus === 'testing' ? 'selected' : '' }}>🔬 Testing</option>
            </select>
        </div>
    </div>
</div>

{{-- ═══════════════ SECTION: BARANG INTI & ADDITIONAL ═══════════════ --}}
<div class="clay-card product-section-wrap" id="core-section" data-reveal>
    <div class="product-section-header">
        <h3>📦 Barang Inti &amp; Additional</h3>
        <span class="section-count" id="core-count">{{ $coreProducts->count() }} produk</span>
        <button type="button" class="clay-btn clay-btn-primary" style="margin-left:auto;padding:6px 14px;font-size:.78rem;"
                onclick="openProductModal(this,'create','core')">＋ Tambah Produk</button>
    </div>
    <div class="table-scroll" id="core-table-wrap">
        @include('product._table', ['products' => $coreProducts, 'showAdColumn' => true, 'tableId' => 'core-tbody', 'emptyMessage' => 'Tidak ada produk inti ditemukan'])
    </div>
    <div id="core-pagination" style="padding:12px 20px;border-top:1px solid rgba(0,0,0,.05);">
        @if($coreProducts->hasPages())
            {{ $coreProducts->links() }}
        @endif
    </div>
</div>

{{-- ═══════════════ SECTION: BARANG PASTI ═══════════════ --}}
<div class="clay-card product-section-wrap" id="consumable-section" data-reveal>
    <div class="product-section-header">
        <h3>📋 Barang Pasti</h3>
        <span class="section-count" id="consumable-count">{{ $consumableProducts->count() }} produk</span>
        <button type="button" class="clay-btn clay-btn-primary" style="margin-left:auto;padding:6px 14px;font-size:.78rem;"
                onclick="openProductModal(this,'create','consumable')">＋ Tambah Barang Pasti</button>
    </div>
    <div class="table-scroll" id="consumable-table-wrap">
        @include('product._table', ['products' => $consumableProducts, 'showAdColumn' => false, 'tableId' => 'consumable-tbody', 'emptyMessage' => 'Tidak ada produk pasti ditemukan'])
    </div>
    <div id="consumable-pagination" style="padding:12px 20px;border-top:1px solid rgba(0,0,0,.05);">
        @if($consumableProducts->hasPages())
            {{ $consumableProducts->links() }}
        @endif
    </div>
</div>

{{-- ═══════════════ MODAL TAMBAH / EDIT PRODUK ═══════════════ --}}
<div class="clay-modal" id="modal-product" role="dialog" aria-modal="true">
    <div class="clay-modal-backdrop" onclick="closeProductModal()"></div>
    <div class="clay-modal-container">
        <div class="clay-modal-header">
            <h2 id="pm-title">➕ Tambah Produk</h2>
            <button class="clay-modal-close" onclick="closeProductModal()" type="button">✕</button>
        </div>
        <div class="clay-modal-body">
            <div class="form-grid" style="gap:12px;">
                <div>
                    <label>Kode Produk <span style="color:#f87171;">*</span></label>
                    <input type="text" id="pm-kode" class="clay-input" placeholder="PRD-001" maxlength="20">
                </div>
                <div>
                    <label>Nama Produk <span style="color:#f87171;">*</span></label>
                    <input type="text" id="pm-nama" class="clay-input" placeholder="Nama produk" maxlength="150">
                </div>
                <div>
                    <label>Tipe Barang <span style="color:#f87171;">*</span></label>
                    <select id="pm-goods-type" class="clay-input">
                        <option value="consumable">Barang Pasti</option>
                        <option value="core">Barang Inti</option>
                        <option value="additional">Barang Additional</option>
                    </select>
                </div>
                <div>
                    <label>Kategori</label>
                    <input type="text" id="pm-kategori" class="clay-input" placeholder="Kacamata / Aksesoris" maxlength="80">
                </div>
                <div>
                    <label>Harga Jual (Rp) <span style="color:#f87171;">*</span></label>
                    <input type="number" id="pm-selling" class="clay-input" min="0" step="500" placeholder="119000">
                </div>
                <div>
                    <label>HPP / PCS</label>
                    <input type="number" id="pm-hpp" class="clay-input" min="0" step="100" placeholder="70000">
                </div>
                <div>
                    <label>Satuan</label>
                    <input type="text" id="pm-unit" class="clay-input" placeholder="pcs" maxlength="30">
                </div>
                <div>
                    <label>Min. Stok (Acuan Restock)</label>
                    <input type="number" id="pm-minstock" class="clay-input" min="0" step="1" value="0">
                </div>
                <div>
                    <label>Status</label>
                    <select id="pm-status" class="clay-input">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <div id="pm-ad-fields">
                    <label>Status Iklan</label>
                    <select id="pm-ad-status" class="clay-input">
                        <option value="testing">🔬 Testing</option>
                        <option value="running">🟢 Running</option>
                    </select>
                    <div style="font-size:.65rem;color:#9ca3af;margin-top:2px;">Testing = fase uji; Running = sudah aktif diiklankan (satu arah, tidak bisa balik).</div>
                </div>
                <div id="pm-start-testing-field">
                    <label>Mulai Testing</label>
                    <input type="date" id="pm-start-testing" class="clay-input">
                    <div style="font-size:.65rem;color:#9ca3af;margin-top:2px;">Otomatis terisi saat produk dibuat. Spending sebelum Mulai Running masuk tab Testing.</div>
                </div>
                <div id="pm-start-running-field">
                    <label>Mulai Running</label>
                    <input type="date" id="pm-start-running" class="clay-input">
                    <div style="font-size:.65rem;color:#9ca3af;margin-top:2px;">Otomatis terisi saat toggle Running diaktifkan. Kosongkan bila masih Testing.</div>
                </div>
                <div style="grid-column: span 2;">
                    <label>Deskripsi</label>
                    <textarea id="pm-deskripsi" rows="2" class="clay-input" style="resize:none;" placeholder="Opsional"></textarea>
                </div>
            </div>
            <div style="margin-top:10px;font-size:.72rem;color:#9ca3af;background:#fafafa;border-radius:8px;padding:8px 12px;">
                📦 Varian default dibuat otomatis. Produk belum terdaftar di gudang mana pun — daftarkan lewat halaman <b>Gudang</b>.
            </div>
        </div>
        <div class="clay-modal-footer">
            <button class="clay-btn clay-btn-outline" onclick="closeProductModal()" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="pm-save" type="button">💾 Simpan Produk</button>
        </div>
    </div>
</div>

{{-- ═══════════════ MODAL TAMBAH / EDIT VARIAN ═══════════════ --}}
<div class="clay-modal" id="modal-variant" role="dialog" aria-modal="true">
    <div class="clay-modal-backdrop" onclick="closeVariantModal()"></div>
    <div class="clay-modal-container">
        <div class="clay-modal-header">
            <h2 id="pv-title">➕ Tambah Varian</h2>
            <button class="clay-modal-close" onclick="closeVariantModal()" type="button">✕</button>
        </div>
        <div class="clay-modal-body">
            <div class="form-grid" style="gap:12px;">
                <div>
                    <label>Kode Varian <span style="color:#f87171;">*</span></label>
                    <input type="text" id="pv-kode" class="clay-input" placeholder="KSP+1.50" maxlength="50">
                </div>
                <div>
                    <label>Nama Varian <span style="color:#f87171;">*</span></label>
                    <input type="text" id="pv-nama" class="clay-input" placeholder="Plus +1.50" maxlength="150">
                </div>
                <div>
                    <label>Jenis</label>
                    <input type="text" id="pv-jenis" class="clay-input" placeholder="ukuran / isi paket" maxlength="80">
                </div>
                <div>
                    <label>Power <span style="color:#f87171;">*</span></label>
                    <input type="number" id="pv-power" class="clay-input" min="0" step="0.25" value="0">
                    <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Ukuran lensa, mis. 1.00 / 1.25. 0 untuk produk tanpa ukuran.</div>
                </div>
                <div>
                    <label>Status</label>
                    <select id="pv-status" class="clay-input">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:10px;font-size:.72rem;color:#9ca3af;background:#fafafa;border-radius:8px;padding:8px 12px;">
                Stok varian diisi per gudang (halaman Gudang / Barang Masuk), bukan di sini.
            </div>
        </div>
        <div class="clay-modal-footer">
            <button class="clay-btn clay-btn-outline" onclick="closeVariantModal()" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="pv-save" type="button">💾 Simpan Varian</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Expand baris varian ──────────────────────────────
var openRows = new Set();
function toggleVarian(id) {
    var el = document.getElementById('pv-' + id);
    var chev = document.getElementById('chev-pv-' + id);
    if (!el) return;
    if (openRows.has(id)) { el.style.display = 'none'; if (chev) chev.style.transform = 'rotate(0deg)'; openRows.delete(id); }
    else { el.style.display = 'table-row'; if (chev) chev.style.transform = 'rotate(180deg)'; openRows.add(id); }
}
</script>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';
    var CSRF = '{{ csrf_token() }}';

    function post(url, method, body) {
        return fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: body ? JSON.stringify(body) : undefined,
        }).then(function(res) {
            if (!res.ok) return res.json().then(function(e) {
                var msg = e.message || 'Gagal';
                if (e.errors) { var k = Object.keys(e.errors)[0]; if (k) msg = e.errors[k][0]; }
                throw new Error(msg);
            });
            return res.json();
        });
    }

    // ══════════════════════════════════════════════════════
    // LIVE FILTERING (AJAX) — DUAL TABLE
    // ══════════════════════════════════════════════════════
    var searchInput = document.getElementById('search-input');
    var goodsTypeFilter = document.getElementById('goods-type-filter');
    var statusFilter = document.getElementById('status-filter');
    var adStatusFilter = document.getElementById('ad-status-filter');
    var consumableWrap = document.getElementById('consumable-table-wrap');
    var consumablePag = document.getElementById('consumable-pagination');
    var consumableSection = document.getElementById('consumable-section');
    var consumableCountEl = document.getElementById('consumable-count');
    var coreWrap = document.getElementById('core-table-wrap');
    var corePag = document.getElementById('core-pagination');
    var coreSection = document.getElementById('core-section');
    var coreCountEl = document.getElementById('core-count');
    var filterUrl = '{{ route("product.filter") }}';
    var debounceTimer = null;

    function getFilterParams() {
        var params = new URLSearchParams();
        var search = searchInput.value.trim();
        var goodsType = goodsTypeFilter.value;
        var status = statusFilter.value;
        var adStatus = adStatusFilter.value;
        if (search) params.set('search', search);
        if (goodsType) params.set('goods_type', goodsType);
        if (status) params.set('status', status);
        if (adStatus) params.set('ad_status', adStatus);
        params.set('page', '1');
        return params;
    }

    function applyTableSection(wrap, pag, section, countEl, html, pagination, total, label) {
        if (html) {
            wrap.innerHTML = html;
            pag.innerHTML = pagination || '';
            countEl.textContent = total + ' ' + label;
            section.style.display = '';
        } else {
            section.style.display = 'none';
        }
    }

    function fetchFiltered() {
        var url = filterUrl + '?' + getFilterParams().toString();
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            applyTableSection(consumableWrap, consumablePag, consumableSection, consumableCountEl,
                data.consumable_html, data.consumable_pagination, data.consumable_total, 'produk');
            applyTableSection(coreWrap, corePag, coreSection, coreCountEl,
                data.core_html, data.core_pagination, data.core_total, 'produk');
            bindToggleEvents();
            bindPaginationLinks(consumablePag);
            bindPaginationLinks(corePag);
        })
        .catch(function(err) { console.error('Filter error:', err); });
    }

    function fetchPage(url, wrap, pag, section, countEl, label) {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            applyTableSection(wrap, pag, section, countEl,
                data.html, data.pagination, data.total, label);
            bindToggleEvents();
            bindPaginationLinks(pag);
            wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(function(err) { console.error('Page fetch error:', err); });
    }

    function bindPaginationLinks(container) {
        container.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var href = this.href;
                if (container === consumablePag) {
                    fetchPage(href, consumableWrap, consumablePag, consumableSection, consumableCountEl, 'produk');
                } else {
                    fetchPage(href, coreWrap, corePag, coreSection, coreCountEl, 'produk');
                }
            });
        });
    }

    // Debounced search (300ms)
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchFiltered, 300);
    });

    // Immediate filter on dropdown change
    goodsTypeFilter.addEventListener('change', fetchFiltered);
    statusFilter.addEventListener('change', fetchFiltered);
    adStatusFilter.addEventListener('change', fetchFiltered);

    // Bind initial pagination
    bindPaginationLinks(consumablePag);
    bindPaginationLinks(corePag);

    // ══════════════════════════════════════════════════════
    // TOGGLE STATUS (NO RELOAD)
    // ══════════════════════════════════════════════════════
    function bindToggleEvents() {
        document.querySelectorAll('.clay-toggle input[type="checkbox"]').forEach(function(input) {
            input.addEventListener('change', function() {
                var self = this;
                var url = self.dataset.toggleUrl;
                if (!url) return;
                self.disabled = true;
                post(url, 'PATCH', null)
                    .then(function(json) {
                        if (!json.success) {
                            self.checked = !self.checked;
                            alert('Gagal: ' + json.message);
                        }
                    })
                    .catch(function(err) {
                        self.checked = !self.checked;
                        alert('Error: ' + err.message);
                    })
                    .finally(function() { self.disabled = false; });
            });
        });
    }
    bindToggleEvents();

    // ══════════════════════════════════════════════════════
    // MODAL PRODUK
    // ══════════════════════════════════════════════════════
    var pm = { url: null, method: 'POST' };
    var mProd = document.getElementById('modal-product');
    var pmTitle = document.getElementById('pm-title');

    function toggleAdFields(visible) {
        var fields = ['pm-ad-fields', 'pm-start-testing-field', 'pm-start-running-field'];
        fields.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = visible ? '' : 'none';
        });
    }

    window.openProductModal = function(btn, mode, defaultGoodsType) {
        var f = function(id) { return document.getElementById(id); };
        if (mode === 'edit') {
            pm.method = 'PUT';
            pm.url = btn.dataset.url;
            pmTitle.textContent = '✏️ Edit Produk';
            f('pm-kode').value = btn.dataset.code;
            f('pm-nama').value = btn.dataset.name;
            f('pm-goods-type').value = btn.dataset.goodsType;
            f('pm-kategori').value = btn.dataset.category || '';
            f('pm-selling').value = btn.dataset.sellingPrice;
            f('pm-hpp').value = btn.dataset.purchasePrice || '';
            f('pm-unit').value = btn.dataset.unit || 'pcs';
            f('pm-minstock').value = btn.dataset.minStock || '0';
            f('pm-deskripsi').value = btn.dataset.description || '';
            f('pm-status').value = btn.dataset.status || 'active';
            f('pm-ad-status').value = btn.dataset.adStatus || 'running';
            f('pm-start-testing').value = btn.dataset.startTesting || '';
            f('pm-start-running').value = btn.dataset.startRunning || '';
            // Show/hide ad fields based on goods type
            var isConsumable = btn.dataset.goodsType === 'consumable';
            toggleAdFields(!isConsumable);
        } else {
            pm.method = 'POST';
            pm.url = '{{ route('product.store') }}';
            pmTitle.textContent = '➕ Tambah Produk';
            f('pm-kode').value = '';
            f('pm-nama').value = '';
            f('pm-goods-type').value = defaultGoodsType || 'core';
            f('pm-kategori').value = '';
            f('pm-selling').value = '';
            f('pm-hpp').value = '';
            f('pm-unit').value = 'pcs';
            f('pm-minstock').value = '0';
            f('pm-deskripsi').value = '';
            f('pm-status').value = 'active';
            f('pm-ad-status').value = 'testing';
            f('pm-start-testing').value = '';
            f('pm-start-running').value = '';
            // Show/hide ad fields based on default type
            var isConsumable = (defaultGoodsType || 'core') === 'consumable';
            toggleAdFields(!isConsumable);
        }
        mProd.classList.add('active');
        setTimeout(function() { f('pm-kode').focus(); }, 150);
    };

    // Toggle ad fields when goods_type changes in modal
    document.getElementById('pm-goods-type').addEventListener('change', function() {
        toggleAdFields(this.value !== 'consumable');
    });

    window.closeProductModal = function() { mProd.classList.remove('active'); };

    document.getElementById('pm-save').addEventListener('click', function() {
        var btn = this; btn.disabled = true; btn.innerHTML = 'Menyimpan...';
        var f = function(id) { return document.getElementById(id); };
        var body = {
            code: f('pm-kode').value.trim(),
            name: f('pm-nama').value.trim(),
            goods_type: f('pm-goods-type').value,
            category: f('pm-kategori').value.trim(),
            selling_price: f('pm-selling').value || '0',
            purchase_price: f('pm-hpp').value || '0',
            unit: f('pm-unit').value.trim() || 'pcs',
            min_stock: f('pm-minstock').value || '0',
            description: f('pm-deskripsi').value.trim(),
            status: f('pm-status').value,
        };
        // Only send ad fields for non-consumable products
        if (body.goods_type !== 'consumable') {
            body.ad_status = f('pm-ad-status').value;
            body.start_testing = f('pm-start-testing').value || null;
            body.start_running = f('pm-start-running').value || null;
        }
        post(pm.url, pm.method, body)
            .then(function(json) {
                if (json.success) { fetchFiltered(); closeProductModal(); }
                else { alert('Gagal: ' + json.message); btn.disabled = false; btn.innerHTML = '💾 Simpan Produk'; }
            })
            .catch(function(err) { alert('Error: ' + err.message); btn.disabled = false; btn.innerHTML = '💾 Simpan Produk'; });
    });

    // ══════════════════════════════════════════════════════
    // MODAL VARIAN
    // ══════════════════════════════════════════════════════
    var st = { url: null, edit: false };
    var mVar = document.getElementById('modal-variant');
    var pvTitle = document.getElementById('pv-title');

    window.openVariantModal = function(productId, btn) {
        var f = function(id) { return document.getElementById(id); };
        if (btn && btn.dataset.id) {
            st.edit = true; st.url = btn.dataset.url;
            pvTitle.textContent = '✏️ Edit Varian';
            f('pv-kode').value = btn.dataset.code;
            f('pv-nama').value = btn.dataset.name;
            f('pv-jenis').value = btn.dataset.jenis || '';
            f('pv-power').value = btn.dataset.power;
            f('pv-status').value = btn.dataset.status || 'active';
        } else {
            st.edit = false; st.url = btn.dataset.storeUrl;
            pvTitle.textContent = '➕ Tambah Varian';
            f('pv-kode').value = ''; f('pv-nama').value = '';
            f('pv-jenis').value = ''; f('pv-power').value = '0';
            f('pv-status').value = 'active';
        }
        mVar.classList.add('active');
        setTimeout(function() { f('pv-kode').focus(); }, 150);
    };

    window.closeVariantModal = function() { mVar.classList.remove('active'); };

    document.getElementById('pv-save').addEventListener('click', function() {
        var btn = this; btn.disabled = true; btn.innerHTML = 'Menyimpan...';
        var f = function(id) { return document.getElementById(id); };
        var body = {
            code: f('pv-kode').value.trim(),
            name: f('pv-nama').value.trim(),
            jenis: f('pv-jenis').value.trim(),
            power: f('pv-power').value || '0',
            status: f('pv-status').value,
        };
        post(st.url, st.edit ? 'PUT' : 'POST', body)
            .then(function(json) {
                if (json.success) { fetchFiltered(); closeVariantModal(); }
                else { alert('Gagal: ' + json.message); btn.disabled = false; btn.innerHTML = '💾 Simpan Varian'; }
            })
            .catch(function(err) { alert('Error: ' + err.message); btn.disabled = false; btn.innerHTML = '💾 Simpan Varian'; });
    });

    window.deleteVariant = function(id) {
        if (!confirm('Hapus varian ini?')) return;
        post('{{ route('product.variant.destroy', ':id') }}'.replace(':id', id), 'DELETE', null)
            .then(function(json) { if (json.success) fetchFiltered(); else alert('Gagal: ' + json.message); })
            .catch(function(err) { alert('Error: ' + err.message); });
    };

    // ══════════════════════════════════════════════════════
    // HAPUS PRODUK
    // ══════════════════════════════════════════════════════
    window.deleteProduct = function(url, name) {
        if (!confirm('Hapus produk ' + name + ' beserta variannya?')) return;
        post(url, 'DELETE')
            .then(function(json) { if (json.success) fetchFiltered(); else alert('Gagal: ' + json.message); })
            .catch(function(err) { alert('Error: ' + err.message); });
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (mProd.classList.contains('active')) closeProductModal();
            if (mVar.classList.contains('active')) closeVariantModal();
        }
    });
})();
</script>
@endpush
