@extends('layouts.app')
@section('title','Edit Kiriman Actual')
@section('page-title','🚚 Edit Kiriman Actual')
@section('page-subtitle','Ubah data kiriman')

@section('content')
<div class="clay-card" style="max-width:560px;margin:0 auto;padding:24px;" data-reveal>
    <form method="POST" action="{{ route('gudang.kiriman.update',$kiriman) }}"
          style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
        @csrf @method('PUT')

        <div>
            <label class="field-label">Tanggal</label>
            <input type="date" name="tanggal" required class="clay-input"
                   value="{{ $kiriman->tanggal->format('Y-m-d') }}">
        </div>
        <div>
            <label class="field-label">Jenis</label>
            <select name="jenis" required class="clay-input">
                <option value="TF" {{ $kiriman->jenis==='TF'?'selected':'' }}>TF (Transfer)</option>
                <option value="COD" {{ $kiriman->jenis==='COD'?'selected':'' }}>COD</option>
            </select>
        </div>
        <div>
            <label class="field-label">Dashboard</label>
            <select name="dashboard" required class="clay-input">
                @foreach($dashboards as $db)
                <option value="{{ $db }}" {{ $kiriman->dashboard===$db?'selected':'' }}>{{ $db }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Jumlah Resi</label>
            <input type="number" name="jumlah_resi" required class="clay-input" min="0"
                   value="{{ old('jumlah_resi',$kiriman->jumlah_resi) }}">
        </div>
        <div>
            <label class="field-label">Value Resi</label>
            <input type="number" name="value_resi" required class="clay-input" min="0" step="0.01"
                   value="{{ old('value_resi',$kiriman->value_resi) }}">
        </div>
        <div style="display:flex;align-items:flex-end;gap:8px;">
            <button type="submit" class="clay-btn clay-btn-primary">Simpan</button>
            <a href="{{ route('gudang.kiriman') }}" class="clay-btn clay-btn-outline">Batal</a>
        </div>
    </form>
</div>

<style>
.field-label { display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151; }
</style>
@endsection
