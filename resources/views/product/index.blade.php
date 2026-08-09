@extends('layouts.app')
@section('title','Produk')
@section('page-title','📦 Data Produk')
@section('page-subtitle','Kelola semua produk & varian ukuran Awanna')

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

    /* ── Toggle Status ──────────────────────────────── */
    .clay-toggle {
        position: relative; display: inline-block;
        width: 36px; height: 20px; vertical-align: middle; cursor: pointer;
    }
    .clay-toggle input {
        position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer;
    }
    .clay-toggle .clay-toggle-slider {
        position: absolute; inset: 0; background: #d1d5db; border-radius: 999px;
        transition: background .18s;
    }
    .clay-toggle .clay-toggle-slider::before {
        content: ''; position: absolute; width: 14px; height: 14px; left: 3px; top: 3px;
        background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.3);
        transition: transform .18s;
    }
    .clay-toggle input:checked + .clay-toggle-slider { background: var(--color-primary, #FF6B6B); }
    .clay-toggle input:checked + .clay-toggle-slider::before { transform: translateX(16px); }
    .clay-toggle-sm { transform: scale(.85); transform-origin: center; }
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
            <option value="active"   {{ request('status')==='active'   ?'selected':'' }}>Aktif</option>
            <option value="inactive" {{ request('status')==='inactive' ?'selected':'' }}>Nonaktif</option>
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
                <th>Kode</th><th>Nama Produk</th><th>Inventory</th><th>Kategori</th>
                <th style="text-align:center;">Status</th>
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
                <td><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.72rem;">{{ $p->code }}</span></td>
                <td>
                    <div style="font-weight:700;font-size:.875rem;">{{ $p->name }}</div>
                    <div style="font-size:.66rem;color:#9ca3af;">
                        {{ $p->variants->count() }} varian · stok induk {{ number_format($p->stok) }} {{ $p->unit }}
                    </div>
                </td>
                <td style="font-size:.83rem;">{{ $p->inventory->name ?? '-' }}</td>
                <td>@if($p->category)<span class="clay-badge clay-badge-purple" style="font-size:.72rem;">{{ $p->category }}</span>@else<span style="color:#d1d5db;">-</span>@endif</td>
                <td style="text-align:center;" onclick="event.stopPropagation()">
                    <label class="clay-toggle" title="Ubah status produk">
                        <input type="checkbox" data-toggle-url="{{ route('product.toggle-status', $p) }}" {{ $p->status==='active'?'checked':'' }}>
                        <span class="clay-toggle-slider"></span>
                    </label>
                </td>
                <td style="text-align:right;font-weight:700;font-size:.83rem;color:var(--color-primary);white-space:nowrap;">
                    Rp {{ number_format($p->purchase_price,0,',','.') }}
                </td>
                <td style="text-align:right;" onclick="event.stopPropagation()">
                    <div style="display:flex;justify-content:flex-end;gap:6px;">
                        <a href="{{ route('product.edit',$p) }}" class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;" data-page-link>✏️</a>
                        <form method="POST" action="{{ route('product.destroy',$p) }}" onsubmit="return confirm('Hapus {{ $p->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="clay-btn clay-btn-danger" style="padding:5px 10px;font-size:.72rem;">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>

            {{-- ── BARIS EXPAND: Varian Ukuran ─────────────────── --}}
            <tr id="{{ $rowId }}" style="display:none;">
                <td colspan="8" style="padding:0;background:#fafafa;border-top:2px dashed rgba(255,107,107,.12);">

                    {{-- Header variasi (dijorokkan mengikuti tabel varian) --}}
                    <div style="display:flex;align-items:center;gap:10px;padding:12px 20px 12px 36px;background:#fff;border-bottom:1px solid rgba(0,0,0,.05);">
                        <span style="background:var(--color-secondary);color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:999px;flex-shrink:0;">🔖 Varian Ukuran</span>
                        <span style="font-size:.75rem;color:#6b7280;font-weight:600;">{{ $p->variants->count() }} varian</span>
                        <button type="button" class="clay-btn clay-btn-primary" style="margin-left:auto;padding:6px 12px;font-size:.72rem;"
                                onclick="openVariantModal('{{ $p->id }}')">＋ Tambah Varian</button>
                    </div>

                    @if($p->variants->isEmpty())
                    <div style="padding:26px 26px 26px 56px;text-align:center;color:#9ca3af;font-size:.82rem;">
                        Belum ada varian untuk produk ini.<br>
                        <span style="font-size:.75rem;">Klik <strong>＋ Tambah Varian</strong> untuk menambahkan ukuran (mis. power +1.00, +1.25).</span>
                    </div>
                    @else
                    <div style="overflow-x:auto;padding-left:36px;border-left:3px solid rgba(78,205,196,.18);margin-left:16px;">
                        <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
                            <thead>
                                <tr style="background:#f9fefe;">
                                    @foreach(['Kode','Nama Varian','Jenis','Power','Stok','Status','Aksi'] as $h)
                                    <th style="padding:8px 10px;font-size:.65rem;font-weight:700;color:#9ca3af;
                                               text-transform:uppercase;letter-spacing:.05em;
                                                text-align:{{ in_array($h,['Power','Stok','Status','Aksi']) ? 'right' : 'left' }};
                                               border-bottom:1px solid rgba(0,0,0,.05);">{{ $h }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($p->variants as $v)
                            <tr onmouseenter="this.style.background='#f0fffe'"
                                onmouseleave="this.style.background=''">
                                <td style="padding:8px 10px;"><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.68rem;">{{ $v->code }}</span></td>
                                <td style="padding:8px 10px;font-weight:600;">{{ $v->name }}</td>
                                <td style="padding:8px 10px;color:#6b7280;">{{ $v->jenis ?? '-' }}</td>
                                <td style="padding:8px 10px;text-align:right;font-weight:700;white-space:nowrap;">
                                    {{ (float) $v->power > 0 ? '+'.number_format($v->power,2,',','.') : '-' }}
                                </td>
                                <td style="padding:8px 10px;text-align:right;">
                                    <span class="clay-badge {{ $v->stock>20?'clay-badge-green':($v->stock>0?'clay-badge-yellow':'clay-badge-red') }}">{{ number_format($v->stock) }}</span>
                                </td>
                                <td style="padding:8px 10px;text-align:right;">
                                    <label class="clay-toggle clay-toggle-sm" title="Ubah status varian">
                                        <input type="checkbox" data-toggle-url="{{ route('product.variant.toggle-status', $v) }}" {{ $v->status==='active'?'checked':'' }}>
                                        <span class="clay-toggle-slider"></span>
                                    </label>
                                </td>
                                <td style="padding:8px 10px;text-align:right;">
                                    <div style="display:flex;justify-content:flex-end;gap:4px;">
                                        <a href="javascript:void(0)" onclick="openVariantModal('{{ $p->id }}', this)"
                                           class="clay-btn clay-btn-secondary" style="padding:3px 8px;font-size:.65rem;"
                                           title="Edit variasi"
                                           data-id="{{ $v->id }}"
                                           data-url="{{ route('product.variant.update', $v) }}"
                                           data-code="{{ $v->code }}"
                                           data-name="{{ $v->name }}"
                                           data-jenis="{{ $v->jenis }}"
                                           data-power="{{ $v->power }}"
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
            <tr><td colspan="8" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">📦</div>
                <p style="color:#9ca3af;">Belum ada data produk</p>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())<div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $products->links() }}</div>@endif
</div>

{{-- ═══════════════ MODAL TAMBAH / EDIT VARIAN ═══════════════ --}}
<div class="pv-modal" id="modal-variant" role="dialog" aria-modal="true" aria-labelledby="pv-title">
    <div class="pv-backdrop" onclick="closeVariantModal()"></div>
    <div class="pv-container">
        <div class="pv-header">
            <h2 id="pv-title">➕ Tambah Varian</h2>
            <button class="pv-close" onclick="closeVariantModal()" type="button">✕</button>
        </div>
        <div class="pv-body">
            <div class="form-grid" style="gap:12px;">
                <div>
                    <label>Kode Varian <span style="color:#f87171;">*</span></label>
                    <input type="text" id="pv-kode" class="clay-input" placeholder="KSP+1.50" maxlength="50">
                </div>
                <div>
                    <label>Nama Varian <span style="color:#f87171;">*</span></label>
                    <input type="text" id="pv-nama" class="clay-input" placeholder="Plus +1.50" maxlength="150">
                </div>
                <div>
                    <label>Jenis</label>
                    <input type="text" id="pv-jenis" class="clay-input" placeholder="ukuran / isi paket" maxlength="80">
                </div>
                <div>
                    <label>Power <span style="color:#f87171;">*</span></label>
                    <input type="number" id="pv-power" class="clay-input" min="0" step="0.25" value="0">
                    <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Ukuran lensa, mis. 1.00 / 1.25. 0 untuk produk tanpa ukuran.</div>
                </div>
                <div id="pv-stock-wrap" style="display:none;">
                    <label>Stok Awal</label>
                    <input type="number" id="pv-stok" class="clay-input" min="0" value="0">
                    <div style="font-size:.68rem;color:#9ca3af;margin-top:3px;">Hanya saat menambah. Stok selanjutnya dikelola via jurnal (Barang Masuk).</div>
                </div>
                <div>
                    <label>Status</label>
                    <select id="pv-status" class="clay-input">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="pv-footer">
            <button class="clay-btn clay-btn-outline" onclick="closeVariantModal()" type="button">Batal</button>
            <button class="clay-btn clay-btn-primary" id="pv-save" type="button">💾 Simpan Varian</button>
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

    var inKode   = document.getElementById('pv-kode');
    var inNama   = document.getElementById('pv-nama');
    var inJenis  = document.getElementById('pv-jenis');
    var inPower  = document.getElementById('pv-power');
    var inStok   = document.getElementById('pv-stok');
    var inStokWrap = document.getElementById('pv-stock-wrap');
    var inStatus = document.getElementById('pv-status');

    var st = { productId: null, url: null, edit: false };

    window.openVariantModal = function(productId, btn) {
        var prod = PV_PRODUCTS[productId];
        if (!prod) return;
        st.productId = productId;

        if (btn && btn.dataset.id) {
            // Mode edit
            st.edit = true;
            st.url = btn.dataset.url;
            titleEl.textContent = '✏️ Edit Varian';
            inKode.value = btn.dataset.code;
            inNama.value = btn.dataset.name;
            inJenis.value = btn.dataset.jenis;
            inPower.value = btn.dataset.power;
            inStatus.value = btn.dataset.status;
            inStokWrap.style.display = 'none';
        } else {
            // Mode tambah
            st.edit = false;
            st.url = prod.store_url;
            titleEl.textContent = '➕ Tambah Varian';
            inKode.value = '';
            inNama.value = '';
            inJenis.value = '';
            inPower.value = '0';
            inStok.value = '0';
            inStatus.value = 'active';
            inStokWrap.style.display = 'block';
        }

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

        var body = {
            code: inKode.value.trim(),
            name: inNama.value.trim(),
            jenis: inJenis.value.trim(),
            power: inPower.value || '0',
            status: inStatus.value,
        };
        if (!st.edit) {
            body.stock_awal = inStok.value || '0';
        }

        fetch(st.url, {
            method: st.edit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(body),
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
                btn.innerHTML = '💾 Simpan Varian';
            }
        })
        .catch(function(err) {
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '💾 Simpan Varian';
        });
    });

    window.deleteVariant = function(id) {
        if (!confirm('Hapus varian ini?')) return;
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

    // ── Toggle status (produk & varian) ────────────────
    document.querySelectorAll('.clay-toggle input[type="checkbox"]').forEach(function(input) {
        input.addEventListener('change', function() {
            var self = this;
            var url = self.dataset.toggleUrl;
            if (!url) return;
            self.disabled = true;
            fetch(url, {
                method: 'PATCH',
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
                else { self.checked = !self.checked; alert('Gagal: ' + json.message); }
            })
            .catch(function(err) {
                self.checked = !self.checked;
                alert('Error: ' + err.message);
            })
            .finally(function() { self.disabled = false; });
        });
    });

    // Tutup dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) window.closeVariantModal();
    });
})();
</script>
@endpush
