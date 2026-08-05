@extends('layouts.app')
@section('title','Produk')
@section('page-title','📦 Data Produk')
@section('page-subtitle','Kelola semua produk & variasi isi paket Awanna')

@push('styles')
<style>
    /* ── Modal Varian ──────────────────────────────── */
    .pv-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .pv-modal.active { display: flex; }
    .pv-modal .pv-backdrop {
        position: absolute; inset: 0;
        background: rgba(15,23,42,.55); backdrop-filter: blur(2px);
    }
    .pv-modal .pv-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 480px; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: pvIn .22s ease;
    }
    @keyframes pvIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .pv-modal .pv-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .pv-modal .pv-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .pv-modal .pv-close {
        background: #f3f4f6; border: none; border-radius: 8px;
        width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280;
        transition: background .15s;
    }
    .pv-modal .pv-close:hover { background: #e5e7eb; }
    .pv-modal .pv-body { padding: 18px 20px; }
    .pv-modal .pv-body label {
        display: block; font-size: .72rem; font-weight: 700; color: #6b7280; margin-bottom: 4px;
    }
    .pv-modal .pv-body .clay-input { font-size: .85rem; padding: 7px 10px; }
    .pv-modal .pv-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
    }
</style>
@endpush

@section('content')
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;justify-content:space-between;margin-bottom:18px;" data-reveal>
    <form method="GET" action="{{ route('product.index') }}" style="display:flex;flex-wrap:wrap;gap:8px;flex:1;min-width:0;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, kategori..." class="clay-input" style="flex:1;min-width:150px;max-width:260px;">
        <select name="kategori" class="clay-input" style="width:auto;min-width:120px;">
            <option value="">Semua Kategori</option>
            @foreach($kategoris as $k)<option value="{{ $k }}" {{ request('kategori')===$k?'selected':'' }}>{{ $k }}</option>@endforeach
        </select>
        <select name="status" class="clay-input" style="width:auto;min-width:110px;">
            <option value="">Semua Status</option>
            <option value="aktif"    {{ request('status')==='aktif'    ?'selected':'' }}>Aktif</option>
            <option value="nonaktif" {{ request('status')==='nonaktif' ?'selected':'' }}>Nonaktif</option>
        </select>
        <button type="submit" class="clay-btn clay-btn-secondary">🔍</button>
    </form>
    <a href="{{ route('product.create') }}" class="clay-btn clay-btn-primary" data-page-link>＋ Tambah Produk</a>
</div>

<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th style="width:28px;"></th>
                <th>Kode</th><th>Nama Produk</th><th>Supplier</th><th>Kategori</th>
                <th style="text-align:right;">HPP / PCS</th>
                <th style="text-align:right;">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse($products as $p)
            @php $rowId = 'pv-' . $p->id; @endphp

            {{-- ── BARIS INDUK (klik → expand varian) ───────────── --}}
            <tr onclick="toggleProd('{{ $rowId }}')" style="cursor:pointer;"
                onmouseenter="this.style.background='#fffbfb'"
                onmouseleave="this.style.background=''">
                <td style="text-align:center;padding:11px 8px;">
                    <span id="chev-{{ $rowId }}" style="display:inline-block;transition:transform .22s;color:#9ca3af;font-size:.78rem;">▶</span>
                </td>
                <td><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.72rem;">{{ $p->kode_produk }}</span></td>
                <td>
                    <div style="font-weight:700;font-size:.875rem;">{{ $p->nama_produk }}</div>
                    <div style="font-size:.66rem;color:#9ca3af;">
                        {{ $p->variants->count() }} variasi isi paket · stok induk {{ number_format($p->stok) }} {{ $p->satuan }}
                    </div>
                </td>
                <td style="font-size:.83rem;">{{ $p->supplier->nama_supplier ?? '-' }}</td>
                <td>@if($p->kategori)<span class="clay-badge clay-badge-purple" style="font-size:.72rem;">{{ $p->kategori }}</span>@else<span style="color:#d1d5db;">-</span>@endif</td>
                <td style="text-align:right;font-weight:700;font-size:.83rem;color:var(--color-primary);white-space:nowrap;">
                    Rp {{ number_format($p->harga_beli,0,',','.') }}
                </td>
                <td style="text-align:right;" onclick="event.stopPropagation()">
                    <div style="display:flex;justify-content:flex-end;gap:6px;">
                        <a href="{{ route('product.edit',$p) }}" class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;" data-page-link>✏️</a>
                        <form method="POST" action="{{ route('product.destroy',$p) }}" onsubmit="return confirm('Hapus {{ $p->nama_produk }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="clay-btn clay-btn-danger" style="padding:5px 10px;font-size:.72rem;">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>

            {{-- ── BARIS EXPAND: Variasi Isi Paket ──────────────── --}}
            <tr id="{{ $rowId }}" style="display:none;">
                <td colspan="7" style="padding:0;background:#fafafa;border-top:2px dashed rgba(255,107,107,.12);">

                    {{-- Header variasi (dijorokkan mengikuti tabel varian) --}}
                    <div style="display:flex;align-items:center;gap:10px;padding:12px 20px 12px 36px;background:#fff;border-bottom:1px solid rgba(0,0,0,.05);">
                        <span style="background:var(--color-secondary);color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:999px;flex-shrink:0;">🔖 Variasi Isi Paket</span>
                        <span style="font-size:.75rem;color:#6b7280;font-weight:600;">{{ $p->variants->count() }} variasi</span>
                        <button type="button" class="clay-btn clay-btn-primary" style="margin-left:auto;padding:6px 12px;font-size:.72rem;"
                                onclick="openVariantModal('{{ $p->id }}')">＋ Tambah Variasi</button>
                    </div>

                    @if($p->variants->isEmpty())
                    <div style="padding:26px 26px 26px 56px;text-align:center;color:#9ca3af;font-size:.82rem;">
                        Belum ada variasi isi paket untuk produk ini.<br>
                        <span style="font-size:.75rem;">Klik <strong>＋ Tambah Variasi</strong> untuk menambahkan (mis. "Beli 1 Dapat 2", "Beli 2 Dapat 4").</span>
                    </div>
                    @else
                    <div style="overflow-x:auto;padding-left:36px;border-left:3px solid rgba(78,205,196,.18);margin-left:16px;">
                        <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
                            <thead>
                                <tr style="background:#f9fefe;">
                                    @foreach(['Kode','Nama Variasi','Stok','Isi Paket','Harga Jual','% Margin','Status','Aksi'] as $h)
                                    <th style="padding:8px 10px;font-size:.65rem;font-weight:700;color:#9ca3af;
                                               text-transform:uppercase;letter-spacing:.05em;
                                               text-align:{{ in_array($h,['Stok','Isi Paket','Harga Jual','% Margin','Aksi']) ? 'right' : 'left' }};
                                               border-bottom:1px solid rgba(0,0,0,.05);">{{ $h }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($p->variants as $v)
                            <tr onmouseenter="this.style.background='#f0fffe'"
                                onmouseleave="this.style.background=''">
                                <td style="padding:8px 10px;"><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.68rem;">{{ $v->kode }}</span></td>
                                <td style="padding:8px 10px;font-weight:600;">{{ $v->nama }}</td>
                                <td style="padding:8px 10px;text-align:right;">
                                    <span class="clay-badge {{ $v->stok>20?'clay-badge-green':($v->stok>0?'clay-badge-yellow':'clay-badge-red') }}">{{ number_format($v->stok) }}</span>
                                </td>
                                <td style="padding:8px 10px;text-align:right;color:#6b7280;">{{ $v->pcs_per_pack }} pcs</td>
                                <td style="padding:8px 10px;text-align:right;font-weight:700;color:var(--color-secondary);white-space:nowrap;">
                                    Rp {{ number_format($v->harga_jual,0,',','.') }}
                                </td>
                                <td style="padding:8px 10px;text-align:right;">
                                    <span class="clay-badge {{ $v->margin>=30?'clay-badge-green':($v->margin>0?'clay-badge-yellow':'clay-badge-red') }}">{{ $v->margin }}%</span>
                                </td>
                                <td style="padding:8px 10px;text-align:right;">
                                    <span class="clay-badge {{ $v->status==='aktif'?'clay-badge-green':'clay-badge-red' }}">{{ ucfirst($v->status) }}</span>
                                </td>
                                <td style="padding:8px 10px;text-align:right;">
                                    <div style="display:flex;justify-content:flex-end;gap:4px;">
                                        <a href="javascript:void(0)" onclick="openVariantModal('{{ $p->id }}', this)"
                                           class="clay-btn clay-btn-secondary" style="padding:3px 8px;font-size:.65rem;"
                                           title="Edit variasi"
                                           data-id="{{ $v->id }}"
                                           data-url="{{ route('product.variant.update', $v) }}"
                                           data-kode="{{ $v->kode }}"
                                           data-nama="{{ $v->nama }}"
                                           data-stok="{{ $v->stok }}"
                                           data-pcs="{{ $v->pcs_per_pack }}"
                                           data-harga="{{ $v->harga_jual }}"
                                           data-status="{{ $v->status }}">✏️</a>
                                        <a href="javascript:void(0)" onclick="deleteVariant('{{ $v->id }}')"
                                           class="clay-btn clay-btn-danger" style="padding:3px 8px;font-size:.65rem;"
                                           title="Hapus variasi">🗑</a>
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
            <tr><td colspan="7" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">📦</div>
                <p style="color:#9ca3af;">Belum ada data produk</p>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())<div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $products->links() }}</div>@endif
</div>

{{-- ═══════════════ MODAL TAMBAH / EDIT VARIASI ISI PAKET ═══════════════ --}}
<div class="pv-modal" id="modal-variant" role="dialog" aria-modal="true" aria-labelledby="pv-title">
    <div class="pv-backdrop" onclick="closeVariantModal()"></div>
    <div class="pv-container">
        <div class="pv-header">
            <h2 id="pv-title">➕ Tambah Variasi Isi Paket</h2>
            <button class="pv-close" onclick="closeVariantModal()" type="button">✕</button>
        </div>
        <div class="pv-body">
            <div class="form-grid" style="gap:12px;">
                <div>
                    <label>Kode Variasi <span style="color:#f87171;">*</span></label>
                    <input type="text" id="pv-kode" class="clay-input" placeholder="KMPU-1D2" maxlength="50">
                </div>
                <div>
                    <label>Nama Variasi <span style="color:#f87171;">*</span></label>
                    <input type="text" id="pv-nama" class="clay-input" placeholder="Beli 1 Dapat 2" maxlength="150">
                </div>
                <div>
                    <label>Isi Paket (pcs) <span style="color:#f87171;">*</span></label>
                    <input type="number" id="pv-pcs" class="clay-input" min="1" value="2">
                    <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Berapa pcs yang didapat pembeli dalam 1 paket (mis. Beli 1 Dapat 2 → 2)</div>
                </div>
                <div>
                    <label>Stok <span style="color:#f87171;">*</span></label>
                    <input type="number" id="pv-stok" class="clay-input" min="0" value="0">
                    <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Stok variasi ini. Stok induk produk = gabungan stok semua variasi.</div>
                </div>
                <div>
                    <label>Harga Jual (Rp) <span style="color:#f87171;">*</span></label>
                    <input type="number" id="pv-harga" class="clay-input" min="0" step="500">
                </div>
                <div>
                    <label>Status</label>
                    <select id="pv-status" class="clay-input">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            {{-- Live preview modal & margin --}}
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;background:#F0FFFE;border-radius:10px;padding:10px 14px;text-align:center;margin-top:12px;">
                <div>
                    <div id="pv-preview-cost" style="font-weight:800;font-size:.95rem;color:#1e1b2e;">—</div>
                    <div style="font-size:.6rem;font-weight:600;text-transform:uppercase;color:#9ca3af;">Modal (HPP × isi)</div>
                </div>
                <div>
                    <div id="pv-preview-margin" style="font-weight:800;font-size:.95rem;color:var(--color-secondary);">—</div>
                    <div style="font-size:.6rem;font-weight:600;text-transform:uppercase;color:#9ca3af;">Margin</div>
                </div>
            </div>
        </div>
        <div class="pv-footer">
            <button class="clay-btn clay-btn-outline" onclick="closeVariantModal()" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="pv-save" type="button">💾 Simpan Variasi</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var openProdRows = new Set();
function toggleProd(id) {
    var el   = document.getElementById(id);
    var chev = document.getElementById('chev-' + id);
    if (!el) return;
    var open = openProdRows.has(id);
    if (open) {
        el.style.display = 'none';
        if (chev) chev.style.transform = 'rotate(0deg)';
        openProdRows.delete(id);
    } else {
        el.style.display = 'table-row';
        if (chev) chev.style.transform = 'rotate(90deg)';
        openProdRows.add(id);
    }
}
</script>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';

    // Map produk (id → hpp & url store) untuk halaman ini saja
    var PV_PRODUCTS = @json($pvProducts);

    var modal = document.getElementById('modal-variant');
    var titleEl = document.getElementById('pv-title');
    var saveBtn = document.getElementById('pv-save');

    var inKode  = document.getElementById('pv-kode');
    var inNama  = document.getElementById('pv-nama');
    var inStok  = document.getElementById('pv-stok');
    var inPcs   = document.getElementById('pv-pcs');
    var inHarga = document.getElementById('pv-harga');
    var inStatus = document.getElementById('pv-status');

    var pvCost = document.getElementById('pv-preview-cost');
    var pvMargin = document.getElementById('pv-preview-margin');

    var st = { productId: null, url: null, hpp: 0, edit: false };

    function fmtNum(n) { return Number(n).toLocaleString('id-ID'); }

    function calcPreview() {
        var hpp = st.hpp || 0;
        var pcs = parseInt(inPcs.value) || 1;
        var harga = parseFloat(inHarga.value) || 0;
        var cost = hpp * pcs;
        pvCost.textContent = cost > 0 ? 'Rp ' + fmtNum(Math.round(cost)) : '—';
        pvMargin.textContent = cost > 0 ? (((harga - cost) / cost) * 100).toFixed(0) + '%' : '—';
    }

    [inPcs, inHarga].forEach(function(inp) {
        inp.addEventListener('input', calcPreview);
    });

    window.openVariantModal = function(productId, btn) {
        var prod = PV_PRODUCTS[productId];
        if (!prod) return;
        st.productId = productId;
        st.hpp = prod.hpp;

        if (btn && btn.dataset.id) {
            // Mode edit
            st.edit = true;
            st.url = btn.dataset.url;
            titleEl.textContent = '✏️ Edit Variasi Isi Paket';
            inKode.value = btn.dataset.kode;
            inNama.value = btn.dataset.nama;
            inStok.value = btn.dataset.stok;
            inPcs.value = btn.dataset.pcs;
            inHarga.value = btn.dataset.harga;
            inStatus.value = btn.dataset.status;
        } else {
            // Mode tambah
            st.edit = false;
            st.url = prod.store_url;
            titleEl.textContent = '➕ Tambah Variasi Isi Paket';
            inKode.value = '';
            inNama.value = '';
            inStok.value = '0';
            inPcs.value = '2';
            inHarga.value = '';
            inStatus.value = 'aktif';
        }

        calcPreview();
        modal.classList.add('active');
        setTimeout(function() { inKode.focus(); }, 150);
    };

    window.closeVariantModal = function() {
        modal.classList.remove('active');
        st.url = null;
    };

    saveBtn.addEventListener('click', function() {
        if (!st.url) return;
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = 'Menyimpan...';

        fetch(st.url, {
            method: st.edit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                kode: inKode.value.trim(),
                nama: inNama.value.trim(),
                stok: inStok.value || '0',
                pcs_per_pack: inPcs.value || '1',
                harga_jual: inHarga.value || '0',
                status: inStatus.value,
            }),
        })
        .then(function(res) {
            if (!res.ok) return res.json().then(function(e) {
                var msg = e.message || 'Gagal';
                if (e.errors) {
                    var k = Object.keys(e.errors)[0];
                    if (k) msg = e.errors[k][0];
                }
                throw new Error(msg);
            });
            return res.json();
        })
        .then(function(json) {
            if (json.success) {
                window.location.reload();
            } else {
                alert('Gagal: ' + json.message);
                btn.disabled = false;
                btn.innerHTML = '💾 Simpan Variasi';
            }
        })
        .catch(function(err) {
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '💾 Simpan Variasi';
        });
    });

    window.deleteVariant = function(id) {
        if (!confirm('Hapus variasi ini?')) return;
        fetch('{{ route('product.variant.destroy', ':id') }}'.replace(':id', id), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        })
        .then(function(res) {
            if (!res.ok) return res.json().then(function(e) { throw new Error(e.message || 'Gagal'); });
            return res.json();
        })
        .then(function(json) {
            if (json.success) window.location.reload();
            else alert('Gagal: ' + json.message);
        })
        .catch(function(err) { alert('Error: ' + err.message); });
    };

    // Tutup dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) window.closeVariantModal();
    });
})();
</script>
@endpush
