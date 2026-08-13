@extends('layouts.app')

@section('title', 'Aturan Gudang')
@section('page-title', '🏬 Aturan Gudang')
@section('page-subtitle', 'Auto-mapping kode produk → gudang (nama pengirim) saat export — dikelola dinamis dari database')

@push('styles')
<style>
    .wr-grid {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    @media (max-width: 1023px) { .wr-grid { grid-template-columns: 1fr; } }

    .wr-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .wr-form .wr-field { margin-bottom: 12px; }
    .wr-form .clay-input { width: 100%; font-size: .8rem; }
    .wr-form .wr-hint { font-size: .66rem; color: #9ca3af; margin-top: 3px; line-height: 1.45; }
    .wr-check { display: flex; align-items: center; gap: 7px; font-size: .78rem; color: #374151; cursor: pointer; font-weight: 600; }
    .wr-check input { width: 16px; height: 16px; accent-color: var(--color-primary, #FF6B6B); cursor: pointer; }

    .wr-badge-code { background: #fae8ff; color: #86198f; font-weight: 700; font-family: monospace; }
    .wr-badge-wh  { background: #dbeafe; color: #1d4ed8; font-weight: 700; }

    .wr-toggle {
        border: none; border-radius: 999px; padding: 3px 11px;
        font-size: .68rem; font-weight: 700; cursor: pointer; font-family: inherit;
        transition: all .15s ease;
    }
    .wr-toggle.on  { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
    .wr-toggle.off { background: #f3f4f6; color: #6b7280; border: 1.5px solid #d1d5db; }
    .wr-toggle:hover { transform: translateY(-1px); box-shadow: 0 3px 0 rgba(0,0,0,.08); }

    .wr-edit-btn {
        background: none; border: none; color: var(--color-primary, #FF6B6B);
        font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px;
    }
    .wr-edit-btn:hover { text-decoration: underline; }
    .wr-del-form { display: inline; }
    .wr-del-btn {
        background: none; border: none; color: #dc2626; font-weight: 700;
        font-size: .76rem; cursor: pointer; padding: 2px 6px;
    }
    .wr-del-btn:hover { text-decoration: underline; }

    .wr-info { font-size: .75rem; color: #4b5563; line-height: 1.6; }
    .wr-info b { color: #1e1b2e; }
    .wr-info code {
        background: #f3f4f6; padding: 1px 6px; border-radius: 5px;
        font-size: .7rem; color: #6d28d9; font-weight: 700;
    }

    /* ── Modal edit (pola cr-modal) ──────────────── */
    .wr-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .wr-modal.active { display: flex; }
    .wr-modal .wr-backdrop {
        position: absolute; inset: 0;
        background: rgba(15,23,42,.55); backdrop-filter: blur(2px);
    }
    .wr-modal .wr-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 420px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: wrIn .22s ease;
    }
    @keyframes wrIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .wr-modal .wr-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .wr-modal .wr-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .wr-modal .wr-close {
        background: #f3f4f6; border: none; border-radius: 8px;
        width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280;
        transition: background .15s;
    }
    .wr-modal .wr-close:hover { background: #e5e7eb; }
    .wr-modal .wr-body { padding: 16px 20px; }
    .wr-modal .wr-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
    }

    @media (max-width: 479px) {
        .wr-table-wrap { overflow-x: auto; }
        .wr-table-wrap .clay-table { min-width: 560px; }
    }
</style>
@endpush

@section('content')

{{-- Info cara kerja rules --}}
<div class="clay-card" style="padding:14px 18px;margin-bottom:16px;background:linear-gradient(135deg,#FFF7F7,#fff);" data-reveal>
    <div class="wr-info">
        💡 <b>Cara kerja:</b> kolom <b>Kode Warehouse</b> pada export template diisi mengikuti
        <b>Kode Produk</b> order. Rule di halaman ini menang atas gudang utama produk
        (pivot <code>is_primary</code>); tanpa rule, produk memakai gudang utamanya lalu nama
        pengirim (sender). Contoh bawaan: <code>SH</code> → <b>GTM</b>, <code>KSP</code> → <b>Aurora</b>.
        Perubahan langsung berlaku untuk export berikutnya — tanpa ubah kode.
    </div>
</div>

<div class="wr-grid">

    {{-- ── Form Tambah ─────────────────────────────────────────── --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <h2 style="margin:0 0 4px;font-size:1rem;font-weight:800;">➕ Tambah Aturan</h2>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:14px;">Mapping kode produk → gudang baru.</div>

        <form method="POST" action="{{ route('warehouse-rule.store') }}" class="wr-form">
            @csrf

            <div class="wr-field">
                <label>Kode Produk *</label>
                <input type="text" name="product_code" class="clay-input" list="wr-code-list"
                       placeholder="mis. SH" value="{{ old('product_code') }}" required>
                <datalist id="wr-code-list">
                    @foreach($productCodes ?? [] as $pc)<option value="{{ $pc }}">@endforeach
                </datalist>
                <div class="wr-hint">Kode master (tanpa varian). Otomatis di-uppercase.</div>
            </div>

            <div class="wr-field">
                <label>Gudang (nama pengirim) *</label>
                <input type="text" name="warehouse" class="clay-input" list="wr-wh-list"
                       placeholder="mis. GTM" value="{{ old('warehouse') }}" required>
                <datalist id="wr-wh-list">
                    @foreach($inventories as $inv)<option value="{{ $inv->name }}">@endforeach
                </datalist>
                <div class="wr-hint">Nama yang muncul di kolom "Kode Warehouse" saat export.</div>
            </div>

            <div class="wr-field">
                <label class="wr-check">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Aktif (dipakai saat export)
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
        </div>

        <div class="wr-table-wrap">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Kode Produk</th>
                        <th>Gudang</th>
                        <th style="text-align:center;">Status</th>
                        <th style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                    <tr style="{{ $rule->is_active ? '' : 'opacity:.55;' }}">
                        <td><span class="clay-badge wr-badge-code">{{ $rule->product_code }}</span></td>
                        <td><span class="clay-badge wr-badge-wh">{{ $rule->warehouse }}</span></td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('warehouse-rule.toggle', $rule) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="wr-toggle {{ $rule->is_active ? 'on' : 'off' }}"
                                        title="Klik untuk {{ $rule->is_active ? 'menonaktifkan' : 'mengaktifkan' }}">
                                    {{ $rule->is_active ? '● Aktif' : '○ Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <button type="button" class="wr-edit-btn" id="wr-edit-{{ $rule->id }}"
                                    onclick="openWrEdit({{ $rule->id }})"
                                    data-code="{{ $rule->product_code }}"
                                    data-warehouse="{{ $rule->warehouse }}"
                                    data-active="{{ $rule->is_active ? '1' : '' }}">✏️ Edit</button>
                            <form method="POST" action="{{ route('warehouse-rule.destroy', $rule) }}" class="wr-del-form"
                                  data-confirm="Hapus aturan {{ $rule->product_code }} → {{ $rule->warehouse }}?">
                                @csrf @method('DELETE')
                                <button type="submit" class="wr-del-btn">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:48px;color:#9ca3af;">
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
<div class="wr-modal" id="wr-modal" role="dialog" aria-modal="true" aria-labelledby="wr-modal-title">
    <div class="wr-backdrop" onclick="closeWrEdit()"></div>
    <div class="wr-container">
        <div class="wr-header">
            <h2 id="wr-modal-title">✏️ Edit Aturan</h2>
            <button class="wr-close" onclick="closeWrEdit()" type="button">✕</button>
        </div>
        <form method="POST" id="wr-edit-form" class="wr-form">
            @csrf @method('PUT')
            <div class="wr-body">
                <div class="wr-field">
                    <label>Kode Produk *</label>
                    <input type="text" name="product_code" id="wr-e-code" class="clay-input" list="wr-code-list"
                           placeholder="mis. SH" required>
                    <div class="wr-hint">Kode master (tanpa varian). Otomatis di-uppercase.</div>
                </div>
                <div class="wr-field">
                    <label>Gudang (nama pengirim) *</label>
                    <input type="text" name="warehouse" id="wr-e-warehouse" class="clay-input" list="wr-wh-list"
                           placeholder="mis. GTM" required>
                    <div class="wr-hint">Nama di kolom "Kode Warehouse" saat export.</div>
                </div>
                <div class="wr-field">
                    <label class="wr-check">
                        <input type="checkbox" name="is_active" id="wr-e-active" value="1">
                        Aktif (dipakai saat export)
                    </label>
                </div>
            </div>
            <div class="wr-footer">
                <button type="button" class="clay-btn clay-btn-outline" onclick="closeWrEdit()">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('wr-modal');
    if (!modal) return;

    var form = document.getElementById('wr-edit-form');
    var updateUrl = '{{ route('warehouse-rule.update', ['warehouseRule' => '__ID__']) }}';

    window.openWrEdit = function (id) {
        var btn = document.getElementById('wr-edit-' + id);
        if (!btn) return;

        document.getElementById('wr-e-code').value      = btn.dataset.code;
        document.getElementById('wr-e-warehouse').value = btn.dataset.warehouse;
        document.getElementById('wr-e-active').checked  = btn.dataset.active === '1';

        form.action = updateUrl.replace('__ID__', id);
        modal.classList.add('active');
    };

    window.closeWrEdit = function () {
        modal.classList.remove('active');
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) window.closeWrEdit();
    });

    document.querySelectorAll('.wr-del-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.dataset.confirm)) e.preventDefault();
        });
    });
})();
</script>
@endpush
