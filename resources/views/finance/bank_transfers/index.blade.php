@extends('layouts.app')

@section('title', 'Bukti Transfer')
@section('page-title', '🧾 Bukti Transfer')
@section('page-subtitle', 'Transaksi masuk/keluar per akun — CS upload bukti, keuangan acc & verifikasi')

@push('styles')
<style>
    .bt-info { font-size: .75rem; color: #4b5563; line-height: 1.6; }
    .bt-info b { color: #1e1b2e; }
    .bt-info code {
        background: #f3f4f6; padding: 1px 6px; border-radius: 5px;
        font-size: .7rem; color: #6d28d9; font-weight: 700;
    }

    .bt-grid { display: grid; grid-template-columns: 1fr; gap: 16px; align-items: start; }
    .bt-grid .bt-form-fields { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0 14px; }
    @media (max-width: 767px) { .bt-grid .bt-form-fields { grid-template-columns: 1fr; } }
    .bt-grid .bt-form-fields .bt-field-wide { grid-column: 1 / -1; }

    .bt-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .bt-form .bt-field { margin-bottom: 12px; }
    .bt-form .clay-input, .bt-form select { width: 100%; font-size: .8rem; }
    .bt-form textarea.clay-input { resize: vertical; }

    .bt-badge { font-size: .68rem; font-weight: 700; padding: 2px 9px; border-radius: 999px; white-space: nowrap; }
    .bt-badge-in  { background: #dcfce7; color: #15803d; }
    .bt-badge-out { background: #fee2e2; color: #b91c1c; }
    .bt-badge-pending  { background: #fef3c7; color: #b45309; }
    .bt-badge-confirmed { background: #e0e7ff; color: #4338ca; }
    .bt-badge-approved { background: #d1fae5; color: #065f46; }
    .bt-badge-rejected { background: #fee2e2; color: #b91c1c; }

    .bt-img {
        display: inline-block; width: 44px; height: 44px; object-fit: cover;
        border-radius: 10px; border: 1.5px solid #e5e7eb; cursor: zoom-in;
    }
    .bt-reject-note {
        font-size: .7rem; color: #b91c1c; background: #fef2f2;
        border: 1px solid #fecaca; border-radius: 8px; padding: 5px 9px;
        margin-top: 5px; max-width: 260px; line-height: 1.45;
    }

    .bt-desc-click {
        font-size: .72rem; color: #4b5563; cursor: pointer;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        border-bottom: 1px dashed #cbd5e1; padding-bottom: 1px;
    }
    .bt-desc-click:hover { color: var(--color-primary, #d97706); border-bottom-color: currentColor; }
    .bt-desc-click::after { content: ' 🔍'; font-size: .6rem; }

    .bt-act-btn {
        border: none; border-radius: 8px; padding: 4px 11px;
        font-size: .68rem; font-weight: 700; cursor: pointer; font-family: inherit;
        transition: all .15s ease;
    }
    .bt-act-approve { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
    .bt-act-reject  { background: #fee2e2; color: #b91c1c; border: 1.5px solid #fca5a5; }
    .bt-act-confirm { background: #e0e7ff; color: #4338ca; border: 1.5px solid #a5b4fc; }
    .bt-act-delete-img { background: #fef3c7; color: #92400e; border: 1.5px solid #fcd34d; }
    .bt-act-approve:hover, .bt-act-reject:hover, .bt-act-confirm:hover, .bt-act-delete-img:hover { transform: translateY(-1px); box-shadow: 0 3px 0 rgba(0,0,0,.08); }

    .bt-del-form { display: inline; }
    .bt-del-btn { background: none; border: none; color: #dc2626; font-weight: 700; font-size: .72rem; cursor: pointer; padding: 2px 6px; }
    .bt-del-btn:hover { text-decoration: underline; }

    .bt-filter-bar {
        display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
        padding: 12px 18px; border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .bt-filter-bar select, .bt-filter-bar .clay-btn { font-size: .75rem; padding: 6px 10px; }
    .bt-filter-bar select { border: 1.5px solid #e5e7eb; border-radius: 10px; background: #fff; color: #374151; }

    /* Modal reject (pola cr-modal) */
    .bt-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .bt-modal.active { display: flex; }
    .bt-modal .bt-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(2px); }
    .bt-modal .bt-container {
        position: relative; background: #fff; border-radius: 18px;
        width: 100%; max-width: 760px; max-height: 92vh;
        display: flex; flex-direction: column;
        box-shadow: 0 25px 60px rgba(0,0,0,.25);
        animation: btIn .22s ease;
    }
    @keyframes btIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .bt-modal .bt-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        background: linear-gradient(135deg, #FFF5F5, #fff);
    }
    .bt-modal .bt-header h2 { margin: 0; font-size: 1rem; font-weight: 800; color: #1e1b2e; }
    .bt-modal .bt-close { background: #f3f4f6; border: none; border-radius: 8px; width: 30px; height: 30px; font-size: .85rem; cursor: pointer; color: #6b7280; }
    .bt-modal .bt-body { padding: 16px 20px; overflow-y: auto; }
    .bt-modal .bt-footer {
        display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;
        padding: 14px 20px; border-top: 1px solid rgba(0,0,0,.06);
    }

    @media (max-width: 479px) {
        .bt-table-wrap { overflow-x: auto; }
        .bt-table-wrap .clay-table { min-width: 760px; }
    }

    /* Kartu statistik */
    .bt-stats {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px; margin-bottom: 16px;
    }
    @media (max-width: 767px) { .bt-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .bt-stat {
        display: flex; align-items: center; gap: 12px;
        background: #fff; border: 1.5px solid #f0e9e4; border-radius: 16px;
        padding: 14px 16px; text-decoration: none; transition: all .15s ease;
    }
    .bt-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.06); }
    .bt-stat-ic {
        width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
    }
    .bt-stat b { display: block; font-size: 1rem; color: #1e1b2e; line-height: 1.2; }
    .bt-stat small { font-size: .66rem; color: #9ca3af; font-weight: 600; }

    .bt-amount-in { color: #15803d; }
    .bt-amount-out { color: #b91c1c; }

    .bt-acct-type {
        display: inline-block; font-size: .6rem; font-weight: 700;
        background: #f3f4f6; color: #6b7280; border-radius: 999px;
        padding: 1px 8px; margin-top: 3px; text-transform: uppercase; letter-spacing: .04em;
    }

    .bt-amount-wrap { position: relative; }
    .bt-amount-wrap .clay-input { padding-left: 40px; }
    .bt-amount-prefix {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        font-size: .8rem; font-weight: 700; color: #9ca3af; pointer-events: none;
    }

    #bt-preview {
        display: none; margin-top: 10px; position: relative; width: fit-content;
    }
    #bt-preview img {
        max-width: 220px; max-height: 150px; object-fit: cover;
        border-radius: 12px; border: 1.5px solid #e5e7eb; display: block;
    }
    #bt-preview button {
        position: absolute; top: -8px; right: -8px; width: 26px; height: 26px;
        border-radius: 999px; border: none; background: #dc2626; color: #fff;
        font-size: .75rem; cursor: pointer; font-weight: 800;
        box-shadow: 0 2px 6px rgba(220,38,38,.4);
    }

    /* Toggle form (details/summary) */
    .bt-form-toggle summary {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        cursor: pointer; list-style: none; user-select: none;
    }
    .bt-form-toggle summary::-webkit-details-marker { display: none; }
    .bt-form-toggle .bt-form-chev {
        font-size: .85rem; color: #9ca3af; transition: transform .2s ease;
        background: #f3f4f6; border-radius: 999px; width: 30px; height: 30px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .bt-form-toggle[open] .bt-form-chev { transform: rotate(180deg); }
    .bt-form-toggle .bt-form-inner { margin-top: 16px; padding-top: 16px; border-top: 1px dashed #e5e7eb; }

    /* Alur singkat (step chips) */
    .bt-alur { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 12px 18px; margin-bottom: 16px; }
    .bt-step {
        display: inline-flex; align-items: center; gap: 6px;
        background: #fff; border: 1.5px solid #f0e9e4; border-radius: 999px;
        padding: 5px 13px; font-size: .72rem; font-weight: 600; color: #4b5563;
    }
    .bt-step-arrow { color: #d1d5db; font-size: .8rem; font-weight: 700; }

    /* Toggle tipe masuk/keluar */
    .bt-type-switch {
        display: flex; background: #f3f4f6; border-radius: 12px; padding: 4px; gap: 4px;
    }
    .bt-type-btn {
        flex: 1; border: none; border-radius: 9px; padding: 8px 10px;
        font-size: .78rem; font-weight: 700; cursor: pointer; font-family: inherit;
        background: transparent; color: #6b7280; transition: all .15s ease;
    }
    .bt-type-btn.active { background: #fff; color: #1e1b2e; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
    .bt-type-btn[data-value="in"].active { color: #15803d; }
    .bt-type-btn[data-value="out"].active { color: #b91c1c; }

    /* Tab status (pill) */
    .bt-tabs {
        display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
        padding: 12px 18px 0; 
    }
    .bt-tab {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 13px; border-radius: 999px; font-size: .72rem; font-weight: 700;
        color: #6b7280; background: #f3f4f6; text-decoration: none; transition: all .15s ease;
        border: 1.5px solid transparent;
    }
    .bt-tab:hover { color: #1e1b2e; }
    .bt-tab.active { background: #fff; color: #1e1b2e; border-color: #e5e7eb; box-shadow: 0 2px 6px rgba(0,0,0,.05); }
    .bt-tab span {
        font-size: .62rem; background: rgba(0,0,0,.06); border-radius: 999px;
        padding: 1px 7px; font-weight: 800;
    }
    .bt-tab[data-status="pending"].active span { background: #fef3c7; color: #b45309; }
    .bt-tab[data-status="confirmed"].active span { background: #e0e7ff; color: #4338ca; }
    .bt-tab[data-status="approved"].active span { background: #d1fae5; color: #065f46; }
    .bt-tab[data-status="rejected"].active span { background: #fee2e2; color: #b91c1c; }
</style>
@endpush

@section('content')

{{-- Info alur --}}
<div class="clay-card bt-alur" style="background:linear-gradient(135deg,#FFF7F7,#fff);" data-reveal>
    @if($isApprover)
        <span class="bt-step" style="border:none;background:transparent;font-weight:800;color:#1e1b2e;">💡 Alur:</span>
        <span class="bt-step">1️⃣ CS upload bukti</span><span class="bt-step-arrow">→</span>
        <span class="bt-step">🏦 Pemilik bank tandai masuk</span><span class="bt-step-arrow">→</span>
        <span class="bt-step">✅ Guru setujui = saldo berubah</span>
    @else
        <span class="bt-step" style="border:none;background:transparent;font-weight:800;color:#1e1b2e;">💡 Alur:</span>
        <span class="bt-step">1️⃣ Upload bukti + keterangan</span><span class="bt-step-arrow">→</span>
        <span class="bt-step">🏦 Tunggu pemilik bank tandai</span><span class="bt-step-arrow">→</span>
        <span class="bt-step">✅ Disetujui guru = saldo masuk</span>
    @endif
</div>

<div class="bt-stats" data-reveal>
    <a class="bt-stat" href="{{ route('finance.bank-transfers.index', request()->except('page')) }}">
        <span class="bt-stat-ic" style="background:#eef2ff;color:#4338ca;">🧾</span>
        <div><b>{{ number_format((int) $stats->total, 0, ',', '.') }}</b><small>Transaksi</small></div>
    </a>
    <a class="bt-stat" href="{{ route('finance.bank-transfers.index', array_merge(request()->except('page'), ['type' => 'in'])) }}">
        <span class="bt-stat-ic" style="background:#dcfce7;color:#15803d;">💰</span>
        <div><b>Rp {{ number_format((float) $stats->masuk, 0, ',', '.') }}</b><small>Total Masuk</small></div>
    </a>
    <a class="bt-stat" href="{{ route('finance.bank-transfers.index', array_merge(request()->except('page'), ['type' => 'out'])) }}">
        <span class="bt-stat-ic" style="background:#fee2e2;color:#b91c1c;">💸</span>
        <div><b>Rp {{ number_format((float) $stats->keluar, 0, ',', '.') }}</b><small>Total Keluar</small></div>
    </a>
    <a class="bt-stat" href="{{ route('finance.bank-transfers.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}">
        <span class="bt-stat-ic" style="background:#fef3c7;color:#b45309;">⏳</span>
        <div><b>{{ number_format((int) $stats->pending, 0, ',', '.') }}</b><small>Menunggu Persetujuan</small></div>
    </a>
</div>

<div class="bt-grid">

    {{-- ── Form Upload ─────────────────────────────────────────── --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <details class="bt-form-toggle" {{ $isApprover ? '' : 'open' }}>
            <summary>
                <div>
                    <div style="font-size:1rem;font-weight:800;color:#1e1b2e;">
                        {{ $isApprover ? '➕ Catat Transaksi' : '📤 Upload Bukti Transfer' }}
                    </div>
                    <div style="font-size:.7rem;color:#9ca3af;margin-top:2px;">
                        @if($isApprover)
                            Masuk: dicatat langsung (saldo bertambah). Keluar: biaya/pengeluaran. Klik untuk buka form.
                        @else
                            Upload bukti pembayaran pembeli → menunggu persetujuan keuangan.
                        @endif
                    </div>
                </div>
                <span class="bt-form-chev">▾</span>
            </summary>
            <div class="bt-form-inner">

        <form method="POST" action="{{ route('finance.bank-transfers.store') }}" class="bt-form" enctype="multipart/form-data">
            @csrf

            <div class="bt-form-fields">
            <div class="bt-field">
                <label>Akun (rekening tujuan) *</label>
                <select name="account_id" class="clay-input" required>
                    <option value="">— pilih akun —</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->name }}{{ $isApprover ? ' — saldo Rp '.number_format((float) $account->current_balance, 0, ',', '.') : '' }}
                        </option>
                    @endforeach
                </select>
                @if($accounts->isEmpty())
                    <div style="font-size:.66rem;color:#dc2626;margin-top:3px;">Belum ada akun aktif — hubungi keuangan untuk membuat akun.</div>
                @endif
            </div>

            @if($isApprover)
            <div class="bt-field">
                <label>Tipe *</label>
                <div class="bt-type-switch" id="bt-type-switch">
                    <button type="button" data-value="in" class="bt-type-btn {{ old('type') === 'out' ? '' : 'active' }}">💰 Masuk</button>
                    <button type="button" data-value="out" class="bt-type-btn {{ old('type') === 'out' ? 'active' : '' }}">💸 Keluar</button>
                </div>
                <input type="hidden" name="type" id="bt-type" value="{{ old('type', 'in') }}">
            </div>
            @else
            <input type="hidden" name="type" id="bt-type" value="in">
            @endif

            <div class="bt-field">
                <label>Kategori *</label>
                <select name="category_id" id="bt-category" class="clay-input" required>
                    <option value="">— pilih kategori —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" data-type="{{ $category->type }}"
                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="bt-field">
                <label>Produk <span style="color:#9ca3af;font-weight:600;">(opsional)</span></label>
                <select name="product_id" class="clay-input">
                    <option value="">— pilih produk —</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->code }} — {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                <div style="font-size:.66rem;color:#9ca3af;margin-top:3px;">Produk pesanan (daftar dari master Produk).</div>
            </div>

            <div class="bt-field">
                <label>ID Order Online <span style="color:#9ca3af;font-weight:600;">(opsional)</span></label>
                <input type="text" name="order_online_id" class="clay-input" list="bt-order-ids" maxlength="100"
                       value="{{ old('order_online_id') }}"
                       placeholder="mis. CBC-101 — ketik atau pilih dari daftar">
                <datalist id="bt-order-ids">
                    @foreach($orderIds as $oid)
                        <option value="{{ $oid }}"></option>
                    @endforeach
                </datalist>
                <div style="font-size:.66rem;color:#9ca3af;margin-top:3px;">ID order dari Data Mentah (ketik bebas atau pilih saran).</div>
            </div>

            <div class="bt-field">
                <label>Jumlah (Rp) *</label>
                <div class="bt-amount-wrap">
                    <span class="bt-amount-prefix">Rp</span>
                    <input type="number" name="amount" class="clay-input" step="0.01" min="0.01"
                           placeholder="mis. 500000" value="{{ old('amount') }}" required>
                </div>
            </div>

            <div class="bt-field">
                <label>Tanggal Transaksi *</label>
                <input type="date" name="transaction_date" class="clay-input"
                       value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
            </div>

            <div class="bt-field bt-field-wide">
                <label>Keterangan</label>
                <textarea name="description" class="clay-input" rows="4" maxlength="5000"
                          placeholder="Tempel template chat konfirmasi pesanan (rincian produk, alamat, rekening tujuan) — klik keterangan/bukti untuk lihat detail">{{ old('description') }}</textarea>
            </div>

            <div class="bt-field bt-field-wide" id="bt-image-field" style="{{ old('type') === 'out' && $isApprover ? 'display:none;' : '' }}">
                <label>Bukti Transfer (gambar) <span id="bt-image-req" style="color:#dc2626;">*</span></label>
                <input type="file" name="image" id="bt-image" class="clay-input" accept="image/jpeg,image/png,image/webp"
                       {{ (! $isApprover || old('type') !== 'out') ? 'required' : '' }}>
                <div id="bt-preview">
                    <img id="bt-preview-img" src="" alt="pratinjau bukti">
                    <button type="button" onclick="clearBtImage()" title="Hapus gambar">✕</button>
                </div>
                <div style="font-size:.66rem;color:#9ca3af;margin-top:3px;">JPG/PNG/WebP, maks 2MB.</div>
            </div>
            </div>

            <button type="submit" class="clay-btn clay-btn-primary" style="width:100%;">
                {{ $isApprover ? '💾 Catat Transaksi' : '📤 Kirim Bukti' }}
            </button>
        </form>

            </div>
        </details>
    </div>

    {{-- ── Daftar Transaksi ────────────────────────────────────── --}}
    <div class="clay-card" style="overflow:hidden;" data-reveal>
        <div style="padding:12px 18px;font-weight:800;font-size:.9rem;border-bottom:1px solid rgba(0,0,0,.06);
                    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span>🗂 Daftar Transaksi <span style="color:#9ca3af;font-weight:600;font-size:.75rem;">({{ $transfers->total() }})</span></span>
        </div>

        <div class="bt-tabs">
            <a class="bt-tab {{ ! request('status') ? 'active' : '' }}"
               href="{{ route('finance.bank-transfers.index', request()->except(['page', 'status'])) }}">Semua <span>{{ $stats->total }}</span></a>
            @foreach(\App\Models\BankTransfer::STATUS_LABELS as $key => $label)
                <a class="bt-tab {{ request('status') === $key ? 'active' : '' }}" data-status="{{ $key }}"
                   href="{{ route('finance.bank-transfers.index', array_merge(request()->except(['page', 'status']), ['status' => $key])) }}">{{ $label }} <span>{{ $statusCounts[$key] ?? 0 }}</span></a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('finance.bank-transfers.index') }}" class="bt-filter-bar">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="🔍 Cari keterangan, ID order, akun, produk, CS..."
                   style="flex:1;min-width:200px;border:1.5px solid #e5e7eb;border-radius:10px;padding:6px 10px;font-size:.75rem;font-family:inherit;">
            <select name="account_id" onchange="this.form.submit()">
                <option value="">Semua Akun</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                @endforeach
            </select>
            <select name="product_id" onchange="this.form.submit()">
                <option value="">Semua Produk</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->code }} — {{ $product->name }}</option>
                @endforeach
            </select>
            <select name="type" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Masuk</option>
                <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Keluar</option>
            </select>
            @if(request()->hasAny(['status', 'account_id', 'product_id', 'type']))
                <a href="{{ route('finance.bank-transfers.index') }}" style="font-size:.72rem;color:#6b7280;">✕ reset</a>
            @endif
        </form>

        <div class="bt-table-wrap">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Akun</th>
                        <th>Kategori</th>
                        <th>Produk</th>
                        <th>ID Order</th>
                        <th>Tipe</th>
                        <th style="text-align:right;">Jumlah</th>
                        <th>Keterangan</th>
                        <th>Bukti</th>
                        <th>Status</th>
                        <th style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $bt)
                    <tr>
                        <td style="white-space:nowrap;font-size:.75rem;">{{ $bt->transaction_date->format('d M Y H:i') }}</td>
                        <td>
                            <b style="font-size:.78rem;">{{ $bt->account?->name ?? '—' }}</b>
                            @if($bt->account)
                                <div><span class="bt-acct-type">{{ $bt->account->type_label }}</span></div>
                            @endif
                        </td>
                        <td style="font-size:.75rem;color:#4b5563;">{{ $bt->category?->name ?? '—' }}</td>
                        <td style="font-size:.72rem;">
                            @if($bt->product)
                                <b>{{ $bt->product->code }}</b><br><span style="color:#6b7280;">{{ $bt->product->name }}</span>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td style="font-size:.75rem;">
                            @if($bt->order_online_id)
                                <a href="{{ route('orders.index') }}" style="color:#2563eb;text-decoration:none;" title="Buka Data Mentah">{{ $bt->order_online_id }}</a>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td><span class="clay-badge bt-badge bt-badge-{{ $bt->type }}">{{ $bt->type === 'in' ? 'Masuk' : 'Keluar' }}</span></td>
                        <td style="text-align:right;font-weight:800;white-space:nowrap;font-size:.82rem;" class="{{ $bt->type === 'in' ? 'bt-amount-in' : 'bt-amount-out' }}">
                            {{ $bt->type === 'in' ? '+' : '−' }} Rp {{ number_format((float) $bt->amount, 0, ',', '.') }}
                        </td>
                        <td style="max-width:180px;">
                            @if($isApprover)
                            <div class="bt-desc-click" onclick="openBtDetail(this)"
                                 data-img="{{ $bt->image_url ? asset('storage/'.$bt->image_url) : '' }}"
                                 data-desc="{{ rawurlencode($bt->description ?? '') }}"
                                 data-dl="{{ $bt->image_url ? route('finance.bank-transfers.download', $bt) : '' }}"
                                 title="Klik untuk lihat foto & keterangan lengkap">
                                {{ $bt->description ?: '—' }}
                            </div>
                            @else
                            <div style="font-size:.72rem;color:#4b5563;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $bt->description }}">
                                {{ $bt->description ?: '—' }}
                            </div>
                            @endif
                            @if($bt->creator)
                                <div style="font-size:.64rem;color:#9ca3af;">oleh {{ $bt->creator->display_name }}</div>
                            @endif
                        </td>
                        <td>
                            @if($bt->image_url)
                                @if($isApprover)
                                <a href="javascript:void(0)" onclick="openBtDetail(this)"
                                   data-img="{{ asset('storage/'.$bt->image_url) }}"
                                   data-desc="{{ rawurlencode($bt->description ?? '') }}"
                                   data-dl="{{ route('finance.bank-transfers.download', $bt) }}"
                                   title="Klik untuk lihat detail">
                                    <img src="{{ asset('storage/'.$bt->image_url) }}" class="bt-img" alt="bukti">
                                </a>
                                @else
                                <img src="{{ asset('storage/'.$bt->image_url) }}" class="bt-img" alt="bukti" title="{{ $bt->description }}">
                                @endif
                            @else
                                <span style="font-size:.7rem;color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="clay-badge bt-badge bt-badge-{{ $bt->status }}">
                                {{ $bt->status_label }}
                            </span>
                            @if($bt->isRejected() && $bt->rejection_note)
                                <div class="bt-reject-note">⚠️ {{ $bt->rejection_note }}</div>
                            @endif
                        </td>
                        <td>
                            @if($isApprover)
                                @if($bt->isPending() || $bt->isConfirmed())
                                    {{-- Tolak: bisa dari pending atau confirmed --}}
                                    <button type="button" class="bt-act-btn bt-act-reject" id="bt-rej-{{ $bt->id }}"
                                            onclick="openBtReject({{ $bt->id }})">✕ Tolak</button>
                                @endif
                                @if($bt->isConfirmed())
                                    {{-- Setujui: hanya dari confirmed --}}
                                    <button type="button" class="bt-act-btn bt-act-approve"
                                            onclick="submitBt('{{ route('finance.bank-transfers.approve', $bt) }}', 'Setujui transfer Rp {{ number_format((float) $bt->amount, 0, ',', '.') }}?')">
                                        ✓ Setujui
                                    </button>
                                @endif
                                @if($bt->isApproved() && $bt->image_url)
                                    {{-- Hapus Gambar: hanya dari approved + gambar ada --}}
                                    <button type="button" class="bt-act-btn bt-act-delete-img"
                                            onclick="submitBt('{{ route('finance.bank-transfers.delete-image', $bt) }}', 'Hapus gambar bukti transfer? Gambar akan dihapus permanen.')">
                                        🗑 Hapus Gambar
                                    </button>
                                @endif
                                <form method="POST" action="{{ route('finance.bank-transfers.destroy', $bt) }}" class="bt-del-form"
                                      data-confirm="Hapus transaksi Rp {{ number_format((float) $bt->amount, 0, ',', '.') }}? Saldo akun akan dikembalikan.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bt-del-btn">🗑 Hapus</button>
                                </form>
                            @elseif(in_array($bt->account_id, $ownedAccountIds) && $bt->isPending())
                                {{-- Pemilik bank: tandai sudah masuk --}}
                                <button type="button" class="bt-act-btn bt-act-confirm"
                                        onclick="submitBt('{{ route('finance.bank-transfers.confirm', $bt) }}', 'Tandai bukti transfer Rp {{ number_format((float) $bt->amount, 0, ',', '.') }} sudah masuk ke rekening {{ $bt->account->name }}?')">
                                    ✓ Tandai Sudah Masuk
                                </button>
                            @else
                                <span style="font-size:.68rem;color:#9ca3af;">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" style="text-align:center;padding:56px 24px;color:#9ca3af;">
                            <div style="font-size:2rem;margin-bottom:10px;">📭</div>
            @if(request()->hasAny(['status', 'account_id', 'product_id', 'type', 'search']))
                                Tidak ada transaksi dengan filter/pencarian ini.
                            @else
                                Belum ada transaksi. {{ $isApprover ? 'Catat transaksi di form sebelah kiri.' : 'Upload bukti transfer di form sebelah kiri.' }}
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
            <div style="padding:12px 18px;">{{ $transfers->links() }}</div>
        @endif
    </div>
</div>

{{-- ── Modal Reject ─────────────────────────────────────────────── --}}
<div class="bt-modal" id="bt-modal" role="dialog" aria-modal="true" aria-labelledby="bt-modal-title">
    <div class="bt-backdrop" onclick="closeBtReject()"></div>
    <div class="bt-container">
        <div class="bt-header">
            <h2 id="bt-modal-title">✕ Tolak Bukti Transfer</h2>
            <button class="bt-close" onclick="closeBtReject()" type="button">✕</button>
        </div>
        <form method="POST" id="bt-reject-form">
            @csrf
            <div class="bt-body">
                <div class="bt-form">
                    <div class="bt-field">
                        <label>Alasan Penolakan (feedback ke CS) *</label>
                        <textarea name="rejection_note" class="clay-input" rows="3" maxlength="500" required
                                  placeholder="mis. nominal tidak sesuai dengan bukti, silakan cek ulang"></textarea>
                        <div style="font-size:.66rem;color:#9ca3af;margin-top:3px;">
                            Catatan ini langsung terkirim ke CS yang meng-upload sebagai notifikasi.
                        </div>
                    </div>
                </div>
            </div>
            <div class="bt-footer">
                <button type="button" class="clay-btn clay-btn-outline" onclick="closeBtReject()">Batal</button>
                <button type="submit" class="clay-btn clay-btn-primary">✕ Tolak & Kirim Feedback</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal Detail Transaksi (foto + keterangan + download) ─────── --}}
<div class="bt-modal" id="btd-modal" role="dialog" aria-modal="true" aria-labelledby="btd-modal-title">
    <div class="bt-backdrop" onclick="closeBtDetail()"></div>
    <div class="bt-container">
        <div class="bt-header">
            <h2 id="btd-modal-title">📄 Detail Transaksi</h2>
            <button class="bt-close" onclick="closeBtDetail()" type="button">✕</button>
        </div>
        <div class="bt-body">
            <div style="display:flex;gap:16px;align-items:stretch;flex-wrap:wrap;">
                <div id="btd-img-wrap" style="display:none;flex:1 1 220px;min-width:0;text-align:center;background:#f3f4f6;border-radius:14px;padding:10px;">
                    <img id="btd-img" src="" alt="bukti transfer" style="max-width:100%;max-height:62vh;object-fit:contain;border-radius:10px;">
                </div>
                <div style="flex:1 1 220px;min-width:0;display:flex;flex-direction:column;">
                    <div style="font-size:.66rem;font-weight:800;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;">Keterangan</div>
                    <div id="btd-text" style="font-size:.8rem;color:#374151;white-space:pre-wrap;word-break:break-word;line-height:1.6;max-height:62vh;overflow-y:auto;background:#f9fafb;border-radius:12px;padding:12px;flex:1;">—</div>
                </div>
            </div>
        </div>
        <div class="bt-footer">
            <button type="button" class="clay-btn clay-btn-outline" id="btd-copy-buyer" onclick="copyBtBuyer(this)">👤 Salin Nama Buyer</button>
            <button type="button" class="clay-btn clay-btn-outline" id="btd-copy" onclick="copyBtDesc(this)">📋 Salin Keterangan</button>
            <a id="btd-download" class="clay-btn clay-btn-primary" download style="display:none;text-decoration:none;color:#fff;">⬇ Download Bukti</a>
            <button type="button" class="clay-btn clay-btn-outline" onclick="closeBtDetail()">Tutup</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var rejectModal = document.getElementById('bt-modal');
    var rejectForm = document.getElementById('bt-reject-form');
    var rejectUrl = '{{ route('finance.bank-transfers.reject', ['bankTransfer' => '__ID__']) }}';

    window.openBtReject = function (id) {
        rejectForm.action = rejectUrl.replace('__ID__', id);
        rejectModal.classList.add('active');
    };

    window.closeBtReject = function () {
        rejectModal.classList.remove('active');
    };

    window.submitBt = function (url, message) {
        if (!window.confirm(message)) return;
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (rejectModal.classList.contains('active')) window.closeBtReject();
            if (btdModal.classList.contains('active')) window.closeBtDetail();
        }
    });

    /* ── Modal Detail Transaksi (approver: klik keterangan/bukti) ── */
    var btdModal = document.getElementById('btd-modal');
    var btdImgWrap = document.getElementById('btd-img-wrap');
    var btdImg = document.getElementById('btd-img');
    var btdText = document.getElementById('btd-text');
    var btdDownload = document.getElementById('btd-download');

    window.openBtDetail = function (el) {
        var img = el.dataset.img || '';
        btdImgWrap.style.display = img ? '' : 'none';
        btdImg.src = img;
        btdText.textContent = el.dataset.desc ? decodeURIComponent(el.dataset.desc) : '—';
        if (img) {
            btdDownload.href = el.dataset.dl || img;
            btdDownload.style.display = 'inline-flex';
        } else {
            btdDownload.style.display = 'none';
        }
        btdModal.classList.add('active');
    };

    window.closeBtDetail = function () {
        btdModal.classList.remove('active');
    };

    window.copyBtDesc = function (btn) {
        var text = btdText.textContent;
        if (!text || text === '—') return;

        function done() {
            var old = btn.textContent;
            btn.textContent = '✓ Tersalin';
            setTimeout(function () { btn.textContent = old; }, 1500);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            done();
        }
    };

    window.copyBtBuyer = function (btn) {
        var text = btdText.textContent;
        var match = text ? text.match(/Nama\s*:\s*([^\r\n]+)/i) : null;
        var buyer = match ? match[1].trim() : '';
        if (!buyer) {
            btn.textContent = 'Tidak ada nama buyer';
            setTimeout(function () { btn.textContent = '👤 Salin Nama Buyer'; }, 1500);
            return;
        }

        function done() {
            var old = btn.textContent;
            btn.textContent = '✓ Tersalin';
            setTimeout(function () { btn.textContent = old; }, 1500);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(buyer).then(done);
        } else {
            var ta = document.createElement('textarea');
            ta.value = buyer;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            done();
        }
    };

    document.querySelectorAll('.bt-del-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.dataset.confirm)) e.preventDefault();
        });
    });

    /* ── Filter kategori mengikuti tipe ── */
    var typeSel = document.getElementById('bt-type');
    var catSel = document.getElementById('bt-category');
    var imageField = document.getElementById('bt-image-field');
    var imageInput = document.getElementById('bt-image');
    var imageReq = document.getElementById('bt-image-req');

    function syncType() {
        var type = typeSel ? typeSel.value : 'in';
        if (imageField) imageField.style.display = type === 'in' ? '' : 'none';
        if (imageInput) imageInput.required = type === 'in';
        if (imageReq) imageReq.style.display = type === 'in' ? '' : 'none';

        if (!catSel) return;
        var selected = catSel.value;
        catSel.querySelectorAll('option').forEach(function (opt) {
            if (!opt.value) return;
            opt.style.display = opt.dataset.type === type ? '' : 'none';
        });
        if (catSel.selectedOptions[0] && catSel.selectedOptions[0].dataset.type !== type) {
            catSel.value = '';
        } else {
            catSel.value = selected;
        }
    }

    var typeSwitch = document.getElementById('bt-type-switch');
    if (typeSwitch) {
        typeSwitch.querySelectorAll('.bt-type-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                typeSel.value = btn.dataset.value;
                typeSwitch.querySelectorAll('.bt-type-btn').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                syncType();
            });
        });
    }
    syncType();

    /* ── Live search: auto-submit saat ketik / kosongkan ── */
    var searchInput = document.querySelector('.bt-filter-bar input[name="search"]');
    if (searchInput) {
        var searchForm = searchInput.closest('form');
        var searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { searchForm.submit(); }, 450);
        });
    }

    /* ── Preview gambar bukti sebelum submit ── */
    var imageInput = document.getElementById('bt-image');
    var previewWrap = document.getElementById('bt-preview');
    var previewImg = document.getElementById('bt-preview-img');

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            var file = imageInput.files[0];
            if (!file) { previewWrap.style.display = 'none'; return; }
            previewImg.src = URL.createObjectURL(file);
            previewWrap.style.display = 'block';
        });
    }

    window.clearBtImage = function () {
        if (imageInput) imageInput.value = '';
        previewImg.src = '';
        previewWrap.style.display = 'none';
    };
})();
</script>
@endpush