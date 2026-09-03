@extends('layouts.app')

@section('title', 'Pengiriman')
@section('page-title', 'Pengiriman')
@section('page-subtitle', 'Upload data mentah → export template Excel (FLIK / SiCepat / SPX)')

@push('styles')
<style>
/* ── Badge courier ── */
.badge-courier { font-size:.68rem; font-weight:800; padding:2px 8px; border-radius:8px; white-space:nowrap; }
.cou-flix-tf        { background:#dbeafe; color:#1d4ed8; }
.cou-flix-idx       { background:#e0f2fe; color:#0369a1; }
.cou-flix-sicepat   { background:#dcfce7; color:#15803d; }
.cou-sicepat        { background:#a7f3d0; color:#047857; }
.cou-flix-spx       { background:#ede9fe; color:#6d28d9; }
.cou-spx            { background:#f3e8ff; color:#7e22ce; }
.cou-undeliverable  { background:#fee2e2; color:#b91c1c; }

/* ── Badge order status ── */
.badge-order-status { font-size:.65rem; font-weight:700; padding:2px 7px; border-radius:7px; white-space:nowrap; }
.st-real            { background:#dcfce7; color:#15803d; }
.st-tembakan        { background:#dbeafe; color:#1d4ed8; }
.st-belum_diproses  { background:#fef3c7; color:#92400e; }
.st-cancel          { background:#f3e8ff; color:#6d28d9; }
.st-duplikat        { background:#fee2e2; color:#b91c1c; }
.stock-note         { font-size:.65rem; color:#b45309; margin-top:2px; }
.badge-batch-status { font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:8px; }
.st-pending         { background:#fef3c7; color:#92400e; }
.st-processing      { background:#dbeafe; color:#1d4ed8; }
.st-completed       { background:#dcfce7; color:#15803d; }
.st-failed          { background:#fee2e2; color:#b91c1c; }

/* ── Dropzone, Mini stat, Accent colors — centralized in clay.css ── */
.clay-dropzone { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; }
.courier-edit-form  { display:flex; gap:4px; align-items:center; }
.courier-edit-form select { padding:2px 4px; font-size:.72rem; border:1px solid #d1d5db; border-radius:6px; }

/* ── Upload grid 2 kolom ── */
.upload-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; margin-bottom:20px; }
.upload-grid .clay-card { display:flex; flex-direction:column; }
.upload-grid .clay-dropzone  { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; }
@media (max-width: 900px) { .upload-grid { grid-template-columns:1fr; } }

/* ── Summary cards layout ── */
.summary-section { margin-bottom:16px; }
.summary-section-label {
    font-size:.68rem; font-weight:800; text-transform:uppercase;
    color:#9ca3af; letter-spacing:.06em; margin-bottom:8px;
}
.summary-row { display:flex; flex-wrap:wrap; gap:10px; }

/* ── Chart wrapper ── */
.chart-card {
    background:#fff; border-radius:16px; padding:20px 24px;
    box-shadow:0 2px 10px rgba(0,0,0,.06); margin-bottom:20px;
    border: 1px solid rgba(0,0,0,.05);
}
.chart-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:16px; }
.chart-title  { font-size:.95rem; font-weight:800; margin:0; }
.chart-legend { display:flex; gap:14px; flex-wrap:wrap; }
.chart-legend-item { display:flex; align-items:center; gap:5px; font-size:.72rem; font-weight:700; cursor:pointer; user-select:none; }
.chart-legend-dot  { width:10px; height:10px; border-radius:50%; }

.chart-scroll-wrap { overflow-x:auto; padding-bottom:4px; cursor:grab; }
.chart-scroll-wrap:active { cursor:grabbing; }
.chart-scroll-wrap::-webkit-scrollbar { height:5px; }
.chart-scroll-wrap::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:4px; }
.chart-inner { min-width:100%; }
</style>
@endpush

@section('content')

@if(!$isCs)
{{-- ─── Upload Panel (2 kolom) ─────────────────────────────────────── --}}
<div class="upload-grid">

  {{-- Upload Data Mentah --}}
  <div class="clay-card" style="padding:20px 24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
      <div>
        <h2 style="margin:0;font-size:1.05rem;font-weight:800;">📥 Upload Data Mentah</h2>
        <div style="font-size:.75rem;color:#9ca3af;">Unggah file order online (.csv). Courier terisi otomatis dari rules, lalu bisa diekspor ke template Excel.</div>
      </div>
    </div>

    <div style="margin-bottom:12px;">
      <label for="csv-sender" style="display:block;font-size:.78rem;font-weight:700;color:#374151;margin-bottom:4px;">Nama Pengirim <span style="color:#b91c1c;">*</span></label>
      <input type="text" id="csv-sender" required placeholder="contoh: eresgestore"
             style="width:100%;padding:8px 10px;font-size:.82rem;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;">
      <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Dipakai sebagai "Kode Warehouse" pada export FLIK.</div>
    </div>

    <div class="clay-dropzone" id="csv-dropzone">
      <span class="clay-dropzone-icon" id="csv-icon">📂</span>
      <div class="clay-dropzone-title">Klik atau tarik file CSV ke sini</div>
      <div class="clay-dropzone-hint" id="csv-hint">.csv — maks 10MB. Kolom order_id, product, name, phone, address, provinsi, dst.</div>
      <div class="clay-dropzone-file" id="csv-filename" style="display:none;"></div>
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
        <div style="font-size:.75rem;color:#9ca3af;">Upload file dari dashboard FLIK / SiCepat / SPX (.csv/.xlsx). Mengisi resi, status pengiriman &amp; tanggal terkirim.</div>
      </div>
    </div>

    <div class="clay-dropzone" id="track-dropzone">
      <span class="clay-dropzone-icon" id="track-icon">📡</span>
      <div class="clay-dropzone-title">Klik atau tarik file dashboard aggregator ke sini</div>
      <div class="clay-dropzone-hint" id="track-hint">.csv / .xlsx — maks 10MB. Sumber (FLIK/SiCepat/SPX) dideteksi otomatis.</div>
      <div class="clay-dropzone-file" id="track-filename" style="display:none;"></div>
    </div>
    <input type="file" id="track-file" accept=".csv,.xlsx,.xls,text/csv,text/plain" style="display:none;">

    <div style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <select id="track-courier" class="clay-input" style="max-width:220px;">
        <option value="">Semua Courier (tanpa filter)</option>
        @php $trackingSources = \App\Models\ExportTemplate::where('is_active', true)->get(); @endphp
        @foreach($trackingSources as $ts)
          @foreach(($ts->couriers ?? []) as $tc)
            <option value="{{ $tc }}">{{ $ts->name }} — {{ $tc }}</option>
          @endforeach
        @endforeach
      </select>
      <button type="button" id="btn-track-import" class="clay-btn clay-btn-primary">📡 Import Status</button>
    </div>
    <div id="track-result" style="margin-top:14px;display:none;"></div>
  </div>

</div>

{{-- Preview Modal --}}
<div id="preview-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:60;align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:16px;max-width:860px;width:100%;max-height:85vh;overflow:auto;padding:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
      <h3 style="margin:0;font-size:1rem;">Preview Data</h3>
      <button onclick="closePreview()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">✕</button>
    </div>
    <div id="preview-body" style="font-size:.85rem;"></div>
  </div>
</div>
@endif

{{-- ─── Summary Cards ─────────────────────────────────────────────── --}}
@php
  $STATUS_LABELS = \App\Models\ShippingOrder::STATUS_LABELS;
  $aggLabels = [
    'waiting_pickup' => 'Waiting Pickup', 'in_transit' => 'In Transit',
    'delivered' => 'Delivered', 'returning' => 'Returning',
    'returned' => 'Returned', 'problem' => 'Problem',
  ];
  $aggIcons = [
    'waiting_pickup' => '📦', 'in_transit' => '🚚', 'delivered' => '✅',
    'returning' => '↩️', 'returned' => '🔄', 'problem' => '⚠️',
  ];

  // Hitung Lead = total − real − tembakan
  $summaryLead = $summaryTotal
    - ($summaryByStatus->get('real', 0) + $summaryByStatus->get('tembakan', 0));
@endphp

{{-- Baris 1: Angka utama ── --}}
<div class="summary-section">
  <div class="summary-section-label">📊 Ringkasan Order</div>
  <div class="summary-row">

    {{-- Total --}}
    <div class="mini-stat ms-total" data-reveal>
      <span class="mini-stat-icon">📋</span>
      <div class="mini-stat-label">Total Order</div>
      <div class="mini-stat-value" data-counter="{{ $summaryTotal }}">{{ $summaryTotal }}</div>
      <div class="mini-stat-sub">semua status · filter aktif</div>
    </div>

    {{-- Real --}}
    <div class="mini-stat ms-real" data-reveal>
      <span class="mini-stat-icon">✅</span>
      <div class="mini-stat-label">Real</div>
      <div class="mini-stat-value" data-counter="{{ $summaryByStatus->get('real', 0) }}">{{ $summaryByStatus->get('real', 0) }}</div>
      <div class="mini-stat-sub">diproses &amp; diekspor</div>
    </div>

    {{-- Tembakan --}}
    <div class="mini-stat ms-tembakan" data-reveal>
      <span class="mini-stat-icon">🎯</span>
      <div class="mini-stat-label">Tembakan</div>
      <div class="mini-stat-value" data-counter="{{ $summaryByStatus->get('tembakan', 0) }}">{{ $summaryByStatus->get('tembakan', 0) }}</div>
      <div class="mini-stat-sub">paid pending → ekspor SPX</div>
    </div>

    {{-- Lead (sisanya) --}}
    <div class="mini-stat ms-lead" data-reveal>
      <span class="mini-stat-icon">⏳</span>
      <div class="mini-stat-label">Lead</div>
      <div class="mini-stat-value" data-counter="{{ $summaryLead }}">{{ $summaryLead }}</div>
      <div class="mini-stat-sub">belum diproses + cancel + duplikat</div>
    </div>

    {{-- Cancel --}}
    @if($summaryByStatus->get('cancel', 0) > 0)
    <div class="mini-stat ms-cancel" data-reveal>
      <span class="mini-stat-icon">❌</span>
      <div class="mini-stat-label">Cancel</div>
      <div class="mini-stat-value" data-counter="{{ $summaryByStatus->get('cancel', 0) }}">{{ $summaryByStatus->get('cancel', 0) }}</div>
      <div class="mini-stat-sub">dibatalkan</div>
    </div>
    @endif

    {{-- Duplikat --}}
    @if($summaryByStatus->get('duplikat', 0) > 0)
    <div class="mini-stat ms-duplikat" data-reveal>
      <span class="mini-stat-icon">⚠️</span>
      <div class="mini-stat-label">Duplikat</div>
      <div class="mini-stat-value" data-counter="{{ $summaryByStatus->get('duplikat', 0) }}">{{ $summaryByStatus->get('duplikat', 0) }}</div>
      <div class="mini-stat-sub">terdeteksi dobel</div>
    </div>
    @endif

  </div>
</div>

{{-- Baris 2: Per Courier ── --}}
@php
  $courierFiltered = $summaryByCourier->filter(fn ($v, $k) => $k !== null);
  $courierColors = [
    'flix-tf'       => ['#1d4ed8','#dbeafe'],
    'flix-idx'      => ['#0369a1','#e0f2fe'],
    'flix-sicepat'  => ['#047857','#dcfce7'],
    'sicepat'       => ['#047857','#a7f3d0'],
    'flix-spx'      => ['#6d28d9','#ede9fe'],
    'spx'           => ['#7e22ce','#f3e8ff'],
    'undeliverable' => ['#b91c1c','#fee2e2'],
  ];
  $courierIcons = [
    'flix-tf' => '💙', 'flix-idx' => '🔵', 'flix-sicepat' => '💚',
    'sicepat' => '🟢', 'flix-spx' => '💜', 'spx' => '🟣',
    'undeliverable' => '⛔',
  ];
@endphp
@if($courierFiltered->count())
<div class="summary-section">
  <div class="summary-section-label">🚚 Per Courier</div>
  <div class="summary-row">
    @foreach($courierFiltered as $cName => $cCount)
    @php
      $cColor = $courierColors[$cName] ?? ['#0ea5e9','#e0f2fe'];
      $cIcon  = $courierIcons[$cName] ?? '🚚';
    @endphp
    <div class="mini-stat" style="border-left:4px solid {{ $cColor[0] }};" data-reveal>
      <span class="mini-stat-icon" style="font-size:1.4rem;">{{ $cIcon }}</span>
      <div class="mini-stat-label" style="color:{{ $cColor[0] }};opacity:1;">{{ $cName }}</div>
      <div class="mini-stat-value" style="color:{{ $cColor[0] }};" data-counter="{{ $cCount }}">{{ $cCount }}</div>
      <div class="mini-stat-sub">order</div>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- Baris 3: Aggregator Status ── --}}
@if($summaryByAggregator->filter(fn ($v, $k) => $k !== null)->count())
<div class="summary-section">
  <div class="summary-section-label">📡 Status Pengiriman (Aggregator)</div>
  <div class="summary-row">
    @foreach($summaryByAggregator->filter(fn ($v, $k) => $k !== null) as $aName => $aCount)
    <div class="mini-stat ms-agg" data-reveal>
      <span class="mini-stat-icon">{{ $aggIcons[$aName] ?? '📡' }}</span>
      <div class="mini-stat-label">{{ $aggLabels[$aName] ?? $aName }}</div>
      <div class="mini-stat-value" data-counter="{{ $aCount }}">{{ $aCount }}</div>
      <div class="mini-stat-sub">resi</div>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- ─── Grafik Tren Order ────────────────────────────────────────── --}}
@if($chartData->count() > 0)
<div class="chart-card" style="margin-bottom:20px;">
  <div class="chart-header">
    <h2 class="chart-title">📈 Tren Order Harian</h2>
    <div class="chart-legend" id="chart-legend">
      <span class="chart-legend-item" data-dataset="0">
        <span class="chart-legend-dot" style="background:#6366f1;"></span> Total Masuk
      </span>
      <span class="chart-legend-item" data-dataset="1">
        <span class="chart-legend-dot" style="background:#10b981;"></span> Real
      </span>
      <span class="chart-legend-item" data-dataset="2">
        <span class="chart-legend-dot" style="background:#3b82f6;"></span> Tembakan
      </span>
      <span class="chart-legend-item" data-dataset="3">
        <span class="chart-legend-dot" style="background:#f59e0b;"></span> Lead
      </span>
    </div>
  </div>

  {{-- Scroll container (geser kiri-kanan jika tanggal banyak) --}}
  <div class="chart-scroll-wrap" id="chart-scroll-wrap">
    <div class="chart-inner" id="chart-inner">
      <canvas id="orderTrendChart" height="220"></canvas>
    </div>
  </div>

  <div style="font-size:.68rem;color:#c0c0c0;margin-top:8px;text-align:center;">
    💡 Geser grafik ke kiri/kanan untuk melihat rentang tanggal lebih banyak. Klik legend untuk sembunyikan/tampilkan.
  </div>
</div>
@endif

{{-- ─── Filter + Tabel Order ────────────────────────────────────── --}}
<div class="clay-card" style="padding:0;overflow:hidden;">

  {{-- Header export per batch ── --}}
  @if($selectedBatch && !$isCs)
  <div style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;border-bottom:1px solid rgba(0,0,0,.06);">
    <div>
      <h2 style="margin:0;font-size:1rem;font-weight:800;">🚚 {{ $selectedBatch->original_filename }}</h2>
      <div style="font-size:.72rem;color:#9ca3af;">
        {{ $selectedBatch->created_at?->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}{{ $selectedBatch->sender ? ' ('.$selectedBatch->sender.')' : '' }}
        · Total {{ $selectedBatch->total_rows }} · Sukses {{ $selectedBatch->success_rows }}
      </div>
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
          @php $etIcon = $et->key === 'sicepat' ? '📘' : ($et->key === 'spx' ? '📙' : '📦'); @endphp
          <a href="{{ route('orders.export', [$selectedBatch->id, $et->key]) }}" class="clay-btn" style="padding:6px 12px;font-size:.78rem;">{{ $etIcon }} Export {{ $et->name }}</a>
        @endif
      @endforeach
    </div>
  </div>
  @endif

  {{-- Filter form ── --}}
  <form method="GET" action="{{ route('orders.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:12px 20px;border-bottom:1px solid rgba(0,0,0,.04);">
    <select name="batch" class="clay-input" style="min-width:180px;" onchange="this.form.submit()">
      <option value="">Semua Batch</option>
      @foreach($batches as $b)
        <option value="{{ $b->id }}" @selected(request('batch') == $b->id)>
          {{ $b->created_at?->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') }}{{ $b->sender ? ' — '.$b->sender : '' }} ({{ $b->shipping_orders_count }})
        </option>
      @endforeach
    </select>
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
    <a href="{{ route('orders.index') }}" class="clay-btn">Reset</a>
  </form>

  {{-- Tabel order ── --}}
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
            <td>
              <a href="{{ route('orders.show', $o->id) }}" style="color:var(--color-primary,#FF6B6B);font-weight:700;text-decoration:none;">
                {{ $o->customer_name }}
              </a>
            </td>
            <td class="sel-nowrap" style="font-size:.75rem;">
              <a href="{{ route('orders.show', $o->id) }}" style="color:var(--color-primary,#FF6B6B);font-weight:700;text-decoration:none;">
                {{ $o->phone }}
              </a>
            </td>
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
                    @php
                      $aggColor = match($o->aggregator_status) {
                        'waiting_pickup', 'in_transit', 'delivered' => 'background:#dcfce7;color:#15803d;',
                        'problem' => 'background:#fee2e2;color:#b91c1c;',
                        'returning', 'returned' => 'background:#fef3c7;color:#92400e;',
                        default => 'background:#f3f4f6;color:#6b7280;',
                      };
                    @endphp
                    <span class="badge-courier" style="{{ $aggColor }}margin-top:2px;">{{ str_replace('_', ' ', $o->aggregator_status) }}</span>
                  @endif
                </div>
              @else
                @if(!$isCs)
                <details style="font-size:.78rem;">
                  <summary style="cursor:pointer;color:var(--color-primary,#FF6B6B);font-weight:700;">Edit</summary>
                  <form method="POST" action="{{ route('orders.update', $o->id) }}" class="courier-edit-form" style="margin-top:6px;flex-wrap:wrap;">
                    @csrf @method('PUT')
                    <select name="courier">
                      <option value="">— Pilih —</option>
                      @foreach($courierList as $cc)
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
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:24px;">Belum ada order{{ request('batch') ? ' di batch ini' : '' }}.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:12px 20px;">{{ $orders->links() }}</div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
/* ══════════════════════════════════════════════════
   1. GRAFIK TREN ORDER
══════════════════════════════════════════════════ */
(function () {
  const RAW = @json($chartData);
  if (!RAW || RAW.length === 0) return;

  const POINT_W = 56; // px per titik
  const MIN_CANVAS_W = Math.max(RAW.length * POINT_W, 480);

  const wrap  = document.getElementById('chart-scroll-wrap');
  const inner = document.getElementById('chart-inner');
  const canvas = document.getElementById('orderTrendChart');
  if (!wrap || !canvas) return;

  inner.style.width = MIN_CANVAS_W + 'px';
  canvas.style.width  = '100%';
  canvas.style.height = '220px';

  const labels   = RAW.map(r => r.date);
  const dTotal   = RAW.map(r => r.total);
  const dReal    = RAW.map(r => r.real);
  const dTembakan= RAW.map(r => r.tembakan);
  const dLead    = RAW.map(r => r.lead);

  const chart = new Chart(canvas, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Total Masuk',
          data: dTotal,
          borderColor: '#6366f1',
          backgroundColor: 'rgba(99,102,241,.10)',
          fill: true,
          tension: .38,
          pointRadius: 4,
          pointHoverRadius: 7,
          borderWidth: 2.5,
        },
        {
          label: 'Real',
          data: dReal,
          borderColor: '#10b981',
          backgroundColor: 'rgba(16,185,129,.10)',
          fill: false,
          tension: .38,
          pointRadius: 4,
          pointHoverRadius: 7,
          borderWidth: 2,
        },
        {
          label: 'Tembakan',
          data: dTembakan,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59,130,246,.08)',
          fill: false,
          tension: .38,
          pointRadius: 4,
          pointHoverRadius: 7,
          borderWidth: 2,
        },
        {
          label: 'Lead',
          data: dLead,
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245,158,11,.08)',
          fill: false,
          tension: .38,
          pointRadius: 4,
          pointHoverRadius: 7,
          borderWidth: 2,
          borderDash: [5, 3],
        },
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false }, // pakai legend custom
        tooltip: {
          backgroundColor: 'rgba(17,24,39,.92)',
          titleFont: { size: 11, weight: '700' },
          bodyFont: { size: 11 },
          padding: 10,
          cornerRadius: 8,
          callbacks: {
            label: function (ctx) {
              return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y;
            }
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(0,0,0,.04)' },
          ticks: { font: { size: 10 }, color: '#9ca3af', maxRotation: 45, minRotation: 0 }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,.06)' },
          ticks: { font: { size: 10 }, color: '#9ca3af', stepSize: 1, precision: 0 }
        }
      }
    }
  });

  /* ── Toggle dataset via legend custom ── */
  document.querySelectorAll('#chart-legend .chart-legend-item').forEach(function (el) {
    el.addEventListener('click', function () {
      const idx = parseInt(this.dataset.dataset);
      const meta = chart.getDatasetMeta(idx);
      meta.hidden = !meta.hidden;
      chart.update();
      const dot = this.querySelector('.chart-legend-dot');
      dot.style.opacity = meta.hidden ? '0.3' : '1';
      this.style.opacity = meta.hidden ? '0.4' : '1';
    });
  });

  /* ── Drag-to-scroll ── */
  let isDown = false, startX = 0, scrollLeft = 0;
  wrap.addEventListener('mousedown', function (e) {
    isDown = true; startX = e.pageX - wrap.offsetLeft; scrollLeft = wrap.scrollLeft;
  });
  ['mouseleave', 'mouseup'].forEach(function (ev) {
    wrap.addEventListener(ev, function () { isDown = false; });
  });
  wrap.addEventListener('mousemove', function (e) {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - wrap.offsetLeft;
    wrap.scrollLeft = scrollLeft - (x - startX);
  });

  /* scroll ke kanan ujung (tampilkan data terbaru) */
  wrap.scrollLeft = wrap.scrollWidth;
})();

/* ══════════════════════════════════════════════════
   2. UPLOAD DATA MENTAH
══════════════════════════════════════════════════ */
(function () {
  const fileInput   = document.getElementById('csv-file');
  const dropzone    = document.getElementById('csv-dropzone');
  const csvIcon     = document.getElementById('csv-icon');
  const csvHint     = document.getElementById('csv-hint');
  const csvFilename = document.getElementById('csv-filename');
  const senderInput = document.getElementById('csv-sender');
  const resultBox   = document.getElementById('import-result');
  if (!dropzone) return;

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
  ['dragover','dragenter'].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); dropzone.classList.add('drag-over'); });
  });
  ['dragleave','dragend','drop'].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); dropzone.classList.remove('drag-over'); });
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

/* ══════════════════════════════════════════════════
   3. UPLOAD STATUS AGGREGATOR
══════════════════════════════════════════════════ */
(function () {
  const fileInput   = document.getElementById('track-file');
  const dropzone    = document.getElementById('track-dropzone');
  const trackIcon   = document.getElementById('track-icon');
  const trackHint   = document.getElementById('track-hint');
  const trackFilename = document.getElementById('track-filename');
  const resultBox   = document.getElementById('track-result');
  if (!dropzone) return;

  function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  }
  dropzone.addEventListener('click', function () { fileInput.click(); });
  ['dragover','dragenter'].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); dropzone.classList.add('drag-over'); });
  });
  ['dragleave','dragend','drop'].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); dropzone.classList.remove('drag-over'); });
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
      trackHint.textContent = '.csv / .xlsx — maks 10MB. Sumber (FLIK/SiCepat/SPX) dideteksi otomatis.';
    }
  });
  document.getElementById('btn-track-import').addEventListener('click', function () {
    if (!fileInput.files.length) { alert('Pilih file terlebih dahulu.'); return; }
    const fd = new FormData();
    fd.append('file', fileInput.files[0]);
    const courierVal = document.getElementById('track-courier').value;
    if (courierVal) fd.append('courier', courierVal);
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
