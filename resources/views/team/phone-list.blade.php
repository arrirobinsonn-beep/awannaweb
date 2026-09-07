@extends('layouts.app')
@section('title','Daftar Nomor CS')
@section('page-title','📞 Daftar Nomor Telepon CS')
@section('page-subtitle','Data kontak dari upload Order Online')

@push('styles')
<style>
.clay-table th, .clay-table td { white-space:nowrap; }
.paginator { display:flex;align-items:center;gap:4px;justify-content:center;margin-top:8px;flex-wrap:wrap; }
.paginator button { background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:3px 10px;font-size:.7rem;cursor:pointer;color:#374151;font-weight:600;transition:all .12s; }
.paginator button:hover { border-color:#3b82f6;color:#3b82f6; }
.paginator button.active { background:#3b82f6;border-color:#3b82f6;color:#fff; }
.paginator button:disabled { opacity:.4;cursor:default; }
</style>
@endpush

@section('content')
@php
$perCs = $phoneList->groupBy('cs_name');
@endphp

@if($phoneList->isEmpty())
<div class="clay-card" style="padding:60px 20px;text-align:center;">
    <div style="font-size:3rem;margin-bottom:12px;">📞</div>
    <h3 style="font-weight:700;font-size:1rem;margin-bottom:6px;">Belum Ada Data Kontak</h3>
    <p style="color:#9ca3af;font-size:.85rem;">
        @if($csName)
            Belum ada nomor yang di-handle oleh <strong>{{ $csName }}</strong>.
        @else
            Upload Order Online di halaman Detail Per Daerah.
        @endif
    </p>
    <a href="{{ route('regional.index') }}" class="clay-btn clay-btn-primary" style="margin-top:16px;" data-page-link>📤 Upload</a>
</div>
@else
<div style="display:grid;gap:16px;">
    @foreach($perCs as $csGroup => $contacts)
    @php $perPage = 50; $totalPages = max(1, ceil($contacts->count() / $perPage)); @endphp
    <div class="clay-card" style="padding:14px;" data-cs="{{ $csGroup }}">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <strong style="font-size:.85rem;">{{ $csGroup }}</strong>
            <span style="font-size:.72rem;color:#6b7280;">{{ $contacts->count() }} nomor</span>
        </div>
        <input type="text" class="clay-input phone-filter" placeholder="Cari nomor..." style="margin-bottom:6px;">
        <div style="overflow-x:auto;">
            <table class="clay-table" style="font-size:.72rem;">
                <thead>
                    <tr>
                        <th style="min-width:130px;">No Telepon</th>
                        <th style="min-width:80px;">Order ID</th>
                        <th>Nama Pembeli</th>
                    </tr>
                </thead>
                <tbody data-start="0" data-per="{{ $perPage }}">
                    @foreach($contacts as $i => $contact)
                    <tr class="phone-row" data-idx="{{ $i }}">
                        <td style="font-family:monospace;">
                            <span class="phone-num">{{ $contact->phone_normalized }}</span>
                            <button type="button" style="background:none;border:none;cursor:pointer;font-size:.65rem;color:#3b82f6;padding:0 4px;" onclick="copyPhone(this)" title="Salin">📋</button>
                        </td>
                        <td style="color:#6b7280;">{{ $contact->order_id ?? '-' }}</td>
                        <td style="color:#6b7280;">{{ $contact->buyer_name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($totalPages > 1)
        <div class="paginator" data-total="{{ $totalPages }}"></div>
        @endif
    </div>
    @endforeach
</div>
@endif
@endsection

@push('scripts')
<script>
function copyPhone(btn) {
    var el = btn.closest('td').querySelector('.phone-num');
    var phone = el ? el.textContent.trim() : '';
    if (!phone) return;
    var ta = document.createElement('textarea');
    ta.value = phone;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    btn.textContent = '✅';
    setTimeout(function() { btn.textContent = '📋'; }, 1500);
}

function renderPagination(card, page) {
    var tbody = card.querySelector('tbody');
    var per = parseInt(tbody.dataset.per);
    var rows = Array.from(tbody.querySelectorAll('.phone-row'));
    var total = rows.length;
    var pages = Math.max(1, Math.ceil(total / per));
    page = Math.min(page, pages - 1);

    rows.forEach(function(row, i) {
        var lo = page * per;
        var hi = lo + per;
        row.style.display = (i >= lo && i < hi) ? '' : 'none';
    });

    var paginator = card.querySelector('.paginator');
    if (!paginator || pages <= 1) return;

    var h = '';
    h += '<button type="button" onclick="goPage(this,\'' + (page - 1) + '\')"' + (page <= 0 ? ' disabled' : '') + '>‹ Prev</button>';
    var startP = Math.max(0, page - 2);
    var endP = Math.min(pages - 1, page + 2);
    for (var p = startP; p <= endP; p++) {
        h += '<button type="button" class="' + (p === page ? 'active' : '') + '" onclick="goPage(this,\'' + p + '\')">' + (p + 1) + '</button>';
    }
    h += '<button type="button" onclick="goPage(this,\'' + (page + 1) + '\')"' + (page >= pages - 1 ? ' disabled' : '') + '>Next ›</button>';
    paginator.innerHTML = h;
}

function goPage(btn, page) {
    var card = btn.closest('[data-cs]');
    renderPagination(card, parseInt(page));
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-cs]').forEach(function(card) {
        renderPagination(card, 0);

        card.querySelector('.phone-filter').addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            var rows = card.querySelectorAll('.phone-row');
            var shown = 0;
            rows.forEach(function(row) {
                var match = !q || row.textContent.toLowerCase().indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) shown++;
            });

            var paginator = card.querySelector('.paginator');
            if (paginator) paginator.style.display = q ? 'none' : '';
        });
    });
});
</script>
@endpush
