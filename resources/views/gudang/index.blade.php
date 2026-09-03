@extends('layouts.app')
@section('title','Gudang')
@section('page-title','🏬 Gudang')
@section('page-subtitle','Pilih gudang — kelola stok, produk & aturan kemasan per gudang')

@push('styles')
<style>
    /* Modal & toggle styles — centralized in clay.css (clay-modal, clay-toggle) */
</style>
@endpush

@section('content')
@if(session('success'))
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#d1fae5;color:#065f46;font-weight:600;border-radius:8px;">
    ✅ {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#fee2e2;color:#991b1b;font-weight:600;border-radius:8px;">
    @foreach($errors->all() as $e)<div>⚠ {{ $e }}</div>@endforeach
</div>
@endif

{{-- ─── Pilih Gudang ─────────────────────────────────────────────────── --}}
<div class="clay-card" style="padding:20px 24px;margin-bottom:20px;" data-reveal>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
        <span style="font-size:1.2rem;">🏭</span>
        <h2 style="margin:0;font-size:1.05rem;font-weight:800;">Pilih Gudang</h2>
        <span class="clay-badge clay-badge-blue">{{ $inventories->count() }} gudang</span>
    </div>
    <div style="font-size:.78rem;color:#9ca3af;margin-bottom:14px;">
        Setiap gudang punya halaman sendiri — stok, produk & aturan kemasannya terpisah agar jelas.
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        @foreach($inventories as $inv)
        @php
            $isActive = $inventory && $inventory->id === $inv->id;
            $count = $isActive ? null : ($inv->products()->where('goods_type','!=','consumable')->count());
        @endphp
        <a href="{{ route('gudang.index', ['inventory_id' => $inv->id]) }}"
           data-page-link
           style="flex:1;min-width:180px;max-width:260px;text-decoration:none;border-radius:14px;padding:14px 16px;
                  border:2px solid {{ $isActive ? '#7c3aed' : 'rgba(0,0,0,.08)' }};
                  background:{{ $isActive ? '#f5f3ff' : '#fff' }};
                  box-shadow:{{ $isActive ? '4px 4px 0 rgba(124,58,237,.18)' : '4px 4px 0 rgba(0,0,0,.05)' }};
                  transition:all .15s ease;">
            <div style="font-weight:800;font-size:.95rem;color:{{ $isActive ? '#7c3aed' : '#111827' }};">🏭 {{ $inv->name }}</div>
            <div style="font-size:.72rem;color:#9ca3af;margin-top:4px;">
                @if($isActive)
                    <span style="color:#7c3aed;font-weight:700;">Sedang dibuka →</span>
                @else
                    {{ $count }} produk inti/additional
                @endif
            </div>
        </a>
        @endforeach
    </div>

    @if($inventory === null)
        <div style="margin-top:16px;padding:20px;text-align:center;background:#fafafa;border:1px dashed #d1d5db;border-radius:12px;color:#9ca3af;font-size:.85rem;">
            👆 Klik salah satu gudang di atas untuk melihat & mengelola stok, produk, dan aturan kemasannya.
        </div>
    @endif
</div>

@if($inventory)
@php
    $sectionInfo = [
        'consumable' => ['icon' => '🧻', 'desc' => 'Barang habis pakai (Kertas Thermal, Lakban, Bubble Wrap) — ADA DI SETIAP GUDANG. Stok ditambah/dikurangi MANUAL per gudang oleh admin.'],
        'core'       => ['icon' => '🎯', 'desc' => 'Barang yang dijual/dikirim dari gudang ini. Stok berkurang OTOMATIS saat order di-export atau shipment di-import.'],
        'additional' => ['icon' => '📦', 'desc' => 'Barang pendamping barang inti (BOX, LAP). Berkurang OTOMATIS mengikuti barang inti sesuai aturan kemasan.'],
    ];
    $badgeClass = [
        'consumable' => 'clay-badge-gray',
        'core'       => 'clay-badge-blue',
        'additional' => 'clay-badge-purple',
    ];
@endphp

{{-- Header gudang aktif --}}
<div class="clay-card" style="padding:18px 24px;margin-bottom:20px;background:linear-gradient(120deg,#f5f3ff,#fff);" data-reveal>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="font-size:1.6rem;">🏭</span>
        <div>
            <h2 style="margin:0;font-size:1.15rem;font-weight:800;color:#6d28d9;">{{ $inventory->name }}</h2>
            <div style="font-size:.78rem;color:#9ca3af;">
                Barang Pasti: {{ $groups['consumable']->count() }} ·
                Barang Inti: {{ $groups['core']->count() }} ·
                Barang Additional: {{ $groups['additional']->count() }}
            </div>
        </div>
    </div>
</div>

@foreach($groups as $type => $list)
@php $colspan = $type === 'consumable' ? 8 : 7; @endphp
<div class="clay-card" style="padding:20px 24px;margin-bottom:20px;" data-reveal>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
        <span style="font-size:1.2rem;">{{ $sectionInfo[$type]['icon'] }}</span>
        <h2 style="margin:0;font-size:1.05rem;font-weight:800;">{{ \App\Models\Product::GOODS_TYPE_LABELS[$type] }}</h2>
        <span class="clay-badge {{ $badgeClass[$type] }}">{{ $list->count() }} produk</span>
        <button type="button" class="clay-btn clay-btn-primary" style="margin-left:auto;padding:8px 14px;font-size:.78rem;"
                onclick="openAttachModal(this)"
                data-goods-type="{{ $type }}" data-inventory-id="{{ $inventory->id }}">
            ＋ Tambah Produk ke Gudang
        </button>
    </div>
    <div style="font-size:.78rem;color:#9ca3af;margin-bottom:14px;">{{ $sectionInfo[$type]['desc'] }}</div>

    @if($list->isEmpty())
        <div style="padding:24px;text-align:center;color:#9ca3af;font-size:.85rem;">Tidak ada produk kategori ini di gudang {{ $inventory->name }}.</div>
    @else
    <div class="table-scroll">
        <table class="clay-table" style="min-width:860px;">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Produk</th>
                    <th style="text-align:right;">Stok di {{ $inventory->name }}</th>
                    <th>Per Varian</th>
                    <th>Min. Stok</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:right;">Aksi</th>
                    @if($type === 'consumable')
                    <th style="min-width:260px;">Penyesuaian Manual</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($list as $p)
                @php
                    $stockHere = 0;
                    foreach ($p->variants as $v) {
                        $stockHere += $perVariant[$v->id][$inventory->id] ?? 0;
                    }
                    $needsRestock = $p->min_stock > 0 && $stockHere <= $p->min_stock;
                    $rowId = 'var-'.$p->id;
                @endphp
                <tr>
                    <td style="font-weight:800;color:#7c3aed;">{{ $p->code }}</td>
                    <td>
                        <div style="font-weight:600;">{{ $p->name }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;">Satuan: {{ $p->unit }}{{ $p->category ? ' · '.$p->category : '' }}</div>
                    </td>
                    <td style="text-align:right;font-weight:800;font-size:1rem;">{{ number_format($stockHere,0,',','.') }}</td>
                    <td>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:240px;">
                            @forelse($p->variants as $v)
                                @php $vs = $perVariant[$v->id][$inventory->id] ?? 0; @endphp
                                <span style="font-size:.68rem;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:1px 6px;color:#4b5563;"
                                      title="{{ $v->name }}">
                                    @if((float)$v->power > 0)+{{ rtrim(rtrim(number_format($v->power,2,'.',''),'0'),'.') }}@else {{ $v->code }}@endif: {{ number_format($vs,0,',','.') }}
                                </span>
                            @empty
                                <span style="font-size:.72rem;color:#9ca3af;">—</span>
                            @endforelse
                        </div>
                    </td>
                    <td>
                        @if($p->min_stock > 0)
                            <span style="font-weight:700;color:#374151;">{{ number_format($p->min_stock,0,',','.') }}</span>
                            <div style="margin-top:4px;">
                                @if($needsRestock)
                                    <span class="clay-badge clay-badge-red">⚠ Perlu Restock</span>
                                @else
                                    <span class="clay-badge clay-badge-green">Stok Aman</span>
                                @endif
                            </div>
                        @else
                            <span style="color:#9ca3af;font-size:.78rem;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($p->status === 'active')
                            <span class="clay-badge clay-badge-green">Aktif</span>
                        @else
                            <span class="clay-badge clay-badge-gray">Nonaktif</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <div style="display:flex;justify-content:flex-end;gap:6px;align-items:center;flex-wrap:wrap;">
                            <button type="button" class="clay-btn clay-btn-sm" style="background:#f3f4f6;color:#374151;"
                                    onclick="toggleVarian('{{ $p->id }}')" title="Lihat varian & stoknya di gudang ini">
                                🔖 Varian ({{ $p->variants->count() }}) <span id="chev-{{ $rowId }}" style="display:inline-block;transition:transform .22s;font-size:.7rem;">▾</span>
                            </button>
                            <button type="button" class="clay-btn clay-btn-sm clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;"
                                    onclick="openWarehouseModal(this)" title="Kelola gudang produk (terdaftar di mana + gudang utama)"
                                    data-url="{{ route('gudang.product.warehouses', $p) }}"
                                    data-wh="{{ $p->inventories->pluck('id')->implode(',') }}"
                                    data-primary-wh="{{ $p->primaryInventoryId() }}"
                                    data-goods-type="{{ $p->goods_type }}">🏷</button>
                            <form method="POST" action="{{ route('gudang.product.detach', $p) }}" onsubmit="return confirm('Lepas produk {{ $p->name }} dari gudang {{ $inventory->name }}? (produk tetap ada di halaman Produk)')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="inventory_id" value="{{ $inventory->id }}">
                                <button type="submit" class="clay-btn clay-btn-sm clay-btn-danger" style="padding:5px 10px;font-size:.72rem;" title="Lepas dari gudang ini">🗑</button>
                            </form>
                        </div>
                    </td>
                    @if($type === 'consumable')
                    <td>
                        @php $pv = $p->variants->first(); @endphp
                        @if($pv)
                            <form method="POST" action="{{ route('gudang.adjust') }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                @csrf
                                <input type="hidden" name="product_variant_id" value="{{ $pv->id }}">
                                <input type="hidden" name="inventory_id" value="{{ $inventory->id }}">
                                <input type="number" name="quantity" required min="1" value="1" style="width:60px;" class="clay-input">
                                <input type="text" name="note" maxlength="255" placeholder="catatan (opsional)" style="flex:1;min-width:80px;" class="clay-input">
                                <button type="submit" name="direction" value="in" class="clay-btn clay-btn-sm clay-btn-primary" style="background:#059669;">＋</button>
                                <button type="submit" name="direction" value="out" class="clay-btn clay-btn-sm clay-btn-danger">−</button>
                            </form>
                        @else
                            <span style="color:#9ca3af;font-size:.78rem;">belum ada varian</span>
                        @endif
                    </td>
                    @endif
                </tr>

                {{-- ── BARIS EXPAND: Varian ─────────────────────── --}}
                <tr id="{{ $rowId }}" style="display:none;">
                    <td colspan="{{ $colspan }}" style="padding:0;background:#fafafa;border-top:2px dashed rgba(255,107,107,.12);">
                        <div style="display:flex;align-items:center;gap:10px;padding:12px 20px;background:#fff;border-bottom:1px solid rgba(0,0,0,.05);">
                            <span style="background:var(--color-secondary);color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:999px;flex-shrink:0;">🔖 Varian</span>
                            <span style="font-size:.75rem;color:#6b7280;font-weight:600;">{{ $p->variants->count() }} varian · stok di gudang ini</span>
                            <span style="margin-left:auto;font-size:.7rem;color:#9ca3af;">Varian dikelola di halaman <a href="{{ route('product.index') }}" data-page-link style="color:#7c3aed;font-weight:700;">Produk</a></span>
                        </div>
                        @if($p->variants->isEmpty())
                            <div style="padding:22px;text-align:center;color:#9ca3af;font-size:.82rem;">
                                Belum ada varian — tambahkan di halaman Produk.
                            </div>
                        @else
                        <div style="overflow-x:auto;padding-left:36px;border-left:3px solid rgba(78,205,196,.18);margin-left:16px;">
                            <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
                                <thead>
                                    <tr style="background:#f9fefe;">
                                        @foreach(['Kode','Nama Varian','Jenis','Power','Stok di Gudang Ini'] as $h)
                                        <th style="padding:8px 10px;font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;
                                                   text-align:{{ $h === 'Stok di Gudang Ini' || $h === 'Power' ? 'right' : 'left' }};border-bottom:1px solid rgba(0,0,0,.05);">{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($p->variants as $v)
                                    @php $vs = $perVariant[$v->id][$inventory->id] ?? 0; @endphp
                                    <tr>
                                        <td style="padding:8px 10px;"><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.68rem;">{{ $v->code }}</span></td>
                                        <td style="padding:8px 10px;font-weight:600;">{{ $v->name }}</td>
                                        <td style="padding:8px 10px;color:#6b7280;">{{ $v->jenis ?? '-' }}</td>
                                        <td style="padding:8px 10px;text-align:right;font-weight:700;white-space:nowrap;">{{ (float)$v->power > 0 ? '+'.number_format($v->power,2,',','.') : '-' }}</td>
                                        <td style="padding:8px 10px;text-align:right;">
                                            <span class="clay-badge {{ $vs>20?'clay-badge-green':($vs>0?'clay-badge-yellow':'clay-badge-red') }}">{{ number_format($vs,0,',','.') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endforeach

{{-- ─── Aturan Kemasan (gudang ini + global) ─────────────────────────── --}}
<div class="clay-card" style="padding:20px 24px;margin-bottom:20px;" data-reveal>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
        <span style="font-size:1.2rem;">⚙️</span>
        <h2 style="margin:0;font-size:1.05rem;font-weight:800;">Aturan Kemasan</h2>
        <span class="clay-badge clay-badge-yellow">{{ $rules->count() }} rule</span>
    </div>
    <div style="font-size:.78rem;color:#9ca3af;margin-bottom:14px;">
        <b>Additional</b>: setiap <b>qty_per</b> barang inti terkirim → 1 barang pendamping ikut keluar
        (mis. KMP → BOX). <b>Split</b>: barang inti dipecah — <b>qty_per</b> unit = 1 unit inti + 1 unit
        bonus (mis. promo "Beli 1 Dapat 2": KMP → KDF).
        Rule khusus gudang menimpa rule "Semua Gudang" untuk kombinasi yang sama.
    </div>

    <form method="POST" action="{{ route('gudang.packaging-store') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;background:#fafafa;border:1px dashed #d1d5db;border-radius:12px;padding:14px;margin-bottom:16px;">
        @csrf
        <div style="flex:1;min-width:160px;">
            <label class="field-label">GUDANG</label>
            <select name="inventory_id" class="clay-input">
                <option value="">Semua Gudang</option>
                @foreach($inventories as $inv)
                    <option value="{{ $inv->id }}" {{ $inventory->id === $inv->id ? 'selected' : '' }}>{{ $inv->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:170px;">
            <label class="field-label">JENIS ATURAN</label>
            <select name="rule_type" class="clay-input">
                <option value="additional">Additional — 1 pendamping per qty_per</option>
                <option value="split">Split — pecah inti + bonus (Beli 1 Dapat 2)</option>
            </select>
        </div>
        <div style="flex:1;min-width:170px;">
            <label class="field-label">BARANG INTI (SUMBER)</label>
            <select name="source_product_id" required class="clay-input">
                <option value="">— Pilih —</option>
                @foreach($groups['core'] as $p)
                    <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:170px;">
            <label class="field-label">BARANG PENDAMPING (TARGET)</label>
            <select name="target_product_id" required class="clay-input">
                <option value="">— Pilih —</option>
                @foreach($groups['core']->merge($groups['additional']) as $p)
                    <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}{{ $p->goods_type === 'additional' ? ' (additional)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">QTY_PER</label>
            <input type="number" name="qty_per" required min="1" value="2" class="clay-input" style="width:80px;">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary">＋ Tambah Rule</button>
    </form>

    @if($rules->isEmpty())
        <div style="padding:24px;text-align:center;color:#9ca3af;font-size:.85rem;">Belum ada aturan kemasan untuk gudang ini.</div>
    @else
    <div class="table-scroll">
        <table class="clay-table" style="min-width:760px;">
            <thead>
                <tr>
                    <th>Gudang</th>
                    <th>Barang Inti</th>
                    <th>→ Pendamping</th>
                    <th style="min-width:190px;">Jenis & Rasio (per berapa qty)</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rules as $rule)
                <tr>
                    <td>
                        @if($rule->inventory_id)
                            <span class="clay-badge clay-badge-blue">🏭 {{ $rule->inventory?->name ?? 'Gudang #'.$rule->inventory_id }}</span>
                        @else
                            <span class="clay-badge clay-badge-gray">Semua Gudang</span>
                        @endif
                    </td>
                    <td style="font-weight:600;">{{ $rule->sourceProduct?->code ?? '-' }} <span style="color:#9ca3af;font-size:.75rem;">{{ $rule->sourceProduct?->name }}</span></td>
                    <td style="font-weight:600;">{{ $rule->targetProduct?->code ?? '-' }} <span style="color:#9ca3af;font-size:.75rem;">{{ $rule->targetProduct?->name }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('gudang.packaging-update', $rule) }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                            @csrf @method('PUT')
                            <select name="rule_type" class="clay-input" style="width:130px;">
                                <option value="additional" {{ $rule->rule_type === 'additional' ? 'selected' : '' }}>Additional</option>
                                <option value="split" {{ $rule->rule_type === 'split' ? 'selected' : '' }}>Split (1:1 bonus)</option>
                            </select>
                            <input type="number" name="qty_per" required min="1" value="{{ $rule->qty_per }}" class="clay-input" style="width:60px;">
                            <input type="hidden" name="is_active" value="0">
                            <label style="display:flex;align-items:center;gap:4px;font-size:.75rem;color:#374151;cursor:pointer;">
                                <input type="checkbox" name="is_active" value="1" {{ $rule->is_active ? 'checked' : '' }} onchange="this.form.submit()"> Aktif
                            </label>
                            <button type="submit" class="clay-btn clay-btn-sm">Simpan</button>
                        </form>
                    </td>
                    <td>
                        @if($rule->is_active)
                            <span class="clay-badge clay-badge-green">Aktif</span>
                        @else
                            <span class="clay-badge clay-badge-gray">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('gudang.packaging-destroy', $rule) }}" onsubmit="return confirm('Hapus aturan ini?')">
                            @csrf @method('DELETE')
                            <button class="clay-btn clay-btn-sm clay-btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endif

{{-- ═══════════════ MODAL TAMBAH PRODUK KE GUDANG ═══════════════ --}}
<div class="clay-modal" id="modal-attach" role="dialog" aria-modal="true">
    <div class="clay-modal-backdrop" onclick="closeAttachModal()"></div>
    <div class="clay-modal-container" style="max-width:520px;">
        <div class="clay-modal-header">
            <h2>➕ Tambah Produk ke Gudang</h2>
            <button class="clay-modal-close" onclick="closeAttachModal()" type="button">✕</button>
        </div>
        <form method="POST" action="{{ route('gudang.product.attach') }}">
            @csrf
            <div class="clay-modal-body">
                <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;margin-bottom:4px;">PILIH PRODUK (sudah ada di halaman Produk) <span style="color:#f87171;">*</span></label>
                <select name="product_id" id="am-product-id" required class="clay-input" style="width:100%;">
                    <option value="">— Pilih produk —</option>
                    @foreach(($availableProducts ?? collect()) as $ap)
                    <option value="{{ $ap->id }}" data-goods-type="{{ $ap->goods_type }}">{{ $ap->code }} — {{ $ap->name }} ({{ $ap->variants->count() }} varian)</option>
                    @endforeach
                </select>
                <div id="am-empty" style="display:none;margin-top:8px;font-size:.76rem;color:#9ca3af;background:#fafafa;border:1px dashed #d1d5db;border-radius:8px;padding:8px 12px;">
                    Semua produk tipe ini sudah terdaftar di gudang ini.
                </div>

                <input type="hidden" name="inventory_id" id="am-inventory-id">

                <div style="margin-top:14px;">
                    <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;margin-bottom:4px;">STOK AWAL (opsional — varian default)</label>
                    <input type="number" name="stock_awal" min="0" value="0" class="clay-input" style="width:140px;">
                    <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Stok lain bisa ditambah lewat Barang Masuk / penyesuaian manual.</div>
                </div>

                {{-- Gudang utama HANYA untuk Barang Inti (core) — ditampilkan via JS saat produk core dipilih --}}
                <div id="am-primary-wrap" style="display:none;">
                    <label style="display:flex;gap:8px;align-items:center;margin-top:14px;font-size:.8rem;color:#374151;cursor:pointer;">
                        <input type="checkbox" name="is_primary" value="1" style="accent-color:#7c3aed;"> Jadikan gudang utama (dipakai export/pengambilan)
                    </label>
                </div>

                <div style="margin-top:14px;font-size:.72rem;color:#9ca3af;background:#fafafa;border-radius:8px;padding:8px 12px;">
                    Produk dibuat di halaman <b>Produk</b> — di sini hanya mendaftarkan produk + variannya ke gudang ini.
                    <a href="{{ route('product.index') }}" data-page-link style="color:#7c3aed;font-weight:700;">Buat produk baru →</a>
                </div>
            </div>
            <div class="clay-modal-footer">
                <button class="clay-btn clay-btn-outline" onclick="closeAttachModal()" type="button">Batal</button>
                <button class="clay-btn clay-btn-primary" type="submit">💾 Tambah ke Gudang</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════ MODAL KELOLA GUDANG PRODUK ═══════════════ --}}
<div class="clay-modal" id="modal-warehouse" role="dialog" aria-modal="true">
    <div class="clay-modal-backdrop" onclick="closeWarehouseModal()"></div>
    <div class="clay-modal-container" style="max-width:480px;">
        <div class="clay-modal-header">
            <h2>🏷 Kelola Gudang Produk</h2>
            <button class="clay-modal-close" onclick="closeWarehouseModal()" type="button">✕</button>
        </div>
        <form method="POST" id="wm-form" action="{{ route('gudang.product.warehouses', 0) }}">
            @csrf @method('PUT')
            <div class="clay-modal-body">
                <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;margin-bottom:6px;">Gudang tempat produk terdaftar
                    <span style="font-weight:400;color:#9ca3af;">— centang gudang & pilih gudang utama (export/pengambilan)</span>
                </label>
                <div style="display:grid;gap:8px;">
                    @foreach($inventories as $inv)
                    <label style="display:flex;align-items:center;gap:10px;background:#fafafa;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;cursor:pointer;">
                        <input type="checkbox" name="inventory_ids[]" class="wm-check" value="{{ $inv->id }}" style="accent-color:#7c3aed;">
                        <span style="flex:1;font-size:.8rem;font-weight:600;color:#374151;">🏭 {{ $inv->name }}</span>
                        <span class="wm-utama" style="font-size:.68rem;color:#9ca3af;">Utama</span>
                        <input type="radio" name="primary_inventory_id" class="wm-radio wm-utama" value="{{ $inv->id }}" style="accent-color:#7c3aed;">
                    </label>
                    @endforeach
                </div>
                <div style="margin-top:10px;font-size:.72rem;color:#9ca3af;background:#fafafa;border-radius:8px;padding:8px 12px;">
                    Melepas centang = produk dilepas dari gudang tsb (stok cache ikut dihapus, jurnal tetap tersimpan).
                </div>
            </div>
            <div class="clay-modal-footer">
                <button class="clay-btn clay-btn-outline" onclick="closeWarehouseModal()" type="button">Batal</button>
                <button class="clay-btn clay-btn-primary" type="submit">💾 Simpan Gudang</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Expand baris varian ──────────────────────────────
var openRows = new Set();
function toggleVarian(id) {
    var el = document.getElementById('var-' + id);
    var chev = document.getElementById('chev-var-' + id);
    if (!el) return;
    if (openRows.has(id)) {
        el.style.display = 'none';
        if (chev) chev.style.transform = 'rotate(0deg)';
        openRows.delete(id);
    } else {
        el.style.display = 'table-row';
        if (chev) chev.style.transform = 'rotate(180deg)';
        openRows.add(id);
    }
}
</script>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';

    // ── MODAL ATTACH (tambah produk MASTER ke gudang ini) ──
    var mAttach = document.getElementById('modal-attach');
    var amProduct = document.getElementById('am-product-id');
    var amInventory = document.getElementById('am-inventory-id');
    var amEmpty = document.getElementById('am-empty');

    var amPrimaryWrap = document.getElementById('am-primary-wrap');

    function amTogglePrimary() {
        var opt = amProduct.options[amProduct.selectedIndex];
        var isCore = opt && opt.dataset.goodsType === 'core';
        amPrimaryWrap.style.display = isCore ? 'block' : 'none';
    }
    amProduct.addEventListener('change', amTogglePrimary);

    window.openAttachModal = function(btn) {
        var type = btn.dataset.goodsType || '';
        amInventory.value = btn.dataset.inventoryId || '';
        var visible = 0;
        Array.prototype.forEach.call(amProduct.options, function(opt) {
            if (!opt.value) return;
            var show = !type || opt.dataset.goodsType === type;
            opt.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        amEmpty.style.display = visible === 0 ? 'block' : 'none';
        amProduct.value = '';
        amTogglePrimary();
        mAttach.classList.add('active');
    };

    window.closeAttachModal = function() { mAttach.classList.remove('active'); };

    // ── MODAL KELOLA GUDANG PRODUK ──
    var mWh = document.getElementById('modal-warehouse');
    var wmForm = document.getElementById('wm-form');

    window.openWarehouseModal = function(btn) {
        wmForm.action = btn.dataset.url;
        var wh = (btn.dataset.wh || '').split(',').filter(Boolean).map(Number);
        var primary = parseInt(btn.dataset.primaryWh || '0', 10);
        document.querySelectorAll('.wm-check').forEach(function(cb) {
            cb.checked = wh.indexOf(parseInt(cb.value, 10)) >= 0;
        });
        document.querySelectorAll('.wm-radio').forEach(function(r) {
            r.checked = parseInt(r.value, 10) === primary;
        });
        // Radio "Utama" hanya untuk Barang Inti (core)
        var isCore = btn.dataset.goodsType === 'core';
        document.querySelectorAll('.wm-utama').forEach(function(el) { el.style.display = isCore ? '' : 'none'; });
        mWh.classList.add('active');
    };

    window.closeWarehouseModal = function() { mWh.classList.remove('active'); };

    // Centang gudang → auto-pilih radio primary bila belum ada; hapus primary
    // bila gudangnya di-uncheck.
    document.querySelectorAll('.wm-check').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var radio = document.querySelector('.wm-radio[value="' + cb.value + '"]');
            if (cb.checked && radio && !document.querySelector('.wm-radio:checked')) {
                radio.checked = true;
            } else if (!cb.checked && radio && radio.checked) {
                radio.checked = false;
            }
        });
    });

    // Tutup dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (mAttach.classList.contains('active')) closeAttachModal();
            if (mWh.classList.contains('active')) closeWarehouseModal();
        }
    });
})();
</script>
@endpush
