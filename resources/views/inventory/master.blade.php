@extends('layouts.app')
@section('title','Master Inventory')
@section('page-title','🏭 Master Inventory')
@section('page-subtitle','Kelola daftar inventory (gudang)')

@section('content')
@if(session('success'))
<div class="clay-card" style="padding:12px 16px;margin-bottom:16px;background:#d1fae5;color:#065f46;font-weight:600;border-radius:8px;">
    {{ session('success') }}
</div>
@endif

<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="POST" action="{{ route('inventory.master.store') }}" style="display:flex;gap:10px;align-items:flex-end;">
        @csrf
        <div style="flex:1;">
            <label class="field-label">NAMA INVENTORY</label>
            <input type="text" name="name" required class="clay-input" placeholder="Gudang Kuningan" maxlength="255">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary">+ Tambah</button>
    </form>
</div>

<div class="clay-card" style="overflow:hidden;" data-reveal>
    <table class="clay-table">
        <thead>
            <tr>
                <th style="width:50px;">No</th>
                <th>Nama Inventory</th>
                <th style="width:100px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventories as $inv)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="font-weight:600;">{{ $inv->name }}</td>
                <td>
                    <form method="POST" action="{{ route('inventory.master.destroy',$inv) }}"
                          onsubmit="return confirm('Hapus inventory {{ $inv->name }}?')">
                        @csrf @method('DELETE')
                        <button class="clay-btn clay-btn-sm clay-btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align:center;padding:48px;color:#9ca3af;">Belum ada inventory.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>
.field-label { display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151; }
</style>
@endsection
