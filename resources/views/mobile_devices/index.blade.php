@extends('layouts.app')

@section('title', 'Mobile Devices')
@section('page-title', '📱 Mobile Devices')
@section('page-subtitle', 'Manajemen device & token untuk aplikasi mobile')

@push('styles')
<style>
    .md-grid { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 16px; align-items: start; }
    @media (max-width: 1023px) { .md-grid { grid-template-columns: 1fr; } }

    .md-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .md-form .md-field { margin-bottom: 12px; }
    .md-form .clay-input, .md-form select { width: 100%; font-size: .8rem; }

    .md-badge { font-size: .68rem; font-weight: 700; padding: 2px 9px; border-radius: 999px; }
    .md-badge-active  { background: #d1fae5; color: #065f46; }
    .md-badge-revoked { background: #fee2e2; color: #991b1b; }

    .md-toggle {
        border: none; border-radius: 999px; padding: 3px 11px;
        font-size: .68rem; font-weight: 700; cursor: pointer; font-family: inherit;
        transition: all .15s ease;
    }
    .md-toggle.on  { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
    .md-toggle.off { background: #f3f4f6; color: #6b7280; border: 1.5px solid #d1d5db; }

    .md-edit-btn { background: none; border: none; color: var(--color-primary, #FF6B6B); font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .md-edit-btn:hover { text-decoration: underline; }
    .md-del-form { display: inline; }
    .md-del-btn { background: none; border: none; color: #dc2626; font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .md-del-btn:hover { text-decoration: underline; }
    .md-regen-btn { background: none; border: none; color: #d97706; font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .md-regen-btn:hover { text-decoration: underline; }

    /* Modal */
    .md-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .md-modal.active { display: flex; }
    .md-modal .md-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(2px); }
    .md-modal .md-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 420px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: mdIn .22s ease;
    }
    @keyframes mdIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .md-modal .md-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .md-modal .md-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .md-modal .md-close { background: #f3f4f6; border: none; border-radius: 8px; width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280; }
    .md-modal .md-body { padding: 16px 20px; }
    .md-modal .md-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
    }

    /* Token display */
    .md-token-box {
        background: #0f172a; color: #4ade80; font-family: 'Courier New', monospace;
        font-size: .78rem; padding: 14px 18px; border-radius: 12px;
        word-break: break-all; line-height: 1.5;
        display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    }
    .md-token-box .md-token-text { flex: 1; user-select: all; }
    .md-token-box .md-copy-btn {
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
        border-radius: 8px; padding: 4px 10px; color: #4ade80; font-size: .7rem;
        font-weight: 700; cursor: pointer; white-space: nowrap;
    }
    .md-token-box .md-copy-btn:hover { background: rgba(255,255,255,.2); }

    @media (max-width: 479px) {
        .md-table-wrap { overflow-x: auto; }
        .md-table-wrap .clay-table { min-width: 620px; }
    }
</style>
@endpush

@section('content')

<div class="md-grid">

    {{-- ── Form Tambah ─────────────────────────────────────────── --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <h2 style="margin:0 0 4px;font-size:1rem;font-weight:800;">➕ Tambah Device</h2>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:14px;">Buat credential baru untuk aplikasi mobile.</div>

        <form method="POST" action="{{ route('mobile-device.store') }}" class="md-form">
            @csrf

            <div class="md-field">
                <label>Akun *</label>
                <select name="account_id" class="clay-input" required>
                    <option value="">— Pilih Akun —</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->name }}{{ $acc->account_number ? ' ('.$acc->account_number.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md-field">
                <label>Nama Device *</label>
                <input type="text" name="name" class="clay-input" placeholder="mis. HP Asep" value="{{ old('name') }}" required>
            </div>

            <button type="submit" class="clay-btn clay-btn-primary" style="width:100%;">+ Buat Device & Token</button>
        </form>

        <div style="margin-top:14px;padding:10px 14px;background:#fef3c7;border-radius:10px;font-size:.68rem;color:#92400e;line-height:1.5;">
            ⚠️ <b>Token hanya ditampilkan SEKALI</b> saat device dibuat atau di-regenerate. Simpan token di tempat aman.
        </div>
    </div>

    {{-- ── Tabel Devices ──────────────────────────────────────── --}}
    <div class="clay-card" style="overflow:hidden;" data-reveal>
        <div style="padding:12px 18px;font-weight:800;font-size:.9rem;border-bottom:1px solid rgba(0,0,0,.06);
                    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span>📱 Daftar Device <span style="color:#9ca3af;font-weight:600;font-size:.75rem;">({{ $devices->count() }})</span></span>
        </div>

        <div class="md-table-wrap">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Nama Device</th>
                        <th>Akun</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Terakhir Dipakai</th>
                        <th style="text-align:center;">Dibuat</th>
                        <th style="width:180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                    <tr style="{{ $device->status === 'revoked' ? 'opacity:.55;' : '' }}">
                        <td><b style="font-size:.82rem;">{{ $device->name }}</b></td>
                        <td style="font-size:.75rem;color:#6b7280;">{{ $device->account->name ?? '—' }}</td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('mobile-device.toggle', $device) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="md-toggle {{ $device->status === 'active' ? 'on' : 'off' }}"
                                        title="Klik untuk {{ $device->status === 'active' ? 'cabut' : 'aktifkan' }}">
                                    {{ $device->status === 'active' ? '● Aktif' : '○ Dicabut' }}
                                </button>
                            </form>
                        </td>
                        <td style="text-align:center;font-size:.72rem;color:#6b7280;">
                            {{ $device->last_used_at ? $device->last_used_at->diffForHumans() : '—' }}
                        </td>
                        <td style="text-align:center;font-size:.72rem;color:#6b7280;">
                            {{ $device->created_at->format('d M Y') }}
                        </td>
                        <td>
                            <button type="button" class="md-edit-btn" id="md-edit-{{ $device->id }}"
                                    onclick="openMdEdit({{ $device->id }})"
                                    data-name="{{ $device->name }}">✏️ Edit</button>
                            <form method="POST" action="{{ route('mobile-device.regenerate', $device) }}" class="md-del-form"
                                  style="display:inline;">
                                @csrf @method('POST')
                                <button type="submit" class="md-regen-btn"
                                        data-confirm="Regenerate token untuk {{ $device->name }}? Token lama akan berhenti berfungsi.">🔑 Token</button>
                            </form>
                            <form method="POST" action="{{ route('mobile-device.destroy', $device) }}" class="md-del-form"
                                  data-confirm="Hapus device {{ $device->name }}?">
                                @csrf @method('DELETE')
                                <button type="submit" class="md-del-btn">🗑</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:48px;color:#9ca3af;">
                            Belum ada device. Buat device pertama di form sebelah kiri.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Modal Edit ─────────────────────────────────────────────── --}}
<div class="md-modal" id="md-modal" role="dialog" aria-modal="true" aria-labelledby="md-modal-title">
    <div class="md-backdrop" onclick="closeMdEdit()"></div>
    <div class="md-container">
        <div class="md-header">
            <h2 id="md-modal-title">✏️ Edit Device</h2>
            <button class="md-close" onclick="closeMdEdit()" type="button">✕</button>
        </div>
        <form method="POST" id="md-edit-form" class="md-form">
            @csrf @method('PUT')
            <div class="md-body">
                <div class="md-field">
                    <label>Nama Device *</label>
                    <input type="text" name="name" id="md-e-name" class="clay-input" required>
                </div>
            </div>
            <div class="md-footer">
                <button type="button" class="clay-btn clay-btn-outline" onclick="closeMdEdit()">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal Token (setelah create/regenerate) ──────────────── --}}
@if(session('plain_token'))
<div class="md-modal active" id="md-token-modal" role="dialog" aria-modal="true">
    <div class="md-backdrop" onclick="closeTokenModal()"></div>
    <div class="md-container">
        <div class="md-header" style="background:linear-gradient(135deg,#064e3b,#065f46);">
            <h2 style="color:#fff;">🔑 Token Berhasil Dibuat</h2>
            <button class="md-close" onclick="closeTokenModal()" type="button">✕</button>
        </div>
        <div class="md-body">
            <p style="font-size:.78rem;color:#374151;margin:0 0 10px;">
                Simpan token ini di tempat aman. <b style="color:#dc2626;">Token tidak akan ditampilkan lagi.</b>
            </p>
            <div class="md-token-box">
                <span class="md-token-text" id="md-token-value">{{ session('plain_token') }}</span>
                <button class="md-copy-btn" onclick="copyToken()">📋 Salin</button>
            </div>
        </div>
        <div class="md-footer">
            <button type="button" class="clay-btn clay-btn-primary" onclick="closeTokenModal()">✓ Tutup</button>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    // ── Edit modal ──
    var modal = document.getElementById('md-modal');
    if (modal) {
        var form = document.getElementById('md-edit-form');
        var updateUrl = '{{ route("mobile-device.update", ["mobileDevice" => "__ID__"]) }}';

        window.openMdEdit = function (id) {
            var btn = document.getElementById('md-edit-' + id);
            if (!btn) return;
            document.getElementById('md-e-name').value = btn.dataset.name;
            form.action = updateUrl.replace('__ID__', id);
            modal.classList.add('active');
        };

        window.closeMdEdit = function () { modal.classList.remove('active'); };

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (modal.classList.contains('active')) window.closeMdEdit();
                var tm = document.getElementById('md-token-modal');
                if (tm && tm.classList.contains('active')) window.closeTokenModal();
            }
        });
    }

    // ── Token modal ──
    window.closeTokenModal = function () {
        var tm = document.getElementById('md-token-modal');
        if (tm) tm.classList.remove('active');
    };

    window.copyToken = function () {
        var text = document.getElementById('md-token-value');
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text.textContent).then(function () {
                alert('Token berhasil disalin!');
            });
        } else {
            // Fallback
            var range = document.createRange();
            range.selectNodeContents(text);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            document.execCommand('copy');
            sel.removeAllRanges();
            alert('Token berhasil disalin!');
        }
    };

    // ── Delete confirm ──
    document.querySelectorAll('.md-del-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.dataset.confirm)) e.preventDefault();
        });
    });

    // ── Regenerate confirm ──
    document.querySelectorAll('.md-regen-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!window.confirm(btn.dataset.confirm)) e.preventDefault();
        });
    });
})();
</script>
@endpush
