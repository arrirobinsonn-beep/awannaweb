@php
    $STATUS_LABELS = \App\Models\ShippingOrder::STATUS_LABELS;
@endphp

<div class="table-scroll ord-desktop-table">
    <div style="padding:12px 18px;font-size:.78rem;color:#6b7280;border-bottom:1px solid #f3f4f6;">Menampilkan {{ $orders->total() }} order</div>
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
                <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:24px;">📭 Belum ada order{{ request('batch') ? ' di batch ini' : '' }}.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Mobile cards --}}
<div class="ord-mobile-cards">
    @forelse($orders as $o)
        <div class="ord-card-item">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <div style="min-width:0;">
                    <a href="{{ route('orders.show', $o->id) }}" style="font-weight:700;font-size:.88rem;color:var(--color-primary,#FF6B6B);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;">{{ $o->customer_name }}</a>
                    <div style="font-size:.72rem;color:#6b7280;">{{ $o->order_id }}</div>
                </div>
                <span class="badge-order-status st-{{ $o->status }}">{{ str_replace('_', ' ', ucwords($o->status, '_')) }}</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:8px;font-size:.8rem;">
                <div style="display:flex;justify-content:space-between;"><span style="color:#9ca3af;font-size:.72rem;">Telp</span><span>{{ $o->phone }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#9ca3af;font-size:.72rem;">Produk</span><span>{{ $o->product_name ?? '-' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#9ca3af;font-size:.72rem;">Provinsi</span><span>{{ $o->province }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#9ca3af;font-size:.72rem;">Payment</span><span>{{ strtoupper($o->payment_method ?? '-') }}</span></div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;border-top:1px solid #f3f4f6;padding-top:8px;">
                <span class="badge-courier cou-{{ $o->courier }}">{{ $o->courier ?? '-' }}</span>
                @if(!empty($o->awb))
                    <span class="badge-courier" style="background:#d1fae5;color:#065f46;font-size:.65rem;">✓ {{ $o->awb }}</span>
                @endif
            </div>
        </div>
    @empty
        <div style="text-align:center;color:#9ca3af;padding:24px;">📭 Belum ada order.</div>
    @endforelse
</div>
