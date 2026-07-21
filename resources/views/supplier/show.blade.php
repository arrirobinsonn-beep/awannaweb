@extends('layouts.app')
@section('title', $supplier->nama_supplier)
@section('page-title', '🏭 ' . $supplier->nama_supplier)
@section('page-subtitle', 'Detail informasi supplier')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Info Utama --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="clay-card p-5" data-reveal>
            <div class="flex items-center justify-between mb-4">
                <span class="clay-badge clay-badge-gray font-mono">{{ $supplier->kode_supplier }}</span>
                <span class="clay-badge {{ $supplier->status === 'aktif' ? 'clay-badge-green' : 'clay-badge-red' }}">
                    {{ ucfirst($supplier->status) }}
                </span>
            </div>
            <h2 class="font-800 text-xl mb-4">{{ $supplier->nama_supplier }}</h2>

            <div class="space-y-3 text-sm">
                @if($supplier->pic_nama)
                <div class="flex gap-3"><span class="text-gray-400 w-24">👤 PIC</span><span class="font-600">{{ $supplier->pic_nama }}</span></div>
                @endif
                @if($supplier->pic_telepon)
                <div class="flex gap-3"><span class="text-gray-400 w-24">📞 Telepon</span><span>{{ $supplier->pic_telepon }}</span></div>
                @endif
                @if($supplier->email)
                <div class="flex gap-3"><span class="text-gray-400 w-24">📧 Email</span><span>{{ $supplier->email }}</span></div>
                @endif
                @if($supplier->alamat)
                <div class="flex gap-3"><span class="text-gray-400 w-24">📍 Alamat</span><span>{{ $supplier->alamat }}</span></div>
                @endif
                @if($supplier->kota)
                <div class="flex gap-3"><span class="text-gray-400 w-24">🏙 Kota</span><span>{{ $supplier->kota }}, {{ $supplier->provinsi }}</span></div>
                @endif
            </div>

            @if($supplier->catatan)
            <div class="mt-4 p-3 rounded-xl text-sm text-gray-600" style="background: #fafafa;">
                📝 {{ $supplier->catatan }}
            </div>
            @endif

            <div class="flex gap-2 mt-5 pt-4 border-t border-gray-100">
                <a href="{{ route('supplier.edit', $supplier) }}" class="clay-btn clay-btn-secondary flex-1 justify-center text-sm" data-page-link>✏️ Edit</a>
                <a href="{{ route('supplier.index') }}" class="clay-btn clay-btn-outline flex-1 justify-center text-sm" data-page-link>← Kembali</a>
            </div>
        </div>
    </div>

    {{-- Produk & Whitelist --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="clay-card p-5" data-reveal data-reveal-delay="80">
            <h3 class="font-800 mb-3">📦 Produk ({{ $supplier->products->count() }})</h3>
            @forelse($supplier->products as $p)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                    <div class="font-600 text-sm">{{ $p->nama_produk }}</div>
                    <div class="text-xs text-gray-400">{{ $p->kode_produk }} · {{ $p->kategori }}</div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-700" style="color: var(--color-secondary);">
                        Rp {{ number_format($p->harga_jual, 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-400">Stok: {{ $p->stok }}</div>
                </div>
            </div>
            @empty<p class="text-sm text-gray-400 py-3 text-center">Belum ada produk</p>@endforelse
        </div>

        <div class="clay-card p-5" data-reveal data-reveal-delay="120">
            <h3 class="font-800 mb-3">✅ Whitelist ({{ $supplier->whitelists->count() }})</h3>
            @forelse($supplier->whitelists as $w)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                    <div class="font-600 text-sm">{{ $w->nama }}</div>
                    <div class="text-xs text-gray-400">{{ ucfirst($w->platform) }} · {{ $w->akun_id }}</div>
                </div>
                <span class="clay-badge {{ $w->status === 'aktif' ? 'clay-badge-green' : ($w->status === 'pending' ? 'clay-badge-yellow' : 'clay-badge-red') }}">
                    {{ ucfirst($w->status) }}
                </span>
            </div>
            @empty<p class="text-sm text-gray-400 py-3 text-center">Belum ada whitelist</p>@endforelse
        </div>
    </div>
</div>
@endsection
