{{-- Tabel utama spending sisi admin/CS — dipakai ulang utk sub-tab Running & Testing.
     Variabel: $summaries (collection summaries per tanggal), $tabKey ('all' | id advertiser),
               $tabDisc (array tanggal discrepant), $activeTab, $emptyText (teks empty state),
               $wrapperId (id unik wrapper utk batas 5 baris), $hidden (opsional, tampilkan/sembunyikan) --}}
<div class="table-scroll table-scroll-limit table-scroll-maxrows" id="{{ $wrapperId }}"@if(!empty($hidden)) style="display:none;"@endif>
    <table class="clay-table">
        <thead>
            <tr>
                <th style="width:28px;"></th>
                <th>Tanggal</th>
                <th style="text-align:right;">Total Spending</th>
                <th style="text-align:right;">Lead</th>
                <th style="text-align:right;">Paid</th>
                <th style="text-align:right;">Paid Ratio</th>
                <th style="text-align:right;">CPA Lead</th>
                <th style="text-align:right;">CPA Paid</th>
            </tr>
        </thead>
        <tbody>
        @if($summaries->isEmpty())
        {{-- Empty state utk sub-tab tanpa data --}}
        <tr>
            <td colspan="8" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">💸</div>
                <p style="color:#9ca3af;">{{ $emptyText }}</p>
            </td>
        </tr>
        @else

        @foreach($summaries as $dateKey => $s)
        @php
            $lvl1 = 'g1-'.$tabKey.'-'.str_replace('-','',$dateKey);
            $isDisc = isset($tabDisc[$dateKey]);
        @endphp

        {{-- ── LEVEL 1: Baris Tanggal ──────────────────────── --}}
        <tr onclick="tog('{{ $lvl1 }}')" style="cursor:pointer;background:{{ $isDisc?'#fff0f0':'' }};"
            onmouseenter="this.style.background='{{ $isDisc?'#ffe0e0':'#fffbfb' }}'"
            onmouseleave="this.style.background='{{ $isDisc?'#fff0f0':'' }}'">
            <td style="text-align:center;padding:11px 8px;">
                <span id="chev-{{ $lvl1 }}"
                      style="display:inline-block;transition:transform .22s;
                             color:#9ca3af;font-size:.78rem;">▶</span>
            </td>
            <td style="font-weight:700;font-size:.88rem;">
                @if($isDisc)<span style="color:#ef4444;margin-right:6px;">⚠️</span>@endif
                {{ $s['tanggal']->translatedFormat('l, d M Y') }}
                @if($isDisc)
                <span style="display:inline-block;background:#fef2f2;color:#dc2626;font-size:.6rem;font-weight:700;padding:0 6px;border-radius:999px;margin-left:6px;vertical-align:middle;">DATA TIDAK SESUAI</span>
                @endif
                <div style="font-size:.68rem;color:#9ca3af;font-weight:400;">
                    {{ $s['total_produk'] }} produk diiklankan
                </div>
            </td>
            <td style="text-align:right;font-weight:800;color:var(--color-primary);white-space:nowrap;">
                Rp {{ number_format($s['spending'],0,',','.') }}
            </td>
            <td style="text-align:right;font-weight:700;color:var(--color-purple);">{{ number_format($s['lead']) }}</td>
            <td style="text-align:right;font-weight:700;color:var(--color-secondary);">{{ number_format($s['paid']) }}</td>
            <td style="text-align:right;">
                <span class="clay-badge {{ $s['paid_ratio']>=75?'clay-badge-green':($s['paid_ratio']>=50?'clay-badge-yellow':'clay-badge-red') }}">
                    {{ round($s['paid_ratio']) }}%
                </span>
            </td>
            <td style="text-align:right;font-size:.82rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($s['cpa_lead'],0,',','.') }}</td>
            <td style="text-align:right;font-size:.82rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($s['cpa_paid'],0,',','.') }}</td>
        </tr>

        {{-- ── LEVEL 1 Expand ──────────────────────────────── --}}
        <tr id="{{ $lvl1 }}" style="display:none;">
            <td colspan="8" style="padding:0;background:#fafafa;
                border-top:2px dashed rgba(255,107,107,.1);">

                @foreach($s['by_product'] as $prodId => $prodData)
                @php $lvl2 = 'g2-'.$tabKey.'-'.str_replace('-','',$dateKey).'-'.$prodId; @endphp

                {{-- ── LEVEL 2: Header Produk ──────────────── --}}
                <div style="border-bottom:1px solid rgba(0,0,0,.05);">
                    <div onclick="tog('{{ $lvl2 }}')"
                         style="display:flex;align-items:center;gap:12px;
                                padding:10px 20px;cursor:pointer;transition:background .15s;"
                         onmouseenter="this.style.background='#f3f4f6'"
                         onmouseleave="this.style.background=''">

                        <span id="chev-{{ $lvl2 }}"
                              style="display:inline-block;transition:transform .22s;
                                     color:var(--color-secondary);font-size:.72rem;flex-shrink:0;">▶</span>

                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span style="background:var(--color-secondary);color:#fff;
                                             font-size:.62rem;font-weight:700;padding:2px 8px;
                                             border-radius:999px;flex-shrink:0;">📦 Produk</span>
                                <span style="font-weight:700;font-size:.85rem;color:#1e1b2e;">
                                    {{ $prodData['product']->name ?? 'Tidak Diketahui' }}
                                </span>
                                <span style="font-size:.68rem;color:#9ca3af;">
                                    {{ $prodData['product']->code ?? '' }}
                                </span>
                                @if(($prodData['product']->ad_status ?? 'running') === 'testing')
                                <span style="display:inline-block;font-size:.58rem;font-weight:700;padding:1px 6px;border-radius:999px;background:#fef3c7;color:#92400e;">🔬 Testing</span>
                                @endif
                            </div>
                            <div style="font-size:.67rem;color:#9ca3af;margin-top:2px;padding-left:52px;">
                                {{ count($prodData['whitelists']) }} whitelist mengiklankan produk ini
                            </div>
                        </div>

                        <div style="display:flex;gap:14px;flex-shrink:0;align-items:center;">
                            <div style="text-align:right;">
                                <div style="font-size:.66rem;color:#9ca3af;">Spending</div>
                                <div style="font-weight:700;font-size:.82rem;color:var(--color-primary);white-space:nowrap;">
                                    Rp {{ number_format($prodData['spending'],0,',','.') }}
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:.66rem;color:#9ca3af;">Lead / Paid</div>
                                <div style="font-weight:700;font-size:.82rem;">
                                    <span style="color:var(--color-purple);">{{ $prodData['lead'] }}</span>
                                    <span style="color:#d1d5db;"> / </span>
                                    <span style="color:var(--color-secondary);">{{ $prodData['paid'] }}</span>
                                </div>
                            </div>
                            <span class="clay-badge {{ $prodData['paid_ratio']>=75?'clay-badge-green':($prodData['paid_ratio']>=50?'clay-badge-yellow':'clay-badge-red') }}"
                                  style="font-size:.67rem;">{{ round($prodData['paid_ratio']) }}%</span>
                        </div>
                    </div>

                    {{-- ── LEVEL 3: Whitelist rows ──────────── --}}
                    <div id="{{ $lvl2 }}"
                         style="display:none;background:#fff;
                                border-top:1px dashed rgba(78,205,196,.2);">
                        <table style="width:100%;">
                            <thead>
                                <tr style="background:#f9fefe;">
                                    <th style="padding:6px 20px 6px 36px;font-size:.64rem;font-weight:700;
                                               color:#9ca3af;text-transform:uppercase;text-align:left;
                                               border-bottom:1px solid rgba(0,0,0,.05);">Whitelist</th>
                                    @foreach(['Spending','Lead','Paid','Paid Ratio','CPA Lead','CPA Paid'] as $h)
                                    <th style="padding:6px 10px;font-size:.64rem;font-weight:700;
                                               color:#9ca3af;text-transform:uppercase;text-align:right;
                                               border-bottom:1px solid rgba(0,0,0,.05);">{{ $h }}</th>
                                    @endforeach
                                    <th style="padding:6px 10px;font-size:.64rem;font-weight:700;
                                               color:#9ca3af;text-transform:uppercase;text-align:center;
                                               border-bottom:1px solid rgba(0,0,0,.05);">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($prodData['whitelists'] as $item)
                            @php
                                $itemStatus = $item->status ?? 'pending';
                                $itemStatusClass = $itemStatus === 'approved' ? 'clay-badge-green' : 'clay-badge-yellow';
                            @endphp
                            <tr onmouseenter="this.style.background='#f0fffe'"
                                onmouseleave="this.style.background=''">
                                <td style="padding:7px 20px 7px 36px;">
                                    <div style="min-width:0;">
                                            <div style="font-weight:600;font-size:.8rem;">{{ $item->whitelist->nama ?? '-' }}</div>
                                            <div style="font-size:.65rem;color:#9ca3af;">{{ $item->whitelist->kode ?? '' }}</div>
                                            @if($activeTab === 'all')
                                            <div style="font-size:.62rem;color:var(--color-secondary);font-weight:600;">
                                                👤 {{ $item->whitelist->user->display_name ?? '-' }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:7px 10px;text-align:right;font-weight:700;
                                           color:var(--color-primary);font-size:.78rem;white-space:nowrap;">
                                    Rp {{ number_format($item->spending,0,',','.') }}
                                </td>
                                <td style="padding:7px 10px;text-align:right;font-size:.78rem;color:var(--color-purple);font-weight:700;">{{ $item->lead }}</td>
                                <td style="padding:7px 10px;text-align:right;font-size:.78rem;color:var(--color-secondary);font-weight:700;">{{ $item->paid }}</td>
                                <td style="padding:7px 10px;text-align:right;">
                                    <span class="clay-badge {{ $item->paid_ratio>=75?'clay-badge-green':($item->paid_ratio>=50?'clay-badge-yellow':'clay-badge-red') }}"
                                          style="font-size:.64rem;">{{ round($item->paid_ratio) }}%</span>
                                </td>
                                <td style="padding:7px 10px;text-align:right;font-size:.74rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($item->cpa_lead,0,',','.') }}</td>
                                <td style="padding:7px 10px;text-align:right;font-size:.74rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($item->cpa_paid,0,',','.') }}</td>
                                <td style="padding:7px 10px;text-align:center;">
                                    <span class="clay-badge {{ $itemStatusClass }}" style="font-size:.62rem;">
                                        {{ $itemStatus === 'approved' ? '✓ Approved' : 'Pending' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                            {{-- Total baris produk --}}
                            <tr style="background:#f0fffe;font-weight:700;">
                                <td style="padding:6px 20px 6px 36px;font-size:.74rem;color:var(--color-secondary);">Total</td>
                                <td style="padding:6px 10px;text-align:right;font-size:.78rem;color:var(--color-primary);white-space:nowrap;">Rp {{ number_format($prodData['spending'],0,',','.') }}</td>
                                <td style="padding:6px 10px;text-align:right;font-size:.78rem;color:var(--color-purple);">{{ $prodData['lead'] }}</td>
                                <td style="padding:6px 10px;text-align:right;font-size:.78rem;color:var(--color-secondary);">{{ $prodData['paid'] }}</td>
                                <td style="padding:6px 10px;text-align:right;">
                                    <span class="clay-badge {{ $prodData['paid_ratio']>=75?'clay-badge-green':($prodData['paid_ratio']>=50?'clay-badge-yellow':'clay-badge-red') }}"
                                          style="font-size:.64rem;">{{ $prodData['paid_ratio'] }}%</span>
                                </td>
                                <td style="padding:6px 10px;text-align:right;font-size:.74rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($prodData['cpa_lead'],0,',','.') }}</td>
                                <td style="padding:6px 10px;text-align:right;font-size:.74rem;color:#6b7280;white-space:nowrap;">Rp {{ number_format($prodData['cpa_paid'],0,',','.') }}</td>
                                <td></td>{{-- kolom status --}}
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>{{-- end produk --}}
                @endforeach

            </td>
        </tr>{{-- end lvl1 expand --}}

        @endforeach
        @endif
        </tbody>
    </table>
</div>
