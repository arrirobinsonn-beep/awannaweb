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

    /* Modal edit — styles centralized in clay.css (clay-modal) */

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

    <div class="table-scroll" id="ts-rules-wrap">
        @include('tracking_status_rule._rules_table')
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
                    <input type="text" name="raw_status" id="ts-add-raw" class="clay-input"
                           placeholder="mis. Dikonfirmasi" required>
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
                    <label class="ts-check">
                        <input type="checkbox" name="is_active" id="ts-add-active" value="1" checked>
                        Aktif (dipakai saat evaluasi)
                    </label>
                </div>
                <button type="submit" class="clay-btn clay-btn-primary">+ Tambah Aturan</button>
                <div class="ts-hint" style="margin-top:6px;">Urutan otomatis di paling bawah. Gunakan tombol ↑↓ untuk menyesuaikan.</div>
            </form>
        </div>
    </details>
</div>

{{-- ── Modal Edit aturan status ─────────────────────────────────── --}}
<div class="clay-modal" id="ts-modal" role="dialog" aria-modal="true" aria-labelledby="ts-modal-title">
    <div class="clay-modal-backdrop" onclick="closeTsEdit()"></div>
    <div class="clay-modal-container">
        <div class="clay-modal-header">
            <h2 id="ts-modal-title">✏️ Edit Aturan</h2>
            <button class="clay-modal-close" onclick="closeTsEdit()" type="button">✕</button>
        </div>
        <form method="POST" id="ts-edit-form" class="ts-form">
            @csrf @method('PUT')
            <input type="hidden" name="source" value="{{ $source }}">
            <input type="hidden" name="sort_order" id="ts-e-sort">
            <div class="ts-body" style="overflow-y:auto;max-height:65vh;padding:20px 24px;">
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
                    <label>Urutan (prioritas)</label>
                    <input type="text" id="ts-e-sort-display" class="clay-input" readonly
                           style="background:#f9fafb;color:#6b7280;cursor:not-allowed;">
                    <div class="ts-hint" style="font-size:.66rem;color:#9ca3af;margin-top:3px;">Diubah otomatis via tombol ↑↓ di tabel.</div>
                </div>
                <div class="ts-field">
                    <label class="ts-check">
                        <input type="checkbox" name="is_active" id="ts-e-active" value="1">
                        Aktif (dipakai saat evaluasi)
                    </label>
                </div>
            </div>
            <div style="padding:14px 24px;border-top:1px solid rgba(0,0,0,.06);display:flex;justify-content:flex-end;gap:8px;flex-shrink:0;">
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
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;
    var filterUrl = '{{ route("tracking-status-rule.filter", $source) }}';

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

    // ── Refresh rules table ──
    function refreshRules() {
        return fetch(filterUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('ts-rules-wrap').innerHTML = data.html;
                bindActions();
            });
    }

    function bindActions() {
        // Toggle
        document.querySelectorAll('.ts-toggle-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var self = this;
                var id = self.dataset.id;
                self.disabled = true;
                post('/tracking-status-rules/' + id + '/toggle', 'PATCH', null)
                    .then(function(json) {
                        if (json.success) {
                            self.className = 'ts-toggle ' + (json.is_active ? 'on' : 'off') + ' ts-toggle-btn';
                            self.textContent = json.is_active ? '● Aktif' : '○ Nonaktif';
                        }
                    })
                    .catch(function(err) { alert('Error: ' + err.message); })
                    .finally(function() { self.disabled = false; });
            });
        });

        // Move
        document.querySelectorAll('.ts-move-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var self = this;
                var id = self.dataset.id;
                var dir = self.dataset.dir;
                self.disabled = true;
                post('/tracking-status-rules/' + id + '/move/' + dir, 'POST', null)
                    .then(function(json) {
                        if (json.success) {
                            document.getElementById('ts-rules-wrap').innerHTML = json.html;
                            bindActions();
                        }
                    })
                    .catch(function(err) { alert('Error: ' + err.message); });
            });
        });

        // Delete
        document.querySelectorAll('.ts-del-btn-ajax').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm(this.dataset.confirm)) return;
                var self = this;
                var id = self.dataset.id;
                self.disabled = true;
                post('/tracking-status-rules/' + id, 'DELETE', null)
                    .then(function(json) {
                        if (json.success) refreshRules();
                        else alert('Gagal: ' + json.message);
                    })
                    .catch(function(err) { alert('Error: ' + err.message); })
                    .finally(function() { self.disabled = false; });
            });
        });
    }

    // ── ADD FORM (AJAX) ──
    var addForm = document.getElementById('ts-form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = this.querySelector('button[type="submit"]');
            btn.disabled = true; btn.innerHTML = 'Menyimpan...';

            var body = {
                source: '{{ $source }}',
                raw_status: document.getElementById('ts-add-raw').value.trim(),
                match_type: this.querySelector('select[name="match_type"]').value,
                status: this.querySelector('select[name="status"]').value,
                problem_mode: document.getElementById('ts-problem-mode').value,
                problem_keyword: document.getElementById('ts-problem-keyword') ? document.getElementById('ts-problem-keyword').value.trim() : '',
                problem_match_type: document.getElementById('ts-problem-mtype') ? document.getElementById('ts-problem-mtype').value : 'contains',
                is_active: document.getElementById('ts-add-active').checked ? 1 : 0,
            };

            post('{{ route('tracking-status-rule.store') }}', 'POST', body)
                .then(function(json) {
                    if (json.success) {
                        // Reset form
                        addForm.querySelector('input[name="raw_status"]').value = '';
                        if (addForm.querySelector('input[name="problem_keyword"]')) addForm.querySelector('input[name="problem_keyword"]').value = '';
                        refreshRules();
                    } else {
                        alert('Gagal: ' + json.message);
                    }
                })
                .catch(function(err) { alert('Error: ' + err.message); })
                .finally(function() { btn.disabled = false; btn.innerHTML = '+ Tambah Aturan'; });
        });
    }

    // ── Problem mode visibility ──
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

    // ── EDIT MODAL ──
    var modal = document.getElementById('ts-modal');
    var editForm = document.getElementById('ts-edit-form');
    var editId = null;

    window.openTsEdit = function (id) {
        var btn = document.getElementById('ts-edit-' + id);
        if (!btn) return;
        editId = id;

        document.getElementById('ts-e-sort').value = btn.dataset.sort;
        document.getElementById('ts-e-sort-display').value = 'Urutan ' + btn.dataset.sort + ' — dievaluasi ' + (parseInt(btn.dataset.sort) <= 5 ? 'awal' : 'belakangan');
        document.getElementById('ts-e-raw').value      = btn.dataset.raw;
        document.getElementById('ts-e-match').value    = btn.dataset.match;
        document.getElementById('ts-e-status').value   = btn.dataset.status;
        document.getElementById('ts-e-problem').value  = btn.dataset.problem;
        document.getElementById('ts-e-keyword').value  = btn.dataset.keyword;
        document.getElementById('ts-e-mtype').value    = btn.dataset.mtype || 'contains';
        document.getElementById('ts-e-active').checked = btn.dataset.active === '1';

        syncKeywordVisibility(document.getElementById('ts-e-problem'), document.getElementById('ts-e-keyword-wrap'), document.getElementById('ts-e-keyword'), document.getElementById('ts-e-mtype-wrap'));

        modal.classList.add('active');
    };

    window.closeTsEdit = function () {
        modal.classList.remove('active');
        editId = null;
    };

    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!editId) return;
        var btn = editForm.querySelector('button[type="submit"]');
        btn.disabled = true; btn.innerHTML = 'Menyimpan...';

        var body = {
            source: '{{ $source }}',
            sort_order: document.getElementById('ts-e-sort').value,
            raw_status: document.getElementById('ts-e-raw').value.trim(),
            match_type: document.getElementById('ts-e-match').value,
            status: document.getElementById('ts-e-status').value,
            problem_mode: document.getElementById('ts-e-problem').value,
            problem_keyword: document.getElementById('ts-e-keyword').value.trim(),
            problem_match_type: document.getElementById('ts-e-mtype').value,
            is_active: document.getElementById('ts-e-active').checked ? 1 : 0,
        };

        post('/tracking-status-rules/' + editId, 'PUT', body)
            .then(function(json) {
                if (json.success) { closeTsEdit(); refreshRules(); }
                else alert('Gagal: ' + json.message);
            })
            .catch(function(err) { alert('Error: ' + err.message); })
            .finally(function() { btn.disabled = false; btn.innerHTML = '💾 Simpan'; });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) closeTsEdit();
    });

    var editSelect = document.getElementById('ts-e-problem');
    if (editSelect) {
        editSelect.addEventListener('change', function () {
            syncKeywordVisibility(editSelect, document.getElementById('ts-e-keyword-wrap'), document.getElementById('ts-e-keyword'), document.getElementById('ts-e-mtype-wrap'));
        });
    }

    bindActions();
})();
</script>
@endpush
