@extends('layouts.app')
@section('title','Notifikasi')
@section('page-title','🔔 Notifikasi')
@section('page-subtitle','Semua pemberitahuan dan aktivitas terbaru')

@section('content')

@if(auth()->user()->hasRole(['owner','super_admin','admin']) && $notifications->count() > 0)
    @php $unread = $notifications->where('is_read',false)->count(); @endphp
    @if($unread > 0)
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px;" data-reveal>
        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
            @csrf
            <button type="submit" class="clay-btn clay-btn-outline" style="padding:6px 14px;font-size:.78rem;">
                ✅ Tandai Semua Dibaca ({{ $unread }})
            </button>
        </form>
    </div>
    @endif
@endif

<div class="clay-card" style="overflow:hidden;" data-reveal>
    @forelse($notifications as $n)
    <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;
                border-bottom:1px solid rgba(0,0,0,.05);
                {{ !$n->is_read ? 'background:#FEF2F2;' : '' }}
                transition:background .2s;">
        {{-- Icon --}}
        <div style="font-size:1.5rem;flex-shrink:0;margin-top:2px;">
            @switch($n->type)
                @case('new_proposal') 💰 @break
                @case('proposal_approved') ✅ @break
                @case('proposal_declined') ❌ @break
                @case('payment_confirmed') 💳 @break
                @default 🔔
            @endswitch
        </div>

        {{-- Content --}}
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:.85rem;color:#1e1b2e;{{ !$n->is_read ? '' : '' }}">
                {{ $n->title }}
                @if(!$n->is_read)
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--color-primary,#FF6B6B);margin-left:6px;"></span>
                @endif
            </div>
            <div style="font-size:.78rem;color:#6b7280;margin-top:2px;line-height:1.5;">
                {{ $n->message }}
            </div>
            <div style="display:flex;align-items:center;gap:12px;margin-top:6px;">
                <span style="font-size:.68rem;color:#9ca3af;">{{ $n->created_at->diffForHumans() }}</span>
                @if($n->fromUser)
                <span style="font-size:.68rem;color:#9ca3af;">oleh {{ $n->fromUser->display_name }}</span>
                @endif
            </div>
        </div>

        {{-- Action --}}
        <div style="flex-shrink:0;">
            @php $url = $n->data['url'] ?? null; @endphp
            @if($url)
            <form method="POST" action="{{ route('notifications.read', $n) }}" style="display:inline;">
                @csrf
                <button type="submit" class="clay-btn clay-btn-outline" style="padding:5px 12px;font-size:.72rem;">
                    @if(!$n->is_read) 👁 Lihat @else 👁 @endif
                </button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:48px 16px;">
        <div style="font-size:2.5rem;margin-bottom:8px;">🔔</div>
        <p style="color:#9ca3af;font-size:.9rem;">Belum ada notifikasi</p>
        <p style="color:#d1d5db;font-size:.78rem;">Notifikasi akan muncul saat ada aktivitas pengajuan top up</p>
    </div>
    @endforelse

    @if($notifications->hasPages())
    <div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $notifications->links() }}</div>
    @endif
</div>

<div style="margin-top:16px;">
    <a href="{{ route('dashboard') }}" class="clay-btn clay-btn-outline" data-page-link>← Kembali ke Dashboard</a>
</div>
@endsection
