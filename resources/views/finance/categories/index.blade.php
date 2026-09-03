@extends('layouts.app')

@section('title', 'Kategori Transaksi')
@section('page-title', '🏷 Kategori Transaksi')
@section('page-subtitle', 'Jenis transaksi bank transfer — mis. "Bank Transfer" (masuk), "Biaya" (keluar)')

@push('styles')
<style>
    .ct-grid { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 16px; align-items: start; }
    @media (max-width: 1023px) { .ct-grid { grid-template-columns: 1fr; } }

    .ct-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .ct-form .ct-field { margin-bottom: 12px; }
    .ct-form .clay-input, .ct-form select { width: 100%; font-size: .8rem; }

    .ct-badge { font-size: .68rem; font-weight: 700; padding: 2px 9px; border-radius: 999px; }
    .ct-badge-in  { background: #dcfce7; color: #15803d; }
    .ct-badge-out { background: #fee2e2; color: #b91c1c; }

    .ct-edit-btn { background: none; border: none; color: var(--color-primary, #FF6B6B); font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .ct-edit-btn:hover { text-decoration: underline; }
    .ct-del-form { display: inline; }
    .ct-del-btn { background: none; border: none; color: #dc2626; font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .ct-del-btn:hover { text-decoration: underline; }

    .ct-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .ct-modal.active { display: flex; }
    .ct-modal .ct-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(2px); }
    .ct-modal .ct-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 400px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: ctIn .22s ease;
    }
    @keyframes ctIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .ct-modal .ct-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .ct-modal .ct-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .ct-modal .ct-close { background: #f3f4f6; border: none; border-radius: 8px; width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280; }
    .ct-modal .ct-body { padding: 16px 20px; }
    .ct-modal .ct-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
    }
</style>
@endpush

@section('content')

<div class="ct-grid">

    {{-- ── Form Tambah ─────────────────────────────────────────── --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <h2 style="margin:0 0 4px;font-size:1rem;font-weight:800;">➕ Tambah Kategori</h2>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:14px;">Kategori transaksi masuk / keluar.</div>

        <form method="POST" action="{{ route('finance.categories.store') }}" class="ct-form">
            @csrf

            <div class="ct-field">
                <label>Nama Kategori *</label>
                <input type="text" name="name" class="clay-input" placeholder="mis. Bank Transfer" value="{{ old('name') }}" required>
            </div>

            <div class="ct-field">
                <label>Tipe *</label>
                <select name="type" class="clay-input" required>
                    @foreach(\App\Models\TransactionCategory::TYPE_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div style="font-size:.66rem;color:#9ca3af;margin-top:3px;">Masuk = uang masuk akun, Keluar = biaya/pengeluaran.</div>
            </div>

            <button type="submit" class="clay-btn clay-btn-primary" style="width:100%;">+ Tambah Kategori</button>
        </form>
    </div>

    {{-- ── Tabel Kategori ──────────────────────────────────────── --}}
    <div class="clay-card" style="overflow:hidden;" data-reveal>
        <div style="padding:12px 18px;font-weight:800;font-size:.9rem;border-bottom:1px solid rgba(0,0,0,.06);
                    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span>🗂 Daftar Kategori <span style="color:#9ca3af;font-weight:600;font-size:.75rem;">({{ $categories->count() }})</span></span>
        </div>

        <div style="overflow-x:auto;">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th style="text-align:center;">Dipakai Transaksi</th>
                        <th style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td><b style="font-size:.82rem;">{{ $category->name }}</b></td>
                        <td><span class="clay-badge ct-badge ct-badge-{{ $category->type }}">{{ $category->type_label }}</span></td>
                        <td style="text-align:center;font-size:.72rem;color:#6b7280;">{{ $category->bank_transfers_count }}</td>
                        <td>
                            <button type="button" class="ct-edit-btn" id="ct-edit-{{ $category->id }}"
                                    onclick="openCtEdit({{ $category->id }})"
                                    data-name="{{ $category->name }}"
                                    data-type="{{ $category->type }}">✏️ Edit</button>
                            <form method="POST" action="{{ route('finance.categories.destroy', $category) }}" class="ct-del-form"
                                  data-confirm="Hapus kategori {{ $category->name }}?">
                                @csrf @method('DELETE')
                                <button type="submit" class="ct-del-btn">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:48px;color:#9ca3af;">
                            Belum ada kategori. Tambahkan kategori pertama di form sebelah kiri.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Modal Edit ─────────────────────────────────────────────── --}}
<div class="ct-modal" id="ct-modal" role="dialog" aria-modal="true" aria-labelledby="ct-modal-title">
    <div class="ct-backdrop" onclick="closeCtEdit()"></div>
    <div class="ct-container">
        <div class="ct-header">
            <h2 id="ct-modal-title">✏️ Edit Kategori</h2>
            <button class="ct-close" onclick="closeCtEdit()" type="button">✕</button>
        </div>
        <form method="POST" id="ct-edit-form" class="ct-form">
            @csrf @method('PUT')
            <div class="ct-body">
                <div class="ct-field">
                    <label>Nama Kategori *</label>
                    <input type="text" name="name" id="ct-e-name" class="clay-input" required>
                </div>
                <div class="ct-field">
                    <label>Tipe *</label>
                    <select name="type" id="ct-e-type" class="clay-input" required>
                        @foreach(\App\Models\TransactionCategory::TYPE_LABELS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="ct-footer">
                <button type="button" class="clay-btn clay-btn-outline" onclick="closeCtEdit()">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('ct-modal');
    if (!modal) return;

    var form = document.getElementById('ct-edit-form');
    var updateUrl = '{{ route('finance.categories.update', ['category' => '__ID__']) }}';

    window.openCtEdit = function (id) {
        var btn = document.getElementById('ct-edit-' + id);
        if (!btn) return;

        document.getElementById('ct-e-name').value = btn.dataset.name;
        document.getElementById('ct-e-type').value = btn.dataset.type;

        form.action = updateUrl.replace('__ID__', id);
        modal.classList.add('active');
    };

    window.closeCtEdit = function () {
        modal.classList.remove('active');
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) window.closeCtEdit();
    });

    document.querySelectorAll('.ct-del-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.dataset.confirm)) e.preventDefault();
        });
    });
})();
</script>
@endpush