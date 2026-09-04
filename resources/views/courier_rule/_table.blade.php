<table class="clay-table">
    <thead>
        <tr>
            <th style="width:70px;text-align:center;">Urutan</th>
            <th>Metode Bayar</th>
            <th>Provinsi</th>
            <th>Kode Produk</th>
            <th>Courier</th>
            <th style="text-align:center;">Status</th>
            <th style="width:170px;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rules as $rule)
        <tr style="{{ $rule->is_active ? '' : 'opacity:.55;' }}">
            <td style="text-align:center;font-weight:700;color:#6b7280;">{{ $rule->sort_order }}</td>
            <td>
                @if($rule->payment_method)
                    <span class="clay-badge cr-badge-pm">{{ $rule->payment_method }}</span>
                @else
                    <span class="clay-badge cr-badge-all">Semua</span>
                @endif
            </td>
            <td>
                @if($rule->province)
                    <span class="clay-badge cr-badge-prov" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;">{{ $rule->province }}</span>
                @else
                    <span class="clay-badge cr-badge-all">Semua Provinsi</span>
                @endif
            </td>
            <td>
                @if($rule->product_code)
                    <span class="clay-badge cr-badge-code">{{ $rule->product_code }}</span>
                @else
                    <span class="clay-badge cr-badge-all">Semua</span>
                @endif
            </td>
            <td>
                <span class="clay-badge cou-{{ $rule->courier }} cr-badge-cou">{{ $rule->courier }}</span>
            </td>
            <td style="text-align:center;">
                <button type="button" class="cr-toggle {{ $rule->is_active ? 'on' : 'off' }} cr-toggle-btn"
                        data-id="{{ $rule->id }}"
                        title="Klik untuk {{ $rule->is_active ? 'menonaktifkan' : 'mengaktifkan' }}">
                    {{ $rule->is_active ? '● Aktif' : '○ Nonaktif' }}
                </button>
            </td>
            <td>
                <div class="cr-aksi">
                    <button type="button" class="cr-move cr-move-btn" data-id="{{ $rule->id }}" data-dir="up"
                            title="Naikkan prioritas" {{ $loop->first ? 'disabled' : '' }}>↑</button>
                    <button type="button" class="cr-move cr-move-btn" data-id="{{ $rule->id }}" data-dir="down"
                            title="Turunkan prioritas" {{ $loop->last ? 'disabled' : '' }}>↓</button>

                    <button type="button" class="cr-edit-btn" id="cr-edit-{{ $rule->id }}"
                            onclick="openCrEdit({{ $rule->id }})"
                            data-sort="{{ $rule->sort_order }}"
                            data-payment="{{ $rule->payment_method ?? '' }}"
                            data-province="{{ $rule->province ?? '' }}"
                            data-product="{{ $rule->product_code ?? '' }}"
                            data-courier="{{ $rule->courier }}"
                            data-active="{{ $rule->is_active ? '1' : '' }}">✏️ Edit</button>

                    <button type="button" class="cr-del-btn cr-del-btn-ajax"
                            data-id="{{ $rule->id }}"
                            data-confirm="Hapus aturan {{ $rule->courier }} untuk {{ $rule->province ?? 'semua provinsi' }}{{ $rule->product_code ? ' (produk '.$rule->product_code.')' : '' }}?">🗑 Hapus</button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;padding:48px;color:#9ca3af;">
                Belum ada aturan. Tambahkan aturan pertama di form sebelah kiri.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
