@extends('layouts.app')
@section('title','Mapping Tim CS')
@section('page-title','🗺️ Mapping Tim CS')
@section('page-subtitle','Lihat seluruh CS (Customer Service) dan advertiser yang menaunginya')

@section('content')
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:18px;" data-reveal>
    <form method="GET" action="{{ route('team.admin-index') }}" style="display:flex;flex-wrap:wrap;gap:8px;flex:1;min-width:0;">
        <select name="advertiser_id" class="clay-input" style="width:auto;min-width:180px;">
            <option value="">Semua Advertiser</option>
            @foreach($advertisers as $adv)
            <option value="{{ $adv->id }}" {{ request('advertiser_id') == $adv->id ? 'selected' : '' }}>
                {{ $adv->nama ?: $adv->email }}
            </option>
            @endforeach
        </select>
        <button type="submit" class="clay-btn clay-btn-secondary">🔍 Filter</button>
    </form>
    <a href="{{ route('user.create') }}" class="clay-btn clay-btn-primary" data-page-link>＋ Tambah CS</a>
</div>

<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th>CS</th>
                <th>Email</th>
                <th>Advertiser (Tuan)</th>
                <th>Status</th>
                <th style="text-align:right;">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse($csUsers as $cs)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="{{ $cs->avatar_url }}" alt="avatar"
                             style="width:34px;height:34px;border-radius:10px;object-fit:cover;border:2px solid #f3f4f6;flex-shrink:0;">
                        <div>
                            <div style="font-weight:700;font-size:.875rem;">{{ $cs->display_name }}</div>
                            @if($cs->nama)<div style="font-size:.7rem;color:#9ca3af;">{{ $cs->nama }}</div>@endif
                        </div>
                    </div>
                </td>
                <td style="font-size:.82rem;color:#6b7280;">{{ $cs->email }}</td>
                <td>
                    @if($cs->advertiser)
                    <div style="display:flex;align-items:center;gap:6px;">
                        <img src="{{ $cs->advertiser->avatar_url }}" alt="avatar"
                             style="width:24px;height:24px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                        <span style="font-size:.82rem;font-weight:600;">{{ $cs->advertiser->display_name }}</span>
                    </div>
                    @else
                    <span style="font-size:.8rem;color:#9ca3af;">— Belum ditugaskan</span>
                    @endif
                </td>
                <td>
                    <span class="clay-badge {{ $cs->is_active ? 'clay-badge-green' : 'clay-badge-red' }}">
                        {{ $cs->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td style="text-align:right;">
                    <a href="{{ route('user.edit', $cs) }}" class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;" data-page-link>✏️</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">👥</div>
                <p style="color:#9ca3af;">Belum ada user CS</p>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($csUsers->hasPages())<div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $csUsers->links() }}</div>@endif
</div>
@endsection
