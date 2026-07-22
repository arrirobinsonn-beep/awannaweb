@extends('layouts.app')
@section('title','Kiriman Actual')
@section('page-title','🚚 Kiriman Actual')
@section('page-subtitle','Data kiriman harian per dashboard (SPX / FLIK / SICEPAT / PEACHTREE)')

@section('content')

<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="GET" action="{{ route('gudang.kiriman') }}"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div>
            <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151;">Bulan</label>
            <input type="month" name="bulan" value="{{ request('bulan') }}"
                   class="clay-input" style="padding:6px 10px;">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary">Filter</button>
        <a href="{{ route('gudang.kiriman') }}" class="clay-btn clay-btn-outline">Reset</a>
        <button type="button" class="clay-btn clay-btn-primary" style="margin-left:auto;"
                onclick="document.getElementById('modal-upload').classList.add('active')">
            📤 Upload Excel
        </button>
        <button type="button" class="clay-btn clay-btn-success"
                onclick="document.getElementById('form-tambah').classList.toggle('hidden')">
            + Tambah
        </button>
        <button type="button" class="clay-btn clay-btn-outline" style="font-size:.7rem;"
                onclick="document.getElementById('panel-dashboard').classList.toggle('hidden')">
            ⚙️ Atur Dashboard
        </button>
    </form>
</div>

{{-- ─── Panel Atur Dashboard ─────────────────────────────────────────────── --}}
<div id="panel-dashboard" class="hidden clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <h4 style="font-weight:700;margin:0;font-size:.85rem;">⚙️ Atur Dashboard</h4>
        <button type="button" class="clay-btn clay-btn-sm clay-btn-outline" onclick="document.getElementById('panel-dashboard').classList.add('hidden')">✕ Tutup</button>
    </div>
    <form method="POST" action="{{ route('gudang.kiriman.dashboard-store') }}"
          style="display:flex;gap:10px;align-items:flex-end;margin-bottom:12px;">
        @csrf
        <div>
            <label class="field-label" style="font-size:.65rem;">Nama Dashboard Baru</label>
            <input type="text" name="name" required class="clay-input" placeholder="contoh: JNE" style="padding:4px 8px;font-size:.8rem;">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary" style="font-size:.75rem;">Tambah</button>
    </form>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
        @foreach($allDashboards as $db)
        <div style="display:flex;align-items:center;gap:4px;background:#f1f5f9;padding:4px 8px;border-radius:6px;font-size:.75rem;font-weight:600;">
            <span>{{ $db->name }}</span>
            <form method="POST" action="{{ route('gudang.kiriman.dashboard-destroy', $db) }}"
                  style="display:inline" onsubmit="return confirm('Hapus dashboard \"{{ $db->name }}\"?')">
                @csrf @method('DELETE')
                <button class="clay-btn clay-btn-xs clay-btn-danger" style="padding:0 4px;font-size:.6rem;min-height:0;">✕</button>
            </form>
        </div>
        @endforeach
    </div>
</div>

<div id="form-tambah" class="hidden clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <h4 style="font-weight:700;margin:0;">Tambah Kiriman</h4>
        <button type="button" class="clay-btn clay-btn-sm clay-btn-outline" onclick="tutupForm()">✕ Tutup</button>
    </div>
    <form method="POST" action="{{ route('gudang.kiriman.store') }}">
        @csrf
        <div style="display:flex;gap:10px;margin-bottom:8px;align-items:flex-end;">
            <div>
                <label class="field-label">Tanggal</label>
                <input type="date" name="tanggal" required class="clay-input" value="{{ date('Y-m-d') }}">
            </div>
            <div style="display:flex;align-items:flex-end;gap:10px;flex:1;">
                <button type="button" class="clay-btn clay-btn-sm clay-btn-success" onclick="tambahBaris()">+ Tambah Baris</button>
            </div>
        </div>
        <div id="baris-container">
            <div class="baris-kiriman" data-index="0" style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:8px;">
                <div style="display:flex;gap:8px;margin-bottom:6px;align-items:flex-end;flex-wrap:wrap;">
                    <div style="flex:0 0 100px;">
                        <label class="field-label" style="font-size:.6rem;">Jenis</label>
                        <select name="rows[0][jenis]" required class="clay-input" style="padding:4px 6px;font-size:.75rem;">
                            <option value="TF">TF</option>
                            <option value="COD">COD</option>
                        </select>
                    </div>
                    <div style="flex:0 0 120px;">
                        <label class="field-label" style="font-size:.6rem;">Dashboard</label>
                        <select name="rows[0][dashboard]" required class="clay-input" style="padding:4px 6px;font-size:.75rem;">
                            @foreach($dashboards as $db)
                            <option value="{{ $db }}">{{ $db }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex:0 0 80px;">
                        <label class="field-label" style="font-size:.6rem;">Jumlah Resi</label>
                        <input type="number" name="rows[0][jumlah_resi]" class="clay-input" min="1" value="1" style="padding:4px 6px;font-size:.75rem;">
                    </div>
                    <div style="padding-top:12px;font-size:.65rem;color:#6b7280;">
                        <span>💵 Value otomatis dari harga_jual × jumlah</span>
                    </div>
                    <button type="button" class="clay-btn clay-btn-sm clay-btn-danger" onclick="hapusBaris(this)" style="font-size:.65rem;">✕</button>
                </div>

                <div style="margin-top:6px;padding-top:6px;border-top:1px dashed #d1d5db;">
                    <label style="display:block;font-size:.65rem;font-weight:600;margin-bottom:4px;color:#6b7280;">PRODUK & JUMLAH</label>
                    <div class="produk-container">
                        <div class="baris-produk" data-produk-index="0" style="display:flex;gap:8px;margin-bottom:4px;align-items:flex-end;">
                            <div style="flex:1;">
                                <select name="rows[0][products][0][product_id]" required class="clay-input" style="padding:4px 6px;font-size:.75rem;">
                                    <option value="">— Pilih Produk —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->nama_produk }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="flex:0 0 100px;">
                                <input type="number" name="rows[0][products][0][jumlah]" class="clay-input" min="1" value="1" placeholder="jumlah" style="padding:4px 6px;font-size:.75rem;">
                            </div>
                            <button type="button" class="clay-btn clay-btn-xs clay-btn-danger" onclick="hapusProduk(this)" style="font-size:.6rem;">✕</button>
                        </div>
                    </div>
                    <button type="button" class="clay-btn clay-btn-xs clay-btn-outline" onclick="tambahProduk(this)" style="font-size:.65rem;">+ Produk</button>
                </div>
            </div>
        </div>
        <div style="margin-top:8px;">
            <button type="submit" class="clay-btn clay-btn-primary">Simpan Semua</button>
        </div>
    </form>
</div>

{{-- ─── Modal Upload Excel ──────────────────────────────────────────── --}}
<div class="modal-kiriman" id="modal-upload">
    <div class="modal-backdrop" onclick="closeModal('modal-upload')"></div>
    <div class="modal-container modal-container-sm">
        <div class="modal-header">
            <h2>📤 Upload Excel Kiriman</h2>
            <button class="modal-close" onclick="closeModal('modal-upload')" type="button">✕</button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom:12px;">
                <label style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:4px;">📅 Tanggal Kiriman</label>
                <input type="date" id="upload-tanggal" value="{{ date('Y-m-d') }}"
                       style="width:100%;padding:8px 10px;border:2px solid #e5e7eb;border-radius:8px;font-size:.8rem;">
            </div>
            <div style="font-size:.78rem;color:#6b7280;margin-bottom:12px;">
                File harus memiliki kolom <strong>Tanggal Pembuatan</strong>, <strong>Kurir</strong>, <strong>AWB</strong>, dan <strong>Nama Produk</strong>.
            </div>
            <div class="dropzone-wrap">
                <div class="processing-overlay" id="upload-processing" style="display:none;">
                    <div class="spinner"></div>
                    <div style="font-size:.82rem;font-weight:600;color:#374151;">Memproses file...</div>
                    <div style="font-size:.7rem;color:#9ca3af;">Mengelompokkan data per kurir & tanggal</div>
                </div>
                <div class="dropzone" id="upload-dropzone">
                    <span class="dropzone-icon" id="upload-icon">📂</span>
                    <div class="dropzone-title">Klik atau tarik file ke sini</div>
                    <div class="dropzone-hint" id="upload-hint">.xlsx, .xls, .csv — maks 10MB</div>
                    <div class="dropzone-file" id="upload-filename" style="display:none;"></div>
                    <input type="file" id="file-input" accept=".xlsx,.xls,.csv">
                </div>
            </div>
            <div class="upload-error" id="upload-error" style="display:none;margin-top:8px;padding:8px 12px;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:.75rem;"></div>
        </div>
    </div>
</div>

{{-- ─── Modal Preview Excel ──────────────────────────────────────────── --}}
<div class="modal-kiriman" id="modal-preview">
    <div class="modal-backdrop" onclick="closeModal('modal-preview')"></div>
    <div class="modal-container modal-container-lg">
        <div class="modal-header">
            <h2 id="preview-title">📊 Preview Data Kiriman</h2>
            <button class="modal-close" onclick="closeModal('modal-preview')" type="button">✕</button>
        </div>
        <div class="modal-body">
            <div class="preview-stats" id="preview-stats" style="display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap;"></div>
            <div class="preview-alert" id="preview-errors" style="display:none;margin-bottom:12px;padding:8px 12px;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:.75rem;white-space:pre-line;"></div>
            <div id="preview-tables"></div>
        </div>
        <div class="modal-footer">
            <button class="clay-btn clay-btn-outline" onclick="closeModal('modal-preview')" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="preview-save-btn" type="button" style="display:none;">💾 Import Data</button>
        </div>
    </div>
</div>

<script>
var rowCounter = 1;
var produkCounter = {};

function tambahBaris() {
    var container = document.getElementById('baris-container');
    var first = container.querySelector('.baris-kiriman');
    var clone = first.cloneNode(true);
    var idx = rowCounter++;
    clone.dataset.index = idx;
    clone.querySelectorAll('[name]').forEach(function(el) {
        el.name = el.name.replace(/^rows\[\d+\]/, 'rows['+idx+']');
    });
    clone.querySelectorAll('input[type="number"]').forEach(function(inp) { inp.value = '1'; });
    clone.querySelectorAll('select').forEach(function(sel) { sel.selectedIndex = 0; });
    container.appendChild(clone);
    produkCounter[idx] = 1;
}
function hapusBaris(btn) {
    var container = document.getElementById('baris-container');
    if (container.querySelectorAll('.baris-kiriman').length > 1) {
        btn.closest('.baris-kiriman').remove();
    }
}
function tambahProduk(btn) {
    var row = btn.closest('.baris-kiriman');
    var container = row.querySelector('.produk-container');
    var first = container.querySelector('.baris-produk');
    var clone = first.cloneNode(true);
    var rowIdx = row.dataset.index;
    if (!produkCounter[rowIdx]) produkCounter[rowIdx] = 1;
    var pIdx = produkCounter[rowIdx]++;
    clone.dataset.produkIndex = pIdx;
    clone.querySelectorAll('[name]').forEach(function(el) {
        el.name = el.name.replace(/\[products\]\[\d+\]/, '[products]['+pIdx+']');
    });
    clone.querySelector('select').selectedIndex = 0;
    clone.querySelector('input').value = '1';
    container.appendChild(clone);
}
function hapusProduk(btn) {
    var container = btn.closest('.produk-container');
    if (container.querySelectorAll('.baris-produk').length > 1) {
        btn.closest('.baris-produk').remove();
    }
}
function tutupForm() {
    document.getElementById('form-tambah').classList.add('hidden');
}

// ─── Modal helpers ──────────────────────────────────────────────────
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// ─── Upload Excel ───────────────────────────────────────────────────
var selectedFile = null;

document.getElementById('file-input').addEventListener('change', function(e) {
    selectedFile = e.target.files[0];
    if (!selectedFile) return;
    var fn = document.getElementById('upload-filename');
    fn.textContent = selectedFile.name;
    fn.style.display = 'block';
    document.getElementById('upload-icon').textContent = '✅';
    document.getElementById('upload-hint').textContent = (selectedFile.size / 1024).toFixed(1) + ' KB';
    uploadAndPreview(selectedFile);
});

// Drag & drop
var dz = document.getElementById('upload-dropzone');
dz.addEventListener('dragover', function(e) { e.preventDefault(); dz.style.borderColor = '#FF6B6B'; });
dz.addEventListener('dragleave', function(e) { dz.style.borderColor = '#d1d5db'; });
dz.addEventListener('drop', function(e) {
    e.preventDefault();
    dz.style.borderColor = '#d1d5db';
    if (e.dataTransfer.files.length) {
        document.getElementById('file-input').files = e.dataTransfer.files;
        document.getElementById('file-input').dispatchEvent(new Event('change'));
    }
});

function uploadAndPreview(file) {
    var processing = document.getElementById('upload-processing');
    var errorDiv = document.getElementById('upload-error');
    processing.style.display = 'flex';
    errorDiv.style.display = 'none';

    var fd = new FormData();
    fd.append('file', file);
    fd.append('tanggal', document.getElementById('upload-tanggal').value);
    fd.append('_token', '{{ csrf_token() }}');

    fetch('{{ route('gudang.kiriman.excel-preview') }}', {
        method: 'POST',
        body: fd,
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        processing.style.display = 'none';
        if (!res.success) {
            errorDiv.textContent = res.message || 'Gagal memproses file.';
            errorDiv.style.display = 'block';
            return;
        }
        showPreview(res);
    })
    .catch(function(err) {
        processing.style.display = 'none';
        errorDiv.textContent = 'Terjadi kesalahan: ' + err.message;
        errorDiv.style.display = 'block';
    });
}

function showPreview(res) {
    var data = res.data;
    var errors = res.errors || [];

    // Aggregate COD/TF totals
    var totalCod = 0, totalTf = 0, totalCodResi = 0, totalTfResi = 0, totalCodPcs = 0, totalTfPcs = 0;
    data.groups.forEach(function(g) {
        var pcs = 0;
        g.products.forEach(function(p) { pcs += p.jumlah; });
        if (g.jenis === 'COD') { totalCod += g.total_value; totalCodResi += g.jumlah_resi; totalCodPcs += pcs; }
        else { totalTf += g.total_value; totalTfResi += g.jumlah_resi; totalTfPcs += pcs; }
    });

    var statsHtml = '';
    if (errors.length) {
        statsHtml += '<div class="stat-card" style="background:#fef2f2;color:#991b1b;">⚠️ ' + errors.length + ' Error</div>';
    }
    statsHtml += '<div class="stat-card" style="background:#ecfdf5;color:#065f46;">✅ ' + data.total + ' Baris Valid</div>';
    statsHtml += '<div class="stat-card" style="background:#eff6ff;color:#1e40af;">📦 ' + data.groups.length + ' Grup Kiriman</div>';
    var totalPcs = totalCodPcs + totalTfPcs;
    var totalNilai = totalCod + totalTf;
    if (totalCod > 0) statsHtml += '<div class="stat-card" style="background:#fefce8;color:#854d0e;">💰 COD: Rp ' + numberFormat(totalCod) + ' (' + totalCodResi + ' resi, ' + numberFormat(totalCodPcs) + ' pcs)</div>';
    if (totalTf > 0) statsHtml += '<div class="stat-card" style="background:#f0fdf4;color:#166534;">💳 TF: Rp ' + numberFormat(totalTf) + ' (' + totalTfResi + ' resi, ' + numberFormat(totalTfPcs) + ' pcs)</div>';
    statsHtml += '<div class="stat-card" style="background:#f3e8ff;color:#6b21a8;">📦 Total: ' + numberFormat(totalPcs) + ' pcs — Rp ' + numberFormat(totalNilai) + '</div>';
    document.getElementById('preview-stats').innerHTML = statsHtml;

    // Errors
    var errorDiv = document.getElementById('preview-errors');
    if (errors.length) {
        errorDiv.innerHTML = '<strong>⚠️ ' + errors.length + ' baris dilewati:</strong>\n' + errors.join('\n');
        errorDiv.style.display = 'block';
    } else {
        errorDiv.style.display = 'none';
    }

    // Groups table
    var html = '';
    data.groups.forEach(function(g, gi) {
        html += '<div style="border:1px solid #e5e7eb;border-radius:10px;margin-bottom:10px;overflow:hidden;">';
        html += '<div style="padding:10px 14px;background:#f8fafc;font-weight:700;font-size:.8rem;border-bottom:1px solid #e5e7eb;">';
        html += '📦 ' + g.tanggal + ' • ' + g.kurir + ' • ' + g.jenis;
        html += ' <span style="font-weight:400;color:#6b7280;">— ' + g.jumlah_resi + ' resi</span>';
        html += ' <span style="float:right;color:var(--color-primary);">Rp ' + numberFormat(g.total_value) + '</span>';
        html += '</div>';
        html += '<div style="padding:8px 14px;font-size:.75rem;">';
        html += '<table class="clay-table" style="font-size:.7rem;">';
        html += '<thead><tr><th>Produk</th><th class="text-right">Jumlah</th></tr></thead><tbody>';
        var totalJumlah = 0;
        g.products.forEach(function(p) {
            html += '<tr><td>' + p.nama_produk + '</td><td class="text-right">' + numberFormat(p.jumlah) + '</td></tr>';
            totalJumlah += p.jumlah;
        });
        html += '</tbody><tfoot><tr style="background:#f1f5f9;font-weight:800;"><td>Total Produk</td><td class="text-right">' + numberFormat(totalJumlah) + '</td></tr></tfoot></table>';
        html += '</div></div>';
    });
    document.getElementById('preview-tables').innerHTML = html;

    // Show save button
    var saveBtn = document.getElementById('preview-save-btn');
    saveBtn.style.display = 'inline-flex';
    saveBtn.onclick = function() { importData(selectedFile); };

    // Close upload modal, open preview
    closeModal('modal-upload');
    document.getElementById('modal-preview').classList.add('active');
}

function importData(file) {
    var saveBtn = document.getElementById('preview-save-btn');
    saveBtn.disabled = true;
    saveBtn.textContent = '⏳ Importing...';

    var fd = new FormData();
    fd.append('file', file);
    fd.append('tanggal', document.getElementById('upload-tanggal').value);
    fd.append('_token', '{{ csrf_token() }}');

    fetch('{{ route('gudang.kiriman.excel-import') }}', {
        method: 'POST',
        body: fd,
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (!res.success) {
            alert('Gagal: ' + (res.message || 'Unknown error'));
            saveBtn.disabled = false;
            saveBtn.textContent = '💾 Import Data';
            return;
        }
        alert('✅ ' + res.message);
        closeModal('modal-preview');
        location.reload();
    })
    .catch(function(err) {
        alert('Gagal: ' + err.message);
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Import Data';
    });
}

function numberFormat(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
</script>

{{-- ─── Rekap Harian per Dashboard ─────────────────────────────────────── --}}
@if($recapByDashboard)
<div class="clay-card" style="padding:16px;margin-top:24px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
        <h4 style="font-weight:800;margin:0;font-size:.85rem;">📋 REKAP HARIAN PER DASHBOARD</h4>
        <form method="GET" action="{{ route('gudang.kiriman') }}" style="display:flex;gap:6px;align-items:flex-end;">
            <input type="hidden" name="bulan" value="{{ $bulan }}">
            <div>
                <label style="display:block;font-size:.6rem;font-weight:600;margin-bottom:2px;color:#6b7280;">Pilih Dashboard</label>
                <select name="dashboard" onchange="this.form.submit()" class="clay-input" style="padding:4px 8px;font-size:.75rem;">
                    <option value="">Semua</option>
                    @foreach($allDashboards as $db)
                    <option value="{{ $db->name }}" {{ $selectedDashboard===$db->name?'selected':'' }}>{{ $db->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($selectedDashboard)
            <a href="{{ route('gudang.kiriman', ['bulan' => $bulan]) }}" class="clay-btn clay-btn-xs clay-btn-outline" style="font-size:.65rem;">Reset</a>
            @endif
        </form>
    </div>

    @foreach($recapByDashboard as $i => $recap)
    <div class="db-folder" style="margin-bottom:8px;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
        {{-- Folder Header --}}
        <div class="db-header" onclick="togDb('db-{{ $i }}')"
             style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);cursor:pointer;user-select:none;border-bottom:1px solid #e5e7eb;">
            <span style="display:flex;align-items:center;gap:8px;font-weight:700;font-size:.85rem;color:#1e1b2e;">
                <span class="db-chevron" style="font-size:.65rem;color:#9ca3af;transition:transform .2s;">▶</span>
                📦 {{ $recap['dashboard'] }}
                <span style="font-weight:400;font-size:.7rem;color:#6b7280;">
                    TF: {{ number_format($recap['tf_resi'],0,',','.') }} resi / {{ number_format($recap['tf_barang'],0,',','.') }} brg
                    | COD: {{ number_format($recap['cod_resi'],0,',','.') }} resi / {{ number_format($recap['cod_barang'],0,',','.') }} brg
                </span>
            </span>
            <span style="font-weight:800;font-size:.75rem;color:var(--color-primary,#FF6B6B);">
                {{ number_format($recap['total_resi'],0,',','.') }} resi | {{ number_format($recap['total_barang'],0,',','.') }} brg
            </span>
        </div>

        {{-- Folder Content --}}
        <div id="db-{{ $i }}" class="db-content" style="display:none;overflow-x:auto;">
            <table class="clay-table" style="font-size:.7rem;">
                <thead>
                    <tr>
                        <th style="min-width:80px;">TANGGAL</th>
                        <th colspan="3" style="min-width:100px;color:#2563eb;">TF</th>
                        <th colspan="3" style="min-width:100px;color:#dc2626;">COD</th>
                        <th colspan="3" style="min-width:100px;">TOTAL</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th style="font-size:.6rem;color:#6b7280;">RESI</th>
                        <th style="font-size:.6rem;color:#6b7280;">BRG</th>
                        <th style="font-size:.6rem;color:#6b7280;">VALUE</th>
                        <th style="font-size:.6rem;color:#6b7280;">RESI</th>
                        <th style="font-size:.6rem;color:#6b7280;">BRG</th>
                        <th style="font-size:.6rem;color:#6b7280;">VALUE</th>
                        <th style="font-size:.6rem;color:#6b7280;">RESI</th>
                        <th style="font-size:.6rem;color:#6b7280;">BRG</th>
                        <th style="font-size:.6rem;color:#6b7280;">VALUE</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recap['daily'] as $r)
                    <tr>
                        <td style="font-weight:600;">{{ \Carbon\Carbon::parse($r['date'])->format('d/m/Y') }}</td>
                        <td class="text-right">{{ $r['tf_resi'] > 0 ? number_format($r['tf_resi'],0,',','.') : '' }}</td>
                        <td class="text-right">{{ $r['tf_barang'] > 0 ? number_format($r['tf_barang'],0,',','.') : '' }}</td>
                        <td class="text-right">{{ $r['tf_value'] > 0 ? number_format($r['tf_value'],0,',','.') : '' }}</td>
                        <td class="text-right">{{ $r['cod_resi'] > 0 ? number_format($r['cod_resi'],0,',','.') : '' }}</td>
                        <td class="text-right">{{ $r['cod_barang'] > 0 ? number_format($r['cod_barang'],0,',','.') : '' }}</td>
                        <td class="text-right">{{ $r['cod_value'] > 0 ? number_format($r['cod_value'],0,',','.') : '' }}</td>
                        <td class="text-right" style="font-weight:700;">{{ number_format($r['total_resi'],0,',','.') }}</td>
                        <td class="text-right" style="font-weight:700;">{{ number_format($r['total_barang'],0,',','.') }}</td>
                        <td class="text-right" style="font-weight:700;">{{ number_format($r['total_value'],0,',','.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="10" style="text-align:center;color:#9ca3af;">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc;font-weight:800;border-top:2px solid #e5e7eb;">
                        <td style="font-size:.7rem;">TOTAL {{ $recap['dashboard'] }}</td>
                        <td class="text-right">{{ number_format($recap['tf_resi'],0,',','.') }}</td>
                        <td class="text-right">{{ number_format($recap['tf_barang'],0,',','.') }}</td>
                        <td class="text-right">{{ number_format($recap['tf_value'],0,',','.') }}</td>
                        <td class="text-right">{{ number_format($recap['cod_resi'],0,',','.') }}</td>
                        <td class="text-right">{{ number_format($recap['cod_barang'],0,',','.') }}</td>
                        <td class="text-right">{{ number_format($recap['cod_value'],0,',','.') }}</td>
                        <td class="text-right" style="color:var(--color-primary,#FF6B6B);">{{ number_format($recap['total_resi'],0,',','.') }}</td>
                        <td class="text-right" style="color:var(--color-primary,#FF6B6B);">{{ number_format($recap['total_barang'],0,',','.') }}</td>
                        <td class="text-right" style="color:var(--color-primary,#FF6B6B);">{{ number_format($recap['total_value'],0,',','.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endforeach

    @if(!$selectedDashboard)
    <div style="border-top:2px solid #e5e7eb;padding-top:12px;display:flex;justify-content:flex-end;gap:24px;font-weight:800;font-size:.85rem;">
        <span>GRAND TOTAL RESI: {{ number_format($grandTotalResi,0,',','.') }}</span>
        <span>GRAND TOTAL BARANG: {{ number_format($grandTotalBarang,0,',','.') }}</span>
        <span style="color:var(--color-primary,#FF6B6B);">GRAND TOTAL VALUE: {{ number_format($grandTotalValue,0,',','.') }}</span>
    </div>
    @endif
</div>

<script>
var openDbs = new Set();
function togDb(id) {
    var el = document.getElementById(id);
    var chev = el.parentElement.querySelector('.db-chevron');
    if (!el) return;
    if (openDbs.has(id)) {
        el.style.display = 'none';
        if (chev) chev.style.transform = 'rotate(0deg)';
        openDbs.delete(id);
    } else {
        el.style.display = 'block';
        if (chev) chev.style.transform = 'rotate(90deg)';
        openDbs.add(id);
    }
}
</script>
@endif

<style>
.hidden { display:none; }
.field-label { display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151; }
.text-right { text-align:right; }
.clay-table th, .clay-table td { white-space:nowrap; }
.badge { display:inline-block;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:700; }
.badge-danger { background:#fee2e2;color:#991b1b; }
.badge-info { background:#dbeafe;color:#1e40af; }
.badge-dashboard { background:#e0e7ff;color:#3730a3; }
.clay-btn-xs { padding:2px 8px !important;font-size:.65rem !important;min-height:0 !important; }
.modal-kiriman { position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:16px; }
.modal-kiriman.active { display:flex; }
.modal-kiriman .modal-backdrop { position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px); }
.modal-kiriman .modal-container { position:relative;background:#fff;border-radius:20px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:modalIn .25s ease;display:flex;flex-direction:column; }
.modal-kiriman .modal-container-sm { max-width:500px; }
.modal-kiriman .modal-container-lg { max-width:1080px;max-height:90vh; }
.modal-kiriman .modal-header { display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid #e5e7eb;flex-shrink:0; }
.modal-kiriman .modal-header h2 { margin:0;font-size:.95rem;font-weight:800; }
.modal-kiriman .modal-close { background:none;border:none;font-size:1.1rem;cursor:pointer;padding:4px 8px;border-radius:6px;color:#6b7280; }
.modal-kiriman .modal-close:hover { background:#f3f4f6; }
.modal-kiriman .modal-body { flex:1;overflow-y:auto;padding:16px 24px 20px; }
.modal-kiriman .modal-footer { display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid #e5e7eb;flex-shrink:0; }
.modal-kiriman .dropzone-wrap { position:relative; }
.modal-kiriman .processing-overlay { position:absolute;inset:0;z-index:2;background:rgba(255,255,255,.92);display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:16px;gap:8px; }
.modal-kiriman .spinner { width:32px;height:32px;border:3px solid #e5e7eb;border-top-color:var(--color-primary,#FF6B6B);border-radius:50%;animation:spin .6s linear infinite; }
.modal-kiriman .dropzone { border:2px dashed #d1d5db;border-radius:16px;padding:32px 24px;text-align:center;cursor:pointer;transition:border-color .2s; }
.modal-kiriman .dropzone:hover { border-color:#9ca3af; }
.modal-kiriman .dropzone-icon { font-size:2.2rem;display:block;margin-bottom:6px; }
.modal-kiriman .dropzone-title { font-weight:700;font-size:.9rem;color:#374151;margin-bottom:4px; }
.modal-kiriman .dropzone-hint { font-size:.7rem;color:#9ca3af; }
.modal-kiriman .dropzone-file { font-size:.75rem;color:var(--color-primary);font-weight:600;margin-top:4px; }
.modal-kiriman .dropzone input[type="file"] { position:absolute;inset:0;opacity:0;cursor:pointer; }
.modal-kiriman .upload-error { padding:8px 12px;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:.75rem;margin-top:8px; }
.modal-kiriman .stat-card { padding:8px 14px;border-radius:10px;font-weight:700;font-size:.78rem; }
@keyframes modalIn { from { opacity:0;transform:scale(.95) translateY(10px); } to { opacity:1;transform:scale(1) translateY(0); } }
@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endsection
