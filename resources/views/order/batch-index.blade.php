@extends('layouts.app')

@section('title', 'Riwayat Batch Import')
@section('page-title', 'Riwayat Batch Import')
@section('page-subtitle', 'Kelola batch upload data mentah order online — hapus batch = hapus semua order terkait')

@section('content')

<div class="clay-card" style="padding:0;overflow:hidden;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;border-bottom:1px solid rgba(0,0,0,.06);">
        <div>
            <h2 style="margin:0;font-size:1rem;font-weight:800;">🗂 Riwayat Batch Upload</h2>
            <div style="font-size:.75rem;color:#9ca3af;">Setiap baris = 1 kali upload file CSV. Klik Hapus = hapus batch & semua order di dalamnya.</div>
        </div>
        <a href="{{ route('orders.index') }}" class="clay-btn clay-btn-primary">📥 Upload Baru</a>
    </div>

    <div class="table-scroll">
        <table class="clay-table" style="min-width:700px;">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>File</th>
                    <th>Pengirim</th>
                    <th>Tanggal Upload</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Sukses</th>
                    <th>Gagal</th>
                    <th style="width:100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $b)
                    <tr>
                        <td style="font-size:.78rem;color:#9ca3af;">{{ $b->id }}</td>
                        <td>
                            <div style="font-size:.82rem;font-weight:700;color:#374151;">{{ $b->original_filename }}</div>
                        </td>
                        <td>
                            <span style="font-size:.78rem;font-weight:600;color:#374151;">{{ $b->sender ?? '-' }}</span>
                        </td>
                        <td style="font-size:.78rem;">
                            {{ $b->created_at?->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
                            <div style="font-size:.65rem;color:#9ca3af;">WIB</div>
                        </td>
                        <td>
                            @php
                                $statusClass = match($b->status) {
                                    'processing' => 'st-processing',
                                    'completed'  => 'st-completed',
                                    'failed'     => 'st-failed',
                                    default      => 'st-pending',
                                };
                            @endphp
                            <span class="badge-batch-status {{ $statusClass }}">{{ $b->status }}</span>
                        </td>
                        <td style="font-size:.78rem;text-align:center;">{{ $b->total_rows }}</td>
                        <td style="font-size:.78rem;text-align:center;color:#059669;font-weight:700;">{{ $b->success_rows }}</td>
                        <td style="font-size:.78rem;text-align:center;color:{{ $b->failed_rows > 0 ? '#b91c1c' : '#9ca3af' }};">{{ $b->failed_rows }}</td>
                        <td>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <a href="{{ route('orders.index', ['batch' => $b->id]) }}" class="clay-btn" style="padding:3px 8px;font-size:.72rem;" title="Lihat order">👁</a>
                                <form method="POST" action="{{ route('order-batch.destroy', $b->id) }}"
                                      onsubmit="return confirm('Hapus batch ini & semua order terkait?\n\nFile: {{ addslashes($b->original_filename) }}\nPengirim: {{ addslashes($b->sender ?? '-') }}\nOrder: {{ $b->shipping_orders_count }}\n\n⚠ Stok akan dikembalikan otomatis.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="clay-btn" style="padding:3px 8px;font-size:.72rem;color:#b91c1c;" title="Hapus batch">🗑</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;color:#9ca3af;padding:30px;">Belum ada batch upload.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($batches->hasPages())
        <div style="padding:12px 20px;">{{ $batches->links() }}</div>
    @endif
</div>

@endsection
