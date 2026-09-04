@extends('layouts.app')

@section('title', 'Aturan Courier')
@section('page-title', '🚚 Aturan Courier')
@section('page-subtitle', 'Auto-mapping kurir berdasarkan provinsi — dikelola dinamis dari database')

@push('styles')
<style>
    /* ── Layout grid ─────────────────────────────── */
    .cr-grid {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    @media (max-width: 1023px) { .cr-grid { grid-template-columns: 1fr; } }

    /* ── Form tambah ─────────────────────────────── */
    .cr-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .cr-form .cr-field { margin-bottom: 12px; }
    .cr-form .clay-input { width: 100%; font-size: .8rem; }
    .cr-form .cr-hint { font-size: .66rem; color: #9ca3af; margin-top: 3px; line-height: 1.45; }
    .cr-check { display: flex; align-items: center; gap: 7px; font-size: .78rem; color: #374151; cursor: pointer; font-weight: 600; }
    .cr-check input { width: 16px; height: 16px; accent-color: var(--color-primary, #FF6B6B); cursor: pointer; }

    /* ── Tabel ───────────────────────────────────── */
    .cr-badge-all { background: #f3f4f6; color: #6b7280; font-weight: 600; }
    .cr-badge-pm  { background: #e0f2fe; color: #0369a1; font-weight: 600; }
    .cr-badge-prov { background: #fef3c7; color: #92400e; font-weight: 600; }
    .cr-badge-code { background: #fae8ff; color: #86198f; font-weight: 700; font-family: monospace; }
    .cr-badge-cou { font-weight: 800; }
    .cou-flix-tf   { background:#dbeafe; color:#1d4ed8; }
    .cou-flix-idx  { background:#e0f2fe; color:#0369a1; }
    .cou-flix-sicepat { background:#dcfce7; color:#15803d; }
    .cou-sicepat  { background:#a7f3d0; color:#047857; }
    .cou-flix-spx  { background:#ede9fe; color:#6d28d9; }
    .cou-spx       { background:#f3e8ff; color:#7e22ce; }
    .cou-undeliverable { background:#fee2e2; color:#b91c1c; }
    .cr-toggle {
        border: none; border-radius: 999px; padding: 3px 11px;
        font-size: .68rem; font-weight: 700; cursor: pointer; font-family: inherit;
        transition: all .15s ease;
    }
    .cr-toggle.on  { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
    .cr-toggle.off { background: #f3f4f6; color: #6b7280; border: 1.5px solid #d1d5db; }
    .cr-toggle:hover { transform: translateY(-1px); box-shadow: 0 3px 0 rgba(0,0,0,.08); }
    .cr-move {
        width: 26px; height: 26px; border-radius: 8px; border: 1.5px solid #e5e7eb;
        background: #fff; color: #6b7280; font-size: .72rem; cursor: pointer; line-height: 1;
        transition: all .15s ease;
    }
    .cr-move:hover:not(:disabled) { background: #fff5f5; color: var(--color-primary, #FF6B6B); border-color: #fecaca; }
    .cr-move:disabled { opacity: .3; cursor: not-allowed; }
    .cr-edit-btn {
        background: none; border: none; color: var(--color-primary, #FF6B6B);
        font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px;
    }
    .cr-edit-btn:hover { text-decoration: underline; }
    .cr-del-btn {
        background: none; border: none; color: #dc2626; font-weight: 700;
        font-size: .76rem; cursor: pointer; padding: 2px 6px;
    }
    .cr-del-btn:hover { text-decoration: underline; }
    .cr-aksi { display: flex; align-items: center; gap: 5px; white-space: nowrap; }

    /* ── Info box ────────────────────────────────── */
    .cr-info { font-size: .75rem; color: #4b5563; line-height: 1.6; }
    .cr-info b { color: #1e1b2e; }
    .cr-info code {
        background: #f3f4f6; padding: 1px 6px; border-radius: 5px;
        font-size: .7rem; color: #6d28d9; font-weight: 700;
    }

    @media (max-width: 479px) {
        .cr-table-wrap { overflow-x: auto; }
        .cr-table-wrap .clay-table { min-width: 640px; }
    }
</style>
@endpush

@section('content')

{{-- Info cara kerja rules --}}
<div class="clay-card" style="padding:14px 18px;margin-bottom:16px;background:linear-gradient(135deg,#FFF7F7,#fff);" data-reveal>
    <div class="cr-info">
        💡 <b>Cara kerja:</b> aturan dievaluasi berurutan dari <b>Urutan</b> terkecil → rule pertama yang cocok
        (<code>metode bayar</code> + <code>provinsi</code>) yang menang.
        Kosongkan <b>Metode Bayar</b> / <b>Provinsi</b> agar berlaku untuk <b>semua</b>.
        Isi <b>Kode Produk</b> (mis. <code>SH</code>) untuk rule yang <b>khusus produk</b> — rule ini SELALU
        dievaluasi lebih dulu daripada aturan provinsi (courier produk tidak terpengaruh provinsi).
        Bila tidak ada rule yang cocok, courier fallback otomatis <code>spx</code>.
        Perubahan langsung berlaku untuk import order berikutnya — tanpa ubah kode.
    </div>
</div>

<div class="cr-grid">

    {{-- ── Form Tambah ─────────────────────────────────────────── --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <h2 style="margin:0 0 4px;font-size:1rem;font-weight:800;">➕ Tambah Aturan</h2>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:14px;">Mapping baru — langsung aktif dipakai saat import. Urutan otomatis di paling bawah.</div>

        <form id="cr-add-form" class="cr-form">
            <div class="cr-field">
                <label>Metode Bayar</label>
                <input type="text" name="payment_method" id="cr-payment" class="clay-input" list="cr-pm-list"
                       placeholder="kosongkan = semua">
                <datalist id="cr-pm-list">
                    <option value="cod">
                    <option value="bank_transfer">
                </datalist>
                <div class="cr-hint">Contoh: <b>cod</b> atau <b>bank_transfer</b>.</div>
            </div>

            <div class="cr-field">
                <label>Provinsi</label>
                <input type="text" name="province" id="cr-province" class="clay-input" list="cr-prov-list"
                       placeholder="kosongkan = semua provinsi">
                <datalist id="cr-prov-list">
                    @foreach($provinces as $p)<option value="{{ $p }}">@endforeach
                </datalist>
                <div class="cr-hint">Tulis nama provinsi (besar kecil bebas, otomatis di-uppercase).</div>
            </div>

            <div class="cr-field">
                <label>Kode Produk (khusus produk)</label>
                <input type="text" name="product_code" id="cr-product-code" class="clay-input" list="cr-code-list"
                       placeholder="kosongkan = semua produk">
                <datalist id="cr-code-list">
                    @foreach($productCodes as $pc)<option value="{{ $pc }}">@endforeach
                </datalist>
                <div class="cr-hint">Mis. <b>SH</b> — rule ini menang atas aturan provinsi untuk produk tsb.</div>
            </div>

            <div class="cr-field">
                <label>Courier *</label>
                <select name="courier" id="cr-courier" class="clay-input" required>
                    <option value="" disabled selected>— pilih courier —</option>
                    @foreach($couriers as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cr-field">
                <label class="cr-check">
                    <input type="checkbox" name="is_active" id="cr-active" value="1" checked>
                    Aktif (dipakai saat evaluasi)
                </label>
            </div>

            <button type="submit" class="clay-btn clay-btn-primary" style="width:100%;">+ Tambah Aturan</button>
        </form>
    </div>

    {{-- ── Tabel Rules ─────────────────────────────────────────── --}}
    <div class="clay-card" style="overflow:hidden;" data-reveal>
        <div style="padding:12px 18px;font-weight:800;font-size:.9rem;border-bottom:1px solid rgba(0,0,0,.06);
                    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span>🗂 Daftar Aturan <span id="cr-count" style="color:#9ca3af;font-weight:600;font-size:.75rem;">({{ $rules->count() }})</span></span>
            <span style="font-size:.68rem;color:#9ca3af;font-weight:500;">urut dari prioritas tertinggi ↓</span>
        </div>

        <div class="cr-table-wrap" id="cr-table-wrap">
            @include('courier_rule._table')
        </div>
    </div>
</div>

{{-- ── Modal Edit ─────────────────────────────────────────────── --}}
<div class="clay-modal" id="cr-modal" role="dialog" aria-modal="true" aria-labelledby="cr-modal-title">
    <div class="clay-modal-backdrop" onclick="closeCrEdit()"></div>
    <div class="clay-modal-container">
        <div class="clay-modal-header">
            <h2 id="cr-modal-title">✏️ Edit Aturan</h2>
            <button class="clay-modal-close" onclick="closeCrEdit()" type="button">✕</button>
        </div>
        <form id="cr-edit-form" class="cr-form">
            <div class="cr-modal-body" style="overflow-y:auto;max-height:65vh;padding:20px 24px;">
                <input type="hidden" name="sort_order" id="cr-e-sort">
                <div class="cr-field">
                    <label>Urutan (prioritas)</label>
                    <input type="text" id="cr-e-sort-display" class="clay-input" readonly
                           style="background:#f9fafb;color:#6b7280;cursor:not-allowed;">
                    <div class="cr-hint">Diubah otomatis via tombol ↑↓ di tabel.</div>
                </div>
                <div class="cr-field">
                    <label>Metode Bayar</label>
                    <input type="text" name="payment_method" id="cr-e-payment" class="clay-input" list="cr-pm-list"
                           placeholder="kosongkan = semua">
                    <div class="cr-hint">Contoh: <b>cod</b> atau <b>bank_transfer</b>.</div>
                </div>
                <div class="cr-field">
                    <label>Provinsi</label>
                    <input type="text" name="province" id="cr-e-province" class="clay-input" list="cr-prov-list"
                           placeholder="kosongkan = semua provinsi">
                    <div class="cr-hint">Tulis nama provinsi (otomatis di-uppercase).</div>
                </div>
                <div class="cr-field">
                    <label>Kode Produk (khusus produk)</label>
                    <input type="text" name="product_code" id="cr-e-product" class="clay-input" list="cr-code-list"
                           placeholder="kosongkan = semua produk">
                    <div class="cr-hint">Rule khusus produk menang atas aturan provinsi.</div>
                </div>
                <div class="cr-field">
                    <label>Courier *</label>
                    <select name="courier" id="cr-e-courier" class="clay-input" required>
                        @foreach($couriers as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cr-field">
                    <label class="cr-check">
                        <input type="checkbox" name="is_active" id="cr-e-active" value="1">
                        Aktif (dipakai saat evaluasi)
                    </label>
                </div>
            </div>
            <div style="padding:14px 24px;border-top:1px solid rgba(0,0,0,.06);display:flex;justify-content:flex-end;gap:8px;flex-shrink:0;">
                <button type="button" class="clay-btn clay-btn-outline" onclick="closeCrEdit()">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var CSRF = '{{ csrf_token() }}';
    var filterUrl = '{{ route("courier-rule.filter") }}';

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

    function refreshTable() {
        return fetch(filterUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('cr-table-wrap').innerHTML = data.html;
                document.getElementById('cr-count').textContent = '(' + data.total + ')';
                bindActions();
            });
    }

    function bindActions() {
        // Toggle buttons
        document.querySelectorAll('.cr-toggle-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var self = this;
                var id = self.dataset.id;
                self.disabled = true;
                post('/courier-rules/' + id + '/toggle', 'PATCH', null)
                    .then(function(json) {
                        if (json.success) {
                            self.className = 'cr-toggle ' + (json.is_active ? 'on' : 'off') + ' cr-toggle-btn';
                            self.textContent = json.is_active ? '● Aktif' : '○ Nonaktif';
                        }
                    })
                    .catch(function(err) { alert('Error: ' + err.message); })
                    .finally(function() { self.disabled = false; });
            });
        });

        // Move buttons
        document.querySelectorAll('.cr-move-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var self = this;
                var id = self.dataset.id;
                var dir = self.dataset.dir;
                self.disabled = true;
                post('/courier-rules/' + id + '/move/' + dir, 'POST', null)
                    .then(function(json) {
                        if (json.success) {
                            document.getElementById('cr-table-wrap').innerHTML = json.html;
                            document.getElementById('cr-count').textContent = '(' + json.total + ')';
                            bindActions();
                        }
                    })
                    .catch(function(err) { alert('Error: ' + err.message); });
            });
        });

        // Delete buttons
        document.querySelectorAll('.cr-del-btn-ajax').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm(this.dataset.confirm)) return;
                var self = this;
                var id = self.dataset.id;
                self.disabled = true;
                post('/courier-rules/' + id, 'DELETE', null)
                    .then(function(json) {
                        if (json.success) refreshTable();
                        else alert('Gagal: ' + json.message);
                    })
                    .catch(function(err) { alert('Error: ' + err.message); })
                    .finally(function() { self.disabled = false; });
            });
        });
    }

    // ── ADD FORM (AJAX) ──
    document.getElementById('cr-add-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = this.querySelector('button[type="submit"]');
        btn.disabled = true; btn.innerHTML = 'Menyimpan...';

        var body = {
            payment_method: document.getElementById('cr-payment').value.trim(),
            province: document.getElementById('cr-province').value.trim(),
            product_code: document.getElementById('cr-product-code').value.trim(),
            courier: document.getElementById('cr-courier').value,
            is_active: document.getElementById('cr-active').checked ? 1 : 0,
        };

        post('{{ route("courier-rule.store") }}', 'POST', body)
            .then(function(json) {
                if (json.success) {
                    // Reset form
                    document.getElementById('cr-payment').value = '';
                    document.getElementById('cr-province').value = '';
                    document.getElementById('cr-product-code').value = '';
                    document.getElementById('cr-courier').value = '';
                    document.getElementById('cr-active').checked = true;
                    refreshTable();
                } else {
                    alert('Gagal: ' + json.message);
                }
            })
            .catch(function(err) { alert('Error: ' + err.message); })
            .finally(function() { btn.disabled = false; btn.innerHTML = '+ Tambah Aturan'; });
    });

    // ── EDIT MODAL ──
    var modal = document.getElementById('cr-modal');
    var editForm = document.getElementById('cr-edit-form');
    var editId = null;

    window.openCrEdit = function (id) {
        var btn = document.getElementById('cr-edit-' + id);
        if (!btn) return;
        editId = id;

        document.getElementById('cr-e-sort').value = btn.dataset.sort;
        document.getElementById('cr-e-sort-display').value = 'Urutan ' + btn.dataset.sort + ' — dievaluasi ' + (parseInt(btn.dataset.sort) <= 5 ? 'awal' : 'belakangan');
        document.getElementById('cr-e-payment').value  = btn.dataset.payment;
        document.getElementById('cr-e-province').value = btn.dataset.province;
        document.getElementById('cr-e-product').value  = btn.dataset.product;
        document.getElementById('cr-e-courier').value  = btn.dataset.courier;
        document.getElementById('cr-e-active').checked = btn.dataset.active === '1';

        modal.classList.add('active');
    };

    window.closeCrEdit = function () {
        modal.classList.remove('active');
        editId = null;
    };

    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!editId) return;
        var btn = editForm.querySelector('button[type="submit"]');
        btn.disabled = true; btn.innerHTML = 'Menyimpan...';

        var body = {
            sort_order: document.getElementById('cr-e-sort').value,
            payment_method: document.getElementById('cr-e-payment').value.trim(),
            province: document.getElementById('cr-e-province').value.trim(),
            product_code: document.getElementById('cr-e-product').value.trim(),
            courier: document.getElementById('cr-e-courier').value,
            is_active: document.getElementById('cr-e-active').checked ? 1 : 0,
        };

        post('/courier-rules/' + editId, 'PUT', body)
            .then(function(json) {
                if (json.success) { closeCrEdit(); refreshTable(); }
                else alert('Gagal: ' + json.message);
            })
            .catch(function(err) { alert('Error: ' + err.message); })
            .finally(function() { btn.disabled = false; btn.innerHTML = '💾 Simpan'; });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) closeCrEdit();
    });

    bindActions();
})();
</script>
@endpush
