@extends('layouts.app')
@section('title','Supplier')
@section('page-title','🏭 Data Supplier')
@section('page-subtitle','Kelola semua supplier Awanna')

@section('content')

{{-- Toolbar --}}
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;justify-content:space-between;margin-bottom:18px;" data-reveal>
    <div style="display:flex;flex-wrap:wrap;gap:8px;flex:1;min-width:0;">
        <input type="text" id="search-input" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, kota..." class="clay-input" style="flex:1;min-width:160px;max-width:300px;">
        <select id="status-filter" name="status" class="clay-input" style="width:auto;min-width:120px;">
            <option value="">Semua Status</option>
            <option value="aktif"    {{ request('status')==='aktif'    ?'selected':'' }}>Aktif</option>
            <option value="nonaktif" {{ request('status')==='nonaktif' ?'selected':'' }}>Nonaktif</option>
        </select>
    </div>
    <a href="{{ route('supplier.create') }}" class="clay-btn clay-btn-primary" data-page-link>＋ Tambah</a>
</div>

{{-- Total --}}
<div id="supplier-count" style="font-size:.78rem;color:#9ca3af;margin-bottom:12px;">
    Menampilkan {{ $suppliers->total() }} supplier
</div>

{{-- Tabel --}}
<div class="clay-card" style="overflow:hidden;" data-reveal>
    <div class="table-scroll" id="supplier-table-wrap">
        @include('supplier._table')
    </div>
    <div id="supplier-pagination" style="padding:14px 18px;border-top:1px solid rgba(0,0,0,.05);">
        @if($suppliers->hasPages())
            {{ $suppliers->links() }}
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    var searchInput = document.getElementById('search-input');
    var statusFilter = document.getElementById('status-filter');
    var tableWrap = document.getElementById('supplier-table-wrap');
    var paginationWrap = document.getElementById('supplier-pagination');
    var countEl = document.getElementById('supplier-count');
    var filterUrl = '{{ route("supplier.filter") }}';
    var debounceTimer = null;

    function fetchFiltered() {
        var search = searchInput.value.trim();
        var status = statusFilter.value;
        var params = new URLSearchParams();
        if (search) params.set('search', search);
        if (status) params.set('status', status);
        params.set('page', '1'); // reset to page 1 on filter change

        var url = filterUrl + '?' + params.toString();

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            tableWrap.innerHTML = data.html;
            paginationWrap.innerHTML = data.pagination || '';
            countEl.textContent = 'Menampilkan ' + data.total + ' supplier';

            // Re-bind pagination links for AJAX
            paginationWrap.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchPage(this.href);
                });
            });
        })
        .catch(function(err) {
            console.error('Filter error:', err);
        });
    }

    function fetchPage(url) {
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            tableWrap.innerHTML = data.html;
            paginationWrap.innerHTML = data.pagination || '';
            countEl.textContent = 'Menampilkan ' + data.total + ' supplier';

            // Re-bind pagination links
            paginationWrap.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchPage(this.href);
                });
            });

            // Scroll to top of table
            tableWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(function(err) {
            console.error('Page fetch error:', err);
        });
    }

    // Debounced search (300ms delay)
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchFiltered, 300);
    });

    // Immediate filter on status change
    statusFilter.addEventListener('change', fetchFiltered);

    // Bind existing pagination links on page load
    paginationWrap.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            fetchPage(this.href);
        });
    });
})();
</script>
@endpush
