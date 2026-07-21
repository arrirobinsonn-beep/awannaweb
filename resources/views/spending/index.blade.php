@extends('layouts.app')
@section('title','Spending Harian')
@section('page-title','💸 Spending Harian')
@section('page-subtitle','Rekap pengeluaran iklan harian semua advertiser')

@section('content')

{{-- Filter --}}
<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="GET" action="{{ route('spending.index') }}" id="filter-form-idx"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <x-date-range-picker
            :dari="request('dari', now()->startOfMonth()->format('Y-m-d'))"
            :sampai="request('sampai', now()->format('Y-m-d'))"
            form-id="filter-form-idx"
        />
        <div style="min-width:120px;">
            <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;margin-bottom:4px;">Platform</label>
            <select name="platform" class="clay-input">
                <option value="">Semua</option>
                @foreach(['facebook','tiktok','google','instagram'] as $p)
                <option value="{{ $p }}" {{ request('platform')===$p?'selected':'' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:120px;">
            <label style="display:block;font-size:.72rem;font-weight:700;color:#6b7280;margin-bottom:4px;">Status</label>
            <select name="status" class="clay-input">
                <option value="">Semua</option>
                @foreach(['draft','submitted','approved','rejected'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="clay-btn clay-btn-secondary">🔍 Filter</button>
            <a href="{{ route('spending.index') }}" class="clay-btn clay-btn-outline">Reset</a>
        </div>
    </form>
</div>

{{-- Tombol Tambah --}}
<div style="display:flex;justify-content:flex-end;margin-bottom:14px;" data-reveal>
    <a href="{{ route('spending.create') }}" class="clay-btn clay-btn-primary" data-page-link>＋ Input Spending</a>
</div>

{{-- Tabel --}}
<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th>Tanggal</th>
                <th>Advertiser</th>
                <th>Platform</th>
                <th>Produk</th>
                <th style="text-align:right;">Spending</th>
                <th style="text-align:right;">Revenue</th>
                <th style="text-align:right;">ROAS</th>
                <th>Status</th>
                <th style="text-align:right;">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse($spendings as $sp)
            @php
                $statusClass = match($sp->status) {
                    'approved'  => 'clay-badge-green',
                    'submitted' => 'clay-badge-yellow',
                    'rejected'  => 'clay-badge-red',
                    default     => 'clay-badge-gray',
                };
                $roasClass = $sp->roas >= 1 ? 'clay-badge-green' : 'clay-badge-red';
            @endphp
            <tr>
                <td style="font-size:.82rem;font-weight:600;white-space:nowrap;">{{ $sp->tanggal->format('d M Y') }}</td>
                <td>
                    <div style="font-weight:600;font-size:.82rem;">{{ $sp->user->name ?? '-' }}</div>
                    @if($sp->nama_akun)<div style="font-size:.7rem;color:#9ca3af;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sp->nama_akun }}</div>@endif
                </td>
                <td><span class="clay-badge clay-badge-blue" style="font-size:.7rem;">{{ ucfirst($sp->platform) }}</span></td>
                <td style="font-size:.82rem;color:#6b7280;">{{ $sp->product->nama_produk ?? '-' }}</td>
                <td style="text-align:right;font-weight:700;color:var(--color-primary);font-size:.82rem;white-space:nowrap;">
                    Rp {{ number_format($sp->spending,0,',','.') }}
                </td>
                <td style="text-align:right;font-weight:700;color:var(--color-secondary);font-size:.82rem;white-space:nowrap;">
                    Rp {{ number_format($sp->revenue,0,',','.') }}
                </td>
                <td style="text-align:right;">
                    <span class="clay-badge {{ $roasClass }}">{{ $sp->roas }}x</span>
                </td>
                <td><span class="clay-badge {{ $statusClass }}">{{ ucfirst($sp->status) }}</span></td>
                <td style="text-align:right;">
                    <div style="display:flex;justify-content:flex-end;gap:4px;">
                        <a href="{{ route('spending.show',$sp) }}" class="clay-btn clay-btn-outline" style="padding:4px 8px;font-size:.7rem;" data-page-link>👁</a>
                        @if($sp->status==='draft')
                        <a href="{{ route('spending.edit',$sp) }}" class="clay-btn clay-btn-secondary" style="padding:4px 8px;font-size:.7rem;" data-page-link>✏️</a>
                        @endif
                        @if($sp->status==='submitted')
                        <form method="POST" action="{{ route('spending.approve',$sp) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="clay-btn clay-btn-secondary" style="padding:4px 8px;font-size:.7rem;">✓</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('spending.destroy',$sp) }}" onsubmit="return confirm('Hapus data ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="clay-btn clay-btn-danger" style="padding:4px 8px;font-size:.7rem;">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">💸</div>
                <p style="color:#9ca3af;">Belum ada data spending</p>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($spendings->hasPages())
    <div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $spendings->links() }}</div>
    @endif
</div>
@endsection
