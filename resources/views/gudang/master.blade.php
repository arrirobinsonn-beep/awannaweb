@extends('layouts.app')
@section('title','Master Gudang')
@section('page-title','🏭 Master Gudang')
@section('page-subtitle','Kelola daftar gudang')

@section('content')
@if(session('success'))
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#d1fae5;color:#065f46;font-weight:600;border-radius:8px;">
    {{ session('success') }}
</div>
@endif

<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="POST" action="{{ route('gudang.master.store') }}" style="display:flex;gap:10px;align-items:flex-end;">
        @csrf
        <div style="flex:1;">
            <label class="field-label">NAMA GUDANG</label>
            <input type="text" name="nama" required class="clay-input" placeholder="Gudang Kuningan" maxlength="255">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary">+ Tambah</button>
    </form>
</div>

<div class="clay-card" style="overflow:hidden;" data-reveal>
    <table class="clay-table">
        <thead>
            <tr>
                <th style="width:50px;">No</th>
                <th>Nama Gudang</th>
                <th style="width:100px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($gudangs as $g)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="font-weight:600;">{{ $g->nama }}</td>
                <td>
                    <form method="POST" action="{{ route('gudang.master.destroy',$g) }}"
                          onsubmit="return confirm('Hapus gudang {{ $g->nama }}?')">
                        @csrf @method('DELETE')
                        <button class="clay-btn clay-btn-sm clay-btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align:center;padding:48px;color:#9ca3af;">Belum ada gudang.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>
.field-label { display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151; }
</style>
@endsection
