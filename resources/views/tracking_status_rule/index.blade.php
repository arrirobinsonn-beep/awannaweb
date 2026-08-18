@extends('layouts.app')

@section('title', 'Aturan Status Aggregator')
@section('page-title', '📡 Aturan Status Aggregator')
@section('page-subtitle', 'Auto-mapping status dashboard FLIK / SiCepat / SPX → status sistem — dikelola dinamis dari database')

@push('styles')
<style>
    /* ── Layout grid ─────────────────────────────── */
    .ts-grid {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    @media (max-width: 1023px) { .ts-grid { grid-template-columns: 1fr; } }

    /* ── Form tambah ─────────────────────────────── */
    .ts-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .ts-form .ts-field { margin-bottom: 12px; }
    .ts-form .clay-input { width: 100%; font-size: .8rem; }
    .ts-form .ts-hint { font-size: .66rem; color: #9ca3af; margin-top: 3px; line-height: 1.45; }
    .ts-check { display: flex; align-items: center; gap: 7px; font-size: .78rem; color: #374151; cursor: pointer; font-weight: 600; }
    .ts-check input { width: 16px; height: 16px; accent-color: var(--color-primary, #FF6B6B); cursor: pointer; }

    /* ── Tabel ───────────────────────────────────── */
    .ts-badge-src { background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: .68rem; }
    .ts-badge-raw { background: #f3f4f6; color: #374151; font-weight: 700; font-family: monospace; }
    .ts-badge-st  { font-weight: 800; }
    .ts-badge-st.waiting_pickup { background:#fef3c7; color:#92400e; }
    .ts-badge-st.in_transit     { background:#dbeafe; color:#1d4ed8; }
    .ts-badge-st.delivered      { background:#d1fae5; color:#065f46; }
    .ts-badge-st.returning      { background:#ede9fe; color:#6d28d9; }
    .ts-badge-st.returned       { background:#fee2e2; color:#b91c1c; }
    .ts-badge-st.problem        { background:#fecaca; color:#991b1b; }
    .ts-badge-type { background: #fae8ff; color: #86198f; font-weight: 700; font-size: .68rem; }
    .ts-badge-none { background: #f3f4f6; color: #6b7280; font-weight: 600; font-size: .68rem; }
    .ts-badge-required { background: #fef3c7; color: #92400e; font-weight: 700; font-size: .68rem; }

    .ts-toggle {
        border: none; border-radius: 999px; padding: 3px 11px;
        font-size: .68rem; font-weight: 700; cursor: pointer; font-family: inherit;
        transition: all .15s ease;
    }
    .ts-toggle.on  { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
    .ts-toggle.off { background: #f3f4f6; color: #6b7280; border: 1.5px solid #d1d5db; }
    .ts-toggle:hover { transform: translateY(-1px); box-shadow: 0 3px 0 rgba(0,0,0,.08); }

    .ts-move {
        width: 26px; height: 26px; border-radius: 8px; border: 1.5px solid #e5e7eb;
        background: #fff; color: #6b7280; font-size: .72rem; cursor: pointer; line-height: 1;
        transition: all .15s ease;
    }
    .ts-move:hover:not(:disabled) { background: #fff5f5; color: var(--color-primary, #FF6B6B); border-color: #fecaca; }
    .ts-move:disabled { opacity: .3; cursor: not-allowed; }

    .ts-edit-btn {
        background: none; border: none; color: var(--color-primary, #FF6B6B);
        font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px;
    }
    .ts-edit-btn:hover { text-decoration: underline; }
    .ts-del-form { display: inline; }
    .ts-del-btn {
        background: none; border: none; color: #dc2626; font-weight: 700;
        font-size: .76rem; cursor: pointer; padding: 2px 6px;
    }
    .ts-del-btn:hover { text-decoration: underline; }
    .ts-aksi { display: flex; align-items: center; gap: 5px; white-space: nowrap; }

    /* ── Info box ────────────────────────────────── */
    .ts-info { font-size: .75rem; color: #4b5563; line-height: 1.6; }
    .ts-info b { color: #1e1b2e; }
    .ts-info code {
        background: #f3f4f6; padding: 1px 6px; border-radius: 5px;
        font-size: .7rem; color: #6d28d9; font-weight: 700;
    }

    /* ── Modal edit ──────────────────────────────── */
    .ts-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .ts-modal.active { display: flex; }
    .ts-modal .ts-backdrop {
        position: absolute; inset: 0;
        background: rgba(15,23,42,.55); backdrop-filter: blur(2px);
    }
    .ts-modal .ts-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 480px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: tsIn .22s ease;
    }
    @keyframes tsIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .ts-modal .ts-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .ts-modal .ts-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .ts-modal .ts-close {
        background: #f3f4f6; border: none; border-radius: 8px;
        width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280;
        transition: background .15s;
    }
    .ts-modal .ts-close:hover { background: #e5e7eb; }
    .ts-modal .ts-body { padding: 16px 20px; }
    .ts-modal .ts-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
    }

    @media (max-width: 479px) {
        .ts-table-wrap { overflow-x: auto; }
        .ts-table-wrap .clay-table { min-width: 720px; }
    }
</style>
@endpush

@section('content')

{{-- Info cara kerja rules --}}
<div class="clay-card" style="padding:14px 18px;margin-bottom:16px;background:linear-gradient(135deg,#FFF7F7,#fff);" data-reveal>
    <div class="ts-info">
        💡 <b>Cara kerja:</b> status mentah dari file dashboard aggregator dicocokkan ke
        <b>status sistem</b> (<code>waiting_pickup</code>, <code>in_transit</code>, <code>delivered</code>,
        <code>returning</code>, <code>returned</code>, <code>problem</code>) — rule dievaluasi berurutan dari
        <b>Urutan</b> terkecil; rule pertama yang cocok menang. Mode <b>wajib kolom masalah</b> membuat rule
        hanya berlaku bila kolom masalah file terisi (FLIK: kolom 3PL berisi <code>problem</code>; SPX: kolom
        OnHold Reason tidak kosong). Bila tidak ada rule cocok, status dianggap tak dikenal → tidak diisi.
        Perubahan langsung berlaku untuk import status berikutnya — tanpa ubah kode.
    </div>
</div>

<div class="ts-grid">

    {{-- ── Form Tambah ─────────────────────────────────────────── --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <h2 style="margin:0 0 4px;font-size:1rem;font-weight:800;">➕ Tambah Aturan</h2>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:14px;">Mapping status baru — langsung aktif dipakai saat import.</div>

        <form method="POST" action="{{ route('tracking-status-rule.store') }}" class="ts-form" id="ts-form">
            @csrf

            <div class="ts-field">
                <label>Sumber (Aggregator) *</label>
                <select name="source" class="clay-input" required>
                    <option value="" disabled {{ old('source') ? '' : 'selected' }}>— pilih sumber —</option>
                    @foreach($sources as $s)
                        <option value="{{ $s }}" @selected(old('source') === $s)>{{ strtoupper($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ts-field">
                <label>Status Mentah (dari file) *</label>
                <input type="text" name="raw_status" class="clay-input"
                       placeholder="mis. Dikonfirmasi" value="{{ old('raw_status') }}" required>
                <div class="ts-hint">Teks status di file dashboard (besar kecil bebas, otomatis di-lowercase).</div>
            </div>

            <div class="ts-field">
                <label>Cara Cocok</label>
                <select name="match_type" class="clay-input">
                    @foreach($matchTypes as $mt)
                        <option value="{{ $mt }}" @selected(old('match_type', 'exact') === $mt)>
                            {{ $mt === 'exact' ? 'Sama persis' : 'Mengandung kata' }}
                        </option>
                    @endforeach
                </select>
                <div class="ts-hint"><b>sama persis</b> = status file harus sama; <b>mengandung</b> = cukup memuat teks ini.</div>
            </div>

            <div class="ts-field">
                <label>Status Sistem *</label>
                <select name="status" class="clay-input" required>
                    <option value="" disabled {{ old('status') ? '' : 'selected' }}>— pilih status —</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" @selected(old('status') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ts-field">
                <label>Kolom Masalah</label>
                <select name="problem_mode" class="clay-input" id="ts-problem-mode">
                    @foreach($problemModes as $pm)
                        <option value="{{ $pm }}" @selected(old('problem_mode', 'none') === $pm)>
                            {{ $pm === 'none' ? 'Tidak dipakai' : 'Wajib terisi (baru cocok)' }}
                        </option>
                    @endforeach
                </select>
                <div class="ts-hint">Pilih <b>wajib terisi</b> untuk aturan status bermasalah — hanya cocok bila kolom masalah file terpenuhi.</div>
            </div>

            <div class="ts-field" id="ts-keyword-wrap" style="display:none;">
                <label>Kata Kunci Kolom Masalah</label>
                <input type="text" name="problem_keyword" class="clay-input" id="ts-problem-keyword"
                       placeholder="kosongkan = cukup terisi" value="{{ old('problem_keyword') }}">
                <div class="ts-hint">Kosongkan = kolom masalah cukup <b>tidak kosong</b> (SPX OnHold). Isi <code>problem</code> = kolom harus <b>mengandung</b> kata itu (FLIK 3PL).</div>
            </div>

            <div class="ts-field">
                <label>Urutan (prioritas) *</label>
                <input type="number" name="sort_order" class="clay-input" min="1" required
                       value="{{ old('sort_order', $nextOrder) }}">
                <div class="ts-hint">Kecil = dievaluasi lebih dulu (menang). Letakkan aturan bermasalah <b>di atas</b> aturan normal utk status yang sama.</div>
            </div>

            <div class="ts-field">
                <label class="ts-check">
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

        <div class="ts-table-wrap">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th style="width:64px;text-align:center;">Urutan</th>
                        <th>Sumber</th>
                        <th>Status Mentah</th>
                        <th>Status Sistem</th>
                        <th>Masalah</th>
                        <th style="text-align:center;">Status</th>
                        <th style="width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                    <tr style="{{ $rule->is_active ? '' : 'opacity:.55;' }}">
                        <td style="text-align:center;font-weight:700;color:#6b7280;">{{ $rule->sort_order }}</td>
                        <td><span class="clay-badge ts-badge-src">{{ strtoupper($rule->source) }}</span></td>
                        <td>
                            <span class="clay-badge ts-badge-raw">{{ $rule->raw_status }}</span>
                            @if($rule->match_type === 'contains')
                                <span class="clay-badge ts-badge-type" title="cocok bila status memuat teks ini">~</span>
                            @endif
                        </td>
                        <td><span class="clay-badge ts-badge-st {{ $rule->status }}">{{ $rule->status }}</span></td>
                        <td>
                            @if($rule->problem_mode === 'required')
                                <span class="clay-badge ts-badge-required" title="{{ $rule->problem_keyword ? 'kolom masalah harus mengandung: '.$rule->problem_keyword : 'kolom masalah harus terisi' }}">
                                    ⚠ {{ $rule->problem_keyword ?: 'terisi' }}
                                </span>
                            @else
                                <span class="clay-badge ts-badge-none">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('tracking-status-rule.toggle', $rule) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="ts-toggle {{ $rule->is_active ? 'on' : 'off' }}"
                                        title="Klik untuk {{ $rule->is_active ? 'menonaktifkan' : 'mengaktifkan' }}">
                                    {{ $rule->is_active ? '● Aktif' : '○ Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="ts-aksi">
                                {{-- Naik/Turun prioritas --}}
                                <form method="POST" action="{{ route('tracking-status-rule.move', [$rule, 'up']) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="ts-move" title="Naikkan prioritas" {{ $loop->first ? 'disabled' : '' }}>↑</button>
                                </form>
                                <form method="POST" action="{{ route('tracking-status-rule.move', [$rule, 'down']) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="ts-move" title="Turunkan prioritas" {{ $loop->last ? 'disabled' : '' }}>↓</button>
                                </form>

                                {{-- Edit --}}
                                <button type="button" class="ts-edit-btn" id="ts-edit-{{ $rule->id }}"
                                        onclick="openTsEdit({{ $rule->id }})"
                                        data-sort="{{ $rule->sort_order }}"
                                        data-source="{{ $rule->source }}"
                                        data-raw="{{ $rule->raw_status }}"
                                        data-match="{{ $rule->match_type }}"
                                        data-status="{{ $rule->status }}"
                                        data-problem="{{ $rule->problem_mode }}"
                                        data-keyword="{{ $rule->problem_keyword ?? '' }}"
                                        data-active="{{ $rule->is_active ? '1' : '' }}">✏️ Edit</button>

                                {{-- Hapus --}}
                                <form method="POST" action="{{ route('tracking-status-rule.destroy', $rule) }}" class="ts-del-form"
                                      data-confirm="Hapus aturan {{ $rule->raw_status }} → {{ $rule->status }} ({{ strtoupper($rule->source) }})?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ts-del-btn">🗑 Hapus</button>
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
<div class="ts-modal" id="ts-modal" role="dialog" aria-modal="true" aria-labelledby="ts-modal-title">
    <div class="ts-backdrop" onclick="closeTsEdit()"></div>
    <div class="ts-container">
        <div class="ts-header">
            <h2 id="ts-modal-title">✏️ Edit Aturan</h2>
            <button class="ts-close" onclick="closeTsEdit()" type="button">✕</button>
        </div>
        <form method="POST" id="ts-edit-form" class="ts-form">
            @csrf @method('PUT')
            <div class="ts-body">
                <div class="ts-field">
                    <label>Sumber (Aggregator) *</label>
                    <select name="source" id="ts-e-source" class="clay-input" required>
                        @foreach($sources as $s)
                            <option value="{{ $s }}">{{ strtoupper($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ts-field">
                    <label>Status Mentah (dari file) *</label>
                    <input type="text" name="raw_status" id="ts-e-raw" class="clay-input" required>
                    <div class="ts-hint">Teks status di file dashboard (otomatis di-lowercase).</div>
                </div>
                <div class="ts-field">
                    <label>Cara Cocok</label>
                    <select name="match_type" id="ts-e-match" class="clay-input">
                        @foreach($matchTypes as $mt)
                            <option value="{{ $mt }}">{{ $mt === 'exact' ? 'Sama persis' : 'Mengandung kata' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ts-field">
                    <label>Status Sistem *</label>
                    <select name="status" id="ts-e-status" class="clay-input" required>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ts-field">
                    <label>Kolom Masalah</label>
                    <select name="problem_mode" id="ts-e-problem" class="clay-input">
                        @foreach($problemModes as $pm)
                            <option value="{{ $pm }}">{{ $pm === 'none' ? 'Tidak dipakai' : 'Wajib terisi (baru cocok)' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ts-field" id="ts-e-keyword-wrap" style="display:none;">
                    <label>Kata Kunci Kolom Masalah</label>
                    <input type="text" name="problem_keyword" id="ts-e-keyword" class="clay-input"
                           placeholder="kosongkan = cukup terisi">
                    <div class="ts-hint">Kosongkan = kolom masalah cukup tidak kosong; isi <code>problem</code> = harus mengandung.</div>
                </div>
                <div class="ts-field">
                    <label>Urutan (prioritas) *</label>
                    <input type="number" name="sort_order" id="ts-e-sort" class="clay-input" min="1" required>
                    <div class="ts-hint">Kecil = dievaluasi lebih dulu (menang).</div>
                </div>
                <div class="ts-field">
                    <label class="ts-check">
                        <input type="checkbox" name="is_active" id="ts-e-active" value="1">
                        Aktif (dipakai saat evaluasi)
                    </label>
                </div>
            </div>
            <div class="ts-footer">
                <button type="button" class="clay-btn clay-btn-outline" onclick="closeTsEdit()">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    // Tampilkan/sembunyikan input kata kunci sesuai mode masalah (form tambah)
    function syncKeywordVisibility(select, wrap, keywordInput) {
        if (!select || !wrap) return;
        var required = select.value === 'required';
        wrap.style.display = required ? 'block' : 'none';
        if (!required && keywordInput) keywordInput.value = '';
    }

    var addSelect = document.getElementById('ts-problem-mode');
    if (addSelect) {
        addSelect.addEventListener('change', function () {
            syncKeywordVisibility(addSelect, document.getElementById('ts-keyword-wrap'), document.getElementById('ts-problem-keyword'));
        });
    }

    var modal = document.getElementById('ts-modal');
    if (!modal) return;

    var form = document.getElementById('ts-edit-form');
    var updateUrl = '{{ route('tracking-status-rule.update', ['trackingStatusRule' => '__ID__']) }}';
    var editSelect = document.getElementById('ts-e-problem');

    window.openTsEdit = function (id) {
        var btn = document.getElementById('ts-edit-' + id);
        if (!btn) return;

        document.getElementById('ts-e-sort').value     = btn.dataset.sort;
        document.getElementById('ts-e-source').value   = btn.dataset.source;
        document.getElementById('ts-e-raw').value      = btn.dataset.raw;
        document.getElementById('ts-e-match').value    = btn.dataset.match;
        document.getElementById('ts-e-status').value   = btn.dataset.status;
        document.getElementById('ts-e-problem').value  = btn.dataset.problem;
        document.getElementById('ts-e-keyword').value  = btn.dataset.keyword;
        document.getElementById('ts-e-active').checked = btn.dataset.active === '1';

        syncKeywordVisibility(editSelect, document.getElementById('ts-e-keyword-wrap'), document.getElementById('ts-e-keyword'));

        form.action = updateUrl.replace('__ID__', id);
        modal.classList.add('active');
    };

    if (editSelect) {
        editSelect.addEventListener('change', function () {
            syncKeywordVisibility(editSelect, document.getElementById('ts-e-keyword-wrap'), document.getElementById('ts-e-keyword'));
        });
    }

    window.closeTsEdit = function () {
        modal.classList.remove('active');
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) window.closeTsEdit();
    });

    // Confirm hapus — label dari data-confirm (nilai di-escape Blade saat render)
    document.querySelectorAll('.ts-del-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.dataset.confirm)) e.preventDefault();
        });
    });
})();
</script>
@endpush
