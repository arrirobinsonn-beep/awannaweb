<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — webAwanna</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @php $manifestExists = file_exists(public_path('build/manifest.json')); @endphp
    @if($manifestExists)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="{{ asset('css/clay.css') }}">
    @endif

    @stack('styles')

    {{-- ─── Layout & Responsive Styles ──────────────────────────────────── --}}
    <style>
        /* ── Variabel ──────────────────────────────────────── */
        :root {
            --sidebar-w:        256px;
            --sidebar-transition: 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ── Layout wrapper ────────────────────────────────── */
        #layout-wrapper {
            display: flex;
            height: 100vh;
            /* Tidak pakai overflow:hidden agar fixed positioning tidak terkurung */
            background: var(--color-bg-base, #FFF5F5);
        }

        /* ── Backdrop (layar sempit saat sidebar overlay) ───── */
        #sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 40;
            backdrop-filter: blur(2px);
            cursor: pointer;
        }
        #sidebar-backdrop.active { display: block; }

        /* ─────────────────────────────────────────────────────
           SIDEBAR — dua mode: "open" dan "closed"

           Layar lebar  (≥ 1024px):
             • Default = open → sidebar mendorong konten
             • Closed  → sidebar tersembunyi (transform kiri)

           Layar sempit (< 1024px):
             • Default = closed → sidebar fixed di luar viewport
             • Open    → sidebar slide masuk sebagai overlay
        ──────────────────────────────────────────────────────── */
        #sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: #fff;
            border-right: 2px solid rgba(0,0,0,0.05);
            box-shadow: 4px 0 20px rgba(0,0,0,0.05);
            overflow: hidden;
            z-index: 50;
            transition: transform var(--sidebar-transition),
                        width    var(--sidebar-transition),
                        margin   var(--sidebar-transition);
        }

        /* ── LAYAR LEBAR (≥ 1024px) ────────────────────────── */
        @media (min-width: 1024px) {
            #sidebar {
                position: relative;
                transform: translateX(0);
                margin-left: 0;
            }
            #sidebar.sidebar-closed {
                transform: translateX(calc(-1 * var(--sidebar-w)));
                width: 0;
                border-right: none;
                overflow: hidden;
            }
            #sidebar-backdrop { display: none !important; }
        }

        /* ── LAYAR SEMPIT (< 1024px) ───────────────────────── */
        @media (max-width: 1023px) {
            #sidebar {
                position: fixed;
                top: 0; left: 0;
                height: 100%;
                transform: translateX(calc(-1 * var(--sidebar-w))); /* default: tertutup */
            }
            #sidebar.sidebar-open {
                transform: translateX(0); /* terbuka = overlay */
            }
        }

        /* ── Main wrapper ──────────────────────────────────── */
        #main-wrapper {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            transition: margin-left var(--sidebar-transition);
            /* PENTING: jangan set transform/will-change agar fixed positioning tidak terkurung */
        }

        /* ── Topbar ────────────────────────────────────────── */
        #topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(255,255,255,0.93);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        @media (max-width: 640px) {
            #topbar { padding: 10px 14px; }
            #main-content { padding: 14px !important; }
        }

        /* ── Tombol toggle sidebar ─────────────────────────── */
        #sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px; height: 36px;
            border-radius: 12px;
            border: 2px solid rgba(0,0,0,0.08);
            background: #fff;
            cursor: pointer;
            flex-shrink: 0;
            color: #6b7280;
            box-shadow: 0 2px 0 rgba(0,0,0,0.06);
            transition: background .15s, transform .15s;
        }
        #sidebar-toggle:hover { background: #fff5f5; color: var(--color-primary, #FF6B6B); }
        #sidebar-toggle:active { transform: scale(.94); }

        /* Ikon hamburger / panah */
        #sidebar-toggle .icon-open  { display: block; }
        #sidebar-toggle .icon-close { display: none;  }
        body.sidebar-is-open #sidebar-toggle .icon-open  { display: none;  }
        body.sidebar-is-open #sidebar-toggle .icon-close { display: block; }

        /* ── Page content ──────────────────────────────────── */
        #main-content {
            flex: 1;
            padding: 20px 24px;
        }

        /* ── Footer ────────────────────────────────────────── */
        #layout-footer {
            padding: 10px 24px;
            text-align: center;
            font-size: 0.7rem;
            color: #9ca3af;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        /* ── Grid helpers ──────────────────────────────────── */
        .grid-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px; margin-bottom: 24px;
        }
        @media (max-width: 1023px) { .grid-stats { grid-template-columns: repeat(2,1fr); gap:12px; } }
        @media (max-width: 479px)  { .grid-stats { gap: 10px; } }

        .grid-2col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px; margin-bottom: 24px;
        }
        @media (max-width: 767px) { .grid-2col { grid-template-columns: 1fr; gap:12px; } }

        .grid-3col {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px; margin-bottom: 24px;
        }
        @media (max-width: 1023px) { .grid-3col { grid-template-columns: 1fr; } }

        /* ── Tabel ─────────────────────────────────────────── */
        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 767px) {
            .clay-table thead th,
            .clay-table tbody td { padding: 9px 10px; font-size: .78rem; }
        }

        /* ── Form grid ─────────────────────────────────────── */
        .form-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 18px; }
        .form-grid .col-span-2 { grid-column: span 2; }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .col-span-2 { grid-column: span 1; }
        }

        /* ── Stat card mobile ──────────────────────────────── */
        @media (max-width: 479px) { .stat-card { padding: 14px; } }

        /* ── Topbar subtitle + tanggal sembunyikan di xs ───── */
        @media (max-width: 480px) {
            .topbar-subtitle, .topbar-date { display: none; }
        }

        /* ── Elemen tampilan (non-input): tidak bisa di-select & di-drag ──
           Seluruh teks/gambar/ikon yang murni tampilan tidak bisa di-block
           (disorot/di-select) maupun di-drag. Area pengisian data (input,
           textarea, select, contenteditable) tetap normal. Elemen drag & drop
           khusus (mis. palette produk di form spending) tetap berfungsi karena
           memakai draggable="true" pada div, bukan img/a/button. */
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }
        /* Input & area pengisian data tetap bisa di-select/ketik normal */
        input, textarea, select, [contenteditable="true"], [contenteditable=""] {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }
        /* Gambar, ikon, link, tombol & elemen visual lain tidak bisa di-drag */
        img, svg, canvas, video, iframe, a, button, [role="button"] {
            -webkit-user-drag: none;
            user-drag: none;
        }
        /* Escape hatch: nilai yang memang perlu di-copy (resi, nomor, kode) */
        .selectable {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }
    </style>
</head>
<body id="app-body" style="overflow-x:hidden;">

{{-- ─── Backdrop overlay ─────────────────────────────────────────────────── --}}
<div id="sidebar-backdrop" onclick="closeSidebar()"></div>

{{-- ─── Layout wrapper ───────────────────────────────────────────────────── --}}
<div id="layout-wrapper">

    {{-- ─── Sidebar ─────────────────────────────────────────────────────── --}}
    <aside id="sidebar">

        {{-- Logo + Close btn --}}
        <div style="padding: 18px 16px 14px; border-bottom: 1px solid rgba(0,0,0,0.06);
                    display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <a href="{{ route('dashboard') }}" style="display:flex; align-items:center; gap:10px; min-width:0; text-decoration:none;">
                <div style="width:36px; height:36px; flex-shrink:0; border-radius:14px;
                            display:flex; align-items:center; justify-content:center;
                            color:#fff; font-size:1.1rem; font-weight:900;
                            background: linear-gradient(135deg, #FF6B6B, #FF9A9A);
                            box-shadow: 0 4px 0 #e05555;">W</div>
                <div class="sidebar-label" style="min-width:0;">
                    <div style="font-weight:800; font-size:0.95rem; color:#1e1b2e; white-space:nowrap;">webAwanna</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Management System</div>
                </div>
            </a>
            <button id="close-sidebar-btn" onclick="closeSidebar()"
                    style="background:none; border:none; cursor:pointer; color:#9ca3af;
                           font-size:1.1rem; padding:4px; border-radius:8px; line-height:1;
                           display:none;">✕</button>
        </div>

        {{-- User badge (klik → profil) --}}
        <a href="{{ route('profile.show') }}" class="sidebar-label clay-card-sm"
           style="margin:12px 12px 0;padding:10px 12px;display:block;text-decoration:none;
                  background:linear-gradient(135deg,#FFF5F5,#fff);transition:box-shadow .15s;"
           data-page-link>
            <div style="display:flex;align-items:center;gap:8px;">
                <img src="{{ auth()->user()->avatar_url }}" alt="avatar"
                     style="width:32px;height:32px;border-radius:10px;object-fit:cover;
                            border:2px solid #fecaca;flex-shrink:0;">
                <div style="min-width:0;">
                    <div style="font-weight:700;font-size:.83rem;color:#1e1b2e;
                                overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ auth()->user()->display_name }}
                    </div>
                    <div style="font-size:.7rem;color:var(--color-primary,#FF6B6B);">
                        @if(auth()->user()->hasRole('owner'))          👑 Owner
                        @elseif(auth()->user()->hasRole('super_admin')) ⚡ Super Admin
                        @elseif(auth()->user()->hasRole('admin'))       🛡 Admin
                        @elseif(auth()->user()->hasRole('advertiser'))  📢 Advertiser
                        @elseif(auth()->user()->hasRole('mentor'))      🎓 Mentor
                        @elseif(auth()->user()->hasRole('keuangan'))    💰 Keuangan
                        @elseif(auth()->user()->hasRole('cs'))          🎧 CS
                        @else {{ ucfirst(auth()->user()->getRoleNames()->first() ?? 'User') }}
                        @endif
                    </div>
                </div>
                <span style="margin-left:auto;font-size:.7rem;color:#d1d5db;">✏️</span>
            </div>
        </a>

        {{-- Navigation — role-based ──────────────────────────── --}}
        <nav style="flex:1;overflow-y:auto;padding:12px 10px;display:flex;flex-direction:column;gap:2px;">

            @php $u = auth()->user(); @endphp

            {{-- Dashboard — semua role --}}
            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               data-page-link>
                <span class="nav-icon">📊</span>
                <span class="sidebar-label">Dashboard</span>
            </a>

            @if($u->hasRole(['owner','super_admin','mentor','advertiser','cs']))
            <div class="sidebar-label nav-divider" style="padding:14px 10px 4px;">
                <span style="font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;">Iklan</span>
            </div>

            @if($u->hasRole(['owner','super_admin','mentor','advertiser']))
            <a href="{{ route('whitelist.index') }}"
               class="nav-item {{ request()->routeIs('whitelist.*') ? 'active' : '' }}"
               data-page-link data-tip="Whitelist">
                <span class="nav-icon">✅</span>
                <span class="sidebar-label">Whitelist</span>
            </a>
            @endif

            @if($u->hasRole(['owner','super_admin','mentor','advertiser','cs']))
            <a href="{{ route('spending.index') }}"
               class="nav-item {{ request()->routeIs('spending.*') ? 'active' : '' }}"
               data-page-link data-tip="Spending Harian">
                <span class="nav-icon">💸</span>
                <span class="sidebar-label">Spending Harian</span>
                <span id="spending-alarm-badge" style="display:none;margin-left:auto;background:#ef4444;color:#fff;font-size:.55rem;font-weight:800;padding:1px 6px;border-radius:6px;line-height:1.5;">!</span>
            </a>
            @endif

            @if($u->hasRole(['owner','super_admin','mentor','advertiser','cs']))
            <a href="{{ route('regional.index') }}"
               class="nav-item {{ request()->routeIs('regional.*') ? 'active' : '' }}"
               data-page-link data-tip="Detail Kiriman Per Daerah">
                <span class="nav-icon">🗺️</span>
                <span class="sidebar-label">Detail Per Daerah</span>
                <span id="regional-alarm-badge" style="display:none;margin-left:auto;background:#ef4444;color:#fff;font-size:.55rem;font-weight:800;padding:1px 6px;border-radius:6px;line-height:1.5;">!</span>
            </a>
            @endif

            @if($u->hasRole(['owner','super_admin','mentor','admin','cs']))
            <a href="{{ route('orders.index') }}"
               class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}"
               data-page-link data-tip="Upload Data Mentah & Export Template">
                <span class="nav-icon">📥</span>
                <span class="sidebar-label">Data Mentah</span>
            </a>
            @endif

            @if($u->hasRole(['owner','super_admin','mentor','advertiser']))
            <a href="{{ route('topup.index') }}"
               class="nav-item {{ request()->routeIs('topup.*') ? 'active' : '' }}"
               data-page-link data-tip="Top Up">
                <span class="nav-icon">💰</span>
                <span class="sidebar-label">Top Up</span>
            </a>
            @endif

            {{-- ── Tim: Advertiser lihat CS tim-nya / CS lihat info advertiser ── --}}
            @if($u->hasRole(['advertiser', 'cs']))
            <a href="{{ route('team.index') }}"
               class="nav-item {{ request()->routeIs('team.index') ? 'active' : '' }}"
               data-page-link data-tip="Tim Saya">
                <span class="nav-icon">👥</span>
                <span class="sidebar-label">Tim</span>
            </a>
            <a href="{{ route('team.performance') }}"
               class="nav-item {{ request()->routeIs('team.performance') ? 'active' : '' }}"
               data-page-link data-tip="Performa Tim">
                <span class="nav-icon">📊</span>
                <span class="sidebar-label">Performa Tim</span>
            </a>
            @if($u->hasRole('cs'))
            <a href="{{ route('team.phone-list') }}"
               class="nav-item {{ request()->routeIs('team.phone-list') ? 'active' : '' }}"
               data-page-link data-tip="Nomor CS">
                <span class="nav-icon">📞</span>
                <span class="sidebar-label">Nomor CS</span>
            </a>
            @endif
            @endif
            @endif

            {{-- ── Admin & atas: Data Master ────────────────────── --}}
            @if($u->hasRole(['owner','super_admin','mentor','admin']))
            <div class="sidebar-label nav-divider" style="padding:14px 10px 4px;">
                <span style="font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;">Data Master</span>
            </div>

            @if($u->hasRole(['owner','super_admin','mentor','admin']))
            <a href="{{ route('supplier.index') }}"
               class="nav-item {{ request()->routeIs('supplier.*') ? 'active' : '' }}"
               data-page-link data-tip="Supplier">
                <span class="nav-icon">🏭</span>
                <span class="sidebar-label">Supplier</span>
            </a>
            @endif

            <a href="{{ route('inventory.master') }}"
               class="nav-item {{ request()->routeIs('inventory.master*') ? 'active' : '' }}"
               data-page-link data-tip="Inventory">
                <span class="nav-icon">🏭</span>
                <span class="sidebar-label">Master Inventory</span>
            </a>

            <a href="{{ route('product.index') }}"
               class="nav-item {{ request()->routeIs('product.*') ? 'active' : '' }}"
               data-page-link data-tip="Master produk & varian">
                <span class="nav-icon">📦</span>
                <span class="sidebar-label">Produk</span>
            </a>

            <a href="{{ route('courier-rule.index') }}"
               class="nav-item {{ request()->routeIs('courier-rule.*') ? 'active' : '' }}"
               data-page-link data-tip="Aturan Courier (auto-mapping kurir)">
                <span class="nav-icon">🚚</span>
                <span class="sidebar-label">Aturan Courier</span>
            </a>

            <a href="{{ route('warehouse-rule.index') }}"
               class="nav-item {{ request()->routeIs('warehouse-rule.*') ? 'active' : '' }}"
               data-page-link data-tip="Aturan Gudang (kode produk → gudang saat export)">
                <span class="nav-icon">🏬</span>
                <span class="sidebar-label">Aturan Gudang</span>
            </a>

            <a href="{{ route('tracking-status-rule.index') }}"
               class="nav-item {{ request()->routeIs('tracking-status-rule.*') ? 'active' : '' }}"
               data-page-link data-tip="Aturan Status Aggregator (status dashboard → status sistem)">
                <span class="nav-icon">📡</span>
                <span class="sidebar-label">Aturan Status</span>
            </a>

            <a href="{{ route('export-mapping.index') }}"
               class="nav-item {{ request()->routeIs('export-mapping.*') ? 'active' : '' }}"
               data-page-link data-tip="Aturan Export (mapping template CSV)">
                <span class="nav-icon">📋</span>
                <span class="sidebar-label">Aturan Export</span>
            </a>
            @endif

            {{-- ── Admin: Gudang & Kiriman ─────────────────── --}}
            @if($u->hasRole(['owner','super_admin','admin']))
            <div class="sidebar-label nav-divider" style="padding:14px 10px 4px;">
                <span style="font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;">Gudang & Kiriman</span>
            </div>

            <a href="{{ route('gudang.index') }}"
               class="nav-item {{ request()->routeIs('gudang.*') ? 'active' : '' }}"
               data-page-link data-tip="Stok per kategori & aturan kemasan">
                <span class="nav-icon">🏬</span>
                <span class="sidebar-label">Gudang</span>
            </a>

            <a href="{{ route('purchase.index') }}"
               class="nav-item {{ request()->routeIs('purchase.*') ? 'active' : '' }}"
               data-page-link data-tip="Barang Masuk (Pembelian)">
                <span class="nav-icon">📥</span>
                <span class="sidebar-label">Barang Masuk</span>
            </a>

            <a href="{{ route('stock-movement.index') }}"
               class="nav-item {{ request()->routeIs('stock-movement.*') ? 'active' : '' }}"
               data-page-link data-tip="Jurnal Stok (Masuk/Keluar)">
                <span class="nav-icon">📊</span>
                <span class="sidebar-label">Jurnal Stok</span>
            </a>

            <a href="{{ route('orders.index') }}"
               class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}"
               data-page-link data-tip="Upload Data Mentah & Export Template">
                <span class="nav-icon">🚚</span>
                <span class="sidebar-label">Data Mentah</span>
            </a>

            @endif

            {{-- ── Keuangan ──────────────────────────────────────── --}}
            @if($u->hasRole(['owner','super_admin','mentor','keuangan']))
            <div class="sidebar-label nav-divider" style="padding:14px 10px 4px;">
                <span style="font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;">Keuangan</span>
            </div>

            <a href="{{ route('spending.index') }}"
               class="nav-item {{ request()->routeIs('spending.*') ? 'active' : '' }}"
               data-page-link data-tip="Spending Harian">
                <span class="nav-icon">💸</span>
                <span class="sidebar-label">Spending Harian</span>
                <span id="spending-alarm-badge" style="display:none;margin-left:auto;background:#ef4444;color:#fff;font-size:.55rem;font-weight:800;padding:1px 6px;border-radius:6px;line-height:1.5;">!</span>
            </a>

            <a href="{{ route('topup.index') }}"
               class="nav-item {{ request()->routeIs('topup.*') ? 'active' : '' }}"
               data-page-link data-tip="Top Up">
                <span class="nav-icon">💰</span>
                <span class="sidebar-label">Top Up</span>
            </a>
            @endif

            {{-- ── Manajemen (owner, super_admin) ─────────── --}}
            @if($u->hasRole(['owner','super_admin']))
            <div class="sidebar-label nav-divider" style="padding:14px 10px 4px;">
                <span style="font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;">Manajemen</span>
            </div>

            <a href="{{ route('user.index') }}"
               class="nav-item {{ request()->routeIs('user.*') ? 'active' : '' }}"
               data-page-link data-tip="Users & Role">
                <span class="nav-icon">👥</span>
                <span class="sidebar-label">Users & Role</span>
            </a>

            {{-- ── Admin: Mapping Tim CS ──────────────────────── --}}
            @if($u->hasRole(['owner','super_admin','admin']))
            <a href="{{ route('team.admin-index') }}"
               class="nav-item {{ request()->routeIs('team.admin-index') ? 'active' : '' }}"
               data-page-link data-tip="Mapping Tim CS">
                <span class="nav-icon">🗺️</span>
                <span class="sidebar-label">Mapping Tim CS</span>
            </a>
            @endif

            <a href="{{ route('topup.index') }}"
               class="nav-item {{ request()->routeIs('topup.*') ? 'active' : '' }}"
               data-page-link data-tip="Top Up">
                <span class="nav-icon">💰</span>
                <span class="sidebar-label">Persetujuan Top Up</span>
            </a>
            @endif

        </nav>

        {{-- Logout --}}
        <div style="padding: 10px; border-top: 1px solid rgba(0,0,0,0.06); flex-shrink:0;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-item" data-tip="Logout"
                        style="width:100%; background:none; border:none; cursor:pointer;
                               font-family:inherit;">
                    <span class="nav-icon">🚪</span>
                    <span class="sidebar-label">Logout</span>
                </button>
            </form>
        </div>

    </aside>

    {{-- ─── Main content ─────────────────────────────────────────────────── --}}
    <div id="main-wrapper">

        {{-- Topbar --}}
        <header id="topbar">
            <div style="display:flex; align-items:center; gap:10px; min-width:0; flex:1;">

                {{-- ── Toggle sidebar (tampil di semua ukuran layar) ── --}}
                <button id="sidebar-toggle" onclick="toggleSidebar()" title="Toggle Sidebar">
                    {{-- Ikon hamburger (saat sidebar tertutup) --}}
                    <svg class="icon-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    {{-- Ikon panah kiri (saat sidebar terbuka) --}}
                    <svg class="icon-close" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7M18 19l-7-7 7-7"/>
                    </svg>
                </button>

                <div style="min-width:0;">
                    <h1 style="font-weight:800; font-size:1rem; color:#1e1b2e;
                                margin:0; line-height:1.2;
                                overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        @yield('page-title', 'Dashboard')
                    </h1>
                    <div class="topbar-subtitle" style="font-size:0.7rem; color:#9ca3af; margin-top:1px;">
                        @yield('page-subtitle', 'Selamat datang di webAwanna')
                    </div>
                </div>
            </div>

            {{-- Notification Bell --}}
            <a href="{{ route('notifications.index') }}" class="clay-card-sm" data-page-link
               style="padding:6px 10px; font-size:0.85rem; color:#6b7280; white-space:nowrap; flex-shrink:0;
                      text-decoration:none;position:relative;cursor:pointer;
                      display:flex;align-items:center;gap:4px;">
                🔔
                <span id="notif-count-badge" style="display:none;
                      background:var(--color-primary,#FF6B6B);color:#fff;
                      font-size:.6rem;font-weight:800;padding:1px 6px;border-radius:10px;
                      min-width:18px;text-align:center;line-height:1.4;">
                    0
                </span>
            </a>

            <div class="topbar-date clay-card-sm"
                 style="padding:6px 12px; font-size:0.75rem; color:#6b7280; white-space:nowrap; flex-shrink:0;">
                📅 {{ now()->translatedFormat('d M Y') }}
            </div>
        </header>

        {{-- Flash Messages --}}
        <div style="padding: 14px 24px 0;" id="flash-zone">
            @if(session('success'))
                <div class="clay-alert clay-alert-success" style="margin-bottom:12px;" data-flash>
                    <span>✅</span>
                    <span style="flex:1; font-size:0.875rem;">{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()"
                            style="background:none;border:none;cursor:pointer;color:#059669;opacity:0.6;font-size:1rem;">✕</button>
                </div>
            @endif
            @if(session('error') || $errors->has('error'))
                <div class="clay-alert clay-alert-error" style="margin-bottom:12px;" data-flash>
                    <span>❌</span>
                    <span style="flex:1; font-size:0.875rem;">{{ session('error') ?? $errors->first('error') }}</span>
                    <button onclick="this.parentElement.remove()"
                            style="background:none;border:none;cursor:pointer;color:#dc2626;opacity:0.6;font-size:1rem;">✕</button>
                </div>
            @endif
            @if ($errors->any() && !$errors->has('error'))
                <div class="clay-alert clay-alert-error" style="margin-bottom:12px;" data-flash>
                    <span>⚠️</span>
                    <div style="flex:1; font-size:0.875rem;">
                        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                    <button onclick="this.parentElement.remove()"
                            style="background:none;border:none;cursor:pointer;color:#dc2626;opacity:0.6;font-size:1rem;">✕</button>
                </div>
            @endif
        </div>

        {{-- Page Content --}}
        <main id="main-content">
            @yield('content')
        </main>

        <footer id="layout-footer">
            © {{ date('Y') }} webAwanna — All rights reserved.
        </footer>

    </div>
</div>

@stack('scripts')

{{-- Popup portals — di sini agar bebas dari transform/overflow ─ --}}
@stack('body-end')

{{-- ── Date Range Picker — global script ─────────────────────── --}}
<script>
var DRP=(function(){
var ST={};
var MO=["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
var DA=["Sen","Sel","Rab","Kam","Jum","Sab","Min"];
function pad(n){return n<10?"0"+n:""+n;}
function pd(s){if(!s)return null;var p=s.split("-").map(Number);var d=new Date(p[0],p[1]-1,p[2]);d.setHours(0,0,0,0);return d;}
function fi(d){return d.getFullYear()+"-"+pad(d.getMonth()+1)+"-"+pad(d.getDate());}
function fl(s){if(!s)return"";var d=pd(s);return d.getDate()+" "+MO[d.getMonth()].slice(0,3)+" "+d.getFullYear();}
function eq(a,b){return a&&b&&a.getTime()===b.getTime();}
function now0(){var t=new Date();t.setHours(0,0,0,0);return t;}

function injectCSS(){
if(document.getElementById("drp-css"))return;
var s=document.createElement("style");s.id="drp-css";
s.textContent=
".drp-d{width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:8px;font-size:.8rem;cursor:pointer;user-select:none;}"
+".drp-d:hover{background:rgba(255,107,107,.15);color:var(--color-primary,#FF6B6B);}"
+".drp-d.drp-rng{background:rgba(255,107,107,.1)!important;border-radius:0;}"
+".drp-d.drp-s,.drp-d.drp-e{background:var(--color-primary,#FF6B6B)!important;color:#fff!important;font-weight:700;border-radius:8px!important;box-shadow:0 3px 0 #e05555;}"
+".drp-d.drp-today:not(.drp-s):not(.drp-e){font-weight:700;color:var(--color-primary,#FF6B6B);}"
+".drp-d.drp-sun:not(.drp-s):not(.drp-e){color:#ef4444;}"
+".drp-d.drp-sat:not(.drp-s):not(.drp-e){color:#3b82f6;}"
+".drp-d.drp-off{color:#d1d5db!important;pointer-events:none!important;cursor:default!important;}"
+".drp-pre:hover{background:rgba(255,107,107,.08)!important;color:var(--color-primary,#FF6B6B)!important;}"
+".drp-pre.on{background:rgba(255,107,107,.12)!important;color:var(--color-primary,#FF6B6B)!important;font-weight:700!important;}";
document.head.appendChild(s);}

/* ── Hitung lo/hi range berdasarkan state ─────────────────
   picking=true  → preview: tS + hov (atau hanya tS)
   picking=false → final:   tS + tE  (sudah bersih)
─────────────────────────────────────────────────────────── */
function calcRange(st){
var a,b;
if(st.picking){
a=pd(st.tS);
b=st.hov?pd(st.hov):null;
}else{
a=pd(st.tS);
b=pd(st.tE);
}
if(!a)return{lo:null,hi:null};
if(!b)return{lo:a,hi:null};
return a<=b?{lo:a,hi:b}:{lo:b,hi:a};
}

function renderMonth(id,yr,mo,side){
var st=ST[id];
var el=document.getElementById(id+"-cal-"+side);
if(!el)return;
var tod=now0();
var rng=calcRange(st);
var lo=rng.lo,hi=rng.hi;
var first=new Date(yr,mo,1);
var dow=first.getDay();dow=dow===0?6:dow-1;
var dim=new Date(yr,mo+1,0).getDate();
var h="<div style=\"display:grid;grid-template-columns:repeat(7,30px);gap:2px;justify-content:center;\">";
DA.forEach(function(d,i){
var c=i===6?"#ef4444":(i===5?"#3b82f6":"#9ca3af");
h+="<div style=\"width:30px;text-align:center;font-size:.68rem;font-weight:700;color:"+c+";margin-bottom:4px;\">"+d+"</div>";
});
for(var i=0;i<dow;i++)h+="<div class=\"drp-d drp-off\"></div>";
for(var d=1;d<=dim;d++){
var dt=new Date(yr,mo,d);dt.setHours(0,0,0,0);
var ds=fi(dt);
var off=dt>tod;
var c="drp-d";
if(off){
c+=" drp-off";
}else{
var dw=dt.getDay();
if(dw===0)c+=" drp-sun";
if(dw===6)c+=" drp-sat";
if(eq(dt,tod))c+=" drp-today";
if(lo&&eq(dt,lo))c+=" drp-s";
if(hi&&eq(dt,hi))c+=" drp-e";
if(lo&&hi&&dt>lo&&dt<hi)c+=" drp-rng";
}                h+="<div class=\""+c+"\" data-d=\""+ds+"\">"+d+"</div>";
}
h+="</div>";el.innerHTML=h;}

function bindDRP(id){
var p=document.getElementById(id+"-popup");
if(!p||p._drp)return;p._drp=1;
/* Pakai capturing phase (true) biar jalan duluan sebelum child punya stopPropagation */
p.addEventListener("click",function(e){
var t=e.target.closest("[data-d]");
if(t&&!t.classList.contains("drp-off")){e.stopPropagation();DRP.ck(id,t.getAttribute("data-d"));}
},true);
var _h=null;
p.addEventListener("mouseover",function(e){
var t=e.target.closest("[data-d]");
if(t&&!t.classList.contains("drp-off")){
var d=t.getAttribute("data-d");
if(d!==_h){_h=d;DRP.hv(id,d);}
}
});
}

function renderBoth(id){
var st=ST[id];
renderMonth(id,st.yL,st.mL,"l");
renderMonth(id,st.yR,st.mR,"r");
var lE=document.getElementById(id+"-ml"),rE=document.getElementById(id+"-mr");
if(lE)lE.textContent=MO[st.mL]+" "+st.yL;
if(rE)rE.textContent=MO[st.mR]+" "+st.yR;
document.querySelectorAll("[data-drp=\""+id+"\"]").forEach(function(b){
b.classList.toggle("on",b.getAttribute("data-key")===st.preset);
});
/* ── Update label di trigger button ── */
var lb=document.getElementById(id+"-label");
if(!lb)return;
if(st.picking&&st.tS){
var preview=st.hov||st.tS;
var a=pd(st.tS),b=pd(preview);
lb.textContent=a<=b?fl(st.tS)+" — "+fl(preview):fl(preview)+" — "+fl(st.tS);
}else if(st.tS&&st.tE){
var a=pd(st.tS),b=pd(st.tE);
lb.textContent=a<=b?fl(st.tS)+" — "+fl(st.tE):fl(st.tE)+" — "+fl(st.tS);
}else if(st.tS){
lb.textContent=fl(st.tS)+" — ...";
}
}

/* ── KLIK ──────────────────────────────────────────────────
   Klik 1: mulai picking → set tS, reset tE & hov
   Klik 2: selesai       → set tE (bisa sama tS = 1 hari)
─────────────────────────────────────────────────────────── */
function ck(id,ds){
var st=ST[id];
if(pd(ds)>now0())return;
st.preset=null;
if(!st.picking){
st.tS=ds;st.tE=null;st.hov=null;st.picking=true;
}else{
st.tE=ds;st.hov=null;st.picking=false;
}
renderBoth(id);
}

/* ── HOVER ─────────────────────────────────────────────────
   Hanya aktif saat picking=true
   Simpan ke st.hov, BUKAN st.tE
─────────────────────────────────────────────────────────── */
function hv(id,ds){
var st=ST[id];
if(!st.picking)return;
st.hov=ds;
renderBoth(id);
}

function applyPreset(id,key){
var now=new Date(),s,e,f=fi;
var so=function(y,m){return new Date(y,m,1);};
var eo=function(y,m){return new Date(y,m+1,0);};
if(key==="kemarin"){var yd=new Date(now);yd.setDate(now.getDate()-1);s=e=f(yd);}
else if(key==="today"){s=e=f(now);}
else if(key==="month"){s=f(so(now.getFullYear(),now.getMonth()));e=f(now);}
else if(key==="lmonth"){
var lm=now.getMonth()===0?11:now.getMonth()-1;
var ly=now.getMonth()===0?now.getFullYear()-1:now.getFullYear();
s=f(so(ly,lm));e=f(eo(ly,lm));
}
else if(key==="7d"){var d7=new Date(now);d7.setDate(now.getDate()-6);s=f(d7);e=f(now);}
else if(key==="30d"){var d30=new Date(now);d30.setDate(now.getDate()-29);s=f(d30);e=f(now);}
else if(key==="90d"){var d90=new Date(now);d90.setDate(now.getDate()-89);s=f(d90);e=f(now);}
else return;
var st=ST[id];
st.tS=s;st.tE=e;st.hov=null;st.picking=false;st.preset=key;
var sv=pd(s);
st.yL=sv.getFullYear();st.mL=sv.getMonth();
var rv=new Date(sv.getFullYear(),sv.getMonth()+1,1);
st.yR=rv.getFullYear();st.mR=rv.getMonth();
renderBoth(id);
}

function prevMonth(id){
var st=ST[id],d=new Date(st.yL,st.mL-1,1);
st.yL=d.getFullYear();st.mL=d.getMonth();
var r=new Date(d.getFullYear(),d.getMonth()+1,1);
st.yR=r.getFullYear();st.mR=r.getMonth();
renderBoth(id);
}
function nextMonth(id){
var st=ST[id],d=new Date(st.yL,st.mL+1,1);
st.yL=d.getFullYear();st.mL=d.getMonth();
var r=new Date(d.getFullYear(),d.getMonth()+1,1);
st.yR=r.getFullYear();st.mR=r.getMonth();
renderBoth(id);
}

function    open(id){
        injectCSS();
        var pop=document.getElementById(id+"-popup");
        var dE=document.getElementById(id+"-dari");
        var sE=document.getElementById(id+"-sampai");
        var s=dE?dE.value:fi(new Date());
        var e=sE?sE.value:fi(new Date());
        var sv=pd(s);
        ST[id]={
            s:s,e:e,tS:s,tE:e,
            hov:null,picking:false,preset:null,
            yL:sv.getFullYear(),mL:sv.getMonth(),
            yR:sv.getMonth()===11?sv.getFullYear()+1:sv.getFullYear(),
            mR:sv.getMonth()===11?0:sv.getMonth()+1
        };
        if(pop)pop.style.display="flex";
        renderBoth(id);
        bindDRP(id);
    }

function close(id){
var pop=document.getElementById(id+"-popup");
if(pop)pop.style.display="none";
var st=ST[id];
if(st){st.tS=st.s;st.tE=st.e;st.hov=null;st.picking=false;}
}

function applyAndSubmit(id,fid){
var st=ST[id];
if(!st||!st.tS)return;
/* Jika hanya 1 klik (picking masih true), pakai hov atau tS untuk tE */
var finalE=st.picking?(st.hov||st.tS):(st.tE||st.tS);
var a=pd(st.tS),b=pd(finalE);
var finalS=a<=b?st.tS:finalE;
finalE=a<=b?finalE:st.tS;
st.s=finalS;st.e=finalE;st.picking=false;
var dE=document.getElementById(id+"-dari");
var sE=document.getElementById(id+"-sampai");
if(dE)dE.value=finalS;
if(sE)sE.value=finalE;
var lb=document.getElementById(id+"-label");
if(lb)lb.textContent=fl(finalS)+" — "+fl(finalE);
close(id);
var form=document.getElementById(fid);
if(form)form.submit();
}

document.addEventListener("click",function(ev){
if(ev.target.id&&ev.target.id.endsWith("-popup")){
close(ev.target.id.replace("-popup",""));
}
});

return{
open:open,close:close,
prevMonth:prevMonth,nextMonth:nextMonth,
applyPreset:applyPreset,applyAndSubmit:applyAndSubmit,
ck:ck,hv:hv,
_click:ck,_hover:hv
};
})();
</script>

<script>
(function () {
    var sidebar   = document.getElementById('sidebar');
    var backdrop  = document.getElementById('sidebar-backdrop');
    var body      = document.body;
    var DESKTOP_BP = 1024; // px

    var isDesktop  = function () { return window.innerWidth >= DESKTOP_BP; };

    /* ── Baca state tersimpan, fallback ke default ── */
    function getStoredState() {
        var stored = localStorage.getItem('wa_sidebar');
        if (stored !== null) return stored === '1';
        // Default: terbuka di desktop, tertutup di sempit
        return isDesktop();
    }

    function saveState(open) {
        localStorage.setItem('wa_sidebar', open ? '1' : '0');
    }

    /* ── Terapkan state ke DOM ── */
    function applyState(open) {
        if (isDesktop()) {
            // Desktop: sidebar mendorong konten (relative)
            if (open) {
                sidebar.classList.remove('sidebar-closed');
                body.classList.add('sidebar-is-open');
            } else {
                sidebar.classList.add('sidebar-closed');
                body.classList.remove('sidebar-is-open');
            }
            // Backdrop tidak dibutuhkan di desktop
            backdrop.classList.remove('active');
            body.style.overflow = '';
        } else {
            // Layar sempit: sidebar overlay
            if (open) {
                sidebar.classList.add('sidebar-open');
                backdrop.classList.add('active');
                body.classList.add('sidebar-is-open');
                body.style.overflow = 'hidden';
            } else {
                sidebar.classList.remove('sidebar-open');
                backdrop.classList.remove('active');
                body.classList.remove('sidebar-is-open');
                body.style.overflow = '';
            }
        }
    }

    /* ── Toggle ── */
    window.toggleSidebar = function () {
        var willOpen;
        if (isDesktop()) {
            willOpen = sidebar.classList.contains('sidebar-closed'); // buka jika sedang tertutup
        } else {
            willOpen = !sidebar.classList.contains('sidebar-open');  // buka jika sedang tertutup
        }
        applyState(willOpen);
        saveState(willOpen);
    };

    /* ── Tutup sidebar (backdrop klik / link di sempit) ── */
    window.closeSidebar = function () {
        applyState(false);
        saveState(false);
    };

    /* ── Inisialisasi saat halaman dimuat ── */
    function init() {
        var open = getStoredState();
        // Nonaktifkan transisi saat inisialisasi agar tidak terlihat animasi awal
        sidebar.style.transition = 'none';
        applyState(open);
        // Re-enable transisi setelah paint
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                sidebar.style.transition = '';
            });
        });
    }

    /* ── Ketika ukuran layar berubah, terapkan ulang state ── */
    var lastIsDesktop = isDesktop();
    window.addEventListener('resize', function () {
        var nowDesktop = isDesktop();
        if (nowDesktop !== lastIsDesktop) {
            // Pindah breakpoint: terapkan default yang sesuai
            // tapi tetap hormati stored state
            lastIsDesktop = nowDesktop;
            applyState(getStoredState());
        }
    });

    /* ── Tutup sidebar saat klik nav link di layar sempit ── */
    document.querySelectorAll('[data-page-link]').forEach(function (el) {
        el.addEventListener('click', function () {
            if (!isDesktop()) closeSidebar();
        });
    });

    init();
})();

{{-- ── Discrepancy Alarm Badge (Spending vs Regional) ── --}}
(function(){
    var spBadge = document.getElementById('spending-alarm-badge');
    var regBadge = document.getElementById('regional-alarm-badge');
    if(!spBadge && !regBadge) return;

    function checkDiscrepancy(){
        fetch('{{ route("regional.check") }}')
            .then(function(r){ return r.json(); })
            .then(function(d){
                var show = d.has_discrepancy ? 'inline-block' : 'none';
                if(spBadge) spBadge.style.display = show;
                if(regBadge) regBadge.style.display = show;
            })
            .catch(function(){});
    }

    checkDiscrepancy();
    // Cek setiap 60 detik
    setInterval(checkDiscrepancy, 60000);
})();

{{-- ── Notification unread count ──────────────────────── --}}
(function(){
    var badge = document.getElementById('notif-count-badge');
    if(!badge) return;

    function fetchUnread(){
        fetch('{{ route("notifications.unread-count") }}')
            .then(function(r){ return r.json(); })
            .then(function(d){
                if(d.count > 0){
                    badge.textContent = d.count > 99 ? '99+' : d.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(function(){});
    }

    fetchUnread();
    // Refresh setiap 30 detik
    setInterval(fetchUnread, 30000);
})();
</script>
</body>
</html>
