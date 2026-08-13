@extends('layouts.app')

@section('title', $template ? 'Edit Template '.$template->name : 'Template Baru')
@section('page-title', $template ? '✏️ Edit Template' : '➕ Template Baru')
@section('page-subtitle', $template ? 'Ubah nama, courier, dan mapping kolom template '.$template->name : 'Buat template export courier baru')

@push('styles')
<style>
    .em-row select { padding:4px 6px; font-size:.74rem; border:1px solid #d1d5db; border-radius:7px; min-width:230px; max-width:100%; }
    .em-row .em-static { display:none; }
    .em-row .em-static.show { display:inline-flex; }
    .em-row .em-static input { width:120px; padding:4px 6px; font-size:.74rem; border:1px solid #d1d5db; border-radius:7px; }
    .em-col-no { color:#9ca3af; font-size:.72rem; font-weight:700; }
    .em-header-cell { font-size:.8rem; font-weight:600; color:#1e1b2e; }
    .em-draft-tag { display:none; font-size:.66rem; font-weight:700; color:#b45309; background:#fef3c7; padding:2px 8px; border-radius:999px; }
    .em-draft-tag.show { display:inline-block; }
    .em-hint { font-size:.66rem; color:#9ca3af; margin-top:3px; line-height:1.45; }
</style>
@endpush

@section('content')

<form method="POST" id="em-main-form"
      action="{{ $template ? route('export-mapping.update', $template) : route('export-mapping.store') }}">
    @csrf
    @if($template) @method('PUT') @endif

    <div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#374151;margin-bottom:4px;">Nama Template *</label>
                <input type="text" name="name" required class="clay-input" maxlength="100"
                       value="{{ old('name', $template?->name ?? '') }}"
                       placeholder="contoh: JNE Express" style="font-size:.82rem;">
                <div class="em-hint">Key (untuk URL/export) dibuat otomatis dari nama, mis. "JNE Express" → <code>jne-express</code>.</div>
            </div>
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#374151;margin-bottom:4px;">Courier yang memakai template ini</label>
                <input type="text" name="couriers" class="clay-input" maxlength="255"
                       value="{{ old('couriers', $template ? implode(', ', $template->couriers ?? []) : '') }}"
                       placeholder="pisahkan dengan koma, mis. jne, jne-cod" style="font-size:.82rem;">
                <div class="em-hint">Kosongkan → nama template dipakai sebagai courier. Export di halaman Data Mentah hanya menampilkan order dengan courier ini.</div>
            </div>
        </div>
    </div>

    <div class="clay-card" style="overflow:hidden;margin-bottom:16px;" data-reveal>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:14px 20px;border-bottom:1px solid rgba(0,0,0,.06);">
            <div>
                <h2 style="margin:0;font-size:1rem;font-weight:800;">🧩 Mapping Kolom</h2>
                <div style="font-size:.72rem;color:#9ca3af;margin-top:2px;">
                    <span id="em-count">{{ $mapping->count() }}</span> kolom
                    <span class="em-draft-tag" id="em-draft">draft — belum disimpan</span>
                </div>
            </div>
            <button type="button" class="clay-btn" style="padding:6px 14px;font-size:.78rem;"
                    onclick="document.getElementById('em-file').click()">⬆ Upload Template CSV</button>
            <input type="file" id="em-file" accept=".csv,text/csv,text/plain" style="display:none;">
        </div>

        <div class="table-scroll">
            <table class="clay-table" style="min-width:720px;">
                <thead>
                    <tr>
                        <th style="width:56px;text-align:center;">Kolom</th>
                        <th>Header Template</th>
                        <th style="width:420px;">Sumber Isi</th>
                    </tr>
                </thead>
                <tbody id="em-body">
                    @forelse($mapping as $m)
                        <tr class="em-row" data-index="{{ $m->column_index }}" data-header="{{ $m->header }}">
                            <td class="em-col-no" style="text-align:center;">{{ $m->column_index + 1 }}</td>
                            <td class="em-header-cell">{{ $m->header }}</td>
                            <td>
                                @php
                                    $selVal = match ($m->source_type) {
                                        'column' => 'column:'.$m->source_value,
                                        'computed' => 'computed:'.$m->source_value,
                                        'static' => 'static',
                                        default => 'empty',
                                    };
                                @endphp
                                <select onchange="toggleEmStatic(this)">
                                    <option value="empty" @selected($selVal === 'empty')>— Kosongkan —</option>
                                    <optgroup label="Kolom shipping_orders">
                                        @foreach($columns as $key => $label)
                                            <option value="column:{{ $key }}" @selected($selVal === 'column:'.$key)>{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Nilai khusus (computed)">
                                        @foreach($computed as $key => $label)
                                            <option value="computed:{{ $key }}" @selected($selVal === 'computed:'.$key)>{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                    <option value="static" @selected($m->source_type === 'static')>✍️ Teks tetap</option>
                                </select>
                                <span class="em-static {{ $m->source_type === 'static' ? 'show' : '' }}">
                                    <input type="text" value="{{ $m->source_type === 'static' ? $m->source_value : '' }}"
                                           placeholder="nilai teks…">
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;padding:40px;color:#9ca3af;">
                                Belum ada kolom. <b>Upload Template CSV</b> untuk memuat header template.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;" data-reveal>
        <a href="{{ route('export-mapping.index') }}" class="clay-btn clay-btn-outline">← Kembali</a>
        <button type="submit" class="clay-btn clay-btn-primary">💾 {{ $template ? 'Simpan Perubahan' : 'Buat Template' }}</button>
    </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var templateKey = '{{ $template?->key ?? '' }}';
    var uploadUrl = '{{ route('export-mapping.upload') }}';

    window.toggleEmStatic = function (sel) {
        var wrap = sel.parentElement.querySelector('.em-static');
        if (wrap) wrap.classList.toggle('show', sel.value === 'static');
    };

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function escAttr(s) {
        return escHtml(s).replace(/"/g, '&quot;');
    }

    function rowHtml(index, header, sourceType, sourceValue) {
        var selVal = sourceType === 'column' ? 'column:' + sourceValue
            : sourceType === 'computed' ? 'computed:' + sourceValue
            : sourceType === 'static' ? 'static' : 'empty';

        var options = '<option value="empty"' + (selVal === 'empty' ? ' selected' : '') + '>— Kosongkan —</option>';
        options += '<optgroup label="Kolom shipping_orders">';
        @foreach($columns as $key => $label)
            options += '<option value="column:{{ $key }}"' + (selVal === 'column:{{ $key }}' ? ' selected' : '') + '>{{ $label }}</option>';
        @endforeach
        options += '</optgroup><optgroup label="Nilai khusus (computed)">';
        @foreach($computed as $key => $label)
            options += '<option value="computed:{{ $key }}"' + (selVal === 'computed:{{ $key }}' ? ' selected' : '') + '>{{ $label }}</option>';
        @endforeach
        options += '</optgroup><option value="static"' + (sourceType === 'static' ? ' selected' : '') + '>✍️ Teks tetap</option>';

        var staticVal = sourceType === 'static' ? sourceValue : '';
        var staticShow = sourceType === 'static' ? ' show' : '';

        return '<tr class="em-row" data-index="' + index + '" data-header="' + escAttr(header) + '">' +
            '<td class="em-col-no" style="text-align:center;">' + (index + 1) + '</td>' +
            '<td class="em-header-cell">' + escHtml(header) + '</td>' +
            '<td><select onchange="toggleEmStatic(this)">' + options + '</select>' +
            '<span class="em-static' + staticShow + '">' +
            '<input type="text" value="' + escAttr(staticVal) + '" placeholder="nilai teks…">' +
            '</span></td></tr>';
    }

    // ── Upload template CSV ─────────────────────────────
    var input = document.getElementById('em-file');
    if (input) {
        input.addEventListener('change', function () {
            if (!this.files.length) return;
            var fd = new FormData();
            fd.append('file', this.files[0]);
            if (templateKey) fd.append('template', templateKey);

            var btn = this.parentElement.querySelector('.clay-btn');
            var oldText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = '⏳ Membaca…'; }

            fetch(uploadUrl, {
                method: 'POST',
                body: fd,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) { alert(res.message || 'Gagal membaca template.'); return; }
                var body = document.getElementById('em-body');
                var html = '';
                res.headers.forEach(function (h, i) {
                    html += rowHtml(i, h.header, h.source_type, h.source_value || '');
                });
                body.innerHTML = html || '<tr><td colspan="3" style="text-align:center;padding:40px;color:#9ca3af;">Template tidak punya kolom.</td></tr>';
                document.getElementById('em-count').textContent = res.headers.length;
                document.getElementById('em-draft').classList.add('show');
                alert('Template dibaca: ' + res.headers.length + ' kolom. Cocokkan sumber lalu simpan.');
            })
            .catch(function (err) { alert('Gagal upload: ' + err.message); })
            .finally(function () {
                if (btn) { btn.disabled = false; btn.textContent = oldText; }
                input.value = '';
            });
        });
    }

    // ── Submit: susun items[] dari tabel mapping ────────
    document.getElementById('em-main-form').addEventListener('submit', function (e) {
        var rows = document.querySelectorAll('#em-body tr.em-row');
        if (!rows.length) { e.preventDefault(); alert('Belum ada kolom mapping. Upload template CSV dulu.'); return; }

        var form = this;
        var failed = false;

        rows.forEach(function (tr) {
            var sel = tr.querySelector('select');
            var raw = sel.value;
            var sourceType, sourceValue = '';

            if (raw === 'static') {
                var inp = tr.querySelector('.em-static input');
                sourceType = 'static';
                sourceValue = inp ? inp.value.trim() : '';
                if (!sourceValue) failed = true;
            } else if (raw.indexOf('column:') === 0) {
                sourceType = 'column';
                sourceValue = raw.slice(7);
            } else if (raw.indexOf('computed:') === 0) {
                sourceType = 'computed';
                sourceValue = raw.slice(9);
            } else {
                sourceType = 'empty';
            }

            [['column_index', tr.dataset.index], ['header', tr.dataset.header],
             ['source_type', sourceType], ['source_value', sourceValue]].forEach(function (p) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'items[' + tr.dataset.index + '][' + p[0] + ']';
                inp.value = p[1];
                form.appendChild(inp);
            });
        });

        if (failed) {
            e.preventDefault();
            alert('Sumber "teks tetap" tidak boleh kosong.');
            return;
        }
    });
})();
</script>
@endpush
