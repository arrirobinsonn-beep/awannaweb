@extends('layouts.app')
@section('title','Barang Masuk')
@section('page-title','📥 Barang Masuk (Pembelian)')
@section('page-subtitle','Ajukan pembelian, tunggu acc keuangan, lalu verifikasi barang datang')

@section('content')
@if(session('success'))
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#d1fae5;color:#065f46;font-weight:600;border-radius:8px;">
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#fee2e2;color:#991b1b;font-weight:600;border-radius:8px;">
    @foreach($errors->all() as $e)<div>⚠ {{ $e }}</div>@endforeach
</div>
@endif

{{-- ═══ Alur ═══ --}}
<div class="pm-card" style="padding:14px 18px;margin-bottom:20px;background:linear-gradient(135deg,#f0f9ff,#fff);border:1.5px solid rgba(59,130,246,.15);" data-reveal>
    <div class="pm-alur">
        <span class="pm-alur-step">📝 <strong>1. Ajukan</strong></span>
        <span class="pm-alur-arrow">→</span>
        <span class="pm-alur-step">⏳ <strong>2. Menunggu Acc</strong></span>
        <span class="pm-alur-arrow">→</span>
        <span class="pm-alur-step">📦 <strong>3. Verifikasi</strong></span>
        <span class="pm-alur-arrow">→</span>
        <span class="pm-alur-step">✅ <strong>4. Stok Masuk</strong></span>
    </div>
</div>

{{-- ═══ Form Ajukan ═══ --}}
<div class="pm-card" style="padding:20px 24px;margin-bottom:20px;" data-reveal>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
        <div style="width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,#3b82f6,#2563eb);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem;flex-shrink:0;">➕</div>
        <div>
            <h2 style="margin:0;font-size:1rem;font-weight:800;">Ajukan Pembelian</h2>
            <div style="font-size:.72rem;color:#9ca3af;">Pengajuan akan dikirim ke tim keuangan untuk disetujui</div>
        </div>
    </div>

    <form method="POST" action="{{ route('purchase.store') }}">
        @csrf
        <div class="form-grid">
            <div>
                <label class="pm-label">TANGGAL</label>
                <input type="date" name="date" required value="{{ old('date', now()->format('Y-m-d')) }}" class="clay-input">
            </div>
            <div>
                <label class="pm-label">SUPPLIER</label>
                <select name="supplier_id" class="clay-input">
                    <option value="">— Pilih Supplier —</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->nama_supplier }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:span 2;">
                <label class="pm-label">VARIAN PRODUK</label>
                <select name="product_variant_id" id="purchase-variant" required class="clay-input">
                    <option value="">— Pilih Produk / Varian —</option>
                    @foreach($products as $p)
                        <optgroup label="{{ $p->name }}" data-primary-wh="{{ $p->primaryInventoryId() ?? '' }}">
                            @foreach($p->variants as $v)
                                <option value="{{ $v->id }}" @selected(old('product_variant_id') == $v->id)>
                                    {{ $v->name }} {{ (float)$v->power > 0 ? '(+'.number_format($v->power,2,',','.').')' : '' }} — stok {{ $v->stock }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:span 2;">
                <label class="pm-label">GUDANG TUJUAN</label>
                <select name="inventory_id" id="purchase-inventory" required class="clay-input">
                    <option value="">— Pilih Gudang —</option>
                    @foreach($inventories as $inv)
                        <option value="{{ $inv->id }}" @selected(old('inventory_id') == $inv->id)>{{ $inv->name }}</option>
                    @endforeach
                </select>
                <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Stok masuk dicatat ke gudang ini</div>
            </div>
            <div>
                <label class="pm-label">JUMLAH (QTY)</label>
                <input type="number" name="quantity" required min="1" value="{{ old('quantity') }}" class="clay-input">
            </div>
            <div>
                <label class="pm-label">HARGA SATUAN</label>
                <input type="number" name="unit_price" required min="0" step="0.01" value="{{ old('unit_price') }}" class="clay-input">
            </div>
            <div>
                <label class="pm-label">ONGKIR</label>
                <input type="number" name="shipping_cost" min="0" step="0.01" value="{{ old('shipping_cost', 0) }}" class="clay-input">
            </div>
            <div style="grid-column:span 2;">
                <label class="pm-label">KETERANGAN</label>
                <input type="text" name="note" maxlength="255" value="{{ old('note') }}" class="clay-input" placeholder="Opsional">
            </div>
        </div>
        <div style="margin-top:16px;display:flex;justify-content:flex-end;">
            <button type="submit" class="clay-btn clay-btn-primary">📤 Kirim Pengajuan</button>
        </div>
    </form>
</div>

{{-- ═══ Filter ═══ --}}
<div class="pm-card" style="padding:0;margin-bottom:20px;" data-reveal>
    <button type="button" class="pm-filter-toggle" onclick="document.getElementById('filterBody').classList.toggle('open');this.querySelector('.pm-filter-chevron').classList.toggle('open')">
        <span>🔍 Filter & Pencarian</span>
        <span class="pm-filter-chevron">▾</span>
    </button>
    <div id="filterBody" class="pm-filter-body{{ request()->hasAny(['variant_id','supplier_id','inventory_id','bulan','status']) ? ' open' : '' }}">
        <form method="GET" action="{{ route('purchase.index') }}" class="pm-filter-form">
            <div class="pm-filter-grid">
                <select name="variant_id" class="clay-input">
                    <option value="">Semua Produk / Varian</option>
                    @foreach($products as $p)
                        <optgroup label="{{ $p->name }}">
                            @foreach($p->variants as $v)
                                <option value="{{ $v->id }}" @selected(request('variant_id') == $v->id)>{{ $v->name }} {{ (float)$v->power > 0 ? '(+'.number_format($v->power,2,',','.').')' : '' }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <select name="supplier_id" class="clay-input">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->nama_supplier }}</option>
                    @endforeach
                </select>
                <select name="inventory_id" class="clay-input">
                    <option value="">Semua Gudang</option>
                    @foreach($inventories as $inv)
                        <option value="{{ $inv->id }}" @selected(request('inventory_id') == $inv->id)>{{ $inv->name }}</option>
                    @endforeach
                </select>
                <select name="bulan" class="clay-input">
                    <option value="">Semua Bulan</option>
                    @foreach($monthList as $b)
                        <option value="{{ $b }}" @selected(request('bulan') === $b)>{{ $b }}</option>
                    @endforeach
                </select>
                <select name="status" class="clay-input">
                    <option value="">Semua Status</option>
                    <option value="pending" @selected(request('status') === 'pending')>⏳ Menunggu</option>
                    <option value="approved" @selected(request('status') === 'approved')>📦 Perlu Verifikasi</option>
                    <option value="received" @selected(request('status') === 'received')>✅ Diterima</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>❌ Ditolak</option>
                </select>
            </div>
            <div class="pm-filter-actions">
                <button class="clay-btn clay-btn-primary" type="submit">🔍 Filter</button>
                <a href="{{ route('purchase.index') }}" class="clay-btn">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- ═══ Tabel / Cards ═══ --}}
<div class="pm-card" style="padding:0;overflow:hidden;" data-reveal>
    {{-- Desktop table --}}
    <div class="table-scroll pm-desktop-table">
        <table class="clay-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Produk / Varian</th>
                    <th>Gudang</th>
                    <th>Supplier</th>
                    <th class="pm-col-num">Qty</th>
                    <th class="pm-col-num">Harga</th>
                    <th class="pm-col-num">Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $pu)
                    @php
                        $total = $pu->quantity * $pu->unit_price + $pu->shipping_cost;
                        $puStatusMap = [
                            'pending'  => ['class' => 'clay-badge-yellow', 'icon' => '⏳', 'label' => 'Menunggu'],
                            'approved' => ['class' => 'clay-badge-blue',   'icon' => '📦', 'label' => 'Perlu Verifikasi'],
                            'rejected' => ['class' => 'clay-badge-red',    'icon' => '❌', 'label' => 'Ditolak'],
                            'received' => ['class' => 'clay-badge-green',  'icon' => '✅', 'label' => 'Diterima'],
                        ];
                        $ps = $puStatusMap[$pu->status] ?? ['class' => 'clay-badge-gray', 'icon' => '•', 'label' => $pu->status];
                    @endphp
                    <tr class="pm-row pm-row--{{ $pu->status }}">
                        <td class="sel-nowrap">{{ $pu->date->format('d/m/Y') }}</td>
                        <td>
                            <span style="font-weight:600;">{{ $pu->variant?->product?->name ?? '-' }}</span>
                            <div style="font-size:.72rem;color:#9ca3af;">{{ $pu->variant?->name }} {{ (float)($pu->variant?->power ?? 0) > 0 ? '(+'.number_format($pu->variant->power,2,',','.').')' : '' }}</div>
                        </td>
                        <td>
                            @if($pu->inventory)
                                <span class="clay-badge clay-badge-blue" style="font-size:.68rem;">🏭 {{ $pu->inventory->name }}</span>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td style="font-size:.82rem;">{{ $pu->supplier->nama_supplier ?? '-' }}</td>
                        <td class="pm-col-num">{{ number_format($pu->quantity,0,',','.') }}</td>
                        <td class="pm-col-num">Rp {{ number_format((float)$pu->unit_price,0,',','.') }}</td>
                        <td class="pm-col-num" style="font-weight:700;">Rp {{ number_format($total,0,',','.') }}</td>
                        <td>
                            <span class="clay-badge {{ $ps['class'] }}" style="font-size:.68rem;">{{ $ps['icon'] }} {{ $ps['label'] }}</span>
                            @if($pu->rejection_note)
                                <div style="font-size:.68rem;color:#991b1b;margin-top:3px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ e($pu->rejection_note) }}">{{ Str::limit($pu->rejection_note, 35) }}</div>
                            @endif
                            @if($pu->status === 'received' && $pu->receive_note)
                                <div style="font-size:.68rem;color:#065f46;margin-top:3px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ e($pu->receive_note) }}">📝 {{ Str::limit($pu->receive_note, 35) }}</div>
                            @endif
                        </td>
                        <td>
                            @if($pu->status === 'approved')
                                <button type="button" class="clay-btn clay-btn-primary" style="padding:4px 10px;font-size:.7rem;"
                                        onclick="openVerifyModal({{ $pu->id }}, '{{ e($pu->variant?->product?->name ?? '-') }} {{ e($pu->variant?->name ?? '-') }}', {{ $pu->quantity }}, {{ (float)$pu->unit_price }}, 'Rp {{ number_format($total,0,',','.') }}')">
                                    📦 Verifikasi
                                </button>
                            @endif
                            <form method="POST" action="{{ route('purchase.destroy',$pu) }}" style="display:inline;"
                                  onsubmit="return confirm('Hapus pembelian ini?{{ $pu->status === 'received' ? ' Stok & HPP akan dikembalikan.' : '' }}')">
                                @csrf @method('DELETE')
                                <button class="clay-btn clay-btn-sm clay-btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:48px;color:#9ca3af;">
                            <div style="font-size:2rem;margin-bottom:8px;">📭</div>
                            Belum ada pengajuan pembelian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="pm-mobile-cards">
        @forelse($purchases as $pu)
            @php
                $total = $pu->quantity * $pu->unit_price + $pu->shipping_cost;
                $puStatusMap = [
                    'pending'  => ['class' => 'clay-badge-yellow', 'icon' => '⏳', 'label' => 'Menunggu'],
                    'approved' => ['class' => 'clay-badge-blue',   'icon' => '📦', 'label' => 'Perlu Verifikasi'],
                    'rejected' => ['class' => 'clay-badge-red',    'icon' => '❌', 'label' => 'Ditolak'],
                    'received' => ['class' => 'clay-badge-green',  'icon' => '✅', 'label' => 'Diterima'],
                ];
                $ps = $puStatusMap[$pu->status] ?? ['class' => 'clay-badge-gray', 'icon' => '•', 'label' => $pu->status];
            @endphp
            <div class="pm-card-item pm-card--{{ $pu->status }}">
                <div class="pm-card-header">
                    <div style="min-width:0;">
                        <div style="font-weight:700;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $pu->variant?->product?->name ?? '-' }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;">{{ $pu->variant?->name }} {{ (float)($pu->variant?->power ?? 0) > 0 ? '(+'.number_format($pu->variant->power,2,',','.').')' : '' }}</div>
                    </div>
                    <span class="clay-badge {{ $ps['class'] }}" style="font-size:.65rem;white-space:nowrap;">{{ $ps['icon'] }} {{ $ps['label'] }}</span>
                </div>
                <div class="pm-card-body">
                    <div class="pm-card-row"><span class="pm-card-key">Tanggal</span><span>{{ $pu->date->format('d/m/Y') }}</span></div>
                    <div class="pm-card-row"><span class="pm-card-key">Qty</span><span style="font-weight:600;">{{ number_format($pu->quantity,0,',','.') }}</span></div>
                    <div class="pm-card-row"><span class="pm-card-key">Harga</span><span>Rp {{ number_format((float)$pu->unit_price,0,',','.') }}</span></div>
                    <div class="pm-card-row"><span class="pm-card-key">Total</span><span style="font-weight:700;color:var(--color-primary,#FF6B6B);">Rp {{ number_format($total,0,',','.') }}</span></div>
                    @if($pu->inventory)
                        <div class="pm-card-row"><span class="pm-card-key">Gudang</span><span class="clay-badge clay-badge-blue" style="font-size:.65rem;">🏭 {{ $pu->inventory->name }}</span></div>
                    @endif
                    @if($pu->supplier)
                        <div class="pm-card-row"><span class="pm-card-key">Supplier</span><span>{{ $pu->supplier->nama_supplier }}</span></div>
                    @endif
                    @if($pu->note)
                        <div class="pm-card-row"><span class="pm-card-key">Ket.</span><span style="font-size:.78rem;color:#6b7280;">{{ $pu->note }}</span></div>
                    @endif
                    @if($pu->rejection_note)
                        <div class="pm-card-row"><span class="pm-card-key">Alasan Tolak</span><span style="font-size:.75rem;color:#991b1b;">{{ Str::limit($pu->rejection_note, 60) }}</span></div>
                    @endif
                </div>
                <div class="pm-card-footer">
                    @if($pu->status === 'approved')
                        <button type="button" class="clay-btn clay-btn-primary" style="padding:5px 12px;font-size:.72rem;"
                                onclick="openVerifyModal({{ $pu->id }}, '{{ e($pu->variant?->product?->name ?? '-') }} {{ e($pu->variant?->name ?? '-') }}', {{ $pu->quantity }}, {{ (float)$pu->unit_price }}, 'Rp {{ number_format($total,0,',','.') }}')">
                            📦 Verifikasi
                        </button>
                    @endif
                    <form method="POST" action="{{ route('purchase.destroy',$pu) }}" style="display:inline;"
                          onsubmit="return confirm('Hapus?{{ $pu->status === 'received' ? ' Stok & HPP dikembalikan.' : '' }}')">
                        @csrf @method('DELETE')
                        <button class="clay-btn clay-btn-sm clay-btn-danger">🗑 Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:48px;color:#9ca3af;">
                <div style="font-size:2rem;margin-bottom:8px;">📭</div>
                Belum ada pengajuan pembelian.
            </div>
        @endforelse
    </div>
</div>

<div style="margin-top:16px;">{{ $purchases->links() }}</div>

{{-- ═══ MODAL: Verifikasi Barang Datang ═══ --}}
<div id="verifyModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px);" onclick="closeVerifyModal()"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #e5e7eb;">
            <div>
                <h3 style="margin:0;font-size:1rem;font-weight:800;">📦 Verifikasi Barang Datang</h3>
                <div style="font-size:.75rem;color:#6b7280;margin-top:2px;">Isi data barang aktual yang diterima</div>
            </div>
            <button onclick="closeVerifyModal()" style="width:32px;height:32px;border-radius:50%;border:none;background:#f3f4f6;cursor:pointer;font-size:1.1rem;">✕</button>
        </div>
        <form id="verifyForm" method="POST" style="padding:20px 24px;">
            @csrf
            @method('PATCH')
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
                <div style="font-size:.72rem;color:#0369a1;font-weight:600;margin-bottom:4px;">📋 Data Pengajuan</div>
                <div id="verifyProductName" style="font-weight:700;font-size:.9rem;color:#1e1b2e;"></div>
                <div style="font-size:.78rem;color:#6b7280;margin-top:2px;">Total: <span id="verifyOriginalTotal" style="font-weight:700;color:var(--color-primary);"></span></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div>
                    <label class="pm-label">Qty Aktual *</label>
                    <input type="number" name="actual_quantity" id="verifyQty" required min="1" class="clay-input" style="width:100%;box-sizing:border-box;">
                </div>
                <div>
                    <label class="pm-label">Harga Satuan Aktual</label>
                    <input type="number" name="actual_unit_price" id="verifyPrice" min="0" step="0.01" class="clay-input" style="width:100%;box-sizing:border-box;" placeholder="Kosongkan = sama">
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label class="pm-label">Catatan Penerimaan</label>
                <textarea name="receive_note" rows="3" maxlength="500" class="clay-input" style="width:100%;box-sizing:border-box;resize:vertical;" placeholder="Opsional"></textarea>
            </div>
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.72rem;color:#92400e;line-height:1.5;">
                ⚠️ <strong>Setelah diverifikasi:</strong> Stok & HPP akan diperbarui otomatis.
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeVerifyModal()" class="clay-btn clay-btn-outline" style="padding:8px 14px;font-size:.82rem;">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary" style="padding:8px 16px;font-size:.82rem;">📦 Konfirmasi Diterima</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ── Label ── */
.pm-label { display:block;font-size:.73rem;font-weight:700;margin-bottom:4px;color:#374151; }

/* ── Card wrapper ── */
.pm-card { background:#fff;border:1.5px solid #e5e7eb;border-radius:16px; }

/* ── Alur ── */
.pm-alur { display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:.78rem;color:#1e40af; }
.pm-alur-arrow { color:#9ca3af; }
.pm-alur-step { white-space:nowrap; }

/* ── Filter ── */
.pm-filter-toggle {
    width:100%;display:flex;align-items:center;justify-content:space-between;
    padding:14px 18px;background:none;border:none;cursor:pointer;
    font-size:.85rem;font-weight:700;color:#374151;
}
.pm-filter-toggle:hover { background:#f9fafb; }
.pm-filter-chevron { font-size:.7rem;transition:transform .2s;color:#9ca3af; }
.pm-filter-chevron.open { transform:rotate(180deg); }
.pm-filter-body { display:none;padding:0 18px 16px; }
.pm-filter-body.open { display:block; }
.pm-filter-form { display:flex;flex-direction:column;gap:10px; }
.pm-filter-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px; }
.pm-filter-actions { display:flex;gap:8px; }

/* ── Desktop table ── */
.pm-col-num { text-align:right; }
.pm-row--pending { background:#fffbeb; }
.pm-row--approved { background:#eff6ff; }
.pm-row--rejected { opacity:.7; }

/* ── Mobile cards ── */
.pm-mobile-cards { display:none; }

.pm-card-item {
    border-bottom:1px solid #f3f4f6;
    padding:14px 16px;
}
.pm-card-item:last-child { border-bottom:none; }
.pm-card--pending { background:#fffbeb; }
.pm-card--approved { background:#f0f9ff; }
.pm-card--rejected { opacity:.7; }

.pm-card-header {
    display:flex;align-items:flex-start;justify-content:space-between;gap:8px;
    margin-bottom:10px;
}
.pm-card-body { display:flex;flex-direction:column;gap:6px;margin-bottom:12px; }
.pm-card-row { display:flex;justify-content:space-between;align-items:center;font-size:.8rem; }
.pm-card-key { color:#9ca3af;font-size:.72rem;font-weight:600;flex-shrink:0;margin-right:8px; }
.pm-card-footer { display:flex;gap:8px;justify-content:flex-end;padding-top:10px;border-top:1px solid #f3f4f6; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .pm-desktop-table { display:none; }
    .pm-mobile-cards { display:block; }

    .pm-alur { gap:6px;font-size:.7rem; }
    .pm-alur-arrow { display:none; }

    .pm-filter-grid { grid-template-columns:1fr; }
    .pm-filter-body { padding:0 14px 14px; }

    #verifyModal > div:last-child { border-radius:16px; }
    #verifyModal form { padding:16px; }
    #verifyModal form > div[style*="grid"] { grid-template-columns:1fr !important; }
}
</style>
@endsection

@push('scripts')
<script>
document.getElementById('purchase-variant').addEventListener('change', function() {
    var sel = this;
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    var group = opt.parentElement;
    var primary = group ? (group.getAttribute('data-primary-wh') || '') : '';
    var inv = document.getElementById('purchase-inventory');
    if (primary) inv.value = primary;
});

function openVerifyModal(id, productName, qty, price, total) {
    document.getElementById('verifyProductName').textContent = productName;
    document.getElementById('verifyOriginalTotal').textContent = total;
    document.getElementById('verifyQty').value = qty;
    document.getElementById('verifyQty').max = qty * 2;
    document.getElementById('verifyPrice').value = '';
    var form = document.getElementById('verifyForm');
    form.action = '{{ route("purchase.verify", "__ID__") }}'.replace('__ID__', id);
    form.querySelector('[name=receive_note]').value = '';
    document.getElementById('verifyModal').style.display = 'flex';
}
function closeVerifyModal() {
    document.getElementById('verifyModal').style.display = 'none';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeVerifyModal();
});
</script>
@endpush
