@extends('layouts.app')
@section('title','Input Sisa Saldo Whitelist')
@section('page-title','📊 Input Sisa Saldo Whitelist')
@section('page-subtitle','Laporkan sisa saldo setelah VA dibayar oleh Super Admin')

@section('content')
<div style="max-width:720px;">

    <div class="clay-alert clay-alert-success" style="margin-bottom:16px;padding:14px;" data-reveal>
        <span>✅</span>
        <div style="flex:1;font-size:.83rem;">
            <strong>VA sudah dibayar oleh Super Admin!</strong> Silakan cek sisa saldo masing-masing whitelist di platform iklan (Facebook/TikTok/Google)
            dan input nominalnya di bawah ini untuk menyelesaikan proses top up.
        </div>
    </div>

    <div class="clay-card" style="padding:24px;" data-reveal>
        <form method="POST" action="{{ route('topup.confirm.store', $proposal) }}">
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
                📋 Konfirmasi per Whitelist
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;">
                @php $i=0; @endphp
                @foreach($proposal->items as $item)
                @php $wl = $item->whitelist; @endphp
                <div class="clay-card-sm" style="padding:16px;
                    {{ $item->sisa_saldo_dilaporkan !== null ? 'background:#F0FFF4;border-color:rgba(52,211,153,.3);' : '' }}
                    {{ !$item->isPaid() ? 'opacity:0.5;' : '' }}">
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">

                        {{-- Info Whitelist --}}
                        <div style="flex:1;min-width:140px;">
                            <div style="font-weight:700;font-size:.85rem;">{{ $wl->nama ?? '-' }}</div>
                            <div style="font-size:.7rem;color:#9ca3af;">
                                {{ ucfirst($wl->platform??'') }} · {{ $wl->kode??'' }}
                            </div>
                            @if($item->isPaid())
                            <div style="font-size:.7rem;color:var(--color-primary);margin-top:2px;">
                                🏦 VA: <span style="font-family:monospace;font-weight:700;">{{ $item->va_number }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Nominal Top Up --}}
                        <div style="min-width:120px;text-align:right;">
                            <div style="font-size:.68rem;color:#6b7280;">Nominal Top Up</div>
                            <div style="font-weight:800;font-size:.95rem;color:var(--color-primary);">
                                Rp {{ number_format($item->nominal,0,',','.') }}
                            </div>
                        </div>

                        {{-- Input Sisa Saldo (hanya untuk item yg sudah dibayar & belum dilaporkan) --}}
                        @if($item->isPaid() && $item->sisa_saldo_dilaporkan === null)
                        <input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $item->id }}">
                        <div style="min-width:180px;flex-shrink:0;">
                            <label style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;margin-bottom:3px;">
                                📊 Sisa Saldo Saat Ini (Rp)
                            </label>
                            <input type="number" name="items[{{ $i }}][sisa_saldo_dilaporkan]" min="0" step="100"
                                   value="{{ old('items.'.$i.'.sisa_saldo_dilaporkan') }}"
                                   placeholder="Cek di platform iklan"
                                   required
                                   class="clay-input"
                                   style="font-size:.85rem;padding:8px 12px;">
                        </div>
                        @php $i++; @endphp
                        @elseif($item->sisa_saldo_dilaporkan !== null)
                        <div style="min-width:160px;text-align:right;">
                            <div style="font-size:.68rem;color:#6b7280;">✅ Sisa Saldo Dilaporkan</div>
                            <div style="font-weight:800;font-size:.9rem;color:var(--color-green);">
                                Rp {{ number_format($item->sisa_saldo_dilaporkan,0,',','.') }}
                            </div>
                        </div>
                        @elseif(!$item->isPaid())
                        <div style="min-width:160px;text-align:right;">
                            <div style="font-size:.68rem;color:#9ca3af;">⏳ Menunggu input VA</div>
                            <div style="font-size:.72rem;color:#9ca3af;">Selesaikan input VA terlebih dahulu</div>
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
                <span style="font-size:.82rem;color:#0d9488;font-weight:600;">Total Top Up</span>
                <span style="font-size:1.3rem;font-weight:900;color:var(--color-secondary);">
                    Rp {{ number_format($proposal->total_nominal,0,',','.') }}
                </span>
            </div>

            {{-- Submit --}}
            @if(!$proposal->isAllSisaSaldoReported())
            <div style="display:flex;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary" style="flex:1;justify-content:center;font-size:.9rem;padding:12px;">
                    ✅ Laporkan Sisa Saldo
                </button>
                <a href="{{ route('topup.show', $proposal) }}" class="clay-btn clay-btn-outline" data-page-link>Kembali</a>
            </div>
            @endif
        </form>
    </div>

    {{-- Info VA (copy) --}}
    @if($proposal->isVaPaid())
    <div class="clay-card" style="padding:16px;margin-top:16px;" data-reveal>
        <div style="font-weight:700;font-size:.8rem;color:#6b7280;margin-bottom:8px;">🏦 VA sudah dibayar oleh Super Admin</div>
        @foreach($proposal->items as $item)
        @if($item->va_number)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.05);font-size:.83rem;">
            <span style="color:#374151;font-weight:600;">{{ $item->whitelist?->nama ?? '-' }}</span>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-family:monospace;font-weight:700;color:var(--color-primary);">{{ $item->va_number }}</span>
            </div>
        </div>
        @endif
        @endforeach
    </div>
    @endif
</div>
@endsection
