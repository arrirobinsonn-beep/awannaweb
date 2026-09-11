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
                <th style="width:120px;text-align:center;">Produk</th>
                <th style="width:140px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventories as $inv)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="font-weight:600;">{{ $inv->name }}</td>
                <td style="text-align:center;">
                    <span class="clay-badge {{ $inv->products_count > 0 ? 'clay-badge-green' : 'clay-badge-gray' }}" style="font-size:.72rem;">
                        {{ $inv->products_count }} produk
                    </span>
                </td>
                <td style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
                    <button type="button" class="clay-btn clay-btn-outline clay-btn-sm"
                            onclick="openEditModal({{ $inv->id }}, '{{ addslashes($inv->name) }}')"
                            style="font-size:.72rem;padding:4px 10px;">
                        ✏️ Edit
                    </button>
                    <form method="POST" action="{{ route('inventory.master.destroy',$inv) }}"
                          onsubmit="return confirm('Hapus inventory {{ addslashes($inv->name) }}?{{ $inv->products_count > 0 ? ' Ada '.$inv->products_count.' produk terhubung — akan dibatalkan.' : '' }}')">
                        @csrf @method('DELETE')
                        <button class="clay-btn clay-btn-danger clay-btn-sm" style="font-size:.72rem;padding:4px 10px;">🗑</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center;padding:48px;color:#9ca3af;">Belum ada inventory.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ═══ EDIT MODAL ═══ --}}
<div id="modal-edit" class="clay-modal" style="display:none;">
    <div class="clay-modal-overlay" onclick="closeEditModal()"></div>
    <div class="clay-modal-content" style="max-width:440px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="margin:0;font-size:1rem;font-weight:700;">✏️ Edit Inventory</h3>
            <button onclick="closeEditModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#9ca3af;">✕</button>
        </div>
        <form id="edit-form" method="POST" style="display:flex;flex-direction:column;gap:14px;">
            @csrf @method('PUT')
            <div>
                <label class="field-label">NAMA INVENTORY</label>
                <input type="text" name="name" id="edit-name" required class="clay-input" maxlength="255" style="width:100%;">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeEditModal()" class="clay-btn clay-btn-outline">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<style>
.field-label { display:block;font-size:.75rem;font-weight:700;margin-bottom:4px;color:#374151; }
.clay-modal { position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px; }
.clay-modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:0; }
.clay-modal-content { position:relative;z-index:1;background:#fff;border-radius:16px;padding:24px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.2); }
@media(max-width:640px) {
    .clay-modal-content { padding:16px; }
    .clay-table td, .clay-table th { padding:8px 6px !important; font-size:.75rem !important; }
}
</style>

<script>
function openEditModal(id, name) {
    var modal = document.getElementById('modal-edit');
    var form = document.getElementById('edit-form');
    var input = document.getElementById('edit-name');
    form.action = '{{ url("inventory/master") }}/' + id;
    input.value = name;
    modal.style.display = 'flex';
    input.focus();
    input.select();
}
function closeEditModal() {
    document.getElementById('modal-edit').style.display = 'none';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEditModal();
});
</script>
@endsection
