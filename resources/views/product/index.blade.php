@extends('layouts.app')
@section('title','Produk')
@section('page-title','📦 Produk')
@section('page-subtitle','Master produk & varian — stok per gudang dikelola di halaman Gudang')

@push('styles')
<style>
    .pm-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .pm-modal.active { display: flex; }
    .pm-modal .pm-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(2px); }
    .pm-modal .pm-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 540px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25); animation: pmIn .22s ease;
    }
    @keyframes pmIn { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
    .pm-modal .pm-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .pm-modal .pm-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .pm-modal .pm-close { background: #f3f4f6; border: none; border-radius: 8px; width: 30px; height: 30px; cursor: pointer; color: #6b7280; }
    .pm-modal .pm-close:hover { background: #e5e7eb; }
    .pm-modal .pm-body { padding: 18px 20px; max-height: 62vh; overflow-y: auto; }
    .pm-modal .pm-body label { display: block; font-size: .72rem; font-weight: 700; color: #6b7280; margin-bottom: 4px; }
    .pm-modal .pm-body .clay-input { font-size: .85rem; padding: 7px 10px; }
    .pm-modal .pm-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06); }

    .clay-toggle {
        position: relative; display: inline-block; width: 36px; height: 20px; vertical-align: middle; cursor: pointer;
    }
    .clay-toggle input { position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer; }
    .clay-toggle .clay-toggle-slider { position: absolute; inset: 0; background: #d1d5db; border-radius: 999px; transition: background .18s; }
    .clay-toggle .clay-toggle-slider::before {
        content: ''; position: absolute; width: 14px; height: 14px; left: 3px; top: 3px;
        background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.3); transition: transform .18s;
    }
    .clay-toggle input:checked + .clay-toggle-slider { background: var(--color-primary, #FF6B6B); }
    .clay-toggle input:checked + .clay-toggle-slider::before { transform: translateX(16px); }
    .clay-toggle-sm { transform: scale(.85); transform-origin: center; }
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
        <div style="flex:1;min-width:220px;">
            <form method="GET" action="{{ route('product.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode / nama / kategori…" class="clay-input" style="flex:1;min-width:160px;">
                <select name="goods_type" class="clay-input" style="width:150px;">
                    <option value="">Semua Tipe</option>
                    @foreach(\App\Models\Product::GOODS_TYPE_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ request('goods_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="clay-input" style="width:120px;">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
                <button class="clay-btn clay-btn-outline" type="submit">🔍</button>
            </form>
        </div>
        <button type="button" class="clay-btn clay-btn-primary" onclick="openProductModal(this,'create')">＋ Tambah Produk</button>
    </div>
</div>

<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table" style="min-width:980px;">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Produk</th>
                    <th>Tipe</th>
                    <th>Gudang</th>
                    <th style="text-align:right;">Stok Total</th>
                    <th style="text-align:right;">Min. Stok</th>
                    <th style="text-align:right;">HPP</th>
                    <th style="text-align:right;">Harga Jual</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                @php
                    $primary = $p->primaryInventory?->first();
                    $rowId = 'pv-'.$p->id;
                @endphp
                <tr>
                    <td style="font-weight:800;color:#7c3aed;">{{ $p->code }}</td>
                    <td>
                        <div style="font-weight:600;">{{ $p->name }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;">{{ $p->unit }}{{ $p->category ? ' · '.$p->category : '' }}</div>
                    </td>
                    <td><span class="clay-badge clay-badge-{{ $p->goods_type === 'core' ? 'blue' : ($p->goods_type === 'additional' ? 'purple' : 'gray') }}">{{ \App\Models\Product::GOODS_TYPE_LABELS[$p->goods_type] ?? $p->goods_type }}</span></td>
                    <td>
                        <div style="font-size:.8rem;font-weight:700;">
                            @if($p->goods_type === 'core')
                                {{ $primary?->name ?? '—' }}
                            @else
                                <span style="color:#9ca3af;font-weight:400;">—</span>
                            @endif
                        </div>
                        <div style="font-size:.68rem;color:#9ca3af;">{{ $p->inventories->count() }} gudang terdaftar</div>
                    </td>
                    <td style="text-align:right;font-weight:800;">{{ number_format($p->stok,0,',','.') }}</td>
                    <td style="text-align:right;">
                        @if($p->min_stock > 0)
                            <span style="font-weight:700;color:#374151;">{{ number_format($p->min_stock,0,',','.') }}</span>
                            <div>
                                @if($p->stok <= $p->min_stock)
                                    <span class="clay-badge clay-badge-red">⚠ Restock</span>
                                @else
                                    <span class="clay-badge clay-badge-green">Aman</span>
                                @endif
                            </div>
                        @else
                            <span style="color:#9ca3af;font-size:.78rem;">—</span>
                        @endif
                    </td>
                    <td style="text-align:right;">{{ $p->purchase_price ? number_format($p->purchase_price,0,',','.') : '—' }}</td>
                    <td style="text-align:right;">{{ number_format($p->selling_price,0,',','.') }}</td>
                    <td style="text-align:center;">
                        <label class="clay-toggle" title="Ubah status produk">
                            <input type="checkbox" data-toggle-url="{{ route('product.toggle-status', $p) }}" {{ $p->status==='active'?'checked':'' }}>
                            <span class="clay-toggle-slider"></span>
                        </label>
                    </td>
                    <td style="text-align:right;">
                        <div style="display:flex;justify-content:flex-end;gap:6px;align-items:center;flex-wrap:wrap;">
                            <button type="button" class="clay-btn clay-btn-sm" style="background:#f3f4f6;color:#374151;"
                                    onclick="toggleVarian('{{ $p->id }}')" title="Lihat / kelola varian">
                                🔖 Varian ({{ $p->variants->count() }}) <span id="chev-{{ $rowId }}" style="display:inline-block;transition:transform .22s;font-size:.7rem;">▾</span>
                            </button>
                            <button type="button" class="clay-btn clay-btn-sm clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;"
                                    onclick="openProductModal(this,'edit')" title="Edit produk"
                                    data-url="{{ route('product.update', $p) }}"
                                    data-code="{{ $p->code }}" data-name="{{ $p->name }}"
                                    data-category="{{ $p->category }}" data-goods-type="{{ $p->goods_type }}"
                                    data-min-stock="{{ $p->min_stock }}" data-description="{{ $p->description }}"
                                    data-purchase-price="{{ $p->purchase_price }}" data-selling-price="{{ $p->selling_price }}"
                                    data-unit="{{ $p->unit }}" data-status="{{ $p->status }}">✏️</button>
                            <form method="POST" action="{{ route('product.destroy', $p) }}" onsubmit="return confirm('Hapus produk {{ $p->name }} beserta variannya?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="clay-btn clay-btn-sm clay-btn-danger" style="padding:5px 10px;font-size:.72rem;" title="Hapus produk">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>

                {{-- ── BARIS EXPAND: Varian ─────────────────────── --}}
                <tr id="{{ $rowId }}" style="display:none;">
                    <td colspan="10" style="padding:0;background:#fafafa;border-top:2px dashed rgba(255,107,107,.12);">
                        <div style="display:flex;align-items:center;gap:10px;padding:12px 20px;background:#fff;border-bottom:1px solid rgba(0,0,0,.05);">
                            <span style="background:var(--color-secondary);color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:999px;">🔖 Varian</span>
                            <span style="font-size:.75rem;color:#6b7280;font-weight:600;">{{ $p->variants->count() }} varian</span>
                            <button type="button" class="clay-btn clay-btn-primary" style="margin-left:auto;padding:6px 12px;font-size:.72rem;"
                                    onclick="openVariantModal('{{ $p->id }}', this)" data-store-url="{{ route('product.variant.store', $p) }}">＋ Tambah Varian</button>
                        </div>
                        @if($p->variants->isEmpty())
                            <div style="padding:22px;text-align:center;color:#9ca3af;font-size:.82rem;">
                                Belum ada varian. Klik <strong>＋ Tambah Varian</strong> (mis. power +1.00, +1.25).
                            </div>
                        @else
                        <div style="overflow-x:auto;padding-left:36px;border-left:3px solid rgba(78,205,196,.18);margin-left:16px;">
                            <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
                                <thead>
                                    <tr style="background:#f9fefe;">
                                        @foreach(['Kode','Nama Varian','Jenis','Power','Status','Aksi'] as $h)
                                        <th style="padding:8px 10px;font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;
                                                   text-align:{{ in_array($h,['Power','Status','Aksi']) ? 'right' : 'left' }};border-bottom:1px solid rgba(0,0,0,.05);">{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($p->variants as $v)
                                    <tr>
                                        <td style="padding:8px 10px;"><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.68rem;">{{ $v->code }}</span></td>
                                        <td style="padding:8px 10px;font-weight:600;">{{ $v->name }}</td>
                                        <td style="padding:8px 10px;color:#6b7280;">{{ $v->jenis ?? '-' }}</td>
                                        <td style="padding:8px 10px;text-align:right;font-weight:700;white-space:nowrap;">{{ (float)$v->power > 0 ? '+'.number_format($v->power,2,',','.') : '-' }}</td>
                                        <td style="padding:8px 10px;text-align:right;">
                                            <label class="clay-toggle clay-toggle-sm" title="Ubah status varian">
                                                <input type="checkbox" data-toggle-url="{{ route('product.variant.toggle-status', $v) }}" {{ $v->status==='active'?'checked':'' }}>
                                                <span class="clay-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td style="padding:8px 10px;text-align:right;">
                                            <div style="display:flex;justify-content:flex-end;gap:4px;">
                                                <button type="button" class="clay-btn clay-btn-sm clay-btn-secondary" style="padding:3px 8px;font-size:.65rem;"
                                                        onclick="openVariantModal('{{ $p->id }}', this)" title="Edit varian"
                                                        data-store-url="{{ route('product.variant.store', $p) }}"
                                                        data-url="{{ route('product.variant.update', $v) }}"
                                                        data-id="{{ $v->id }}"
                                                        data-code="{{ $v->code }}" data-name="{{ $v->name }}"
                                                        data-jenis="{{ $v->jenis }}" data-power="{{ $v->power }}" data-status="{{ $v->status }}">✏️</button>
                                                <button type="button" class="clay-btn clay-btn-sm clay-btn-danger" style="padding:3px 8px;font-size:.65rem;"
                                                        onclick="deleteVariant('{{ $v->id }}')" title="Hapus varian">🗑</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;padding:48px;color:#9ca3af;">Belum ada produk. Klik <strong>＋ Tambah Produk</strong> untuk membuat produk pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 20px;border-top:1px solid rgba(0,0,0,.05);">
        {{ $products->links() }}
    </div>
</div>

{{-- ═══════════════ MODAL TAMBAH / EDIT PRODUK ═══════════════ --}}
<div class="pm-modal" id="modal-product" role="dialog" aria-modal="true">
    <div class="pm-backdrop" onclick="closeProductModal()"></div>
    <div class="pm-container">
        <div class="pm-header">
            <h2 id="pm-title">➕ Tambah Produk</h2>
            <button class="pm-close" onclick="closeProductModal()" type="button">✕</button>
        </div>
        <div class="pm-body">
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
                <div style="grid-column: span 2;">
                    <label>Deskripsi</label>
                    <textarea id="pm-deskripsi" rows="2" class="clay-input" style="resize:none;" placeholder="Opsional"></textarea>
                </div>
            </div>
            <div style="margin-top:10px;font-size:.72rem;color:#9ca3af;background:#fafafa;border-radius:8px;padding:8px 12px;">
                📦 Varian default dibuat otomatis. Produk belum terdaftar di gudang mana pun — daftarkan lewat halaman <b>Gudang</b>.
            </div>
        </div>
        <div class="pm-footer">
            <button class="clay-btn clay-btn-outline" onclick="closeProductModal()" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="pm-save" type="button">💾 Simpan Produk</button>
        </div>
    </div>
</div>

{{-- ═══════════════ MODAL TAMBAH / EDIT VARIAN ═══════════════ --}}
<div class="pm-modal" id="modal-variant" role="dialog" aria-modal="true">
    <div class="pm-backdrop" onclick="closeVariantModal()"></div>
    <div class="pm-container">
        <div class="pm-header">
            <h2 id="pv-title">➕ Tambah Varian</h2>
            <button class="pm-close" onclick="closeVariantModal()" type="button">✕</button>
        </div>
        <div class="pm-body">
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
        <div class="pm-footer">
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

    // ── MODAL PRODUK ────────────────────────────────
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
        };
        post(pm.url, pm.method, body)
            .then(function(json) { if (json.success) { window.location.reload(); } else { alert('Gagal: ' + json.message); btn.disabled = false; btn.innerHTML = '💾 Simpan Produk'; } })
            .catch(function(err) { alert('Error: ' + err.message); btn.disabled = false; btn.innerHTML = '💾 Simpan Produk'; });
    });

    // ── MODAL VARIAN ────────────────────────────────
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
            .then(function(json) { if (json.success) { window.location.reload(); } else { alert('Gagal: ' + json.message); btn.disabled = false; btn.innerHTML = '💾 Simpan Varian'; } })
            .catch(function(err) { alert('Error: ' + err.message); btn.disabled = false; btn.innerHTML = '💾 Simpan Varian'; });
    });

    window.deleteVariant = function(id) {
        if (!confirm('Hapus varian ini?')) return;
        post('{{ route('product.variant.destroy', ':id') }}'.replace(':id', id), 'DELETE', null)
            .then(function(json) { if (json.success) window.location.reload(); else alert('Gagal: ' + json.message); })
            .catch(function(err) { alert('Error: ' + err.message); });
    };

    // ── Toggle status (produk & varian) ─────────────
    document.querySelectorAll('.clay-toggle input[type="checkbox"]').forEach(function(input) {
        input.addEventListener('change', function() {
            var self = this;
            var url = self.dataset.toggleUrl;
            if (!url) return;
            self.disabled = true;
            post(url, 'PATCH', null)
                .then(function(json) {
                    if (!json.success) { self.checked = !self.checked; alert('Gagal: ' + json.message); }
                    else { window.location.reload(); }
                })
                .catch(function(err) { self.checked = !self.checked; alert('Error: ' + err.message); })
                .finally(function() { self.disabled = false; });
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (mProd.classList.contains('active')) closeProductModal();
            if (mVar.classList.contains('active')) closeVariantModal();
        }
    });
})();
</script>
@endpush
