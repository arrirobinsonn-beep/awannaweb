@extends('layouts.app')
@section('title','Whitelist')
@section('page-title','✅ Whitelist Akun Iklan')
@section('page-subtitle','Kelola akun iklan yang sudah diwhitelist')

@section('content')

{{-- Toolbar --}}
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;justify-content:space-between;margin-bottom:18px;" data-reveal>
    <form method="GET" action="{{ route('whitelist.index') }}"
          style="display:flex;flex-wrap:wrap;gap:8px;flex:1;min-width:0;">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari nama atau kode..."
               class="clay-input" style="flex:1;min-width:140px;max-width:260px;">
        <select name="platform" class="clay-input" style="width:auto;min-width:120px;">
            <option value="">Semua Platform</option>
            @foreach($platforms as $p)
            <option value="{{ $p }}" {{ request('platform')===$p?'selected':'' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
        <select name="status" class="clay-input" style="width:auto;min-width:110px;">
            <option value="">Semua Status</option>
            <option value="aktif"    {{ request('status')==='aktif'   ?'selected':'' }}>Aktif</option>
            <option value="nonaktif" {{ request('status')==='nonaktif'?'selected':'' }}>Nonaktif</option>
        </select>
        <button type="submit" class="clay-btn clay-btn-secondary">🔍</button>
        @if(request()->hasAny(['search','platform','status']))
        <a href="{{ route('whitelist.index') }}" class="clay-btn clay-btn-outline">Reset</a>
        @endif
    </form>

    @can('whitelist.create')
    <a href="{{ route('whitelist.create') }}" class="clay-btn clay-btn-primary" data-page-link>
        ＋ Tambah Whitelist
    </a>
    @endcan
</div>

{{-- Tabel dengan expand row --}}
<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll">
        <table class="clay-table" id="wl-table">
            <thead>
                <tr>
                    <th style="width:36px;"></th>
                    <th>Nama Akun</th>
                    <th>Kode</th>
                    @if(auth()->user()->hasRole(['owner','super_admin','mentor']))
                    <th>Pemilik</th>
                    @endif
                    <th>Platform</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($whitelists as $wl)
            @php
                $pMap = ['facebook'=>'clay-badge-blue','tiktok'=>'clay-badge-purple',
                         'google'=>'clay-badge-yellow','instagram'=>'clay-badge-red'];
                $pClass = $pMap[strtolower($wl->platform)] ?? 'clay-badge-gray';
            @endphp

            {{-- Row utama --}}
            <tr class="wl-row" data-id="{{ $wl->id }}" style="cursor:pointer;"
                onclick="toggleRow({{ $wl->id }})">
                <td style="text-align:center;">
                    <span class="wl-chevron" id="chevron-{{ $wl->id }}"
                          style="display:inline-block;transition:transform .25s;font-size:.8rem;color:#9ca3af;">▶</span>
                </td>
                <td>
                    <div style="font-weight:700;font-size:.875rem;">{{ $wl->nama }}</div>
                </td>
                <td>
                    <span class="clay-badge clay-badge-gray" style="font-family:monospace;font-size:.72rem;">
                        {{ $wl->kode }}
                    </span>
                </td>
                @if(auth()->user()->hasRole(['owner','super_admin','mentor']))
                <td>
                    <div style="font-size:.83rem;font-weight:600;">
                        {{ $wl->user->panggilan ?? $wl->user->display_name ?? '-' }}
                    </div>
                    <div style="font-size:.7rem;color:#9ca3af;">{{ $wl->user->email ?? '' }}</div>
                </td>
                @endif
                <td><span class="clay-badge {{ $pClass }}">{{ ucfirst($wl->platform) }}</span></td>
                <td>
                    <span class="clay-badge {{ $wl->status==='aktif'?'clay-badge-green':'clay-badge-red' }}">
                        {{ ucfirst($wl->status) }}
                    </span>
                </td>
                <td style="text-align:right;" onclick="event.stopPropagation()">
                    <div style="display:flex;justify-content:flex-end;gap:6px;">
                        @can('whitelist.edit')
                        <a href="{{ route('whitelist.edit',$wl) }}"
                           class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;"
                           data-page-link>✏️</a>
                        @endcan
                        @can('whitelist.delete')
                        <form method="POST" action="{{ route('whitelist.destroy',$wl) }}"
                              onsubmit="return confirm('Hapus whitelist {{ $wl->nama }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="clay-btn clay-btn-danger"
                                    style="padding:5px 10px;font-size:.72rem;">🗑</button>
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>

            <tr id="detail-{{ $wl->id }}" class="wl-detail" style="display:none;">
                <td colspan="{{ auth()->user()->hasRole(['owner','super_admin','mentor']) ? 7 : 6 }}" style="padding:0;background:#fafafa;">
                    <div style="padding:18px 24px;border-top:2px dashed rgba(255,107,107,.15);">

                        {{-- Grid detail --}}
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:14px;">

                            <div class="clay-card-sm" style="padding:14px;background:#FFF5F5;">
                                <div style="font-size:.7rem;color:#9ca3af;margin-bottom:3px;">Total Top Up</div>
                                <div style="font-weight:800;font-size:1rem;color:var(--color-primary);">
                                    Rp {{ number_format($wl->total_topup,0,',','.') }}
                                </div>
                            </div>

                            <div class="clay-card-sm" style="padding:14px;background:#F0FFFE;">
                                <div style="font-size:.7rem;color:#9ca3af;margin-bottom:3px;">Total Spending</div>
                                <div style="font-weight:800;font-size:1rem;color:var(--color-secondary);">
                                    Rp {{ number_format($wl->total_spending,0,',','.') }}
                                </div>
                            </div>

                            <div class="clay-card-sm" style="padding:14px;background:{{ $wl->sisa_saldo >= 0 ? '#F0FFF4' : '#FFF5F5' }};">
                                <div style="font-size:.7rem;color:#9ca3af;margin-bottom:3px;">Sisa Saldo</div>
                                <div style="font-weight:800;font-size:1rem;color:{{ $wl->sisa_saldo >= 0 ? 'var(--color-green)' : 'var(--color-primary)' }};">
                                    Rp {{ number_format(abs($wl->sisa_saldo),0,',','.') }}
                                </div>
                            </div>

                            <div class="clay-card-sm" style="padding:14px;background:#FFF8F0;">
                                <div style="font-size:.7rem;color:#9ca3af;margin-bottom:3px;">Top Up Terakhir</div>
                                <div style="font-weight:800;font-size:1rem;color:var(--color-orange);">
                                    Rp {{ number_format($wl->nominal_terakhir_topup,0,',','.') }}
                                </div>
                            </div>

                        </div>

                        {{-- Info tambahan --}}
                        <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:.8rem;color:#6b7280;">
                            <span>📅 Terdaftar: <strong>{{ $wl->tanggal->translatedFormat('d M Y') }}</strong></span>
                            @if($wl->catatan)
                            <span>📝 {{ $wl->catatan }}</span>
                            @endif
                        </div>

                    </div>
                </td>
            </tr>

            @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:48px 16px;">
                    <div style="font-size:2.5rem;margin-bottom:8px;">✅</div>
                    <p style="color:#9ca3af;">Belum ada data whitelist</p>
                </td>            </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($whitelists->hasPages())
    <div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">
        {{ $whitelists->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
var openRows = new Set();

function toggleRow(id) {
    var detail  = document.getElementById('detail-' + id);
    var chevron = document.getElementById('chevron-' + id);
    var isOpen  = openRows.has(id);

    if (isOpen) {
        detail.style.display  = 'none';
        chevron.style.transform = 'rotate(0deg)';
        openRows.delete(id);
    } else {
        detail.style.display  = 'table-row';
        chevron.style.transform = 'rotate(90deg)';
        openRows.add(id);
    }
}
</script>
@endpush
@endsection
