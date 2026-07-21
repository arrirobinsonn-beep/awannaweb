@extends('layouts.app')
@section('title','Tim Saya')
@section('page-title','👥 Tim Saya')
@section('page-subtitle', isset($advertiser) ? "Tim CS — bernaung di bawah {$advertiser->panggilan}" : 'Daftar CS (Customer Service) yang tergabung dalam tim Anda')

@section('content')
@if($team->isEmpty())
    {{-- Empty state --}}
    <div class="clay-card" style="padding:60px 20px;text-align:center;" data-reveal>
        <div style="font-size:3.5rem;margin-bottom:12px;">👥</div>
        <h3 style="font-weight:700;font-size:1.1rem;color:#1e1b2e;margin-bottom:6px;">Belum Ada Anggota Tim</h3>
        <p style="color:#9ca3af;font-size:.85rem;max-width:360px;margin:0 auto;">
            Saat ini belum ada CS (Customer Service) yang ditugaskan untuk tim Anda.
            Hubungi Super Admin untuk menambahkan anggota tim.
        </p>
    </div>
@else
    {{-- Stats ringkasan --}}
    <div class="grid-stats" data-reveal>
        <div class="clay-card" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:40px;height:40px;border-radius:12px;background:#FFF0F0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">👥</div>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:#1e1b2e;">{{ $team->count() }}</div>
                    <div style="font-size:.72rem;color:#9ca3af;font-weight:600;">Total Anggota</div>
                </div>
            </div>
        </div>
        <div class="clay-card" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:40px;height:40px;border-radius:12px;background:#F0FFF4;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">✅</div>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:#1e1b2e;">{{ $team->where('is_active', true)->count() }}</div>
                    <div style="font-size:.72rem;color:#9ca3af;font-weight:600;">Aktif</div>
                </div>
            </div>
        </div>
        <div class="clay-card" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:40px;height:40px;border-radius:12px;background:#FFF5F0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📧</div>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:#1e1b2e;">{{ $team->where('is_profile_complete', true)->count() }}</div>
                    <div style="font-size:.72rem;color:#9ca3af;font-weight:600;">Profil Lengkap</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Tim --}}
    <div class="clay-card" style="overflow:hidden;" data-reveal>
        <div class="table-scroll">
            <table class="clay-table">
                <thead><tr>
                    <th>Anggota Tim</th>
                    <th>Kontak</th>
                    <th>Status</th>
                    <th>Bergabung</th>
                </tr></thead>
                <tbody>
                @foreach($team as $member)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="{{ $member->avatar_url }}" alt="avatar"
                                 style="width:38px;height:38px;border-radius:12px;object-fit:cover;border:2px solid #f3f4f6;flex-shrink:0;">
                            <div>
                                <div style="font-weight:700;font-size:.875rem;">{{ $member->display_name }}</div>
                                <div style="font-size:.7rem;color:#9ca3af;">
                                    {{ $member->nama ? $member->nama : '—' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7280;">
                        <div>{{ $member->email }}</div>
                        @if($member->nohp)
                            <div style="font-size:.72rem;color:#9ca3af;">{{ $member->nohp }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="clay-badge {{ $member->is_active ? 'clay-badge-green' : 'clay-badge-red' }}">
                            {{ $member->is_active ? '✅ Aktif' : '❌ Nonaktif' }}
                        </span>
                        @if(!$member->is_profile_complete)
                        <span class="clay-badge clay-badge-yellow" style="margin-left:4px;">Profil ⏳</span>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:#9ca3af;">
                        {{ $member->created_at->translatedFormat('d M Y') }}
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
