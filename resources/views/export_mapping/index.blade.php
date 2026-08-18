@extends('layouts.app')

@section('title', 'Aturan Export Template')
@section('page-title', '📋 Aturan Export Template')
@section('page-subtitle', 'Kelola template export courier — buat baru, edit, atau hapus')

@push('styles')
<style>
    .et-card { transition: box-shadow .2s, transform .2s; }
    .et-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
    .et-icon {
        width: 42px; height: 42px; border-radius: 13px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; font-weight: 800;
    }
    .et-key { font-size: .68rem; color: #9ca3af; font-weight: 600; }
    .et-courier { font-size: .66rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #f3f4f6; color: #6b7280; }
    .et-empty { font-size: .7rem; color: #b45309; font-weight: 700; }
    .et-ok { font-size: .7rem; color: #059669; font-weight: 700; }
    .et-del-form { display: inline; }
</style>
@endpush

@section('content')

{{-- Info cara kerja --}}
<div class="clay-card" style="padding:14px 18px;margin-bottom:16px;background:linear-gradient(135deg,#FFF7F7,#fff);" data-reveal>
    <div style="font-size:.75rem;color:#4b5563;line-height:1.6;">
        💡 <b>Cara kerja:</b> setiap template export dipakai untuk mencetak file pengiriman ke
        aplikasi ekspedisi (courier). Klik <b>Template Baru</b> untuk membuat template custom (upload file
        template CSV → cocokkan kolom). Template aktif otomatis muncul sebagai tombol export di halaman
        <b>Data Mentah</b>. <b>Hapus</b> bersifat permanen — pastikan template lain (atau rule courier
        fallback <code>spx</code>) tetap menutupi courier yang bersangkutan.
    </div>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;" data-reveal>
    <h2 style="margin:0;font-size:1.02rem;font-weight:800;">🗂 Daftar Template <span style="color:#9ca3af;font-weight:600;font-size:.78rem;">({{ $templates->count() }})</span></h2>
    <a href="{{ route('export-mapping.create') }}" class="clay-btn clay-btn-primary">➕ Template Baru</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:14px;">
    @forelse($templates as $tpl)
    <div class="clay-card et-card" style="padding:16px;display:flex;flex-direction:column;gap:12px;" data-reveal>
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="et-icon" style="background:{{ $tpl->key === 'flik' ? 'linear-gradient(135deg,#dbeafe,#eff6ff)' : ($tpl->key === 'sicepat' ? 'linear-gradient(135deg,#dcfce7,#f0fdf4)' : 'linear-gradient(135deg,#f3e8ff,#faf5ff)') }};color:{{ $tpl->key === 'flik' ? '#1d4ed8' : ($tpl->key === 'sicepat' ? '#047857' : '#7e22ce') }};">
                {{ $tpl->key === 'flik' ? 'F' : ($tpl->key === 'sicepat' ? 'S' : strtoupper(substr($tpl->key, 0, 1))) }}
            </div>
            <div style="min-width:0;">
                <div style="font-weight:800;font-size:.92rem;color:#1e1b2e;">{{ $tpl->name }}</div>
                <div class="et-key">key: {{ $tpl->key }}</div>
            </div>
            <div style="margin-left:auto;text-align:right;flex-shrink:0;">
                <div style="font-size:1.1rem;font-weight:800;color:var(--color-primary,#FF6B6B);">{{ $tpl->columns_count }}</div>
                <div style="font-size:.62rem;color:#9ca3af;font-weight:600;">kolom</div>
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;">
            @if(!empty($tpl->couriers))
                @foreach($tpl->couriers as $c)
                    <span class="et-courier">{{ $c }}</span>
                @endforeach
            @else
                <span class="et-courier">courier = key</span>
            @endif
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;border-top:1px solid rgba(0,0,0,.05);padding-top:12px;">
            <div>
                @if($tpl->columns_count > 0)
                    <span class="et-ok">✓ mapping aktif</span>
                @else
                    <span class="et-empty">⚠ belum ada mapping</span>
                @endif
            </div>
            <div style="display:flex;gap:8px;">
                <a href="{{ route('export-mapping.edit', $tpl) }}" class="clay-btn" style="padding:6px 14px;font-size:.76rem;">✏️ Edit</a>
                <form method="POST" action="{{ route('export-mapping.destroy', $tpl) }}" class="et-del-form"
                      onsubmit="return confirm('Hapus permanen template {{ $tpl->name }} beserta mapping-nya? Export dengan template ini akan gagal sampai dibuat ulang.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="clay-btn clay-btn-danger" style="padding:6px 14px;font-size:.76rem;">🗑 Hapus</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="clay-card" style="grid-column:1/-1;padding:48px;text-align:center;color:#9ca3af;">
        Belum ada template. Klik <b>➕ Template Baru</b> untuk membuat template pertama.
    </div>
    @endforelse
</div>

@endsection
