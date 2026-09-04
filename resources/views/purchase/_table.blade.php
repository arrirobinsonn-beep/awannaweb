@php
    $statusMap = [
        'in_transit' => ['class' => 'clay-badge-yellow', 'icon' => '📋', 'label' => 'Belum Masuk'],
        'received'   => ['class' => 'clay-badge-green',  'icon' => '✅', 'label' => 'Diterima'],
    ];
@endphp

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
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $pu)
                @php
                    $total = $pu->totalCost();
                    $ps = $statusMap[$pu->status] ?? ['class' => 'clay-badge-gray', 'icon' => '•', 'label' => $pu->status];
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
                        @if($pu->received_note)
                            <div style="font-size:.68rem;color:#065f46;margin-top:3px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ e($pu->received_note) }}">📝 {{ Str::limit($pu->received_note, 35) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($pu->isInTransit())
                            <button type="button" class="clay-btn clay-btn-primary"
                                    style="padding:4px 10px;font-size:.7rem;"
                                    onclick="openReceiveModal({{ $pu->id }}, '{{ e($pu->variant?->product?->name ?? '-') }} {{ e($pu->variant?->name ?? '-') }}', {{ $pu->quantity }}, {{ (float)$pu->unit_price }}, 'Rp {{ number_format($total,0,',','.') }}')">
                                📦 Terima
                            </button>
                        @endif
                        <button type="button" class="clay-btn clay-btn-sm clay-btn-danger pu-del-btn"
                                data-id="{{ $pu->id }}"
                                data-confirm="Hapus pembelian ini?{{ $pu->isReceived() ? ' Stok & HPP akan dikembalikan.' : '' }}"
                                onclick="deletePurchase(this)">
                            🗑 Hapus
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:48px;color:#9ca3af;">
                        <div style="font-size:2rem;margin-bottom:8px;">📭</div>
                        Belum ada pembelian.
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
            $total = $pu->totalCost();
            $ps = $statusMap[$pu->status] ?? ['class' => 'clay-badge-gray', 'icon' => '•', 'label' => $pu->status];
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
                @if($pu->received_note)
                    <div class="pm-card-row"><span class="pm-card-key">Catatan</span><span style="font-size:.75rem;color:#065f46;">{{ Str::limit($pu->received_note, 60) }}</span></div>
                @endif
            </div>
            <div class="pm-card-footer">
                @if($pu->isInTransit())
                    <button type="button" class="clay-btn clay-btn-primary"
                            style="padding:5px 12px;font-size:.72rem;"
                            onclick="openReceiveModal({{ $pu->id }}, '{{ e($pu->variant?->product?->name ?? '-') }} {{ e($pu->variant?->name ?? '-') }}', {{ $pu->quantity }}, {{ (float)$pu->unit_price }}, 'Rp {{ number_format($total,0,',','.') }}')">
                        📦 Terima
                    </button>
                @endif
                <button type="button" class="clay-btn clay-btn-sm clay-btn-danger pu-del-btn"
                        data-id="{{ $pu->id }}"
                        data-confirm="Hapus pembelian ini?{{ $pu->isReceived() ? ' Stok & HPP akan dikembalikan.' : '' }}"
                        onclick="deletePurchase(this)">
                    🗑 Hapus
                </button>
            </div>
        </div>
    @empty
        <div style="text-align:center;padding:48px;color:#9ca3af;">
            <div style="font-size:2rem;margin-bottom:8px;">📭</div>
            Belum ada pembelian.
        </div>
    @endforelse
</div>
