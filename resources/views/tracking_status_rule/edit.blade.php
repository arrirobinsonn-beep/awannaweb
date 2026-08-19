@extends('layouts.app')

@section('title', 'Aturan Status — '.strtoupper($source))
@section('page-title', '📡 Aturan Status — '.strtoupper($source))
@section('page-subtitle', 'Kolom database tetap (kiri) — pilih header CSV dari file dashboard (kanan) + aturan status mentah → status sistem')

@push('styles')
<style>
    /* ── Header mapping (pola export-mapping) ────── */
    .tsm-hint { font-size: .66rem; color: #9ca3af; margin-top: 3px; line-height: 1.45; }
    .tsm-select {
        padding: 4px 6px; font-size: .74rem; border: 1px solid #d1d5db;
        border-radius: 7px; min-width: 250px; max-width: 100%;
    }
    .tsm-col-no { color: #9ca3af; font-size: .72rem; font-weight: 700; }
    .tsm-header-cell { font-size: .8rem; font-weight: 600; color: #1e1b2e; font-family: monospace; }
    .tsm-draft-tag {
        display: none; font-size: .66rem; font-weight: 700; color: #b45309;
        background: #fef3c7; padding: 2px 8px; border-radius: 999px;
    }
    .tsm-draft-tag.show { display: inline-block; }

    /* ── Aturan status (dari halaman lama) ───────── */
    .ts-info { font-size: .75rem; color: #4b5563; line-height: 1.6; }
    .ts-info b { color: #1e1b2e; }
    .ts-info code { background: #f3f4f6; padding: 1px 6px; border-radius: 5px; font-size: .7rem; color: #6d28d9; font-weight: 700; }
    .ts-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .ts-form .ts-field { margin-bottom: 12px; }
    .ts-form .clay-input { width: 100%; font-size: .8rem; }
    .ts-form .ts-hint { font-size: .66rem; color: #9ca3af; margin-top: 3px; line-height: 1.45; }
    .ts-check { display: flex; align-items: center; gap: 7px; font-size: .78rem; color: #374151; cursor: pointer; font-weight: 600; }
    .ts-check input { width: 16px; height: 16px; accent-color: var(--color-primary, #FF6B6B); cursor: pointer; }
    details.ts-manual summary { cursor: pointer; font-weight: 800; font-size: .82rem; color: #1e1b2e; padding: 10px 2px; user-select: none; }
    details.ts-manual summary:hover { color: var(--color-primary, #FF6B6B); }

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
    .ts-edit-btn { background: none; border: none; color: var(--color-primary, #FF6B6B); font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .ts-edit-btn:hover { text-decoration: underline; }
    .ts-del-form { display: inline; }
    .ts-del-btn { background: none; border: none; color: #dc2626; font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .ts-del-btn:hover { text-decoration: underline; }
    .ts-aksi { display: flex; align-items: center; gap: 5px; white-space: nowrap; }

    /* ── Modal edit ─────────────────────────────── */
    .ts-modal { position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; padding: 16px; }
    .ts-modal.active { display: flex; }
    .ts-modal .ts-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(2px); }
    .ts-modal .ts-container {
        position: relative; background: #fff; border-radius: 18px; width: 100%; max-width: 480px;
        overflow: hidden; display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25); animation: tsIn .22s ease;
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
    .ts-modal .ts-close { background: #f3f4f6; border: none; border-radius: 8px; width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280; transition: background .15s; }
    .ts-modal .ts-close:hover { background: #e5e7eb; }
    .ts-modal .ts-body { padding: 16px 20px; }
    .ts-modal .ts-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06); }

    @media (max-width: 767px) {
        .table-scroll { overflow-x: auto; }
        .table-scroll .clay-table { min-width: 760px; }
    }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;" data-reveal>
    <a href="{{ route('tracking-status-rule.index') }}" class="clay-btn clay-btn-outline" style="padding:6px 14px;font-size:.78rem;" data-page-link>← Semua Dashboard</a>
    <span class="clay-badge" style="background:#e0f2fe;color:#0369a1;font-weight:800;font-size:.72rem;">{{ strtoupper($source) }}</span>
</div>

{{-- ── Format No HP di File (konfigurasi per dashboard) ───────────── --}}
<div class="clay-card" style="padding:14px 18px;margin-bottom:16px;background:linear-gradient(135deg,#FFF7F7,#fff);" data-reveal>
    <form method="POST" action="{{ route('tracking-status-rule.config', $source) }}" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        @csrf
        <div style="font-size:.85rem;font-weight:800;">📱 Format No HP di File</div>
        <select name="phone_format" class="tsm-select" style="min-width:240px;">
            @foreach($phoneFormats as $pf)
                <option value="{{ $pf }}" @selected($phoneFormat === $pf)>
                    @php
                        $label = match ($pf) {
                            'auto' => 'Auto (0/8/62 → 62, otomatis)',
                            '8' => 'Berawalan 8 (SPX) — tambah 62',
                            '0' => 'Berawalan 0 — ganti jadi 62',
                            default => 'Sudah 62 — dipakai apa adanya',
                        };
                    @endphp
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="clay-btn" style="padding:6px 14px;font-size:.76rem;">💾 Simpan</button>
        <div class="tsm-hint" style="font-size:.68rem;">
            Nomor HP di file dashboard biasanya <b>belum berawalan 62</b> (SPX: <code>8123456789</code>).
            Pilih format yang sesuai agar tetap bisa dicocokkan dengan <b>62xxxxxxxxxx</b> di database.
        </div>
    </form>
</div>

{{-- ── Mapping Kolom Database → Header CSV (UI dibalik) ───────────── --}}
<div class="clay-card" style="overflow:hidden;margin-bottom:16px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:14px 20px;border-bottom:1px solid rgba(0,0,0,.06);">
        <div>
            <h2 style="margin:0;font-size:1rem;font-weight:800;">🧩 Mapping Kolom Database → Header CSV</h2>
            <div style="font-size:.72rem;color:#9ca3af;margin-top:2px;">
                Kolom database <b>tetap</b> (tidak berubah) — pilih header CSV dari file dashboard untuk tiap kolom
                <span class="tsm-draft-tag" id="tsm-draft">draft — belum disimpan</span>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="clay-btn" style="padding:6px 14px;font-size:.78rem;"
                    onclick="document.getElementById('tsm-file').click()">⬆ Upload File Dashboard</button>
            <button type="button" class="clay-btn clay-btn-primary" style="padding:6px 14px;font-size:.78rem;" id="tsm-save-btn"
                    onclick="submitTsm()">💾 Simpan Mapping</button>
        </div>
        <input type="file" id="tsm-file" accept=".csv,.txt,.xlsx,.xls" style="display:none;">
    </div>

    <div class="table-scroll">
        <table class="clay-table">
            <thead>
                <tr>
                    <th style="width:56px;text-align:center;">No</th>
                    <th>Kolom Database (tetap)</th>
                    <th style="width:430px;">Header CSV (dari file)</th>
                </tr>
            </thead>
            <tbody id="tsm-body">
                @foreach($columns as $key => $label)
                    <tr class="tsm-row" data-column="{{ $key }}">
                        <td class="tsm-col-no" style="text-align:center;">{{ $loop->iteration }}</td>
                        <td class="tsm-col-cell">{{ $label }}</td>
                        <td>
                            <select class="tsm-select" data-column="{{ $key }}">
                                <option value="">— pilih header —</option>
                                @if(!empty($mapping[$key]))
                                    <option value="{{ $mapping[$key] }}" selected>{{ $mapping[$key] }}</option>
                                @endif
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="padding:10px 20px;border-top:1px solid rgba(0,0,0,.06);" class="tsm-hint">
        <b>Kolom yang didukung:</b> Nomor Resi/AWB, No HP, Nama Pelanggan, Alamat, Nama Produk, Jumlah, Status, Kolom Masalah, Tanggal Terkirim.
        Satu header hanya boleh dipakai untuk <b>satu</b> kolom database. Kolom yang dibiarkan <b>— pilih header —</b> tidak diisi.
        Header yang tidak dipilih tetap dikenali lewat pencocokan otomatis bawaan.
    </div>
</div>

{{-- Form submit mapping (diisi JS saat simpan) --}}
<form method="POST" action="{{ route('tracking-status-rule.mapping', $source) }}" id="tsm-form">
    @csrf
</form>

{{-- ── Aturan Status (raw → sistem) per dashboard ───────────────── --}}
<div class="clay-card" style="overflow:hidden;margin-bottom:16px;" data-reveal>
    <div style="padding:12px 18px;font-weight:800;font-size:.9rem;border-bottom:1px solid rgba(0,0,0,.06);
                display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <span>🗂 Aturan Status <span style="color:#9ca3af;font-weight:600;font-size:.75rem;">({{ $rules->count() }}) — teks status file → status sistem</span></span>
        <span style="font-size:.68rem;color:#9ca3af;font-weight:500;">urut dari prioritas tertinggi ↓</span>
    </div>

    {{-- Info singkat --}}
    <div style="padding:10px 18px;border-bottom:1px solid rgba(0,0,0,.05);background:#FAFAFA;">
        <div class="ts-info">
            Status mentah dicocokkan ke status sistem (<code>waiting_pickup</code>, <code>in_transit</code>, <code>delivered</code>,
            <code>returning</code>, <code>returned</code>, <code>problem</code>) — rule pertama yang cocok menang.
            Mode <b>wajib kolom masalah</b> hanya cocok bila kolom masalah file terpenuhi — FLIK: kolom 3PL <b>diawali</b>
            <code>problem</code> (status normal, masalah di kolom terpisah); SPX: OnHold Reason tidak kosong. Tanpa rule cocok
            → status tak dikenal (tidak diisi).
        </div>
    </div>

    <div class="table-scroll">
        <table class="clay-table">
            <thead>
                <tr>
                    <th style="width:64px;text-align:center;">Urutan</th>
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
                    <td>
                        <span class="clay-badge ts-badge-raw">{{ $rule->raw_status }}</span>
                        @if($rule->match_type === 'contains')
                            <span class="clay-badge ts-badge-type" title="cocok bila status memuat teks ini">~</span>
                        @endif
                    </td>
                    <td><span class="clay-badge ts-badge-st {{ $rule->status }}">{{ $rule->status }}</span></td>
                    <td>
                        @if($rule->problem_mode === 'required')
                            @php
                                $mtype = $rule->problem_match_type ?? 'contains';
                                $cara = $mtype === 'starts_with' ? 'diawali' : 'mengandung';
                            @endphp
                            <span class="clay-badge ts-badge-required" title="kolom masalah {{ $rule->problem_keyword ? $cara.' “'.$rule->problem_keyword.'”' : 'harus terisi' }}">
                                ⚠ {{ $rule->problem_keyword ? $cara.' '.$rule->problem_keyword : 'terisi' }}
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
                            <form method="POST" action="{{ route('tracking-status-rule.move', [$rule, 'up']) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="ts-move" title="Naikkan prioritas" {{ $loop->first ? 'disabled' : '' }}>↑</button>
                            </form>
                            <form method="POST" action="{{ route('tracking-status-rule.move', [$rule, 'down']) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="ts-move" title="Turunkan prioritas" {{ $loop->last ? 'disabled' : '' }}>↓</button>
                            </form>
                            <button type="button" class="ts-edit-btn" id="ts-edit-{{ $rule->id }}"
                                    onclick="openTsEdit({{ $rule->id }})"
                                    data-sort="{{ $rule->sort_order }}"
                                    data-raw="{{ $rule->raw_status }}"
                                    data-match="{{ $rule->match_type }}"
                                    data-status="{{ $rule->status }}"
                                    data-problem="{{ $rule->problem_mode }}"
                                    data-keyword="{{ $rule->problem_keyword ?? '' }}"
                                    data-mtype="{{ $rule->problem_match_type ?? 'contains' }}"
                                    data-active="{{ $rule->is_active ? '1' : '' }}">✏️ Edit</button>
                            <form method="POST" action="{{ route('tracking-status-rule.destroy', $rule) }}" class="ts-del-form"
                                  data-confirm="Hapus aturan {{ $rule->raw_status }} → {{ $rule->status }}?">
                                @csrf @method('DELETE')
                                <button type="submit" class="ts-del-btn">🗑 Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;">
                        Belum ada aturan status untuk {{ strtoupper($source) }}. Tambahkan di form bawah.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Form tambah manual (collapsible) — source terkunci dashboard ini --}}
    <details class="ts-manual" style="padding:4px 18px 16px;border-top:1px solid rgba(0,0,0,.06);">
        <summary>➕ Tambah Aturan Status</summary>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-top:10px;">
            <form method="POST" action="{{ route('tracking-status-rule.store') }}" class="ts-form" id="ts-form">
                @csrf
                <input type="hidden" name="source" value="{{ $source }}">

                <div class="ts-field">
                    <label>Status Mentah (dari file) *</label>
                    <input type="text" name="raw_status" class="clay-input"
                           placeholder="mis. Dikonfirmasi" value="{{ old('raw_status') }}" required>
                    <div class="ts-hint">Teks status di file dashboard {{ strtoupper($source) }} (otomatis di-lowercase).</div>
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
                </div>
                <div class="ts-field" id="ts-keyword-wrap" style="display:none;">
                    <label>Kata Kunci Kolom Masalah</label>
                    <input type="text" name="problem_keyword" class="clay-input" id="ts-problem-keyword"
                           placeholder="kosongkan = cukup terisi" value="{{ old('problem_keyword') }}">
                    <div class="ts-hint">Kosongkan = kolom masalah cukup <b>tidak kosong</b> (SPX OnHold). Isi <code>problem</code> = kolom masalah <b>diawali</b> kata tsb (FLIK 3PL).</div>
                </div>
                <div class="ts-field" id="ts-mtype-wrap" style="display:none;">
                    <label>Cara Cocok Kolom Masalah</label>
                    <select name="problem_match_type" class="clay-input" id="ts-problem-mtype">
                        @foreach($problemMatchTypes as $pmt)
                            <option value="{{ $pmt }}" @selected(old('problem_match_type', 'starts_with') === $pmt)>
                                {{ $pmt === 'starts_with' ? 'Diawali kata kunci' : 'Mengandung kata kunci' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="ts-hint">FLIK: kolom 3PL paket bermasalah <b>diawali</b> "Problem" → pilih <b>Diawali</b>.</div>
                </div>
                <div class="ts-field">
                    <label>Urutan (prioritas) *</label>
                    <input type="number" name="sort_order" class="clay-input" min="1" required
                           value="{{ old('sort_order', $nextOrder) }}">
                    <div class="ts-hint">Kecil = dievaluasi lebih dulu (menang). Aturan bermasalah di <b>atas</b> rule normal utk status yang sama.</div>
                </div>
                <div class="ts-field">
                    <label class="ts-check">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Aktif (dipakai saat evaluasi)
                    </label>
                </div>
                <button type="submit" class="clay-btn clay-btn-primary">+ Tambah Aturan</button>
            </form>
        </div>
    </details>
</div>

{{-- ── Modal Edit aturan status ─────────────────────────────────── --}}
<div class="ts-modal" id="ts-modal" role="dialog" aria-modal="true" aria-labelledby="ts-modal-title">
    <div class="ts-backdrop" onclick="closeTsEdit()"></div>
    <div class="ts-container">
        <div class="ts-header">
            <h2 id="ts-modal-title">✏️ Edit Aturan</h2>
            <button class="ts-close" onclick="closeTsEdit()" type="button">✕</button>
        </div>
        <form method="POST" id="ts-edit-form" class="ts-form">
            @csrf @method('PUT')
            <input type="hidden" name="source" value="{{ $source }}">
            <div class="ts-body">
                <div class="ts-field">
                    <label>Status Mentah (dari file) *</label>
                    <input type="text" name="raw_status" id="ts-e-raw" class="clay-input" required>
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
                    <input type="text" name="problem_keyword" id="ts-e-keyword" class="clay-input" placeholder="kosongkan = cukup terisi">
                </div>
                <div class="ts-field" id="ts-e-mtype-wrap" style="display:none;">
                    <label>Cara Cocok Kolom Masalah</label>
                    <select name="problem_match_type" id="ts-e-mtype" class="clay-input">
                        @foreach($problemMatchTypes as $pmt)
                            <option value="{{ $pmt }}">{{ $pmt === 'starts_with' ? 'Diawali kata kunci' : 'Mengandung kata kunci' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ts-field">
                    <label>Urutan (prioritas) *</label>
                    <input type="number" name="sort_order" id="ts-e-sort" class="clay-input" min="1" required>
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
    'use strict';

    var uploadUrl = '{{ route('tracking-status-rule.upload') }}';
    var tsmForm = document.getElementById('tsm-form');
    var tsmBody = document.getElementById('tsm-body');

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function escAttr(s) {
        return escHtml(s).replace(/"/g, '&quot;');
    }

    function headerOptions(current, headers) {
        var o = '<option value="">— pilih header —</option>';
        (headers || []).forEach(function (h) {
            o += '<option value="' + escAttr(h) + '"' + (h === current ? ' selected' : '') + '>' + escHtml(h) + '</option>';
        });
        return o;
    }

    // ── Upload file dashboard → isi dropdown header tiap kolom DB ──
    var input = document.getElementById('tsm-file');
    if (input) {
        input.addEventListener('change', function () {
            if (!this.files.length) return;
            var fd = new FormData();
            fd.append('file', this.files[0]);
            fd.append('source', '{{ $source }}');

            var btn = document.getElementById('tsm-save-btn');
            var oldText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = '⏳ Membaca…'; }

            fetch(uploadUrl, {
                method: 'POST',
                body: fd,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (!res.ok || !res.j.success) { alert(res.j.message || 'Gagal membaca file.'); return; }

                if (res.j.source !== '{{ $source }}') {
                    alert('File terdeteksi sebagai ' + res.j.source.toUpperCase() + ', bukan ' + '{{ strtoupper($source) }}' + '. Pakai file yang benar.');
                    return;
                }

                var headers = res.j.headers || [];
                var mapping = res.j.mapping || {};

                document.querySelectorAll('#tsm-body tr.tsm-row').forEach(function (tr) {
                    var key = tr.dataset.column;
                    var sel = tr.querySelector('.tsm-select');
                    sel.innerHTML = headerOptions(mapping[key] || '', headers);
                });
                preventDupHeaders();
                document.getElementById('tsm-draft').classList.add('show');
                alert('File dibaca: ' + headers.length + ' header. Pilih header untuk tiap kolom database lalu Simpan Mapping.');
            })
            .catch(function (err) { alert('Gagal upload: ' + err.message); })
            .finally(function () {
                if (btn) { btn.disabled = false; btn.textContent = oldText; }
                input.value = '';
            });
        });
    }

    // Cegah header yang sama dipakai di dua kolom (unique (source, header))
    function preventDupHeaders() {
        var sels = document.querySelectorAll('#tsm-body .tsm-select');
        sels.forEach(function (sel) {
            sel.onchange = function () {
                var chosen = {};
                sels.forEach(function (s) { if (s.value) chosen[s.value] = true; });
                sels.forEach(function (s) {
                    Array.prototype.forEach.call(s.options, function (opt) {
                        opt.disabled = opt.value !== '' && chosen[opt.value] && opt.value !== s.value;
                    });
                });
            };
        });
        sels.forEach(function (sel) { if (sel.onchange) sel.onchange(); });
    }
    preventDupHeaders();

    // ── Simpan mapping → susun items[] per kolom DB lalu submit ──
    window.submitTsm = function () {
        var rows = document.querySelectorAll('#tsm-body tr.tsm-row');
        if (!rows.length) { return; }

        tsmForm.querySelectorAll('input[name^="items["]').forEach(function (el) { el.remove(); });

        rows.forEach(function (tr, i) {
            [['db_column', tr.dataset.column], ['header', tr.querySelector('.tsm-select').value]].forEach(function (p) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'items[' + i + '][' + p[0] + ']';
                inp.value = p[1];
                tsmForm.appendChild(inp);
            });
        });

        tsmForm.submit();
    };

    // ── Form tambah manual: visibilitas kata kunci + cara cocok ──
    function syncKeywordVisibility(select, wrap, keywordInput, mtypeWrap) {
        if (!select || !wrap) return;
        var required = select.value === 'required';
        wrap.style.display = required ? 'block' : 'none';
        if (mtypeWrap) mtypeWrap.style.display = required ? 'block' : 'none';
        if (!required && keywordInput) keywordInput.value = '';
    }
    var addSelect = document.getElementById('ts-problem-mode');
    if (addSelect) {
        addSelect.addEventListener('change', function () {
            syncKeywordVisibility(addSelect, document.getElementById('ts-keyword-wrap'), document.getElementById('ts-problem-keyword'), document.getElementById('ts-mtype-wrap'));
        });
        syncKeywordVisibility(addSelect, document.getElementById('ts-keyword-wrap'), document.getElementById('ts-problem-keyword'), document.getElementById('ts-mtype-wrap'));
    }

    // ── Modal edit ────────────────────────────────────────────
    var modal = document.getElementById('ts-modal');
    if (modal) {
        var form = document.getElementById('ts-edit-form');
        var updateUrl = '{{ route('tracking-status-rule.update', ['trackingStatusRule' => '__ID__']) }}';
        var editSelect = document.getElementById('ts-e-problem');

        window.openTsEdit = function (id) {
            var btn = document.getElementById('ts-edit-' + id);
            if (!btn) return;

            document.getElementById('ts-e-sort').value     = btn.dataset.sort;
            document.getElementById('ts-e-raw').value      = btn.dataset.raw;
            document.getElementById('ts-e-match').value    = btn.dataset.match;
            document.getElementById('ts-e-status').value   = btn.dataset.status;
            document.getElementById('ts-e-problem').value  = btn.dataset.problem;
            document.getElementById('ts-e-keyword').value  = btn.dataset.keyword;
            document.getElementById('ts-e-mtype').value    = btn.dataset.mtype || 'contains';
            document.getElementById('ts-e-active').checked = btn.dataset.active === '1';

            syncKeywordVisibility(editSelect, document.getElementById('ts-e-keyword-wrap'), document.getElementById('ts-e-keyword'), document.getElementById('ts-e-mtype-wrap'));

            form.action = updateUrl.replace('__ID__', id);
            modal.classList.add('active');
        };

        if (editSelect) {
            editSelect.addEventListener('change', function () {
                syncKeywordVisibility(editSelect, document.getElementById('ts-e-keyword-wrap'), document.getElementById('ts-e-keyword'), document.getElementById('ts-e-mtype-wrap'));
            });
        }

        window.closeTsEdit = function () {
            modal.classList.remove('active');
        };

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) window.closeTsEdit();
        });
    }

    // Confirm hapus
    document.querySelectorAll('.ts-del-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.dataset.confirm)) e.preventDefault();
        });
    });
})();
</script>
@endpush
