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
    .cr-del-form { display: inline; }
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

    /* ── Modal edit (pola be-modal) ──────────────── */
    .cr-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .cr-modal.active { display: flex; }
    .cr-modal .cr-backdrop {
        position: absolute; inset: 0;
        background: rgba(15,23,42,.55); backdrop-filter: blur(2px);
    }
    .cr-modal .cr-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 460px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: crIn .22s ease;
    }
    @keyframes crIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .cr-modal .cr-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .cr-modal .cr-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .cr-modal .cr-close {
        background: #f3f4f6; border: none; border-radius: 8px;
        width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280;
        transition: background .15s;
    }
    .cr-modal .cr-close:hover { background: #e5e7eb; }
    .cr-modal .cr-body { padding: 16px 20px; }
    .cr-modal .cr-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
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
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:14px;">Mapping baru — langsung aktif dipakai saat import.</div>

        <form method="POST" action="{{ route('courier-rule.store') }}" class="cr-form">
            @csrf

            <div class="cr-field">
                <label>Urutan (prioritas) *</label>
                <input type="number" name="sort_order" class="clay-input" min="1" required
                       value="{{ old('sort_order', $nextOrder) }}">
                <div class="cr-hint">Kecil = dievaluasi lebih dulu (menang).</div>
            </div>

            <div class="cr-field">
                <label>Metode Bayar</label>
                <input type="text" name="payment_method" class="clay-input" list="cr-pm-list"
                       placeholder="kosongkan = semua" value="{{ old('payment_method') }}">
                <datalist id="cr-pm-list">
                    <option value="cod">
                    <option value="bank_transfer">
                </datalist>
                <div class="cr-hint">Contoh: <b>cod</b> atau <b>bank_transfer</b>.</div>
            </div>

            <div class="cr-field">
                <label>Provinsi</label>
                <input type="text" name="province" class="clay-input" list="cr-prov-list"
                       placeholder="kosongkan = semua provinsi" value="{{ old('province') }}">
                <datalist id="cr-prov-list">
                    @foreach($provinces as $p)<option value="{{ $p }}">@endforeach
                </datalist>
                <div class="cr-hint">Tulis nama provinsi (besar kecil bebas, otomatis di-uppercase).</div>
            </div>

            <div class="cr-field">
                <label>Kode Produk (khusus produk)</label>
                <input type="text" name="product_code" class="clay-input" list="cr-code-list"
                       placeholder="kosongkan = semua produk" value="{{ old('product_code') }}">
                <datalist id="cr-code-list">
                    @foreach($productCodes as $pc)<option value="{{ $pc }}">@endforeach
                </datalist>
                <div class="cr-hint">Mis. <b>SH</b> — rule ini menang atas aturan provinsi untuk produk tsb.</div>
            </div>

            <div class="cr-field">
                <label>Courier *</label>
                <select name="courier" class="clay-input" required>
                    <option value="" disabled {{ old('courier') ? '' : 'selected' }}>— pilih courier —</option>
                    @foreach($couriers as $c)
                        <option value="{{ $c }}" @selected(old('courier') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cr-field">
                <label class="cr-check">
                    <input type="checkbox" name="is_active" value="1" checked>
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
            <span>🗂 Daftar Aturan <span style="color:#9ca3af;font-weight:600;font-size:.75rem;">({{ $rules->count() }})</span></span>
            <span style="font-size:.68rem;color:#9ca3af;font-weight:500;">urut dari prioritas tertinggi ↓</span>
        </div>

        <div class="cr-table-wrap">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th style="width:70px;text-align:center;">Urutan</th>
                        <th>Metode Bayar</th>
                        <th>Provinsi</th>
                        <th>Kode Produk</th>
                        <th>Courier</th>
                        <th style="text-align:center;">Status</th>
                        <th style="width:170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                    <tr style="{{ $rule->is_active ? '' : 'opacity:.55;' }}">
                        <td style="text-align:center;font-weight:700;color:#6b7280;">{{ $rule->sort_order }}</td>
                        <td>
                            @if($rule->payment_method)
                                <span class="clay-badge cr-badge-pm">{{ $rule->payment_method }}</span>
                            @else
                                <span class="clay-badge cr-badge-all">Semua</span>
                            @endif
                        </td>
                        <td>
                            @if($rule->province)
                                <span class="clay-badge cr-badge-prov" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;">{{ $rule->province }}</span>
                            @else
                                <span class="clay-badge cr-badge-all">Semua Provinsi</span>
                            @endif
                        </td>
                        <td>
                            @if($rule->product_code)
                                <span class="clay-badge cr-badge-code">{{ $rule->product_code }}</span>
                            @else
                                <span class="clay-badge cr-badge-all">Semua</span>
                            @endif
                        </td>
                        <td>
                            <span class="clay-badge cou-{{ $rule->courier }} cr-badge-cou">{{ $rule->courier }}</span>
                        </td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('courier-rule.toggle', $rule) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="cr-toggle {{ $rule->is_active ? 'on' : 'off' }}"
                                        title="Klik untuk {{ $rule->is_active ? 'menonaktifkan' : 'mengaktifkan' }}">
                                    {{ $rule->is_active ? '● Aktif' : '○ Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="cr-aksi">
                                {{-- Naik/Turun prioritas --}}
                                <form method="POST" action="{{ route('courier-rule.move', [$rule, 'up']) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="cr-move" title="Naikkan prioritas" {{ $loop->first ? 'disabled' : '' }}>↑</button>
                                </form>
                                <form method="POST" action="{{ route('courier-rule.move', [$rule, 'down']) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="cr-move" title="Turunkan prioritas" {{ $loop->last ? 'disabled' : '' }}>↓</button>
                                </form>

                                {{-- Edit --}}
                                <button type="button" class="cr-edit-btn" id="cr-edit-{{ $rule->id }}"
                                        onclick="openCrEdit({{ $rule->id }})"
                                        data-sort="{{ $rule->sort_order }}"
                                        data-payment="{{ $rule->payment_method ?? '' }}"
                                        data-province="{{ $rule->province ?? '' }}"
                                        data-product="{{ $rule->product_code ?? '' }}"
                                        data-courier="{{ $rule->courier }}"
                                        data-active="{{ $rule->is_active ? '1' : '' }}">✏️ Edit</button>

                                {{-- Hapus — label confirm dari data attribute (aman utk teks bebas) --}}
                                <form method="POST" action="{{ route('courier-rule.destroy', $rule) }}" class="cr-del-form"
                                      data-confirm="Hapus aturan {{ $rule->courier }} untuk {{ $rule->province ?? 'semua provinsi' }}{{ $rule->product_code ? ' (produk '.$rule->product_code.')' : '' }}?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="cr-del-btn">🗑 Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:48px;color:#9ca3af;">
                            Belum ada aturan. Tambahkan aturan pertama di form sebelah kiri.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Modal Edit ─────────────────────────────────────────────── --}}
<div class="cr-modal" id="cr-modal" role="dialog" aria-modal="true" aria-labelledby="cr-modal-title">
    <div class="cr-backdrop" onclick="closeCrEdit()"></div>
    <div class="cr-container">
        <div class="cr-header">
            <h2 id="cr-modal-title">✏️ Edit Aturan</h2>
            <button class="cr-close" onclick="closeCrEdit()" type="button">✕</button>
        </div>
        <form method="POST" id="cr-edit-form" class="cr-form">
            @csrf @method('PUT')
            <div class="cr-body">
                <div class="cr-field">
                    <label>Urutan (prioritas) *</label>
                    <input type="number" name="sort_order" id="cr-e-sort" class="clay-input" min="1" required>
                    <div class="cr-hint">Kecil = dievaluasi lebih dulu (menang).</div>
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
            <div class="cr-footer">
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
    var modal = document.getElementById('cr-modal');
    if (!modal) return;

    var form = document.getElementById('cr-edit-form');
    var updateUrl = '{{ route('courier-rule.update', ['courierRule' => '__ID__']) }}';

    window.openCrEdit = function (id) {
        var btn = document.getElementById('cr-edit-' + id);
        if (!btn) return;

        document.getElementById('cr-e-sort').value     = btn.dataset.sort;
        document.getElementById('cr-e-payment').value  = btn.dataset.payment;
        document.getElementById('cr-e-province').value = btn.dataset.province;
        document.getElementById('cr-e-product').value  = btn.dataset.product;
        document.getElementById('cr-e-courier').value  = btn.dataset.courier;
        document.getElementById('cr-e-active').checked = btn.dataset.active === '1';

        form.action = updateUrl.replace('__ID__', id);
        modal.classList.add('active');
    };

    window.closeCrEdit = function () {
        modal.classList.remove('active');
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) window.closeCrEdit();
    });

    // Confirm hapus — label dari data-confirm (nilai di-escape Blade saat render)
    document.querySelectorAll('.cr-del-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.dataset.confirm)) e.preventDefault();
        });
    });
})();
</script>
@endpush
