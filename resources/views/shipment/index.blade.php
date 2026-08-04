@extends('layouts.app')

@section('title', 'Data Pengiriman')
@section('page-title', 'Data Pengiriman Terpadu')
@section('page-subtitle', 'Gabungan FLIK, SiCepat, SPX')

@push('styles')
<style>
    .badge-sumb { font-size:.68rem; font-weight:800; padding:2px 8px; border-radius:8px; }
    .src-flik   { background:#e0f2fe; color:#0369a1; }
    .src-sicepat{ background:#dcfce7; color:#15803d; }
    .src-spx    { background:#ede9fe; color:#6d28d9; }
    .sel-nowrap { white-space:nowrap; }

    /* ── Dropzone upload ─────────────────────────── */
    .dropzone {
        border: 2px dashed #d1d5db; border-radius: 14px;
        padding: 38px 20px; text-align: center;
        transition: all .25s ease; cursor: pointer; background: #fafafa;
    }
    .dropzone:hover, .dropzone.drag-over {
        border-color: var(--color-primary, #FF6B6B); background: #fef2f2;
    }
    .dropzone.has-file { border-color: #059669; background: #f0fdf4; }
    .dropzone-icon { font-size: 2.4rem; margin-bottom: 6px; display: block; }
    .dropzone-title { font-weight: 700; font-size: .9rem; color: #374151; }
    .dropzone-hint  { font-size: .72rem; color: #9ca3af; margin-top: 2px; }
    .dropzone-file  { font-size: .78rem; color: #059669; font-weight: 600; margin-top: 6px; }
</style>
@endpush

@section('content')

{{-- Upload CSV --}}
<div class="clay-card" style="padding:20px 24px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;font-size:1.05rem;font-weight:800;">📥 Upload CSV Pengiriman</h2>
            <div style="font-size:.75rem;color:#9ca3af;">Unggah file dari FLIK, SiCepat, atau SPX. Data lama ditimpah bila status berubah (upsert).</div>
        </div>
    </div>

    <div class="dropzone" id="csv-dropzone">
        <span class="dropzone-icon" id="csv-icon">📂</span>
        <div class="dropzone-title">Klik atau tarik file CSV ke sini</div>
        <div class="dropzone-hint" id="csv-hint">.csv / teks — maks 10MB. Sumber otomatis dikenali dari header.</div>
        <div class="dropzone-file" id="csv-filename" style="display:none;"></div>
    </div>
    <input type="file" id="csv-file" accept=".csv,text/csv,text/plain" style="display:none;">

    <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
        <button type="button" id="btn-preview" class="clay-btn clay-btn-primary">👁 Preview</button>
        <button type="button" id="btn-import" class="clay-btn">📥 Import</button>
    </div>

    <div id="import-result" style="margin-top:14px;display:none;"></div>
</div>

{{-- Preview modal --}}
<div id="preview-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:60;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;max-width:860px;width:100%;max-height:85vh;overflow:auto;padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h3 style="margin:0;font-size:1rem;">Preview Data</h3>
            <button onclick="closePreview()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">✕</button>
        </div>
        <div id="preview-body" style="font-size:.85rem;"></div>
    </div>
</div>

{{-- Filter --}}
<div class="clay-card" style="padding:0;margin-bottom:20px;">
    <form method="GET" action="{{ route('shipment.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:16px;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari resi / penerima / telp" class="clay-input" style="flex:1;min-width:180px;">
        <select name="source" class="clay-input">
            <option value="">Semua Sumber</option>
            @foreach($sourceList as $s)
                <option value="{{ $s }}" @selected(request('source') === $s)>{{ strtoupper($s) }}</option>
            @endforeach
        </select>
        <select name="bulan" class="clay-input">
            <option value="">Semua Bulan</option>
            @foreach($monthList as $b)
                <option value="{{ $b }}" @selected(request('bulan') === $b)>{{ $b }}</option>
            @endforeach
        </select>
        <button class="clay-btn clay-btn-primary" type="submit">🔍 Filter</button>
        <a href="{{ route('shipment.index') }}" class="clay-btn">Reset</a>
    </form>
</div>

{{-- Table --}}
<div class="clay-card" style="padding:0;overflow:hidden;">
    <div class="table-scroll">
        <table class="clay-table" style="min-width:1000px;">
            <thead>
                <tr>
                    <th>Sumber</th>
                    <th>No. Resi</th>
                    <th>Penerima</th>
                    <th>Kota</th>
                    <th>Produk</th>
                    <th>Jml</th>
                    <th>Ongkir</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipments as $p)
                    <tr>
                        <td><span class="badge-sumb src-{{ $p->source }}">{{ strtoupper($p->source) }}</span></td>
                        <td class="sel-nowrap">{{ $p->tracking_number }}</td>
                        <td>{{ $p->recipient_name }}</td>
                        <td>{{ $p->city }}</td>
                        <td style="font-size:.8rem;">{{ $p->product_name }}</td>
                        <td>{{ $p->quantity }}</td>
                        <td class="sel-nowrap">Rp {{ number_format((float)$p->shipping_fee,0,',','.') }}</td>
                        <td><span style="font-size:.72rem;">{{ $p->status ?? '-' }}</span></td>
                        <td class="sel-nowrap">{{ $p->created_date?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:24px;">Belum ada data. Upload CSV untuk mulai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px;">{{ $shipments->links() }}</div>

@endsection

@push('scripts')
<script>
(function () {
    const fileInput = document.getElementById('csv-file');
    const dropzone  = document.getElementById('csv-dropzone');
    const csvIcon   = document.getElementById('csv-icon');
    const csvHint   = document.getElementById('csv-hint');
    const csvFilename = document.getElementById('csv-filename');
    const resultBox = document.getElementById('import-result');

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function setFile(name, size) {
        dropzone.classList.add('has-file');
        csvIcon.textContent = '✅';
        csvFilename.style.display = 'block';
        csvFilename.textContent = '📄 ' + name + ' (' + formatSize(size) + ')';
        csvHint.textContent = 'File siap. Klik Preview untuk melihat hasil.';
    }

    function resetDropzone() {
        dropzone.classList.remove('has-file');
        csvIcon.textContent = '📂';
        csvFilename.style.display = 'none';
        csvHint.textContent = '.csv / teks — maks 10MB. Sumber otomatis dikenali dari header.';
    }

    // ── Klik dropzone → buka file picker ─────────────
    dropzone.addEventListener('click', function () { fileInput.click(); });

    // ── Drag & drop ─────────────────────────────────
    ['dragover', 'dragenter'].forEach(function (ev) {
        dropzone.addEventListener(ev, function (e) {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.add('drag-over');
        });
    });
    ['dragleave', 'dragend', 'drop'].forEach(function (ev) {
        dropzone.addEventListener(ev, function (e) {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.remove('drag-over');
        });
    });
    dropzone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files && e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    fileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            setFile(this.files[0].name, this.files[0].size);
        } else {
            resetDropzone();
        }
    });

    function getFile() {
        if (!fileInput.files.length) { alert('Pilih file terlebih dahulu.'); return null; }
        return fileInput.files[0];
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = (s === null || s === undefined) ? '' : String(s);
        return d.innerHTML;
    }
    window.esc = esc;

    function showResult(msg, ok) {
        resultBox.style.display = 'block';
        resultBox.className = ok ? 'clay-alert clay-alert-success' : 'clay-alert clay-alert-error';
        resultBox.innerHTML = '<span>' + (ok ? '✅' : '⚠️') + '</span>' +
            '<span style="flex:1;">' + esc(msg) + '</span>' +
            '<button onclick="this.parentElement.style.display=\'none\'" style="background:none;border:none;cursor:pointer;">✕</button>';
    }

    document.getElementById('btn-preview').addEventListener('click', function () {
        const f = getFile(); if (!f) return;
        showResult('Memproses file...', true);
        const fd = new FormData(); fd.append('file', f);
        fetch('{{ route("shipment.preview") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (!d.success) { showResult(d.message || 'Gagal membaca file.', false); return; }
            resultBox.style.display = 'none';
            const modal = document.getElementById('preview-modal');
            modal.style.display = 'flex';
            let html = '<div style="margin-bottom:10px;font-weight:700;">Sumber: ' + esc(String(d.source || '-').toUpperCase()) +
                       ' &nbsp;•&nbsp; Total: ' + d.total + ' baris</div>';

            if (typeof d.matched === 'number') {
                html += '<div style="margin-bottom:10px;font-size:.8rem;display:flex;gap:10px;flex-wrap:wrap;">' +
                    '<span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:8px;font-weight:700;">✅ Cocok: ' + d.matched + '</span>' +
                    '<span style="background:' + (d.unmatched.length ? '#fee2e2' : '#f3f4f6') + ';color:' + (d.unmatched.length ? '#b91c1c' : '#6b7280') + ';padding:3px 10px;border-radius:8px;font-weight:700;">⚠️ Tidak Cocok: ' + d.unmatched.length + ' (tidak disimpan)</span>' +
                    '</div>';
            }

            if (d.unmatched && d.unmatched.length) {
                html += '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;margin-bottom:10px;max-height:140px;overflow:auto;">' +
                    '<div style="font-size:.75rem;font-weight:700;color:#b91c1c;margin-bottom:4px;">Produk tidak dikenal — baris ini tidak akan disimpan:</div>';
                var seen = {};
                d.unmatched.forEach(function (u) {
                    var key = u.product_name || u.tracking_number;
                    if (seen[key]) return; seen[key] = true;
                    html += '<div style="font-size:.72rem;color:#991b1b;">• ' + esc(u.product_name || '-') + ' <span style="color:#9ca3af;">(' + esc(u.tracking_number) + ')</span></div>';
                });
                html += '</div>';
            }

            if (d.sampel && d.sampel.length) {
                html += '<table class="clay-table"><thead><tr><th>Resi</th><th>Penerima</th><th>Kota</th><th>Produk</th><th>Status</th></tr></thead><tbody>' +
                    d.sampel.map(function (r) {
                        return '<tr><td>' + esc(r.tracking_number) + '</td><td>' + esc(r.recipient_name) + '</td><td>' + esc(r.city) +
                               '</td><td>' + esc(r.product_name) + '</td><td>' + esc(r.status) + '</td></tr>';
                    }).join('') + '</tbody></table>';
            } else {
                html += '<div style="color:#9ca3af;">Tidak ada data terbaca.</div>';
            }
            document.getElementById('preview-body').innerHTML = html;
        })
        .catch(function () { showResult('Gagal membaca file.', false); });
    });

    document.getElementById('btn-import').addEventListener('click', function () {
        const f = getFile(); if (!f) return;
        showResult('Mengimport...', true);
        const fd = new FormData(); fd.append('file', f);
        fetch('{{ route("shipment.import") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (!d.success) { showResult(d.message || 'Gagal import.', false); return; }
            showResult(d.message, true);
            setTimeout(function () { window.location.reload(); }, 900);
        })
        .catch(function () { showResult('Gagal import.', false); });
    });

    window.closePreview = function () { document.getElementById('preview-modal').style.display = 'none'; };
})();
</script>
@endpush