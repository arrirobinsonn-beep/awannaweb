@extends('layouts.app')
@section('title','Detail Top Up')
@section('page-title','💰 Detail Top Up')
@section('page-subtitle','Informasi lengkap pengajuan top up')

@section('content')
@php $user = auth()->user(); @endphp

<div style="max-width:720px;">

    {{-- Header Status --}}
    <div class="clay-card" style="padding:20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;" data-reveal>
        <div style="display:flex;align-items:center;gap:14px;">
            <img src="{{ $proposal->user->avatar_url ?? '' }}" style="width:44px;height:44px;border-radius:14px;object-fit:cover;border:2px solid rgba(255,107,107,.2);">
            <div>
                <div style="font-weight:800;font-size:1rem;color:#1e1b2e;">{{ $proposal->user->display_name ?? '-' }}</div>
                <div style="font-size:.75rem;color:#9ca3af;">Pengajuan • {{ $proposal->created_at->translatedFormat('d M Y H:i') }}</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            @php
                $smMap = ['pending'=>'clay-badge-yellow','approved'=>'clay-badge-green','declined'=>'clay-badge-red','menunggu_pembayaran'=>'clay-badge-blue','completed'=>'clay-badge-green'];
                $smLbl = ['pending'=>'Menunggu','approved'=>'Disetujui','declined'=>'Ditolak','menunggu_pembayaran'=>'⏳ Menunggu Pembayaran VA','completed'=>'✅ Selesai'];
            @endphp
            <span class="clay-badge {{ $smMap[$proposal->status] ?? 'clay-badge-gray' }}" style="font-size:.8rem;">
                {{ $smLbl[$proposal->status] ?? $proposal->status }}
            </span>
            @if($proposal->isApproved() && $proposal->approver)
            <span style="font-size:.72rem;color:#9ca3af;">oleh {{ $proposal->approver->display_name }}</span>
            @endif
        </div>
    </div>

    {{-- Info Performa --}}
    @if($proposal->today_lead !== null || $proposal->previous_topup_total > 0)
    <div class="clay-card" style="padding:18px;margin-bottom:16px;" data-reveal>
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:10px;">📊 Data Referensi</div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#FFF5F5;">
                <div style="font-size:.68rem;color:#6b7280;">Top Up Sebelumnya</div>
                <div style="font-weight:800;font-size:.85rem;color:var(--color-primary);">
                    Rp {{ number_format($proposal->previous_topup_total,0,',','.') }}
                </div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#FFF5F5;">
                <div style="font-size:.68rem;color:#6b7280;">Spending</div>
                <div style="font-weight:800;font-size:.85rem;color:var(--color-primary);">
                    Rp {{ number_format($proposal->today_spending??0,0,',','.') }}
                </div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F5F0FF;">
                <div style="font-size:.68rem;color:#6b7280;">Lead</div>
                <div style="font-weight:800;font-size:.85rem;color:var(--color-purple);">
                    {{ number_format($proposal->today_lead??0) }}
                </div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#F0FFFE;">
                <div style="font-size:.68rem;color:#6b7280;">Paid</div>
                <div style="font-weight:800;font-size:.85rem;color:var(--color-secondary);">
                    {{ number_format($proposal->today_paid??0) }}
                </div>
            </div>
        </div>

        {{-- CPA --}}
        @php
            $cpaLead = ($proposal->today_lead ?? 0) > 0 ? round(($proposal->today_spending ?? 0) / $proposal->today_lead) : 0;
            $cpaPaid = ($proposal->today_paid ?? 0) > 0 ? round(($proposal->today_spending ?? 0) / $proposal->today_paid) : 0;
        @endphp
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;">
            <div class="clay-card-sm" style="padding:10px 14px;text-align:center;background:linear-gradient(135deg,#FFF5F5,#fff);border:1.5px solid rgba(255,107,107,.15);">
                <div style="font-size:.68rem;color:#6b7280;font-weight:600;">📊 CPA Lead</div>
                <div style="font-weight:900;font-size:1rem;color:var(--color-primary);margin-top:2px;">Rp {{ number_format($cpaLead,0,',','.') }}</div>
            </div>
            <div class="clay-card-sm" style="padding:10px 14px;text-align:center;background:linear-gradient(135deg,#F0FFFE,#fff);border:1.5px solid rgba(78,205,196,.2);">
                <div style="font-size:.68rem;color:#6b7280;font-weight:600;">📊 CPA Paid</div>
                <div style="font-weight:900;font-size:1rem;color:var(--color-secondary);margin-top:2px;">Rp {{ number_format($cpaPaid,0,',','.') }}</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Decline Note --}}
    @if($proposal->decline_note)
    <div class="clay-alert clay-alert-warning" style="margin-bottom:16px;" data-reveal>
        <span>📝</span>
        <div style="flex:1;font-size:.83rem;">
            <strong>Alasan ditolak:</strong> {{ $proposal->decline_note }}
        </div>
    </div>
    @endif

    {{-- Daftar Whitelist --}}
    <div class="clay-card" style="padding:20px;margin-bottom:16px;" data-reveal>
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:14px;">
            📋 Rencana Top Up ({{ $proposal->items->count() }} whitelist)
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($proposal->items as $item)
            @php $wl = $item->whitelist; @endphp
            <div class="clay-card-sm" style="padding:14px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;
                        {{ $item->isPaid() ? 'background:#F0FFF4;border-color:rgba(52,211,153,.3);' : '' }}">
                <div style="flex:1;min-width:120px;">
                    <div style="font-weight:700;font-size:.85rem;">{{ $wl->nama ?? '-' }}</div>
                    <div style="font-size:.7rem;color:#9ca3af;">{{ ucfirst($wl->platform??'') }} · {{ $wl->kode??'' }}</div>
                </div>
                <div style="text-align:right;min-width:120px;">
                    <div style="font-weight:800;font-size:.9rem;color:var(--color-primary);">
                        Rp {{ number_format($item->nominal,0,',','.') }}
                    </div>
                    <div style="font-size:.7rem;color:#9ca3af;">
                        @if($item->isPaid())
                            ✅ Dibayar • {{ $item->paid_at?->format('d M Y') }}
                            @if($item->va_number)
                            <br>🏦 VA: {{ $item->va_number }}
                            @if(!$user->hasRole('advertiser'))
                            <button onclick="navigator.clipboard.writeText('{{ $item->va_number }}');this.textContent='✅ Tersalin!';setTimeout(()=>this.textContent='📋 Salin',2000)"
                                    style="background:none;border:1.5px solid #d1d5db;cursor:pointer;font-size:.68rem;padding:1px 8px;border-radius:6px;margin-left:4px;">
                                📋 Salin VA
                            </button>
                            @endif
                            @endif
                            @if($item->sisa_saldo_dilaporkan !== null)
                            <br>📊 Sisa saldo dilaporkan: <strong>Rp {{ number_format($item->sisa_saldo_dilaporkan,0,',','.') }}</strong>
                            @endif
                        @else
                            ⏳ Menunggu pembayaran
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Total --}}
        <div style="display:flex;justify-content:flex-end;align-items:center;gap:16px;
                    margin-top:14px;padding:14px 16px;border-radius:14px;
                    background:#F0FFFE;border:1.5px solid rgba(78,205,196,.3);">
            <span style="font-size:.82rem;color:#0d9488;font-weight:600;">Total</span>
            <span style="font-size:1.3rem;font-weight:900;color:var(--color-secondary);">
                Rp {{ number_format($proposal->total_nominal,0,',','.') }}
            </span>
        </div>
    </div>

    {{-- Approve/Decline (Super Admin only) --}}
    @if($user->hasRole(['owner','super_admin','admin']) && $proposal->isPending())
    <div class="clay-card" style="padding:20px;margin-bottom:16px;" data-reveal>
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:14px;">
            ✅ Review & Approval
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
            <form method="POST" action="{{ route('topup.approve', $proposal) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="clay-btn clay-btn-secondary"
                        onclick="return confirm('Setujui pengajuan top up Rp {{ number_format($proposal->total_nominal,0,',','.') }} ini?')">
                    ✅ Setujui
                </button>
            </form>
        </div>

        {{-- Decline Form --}}
        <form method="POST" action="{{ route('topup.decline', $proposal) }}">
            @csrf @method('PATCH')
            <div style="margin-bottom:10px;">
                <label style="display:block;font-size:.8rem;font-weight:700;color:#374151;margin-bottom:4px;">
                    📝 Alasan Penolakan
                </label>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
                    <button type="button" class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.72rem;"
                            onclick="document.getElementById('decline-note').value='Nominal terlalu besar, tolong kurangi yaa';">
                        💬 Nominal terlalu besar
                    </button>
                    <button type="button" class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.72rem;"
                            onclick="document.getElementById('decline-note').value='Data performa kurang mendukung, silakan ajukan ulang dengan nominal lebih kecil';">
                        💬 Performa kurang
                    </button>
                </div>
                <textarea id="decline-note" name="decline_note" rows="2" required
                          placeholder="Tulis alasan penolakan..."
                          class="clay-input @error('decline_note') border-red-400 @enderror"
                          style="resize:none;font-size:.83rem;">{{ old('decline_note') }}</textarea>
                @error('decline_note')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="clay-btn clay-btn-danger"
                    onclick="return confirm('Tolak pengajuan ini?')">
                ❌ Tolak Pengajuan
            </button>
        </form>
    </div>
    @endif

    {{-- Tombol Pembayaran (advertiser, setelah approve) --}}
    @if($user->hasRole('advertiser') && $proposal->isApproved() && !$proposal->isFullyPaid())
    <div style="display:flex;gap:10px;margin-bottom:16px;" data-reveal>
        <a href="{{ route('topup.payment', $proposal) }}" class="clay-btn clay-btn-primary" style="flex:1;justify-content:center;font-size:.9rem;padding:14px;" data-page-link>
            💳 Input Nomor VA
        </a>
    </div>
    @endif

    {{-- Super admin: lihat info VA + tombol tandai VA sudah dibayar --}}
    @if(!$user->hasRole('advertiser') && $proposal->isMenungguPembayaran())
    <div class="clay-card" style="padding:18px;margin-bottom:16px;" data-reveal>
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:10px;">🏦 Informasi VA Top Up</div>

        @if($proposal->isVaPaid())
            <div class="clay-alert clay-alert-success" style="margin-bottom:12px;">
                <span>✅</span>
                <span style="flex:1;font-size:.83rem;">VA sudah dibayar. Menunggu advertiser menginput sisa saldo.</span>
            </div>
        @else
            <div style="font-size:.8rem;color:#6b7280;margin-bottom:10px;">
                Advertiser sudah mencatat nomor VA. Silakan lakukan pembayaran ke VA berikut, lalu klik tombol di bawah:
            </div>
        @endif

        @foreach($proposal->items as $item)
        @if($item->va_number)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(0,0,0,.05);">
            <div>
                <div style="font-weight:600;font-size:.85rem;">{{ $item->whitelist?->nama ?? '-' }}</div>
                <div style="font-size:.72rem;color:#9ca3af;">Rp {{ number_format($item->nominal,0,',','.') }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-family:monospace;font-weight:700;color:var(--color-primary);font-size:.9rem;">{{ $item->va_number }}</span>
                <button onclick="navigator.clipboard.writeText('{{ $item->va_number }}');this.textContent='✅ Tersalin!';setTimeout(()=>this.textContent='📋 Salin VA',2000)"
                        class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.72rem;">
                    📋 Salin VA
                </button>
            </div>
        </div>
        @endif
        @endforeach

        {{-- Tombol Tandai VA Dibayar (Super Admin, sekali klik) --}}
        @if(!$proposal->isVaPaid())
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid rgba(0,0,0,.06);">
            <form method="POST" action="{{ route('topup.va-paid', $proposal) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="clay-btn clay-btn-secondary"
                        onclick="return confirm('Konfirmasi bahwa VA sudah dibayar? Advertiser akan mendapat notifikasi.')">
                    ✅ Tandai VA Sudah Dibayar
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Advertiser: lihat status VA --}}
    @elseif($user->hasRole('advertiser') && $proposal->isMenungguPembayaran())
        @if($proposal->isVaPaid())
            {{-- VA sudah dibayar, advertiser bisa input sisa saldo --}}
            <div class="clay-alert clay-alert-success" style="margin-bottom:16px;" data-reveal>
                <span>✅</span>
                <span style="flex:1;font-size:.83rem;">
                    VA sudah dibayar oleh Super Admin! Silakan cek sisa saldo whitelist di platform iklan dan laporkan.
                </span>
            </div>
            @if(!$proposal->isAllSisaSaldoReported())
            <div style="display:flex;gap:10px;margin-bottom:16px;" data-reveal>
                <a href="{{ route('topup.confirm', $proposal) }}" class="clay-btn clay-btn-primary" style="flex:1;justify-content:center;font-size:.9rem;padding:14px;" data-page-link>
                    📊 Input Sisa Saldo Whitelist
                </a>
            </div>
            @endif
        @else
            {{-- Masih menunggu Super Admin bayar --}}
            <div class="clay-alert clay-alert-info" style="margin-bottom:16px;" data-reveal>
                <span>ℹ️</span>
                <span style="flex:1;font-size:.83rem;">
                    Nomor VA sudah dicatat dan menunggu proses pembayaran oleh Super Admin.
                    Kami akan mengirimkan notifikasi setelah pembayaran berhasil dikonfirmasi.
                </span>
            </div>
        @endif
    @endif

    {{-- Kembali --}}
    <a href="{{ route('topup.index') }}" class="clay-btn clay-btn-outline" data-page-link>← Kembali</a>
</div>
@endsection
