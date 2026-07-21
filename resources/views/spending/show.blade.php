@extends('layouts.app')
@section('title', 'Detail Spending')
@section('page-title', '💸 Detail Spending')
@section('page-subtitle', 'Informasi lengkap data spending')

@section('content')
<div class="max-w-3xl">
    <div class="clay-card p-6" data-reveal>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-5 pb-5 border-b border-gray-100">
            <div>
                <div class="font-800 text-xl">{{ $spending->tanggal->format('d F Y') }}</div>
                <div class="text-gray-400 text-sm">{{ $spending->user->name ?? '-' }} · {{ ucfirst($spending->platform) }}</div>
            </div>
            @php
                $statusClass = match($spending->status) {
                    'approved'  => 'clay-badge-green',
                    'submitted' => 'clay-badge-yellow',
                    'rejected'  => 'clay-badge-red',
                    default     => 'clay-badge-gray',
                };
            @endphp
            <span class="clay-badge {{ $statusClass }} text-sm px-4 py-1.5">{{ ucfirst($spending->status) }}</span>
        </div>

        {{-- Angka Utama --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="clay-card-sm p-3 text-center" style="background:#FFF5F5">
                <div class="text-xs text-gray-500">Budget</div>
                <div class="font-800 mt-1" style="color:#374151">Rp {{ number_format($spending->budget,0,',','.') }}</div>
            </div>
            <div class="clay-card-sm p-3 text-center" style="background:#FFF5F5">
                <div class="text-xs text-gray-500">Spending</div>
                <div class="font-800 mt-1" style="color:var(--color-primary)">Rp {{ number_format($spending->spending,0,',','.') }}</div>
            </div>
            <div class="clay-card-sm p-3 text-center" style="background:#F0FFFE">
                <div class="text-xs text-gray-500">Revenue</div>
                <div class="font-800 mt-1" style="color:var(--color-secondary)">Rp {{ number_format($spending->revenue,0,',','.') }}</div>
            </div>
            <div class="clay-card-sm p-3 text-center" style="background:{{ $spending->profit >= 0 ? '#F0FFF4' : '#FFF5F5' }}">
                <div class="text-xs text-gray-500">Profit</div>
                <div class="font-800 mt-1" style="color:{{ $spending->profit >= 0 ? 'var(--color-green)' : 'var(--color-primary)' }}">
                    Rp {{ number_format(abs($spending->profit),0,',','.') }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="clay-card-sm p-3 text-center" style="background:#F5F0FF">
                <div class="text-xs text-gray-500">Leads</div>
                <div class="font-800 mt-1" style="color:var(--color-purple)">{{ number_format($spending->leads) }}</div>
            </div>
            <div class="clay-card-sm p-3 text-center" style="background:#FFF8F0">
                <div class="text-xs text-gray-500">Konversi</div>
                <div class="font-800 mt-1" style="color:var(--color-orange)">{{ number_format($spending->konversi) }}</div>
            </div>
            <div class="clay-card-sm p-3 text-center" style="background:#F0FFFE">
                <div class="text-xs text-gray-500">CPL</div>
                <div class="font-800 mt-1" style="color:var(--color-secondary)">Rp {{ number_format($spending->cpl,0,',','.') }}</div>
            </div>
            <div class="clay-card-sm p-3 text-center" style="background:{{ $spending->roas >= 1 ? '#F0FFF4' : '#FFF5F5' }}">
                <div class="text-xs text-gray-500">ROAS</div>
                <div class="font-800 mt-1" style="color:{{ $spending->roas >= 1 ? 'var(--color-green)' : 'var(--color-primary)' }}">{{ $spending->roas }}x</div>
            </div>
        </div>

        {{-- Detail Info --}}
        <div class="space-y-2 text-sm mb-6">
            @if($spending->supplier) <div class="flex gap-3"><span class="text-gray-400 w-28">🏭 Supplier</span><span class="font-600">{{ $spending->supplier->nama_supplier }}</span></div> @endif
            @if($spending->product)  <div class="flex gap-3"><span class="text-gray-400 w-28">📦 Produk</span><span class="font-600">{{ $spending->product->nama_produk }}</span></div> @endif
            @if($spending->whitelist)<div class="flex gap-3"><span class="text-gray-400 w-28">✅ Whitelist</span><span class="font-600">{{ $spending->whitelist->nama }}</span></div> @endif
            @if($spending->nama_akun)<div class="flex gap-3"><span class="text-gray-400 w-28">📢 Nama Akun</span><span>{{ $spending->nama_akun }}</span></div> @endif
            @if($spending->catatan)  <div class="flex gap-3"><span class="text-gray-400 w-28">📝 Catatan</span><span>{{ $spending->catatan }}</span></div> @endif
        </div>

        {{-- Actions --}}
        <div class="flex gap-3 pt-5 border-t border-gray-100 flex-wrap">
            @if($spending->status === 'submitted')
            <form method="POST" action="{{ route('spending.approve', $spending) }}">
                @csrf @method('PATCH')
                <button type="submit" class="clay-btn clay-btn-secondary">✓ Approve</button>
            </form>
            @endif
            @if($spending->status === 'draft')
            <a href="{{ route('spending.edit', $spending) }}" class="clay-btn clay-btn-secondary" data-page-link>✏️ Edit</a>
            @endif
            <a href="{{ route('spending.index') }}" class="clay-btn clay-btn-outline" data-page-link>← Kembali</a>
        </div>
    </div>
</div>
@endsection
