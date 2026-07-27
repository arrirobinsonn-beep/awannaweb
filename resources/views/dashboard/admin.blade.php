@extends('layouts.app')
@section('title','Dashboard Admin')
@section('page-title','📊 Dashboard Admin')
@section('page-subtitle','Overview paket & pengiriman')

@section('content')

{{-- Source tabs --}}
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
    @php
        $sources = ['all' => 'Semua', 'FLIK' => 'FLIK', 'SPX' => 'SPX', 'SICEPAT' => 'SICEPAT'];
    @endphp
    @foreach($sources as $val => $label)
    <a href="{{ route('dashboard', ['source' => $val]) }}"
       class="clay-btn {{ $source === $val ? 'clay-btn-primary' : 'clay-btn-outline' }}"
       style="font-size:.78rem;padding:6px 14px;">
        {{ $label }}
    </a>
    @endforeach

    <span style="font-size:.75rem;color:#9ca3af;margin-left:8px;">|</span>

    <div style="display:flex;gap:4px;flex-wrap:wrap;">
        <a href="{{ route('dashboard', ['source' => $source, 'dari' => now()->format('Y-m-d'), 'sampai' => now()->format('Y-m-d')]) }}" class="clay-btn clay-btn-outline" style="font-size:.68rem;padding:4px 10px;">Hari ini</a>
        <a href="{{ route('dashboard', ['source' => $source, 'dari' => now()->subDays(7)->format('Y-m-d'), 'sampai' => now()->format('Y-m-d')]) }}" class="clay-btn clay-btn-outline" style="font-size:.68rem;padding:4px 10px;">7 hari</a>
        <a href="{{ route('dashboard', ['source' => $source, 'dari' => now()->startOfMonth()->format('Y-m-d'), 'sampai' => now()->format('Y-m-d')]) }}" class="clay-btn clay-btn-outline" style="font-size:.68rem;padding:4px 10px;">Bulan ini</a>
        <a href="{{ route('dashboard', ['source' => $source, 'dari' => now()->subMonth()->startOfMonth()->format('Y-m-d'), 'sampai' => now()->subMonth()->endOfMonth()->format('Y-m-d')]) }}" class="clay-btn clay-btn-outline" style="font-size:.68rem;padding:4px 10px;">Bulan lalu</a>
    </div>

    <form id="periodeForm" method="GET" action="{{ route('dashboard') }}" style="display:flex;gap:6px;align-items:center;">
        <input type="hidden" name="source" value="{{ $source }}">
        <input type="date" name="dari" id="dari" value="{{ $dari }}" style="font-size:.75rem;padding:5px 8px;border:1px solid #d1d5db;border-radius:8px;background:#fff;">
        <span style="font-size:.75rem;color:#6b7280;">s/d</span>
        <input type="date" name="sampai" id="sampai" value="{{ $sampai }}" style="font-size:.75rem;padding:5px 8px;border:1px solid #d1d5db;border-radius:8px;background:#fff;">
        <button type="submit" class="clay-btn clay-btn-primary" style="font-size:.72rem;padding:5px 12px;">Terapkan</button>
    </form>
</div>

<div class="grid-stats" style="margin-bottom:20px;">
    @php
        $colors = ['#3b82f6','#f59e0b','#ef4444','#06b6d4','#10b981','#dc2626'];
        $idx = 0;
    @endphp
    @foreach($paketStats as $key => $stat)
    @php
        $c = $colors[$idx % count($colors)];
        $darker = '#'.dechex(max(0, hexdec(substr($c,1,2))-30)).dechex(max(0, hexdec(substr($c,3,2))-30)).dechex(max(0, hexdec(substr($c,5,2))-30));
        $idx++;
    @endphp
    <div class="stat-card" data-key="{{ $key }}"
         onclick="loadDetail('{{ $key }}')"
         style="cursor:pointer;background:linear-gradient(135deg,{{ $c }}dd,{{ $c }}88);box-shadow:4px 4px 0 {{ $darker }};border-color:{{ $darker }};color:#fff;transition:all .25s ease;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='6px 6px 0 {{ $darker }}';"
         onmouseout="if(!this.classList.contains('active-card')){this.style.transform='none';this.style.boxShadow='4px 4px 0 {{ $darker }}';}">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">{{ $stat['label'] }}</div>
        <div style="font-size:2rem;font-weight:900;" data-counter="{{ $stat['total'] }}">0</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">{{ $stat['icon'] }} Paket</div>
        <div style="position:absolute;right:14px;top:14px;font-size:2.5rem;opacity:.15;pointer-events:none;">{{ $stat['icon'] }}</div>
    </div>
    @endforeach
</div>

{{-- Stok Kritis --}}
<div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="{{ route('gudang.stok') }}" class="clay-card-sm" style="padding:12px 18px;display:flex;align-items:center;gap:10px;text-decoration:none;background:linear-gradient(135deg,#fef2f2,#fee2e2);border:2px solid #fecaca;flex:1;">
        <span style="font-size:1.4rem;">⚠️</span>
        <div>
            <div style="font-size:.85rem;font-weight:800;color:#dc2626;">{{ $stokKritis }} Stok Kritis</div>
            <div style="font-size:.7rem;color:#9ca3af;">Produk dengan stok ≤ 10 — klik untuk kelola</div>
        </div>
    </a>
    <div class="clay-card-sm" style="padding:12px 18px;display:flex;align-items:center;gap:10px;background:#fff;border:2px dashed #3b82f6;min-width:200px;cursor:pointer;transition:all .2s;"
         onclick="document.getElementById('modal-undel-upload').classList.add('active')"
         onmouseover="this.style.borderColor='#2563eb';this.style.background='#eff6ff';"
         onmouseout="this.style.borderColor='#3b82f6';this.style.background='#fff';">
        <span style="font-size:1.4rem;">📥</span>
        <div>
            <div style="font-size:.8rem;font-weight:700;color:#2563eb;">Upload Excel Status Undel</div>
            <div style="font-size:.68rem;color:#9ca3af;">Update status paket dari file Excel</div>
        </div>
    </div>
</div>

{{-- Detail container --}}
<div id="detail-container" style="display:none;"></div>

<style>
.stat-card.active-card {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
}
.stat-card.active-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 4px;
    background: rgba(255,255,255,0.5);
    border-radius: 0 0 20px 20px;
}
.detail-table { width:100%; border-collapse:collapse; font-size:.72rem; }
.detail-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#6b7280; font-weight:700; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
.detail-table td { padding:7px 10px; border-bottom:1px solid #f1f5f9; }
.detail-table tr:hover td { background:#f8fafc; }
</style>

<script>
var activeKategori = null;
var currentSource = '{{ $source }}';

function loadDetail(kategori) {
    if (activeKategori === kategori) {
        document.getElementById('detail-container').style.display = 'none';
        document.querySelectorAll('.stat-card').forEach(function(c) { c.classList.remove('active-card'); c.style.transform = 'none'; c.style.boxShadow = ''; });
        activeKategori = null;
        return;
    }

    activeKategori = kategori;
    var container = document.getElementById('detail-container');

    document.querySelectorAll('.stat-card').forEach(function(c) {
        c.classList.remove('active-card');
        c.style.transform = 'none';
        c.style.boxShadow = '';
        if (c.dataset.key === kategori) c.classList.add('active-card');
    });

    container.innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af;">⏳ Memuat data...</div>';
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });

    fetch('{{ route('dashboard.paket-detail') }}?kategori=' + kategori + '&source=' + currentSource + '&dari=' + encodeURIComponent(document.getElementById('dari').value) + '&sampai=' + encodeURIComponent(document.getElementById('sampai').value))
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success || !res.records.length) {
                container.innerHTML = '<div class="clay-card" style="padding:40px;text-align:center;color:#9ca3af;font-size:.8rem;">Tidak ada data untuk kategori ini.</div>';
                return;
            }

            var html = '<div class="clay-card" style="padding:16px;overflow-x:auto;">';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">';
            html += '    <div style="font-weight:800;font-size:.85rem;">📋 Detail ' + getLabel(kategori) + ' <span style="font-weight:400;color:#9ca3af;font-size:.7rem;">(' + res.total + ' record)</span></div>';
            html += '    <button class="clay-btn clay-btn-xs clay-btn-outline" onclick="closeDetail()">✕ Tutup</button>';
            html += '</div>';
            html += '<table class="detail-table">';
            html += '<thead><tr><th>AWB</th><th>Kurir</th><th>Status</th><th>Keterangan</th><th>Tanggal</th><th>Produk</th><th>Shopper</th><th>Kota</th><th class="text-right">Harga</th></tr></thead><tbody>';

            res.records.forEach(function(r) {
                html += '<tr><td><strong>' + (r.awb || '-') + '</strong></td><td>' + (r.kurir || '-') + '</td><td>' + (r.status || '-') + '</td><td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + (r.catatan_kurir || '-') + '">' + (r.catatan_kurir || '-') + '</td><td>' + (r.tanggal || '-') + '</td><td>' + (r.nama_produk || '-') + '</td><td>' + (r.nama_shopper || '-') + '</td><td>' + (r.kota || '-') + '</td><td class="text-right">' + (r.harga ? 'Rp ' + numberFormat(r.harga) : '-') + '</td></tr>';
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        })
        .catch(function(err) {
            container.innerHTML = '<div class="clay-card" style="padding:40px;text-align:center;color:#dc2626;font-size:.8rem;">Gagal memuat data: ' + err.message + '</div>';
        });
}

function closeDetail() {
    document.getElementById('detail-container').style.display = 'none';
    document.querySelectorAll('.stat-card').forEach(function(c) { c.classList.remove('active-card'); c.style.transform = 'none'; c.style.boxShadow = ''; });
    activeKategori = null;
}

function getLabel(key) {
    var labels = { 'total_paket':'Total Paket', 'proses_retur':'Proses Retur', 'retur':'Retur', 'proses_kirim':'Proses Pengiriman', 'terkirim':'Terkirim', 'bermasalah':'Bermasalah' };
    return labels[key] || key;
}

function numberFormat(x) {
    if (x === null || x === undefined) return '0';
    return Math.round(x).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-counter]').forEach(function(el) {
        var target = parseInt(el.dataset.counter);
        var current = 0;
        var step = Math.max(1, Math.floor(target / 30));
        var interval = setInterval(function() {
            current += step;
            if (current >= target) { current = target; clearInterval(interval); }
            el.textContent = numberFormat(current);
        }, 30);
    });
});
</script>

{{-- ─── Modal Upload Excel Undel ─────────────────────────────────────── --}}
<div class="modal-kiriman" id="modal-undel-upload">
    <div class="modal-backdrop" onclick="closeModal('modal-undel-upload')"></div>
    <div class="modal-container modal-container-sm">
        <div class="modal-header">
            <h2>📥 Upload Status Undel</h2>
            <button class="modal-close" onclick="closeModal('modal-undel-upload')" type="button">✕</button>
        </div>
        <div class="modal-body">
            <div style="font-size:.78rem;color:#6b7280;margin-bottom:12px;">
                File harus memiliki kolom <strong>Tracking No. (AWB)</strong> dan <strong>Tracking Status</strong>.
                Kolom <strong>HANDLE BY</strong> akan dicocokkan dengan nama CS di sistem.
            </div>
            <div class="dropzone-wrap">
                <div class="processing-overlay" id="undel-processing" style="display:none;">
                    <div class="spinner"></div>
                    <div style="font-size:.82rem;font-weight:600;color:#374151;">Memproses file...</div>
                    <div style="font-size:.7rem;color:#9ca3af;">Membaca data undel dari Excel</div>
                </div>
                <div class="dropzone" id="undel-dropzone">
                    <span class="dropzone-icon" id="undel-icon">📂</span>
                    <div class="dropzone-title">Klik atau tarik file ke sini</div>
                    <div class="dropzone-hint" id="undel-hint">.xlsx, .xls, .csv — maks 10MB</div>
                    <div class="dropzone-file" id="undel-filename" style="display:none;"></div>
                    <input type="file" id="undel-file-input" accept=".xlsx,.xls,.csv">
                </div>
            </div>
            <div class="upload-error" id="undel-error" style="display:none;margin-top:8px;padding:8px 12px;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:.75rem;"></div>
        </div>
    </div>
</div>

{{-- ─── Modal Preview Undel ──────────────────────────────────────────── --}}
<div class="modal-kiriman" id="modal-undel-preview">
    <div class="modal-backdrop" onclick="closeModal('modal-undel-preview')"></div>
    <div class="modal-container modal-container-lg">
        <div class="modal-header">
            <h2>📊 Preview Update Status Undel</h2>
            <button class="modal-close" onclick="closeModal('modal-undel-preview')" type="button">✕</button>
        </div>
        <div class="modal-body">
            <div class="preview-stats" id="undel-preview-stats" style="display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap;"></div>
            <div class="preview-alert" id="undel-preview-errors" style="display:none;margin-bottom:12px;padding:8px 12px;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:.75rem;white-space:pre-line;"></div>
            <div id="undel-preview-table-wrap" style="overflow-x:auto;"></div>
        </div>
        <div class="modal-footer">
            <button class="clay-btn clay-btn-outline" onclick="closeModal('modal-undel-preview')" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="undel-save-btn" type="button" style="display:none;">💾 Import Data</button>
        </div>
    </div>
</div>

<style>
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

<script>
// ─── Undel Upload ───────────────────────────────────────────────────
var undelFile = null;

document.getElementById('undel-file-input').addEventListener('change', function(e) {
    undelFile = e.target.files[0];
    if (!undelFile) return;
    var fn = document.getElementById('undel-filename');
    fn.textContent = undelFile.name;
    fn.style.display = 'block';
    document.getElementById('undel-icon').textContent = '✅';
    document.getElementById('undel-hint').textContent = (undelFile.size / 1024).toFixed(1) + ' KB';
    undelUploadAndPreview(undelFile);
});

var undelDz = document.getElementById('undel-dropzone');
undelDz.addEventListener('dragover', function(e) { e.preventDefault(); undelDz.style.borderColor = '#FF6B6B'; });
undelDz.addEventListener('dragleave', function(e) { undelDz.style.borderColor = '#d1d5db'; });
undelDz.addEventListener('drop', function(e) {
    e.preventDefault();
    undelDz.style.borderColor = '#d1d5db';
    if (e.dataTransfer.files.length) {
        document.getElementById('undel-file-input').files = e.dataTransfer.files;
        document.getElementById('undel-file-input').dispatchEvent(new Event('change'));
    }
});

function undelUploadAndPreview(file) {
    var processing = document.getElementById('undel-processing');
    var errorDiv = document.getElementById('undel-error');
    processing.style.display = 'flex';
    errorDiv.style.display = 'none';

    var fd = new FormData();
    fd.append('file', file);
    fd.append('_token', '{{ csrf_token() }}');

    fetch('{{ route('gudang.kiriman.excel-undel-preview') }}', {
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
        undelShowPreview(res);
    })
    .catch(function(err) {
        processing.style.display = 'none';
        errorDiv.textContent = 'Terjadi kesalahan: ' + err.message;
        errorDiv.style.display = 'block';
    });
}

function undelShowPreview(res) {
    var data = res.data || [];
    var errors = res.errors || [];

    var matched = data.filter(function(r) { return r.exists; }).length;
    var unmatched = data.length - matched;

    var statsHtml = '';
    if (errors.length) {
        statsHtml += '<div class="stat-card" style="background:#fef2f2;color:#991b1b;">⚠️ ' + errors.length + ' Error</div>';
    }
    statsHtml += '<div class="stat-card" style="background:#ecfdf5;color:#065f46;">✅ ' + data.length + ' Baris</div>';
    statsHtml += '<div class="stat-card" style="background:#eff6ff;color:#1e40af;">🔗 ' + matched + ' AWB Cocok</div>';
    if (unmatched > 0) {
        statsHtml += '<div class="stat-card" style="background:#fefce8;color:#854d0e;">⚠️ ' + unmatched + ' AWB Tidak Ditemukan</div>';
    }
    document.getElementById('undel-preview-stats').innerHTML = statsHtml;

    var errorDiv = document.getElementById('undel-preview-errors');
    if (errors.length) {
        errorDiv.innerHTML = '<strong>⚠️ ' + errors.length + ' error:</strong>\n' + errors.join('\n');
        errorDiv.style.display = 'block';
    } else {
        errorDiv.style.display = 'none';
    }

    var html = '<table class="clay-table" style="font-size:.7rem;">';
    html += '<thead><tr><th>AWB</th><th>Status</th><th>Handle By</th><th>Catatan Kurir</th><th>No Telp</th><th>Status</th></tr></thead><tbody>';
    data.forEach(function(r) {
        var statusBadge = r.exists
            ? '<span style="color:#065f46;font-weight:700;">✅ Update</span>'
            : '<span style="color:#dc2626;font-weight:700;">❌ AWB Tidak Ditemukan</span>';
        html += '<tr>';
        html += '<td><strong>' + (r.awb || '-') + '</strong></td>';
        html += '<td>' + (r.status || '-') + '</td>';
        html += '<td>' + (r.handle_by || '-') + '</td>';
        html += '<td>' + (r.catatan_kurir || '-') + '</td>';
        html += '<td>' + (r.no_telp || '-') + '</td>';
        html += '<td>' + statusBadge + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    document.getElementById('undel-preview-table-wrap').innerHTML = html;

    var saveBtn = document.getElementById('undel-save-btn');
    if (matched > 0) {
        saveBtn.style.display = 'inline-flex';
        saveBtn.onclick = function() { undelImport(undelFile); };
    } else {
        saveBtn.style.display = 'none';
    }

    closeModal('modal-undel-upload');
    document.getElementById('modal-undel-preview').classList.add('active');
}

function undelImport(file) {
    var saveBtn = document.getElementById('undel-save-btn');
    saveBtn.disabled = true;
    saveBtn.textContent = '⏳ Importing...';

    var fd = new FormData();
    fd.append('file', file);
    fd.append('_token', '{{ csrf_token() }}');

    fetch('{{ route('gudang.kiriman.excel-undel-import') }}', {
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
        var msg = '✅ ' + res.message;
        if (res.not_found && res.not_found.length) {
            msg += '\n\n⚠️ AWB tidak ditemukan (' + res.not_found.length + '):\n' + res.not_found.join(', ');
        }
        alert(msg);
        closeModal('modal-undel-preview');
        location.reload();
    })
    .catch(function(err) {
        alert('Gagal: ' + err.message);
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Import Data';
    });
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
</script>
@endsection