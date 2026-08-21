@extends('layouts.app')

@section('title', 'Akun Keuangan')
@section('page-title', '💼 Akun Keuangan')
@section('page-subtitle', 'Sumber uang perusahaan — rekening bank, cash, aggregator, e-wallet')

@push('styles')
<style>
    .fn-grid { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 16px; align-items: start; }
    @media (max-width: 1023px) { .fn-grid { grid-template-columns: 1fr; } }

    .fn-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .fn-form .fn-field { margin-bottom: 12px; }
    .fn-form .clay-input, .fn-form select { width: 100%; font-size: .8rem; }

    .fn-total {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 14px 18px; border-radius: 16px; margin-bottom: 16px;
        background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff;
    }
    .fn-total .fn-total-label { font-size: .72rem; opacity: .7; font-weight: 600; }
    .fn-total .fn-total-value { font-size: 1.25rem; font-weight: 800; }

    .fn-badge-type { font-size: .68rem; font-weight: 700; padding: 2px 9px; border-radius: 999px; }
    .fn-badge-bank { background: #dbeafe; color: #1d4ed8; }
    .fn-badge-cash { background: #dcfce7; color: #15803d; }
    .fn-badge-aggregator { background: #fef3c7; color: #b45309; }
    .fn-badge-ewallet { background: #fae8ff; color: #86198f; }
    .fn-badge-other { background: #f3f4f6; color: #4b5563; }

    .fn-toggle {
        border: none; border-radius: 999px; padding: 3px 11px;
        font-size: .68rem; font-weight: 700; cursor: pointer; font-family: inherit;
        transition: all .15s ease;
    }
    .fn-toggle.on  { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
    .fn-toggle.off { background: #f3f4f6; color: #6b7280; border: 1.5px solid #d1d5db; }

    .fn-edit-btn { background: none; border: none; color: var(--color-primary, #FF6B6B); font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .fn-edit-btn:hover { text-decoration: underline; }
    .fn-del-form { display: inline; }
    .fn-del-btn { background: none; border: none; color: #dc2626; font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .fn-del-btn:hover { text-decoration: underline; }

    /* Modal edit (pola cr-modal) */
    .fn-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .fn-modal.active { display: flex; }
    .fn-modal .fn-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(2px); }
    .fn-modal .fn-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 420px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: fnIn .22s ease;
    }
    @keyframes fnIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .fn-modal .fn-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .fn-modal .fn-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .fn-modal .fn-close { background: #f3f4f6; border: none; border-radius: 8px; width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280; }
    .fn-modal .fn-body { padding: 16px 20px; }
    .fn-modal .fn-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
    }
    @media (max-width: 479px) {
        .fn-table-wrap { overflow-x: auto; }
        .fn-table-wrap .clay-table { min-width: 620px; }
    }
</style>
@endpush

@section('content')

<div class="fn-total" data-reveal>
    <div>
        <div class="fn-total-label">💰 TOTAL SALDO SEMUA AKUN</div>
        <div class="fn-total-value">Rp {{ number_format($totalBalance, 0, ',', '.') }}</div>
    </div>
    <div style="font-size:.7rem;opacity:.6;text-align:right;">
        {{ $accounts->count() }} akun terdaftar
    </div>
</div>

<div class="fn-grid">

    {{-- ── Form Tambah ─────────────────────────────────────────── --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <h2 style="margin:0 0 4px;font-size:1rem;font-weight:800;">➕ Tambah Akun</h2>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:14px;">Rekening / cash / aggregator / e-wallet.</div>

        <form method="POST" action="{{ route('finance.accounts.store') }}" class="fn-form">
            @csrf

            <div class="fn-field">
                <label>Nama Akun *</label>
                <input type="text" name="name" class="clay-input" placeholder="mis. BCA ASEP" value="{{ old('name') }}" required>
            </div>

            <div class="fn-field">
                <label>No. Rekening <span style="color:#9ca3af;font-weight:600;">(opsional)</span></label>
                <input type="text" name="account_number" class="clay-input" maxlength="100"
                       placeholder="mis. 1234567890" value="{{ old('account_number') }}">
            </div>

            <div class="fn-field">
                <label>Jenis *</label>
                <select name="type" class="clay-input" required>
                    @foreach(\App\Models\Account::TYPE_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fn-field">
                <label>Saldo Awal (Rp) *</label>
                <input type="number" name="current_balance" class="clay-input" step="0.01" min="0"
                       placeholder="0" value="{{ old('current_balance', 0) }}" required>
                <div style="font-size:.66rem;color:#9ca3af;margin-top:3px;">Saldo awal saat akun dibuat.</div>
            </div>

            <button type="submit" class="clay-btn clay-btn-primary" style="width:100%;">+ Tambah Akun</button>
        </form>
    </div>

    {{-- ── Tabel Akun ──────────────────────────────────────────── --}}
    <div class="clay-card" style="overflow:hidden;" data-reveal>
        <div style="padding:12px 18px;font-weight:800;font-size:.9rem;border-bottom:1px solid rgba(0,0,0,.06);
                    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span>🗂 Daftar Akun <span style="color:#9ca3af;font-weight:600;font-size:.75rem;">({{ $accounts->count() }})</span></span>
        </div>

        <div class="fn-table-wrap">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Nama Akun</th>
                        <th>No. Rekening</th>
                        <th>Jenis</th>
                        <th style="text-align:right;">Saldo</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Transaksi</th>
                        <th style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                    <tr style="{{ $account->status === 'inactive' ? 'opacity:.55;' : '' }}">
                        <td><b style="font-size:.82rem;">{{ $account->name }}</b></td>
                        <td style="font-size:.75rem;color:#6b7280;">{{ $account->account_number ?: '—' }}</td>
                        <td><span class="clay-badge fn-badge-type fn-badge-{{ $account->type }}">{{ $account->type_label }}</span></td>
                        <td style="text-align:right;font-weight:700;">Rp {{ number_format((float) $account->current_balance, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('finance.accounts.toggle', $account) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="fn-toggle {{ $account->status === 'active' ? 'on' : 'off' }}"
                                        title="Klik untuk {{ $account->status === 'active' ? 'menonaktifkan' : 'mengaktifkan' }}">
                                    {{ $account->status === 'active' ? '● Aktif' : '○ Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td style="text-align:center;font-size:.72rem;color:#6b7280;">
                            {{ $account->bank_transfers_count + $account->transfers_from_count + $account->transfers_to_count }}
                        </td>
                        <td>
                            <button type="button" class="fn-edit-btn" id="fn-edit-{{ $account->id }}"
                                    onclick="openFnEdit({{ $account->id }})"
                                    data-name="{{ $account->name }}"
                                    data-account-number="{{ $account->account_number }}"
                                    data-type="{{ $account->type }}"
                                    data-balance="{{ (float) $account->current_balance }}"
                                    data-status="{{ $account->status }}">✏️ Edit</button>
                            <form method="POST" action="{{ route('finance.accounts.destroy', $account) }}" class="fn-del-form"
                                  data-confirm="Hapus akun {{ $account->name }}?">
                                @csrf @method('DELETE')
                                <button type="submit" class="fn-del-btn">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:48px;color:#9ca3af;">
                            Belum ada akun. Tambahkan akun pertama di form sebelah kiri.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Modal Edit ─────────────────────────────────────────────── --}}
<div class="fn-modal" id="fn-modal" role="dialog" aria-modal="true" aria-labelledby="fn-modal-title">
    <div class="fn-backdrop" onclick="closeFnEdit()"></div>
    <div class="fn-container">
        <div class="fn-header">
            <h2 id="fn-modal-title">✏️ Edit Akun</h2>
            <button class="fn-close" onclick="closeFnEdit()" type="button">✕</button>
        </div>
        <form method="POST" id="fn-edit-form" class="fn-form">
            @csrf @method('PUT')
            <div class="fn-body">
                <div class="fn-field">
                    <label>Nama Akun *</label>
                    <input type="text" name="name" id="fn-e-name" class="clay-input" required>
                </div>
                <div class="fn-field">
                    <label>No. Rekening <span style="color:#9ca3af;font-weight:600;">(opsional)</span></label>
                    <input type="text" name="account_number" id="fn-e-account-number" class="clay-input" maxlength="100" placeholder="mis. 1234567890">
                </div>
                <div class="fn-field">
                    <label>Jenis *</label>
                    <select name="type" id="fn-e-type" class="clay-input" required>
                        @foreach(\App\Models\Account::TYPE_LABELS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fn-field">
                    <label>Saldo (Rp) *</label>
                    <input type="number" name="current_balance" id="fn-e-balance" class="clay-input" step="0.01" min="0" required>
                    <div style="font-size:.66rem;color:#9ca3af;margin-top:3px;">Saldo otomatis berubah dari transaksi — edit hanya untuk koreksi.</div>
                </div>
                <div class="fn-field">
                    <label class="fn-check" style="display:flex;align-items:center;gap:7px;font-size:.78rem;color:#374151;font-weight:600;">
                        <input type="checkbox" name="status" id="fn-e-active" value="active"
                               style="width:16px;height:16px;accent-color:var(--color-primary,#FF6B6B);">
                        Aktif
                    </label>
                </div>
            </div>
            <div class="fn-footer">
                <button type="button" class="clay-btn clay-btn-outline" onclick="closeFnEdit()">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('fn-modal');
    if (!modal) return;

    var form = document.getElementById('fn-edit-form');
    var updateUrl = '{{ route('finance.accounts.update', ['account' => '__ID__']) }}';

    window.openFnEdit = function (id) {
        var btn = document.getElementById('fn-edit-' + id);
        if (!btn) return;

        document.getElementById('fn-e-name').value = btn.dataset.name;
        document.getElementById('fn-e-account-number').value = btn.dataset.accountNumber || '';
        document.getElementById('fn-e-type').value = btn.dataset.type;
        document.getElementById('fn-e-balance').value = btn.dataset.balance;
        document.getElementById('fn-e-active').checked = btn.dataset.status === 'active';

        form.action = updateUrl.replace('__ID__', id);
        modal.classList.add('active');
    };

    window.closeFnEdit = function () {
        modal.classList.remove('active');
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) window.closeFnEdit();
    });

    document.querySelectorAll('.fn-del-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.dataset.confirm)) e.preventDefault();
        });
    });
})();
</script>
@endpush