@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','📊 Dashboard')
@section('page-subtitle','Overview lengkap semua divisi Awanna')

@section('content')

@php
    $tSpend  = $spendingBulan->total_spending ?? 0;
    $tLead   = $spendingBulan->total_lead ?? 0;
    $tPaid   = $spendingBulan->total_paid ?? 0;
    $paidRatio = $tLead > 0 ? round($tPaid / $tLead * 100, 0) : 0;
    $cpaPaid = $tPaid > 0 ? round($tSpend / $tPaid, 0) : 0;

    $revTotal = $revenueBulan->total ?? 0;
    $revJml   = $revenueBulan->jumlah ?? 0;

    $codTotal = $orderPerPayment->where('payment_method', 'cod')->sum('jumlah');
    $btTotal  = $orderPerPayment->where('payment_method', 'bank_transfer')->sum('jumlah');

    $chartColors = ['#FF6B6B','#4ECDC4','#A78BFA','#FB923C','#34D399','#F472B6','#60A5FA','#FBBF24','#8B5CF6','#EC4899'];
@endphp

{{-- ═══════════════════════════════════════════════════════════
     ROW 1: 6 SUMMARY CARDS — SEMUA DOMAIN
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-summary-row" data-reveal>
    <div class="db-card db-card-blue">
        <div class="db-card-header"><span class="db-card-icon">🏦</span><span class="db-card-label">Saldo Total</span></div>
        <div class="db-card-value">Rp {{ number_format($totalBalance, 0, ',', '.') }}</div>
        <div class="db-card-sub">{{ $accounts->count() }} akun aktif</div>
    </div>
    <div class="db-card db-card-green">
        <div class="db-card-header"><span class="db-card-icon">💰</span><span class="db-card-label">Revenue {{ now()->translatedFormat('M') }}</span></div>
        <div class="db-card-value">Rp {{ number_format($revTotal, 0, ',', '.') }}</div>
        <div class="db-card-sub">{{ $revJml }} order</div>
    </div>
    <div class="db-card db-card-red">
        <div class="db-card-header"><span class="db-card-icon">📢</span><span class="db-card-label">Spending {{ now()->translatedFormat('M') }}</span></div>
        <div class="db-card-value">Rp {{ number_format($tSpend, 0, ',', '.') }}</div>
        <div class="db-card-sub">PR {{ $paidRatio }}% · CPA Rp {{ number_format($cpaPaid, 0, ',', '.') }}</div>
    </div>
    <a href="{{ route('approval.index') }}" class="db-card db-card-amber {{ $pendingApproval === 0 ? 'db-card-muted' : '' }}" style="text-decoration:none;color:inherit;">
        <div class="db-card-header"><span class="db-card-icon">⏳</span><span class="db-card-label">Pengajuan Pending</span></div>
        <div class="db-card-value">{{ $pendingApproval }}</div>
        <div class="db-card-sub">Top Up + Pembelian</div>
    </a>
    <a href="{{ route('finance.bank-transfers.index') }}" class="db-card db-card-purple {{ $pendingBukti === 0 ? 'db-card-muted' : '' }}" style="text-decoration:none;color:inherit;">
        <div class="db-card-header"><span class="db-card-icon">🧾</span><span class="db-card-label">Bukti Transfer</span></div>
        <div class="db-card-value">{{ $pendingBukti }}</div>
        <div class="db-card-sub">Menunggu review</div>
    </a>
    <div class="db-card db-card-teal">
        <div class="db-card-header"><span class="db-card-icon">📦</span><span class="db-card-label">Operasi Hari Ini</span></div>
        <div class="db-card-value">{{ $opsHariIni['total'] }} order</div>
        <div class="db-card-sub">🚚 {{ $opsHariIni['keluar'] }} out · 📥 {{ $opsHariIni['masuk'] }} in</div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ROW 2: Revenue Harian + Spending Harian
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-grid-2" data-reveal>
    <div class="clay-card" style="padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <div>
                <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;">💰 Revenue Harian (30 Hari)</div>
                <div style="font-size:.7rem;color:#9ca3af;">Total revenue dari order</div>
            </div>
            <span class="clay-badge clay-badge-green" style="font-size:.68rem;">Harian</span>
        </div>
        <div style="height:140px;margin-top:6px;position:relative;"><canvas id="chartRevenue"></canvas></div>
    </div>
    <div class="clay-card" style="padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <div>
                <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;">📢 Spending Harian (30 Hari)</div>
                <div style="font-size:.7rem;color:#9ca3af;">Total spending semua advertiser</div>
            </div>
            <span class="clay-badge clay-badge-blue" style="font-size:.68rem;">Harian</span>
        </div>
        <div style="height:140px;margin-top:6px;position:relative;"><canvas id="chartSpending"></canvas></div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ROW 3: Order per Kurir + COD vs BT + Stok In/Out
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-grid-3" data-reveal>
    <div class="clay-card" style="padding:18px;">
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:4px;">📦 Order per Kurir</div>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:8px;">Bulan ini</div>
        <div style="height:160px;position:relative;"><canvas id="chartCourier"></canvas></div>
    </div>
    <div class="clay-card" style="padding:18px;">
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:4px;">💳 COD vs Bank Transfer</div>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:8px;">Bulan ini</div>
        <div style="height:160px;position:relative;"><canvas id="chartPayment"></canvas></div>
    </div>
    <div class="clay-card" style="padding:18px;">
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:4px;">📊 Stok In/Out (14 Hari)</div>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:8px;">Jurnal stok harian</div>
        <div style="height:160px;position:relative;"><canvas id="chartStock"></canvas></div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ROW 4: Saldo Akun + Top Advertiser + Top Whitelist
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-grid-3" data-reveal>
    <div class="clay-card" style="padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🏦 Saldo Akun</div>
                <div style="font-size:.7rem;color:#9ca3af;">Semua akun aktif</div>
            </div>
            <a href="{{ route('finance.accounts.index') }}" class="clay-btn clay-btn-outline" style="padding:4px 10px;font-size:.72rem;">Lihat →</a>
        </div>
        @forelse($accounts as $acc)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:10px;margin-bottom:5px;background:{{ $acc->current_balance > 0 ? '#f0fdf4' : '#fef2f2' }};">
            <div>
                <div style="font-weight:700;font-size:.78rem;">{{ $acc->name }}</div>
                <div style="font-size:.65rem;color:#9ca3af;">{{ $acc->type_label }}</div>
            </div>
            <div style="font-weight:800;font-size:.82rem;color:{{ $acc->current_balance > 0 ? '#059669' : '#dc2626' }};">Rp {{ number_format($acc->current_balance, 0, ',', '.') }}</div>
        </div>
        @empty
        <p style="font-size:.8rem;color:#9ca3af;text-align:center;padding:16px 0;">Belum ada akun</p>
        @endforelse
    </div>

    <div class="clay-card" style="padding:18px;">
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:12px;">🏆 Top Advertiser — {{ now()->translatedFormat('F Y') }}</div>
        @php $medals = ['🥇','🥈','🥉','4️⃣','5️⃣']; @endphp
        @forelse($topAdvertiser as $idx => $adv)
        @php
            $pr  = ($adv->total_lead??0) > 0 ? round(($adv->total_paid??0)/($adv->total_lead??0)*100,0) : 0;
            $cpa = ($adv->total_paid??0) > 0 ? round(($adv->total_spending??0)/($adv->total_paid??0),0) : 0;
        @endphp
        <div style="display:flex;align-items:center;gap:8px;padding:6px;border-radius:10px;margin-bottom:3px;transition:background .15s;" onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background=''">
            <span style="font-size:1rem;">{{ $medals[$idx] ?? ($idx+1) }}</span>
            <img src="{{ $adv->user->avatar_url ?? '' }}" style="width:26px;height:26px;border-radius:7px;object-fit:cover;background:#f3f4f6;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.78rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $adv->user->display_name ?? 'Unknown' }}</div>
                <div style="font-size:.65rem;color:#9ca3af;">Rp {{ number_format($adv->total_spending??0,0,',','.') }} · CPA Rp {{ number_format($cpa,0,',','.') }}</div>
            </div>
            <span class="clay-badge {{ $pr>=20?'clay-badge-green':($pr>=10?'clay-badge-yellow':'clay-badge-red') }}" style="font-size:.65rem;">{{ $pr }}%</span>
        </div>
        @empty
        <p style="font-size:.8rem;color:#9ca3af;text-align:center;padding:20px 0;">Belum ada data</p>
        @endforelse
    </div>

    <div class="clay-card" style="padding:18px;">
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:2px;">✅ Top Whitelist</div>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:12px;">Bulan ini · berdasarkan spending</div>
        @php $totalWl = $spendingPerWhitelist->sum('total_spending') ?: 1; @endphp
        @forelse($spendingPerWhitelist as $w)
        @php $pct = round(($w->total_spending / $totalWl) * 100); @endphp
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                <span style="font-size:.76rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:60%;">{{ $w->whitelist->nama ?? '-' }}</span>
                <span style="font-size:.7rem;font-weight:700;color:var(--color-primary);">{{ $pct }}%</span>
            </div>
            <div style="height:4px;border-radius:999px;background:#FFF5F5;overflow:hidden;">
                <div style="height:4px;border-radius:999px;background:var(--color-primary);opacity:.7;width:{{ $pct }}%;"></div>
            </div>
            <div style="font-size:.63rem;color:#9ca3af;margin-top:1px;">Rp {{ number_format($w->total_spending,0,',','.') }} · {{ $w->total_lead }} lead · {{ $w->total_paid }} paid</div>
        </div>
        @empty
        <p style="font-size:.8rem;color:#9ca3af;text-align:center;padding:12px 0;">Belum ada data</p>
        @endforelse
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ROW 5: Order Terbaru + Pengiriman Terakhir
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-grid-2" data-reveal>
    <div class="clay-card" style="padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;">📋 Order Terbaru (7 Hari)</div>
                <div style="font-size:.7rem;color:#9ca3af;">{{ $recentOrders->count() }} order</div>
            </div>
            <a href="{{ route('orders.index') }}" class="clay-btn clay-btn-outline" style="padding:4px 10px;font-size:.72rem;">Lihat →</a>
        </div>
        <div class="table-scroll">
            <table class="clay-table">
                <thead><tr><th>Order ID</th><th>Penerima</th><th style="text-align:right;">Amount</th><th>Status</th><th>Kurir</th></tr></thead>
                <tbody>
                @forelse($recentOrders as $o)
                <tr>
                    <td style="font-weight:600;font-size:.76rem;">{{ $o->order_id }}</td>
                    <td style="font-size:.8rem;">{{ Str::limit($o->customer_name ?? '-', 18) }}</td>
                    <td style="text-align:right;font-weight:600;font-size:.8rem;color:var(--color-primary);">Rp {{ number_format($o->amount??0,0,',','.') }}</td>
                    <td>
                        @php $sClass = match($o->status) { 'real' => 'clay-badge-green', 'tembakan' => 'clay-badge-blue', 'cancel' => 'clay-badge-red', default => 'clay-badge-gray' }; @endphp
                        <span class="clay-badge {{ $sClass }}" style="font-size:.66rem;">{{ ucfirst($o->status) }}</span>
                    </td>
                    <td style="font-size:.76rem;font-weight:600;">{{ strtoupper($o->courier ?? '-') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:16px;color:#9ca3af;">Belum ada order</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="clay-card" style="padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🚚 Pengiriman Terakhir</div>
                <div style="font-size:.7rem;color:#9ca3af;">7 hari terakhir</div>
            </div>
            <a href="{{ route('shipment.index') }}" class="clay-btn clay-btn-outline" style="padding:4px 10px;font-size:.72rem;">Lihat →</a>
        </div>
        <div class="table-scroll">
            <table class="clay-table">
                <thead><tr><th>No Resi</th><th>Kurir</th><th>Penerima</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($recentShipments as $s)
                <tr>
                    <td style="font-weight:600;font-size:.76rem;">{{ Str::limit($s->tracking_number, 18) }}</td>
                    <td style="font-size:.8rem;font-weight:600;">{{ strtoupper($s->courier ?? '-') }}</td>
                    <td style="font-size:.8rem;">{{ Str::limit($s->recipient_name ?? '-', 18) }}</td>
                    <td>
                        @php $sClass = match($s->status ?? '') { 'delivered' => 'clay-badge-green', 'in_transit' => 'clay-badge-blue', 'returned' => 'clay-badge-red', default => 'clay-badge-gray' }; @endphp
                        <span class="clay-badge {{ $sClass }}" style="font-size:.66rem;">{{ ucfirst($s->status ?? '-') }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;padding:16px;color:#9ca3af;">Belum ada pengiriman</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ROW 6: Barang Masuk Terakhir + Stok Menipis + Master Data
     ═══════════════════════════════════════════════════════════ --}}
<div class="db-grid-3" data-reveal>
    <div class="clay-card" style="padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;">📥 Barang Masuk</div>
                <div style="font-size:.7rem;color:#9ca3af;">Pembelian terbaru</div>
            </div>
            <a href="{{ route('purchase.index') }}" class="clay-btn clay-btn-outline" style="padding:4px 10px;font-size:.72rem;">Lihat →</a>
        </div>
        @forelse($recentPurchases as $pu)
        @php
            $puClass = match($pu->status) { 'received' => 'clay-badge-green', 'approved' => 'clay-badge-blue', 'pending' => 'clay-badge-yellow', 'rejected' => 'clay-badge-red', default => 'clay-badge-gray' };
            $puIcon = match($pu->status) { 'pending' => '⏳', 'approved' => '✅', 'received' => '📦', 'rejected' => '❌', default => '-' };
        @endphp
        <div style="display:flex;align-items:center;gap:8px;padding:8px;border-radius:10px;margin-bottom:5px;background:#f0fdf4;">
            <span style="font-size:1rem;">📦</span>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.78rem;">{{ $pu->variant->product->name ?? '-' }} {{ $pu->variant->power ? '+'.$pu->variant->power : '' }}</div>
                <div style="font-size:.65rem;color:#9ca3af;">{{ $pu->quantity }} pcs · Rp {{ number_format($pu->unit_price??0,0,',','.') }} · {{ $pu->created_at->diffForHumans() }}</div>
            </div>
            <span class="clay-badge {{ $puClass }}" style="font-size:.64rem;">{{ $puIcon }} {{ ucfirst($pu->status) }}</span>
        </div>
        @empty
        <div style="text-align:center;padding:20px 0;">
            <div style="font-size:1.8rem;margin-bottom:6px;">📥</div>
            <div style="font-size:.82rem;color:#9ca3af;">Belum ada pembelian</div>
        </div>
        @endforelse
    </div>

    <div class="clay-card" style="padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;">⚠️ Stok Menipis</div>
                <div style="font-size:.7rem;color:#9ca3af;">Produk di bawah min. stok</div>
            </div>
            <a href="{{ route('gudang.index') }}" class="clay-btn clay-btn-outline" style="padding:4px 10px;font-size:.72rem;">Gudang →</a>
        </div>
        @forelse($lowStockProducts as $ls)
        @php
            $totalStok = $ls->variants->sum('stock');
            $pct = $ls->min_stock > 0 ? round($totalStok / $ls->min_stock * 100) : 0;
            $barColor = $pct <= 30 ? '#dc2626' : ($pct <= 60 ? '#f59e0b' : '#059669');
        @endphp
        <div style="display:flex;align-items:center;gap:8px;padding:8px;border-radius:10px;margin-bottom:5px;background:{{ $pct <= 30 ? '#fef2f2' : '#fffbeb' }};">
            <span style="font-size:1rem;">{{ $pct <= 30 ? '🔴' : '🟡' }}</span>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.78rem;">{{ $ls->name }} ({{ $ls->code }})</div>
                <div style="font-size:.65rem;color:#9ca3af;">Stok: {{ $totalStok }} / Min: {{ $ls->min_stock }}</div>
                <div style="height:3px;border-radius:999px;background:#e5e7eb;overflow:hidden;margin-top:3px;">
                    <div style="height:3px;border-radius:999px;background:{{ $barColor }};width:{{ min($pct, 100) }}%;"></div>
                </div>
            </div>
            <span style="font-size:.7rem;font-weight:700;color:{{ $barColor }};">{{ $pct }}%</span>
        </div>
        @empty
        <div style="text-align:center;padding:20px 0;">
            <div style="font-size:1.8rem;margin-bottom:6px;">✅</div>
            <div style="font-size:.82rem;color:#9ca3af;">Semua stok aman</div>
        </div>
        @endforelse
    </div>

    <div class="clay-card" style="padding:18px;">
        <div style="font-weight:800;font-size:.9rem;color:#1e1b2e;margin-bottom:12px;">📊 Master Data</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#f0fdf4;">
                <div style="font-size:1.4rem;margin-bottom:4px;">🏭</div>
                <div style="font-weight:800;font-size:1rem;color:#059669;">{{ $stats['total_supplier'] }}</div>
                <div style="font-size:.68rem;color:#6b7280;">Supplier</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#eff6ff;">
                <div style="font-size:1.4rem;margin-bottom:4px;">📦</div>
                <div style="font-weight:800;font-size:1rem;color:#2563eb;">{{ $stats['total_produk'] }}</div>
                <div style="font-size:.68rem;color:#6b7280;">Produk</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#f5f0ff;">
                <div style="font-size:1.4rem;margin-bottom:4px;">👥</div>
                <div style="font-weight:800;font-size:1rem;color:#7c3aed;">{{ $stats['total_user'] }}</div>
                <div style="font-size:.68rem;color:#6b7280;">User</div>
            </div>
            <div class="clay-card-sm" style="padding:12px;text-align:center;background:#fef2f2;">
                <div style="font-size:1.4rem;margin-bottom:4px;">✅</div>
                <div style="font-weight:800;font-size:1rem;color:#dc2626;">{{ $stats['total_whitelist'] }}</div>
                <div style="font-size:.68rem;color:#6b7280;">Whitelist</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .db-summary-row { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:18px; }
    @media(max-width:1200px){ .db-summary-row{ grid-template-columns:repeat(3,1fr); } }
    @media(max-width:767px){ .db-summary-row{ grid-template-columns:repeat(2,1fr); gap:8px; } }

    .db-card { border-radius:14px; padding:14px 16px; border:2px solid transparent; position:relative; overflow:hidden; transition:transform .2s,box-shadow .2s; }
    .db-card:hover { transform:translateY(-2px); }
    .db-card-header { display:flex; align-items:center; gap:6px; margin-bottom:6px; }
    .db-card-icon { font-size:1.15rem; }
    .db-card-label { font-size:.68rem; font-weight:600; text-transform:uppercase; opacity:.8; letter-spacing:.03em; }
    .db-card-value { font-size:1.1rem; font-weight:900; line-height:1.2; }
    .db-card-sub { font-size:.67rem; opacity:.75; margin-top:3px; }

    .db-card-blue { background:linear-gradient(135deg,#3b82f6,#60a5fa); box-shadow:4px 4px 0 #2563eb; color:#fff; }
    .db-card-green { background:linear-gradient(135deg,#10b981,#34d399); box-shadow:4px 4px 0 #059669; color:#fff; }
    .db-card-red { background:linear-gradient(135deg,#FF6B6B,#FF9A9A); box-shadow:4px 4px 0 #e05555; color:#fff; }
    .db-card-amber { background:linear-gradient(135deg,#f59e0b,#fbbf24); box-shadow:4px 4px 0 #d97706; color:#fff; }
    .db-card-purple { background:linear-gradient(135deg,#A78BFA,#C4B5FD); box-shadow:4px 4px 0 #7c5cf5; color:#fff; }
    .db-card-teal { background:linear-gradient(135deg,#4ECDC4,#88DED8); box-shadow:4px 4px 0 #3ab8b0; color:#fff; }
    .db-card-muted { opacity:.7; }
    .db-card-muted:hover { opacity:1; }

    .db-grid-2 { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; margin-bottom:18px; }
    .db-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:18px; }
    @media(max-width:1023px){ .db-grid-3{ grid-template-columns:1fr; } }
    @media(max-width:767px){ .db-grid-2{ grid-template-columns:1fr; gap:10px; } .db-card-value{ font-size:.95rem; } }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.font.size = 10;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyleWidth = 7;
    Chart.defaults.plugins.legend.labels.padding = 10;
    Chart.defaults.elements.point.radius = 1.5;
    Chart.defaults.elements.point.hoverRadius = 4;

    const colors = @json($chartColors);
    const fmtRp = v => 'Rp ' + Number(v).toLocaleString('id-ID');

    const makeLabels = data => data.map(d => { const dt = new Date(d.tanggal || d.date); return dt.getDate()+'/'+(dt.getMonth()+1); });

    // ── Revenue Harian ──
    const rData = @json($chartRevenue30);
    const rCtx = document.getElementById('chartRevenue');
    if (rCtx) new Chart(rCtx, {
        type:'line', data:{ labels:makeLabels(rData), datasets:[{ label:'Revenue', data:rData.map(d=>d.total), borderColor:'#10b981', backgroundColor:'rgba(16,185,129,0.1)', fill:true, tension:.35, borderWidth:2, pointBackgroundColor:'#10b981' }] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false}, tooltip:{callbacks:{label:c=>fmtRp(c.parsed.y)}} }, scales:{ x:{grid:{display:false},ticks:{maxRotation:0,autoSkip:true,maxTicksLimit:8}}, y:{grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:v=>v>=1e6?(v/1e6).toFixed(0)+'jt':v>=1e3?(v/1e3).toFixed(0)+'rb':v}} } }
    });

    // ── Spending Harian ──
    const sData = @json($chartSpending30);
    const sCtx = document.getElementById('chartSpending');
    if (sCtx) new Chart(sCtx, {
        type:'line', data:{ labels:makeLabels(sData), datasets:[{ label:'Spending', data:sData.map(d=>d.total_spending), borderColor:'#FF6B6B', backgroundColor:'rgba(255,107,107,0.1)', fill:true, tension:.35, borderWidth:2, pointBackgroundColor:'#FF6B6B' }] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false}, tooltip:{callbacks:{label:c=>fmtRp(c.parsed.y)}} }, scales:{ x:{grid:{display:false},ticks:{maxRotation:0,autoSkip:true,maxTicksLimit:8}}, y:{grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:v=>v>=1e6?(v/1e6).toFixed(0)+'jt':v>=1e3?(v/1e3).toFixed(0)+'rb':v}} } }
    });

    // ── Order per Courier ──
    const cData = @json($orderPerCourier);
    const cCtx = document.getElementById('chartCourier');
    if (cCtx && cData.length) new Chart(cCtx, {
        type:'doughnut', data:{ labels:cData.map(d=>(d.courier||'N/A').toUpperCase()), datasets:[{ data:cData.map(d=>d.jumlah), backgroundColor:colors.slice(0,cData.length), borderWidth:2, borderColor:'#fff' }] },
        options:{ responsive:true, maintainAspectRatio:false, cutout:'60%', plugins:{ legend:{position:'bottom',labels:{padding:8,font:{size:9}}}, tooltip:{callbacks:{label:c=>c.label+': '+c.parsed+' order'}} } }
    });

    // ── COD vs BT ──
    const pCtx = document.getElementById('chartPayment');
    if (pCtx) {
        const pL=[],pV=[],pC=[];
        const codVal={{ $codTotal }},btVal={{ $btTotal }};
        if(codVal>0){pL.push('COD');pV.push(codVal);pC.push('#FB923C');}
        if(btVal>0){pL.push('Bank Transfer');pV.push(btVal);pC.push('#4ECDC4');}
        if(pV.length) new Chart(pCtx, {
            type:'doughnut', data:{ labels:pL, datasets:[{ data:pV, backgroundColor:pC, borderWidth:2, borderColor:'#fff' }] },
            options:{ responsive:true, maintainAspectRatio:false, cutout:'60%', plugins:{ legend:{position:'bottom',labels:{padding:8,font:{size:9}}}, tooltip:{callbacks:{label:c=>c.label+': '+c.parsed+' order'}} } }
        });
    }

    // ── Stok In/Out ──
    const stData = @json($chartStock14);
    const stCtx = document.getElementById('chartStock');
    if (stCtx) new Chart(stCtx, {
        type:'bar', data:{ labels:makeLabels(stData), datasets:[
            { label:'Masuk', data:stData.map(d=>d.masuk), backgroundColor:'rgba(78,205,196,0.75)', borderColor:'#3ab8b0', borderWidth:1, borderRadius:3 },
            { label:'Keluar', data:stData.map(d=>d.keluar), backgroundColor:'rgba(255,107,107,0.75)', borderColor:'#e05555', borderWidth:1, borderRadius:3 }
        ] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{position:'bottom',labels:{padding:8,font:{size:9}}} }, scales:{ x:{grid:{display:false},ticks:{maxRotation:0,autoSkip:true,maxTicksLimit:7}}, y:{grid:{color:'rgba(0,0,0,0.04)'},beginAtZero:true} } }
    });
});
</script>
@endpush
