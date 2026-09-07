{{-- Tabel whitelist dengan expand row — dipakai halaman whitelist (tab per advertiser) --}}
@php
    $isAdv = auth()->user()->hasRole('advertiser');
    // Kolom Pemilik hanya relevan di tab "Semua whitelist" — di tab per advertiser
    // pemiliknya sudah jelas dari tab yang dipilih, jadi kolomnya disembunyikan.
    $showPemilik = (! $isAdv) && (($activeTab ?? 'all') === 'all');
    // Kolom: chevron+nama+kode+[pemilik]+platform+status+[aksi]
    //   advertiser: 6 (tanpa pemilik, ada aksi)
    //   non-advertiser tab "Semua": 6 (ada pemilik, tanpa aksi)
    //   non-advertiser tab advertiser: 5 (tanpa pemilik, tanpa aksi)
    $colspan = $isAdv ? 6 : ($showPemilik ? 6 : 5);
@endphp
<div class="table-scroll table-scroll-limit table-scroll-maxrows">
    <table class="clay-table" id="wl-table">
        <thead>
            <tr>
                <th style="width:36px;"></th>
                <th>Nama Akun</th>
                <th>Kode</th>
                @if($showPemilik)
                <th>Pemilik</th>
                @endif
                <th>Platform</th>
                <th>Status</th>
                @if($isAdv)
                <th style="text-align:right;">Aksi</th>
                @endif
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
            @if($showPemilik)
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
            @if($isAdv)
            <td style="text-align:right;" onclick="event.stopPropagation()">
                <div style="display:flex;justify-content:flex-end;gap:6px;">
                    <a href="{{ route('whitelist.edit',$wl) }}"
                       class="clay-btn clay-btn-secondary" style="padding:5px 10px;font-size:.72rem;"
                       data-page-link>✏️</a>
                    <form method="POST" action="{{ route('whitelist.destroy',$wl) }}"
                          onsubmit="return confirm('Hapus whitelist {{ $wl->nama }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="clay-btn clay-btn-danger"
                                style="padding:5px 10px;font-size:.72rem;">🗑</button>
                    </form>
                </div>
            </td>
            @endif
        </tr>

        <tr id="detail-{{ $wl->id }}" class="wl-detail" style="display:none;">
            <td colspan="{{ $colspan }}" style="padding:0;background:#fafafa;">
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
            <td colspan="{{ $colspan }}" style="text-align:center;padding:48px 16px;">
                <div style="font-size:2.5rem;margin-bottom:8px;">✅</div>
                @if(isset($activeTab) && $activeTab !== 'all' && isset($advertisers) && $advertisers->isNotEmpty())
                <p style="color:#9ca3af;">Advertiser ini belum memiliki whitelist</p>
                @else
                <p style="color:#9ca3af;">Belum ada data whitelist</p>
                @endif
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($whitelists->hasPages())
<div style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">
    {{ $whitelists->links() }}
</div>
@endif