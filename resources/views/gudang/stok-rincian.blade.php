@extends('layouts.app')
@section('title','Rincian Stok')
@section('page-title','📋 Rincian Stok')
@section('page-subtitle','Pergerakan stok harian per produk per gudang')

@section('content')

{{-- Filter + Tambah --}}
<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="GET" action="{{ route('gudang.stok-rincian') }}"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div>
            <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151;">Bulan</label>
            <input type="month" name="bulan" value="{{ $bulan }}" class="clay-input" style="padding:6px 10px;">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary">Filter</button>
        <a href="{{ route('gudang.stok-rincian') }}" class="clay-btn clay-btn-outline">Reset</a>
        <button type="button" class="clay-btn clay-btn-success" style="margin-left:auto;"
                onclick="bukaForm()">+ Input Stok</button>
    </form>
</div>

{{-- Form Input --}}
<div id="form-input" class="hidden" data-reveal>
    <form method="POST" action="{{ route('gudang.stok-rincian.store') }}"
          class="clay-card" style="padding:16px;margin-bottom:16px;">
        @csrf
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <h4 style="font-weight:700;margin:0;">Input Stok Harian</h4>
            <button type="button" class="clay-btn clay-btn-sm clay-btn-outline" onclick="tutupForm()">✕ Tutup</button>
        </div>
        <div style="display:grid;grid-template-columns:repeat(11,1fr);gap:8px;align-items:end;">
            <div>
                <label class="field-label">GUDANG</label>
                <select name="gudang" id="input-gudang" class="clay-input" required>
                    <option value="">— Pilih —</option>
                    @foreach($gudangs as $g)
                    <option value="{{ $g->nama }}" {{ $loop->first ? 'selected' : '' }}>{{ $g->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">PRODUK</label>
                <select name="product_id" id="input-product" required class="clay-input" onchange="showStockAwal(this)">
                    <option value="">— Pilih —</option>
                    @foreach(\App\Models\Product::where('status','aktif')->orderBy('nama_produk')->get() as $p)
                    <option value="{{ $p->id }}" data-stok="{{ $p->stok }}">{{ $p->nama_produk }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">TANGGAL</label>
                <input type="date" name="tanggal" required class="clay-input" value="{{ $bulan }}-01">
            </div>
            <div>
                <label class="field-label">AWAL</label>
                <input type="text" id="stok-awal-display" class="clay-input" readonly style="background:#f3f4f6;font-weight:700;" value="-">
            </div>
            <div>
                <label class="field-label">BELANJA</label>
                <input type="number" name="masuk_belanja" class="clay-input" min="0" value="0">
            </div>
            <div>
                <label class="field-label">RTS</label>
                <input type="number" name="masuk_rts" class="clay-input" min="0" value="0">
            </div>
            <div>
                <label class="field-label">REPAIR</label>
                <input type="number" name="masuk_repair" class="clay-input" min="0" value="0">
            </div>
            <div>
                <label class="field-label">RUSAK</label>
                <input type="number" name="barang_rusak" class="clay-input" min="0" value="0">
            </div>
            <div>
                <label class="field-label">KELUAR</label>
                <input type="number" name="barang_keluar" class="clay-input" min="0" value="0">
            </div>
            <div>
                <label class="field-label">CATATAN</label>
                <input type="text" name="catatan" class="clay-input" placeholder="(opsional)">
            </div>
            <div>
                <button type="submit" class="clay-btn clay-btn-primary">Simpan</button>
            </div>
        </div>
    </form>
</div>

{{-- Level 1: Gudang --}}
@forelse($gudangData as $gudang => $produkList)
<div style="margin-bottom:8px;" data-reveal>
    <div class="clay-card" style="padding:10px 14px;background:linear-gradient(135deg,#1e1b2e,#374151);color:#fff;font-weight:800;font-size:.9rem;display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;border-radius:10px;"
         onclick="toggleGroup('gudang-{{ \Illuminate\Support\Str::slug($gudang) }}', this)">
        <span style="display:flex;align-items:center;gap:8px;">
            <span class="grp-arrow" style="font-size:.65rem;">▶</span>
            🏭 {{ $gudang }}
        </span>
        <span style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:.7rem;opacity:.7;">{{ count($produkList) }} produk</span>
            <button type="button" class="clay-btn clay-btn-xs" style="font-size:.6rem;padding:2px 8px;background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:6px;cursor:pointer;"
                    onclick="event.stopPropagation();bukaFormGudang('{{ $gudang }}')">+ Tambah</button>
        </span>
    </div>

    {{-- Level 2: Produk --}}
    <div id="gudang-{{ \Illuminate\Support\Str::slug($gudang) }}" style="display:none;margin-left:12px;margin-top:4px;">
    @forelse($produkList as $produk)
    <div style="margin-bottom:4px;">
        <div class="clay-card" style="padding:8px 12px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-left:3px solid var(--color-primary,#FF6B6B);font-weight:700;font-size:.8rem;color:#1e1b2e;display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;"
             onclick="toggleGroup('produk-{{ \Illuminate\Support\Str::slug($gudang.'-'.$produk->nama_produk) }}', this)">
            <span style="display:flex;align-items:center;gap:8px;">
                <span class="grp-arrow" style="font-size:.6rem;color:#9ca3af;">▶</span>
                📦 {{ $produk->nama_produk }}
            </span>
            <span style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:.7rem;font-weight:600;color:#6b7280;">
                    {{ number_format($produk->stok) }} → {{ number_format($produk->stock_akhir_bulan) }}
                </span>
                <button type="button" class="clay-btn clay-btn-xs clay-btn-success" style="font-size:.6rem;padding:2px 8px;"
                        onclick="event.stopPropagation();bukaFormProduk('{{ $gudang }}',{{ $produk->id }},'{{ $produk->nama_produk }}')">+ Tambah</button>
            </span>
        </div>

        {{-- Level 3: Data --}}
        <div id="produk-{{ \Illuminate\Support\Str::slug($gudang.'-'.$produk->nama_produk) }}" style="display:none;">
        @if($produk->movements->isEmpty())
            <div style="padding:16px;text-align:center;color:#9ca3af;font-size:.75rem;">Belum ada data.</div>
        @else
            <div class="clay-card" style="padding:0;overflow-x:auto;margin-top:2px;margin-left:8px;">
            @foreach($produk->movements->groupBy(fn($m) => $m->tanggal->format('Y-m-d')) as $tgl => $movementsByDate)
            <div style="border-bottom:1px solid #f1f5f9;">
                <div style="padding:6px 10px;background:#f8fafc;font-weight:600;font-size:.75rem;color:#475569;display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;"
                     onclick="toggleGroup('tgl-{{ \Illuminate\Support\Str::slug($gudang.'-'.$produk->nama_produk.'-'.$tgl) }}', this)">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span class="grp-arrow" style="font-size:.55rem;color:#94a3b8;">▶</span>
                        📅 {{ \Carbon\Carbon::parse($tgl)->format('d/m/Y') }}
                    </span>
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:.65rem;color:#6b7280;">
                            Stok akhir: {{ number_format($movementsByDate->last()->stock_akhir_hari) }}
                            @if($movementsByDate->count() > 1)
                                ({{ $movementsByDate->count() }} baris)
                            @endif
                        </span>
                        <form method="POST" action="{{ route('gudang.stok-rincian.delete-date') }}"
                              onsubmit="return confirm('Hapus semua data tanggal {{ \Carbon\Carbon::parse($tgl)->format('d/m/Y') }} untuk {{ $produk->nama_produk }} di {{ $gudang }}?')"
                              style="display:inline;">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $produk->id }}">
                            <input type="hidden" name="gudang" value="{{ $gudang }}">
                            <input type="hidden" name="tanggal" value="{{ $tgl }}">
                            <button type="submit" class="clay-btn clay-btn-xs clay-btn-danger" style="font-size:.55rem;padding:1px 5px;cursor:pointer;">🗑️ Hapus Tanggal</button>
                        </form>
                    </span>
                </div>
                <div id="tgl-{{ \Illuminate\Support\Str::slug($gudang.'-'.$produk->nama_produk.'-'.$tgl) }}" style="display:none;overflow-x:auto;">
                <table class="clay-table" style="font-size:.72rem;">
                    <thead>
                        <tr>
                            <th style="padding:4px 8px;font-size:.65rem;color:#6b7280;">STOK AWAL</th>
                            <th style="padding:4px 8px;font-size:.65rem;color:#6b7280;text-align:right;">BELANJA</th>
                            <th style="padding:4px 8px;font-size:.65rem;color:#6b7280;text-align:right;">RTS</th>
                            <th style="padding:4px 8px;font-size:.65rem;color:#6b7280;text-align:right;">REPAR</th>
                            <th style="padding:4px 8px;font-size:.65rem;color:#6b7280;text-align:right;">RUSAK</th>
                            <th style="padding:4px 8px;font-size:.65rem;color:#6b7280;text-align:right;">KELUAR</th>
                            <th style="padding:4px 8px;font-size:.65rem;color:#6b7280;text-align:right;">AKHIR</th>
                            <th style="padding:4px 8px;font-size:.65rem;color:#6b7280;">CATATAN</th>
                            <th style="padding:4px 8px;width:50px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $m = $movementsByDate->first();
                            $mLast = $movementsByDate->last();
                            $daySum = fn ($f) => $movementsByDate->sum($f);
                        @endphp
                        <tr>
                            <td class="text-right" style="padding:4px 8px;font-size:.65rem;color:#6b7280;white-space:nowrap;">{{ number_format($m->stock_awal_hari) }}</td>
                            <td class="text-right" style="padding:4px 8px;">{{ $daySum('masuk_belanja') > 0 ? '+'.number_format($daySum('masuk_belanja')) : '-' }}</td>
                            <td class="text-right" style="padding:4px 8px;">{{ $daySum('masuk_rts') > 0 ? '+'.number_format($daySum('masuk_rts')) : '-' }}</td>
                            <td class="text-right" style="padding:4px 8px;">{{ $daySum('masuk_repair') > 0 ? '+'.number_format($daySum('masuk_repair')) : '-' }}</td>
                            <td class="text-right" style="padding:4px 8px;{{ $daySum('barang_rusak') > 0 ? 'color:#dc2626;' : '' }}">{{ $daySum('barang_rusak') > 0 ? '-'.number_format($daySum('barang_rusak')) : '-' }}</td>
                            <td class="text-right" style="padding:4px 8px;{{ $daySum('barang_keluar') > 0 ? 'color:#dc2626;' : '' }}">{{ $daySum('barang_keluar') > 0 ? '-'.number_format($daySum('barang_keluar')) : '-' }}</td>
                            <td class="text-right" style="padding:4px 8px;font-weight:700;">{{ number_format($mLast->stock_akhir_hari) }}</td>
                            <td style="padding:4px 8px;font-size:.6rem;color:#6b7280;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $m->catatan ?? '-' }}</td>
                            <td style="padding:4px 8px;">
                                <a href="{{ route('gudang.stok-rincian.edit',$m) }}" class="clay-btn clay-btn-xs clay-btn-outline" style="font-size:.55rem;padding:1px 5px;">Edit</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
            @endforeach
            <div style="padding:6px 10px;background:#f1f5f9;font-weight:700;font-size:.7rem;display:flex;align-items:center;gap:12px;">
                <span style="color:#6b7280;">Total</span>
                <span style="color:#059669;">Belanja: {{ number_format($produk->movements->sum('masuk_belanja')) }}</span>
                <span style="color:#059669;">RTS: {{ number_format($produk->movements->sum('masuk_rts')) }}</span>
                <span style="color:#059669;">Repair: {{ number_format($produk->movements->sum('masuk_repair')) }}</span>
                <span style="color:#dc2626;">Rusak: {{ number_format($produk->movements->sum('barang_rusak')) }}</span>
                <span style="color:#dc2626;">Keluar: {{ number_format($produk->movements->sum('barang_keluar')) }}</span>
                <span style="color:var(--color-primary,#FF6B6B);margin-left:auto;">Stok akhir: {{ number_format($produk->stock_akhir_bulan) }}</span>
            </div>
            </div>
        @endif
        </div>
    </div>
    @empty
    <div style="padding:16px;text-align:center;color:#9ca3af;font-size:.75rem;">Tidak ada data di gudang ini.</div>
    @endforelse
    </div>
</div>
@empty
<div class="clay-card" style="padding:48px;text-align:center;" data-reveal>
    <div style="font-size:2rem;margin-bottom:8px;">📭</div>
    <p style="color:#9ca3af;">Belum ada data stok.</p>
</div>
@endforelse

<style>
.hidden { display:none; }
.field-label { display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151; }
.text-right { text-align:right; }
.clay-table th, .clay-table td { white-space:nowrap; }
tfoot td { border-top:2px solid #e5e7eb; }
</style>

<script>
function toggleGroup(id, headerEl) {
    var el = document.getElementById(id);
    var arrow = headerEl.querySelector('.grp-arrow');
    if (el.style.display === 'none') {
        el.style.display = 'block';
        if (arrow) arrow.textContent = '▼';
    } else {
        el.style.display = 'none';
        if (arrow) arrow.textContent = '▶';
    }
}
function bukaForm() { document.getElementById('form-input').classList.remove('hidden'); document.getElementById('form-input').scrollIntoView({behavior:'smooth'}); }
function tutupForm() { document.getElementById('form-input').classList.add('hidden'); }

function showStockAwal(sel) {
    var opt = sel.options[sel.selectedIndex];
    var stok = opt.getAttribute('data-stok');
    document.getElementById('stok-awal-display').value = stok !== null ? parseInt(stok).toLocaleString('id-ID') : '-';
}

function bukaFormGudang(gudang) {
    document.getElementById('input-gudang').value = gudang;
    document.getElementById('input-product').selectedIndex = 0;
    document.getElementById('stok-awal-display').value = '-';
    document.getElementById('form-input').classList.remove('hidden');
    document.getElementById('form-input').scrollIntoView({behavior:'smooth', block:'start'});
    document.querySelector('input[name="tanggal"]').focus();
}

function bukaFormProduk(gudang, productId, productName) {
    document.getElementById('input-gudang').value = gudang;
    var sel = document.getElementById('input-product');
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value == productId) {
            sel.selectedIndex = i;
            break;
        }
    }
    showStockAwal(sel);
    document.getElementById('form-input').classList.remove('hidden');
    document.getElementById('form-input').scrollIntoView({behavior:'smooth', block:'start'});
    document.querySelector('input[name="tanggal"]').focus();
}

</script>
@endsection
