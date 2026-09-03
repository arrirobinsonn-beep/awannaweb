@extends('layouts.app')

@section('title', 'Detail Order '.$shippingOrder->order_id)
@section('page-title', '📦 Detail Order')
@section('page-subtitle', 'Informasi lengkap data order online')

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
    .detail-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; }
    @media (max-width: 900px) { .detail-grid { grid-template-columns:1fr; } }
    .detail-section { padding:18px 20px; }
    .detail-section h3 { margin:0 0 12px; font-size:.88rem; font-weight:800; color:#374151; display:flex; align-items:center; gap:6px; }
    .detail-row { display:flex; gap:10px; padding:6px 0; border-bottom:1px dashed rgba(0,0,0,.05); font-size:.8rem; }
    .detail-row:last-child { border-bottom:none; }
    .detail-label { width:140px; min-width:140px; color:#9ca3af; }
    .detail-value { flex:1; color:#1f2937; font-weight:600; word-break:break-word; }
    .link-order { color:var(--color-primary,#FF6B6B); font-weight:700; text-decoration:none; }
    .link-order:hover { text-decoration:underline; }
    .raw-table { width:100%; border-collapse:collapse; font-size:.72rem; }
    .raw-table td { padding:3px 8px; border-bottom:1px solid rgba(0,0,0,.04); vertical-align:top; }
    .raw-table td:first-child { width:40%; color:#9ca3af; font-weight:700; }
</style>
@endpush

@section('content')
<div class="max-w-6xl" style="margin:0 auto;">

    {{-- Header --}}
    <div class="clay-card" style="padding:18px 24px;margin-bottom:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="font-size:1.05rem;font-weight:800;color:#1f2937;">{{ $shippingOrder->order_id }}</div>
                <div style="font-size:.75rem;color:#9ca3af;margin-top:2px;">
                    @if($shippingOrder->importBatch)
                        🗂 {{ $shippingOrder->importBatch->original_filename }}
                        @if($shippingOrder->importBatch->sender) • {{ $shippingOrder->importBatch->sender }} @endif
                        • {{ $shippingOrder->created_at?->format('d/m/Y H:i') }}
                    @else
                        {{ $shippingOrder->created_at?->format('d/m/Y H:i') }}
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <span class="badge-order-status st-{{ $shippingOrder->status }}">{{ $shippingOrder->status ? str_replace('_', ' ', ucwords($shippingOrder->status, '_')) : '-' }}</span>
                <span class="badge-courier cou-{{ $shippingOrder->courier }}">{{ $shippingOrder->courier ?? '-' }}</span>
                @if($shippingOrder->aggregator_status)
                    @php
                        $aggColor = match($shippingOrder->aggregator_status) {
                            'waiting_pickup', 'in_transit', 'delivered' => 'background:#dcfce7;color:#15803d;',
                            'problem' => 'background:#fee2e2;color:#b91c1c;',
                            'returning', 'returned' => 'background:#fef3c7;color:#92400e;',
                            default => 'background:#f3f4f6;color:#6b7280;',
                        };
                    @endphp
                    <span class="badge-courier" style="{{ $aggColor }}">{{ str_replace('_', ' ', $shippingOrder->aggregator_status) }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="detail-grid">
        {{-- Pelanggan --}}
        <div class="clay-card detail-section">
            <h3>👤 Pelanggan</h3>
            <div class="detail-row"><span class="detail-label">Nama</span><span class="detail-value">{{ $shippingOrder->customer_name ?? '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Telepon</span><span class="detail-value">{{ $shippingOrder->phone ?? '-' }} @if($shippingOrder->phone_normalized)<span style="color:#9ca3af;font-weight:400;"> ({{ $shippingOrder->phone_normalized }})</span>@endif</span></div>
            <div class="detail-row"><span class="detail-label">Alamat</span><span class="detail-value">{{ $shippingOrder->address ?? '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Kecamatan</span><span class="detail-value">{{ $shippingOrder->subdistrict ?? '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Kota</span><span class="detail-value">{{ $shippingOrder->city ?? '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Provinsi</span><span class="detail-value">{{ $shippingOrder->province ?? '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Kode Pos</span><span class="detail-value">{{ $shippingOrder->postal_code ?? '-' }}</span></div>
        </div>

        {{-- Produk --}}
        <div class="clay-card detail-section">
            <h3>🛍 Produk</h3>
            <div class="detail-row"><span class="detail-label">Nama Produk</span><span class="detail-value">{{ $shippingOrder->product_name ?? '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Kode Produk</span><span class="detail-value">{{ $shippingOrder->product_code ?? '-' }}</span></div>
            <div class="detail-row">
                <span class="detail-label">Produk Master</span>
                <span class="detail-value">
                    @if($shippingOrder->product)
                        {{ $shippingOrder->product->name }} <span style="color:#9ca3af;font-weight:400;">({{ $shippingOrder->product->code }})</span>
                    @else
                        <span style="color:#b91c1c;">Belum di-link</span>
                    @endif
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Varian</span>
                <span class="detail-value">
                    @if($shippingOrder->variant)
                        {{ $shippingOrder->variant->name ?: $shippingOrder->variant->code }} <span style="color:#9ca3af;font-weight:400;">(power {{ $shippingOrder->variant->power }})</span>
                    @else
                        —
                    @endif
                </span>
            </div>
            <div class="detail-row"><span class="detail-label">Quantity</span><span class="detail-value">{{ $shippingOrder->quantity }}</span></div>
            <div class="detail-row"><span class="detail-label">Berat</span><span class="detail-value">{{ $shippingOrder->weight }}</span></div>
            @if($shippingOrder->meta_account)
                <div class="detail-row"><span class="detail-label">Meta Account</span><span class="detail-value">{{ $shippingOrder->meta_account }}</span></div>
            @endif
            @if($shippingOrder->stock_note)
                <div class="detail-row"><span class="detail-label">Catatan Stok</span><span class="detail-value" style="color:#b45309;">⚠ {{ $shippingOrder->stock_note }}</span></div>
            @endif
        </div>

        {{-- Pembayaran --}}
        <div class="clay-card detail-section">
            <h3>💳 Pembayaran</h3>
            <div class="detail-row"><span class="detail-label">Metode</span><span class="detail-value">{{ strtoupper($shippingOrder->payment_method ?? '-') }}</span></div>
            <div class="detail-row"><span class="detail-label">COD</span><span class="detail-value">{{ $shippingOrder->is_cod ? 'Ya' : 'Tidak' }}</span></div>
            <div class="detail-row"><span class="detail-label">Nilai (Amount)</span><span class="detail-value">Rp {{ number_format((float) $shippingOrder->amount, 0, ',', '.') }}</span></div>
            <div class="detail-row"><span class="detail-label">Ongkir</span><span class="detail-value">Rp {{ number_format((float) $shippingOrder->shipping_cost, 0, ',', '.') }}</span></div>
        </div>

        {{-- Courier & Status --}}
        <div class="clay-card detail-section">
            <h3>🚚 Courier & Status</h3>
            <div class="detail-row"><span class="detail-label">Courier</span><span class="detail-value">{{ $shippingOrder->courier ?? '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Catatan Kurir</span><span class="detail-value">{{ $shippingOrder->courier_note ?? '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value">{{ $shippingOrder->status ? str_replace('_', ' ', ucwords($shippingOrder->status, '_')) : '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Handle By</span><span class="detail-value">{{ $shippingOrder->handled_by ?? '-' }} @if($shippingOrder->handledByUser)<span style="color:#9ca3af;font-weight:400;"> ({{ $shippingOrder->handledByUser->name }})</span>@endif</span></div>
            <div class="detail-row"><span class="detail-label">Batch</span><span class="detail-value">#{{ $shippingOrder->order_online_import_batch_id }}</span></div>
        </div>

        {{-- Tracking / AWB --}}
        <div class="clay-card detail-section" style="grid-column:1 / -1;">
            <h3>📡 Tracking Aggregator</h3>
            <div class="detail-row"><span class="detail-label">Resi (AWB)</span><span class="detail-value">{{ $shippingOrder->awb ?: '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Status Aggregator</span><span class="detail-value">{{ $shippingOrder->aggregator_status ? str_replace('_', ' ', $shippingOrder->aggregator_status) : '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Terkirim</span><span class="detail-value">{{ $shippingOrder->delivered_at?->format('d/m/Y H:i') ?: '-' }}</span></div>
            <div class="detail-row"><span class="detail-label">Sync Terakhir</span><span class="detail-value">{{ $shippingOrder->last_synced_at?->format('d/m/Y H:i') ?: '-' }}</span></div>
        </div>

        {{-- Data mentah (raw payload) --}}
        <div class="clay-card detail-section" style="grid-column:1 / -1;">
            <details>
                <summary style="cursor:pointer;font-weight:800;font-size:.88rem;color:#374151;display:flex;align-items:center;gap:6px;">🗄 Data Mentah CSV <span style="color:#9ca3af;font-weight:400;font-size:.72rem;">(raw_payload — {{ is_array($shippingOrder->raw_payload) ? count($shippingOrder->raw_payload) : 0 }} kolom)</span></summary>
                <div class="table-scroll" style="margin-top:10px;max-height:320px;overflow-y:auto;">
                    <table class="raw-table">
                        @foreach(($shippingOrder->raw_payload ?? []) as $key => $value)
                            <tr>
                                <td>{{ $key }}</td>
                                <td>{{ is_array($value) ? json_encode($value) : ($value === '' ? '—' : $value) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </details>
        </div>
    </div>

    {{-- Aksi --}}
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
        <a href="{{ route('orders.index', ['batch' => $shippingOrder->order_online_import_batch_id]) }}" class="clay-btn" data-page-link>← Kembali ke Batch</a>
        @if(!empty($shippingOrder->awb))
            <span class="badge-courier" style="background:#d1fae5;color:#065f46;align-self:center;">✓ {{ $shippingOrder->awb }} — terkunci (sudah ber-resi)</span>
        @else
            <a href="{{ route('orders.index', ['batch' => $shippingOrder->order_online_import_batch_id, 'search' => $shippingOrder->order_id]) }}" class="clay-btn clay-btn-outline" data-page-link>✏️ Edit di Halaman Data Mentah</a>
        @endif
    </div>
</div>
@endsection
