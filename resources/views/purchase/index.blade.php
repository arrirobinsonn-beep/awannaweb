@extends('layouts.app')
@section('title','Barang Masuk')
@section('page-title','📦 Pembelian Barang')
@section('page-subtitle','Catat pembelian barang, tandai saat barang diterima')

@section('content')
@if(session('success'))
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#d1fae5;color:#065f46;font-weight:600;border-radius:8px;">
    {{ session('success') }}
</div>
@endif

{{-- ═══ Collapsible Form Tambah Pembelian ═══ --}}
<details class="pm-card" style="padding:0;margin-bottom:20px;" data-reveal>
    <summary style="padding:16px 20px;cursor:pointer;display:flex;align-items:center;gap:10px;font-weight:700;font-size:.95rem;user-select:none;list-style:none;">
        <div style="width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,#3b82f6,#2563eb);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem;flex-shrink:0;">➕</div>
        <div style="flex:1;">
            Tambah Pembelian Baru
            <div style="font-size:.72rem;color:#9ca3af;font-weight:400;">Klik untuk membuka formulir</div>
        </div>
        <span class="pm-details-chevron" style="transition:transform .2s;">▾</span>
    </summary>
    <div style="padding:0 20px 20px;">
        <form id="pu-add-form">
            <div class="form-grid">
                <div>
                    <label class="pm-label">TANGGAL *</label>
                    <input type="date" name="date" required value="{{ old('date', now()->format('Y-m-d')) }}" class="clay-input">
                </div>
                <div>
                    <label class="pm-label">SUPPLIER</label>
                    <select name="supplier_id" class="clay-input">
                        <option value="">— Pilih Supplier —</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:span 2;">
                    <label class="pm-label">VARIAN PRODUK *</label>
                    <select name="product_variant_id" id="purchase-variant" required class="clay-input">
                        <option value="">— Pilih Produk / Varian —</option>
                        @foreach($products as $p)
                            <optgroup label="{{ $p->name }}" data-primary-wh="{{ $p->primaryInventoryId() ?? '' }}">
                                @foreach($p->variants as $v)
                                    <option value="{{ $v->id }}">
                                        {{ $v->name }} {{ (float)$v->power > 0 ? '(+'.number_format($v->power,2,',','.').')' : '' }} — stok {{ $v->stock }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:span 2;">
                    <label class="pm-label">GUDANG TUJUAN *</label>
                    <select name="inventory_id" id="purchase-inventory" required class="clay-input">
                        <option value="">— Pilih Gudang —</option>
                        @foreach($inventories as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                        @endforeach
                    </select>
                    <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Stok masuk dicatat ke gudang ini</div>
                </div>
                <div>
                    <label class="pm-label">JUMLAH (QTY) *</label>
                    <input type="number" name="quantity" required min="1" class="clay-input">
                </div>
                <div>
                    <label class="pm-label">HARGA SATUAN *</label>
                    <input type="number" name="unit_price" required min="0" step="0.01" class="clay-input">
                </div>
                <div>
                    <label class="pm-label">ONGKIR</label>
                    <input type="number" name="shipping_cost" min="0" step="0.01" value="0" class="clay-input">
                </div>
                <div style="grid-column:span 2;">
                    <label class="pm-label">KETERANGAN</label>
                    <input type="text" name="note" maxlength="255" class="clay-input" placeholder="Opsional">
                </div>
            </div>
            <div style="margin-top:16px;display:flex;justify-content:flex-end;gap:8px;">
                <button type="reset" class="clay-btn">Reset</button>
                <button type="submit" class="clay-btn clay-btn-primary">📤 Simpan Pembelian</button>
            </div>
        </form>
    </div>
</details>

{{-- ═══ Filter ═══ --}}
<div class="pm-card" style="padding:0;margin-bottom:20px;" data-reveal>
    <button type="button" class="pm-filter-toggle" onclick="document.getElementById('filterBody').classList.toggle('open');this.querySelector('.pm-filter-chevron').classList.toggle('open')">
        <span>🔍 Filter & Pencarian</span>
        <span class="pm-filter-chevron">▾</span>
    </button>
    <div id="filterBody" class="pm-filter-body{{ request()->hasAny(['variant_id','supplier_id','inventory_id','bulan','status']) ? ' open' : '' }}">
        <div class="pm-filter-form">
            <div class="pm-filter-grid">
                <select id="pu-filter-variant" class="clay-input">
                    <option value="">Semua Produk / Varian</option>
                    @foreach($products as $p)
                        <optgroup label="{{ $p->name }}">
                            @foreach($p->variants as $v)
                                <option value="{{ $v->id }}">{{ $v->name }} {{ (float)$v->power > 0 ? '(+'.number_format($v->power,2,',','.').')' : '' }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <select id="pu-filter-supplier" class="clay-input">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option>
                    @endforeach
                </select>
                <select id="pu-filter-inventory" class="clay-input">
                    <option value="">Semua Gudang</option>
                    @foreach($inventories as $inv)
                        <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                    @endforeach
                </select>
                <select id="pu-filter-bulan" class="clay-input">
                    <option value="">Semua Bulan</option>
                    @foreach($monthList as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
                <select id="pu-filter-status" class="clay-input">
                    <option value="">Semua Status</option>
                    <option value="in_transit">📋 Belum Masuk</option>
                    <option value="received">✅ Diterima</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Table ═══ --}}
<div class="pm-card" style="padding:0;overflow:hidden;" data-reveal>
    <div id="pu-count" style="padding:12px 18px;font-size:.78rem;color:#6b7280;border-bottom:1px solid #f3f4f6;">
        Menampilkan {{ $purchases->total() }} pembelian
    </div>
    <div id="pu-table-wrap">
        @include('purchase._table', ['purchases' => $purchases])
    </div>
    <div id="pu-pagination" style="padding:12px 18px;">
        {{ $purchases->links() }}
    </div>
</div>

{{-- ═══ MODAL: Terima Barang ═══ --}}
<div id="receiveModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px);" onclick="closeReceiveModal()"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #e5e7eb;">
            <div>
                <h3 style="margin:0;font-size:1rem;font-weight:800;">📦 Tandai Diterima</h3>
                <div style="font-size:.75rem;color:#6b7280;margin-top:2px;">Konfirmasi barang yang diterima</div>
            </div>
            <button onclick="closeReceiveModal()" style="width:32px;height:32px;border-radius:50%;border:none;background:#f3f4f6;cursor:pointer;font-size:1.1rem;">✕</button>
        </div>
        <div style="padding:20px 24px;">
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
                <div style="font-size:.72rem;color:#0369a1;font-weight:600;margin-bottom:4px;">📋 Data Pembelian</div>
                <div id="rcvProductName" style="font-weight:700;font-size:.9rem;color:#1e1b2e;"></div>
                <div style="font-size:.78rem;color:#6b7280;margin-top:2px;">Total: <span id="rcvOriginalTotal" style="font-weight:700;color:var(--color-primary);"></span></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div>
                    <label class="pm-label">Qty Diterima</label>
                    <input type="number" id="rcvQty" min="1" class="clay-input" placeholder="Kosongkan = sesuai pesanan">
                </div>
                <div style="grid-column:span 2;">
                    <label class="pm-label">Catatan (opsional)</label>
                    <textarea id="rcvNote" rows="2" maxlength="500" class="clay-input" style="width:100%;box-sizing:border-box;resize:vertical;" placeholder="Contoh: 10 rusak, selisih qty, dll."></textarea>
                </div>
            </div>
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.72rem;color:#92400e;line-height:1.5;">
                ⚠️ <strong>Stok & HPP</strong> akan diperbarui otomatis setelah barang diterima.
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeReceiveModal()" class="clay-btn clay-btn-outline" style="padding:8px 14px;font-size:.82rem;">Batal</button>
                <button type="button" onclick="submitReceive()" class="clay-btn clay-btn-primary" style="padding:8px 16px;font-size:.82rem;">✅ Konfirmasi Diterima</button>
            </div>
        </div>
    </div>
</div>

<style>
.pm-label { display:block;font-size:.73rem;font-weight:700;margin-bottom:4px;color:#374151; }
.pm-card { background:#fff;border:1.5px solid #e5e7eb;border-radius:16px; }
.pm-card[open] > summary .pm-details-chevron { transform:rotate(180deg); }

.pm-filter-toggle {
    width:100%;display:flex;align-items:center;justify-content:space-between;
    padding:14px 18px;background:none;border:none;cursor:pointer;
    font-size:.85rem;font-weight:700;color:#374151;
}
.pm-filter-toggle:hover { background:#f9fafb; }
.pm-filter-chevron { font-size:.7rem;transition:transform .2s;color:#9ca3af; }
.pm-filter-chevron.open { transform:rotate(180deg); }
.pm-filter-body { display:none;padding:0 18px 16px; }
.pm-filter-body.open { display:block; }
.pm-filter-form { display:flex;flex-direction:column;gap:10px; }
.pm-filter-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px; }

.pm-col-num { text-align:right; }
.pm-row--in_transit { background:#fffbeb; }
.pm-row--received { background:#d1fae5; }

.pm-mobile-cards { display:none; }
.pm-card-item { border-bottom:1px solid #f3f4f6;padding:14px 16px; }
.pm-card-item:last-child { border-bottom:none; }
.pm-card--in_transit { background:#fffbeb; }
.pm-card--received { background:#d1fae5; }
.pm-card-header { display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px; }
.pm-card-body { display:flex;flex-direction:column;gap:6px;margin-bottom:12px; }
.pm-card-row { display:flex;justify-content:space-between;align-items:center;font-size:.8rem; }
.pm-card-key { color:#9ca3af;font-size:.72rem;font-weight:600;flex-shrink:0;margin-right:8px; }
.pm-card-footer { display:flex;gap:8px;justify-content:flex-end;padding-top:10px;border-top:1px solid #f3f4f6; }

@media (max-width: 768px) {
    .pm-desktop-table { display:none; }
    .pm-mobile-cards { display:block; }
    .pm-filter-grid { grid-template-columns:1fr; }
    .pm-filter-body { padding:0 14px 14px; }
    #receiveModal > div:last-child { border-radius:16px; }
    #receiveModal form > div[style*="grid"] { grid-template-columns:1fr !important; }
}
</style>
@endsection

@push('scripts')
<script>
(function() {
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;

    // ── Auto-fill gudang when variant changes ──
    document.getElementById('purchase-variant').addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;
        var group = opt.parentElement;
        var primary = group ? (group.getAttribute('data-primary-wh') || '') : '';
        if (primary) document.getElementById('purchase-inventory').value = primary;
    });

    // ── AJAX fetch helper ──
    function fetchTable() {
        var params = new URLSearchParams();
        var fields = [
            ['pu-filter-variant', 'variant_id'],
            ['pu-filter-supplier', 'supplier_id'],
            ['pu-filter-inventory', 'inventory_id'],
            ['pu-filter-bulan', 'bulan'],
            ['pu-filter-status', 'status'],
        ];
        fields.forEach(function(f) {
            var v = document.getElementById(f[0]).value;
            if (v) params.set(f[1], v);
        });

        fetch('{{ route("purchase.filter") }}?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('pu-table-wrap').innerHTML = data.html;
            document.getElementById('pu-count').textContent = 'Menampilkan ' + data.total + ' pembelian';
        });
    }

    // ── Filter: auto-apply on change ──
    ['pu-filter-variant', 'pu-filter-supplier', 'pu-filter-inventory', 'pu-filter-bulan', 'pu-filter-status'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', fetchTable);
    });

    // ── Add form ──
    document.getElementById('pu-add-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = this.querySelector('button[type="submit"]');
        btn.disabled = true; btn.innerHTML = 'Menyimpan...';

        var form = this;
        var body = {};
        new FormData(form).forEach(function(v, k) { body[k] = v; });

        fetch('{{ route("purchase.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        })
        .then(function(r) {
            if (!r.ok) return r.json().then(function(e) { throw new Error(e.message || Object.values(e.errors||{}).flat()[0] || 'Gagal'); });
            return r.json();
        })
        .then(function(json) {
            if (json.success) {
                form.reset();
                // Re-set date to today
                form.querySelector('[name="date"]').value = new Date().toISOString().split('T')[0];
                form.querySelector('[name="shipping_cost"]').value = '0';
                fetchTable();
                // Close details
                form.closest('details').removeAttribute('open');
            }
        })
        .catch(function(err) { alert('Error: ' + err.message); })
        .finally(function() { btn.disabled = false; btn.innerHTML = '📤 Simpan Pembelian'; });
    });

    // ── Delete ──
    window.deletePurchase = function(btn) {
        if (!confirm(btn.dataset.confirm)) return;
        btn.disabled = true;
        fetch('/barang-masuk/' + btn.dataset.id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            if (json.success) fetchTable();
            else alert('Gagal: ' + json.message);
        })
        .catch(function(err) { alert('Error: ' + err.message); })
        .finally(function() { btn.disabled = false; });
    };

    // ── Receive modal ──
    var receivePurchaseId = null;
    window.openReceiveModal = function(id, name, qty, price, total) {
        receivePurchaseId = id;
        document.getElementById('rcvProductName').textContent = name;
        document.getElementById('rcvOriginalTotal').textContent = total;
        document.getElementById('rcvQty').value = '';
        document.getElementById('rcvQty').placeholder = 'Kosongkan = ' + qty + ' (sesuai pesanan)';
        document.getElementById('rcvNote').value = '';
        document.getElementById('receiveModal').style.display = 'flex';
    };
    window.closeReceiveModal = function() {
        document.getElementById('receiveModal').style.display = 'none';
        receivePurchaseId = null;
    };
    window.submitReceive = function() {
        if (!receivePurchaseId) return;
        var body = {};
        var qty = document.getElementById('rcvQty').value.trim();
        var note = document.getElementById('rcvNote').value.trim();
        if (qty) body.received_qty = parseInt(qty);
        if (note) body.received_note = note;

        fetch('/barang-masuk/' + receivePurchaseId + '/receive', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        })
        .then(function(r) {
            if (!r.ok) return r.json().then(function(e) { throw new Error(e.message || 'Gagal'); });
            return r.json();
        })
        .then(function(json) {
            closeReceiveModal();
            if (json.success) fetchTable();
            else alert('Gagal: ' + json.message);
        })
        .catch(function(err) { alert('Error: ' + err.message); });
    };
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeReceiveModal(); });
})();
</script>
@endpush
