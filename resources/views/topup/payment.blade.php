@extends('layouts.app')
@section('title','Konfirmasi Pembayaran Top Up')
@section('page-title','💳 Konfirmasi Pembayaran Top Up')
@section('page-subtitle','Lengkapi data VA untuk setiap batch pembayaran')

@section('content')
<div style="max-width:840px;">

    <div class="clay-alert clay-alert-success" style="margin-bottom:16px;" data-reveal>
        <span>✅</span>
        <div style="flex:1;font-size:.83rem;">
            Pengajuan top up Anda telah <strong>disetujui</strong>.
            Isi nomor VA untuk setiap batch pembayaran di bawah ini.
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

            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach($proposal->paymentBatches as $index => $batch)
                <div class="clay-card-sm" style="padding:16px;{{ $batch->status !== 'waiting_va' ? 'background:#F0FFF4;border-color:rgba(52,211,153,.3);' : '' }}">
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:160px;">
                            <div style="font-weight:700;font-size:.85rem;">Batch #{{ $batch->batch_no }}</div>
                            <div style="font-size:.7rem;color:#9ca3af;">{{ ucfirst(str_replace('_', ' ', $batch->payment_mode)) }}</div>
                            <div style="font-size:.72rem;color:#6b7280;margin-top:4px;">{{ $batch->items->count() }} whitelist</div>
                        </div>

                        <div style="min-width:140px;text-align:right;">
                            <div style="font-size:.68rem;color:#6b7280;">Nominal Batch</div>
                            <div style="font-weight:800;font-size:1rem;color:var(--color-primary);">Rp {{ number_format($batch->nominal,0,',','.') }}</div>
                        </div>

                        <div style="min-width:220px;flex-shrink:0;">
                            <input type="hidden" name="batches[{{ $index }}][batch_id]" value="{{ $batch->id }}">
                            <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:3px;">🏦 Nomor VA</label>
                            <input type="text" name="batches[{{ $index }}][va_number]"
                                   value="{{ old('batches.'.$index.'.va_number', $batch->va_number ?? '') }}"
                                   placeholder="Contoh: 1234567890"
                                   required
                                   class="clay-input"
                                   style="font-size:.85rem;padding:8px 12px;font-family:monospace;">
                        </div>
                    </div>

                    <div style="margin-top:10px;font-size:.72rem;color:#6b7280;">
                        @foreach($batch->items as $item)
                            <span style="display:inline-block;margin-right:8px;">• {{ $item->whitelist?->nama ?? '-' }} (Rp {{ number_format($item->nominal,0,',','.') }})</span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <div style="display:flex;justify-content:flex-end;align-items:center;gap:16px;margin-top:16px;padding:14px 16px;border-radius:14px;background:#F0FFFE;border:1.5px solid rgba(78,205,196,.3);">
                <span style="font-size:.82rem;color:#0d9488;font-weight:600;">Total Dibayar</span>
                <span style="font-size:1.3rem;font-weight:900;color:var(--color-secondary);">Rp {{ number_format($proposal->total_nominal,0,',','.') }}</span>
            </div>

            <div style="display:flex;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary" style="flex:1;justify-content:center;font-size:.9rem;padding:12px;">
                    💾 Simpan Nomor VA
                </button>
                <a href="{{ route('topup.show', $proposal) }}" class="clay-btn clay-btn-outline" data-page-link>Kembali</a>
            </div>
        </form>
    </div>
</div>
@endsection
