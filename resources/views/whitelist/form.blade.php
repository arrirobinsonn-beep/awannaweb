@extends('layouts.app')
@section('title', $mode==='create'?'Tambah Whitelist':'Edit Whitelist')
@section('page-title', $mode==='create'?'➕ Tambah Whitelist':'✏️ Edit Whitelist')
@section('page-subtitle','Data akun iklan yang diwhitelist')

@section('content')
<div style="max-width:640px;">
    <div class="clay-card" style="padding:28px;" data-reveal>
        <form method="POST"
              action="{{ $mode==='create'?route('whitelist.store'):route('whitelist.update',$whitelist) }}">
            @csrf
            @if($mode==='edit') @method('PUT') @endif

            <div class="form-grid" style="gap:16px;">

                {{-- Nama --}}
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">
                        Nama Akun <span style="color:#f87171;">*</span>
                    </label>
                    <input type="text" name="nama"
                           value="{{ old('nama',$whitelist->nama) }}"
                           placeholder="Contoh: Awanna Beauty FB 01"
                           class="clay-input @error('nama') border-red-400 @enderror">
                    @error('nama')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Kode --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">
                        Kode Unik <span style="color:#f87171;">*</span>
                    </label>
                    <input type="text" name="kode"
                           value="{{ old('kode',$whitelist->kode) }}"
                           placeholder="WL-FB-001"
                           class="clay-input @error('kode') border-red-400 @enderror"
                           style="font-family:monospace;">
                    @error('kode')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Platform --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">
                        Platform <span style="color:#f87171;">*</span>
                    </label>
                    <select name="platform" class="clay-input @error('platform') border-red-400 @enderror">
                        <option value="">— Pilih Platform —</option>
                        @foreach(['facebook','tiktok','google','instagram','youtube','twitter'] as $p)
                        <option value="{{ $p }}"
                            {{ old('platform',$whitelist->platform)===$p?'selected':'' }}>
                            {{ ucfirst($p) }}
                        </option>
                        @endforeach
                    </select>
                    @error('platform')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Pemilik --}}
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">
                        Pemilik (Advertiser) <span style="color:#f87171;">*</span>
                    </label>
                    <select name="user_id" class="clay-input @error('user_id') border-red-400 @enderror">
                        <option value="">— Pilih Pemilik —</option>
                        @foreach($advertisers as $adv)
                        <option value="{{ $adv->id }}"
                            {{ old('user_id',$whitelist->user_id)==$adv->id?'selected':'' }}>
                            {{ $adv->panggilan ?? $adv->nama ?? $adv->email }}
                            ({{ $adv->email }})
                        </option>
                        @endforeach
                    </select>
                    @error('user_id')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Tanggal --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">
                        Tanggal Terdaftar <span style="color:#f87171;">*</span>
                    </label>
                    <input type="date" name="tanggal"
                           value="{{ old('tanggal', $whitelist->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           class="clay-input @error('tanggal') border-red-400 @enderror">
                    @error('tanggal')<p style="color:#ef4444;font-size:.72rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Status --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">
                        Status <span style="color:#f87171;">*</span>
                    </label>
                    <select name="status" class="clay-input">
                        <option value="aktif"    {{ old('status',$whitelist->status??'aktif')==='aktif'   ?'selected':'' }}>Aktif</option>
                        <option value="nonaktif" {{ old('status',$whitelist->status)==='nonaktif'?'selected':'' }}>Nonaktif</option>
                    </select>
                </div>

                {{-- Total Top Up --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">
                        Total Top Up (Rp)
                    </label>
                    <input type="number" name="total_topup" min="0" step="1000"
                           value="{{ old('total_topup', $whitelist->total_topup ?? 0) }}"
                           placeholder="0"
                           class="clay-input">
                </div>

                {{-- Nominal Terakhir Top Up --}}
                <div>
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">
                        Nominal Top Up Terakhir (Rp)
                    </label>
                    <input type="number" name="nominal_terakhir_topup" min="0" step="1000"
                           value="{{ old('nominal_terakhir_topup', $whitelist->nominal_terakhir_topup ?? 0) }}"
                           placeholder="0"
                           class="clay-input">
                </div>

                {{-- Catatan --}}
                <div class="col-span-2">
                    <label style="display:block;font-size:.83rem;font-weight:700;margin-bottom:6px;">Catatan</label>
                    <textarea name="catatan" rows="2" placeholder="Catatan tambahan..."
                              class="clay-input" style="resize:none;">{{ old('catatan',$whitelist->catatan) }}</textarea>
                </div>

            </div>

            <div style="display:flex;gap:10px;margin-top:20px;padding-top:18px;border-top:1px solid rgba(0,0,0,.06);">
                <button type="submit" class="clay-btn clay-btn-primary">
                    {{ $mode==='create'?'＋ Simpan Whitelist':'💾 Update Whitelist' }}
                </button>
                <a href="{{ route('whitelist.index') }}" class="clay-btn clay-btn-outline" data-page-link>Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
