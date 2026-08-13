@extends('layouts.app')

@section('title', 'Data Mentah Order Online')
@section('page-title', 'Data Mentah Order Online')
@section('page-subtitle', 'Upload data mentah → export template Excel (FLIK / SiCepat / SPX)')

@push('styles')
<style>
    .badge-courier { font-size:.68rem; font-weight:800; padding:2px 8px; border-radius:8px; white-space:nowrap; }
    .cou-flix-tf   { background:#dbeafe; color:#1d4ed8; }
    .cou-flix-idx  { background:#e0f2fe; color:#0369a1; }
    .cou-flix-sicepat { background:#dcfce7; color:#15803d; }
    .cou-sicepat  { background:#a7f3d0; color:#047857; }
    .cou-flix-spx  { background:#ede9fe; color:#6d28d9; }
    .cou-spx       { background:#f3e8ff; color:#7e22ce; }
    .cou-undeliverable { background:#fee2e2; color:#b91c1c; }
    .badge-order-status { font-size:.65rem; font-weight:700; padding:2px 7px; border-radius:7px; white-space:nowrap; }
    .st-real { background:#dcfce7; color:#15803d; }
    .st-tembakan { background:#dbeafe; color:#1d4ed8; }
    .st-belum_diproses { background:#fef3c7; color:#92400e; }
    .st-cancel { background:#f3e8ff; color:#6d28d9; }
    .st-duplikat { background:#fee2e2; color:#b91c1c; }
    .stock-note { font-size:.65rem; color:#b45309; margin-top:2px; }
    .badge-batch-status { font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:8px; }
    .st-pending { background:#fef3c7; color:#92400e; }
    .st-processing { background:#dbeafe; color:#1d4ed8; }
    .st-completed { background:#dcfce7; color:#15803d; }
    .st-failed { background:#fee2e2; color:#b91c1c; }

    .dropzone {
        border: 2px dashed #d1d5db; border-radius: 14px;
        padding: 30px 20px; text-align: center;
        transition: all .25s ease; cursor: pointer; background: #fafafa;
    }
    .dropzone:hover, .dropzone.drag-over {
        border-color: var(--color-primary, #FF6B6B); background: #fef2f2;
    }
    .dropzone.has-file { border-color: #059669; background: #f0fdf4; }
    .dropzone-icon { font-size: 2rem; margin-bottom: 4px; display: block; }
    .dropzone-title { font-weight: 700; font-size: .9rem; color: #374151; }
    .dropzone-hint  { font-size: .72rem; color: #9ca3af; margin-top: 2px; }
    .dropzone-file  { font-size: .78rem; color: #059669; font-weight: 600; margin-top: 6px; }
    .courier-edit-form { display:flex; gap:4px; align-items:center; }
    .courier-edit-form select { padding:2px 4px; font-size:.72rem; border:1px solid #d1d5db; border-radius:6px; }

    /* ── Card upload berdampingan ── */
    .upload-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; margin-bottom:20px; }
    .upload-grid .clay-card { display:flex; flex-direction:column; }
    .upload-grid .dropzone { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; }
    @media (max-width: 900px) { .upload-grid { grid-template-columns:1fr; } }

    /* ── Riwayat upload: zebra stripe (jejak aktivitas) ── */
    .batch-panel { display:flex; flex-direction:column; }
    .batch-list { flex:1; min-height:260px; overflow-y:auto; }
    .batch-item { display:block; padding:10px 16px; text-decoration:none; border-bottom:1px solid rgba(0,0,0,.05); transition:background .15s ease; }
    .batch-item:nth-child(odd)  { background:#fff; }
    .batch-item:nth-child(even) { background:#faf6f6; }
    .batch-item:hover { background:#fff5f5; }
    .batch-panel .batch-item.selected { background:#fff0f0; box-shadow:inset 3px 0 0 var(--color-primary,#FF6B6B); }
</style>
@endpush

@section('content')

{{-- Upload CSV & Status Aggregator — berdampingan --}}
<div class="upload-grid">
<div class="clay-card" style="padding:20px 24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;font-size:1.05rem;font-weight:800;">📥 Upload Data Mentah</h2>
            <div style="font-size:.75rem;color:#9ca3af;">Unggah file order online (.csv). Setelah masuk, courier terisi otomatis dari rules, lalu bisa diekspor ke template Excel.</div>
        </div>
    </div>

    <div style="margin-bottom:12px;max-width:420px;">
        <label for="csv-sender" style="display:block;font-size:.78rem;font-weight:700;color:#374151;margin-bottom:4px;">Nama Pengirim <span style="color:#b91c1c;">*</span></label>
        <input type="text" id="csv-sender" required placeholder="contoh: eresgestore"
               style="width:100%;padding:8px 10px;font-size:.82rem;border:1px solid #d1d5db;border-radius:8px;">
        <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Nama pengirim/warehouse — dipakai sebagai "Kode Warehouse" pada export FLIK.</div>
    </div>

    <div class="dropzone" id="csv-dropzone">
        <span class="dropzone-icon" id="csv-icon">📂</span>
        <div class="dropzone-title">Klik atau tarik file CSV ke sini</div>
        <div class="dropzone-hint" id="csv-hint">.csv — maks 10MB. Kolom order_id, product, name, phone, address, provinsi, dst.</div>
        <div class="dropzone-file" id="csv-filename" style="display:none;"></div>
    </div>
    <input type="file" id="csv-file" accept=".csv,text/csv,text/plain" style="display:none;">

    <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
        <button type="button" id="btn-preview" class="clay-btn clay-btn-primary">👁 Preview</button>
        <button type="button" id="btn-import" class="clay-btn">📥 Import</button>
    </div>

    <div id="import-result" style="margin-top:14px;display:none;"></div>
</div>

{{-- Upload Status Aggregator --}}
<div class="clay-card" style="padding:20px 24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;font-size:1.05rem;font-weight:800;">📡 Upload Status Aggregator</h2>
            <div style="font-size:.75rem;color:#9ca3af;">Upload file dari dashboard FLIK / SiCepat / SPX (.csv/.xlsx). Mengisi kolom resi (AWB), status pengiriman & tanggal terkirim; saat status <b>returned</b>, stok yang keluar saat export otomatis dikembalikan.</div>
        </div>
    </div>

    <div class="dropzone" id="track-dropzone">
        <span class="dropzone-icon" id="track-icon">📡</span>
        <div class="dropzone-title">Klik atau tarik file dashboard aggregator ke sini</div>
        <div class="dropzone-hint" id="track-hint">.csv / .xlsx — maks 10MB. Sumber (FLIK/SiCepat/SPX) dideteksi otomatis dari header.</div>
        <div class="dropzone-file" id="track-filename" style="display:none;"></div>
    </div>
    <input type="file" id="track-file" accept=".csv,.xlsx,.xls,text/csv,text/plain" style="display:none;">

    <div style="margin-top:14px;">
        <button type="button" id="btn-track-import" class="clay-btn clay-btn-primary">📡 Import Status</button>
    </div>

    <div id="track-result" style="margin-top:14px;display:none;"></div>
</div>
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

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px;align-items:stretch;">
    {{-- Daftar batch --}}
    <div class="clay-card batch-panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;font-weight:800;font-size:.9rem;border-bottom:1px solid rgba(0,0,0,.06);">🗂 Riwayat Upload</div>
        <div class="batch-list">
            @forelse($batches as $b)
                <a href="{{ route('orders.index', ['batch' => $b->id]) }}"
                   class="batch-item {{ $selectedBatch && $selectedBatch->id === $b->id ? 'selected' : '' }}">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                        <span style="font-size:.78rem;font-weight:700;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">🗓 {{ $b->created_at?->format('d M Y H:i') }}</span>
                        <span class="badge-batch-status st-{{ $b->status }}">{{ $b->status }}</span>
                    </div>
                    <div style="font-size:.68rem;color:#9ca3af;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $b->original_filename }} &nbsp;•&nbsp; {{ $b->total_rows }} baris{{ $b->sender ? ' &nbsp;•&nbsp; '.$b->sender : '' }}
                    </div>
                </a>
            @empty
                <div style="padding:16px;font-size:.78rem;color:#9ca3af;">Belum ada upload.</div>
            @endforelse
        </div>
        <div style="padding:10px 16px;border-top:1px solid rgba(0,0,0,.05);font-size:.72rem;">{{ $batches->links() }}</div>
    </div>

    {{-- Detail batch terpilih --}}
    <div>
        @if($selectedBatch)
            <div class="clay-card" style="padding:0;overflow:hidden;">
                <div style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;border-bottom:1px solid rgba(0,0,0,.06);">
                    <div>
                        <h2 style="margin:0;font-size:1rem;font-weight:800;">{{ $selectedBatch->original_filename }}</h2>
                        <div style="font-size:.72rem;color:#9ca3af;">{{ $selectedBatch->created_at?->format('d/m/Y H:i') }} • Total {{ $selectedBatch->total_rows }} • Sukses {{ $selectedBatch->success_rows }}</div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        @foreach($exportTemplates as $et)
                            @if($et->key === \App\Services\OrderTemplateExportService::TEMPLATE_FLIK)
                                <details style="position:relative;">
                                    <summary class="clay-btn" style="padding:6px 12px;font-size:.78rem;cursor:pointer;">📗 Export {{ $et->name }} ▾</summary>
                                    <div style="position:absolute;right:0;top:100%;margin-top:4px;background:#fff;border:1px solid #eee;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,.12);min-width:200px;z-index:50;padding:6px;">
                                        @php
                                            $flikWithData = array_filter(\App\Services\OrderTemplateExportService::FLIK_COURIERS, fn ($fc) => ($courierCounts[$fc] ?? 0) > 0);
                                        @endphp
                                        @forelse($flikWithData as $fc)
                                            <a href="{{ route('orders.export', [$selectedBatch->id, 'flik', $fc]) }}" style="display:block;padding:7px 10px;font-size:.76rem;color:#374151;text-decoration:none;border-radius:6px;">
                                                {{ $et->name }} — {{ $fc }} <span style="color:#9ca3af;">({{ $courierCounts[$fc] }})</span>
                                            </a>
                                        @empty
                                            <div style="padding:7px 10px;font-size:.74rem;color:#9ca3af;">Belum ada data {{ $et->name }}.</div>
                                        @endforelse
                                    </div>
                                </details>
                            @else
                                @php
                                    $etIcon = $et->key === 'sicepat' ? '📘' : ($et->key === 'spx' ? '📙' : '📦');
                                @endphp
                                <a href="{{ route('orders.export', [$selectedBatch->id, $et->key]) }}" class="clay-btn" style="padding:6px 12px;font-size:.78rem;">{{ $etIcon }} Export {{ $et->name }}</a>
                            @endif
                        @endforeach
                    </div>
                </div>

                <form method="GET" action="{{ route('orders.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:12px 20px;">
                    <input type="hidden" name="batch" value="{{ $selectedBatch->id }}">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari order / nama / telp" class="clay-input" style="flex:1;min-width:160px;">
                    <select name="courier" class="clay-input">
                        <option value="">Semua Courier</option>
                        @foreach($courierList as $c)
                            <option value="{{ $c }}" @selected(request('courier') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="clay-input">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\ShippingOrder::STATUSES as $st)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                    <select name="product_code" class="clay-input">
                        <option value="">Semua Produk</option>
                        @foreach($productOptions as $code => $label)
                            <option value="{{ $code }}" @selected(request('product_code') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="clay-btn clay-btn-primary" type="submit">🔍 Filter</button>
                    <a href="{{ route('orders.index', ['batch' => $selectedBatch->id]) }}" class="clay-btn">Reset</a>
                </form>

                <div class="table-scroll">
                    <table class="clay-table" style="min-width:1000px;">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Nama</th>
                                <th>Telp</th>
                                <th>Provinsi</th>
                                <th>Produk</th>
                                <th>Status</th>
                                <th>Pay</th>
                                <th>Courier</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $o)
                                <tr>
                                    <td class="sel-nowrap" style="font-size:.75rem;">{{ $o->order_id }}</td>
                                    <td><a href="{{ route('orders.show', $o->id) }}" style="color:var(--color-primary,#FF6B6B);font-weight:700;text-decoration:none;">{{ $o->customer_name }}</a></td>
                                    <td class="sel-nowrap" style="font-size:.75rem;"><a href="{{ route('orders.show', $o->id) }}" style="color:var(--color-primary,#FF6B6B);font-weight:700;text-decoration:none;">{{ $o->phone }}</a></td>
                                    <td style="font-size:.78rem;">{{ $o->province }}</td>
                                    <td>
                                        <div style="font-size:.78rem;">{{ $o->product_name }}</div>
                                        @if($o->product_code)
                                            <div style="font-size:.65rem;color:#6b7280;">{{ $o->product_code }}</div>
                                        @endif
                                        @if($o->stock_note)
                                            <div class="stock-note">⚠ {{ $o->stock_note }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge-order-status st-{{ $o->status }}">{{ $o->status ? str_replace('_', ' ', ucwords($o->status, '_')) : '-' }}</span>
                                    </td>
                                    <td style="font-size:.75rem;">{{ strtoupper($o->payment_method ?? '-') }}</td>
                                    <td>
                                        <span class="badge-courier cou-{{ $o->courier }}">{{ $o->courier ?? '-' }}</span>
                                        @if($o->courier === 'undeliverable' && $o->courier_note)
                                            <div style="font-size:.65rem;color:#b91c1c;margin-top:2px;">{{ $o->courier_note }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($o->awb))
                                            <div>
                                                <span class="badge-courier" style="background:#d1fae5;color:#065f46;">✓ {{ $o->awb }}</span>
                                                @if($o->aggregator_status)
                                                    <div style="font-size:.65rem;color:#047857;margin-top:2px;">{{ str_replace('_', ' ', $o->aggregator_status) }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <details style="font-size:.78rem;">
                                                <summary style="cursor:pointer;color:var(--color-primary,#FF6B6B);font-weight:700;">Edit</summary>
                                                <form method="POST" action="{{ route('orders.update', $o->id) }}" class="courier-edit-form" style="margin-top:6px;flex-wrap:wrap;">
                                                    @csrf @method('PUT')
                                                    <select name="courier">
                                                        <option value="">— Pilih —</option>
                                                        @foreach(\App\Services\CourierRuleService::COURIERS as $cc)
                                                            <option value="{{ $cc }}" @selected($o->courier === $cc)>{{ $cc }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="courier_note" value="{{ $o->courier_note }}" placeholder="Catatan" style="width:110px;padding:2px 4px;font-size:.72rem;border:1px solid #d1d5db;border-radius:6px;">
                                                    <select name="product_code">
                                                        <option value="">— Produk —</option>
                                                        @foreach($products as $p)
                                                            @foreach($p->variants as $v)
                                                                <option value="{{ $v->code }}" @selected($o->product_code === $v->code)>{{ $v->code }} — {{ $p->name }}</option>
                                                            @endforeach
                                                        @endforeach
                                                    </select>
                                                    <button class="clay-btn clay-btn-primary" style="padding:2px 8px;font-size:.72rem;">Simpan</button>
                                                </form>
                                            </details>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:24px;">Belum ada order di batch ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="padding:12px 20px;">{{ $orders->links() }}</div>
            </div>
        @else
            <div class="clay-card" style="padding:40px;text-align:center;color:#9ca3af;">
                Pilih sebuah batch upload di sebelah kiri untuk melihat & mengekspor datanya.
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const fileInput = document.getElementById('csv-file');
    const dropzone  = document.getElementById('csv-dropzone');
    const csvIcon   = document.getElementById('csv-icon');
    const csvHint   = document.getElementById('csv-hint');
    const csvFilename = document.getElementById('csv-filename');
    const senderInput = document.getElementById('csv-sender');
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
        csvHint.textContent = '.csv — maks 10MB. Kolom order_id, product, name, phone, address, provinsi, dst.';
    }

    dropzone.addEventListener('click', function () { fileInput.click(); });

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
        if (this.files.length > 0) setFile(this.files[0].name, this.files[0].size);
        else resetDropzone();
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

    function showResult(msg, ok) {
        resultBox.style.display = 'block';
        resultBox.className = ok ? 'clay-alert clay-alert-success' : 'clay-alert clay-alert-error';
        resultBox.innerHTML = '<span>' + (ok ? '✅' : '⚠️') + '</span>' +
            '<span style="flex:1;">' + esc(msg) + '</span>' +
            '<button onclick="this.parentElement.style.display=\'none\'" style="background:none;border:none;cursor:pointer;">✕</button>';
    }

    document.getElementById('btn-preview').addEventListener('click', function () {
        const f = getFile(); if (!f) return;
        const sender = senderInput.value.trim();
        if (!sender) { alert('Isi Nama Pengirim terlebih dahulu.'); senderInput.focus(); return; }
        showResult('Memproses file...', true);
        const fd = new FormData(); fd.append('file', f); fd.append('sender', sender);
        fetch('{{ route("orders.preview") }}', {
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
            let html = '<div style="margin-bottom:10px;font-weight:700;">Total: ' + d.total + ' baris</div>';

            if (d.unknown_cs && d.unknown_cs.length) {
                html += '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;margin-bottom:10px;">' +
                    '<div style="font-size:.75rem;font-weight:700;color:#b91c1c;margin-bottom:4px;">CS tidak dikenal (' + d.unknown_cs.length + '): ' +
                    d.unknown_cs.map(esc).join(', ') + '</div></div>';
            }
            if (d.errors && d.errors.length) {
                html += '<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:8px 12px;margin-bottom:10px;font-size:.75rem;color:#92400e;">' +
                    d.errors.slice(0, 5).map(esc).join('<br>') + '</div>';
            }

            if (d.sampel && d.sampel.length) {
                html += '<table class="clay-table"><thead><tr><th>Order</th><th>Nama</th><th>Provinsi</th><th>Produk</th><th>Payment</th></tr></thead><tbody>' +
                    d.sampel.map(function (r) {
                        return '<tr><td>' + esc(r.order_id) + '</td><td>' + esc(r.customer_name) + '</td><td>' + esc(r.province) +
                               '</td><td>' + esc(r.product_name) + '</td><td>' + esc(r.payment_method) + '</td></tr>';
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
        const sender = senderInput.value.trim();
        if (!sender) { alert('Isi Nama Pengirim terlebih dahulu.'); senderInput.focus(); return; }
        showResult('Mengimport...', true);
        const fd = new FormData(); fd.append('file', f); fd.append('sender', sender);
        fetch('{{ route("orders.import") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (!d.success) { showResult(d.message || 'Gagal import.', false); return; }
            showResult(d.message, true);
            senderInput.value = '';
            setTimeout(function () { window.location.reload(); }, 900);
        })
        .catch(function () { showResult('Gagal import.', false); });
    });

    window.closePreview = function () { document.getElementById('preview-modal').style.display = 'none'; };
})();

(function () {
    const fileInput = document.getElementById('track-file');
    const dropzone  = document.getElementById('track-dropzone');
    const trackIcon = document.getElementById('track-icon');
    const trackHint = document.getElementById('track-hint');
    const trackFilename = document.getElementById('track-filename');
    const resultBox = document.getElementById('track-result');

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    dropzone.addEventListener('click', function () { fileInput.click(); });

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
            dropzone.classList.add('has-file');
            trackIcon.textContent = '✅';
            trackFilename.style.display = 'block';
            trackFilename.textContent = '📄 ' + this.files[0].name + ' (' + formatSize(this.files[0].size) + ')';
            trackHint.textContent = 'File siap. Klik Import Status.';
        } else {
            dropzone.classList.remove('has-file');
            trackIcon.textContent = '📡';
            trackFilename.style.display = 'none';
            trackHint.textContent = '.csv / .xlsx — maks 10MB. Sumber (FLIK/SiCepat/SPX) dideteksi otomatis dari header.';
        }
    });

    document.getElementById('btn-track-import').addEventListener('click', function () {
        if (!fileInput.files.length) { alert('Pilih file terlebih dahulu.'); return; }
        const fd = new FormData();
        fd.append('file', fileInput.files[0]);
        resultBox.style.display = 'block';
        resultBox.className = 'clay-alert clay-alert-success';
        resultBox.innerHTML = '<span>⏳</span><span>Mengimport status... mohon tunggu.</span>';
        fetch('{{ route("orders.tracking-import") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            const ok = !!d.success;
            resultBox.className = ok ? 'clay-alert clay-alert-success' : 'clay-alert clay-alert-error';
            resultBox.innerHTML = '<span>' + (ok ? '✅' : '⚠️') + '</span>' +
                '<span style="flex:1;">' + (d.message || 'Selesai.') + '</span>' +
                '<button onclick="this.parentElement.style.display=\'none\'" style="background:none;border:none;cursor:pointer;">✕</button>';
            if (ok) setTimeout(function () { window.location.reload(); }, 1200);
        })
        .catch(function () {
            resultBox.className = 'clay-alert clay-alert-error';
            resultBox.innerHTML = '<span>⚠️</span><span>Gagal import tracking.</span>';
        });
    });
})();
</script>
@endpush
