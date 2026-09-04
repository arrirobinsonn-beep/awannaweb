<table class="clay-table">
    <thead><tr>
        <th>Kode</th><th>Nama Supplier</th><th>PIC</th><th>Kota</th><th>Status</th><th style="text-align:right;">Aksi</th>
    </tr></thead>
    <tbody>
    @forelse($suppliers as $s)
    <tr>
        <td><span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.72rem;">{{ $s->kode_supplier }}</span></td>
        <td>
            <div style="font-weight:700;font-size:.875rem;">{{ $s->nama_supplier }}</div>
            @if($s->email)<div style="font-size:.72rem;color:#9ca3af;">{{ $s->email }}</div>@endif
        </td>
        <td>
            <div style="font-size:.83rem;">{{ $s->pic_nama ?? '-' }}</div>
            @if($s->pic_telepon)<div style="font-size:.72rem;color:#9ca3af;">{{ $s->pic_telepon }}</div>@endif
        </td>
        <td style="font-size:.83rem;">{{ $s->kota ?? '-' }}</td>
        <td><span class="clay-badge {{ $s->status==='aktif'?'clay-badge-green':'clay-badge-red' }}">{{ ucfirst($s->status) }}</span></td>
        <td style="text-align:right;">
            <div style="display:flex;justify-content:flex-end;gap:6px;">
                <a href="{{ route('supplier.show',$s) }}" class="clay-btn clay-btn-outline" style="padding:5px 10px;font-size:.72rem;" data-page-link>👁</a>
                <a href="{{ route('supplier.edit',$s) }}" class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;" data-page-link>✏️</a>
                <form method="POST" action="{{ route('supplier.destroy',$s) }}" onsubmit="return confirm('Hapus {{ $s->nama_supplier }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="clay-btn clay-btn-danger" style="padding:5px 10px;font-size:.72rem;">🗑</button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="6" style="text-align:center;padding:48px 16px;">
        <div style="font-size:2.5rem;margin-bottom:8px;">🏭</div>
        <p style="color:#9ca3af;">Tidak ada supplier ditemukan</p>
    </td></tr>
    @endforelse
    </tbody>
</table>
