@extends('layouts.app')
@section('title','Konfirmasi Pembayaran Top Up')
@section('page-title','💳 Konfirmasi Pembayaran Top Up')
@section('page-subtitle','Lengkapi data pembayaran untuk whitelist yang sudah disetujui')

@section('content')
<div style="max-width:720px;">

    <div class="clay-alert clay-alert-success" style="margin-bottom:16px;" data-reveal>
        <span>✅</span>
        <div style="flex:1;font-size:.83rem;">
            Pengajuan top up Anda telah <strong>disetujui</strong>!
            Silakan isi nomor VA untuk masing-masing whitelist dan konfirmasi pembayaran.
        </div>
    </div>

    <div class="clay-card" style="padding:24px;" data-reveal>
        <form method="POST" action="{{ route('topup.payment.store', $proposal) }}">
            @csrf

            @if($errors->any())
            <div class="clay-alert clay-alert-error" style="margin-bottom:16px;">
                <span>⚠️</span>
                <div style="flex:1;font-size:.83rem;">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            </div>
            @endif

            <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:14px;">
                📋 Data Pembayaran per Whitelist
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;">
                @php $pendingIdx = 0; @endphp
                @foreach($proposal->items as $item)
                @php $wl = $item->whitelist; @endphp
                <div class="clay-card-sm" style="padding:16px;{{ $item->isPaid() ? 'background:#F0FFF4;border-color:rgba(52,211,153,.3);' : '' }}">
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:120px;">
                            <div style="font-weight:700;font-size:.85rem;">{{ $wl->nama ?? '-' }}</div>
                            <div style="font-size:.7rem;color:#9ca3af;">
                                {{ ucfirst($wl->platform??'') }} · {{ $wl->kode??'' }}
                            </div>
                        </div>

                        <div style="min-width:140px;text-align:right;">
                            <div style="font-size:.68rem;color:#6b7280;">Nominal Disetujui</div>
                            <div style="font-weight:800;font-size:1rem;color:var(--color-primary);">
                                Rp {{ number_format($item->nominal,0,',','.') }}
                            </div>
                        </div>

                        @if(!$item->isPaid())
                        <input type="hidden" name="items[{{ $pendingIdx }}][item_id]" value="{{ $item->id }}">
                        <div style="min-width:200px;flex-shrink:0;">
                            <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:3px;">
                                🏦 Nomor VA
                            </label>
                            <input type="text" name="items[{{ $pendingIdx }}][va_number]"
                                   value="{{ old('items.'.$pendingIdx.'.va_number', $item->va_number ?? '') }}"
                                   placeholder="Contoh: 1234567890"
                                   required
                                   class="clay-input"
                                   style="font-size:.85rem;padding:8px 12px;font-family:monospace;">
                        </div>
                        @php $pendingIdx++; @endphp
                        @else
                        <div style="min-width:180px;text-align:right;">
                            <div style="font-size:.68rem;color:#6b7280;">Status</div>
                            <div style="font-weight:700;font-size:.85rem;color:var(--color-green);">
                                ✅ Sudah Dibayar
                            </div>
                            @if($item->va_number)
                            <div style="font-size:.72rem;color:#9ca3af;">VA: {{ $item->va_number }}</div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Total --}}
            <div style="display:flex;justify-content:flex-end;align-items:center;gap:16px;
                        margin-top:16px;padding:14px 16px;border-radius:14px;
                        background:#F0FFFE;border:1.5px solid rgba(78,205,196,.3);">
                <span style="font-size:.82rem;color:#0d9488;font-weight:600;">Total Dibayar</span>
                <span style="font-size:1.3rem;font-weight:900;color:var(--color-secondary);">
                    Rp {{ number_format($proposal->total_nominal,0,',','.') }}
                </span>
            </div>

            {{-- Submit --}}
            @if(!$proposal->isFullyPaid())
            <div style="display:flex;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary" style="flex:1;justify-content:center;font-size:.9rem;padding:12px;">
                    💾 Konfirmasi Pembayaran
                </button>
                <a href="{{ route('topup.show', $proposal) }}" class="clay-btn clay-btn-outline" data-page-link>Kembali</a>
            </div>
            @endif
        </form>
    </div>
</div>
@endsection
