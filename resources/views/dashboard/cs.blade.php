@extends('layouts.app')
@section('title','Dashboard CS')
@section('page-title','📞 Dashboard CS')
@section('page-subtitle','Overview undel & performa — ' . $namaCs)

@section('content')

{{-- Statistik Cards --}}
<div class="grid-stats" style="margin-bottom:20px;">
    <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6dd,#3b82f688);box-shadow:4px 4px 0 #1d4ed8;border-color:#1d4ed8;color:#fff;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Total Lead</div>
        <div style="font-size:2rem;font-weight:900;">{{ number_format($spending->total_lead,0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">📊 Lead</div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#10b981dd,#10b98188);box-shadow:4px 4px 0 #047857;border-color:#047857;color:#fff;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Total Paid</div>
        <div style="font-size:2rem;font-weight:900;">{{ number_format($spending->total_paid,0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">✅ Paid</div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#f59e0bdd,#f59e0b88);box-shadow:4px 4px 0 #b45309;border-color:#b45309;color:#fff;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Paid Ratio</div>
        <div style="font-size:2rem;font-weight:900;">{{ $paidRatio }}%</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">📈 Ratio</div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#ef4444dd,#ef444488);box-shadow:4px 4px 0 #b91c1c;border-color:#b91c1c;color:#fff;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Total Order</div>
        <div style="font-size:2rem;font-weight:900;">{{ number_format($totalOrder,0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">📋 Yang dihandle</div>
    </div>
    <a href="{{ route('dashboard.cs', ['kategori' => 'proses_kirim']) }}" style="text-decoration:none;">
    <div class="stat-card" style="background:linear-gradient(135deg,#06b6d4dd,#06b6d488);box-shadow:4px 4px 0 #0e7490;border-color:#0e7490;color:#fff;cursor:pointer;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Proses Kirim</div>
        <div style="font-size:2rem;font-weight:900;">{{ number_format($statusCounts['proses_kirim'],0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">🚚 Proses Pengiriman</div>
    </div>
    </a>
    <a href="{{ route('dashboard.cs', ['kategori' => 'terkirim']) }}" style="text-decoration:none;">
    <div class="stat-card" style="background:linear-gradient(135deg,#10b981dd,#10b98188);box-shadow:4px 4px 0 #047857;border-color:#047857;color:#fff;cursor:pointer;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Terkirim</div>
        <div style="font-size:2rem;font-weight:900;">{{ number_format($statusCounts['terkirim'],0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">✅ Dikirim</div>
    </div>
    </a>
    <a href="{{ route('dashboard.cs', ['kategori' => 'bermasalah']) }}" style="text-decoration:none;">
    <div class="stat-card" style="background:linear-gradient(135deg,#dc2626dd,#dc262688);box-shadow:4px 4px 0 #991b1b;border-color:#991b1b;color:#fff;cursor:pointer;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Bermasalah</div>
        <div style="font-size:2rem;font-weight:900;">{{ number_format($statusCounts['bermasalah'],0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">⚠️ Bermasalah</div>
    </div>
    </a>
    <a href="{{ route('dashboard.cs', ['kategori' => 'retur']) }}" style="text-decoration:none;">
    <div class="stat-card" style="background:linear-gradient(135deg,#f59e0bdd,#f59e0b88);box-shadow:4px 4px 0 #b45309;border-color:#b45309;color:#fff;cursor:pointer;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Retur</div>
        <div style="font-size:2rem;font-weight:900;">{{ number_format($statusCounts['retur'],0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">🔄 Return</div>
    </div>
    </a>
</div>

{{-- Tracking Resi Mandiri --}}
<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <h4 style="font-weight:700;margin:0 0 10px;font-size:.85rem;">🔍 Tracking Resi / No HP</h4>
    <div style="display:flex;gap:10px;align-items:flex-end;">
        <div style="flex:1;">
            <input type="text" id="search-tracking" class="clay-input"
                   placeholder="AWB atau No HP..." style="padding:8px 12px;font-size:.8rem;">
        </div>
        <button type="button" class="clay-btn clay-btn-primary"
                onclick="cariResi()">Cari</button>
    </div>
    <div id="tracking-result" style="margin-top:12px;display:none;"></div>
</div>

{{-- Tabel Order --}}
<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
        <h4 style="font-weight:700;margin:0;font-size:.85rem;">📋 Daftar Order yang dihandle CS</h4>
        <span style="font-size:.75rem;color:#6b7280;">{{ $dataList->firstItem() }}-{{ $dataList->lastItem() }} dari {{ $dataList->total() }} paket</span>
    </div>

    <form method="GET" action="{{ route('dashboard.cs') }}" style="margin-bottom:10px;display:flex;gap:8px;">
        <input type="text" name="search" class="clay-input" placeholder="Cari AWB, status, produk, no telp..." value="{{ request('search') }}" style="flex:1;padding:7px 10px;font-size:.78rem;">
        <button type="submit" class="clay-btn clay-btn-primary" style="font-size:.75rem;">Cari</button>
        @if(request('search') || request('kategori'))
        <a href="{{ route('dashboard.cs') }}" class="clay-btn clay-btn-outline" style="font-size:.75rem;">Reset</a>
        @endif
    </form>
    @if(request('kategori'))
    <div style="margin-bottom:8px;font-size:.75rem;color:#6b7280;">
        Menampilkan: <strong>{{ request('kategori') }}</strong>
        <a href="{{ route('dashboard.cs', ['kategori' => null]) }}" style="color:#ef4444;text-decoration:underline;">Hapus filter</a>
    </div>
    @endif

    @if($dataList->isEmpty())
    <div style="text-align:center;padding:40px;color:#9ca3af;font-size:.85rem;">Belum ada order untuk {{ $namaCs }}.</div>
    @else
    <div style="overflow-x:auto;">
        <table class="clay-table" style="font-size:.72rem;">
<thead>
	                <tr>
	                    <th>AWB</th>
	                    <th>Status</th>
	                    <th>Keterangan</th>
	                    <th>Produk</th>
	                    <th>No Telp</th>
	                    <th>WA</th>
	                </tr>
	            </thead>
	            <tbody>
	                @foreach($dataList as $pt)
	                <tr>
	                    <td><strong>{{ $pt->awb ?? '-' }}</strong> <span style="cursor:pointer;color:#6b7280;font-size:.7rem;" onclick="copyAwb(this, '{{ $pt->awb }}')" title="Salin AWB">📋</span></td>
	                    <td>@php
	                        $s = strtolower($pt->status ?? '');
	                        if (in_array($s, ['terkirim','delivered','delivered successfully','berhasil'])) $cls = 'badge-success';
	                        elseif (in_array($s, ['menunggu pickup','pending','pickup','process'])) $cls = 'badge-warning';
                        elseif (in_array($s, ['dalam pengiriman','in transit','on progress','pengiriman'])) $cls = 'badge-info';
                        elseif (in_array($s, ['undelivered','delivery failed','gagal kirim','returning','returned','cancelled','return to seller','return to sender'])) $cls = 'badge-danger';
                        else $cls = 'badge-danger';
                    @endphp
<span class="badge {{ $cls }}">{{ $pt->status ?? '-' }}</span></td>
                    				<td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $pt->catatan_kurir ?? '-' }}">{{ $pt->catatan_kurir ?? '-' }}</td>
	                    <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $pt->nama_produk ?? '-' }}</td>
                    <td>{{ $pt->no_telp ?? '-' }} @if($pt->no_telp) <span style="cursor:pointer;color:#6b7280;font-size:.7rem;" onclick="copyAwb(this, '{{ $pt->no_telp }}')" title="Salin nomor">📋</span> @endif</td>
                    <td>
                        <a href="{{ $pt->wa_link }}" target="_blank" class="clay-btn clay-btn-xs clay-btn-success" style="font-size:.7rem;text-decoration:none;">📞 WA</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:12px;">
        {{ $dataList->links() }}
    </div>
    @endif
</div>



<style>
.badge { display:inline-block;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:700; }
.badge-success { background:#dcfce7;color:#166534; }
.badge-warning { background:#fef3c7;color:#92400e; }
.badge-info { background:#dbeafe;color:#1e40af; }
.badge-danger { background:#fee2e2;color:#991b1b; }
.clay-table th, .clay-table td { white-space:nowrap; }
.text-right { text-align:right; }
</style>

<script>
function copyAwb(el, awb) {
    navigator.clipboard.writeText(awb).then(function() {
        var orig = el.textContent;
        el.textContent = '✅';
        setTimeout(function() { el.textContent = orig; }, 1500);
    }).catch(function() {
        alert('Gagal menyalin AWB');
    });
}
function cariResi() {
    var query = document.getElementById('search-tracking').value.trim();
    var container = document.getElementById('tracking-result');
    if (!query) {
        container.style.display = 'none';
        return;
    }
    container.innerHTML = '<div style="text-align:center;padding:20px;color:#9ca3af;">Mencari...</div>';
    container.style.display = 'block';

    var url = '{{ route('dashboard.cs.search-awb') }}';
    var params = new URLSearchParams();
    params.set('awb', query);
    params.set('no_telp', query);
    fetch(url + '?' + params.toString())
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success || !res.records.length) {
                container.innerHTML = '<div style="padding:16px;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:.8rem;">Tidak ditemukan.</div>';
                return;
            }
            var html = '<div style="padding:16px;background:#f0fdf4;border-radius:8px;font-size:.8rem;">';
            res.records.forEach(function(r) {
                var s = (r.status || '').toLowerCase();
                var badgeClass = ['terkirim','delivered','delivered successfully','berhasil'].includes(s) ? 'badge-success' :
                    ['menunggu pickup','pending','pickup','process'].includes(s) ? 'badge-warning' :
                    ['dalam pengiriman','in transit','on progress','pengiriman'].includes(s) ? 'badge-info' :
                    'badge-danger';
                html += '<div style="padding:10px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:8px;">';
                html += '<table style="width:100%;border-collapse:collapse;">';
                html += '<tr><td style="padding:3px 6px;font-weight:700;color:#374151;width:100px;">AWB</td><td style="padding:3px 6px;" colspan="3"><strong>' + (r.awb || '-') + '</strong></td></tr>';
                html += '<tr><td style="padding:3px 6px;font-weight:700;color:#374151;">Status</td><td colspan="3" style="padding:3px 6px;"><span class="badge ' + badgeClass + '">' + (r.status || '-') + '</span></td></tr>';
                html += '<tr><td style="padding:3px 6px;font-weight:700;color:#374151;">Keterangan</td><td style="padding:3px 6px;" colspan="3">' + (r.catatan_kurir || '-') + '</td></tr>';
                html += '<tr><td style="padding:3px 6px;font-weight:700;color:#374151;">Kurir</td><td style="padding:3px 6px;" colspan="3">' + (r.kurir || '-') + '</td></tr>';
                html += '<tr><td style="padding:3px 6px;font-weight:700;color:#374151;">Tanggal</td><td style="padding:3px 6px;" colspan="3">' + (r.tanggal || '-') + '</td></tr>';
                html += '<tr><td style="padding:3px 6px;font-weight:700;color:#374151;">Produk</td><td style="padding:3px 6px;" colspan="3">' + (r.nama_produk || '-') + '</td></tr>';
                html += '<tr><td style="padding:3px 6px;font-weight:700;color:#374151;">Shopper</td><td style="padding:3px 6px;" colspan="3">' + (r.nama_shopper || '-') + '</td></tr>';
                html += '<tr><td style="padding:3px 6px;font-weight:700;color:#374151;">No HP</td><td style="padding:3px 6px;" colspan="3">' + (r.no_telp ? '<a href="https://wa.me/' + r.no_telp.replace(/[^0-9]/g, '') + '" target="_blank" style="color:#25d366;">' + r.no_telp + ' WA →</a>' : '-') + '</td></tr>';
                html += '<tr><td style="padding:3px 6px;font-weight:700;color:#374151;">Kota</td><td style="padding:3px 6px;" colspan="3">' + (r.kota || '-') + '</td></tr>';
                html += '</table></div>';
            });
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(function(err) {
            container.innerHTML = '<div style="padding:16px;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:.8rem;">Gagal: ' + err.message + '</div>';
        });
}

document.getElementById('search-tracking').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') cariResi();
});
</script>
@endsection
