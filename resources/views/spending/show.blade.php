@extends('layouts.app')
@section('title', 'Detail Spending')
@section('page-title', '💸 Detail Spending')
@section('page-subtitle', 'Informasi lengkap data spending')

@section('content')
<div style="max-width:640px;">
    <div class="clay-card" style="padding:20px;" data-reveal>

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;">
            <div>
                <div style="font-weight:800;font-size:1.1rem;">{{ $spending->tanggal->format('d F Y') }}</div>
                <div style="color:#9ca3af;font-size:.8rem;">{{ $spending->user?->nama ?? '-' }} · {{ $spending->whitelist?->nama ?? '-' }}</div>
            </div>
            <span class="badge badge-success">{{ $spending->paid_ratio }}%</span>
        </div>

        {{-- Angka Utama --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:16px;">
            <div class="clay-card" style="padding:12px;text-align:center;background:#FFF5F5;">
                <div style="font-size:.7rem;color:#6b7280;">SPENDING</div>
                <div style="font-weight:800;margin-top:4px;color:var(--color-primary,#FF6B6B);">Rp {{ number_format($spending->spending,0,',','.') }}</div>
            </div>
            <div class="clay-card" style="padding:12px;text-align:center;background:#F5F0FF;">
                <div style="font-size:.7rem;color:#6b7280;">LEAD</div>
                <div style="font-weight:800;margin-top:4px;">{{ number_format($spending->lead) }}</div>
            </div>
            <div class="clay-card" style="padding:12px;text-align:center;background:#F0FFFE;">
                <div style="font-size:.7rem;color:#6b7280;">PAID</div>
                <div style="font-weight:800;margin-top:4px;">{{ number_format($spending->paid) }}</div>
            </div>
            <div class="clay-card" style="padding:12px;text-align:center;background:#F0FFF4;">
                <div style="font-size:.7rem;color:#6b7280;">CPA LEAD</div>
                <div style="font-weight:800;margin-top:4px;">Rp {{ number_format($spending->cpa_lead,0,',','.') }}</div>
            </div>
        </div>

        {{-- Detail Info --}}
        <div class="space-y-2 text-sm mb-6">
            @if($spending->supplier) <div class="flex gap-3"><span class="text-gray-400 w-28">🏭 Supplier</span><span class="font-600">{{ $spending->supplier->nama_supplier }}</span></div> @endif
            @if($spending->product)  <div class="flex gap-3"><span class="text-gray-400 w-28">📦 Produk</span><span class="font-600">{{ $spending->product->name }}</span></div> @endif
            @if($spending->whitelist)<div class="flex gap-3"><span class="text-gray-400 w-28">✅ Whitelist</span><span class="font-600">{{ $spending->whitelist->nama }}</span></div> @endif
            @if($spending->nama_akun)<div class="flex gap-3"><span class="text-gray-400 w-28">📢 Nama Akun</span><span>{{ $spending->nama_akun }}</span></div> @endif
            @if($spending->catatan)  <div class="flex gap-3"><span class="text-gray-400 w-28">📝 Catatan</span><span>{{ $spending->catatan }}</span></div> @endif
        </div>

        {{-- Actions --}}
        <div style="display:flex;gap:10px;padding-top:16px;border-top:1px solid #f1f5f9;flex-wrap:wrap;">
            <a href="{{ route('spending.edit', $spending) }}" class="clay-btn clay-btn-secondary" data-page-link>✏️ Edit</a>
            <a href="{{ route('spending.index') }}" class="clay-btn clay-btn-outline" data-page-link>← Kembali</a>
        </div>
    </div>
</div>
@endsection
