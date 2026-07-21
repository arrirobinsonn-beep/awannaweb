@extends('layouts.app')
@section('title','Users & Role')
@section('page-title','👥 Users & Role')
@section('page-subtitle','Kelola pengguna dan hak akses')

@section('content')
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;justify-content:space-between;margin-bottom:18px;" data-reveal>
    <form method="GET" action="{{ route('user.index') }}" style="display:flex;flex-wrap:wrap;gap:8px;flex:1;min-width:0;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, divisi..." class="clay-input" style="flex:1;min-width:160px;max-width:280px;">
        <select name="role" class="clay-input" style="width:auto;min-width:130px;">
            <option value="">Semua Role</option>
            @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ request('role')===$role->name?'selected':'' }}>
                {{ ucfirst(str_replace('_',' ',$role->name)) }}
            </option>
            @endforeach
        </select>
        <button type="submit" class="clay-btn clay-btn-secondary">🔍</button>
    </form>
    <a href="{{ route('user.create') }}" class="clay-btn clay-btn-primary" data-page-link>＋ Tambah User</a>
</div>

<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table">
            <thead><tr>
                <th>User</th><th>Email</th><th>Divisi</th><th>Role</th><th>Status</th><th style="text-align:right;">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse($users as $u)
            @php
                $roleColors=['super_admin'=>'clay-badge-red','admin'=>'clay-badge-purple','advertiser'=>'clay-badge-blue','mentor'=>'clay-badge-yellow','keuangan'=>'clay-badge-green','cs'=>'clay-badge-gray'];
                $userRole = $u->getRoleNames()->first() ?? 'none';
                $roleClass= $roleColors[$userRole] ?? 'clay-badge-gray';
            @endphp
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="{{ $u->avatar_url }}" alt="avatar"
                             style="width:34px;height:34px;border-radius:10px;object-fit:cover;border:2px solid #f3f4f6;flex-shrink:0;">
                        <div>
                            <div style="font-weight:700;font-size:.875rem;">{{ $u->name }}</div>
                            @if($u->telepon)<div style="font-size:.7rem;color:#9ca3af;">{{ $u->telepon }}</div>@endif
                        </div>
                    </div>
                </td>
                <td style="font-size:.82rem;color:#6b7280;">{{ $u->email }}</td>
                <td style="font-size:.82rem;">{{ $u->divisi?ucfirst($u->divisi):'-' }}</td>
                <td><span class="clay-badge {{ $roleClass }}">{{ ucfirst(str_replace('_',' ',$userRole)) }}</span></td>
                <td><span class="clay-badge {{ $u->is_active?'clay-badge-green':'clay-badge-red' }}">{{ $u->is_active?'Aktif':'Nonaktif' }}</span></td>
                <td style="text-align:right;">
                    <div style="display:flex;justify-content:flex-end;gap:6px;">
                        <a href="{{ route('user.edit',$u) }}" class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;" data-page-link>✏️</a>
                        @if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('user.destroy',$u) }}" onsubmit="return confirm('Hapus user {{ $u->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="clay-btn clay-btn-danger" style="padding:5px 10px;font-size:.72rem;">🗑</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">👥</div>
                <p style="color:#9ca3af;">Belum ada user</p>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())<div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">{{ $users->links() }}</div>@endif
</div>
@endsection
