@extends('layouts.app')
@section('title','Produk')
@section('page-title','📦 Produk')
@section('page-subtitle','Master produk & varian — stok per gudang dikelola di halaman Gudang')

@push('styles')
<style>
    /* Modal styles — now centralized in clay.css (clay-modal) */
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
            <input type="text" id="search-input" value="{{ request('search') }}" placeholder="Cari kode / nama / kategori…" class="clay-input" style="flex:1;min-width:160px;">
            <select id="goods-type-filter" class="clay-input" style="width:150px;">
                <option value="">Semua Tipe</option>
                @foreach(\App\Models\Product::GOODS_TYPE_LABELS as $key => $label)
                    <option value="{{ $key }}" {{ request('goods_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select id="status-filter" class="clay-input" style="width:120px;">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <select id="ad-status-filter" class="clay-input" style="width:130px;">
                <option value="">Semua Iklan</option>
                <option value="running" {{ request('ad_status') === 'running' ? 'selected' : '' }}>🟢 Running</option>
                <option value="testing" {{ request('ad_status') === 'testing' ? 'selected' : '' }}>🔬 Testing</option>
            </select>
        </div>
        <button type="button" class="clay-btn clay-btn-primary" onclick="openProductModal(this,'create')">＋ Tambah Produk</button>
    </div>
</div>

<div id="product-count" style="font-size:.78rem;color:#9ca3af;margin-bottom:12px;padding:0 4px;">
    Menampilkan {{ $products->total() }} produk
</div>

<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll" id="product-table-wrap">
        @include('product._table')
    </div>
    <div id="product-pagination" style="padding:12px 20px;border-top:1px solid rgba(0,0,0,.05);">
        @if($products->hasPages())
            {{ $products->links() }}
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
                <div>
                    <label>Status Iklan</label>
                    <select id="pm-ad-status" class="clay-input">
                        <option value="testing">🔬 Testing</option>
                        <option value="running">🟢 Running</option>
                    </select>
                    <div style="font-size:.65rem;color:#9ca3af;margin-top:2px;">Testing = produk fase uji; Running = sudah aktif diiklankan.</div>
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
    // LIVE FILTERING (AJAX)
    // ══════════════════════════════════════════════════════
    var searchInput = document.getElementById('search-input');
    var goodsTypeFilter = document.getElementById('goods-type-filter');
    var statusFilter = document.getElementById('status-filter');
    var adStatusFilter = document.getElementById('ad-status-filter');
    var tableWrap = document.getElementById('product-table-wrap');
    var paginationWrap = document.getElementById('product-pagination');
    var countEl = document.getElementById('product-count');
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

    function fetchFiltered() {
        var url = filterUrl + '?' + getFilterParams().toString();
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            tableWrap.innerHTML = data.html;
            paginationWrap.innerHTML = data.pagination || '';
            countEl.textContent = 'Menampilkan ' + data.total + ' produk';
            bindToggleEvents();
            bindPaginationLinks();
        })
        .catch(function(err) { console.error('Filter error:', err); });
    }

    function fetchPage(url) {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            tableWrap.innerHTML = data.html;
            paginationWrap.innerHTML = data.pagination || '';
            countEl.textContent = 'Menampilkan ' + data.total + ' produk';
            bindToggleEvents();
            bindPaginationLinks();
            tableWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(function(err) { console.error('Page fetch error:', err); });
    }

    function bindPaginationLinks() {
        paginationWrap.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                fetchPage(this.href);
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
    bindPaginationLinks();

    // ══════════════════════════════════════════════════════
    // TOGGLE STATUS (NO RELOAD)
    // ══════════════════════════════════════════════════════
    function bindToggleEvents() {
        tableWrap.querySelectorAll('.clay-toggle input[type="checkbox"]').forEach(function(input) {
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
                        // No reload — just re-enable the toggle
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

    window.openProductModal = function(btn, mode) {
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
        } else {
            pm.method = 'POST';
            pm.url = '{{ route('product.store') }}';
            pmTitle.textContent = '➕ Tambah Produk';
            f('pm-kode').value = '';
            f('pm-nama').value = '';
            f('pm-goods-type').value = 'core';
            f('pm-kategori').value = '';
            f('pm-selling').value = '';
            f('pm-hpp').value = '';
            f('pm-unit').value = 'pcs';
            f('pm-minstock').value = '0';
            f('pm-deskripsi').value = '';
            f('pm-status').value = 'active';
            f('pm-ad-status').value = 'testing';
        }
        mProd.classList.add('active');
        setTimeout(function() { f('pm-kode').focus(); }, 150);
    };

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
            ad_status: f('pm-ad-status').value,
        };
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
