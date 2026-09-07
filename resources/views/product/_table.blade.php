<table class="clay-table" style="min-width:980px;">
    <thead>
        <tr>
            <th>Kode</th>
            <th>Produk</th>
            <th>Tipe</th>
            <th>Gudang</th>
            <th style="text-align:right;">Stok Total</th>
            <th style="text-align:right;">Min. Stok</th>
            <th style="text-align:right;">HPP</th>
            <th style="text-align:right;">Harga Jual</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:center;">Iklan</th>
            <th style="text-align:right;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $p)
        @php
            $primary = $p->primaryInventory?->first();
            $rowId = 'pv-'.$p->id;
        @endphp
        <tr>
            <td style="font-weight:800;color:#7c3aed;">{{ $p->code }}</td>
            <td>
                <div style="font-weight:600;">{{ $p->name }}</div>
                <div style="font-size:.72rem;color:#9ca3af;">{{ $p->unit }}{{ $p->category ? ' · '.$p->category : '' }}</div>
            </td>
            <td><span class="clay-badge clay-badge-{{ $p->goods_type === 'core' ? 'blue' : ($p->goods_type === 'additional' ? 'purple' : 'gray') }}">{{ \App\Models\Product::GOODS_TYPE_LABELS[$p->goods_type] ?? $p->goods_type }}</span></td>
            <td>
                <div style="font-size:.8rem;font-weight:700;">
                    @if($p->goods_type === 'core')
                        {{ $primary?->name ?? '—' }}
                    @else
                        <span style="color:#9ca3af;font-weight:400;">—</span>
                    @endif
                </div>
                <div style="font-size:.68rem;color:#9ca3af;">{{ $p->inventories->count() }} gudang terdaftar</div>
            </td>
            <td style="text-align:right;font-weight:800;">{{ number_format($p->stok,0,',','.') }}</td>
            <td style="text-align:right;">
                @if($p->min_stock > 0)
                    <span style="font-weight:700;color:#374151;">{{ number_format($p->min_stock,0,',','.') }}</span>
                    <div>
                        @if($p->stok <= $p->min_stock)
                            <span class="clay-badge clay-badge-red">⚠ Restock</span>
                        @else
                            <span class="clay-badge clay-badge-green">Aman</span>
                        @endif
                    </div>
                @else
                    <span style="color:#9ca3af;font-size:.78rem;">—</span>
                @endif
            </td>
            <td style="text-align:right;">{{ $p->purchase_price ? number_format($p->purchase_price,0,',','.') : '—' }}</td>
            <td style="text-align:right;">{{ number_format($p->selling_price,0,',','.') }}</td>
            <td style="text-align:center;">
                <label class="clay-toggle" title="Ubah status produk">
                    <input type="checkbox" data-toggle-url="{{ route('product.toggle-status', $p) }}" {{ $p->status==='active'?'checked':'' }}>
                    <span class="clay-toggle-slider"></span>
                </label>
            </td>
            <td style="text-align:center;">
                @if($p->ad_status === 'running')
                    <label class="clay-toggle" title="Ubah status iklan (Running ↔ Testing)">
                        <input type="checkbox" checked data-toggle-url="{{ route('product.toggle-ad-status', $p) }}">
                        <span class="clay-toggle-slider"></span>
                    </label>
                    <span style="display:inline-block;font-size:.62rem;font-weight:700;padding:1px 6px;border-radius:999px;background:#d1fae5;color:#065f46;margin-left:4px;">🟢 Running</span>
                @else
                    <label class="clay-toggle" title="Ubah status iklan (Running ↔ Testing)">
                        <input type="checkbox" data-toggle-url="{{ route('product.toggle-ad-status', $p) }}">
                        <span class="clay-toggle-slider"></span>
                    </label>
                    <span style="display:inline-block;font-size:.62rem;font-weight:700;padding:1px 6px;border-radius:999px;background:#fef3c7;color:#92400e;margin-left:4px;">🔬 Testing</span>
                @endif
            </td>
            <td style="text-align:right;">
                <div style="display:flex;justify-content:flex-end;gap:6px;align-items:center;flex-wrap:wrap;">
                    <button type="button" class="clay-btn clay-btn-sm" style="background:#f3f4f6;color:#374151;"
                            onclick="toggleVarian('{{ $p->id }}')" title="Lihat / kelola varian">
                        🔖 Varian ({{ $p->variants->count() }}) <span id="chev-{{ $rowId }}" style="display:inline-block;transition:transform .22s;font-size:.7rem;">▾</span>
                    </button>
                    <button type="button" class="clay-btn clay-btn-sm clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;"
                            onclick="openProductModal(this,'edit')" title="Edit produk"
                            data-url="{{ route('product.update', $p) }}"
                            data-code="{{ $p->code }}" data-name="{{ $p->name }}"
                            data-category="{{ $p->category }}" data-goods-type="{{ $p->goods_type }}"
                            data-min-stock="{{ $p->min_stock }}" data-description="{{ $p->description }}"
                            data-purchase-price="{{ $p->purchase_price }}" data-selling-price="{{ $p->selling_price }}"
                            data-unit="{{ $p->unit }}" data-status="{{ $p->status }}"
                            data-ad-status="{{ $p->ad_status }}">✏️</button>
                    <button type="button" class="clay-btn clay-btn-sm clay-btn-danger" style="padding:5px 10px;font-size:.72rem;" title="Hapus produk"
                            onclick="deleteProduct('{{ route('product.destroy', $p) }}', '{{ addslashes($p->name) }}')">🗑</button>
                </div>
            </td>
        </tr>

        {{-- ── BARIS EXPAND: Varian ─────────────────────── --}}
        <tr id="{{ $rowId }}" style="display:none;">
            <td colspan="11" style="padding:0;background:#fafafa;border-top:2px dashed rgba(255,107,107,.12);">
                <div style="display:flex;align-items:center;gap:10px;padding:12px 20px;background:#fff;border-bottom:1px solid rgba(0,0,0,.05);">
                    <span style="background:var(--color-secondary);color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:999px;">🔖 Varian</span>
                    <span style="font-size:.75rem;color:#6b7280;font-weight:600;">{{ $p->variants->count() }} varian</span>
                    <button type="button" class="clay-btn clay-btn-primary" style="margin-left:auto;padding:6px 12px;font-size:.72rem;"
                            onclick="openVariantModal('{{ $p->id }}', this)" data-store-url="{{ route('product.variant.store', $p) }}">＋ Tambah Varian</button>
                </div>
                @if($p->variants->isEmpty())
                    <div style="padding:22px;text-align:center;color:#9ca3af;font-size:.82rem;">
                        Belum ada varian. Klik <strong>＋ Tambah Varian</strong> (mis. power +1.00, +1.25).
                    </div>
                @else
                <div style="overflow-x:auto;padding-left:36px;border-left:3px solid rgba(78,205,196,.18);margin-left:16px;">
                    <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
                        <thead>
                            <tr style="background:#f9fefe;">
                                @foreach(['Kode','Nama Varian','Jenis','Power','Status','Aksi'] as $h)
                                <th style="padding:8px 10px;font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;
                                           text-align:{{ in_array($h,['Power','Status','Aksi']) ? 'right' : 'left' }};border-bottom:1px solid rgba(0,0,0,.05);">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($p->variants as $v)
                            <tr>
                                <td style="padding:8px 10px;"><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.68rem;">{{ $v->code }}</span></td>
                                <td style="padding:8px 10px;font-weight:600;">{{ $v->name }}</td>
                                <td style="padding:8px 10px;color:#6b7280;">{{ $v->jenis ?? '-' }}</td>
                                <td style="padding:8px 10px;text-align:right;font-weight:700;white-space:nowrap;">{{ (float)$v->power > 0 ? '+'.number_format($v->power,2,',','.') : '-' }}</td>
                                <td style="padding:8px 10px;text-align:right;">
                                    <label class="clay-toggle clay-toggle-sm" title="Ubah status varian">
                                        <input type="checkbox" data-toggle-url="{{ route('product.variant.toggle-status', $v) }}" {{ $v->status==='active'?'checked':'' }}>
                                        <span class="clay-toggle-slider"></span>
                                    </label>
                                </td>
                                <td style="padding:8px 10px;text-align:right;">
                                    <div style="display:flex;justify-content:flex-end;gap:4px;">
                                        <button type="button" class="clay-btn clay-btn-sm clay-btn-secondary" style="padding:3px 8px;font-size:.65rem;"
                                                onclick="openVariantModal('{{ $p->id }}', this)" title="Edit varian"
                                                data-store-url="{{ route('product.variant.store', $p) }}"
                                                data-url="{{ route('product.variant.update', $v) }}"
                                                data-id="{{ $v->id }}"
                                                data-code="{{ $v->code }}" data-name="{{ $v->name }}"
                                                data-jenis="{{ $v->jenis }}" data-power="{{ $v->power }}" data-status="{{ $v->status }}">✏️</button>
                                        <button type="button" class="clay-btn clay-btn-sm clay-btn-danger" style="padding:3px 8px;font-size:.65rem;"
                                                onclick="deleteVariant('{{ $v->id }}')" title="Hapus varian">🗑</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="11" style="text-align:center;padding:48px;color:#9ca3af;">Tidak ada produk ditemukan</td></tr>
        @endforelse
    </tbody>
</table>
