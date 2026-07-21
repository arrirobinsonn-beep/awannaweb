@extends('layouts.app')
@section('title','Kiriman Actual')
@section('page-title','🚚 Kiriman Actual')
@section('page-subtitle','Data kiriman harian per dashboard (SPX / FLIK / SICEPAT / PEACHTREE)')

@section('content')

<div class="clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <form method="GET" action="{{ route('gudang.kiriman') }}"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div>
            <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151;">Bulan</label>
            <input type="month" name="bulan" value="{{ request('bulan') }}"
                   class="clay-input" style="padding:6px 10px;">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary">Filter</button>
        <a href="{{ route('gudang.kiriman') }}" class="clay-btn clay-btn-outline">Reset</a>
        <button type="button" class="clay-btn clay-btn-success" style="margin-left:auto;"
                onclick="document.getElementById('form-tambah').classList.toggle('hidden')">
            + Tambah
        </button>
        <button type="button" class="clay-btn clay-btn-outline" style="font-size:.7rem;"
                onclick="document.getElementById('panel-dashboard').classList.toggle('hidden')">
            ⚙️ Atur Dashboard
        </button>
    </form>
</div>

{{-- ─── Panel Atur Dashboard ─────────────────────────────────────────────── --}}
<div id="panel-dashboard" class="hidden clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <h4 style="font-weight:700;margin:0;font-size:.85rem;">⚙️ Atur Dashboard</h4>
        <button type="button" class="clay-btn clay-btn-sm clay-btn-outline" onclick="document.getElementById('panel-dashboard').classList.add('hidden')">✕ Tutup</button>
    </div>
    <form method="POST" action="{{ route('gudang.kiriman.dashboard-store') }}"
          style="display:flex;gap:10px;align-items:flex-end;margin-bottom:12px;">
        @csrf
        <div>
            <label class="field-label" style="font-size:.65rem;">Nama Dashboard Baru</label>
            <input type="text" name="name" required class="clay-input" placeholder="contoh: JNE" style="padding:4px 8px;font-size:.8rem;">
        </div>
        <button type="submit" class="clay-btn clay-btn-primary" style="font-size:.75rem;">Tambah</button>
    </form>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
        @foreach($allDashboards as $db)
        <div style="display:flex;align-items:center;gap:4px;background:#f1f5f9;padding:4px 8px;border-radius:6px;font-size:.75rem;font-weight:600;">
            <span>{{ $db->name }}</span>
            <form method="POST" action="{{ route('gudang.kiriman.dashboard-destroy', $db) }}"
                  style="display:inline" onsubmit="return confirm('Hapus dashboard \"{{ $db->name }}\"?')">
                @csrf @method('DELETE')
                <button class="clay-btn clay-btn-xs clay-btn-danger" style="padding:0 4px;font-size:.6rem;min-height:0;">✕</button>
            </form>
        </div>
        @endforeach
    </div>
</div>

<div id="form-tambah" class="hidden clay-card" style="padding:16px;margin-bottom:16px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <h4 style="font-weight:700;margin:0;">Tambah Kiriman</h4>
        <button type="button" class="clay-btn clay-btn-sm clay-btn-outline" onclick="tutupForm()">✕ Tutup</button>
    </div>
    <form method="POST" action="{{ route('gudang.kiriman.store') }}">
        @csrf
        <div style="display:flex;gap:10px;margin-bottom:8px;align-items:flex-end;">
            <div>
                <label class="field-label">Tanggal</label>
                <input type="date" name="tanggal" required class="clay-input" value="{{ date('Y-m-d') }}">
            </div>
            <div style="display:flex;align-items:flex-end;gap:10px;flex:1;">
                <button type="button" class="clay-btn clay-btn-sm clay-btn-success" onclick="tambahBaris()">+ Tambah Baris</button>
            </div>
        </div>
        <div id="baris-container">
            <div class="baris-kiriman" style="display:flex;gap:10px;margin-bottom:6px;align-items:flex-end;">
                <div style="flex:0 0 100px;">
                    <label class="field-label" style="font-size:.6rem;">Jenis</label>
                    <select name="jenis[]" required class="clay-input" style="padding:4px 6px;font-size:.75rem;">
                        <option value="TF">TF</option>
                        <option value="COD">COD</option>
                    </select>
                </div>
                <div style="flex:0 0 120px;">
                    <label class="field-label" style="font-size:.6rem;">Dashboard</label>
                    <select name="dashboard[]" required class="clay-input" style="padding:4px 6px;font-size:.75rem;">
                        @foreach($dashboards as $db)
                        <option value="{{ $db }}">{{ $db }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:0 0 80px;">
                    <label class="field-label" style="font-size:.6rem;">Jumlah Resi</label>
                    <input type="number" name="jumlah_resi[]" class="clay-input" min="0" value="0" style="padding:4px 6px;font-size:.75rem;">
                </div>
                <div style="flex:1;">
                    <label class="field-label" style="font-size:.6rem;">Value Resi</label>
                    <input type="number" name="value_resi[]" class="clay-input" min="0" step="0.01" value="0" style="padding:4px 6px;font-size:.75rem;">
                </div>
                <button type="button" class="clay-btn clay-btn-sm clay-btn-danger" onclick="hapusBaris(this)" style="margin-bottom:2px;font-size:.65rem;">✕</button>
            </div>
        </div>
        <div style="margin-top:8px;">
            <button type="submit" class="clay-btn clay-btn-primary">Simpan Semua</button>
        </div>
    </form>
</div>

<script>
function tambahBaris() {
    var container = document.getElementById('baris-container');
    var first = container.querySelector('.baris-kiriman');
    var clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(function(inp) { inp.value = ''; });
    container.appendChild(clone);
}
function hapusBaris(btn) {
    var container = document.getElementById('baris-container');
    if (container.querySelectorAll('.baris-kiriman').length > 1) {
        btn.closest('.baris-kiriman').remove();
    }
}
function tutupForm() {
    document.getElementById('form-tambah').classList.add('hidden');
}
</script>

{{-- ─── Rekap Harian per Dashboard ─────────────────────────────────────── --}}
@if($recapByDashboard)
<div class="clay-card" style="padding:16px;margin-top:24px;" data-reveal>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
        <h4 style="font-weight:800;margin:0;font-size:.85rem;">📋 REKAP HARIAN PER DASHBOARD</h4>
        <form method="GET" action="{{ route('gudang.kiriman') }}" style="display:flex;gap:6px;align-items:flex-end;">
            <input type="hidden" name="bulan" value="{{ $bulan }}">
            <div>
                <label style="display:block;font-size:.6rem;font-weight:600;margin-bottom:2px;color:#6b7280;">Pilih Dashboard</label>
                <select name="dashboard" onchange="this.form.submit()" class="clay-input" style="padding:4px 8px;font-size:.75rem;">
                    <option value="">Semua</option>
                    @foreach($allDashboards as $db)
                    <option value="{{ $db->name }}" {{ $selectedDashboard===$db->name?'selected':'' }}>{{ $db->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($selectedDashboard)
            <a href="{{ route('gudang.kiriman', ['bulan' => $bulan]) }}" class="clay-btn clay-btn-xs clay-btn-outline" style="font-size:.65rem;">Reset</a>
            @endif
        </form>
    </div>

    @foreach($recapByDashboard as $i => $recap)
    <div class="db-folder" style="margin-bottom:8px;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
        {{-- Folder Header --}}
        <div class="db-header" onclick="togDb('db-{{ $i }}')"
             style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);cursor:pointer;user-select:none;border-bottom:1px solid #e5e7eb;">
            <span style="display:flex;align-items:center;gap:8px;font-weight:700;font-size:.85rem;color:#1e1b2e;">
                <span class="db-chevron" style="font-size:.65rem;color:#9ca3af;transition:transform .2s;">▶</span>
                📦 {{ $recap['dashboard'] }}
                <span style="font-weight:400;font-size:.7rem;color:#6b7280;">
                    (TF: {{ number_format($recap['tf_resi'],0,',','.') }} | COD: {{ number_format($recap['cod_resi'],0,',','.') }})
                </span>
            </span>
            <span style="font-weight:800;font-size:.75rem;color:var(--color-primary,#FF6B6B);">
                {{ number_format($recap['total_resi'],0,',','.') }} resi
            </span>
        </div>

        {{-- Folder Content --}}
        <div id="db-{{ $i }}" class="db-content" style="display:none;overflow-x:auto;">
            <table class="clay-table" style="font-size:.7rem;">
                <thead>
                    <tr>
                        <th style="min-width:80px;">TANGGAL</th>
                        <th colspan="2" style="min-width:70px;color:#2563eb;">TF</th>
                        <th colspan="2" style="min-width:70px;color:#dc2626;">COD</th>
                        <th colspan="2" style="min-width:70px;">TOTAL</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th style="font-size:.6rem;color:#6b7280;">JML</th>
                        <th style="font-size:.6rem;color:#6b7280;">VALUE</th>
                        <th style="font-size:.6rem;color:#6b7280;">JML</th>
                        <th style="font-size:.6rem;color:#6b7280;">VALUE</th>
                        <th style="font-size:.6rem;color:#6b7280;">JML</th>
                        <th style="font-size:.6rem;color:#6b7280;">VALUE</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recap['daily'] as $r)
                    <tr>
                        <td style="font-weight:600;">{{ \Carbon\Carbon::parse($r['date'])->format('d/m/Y') }}</td>
                        <td class="text-right">{{ $r['tf_resi'] > 0 ? number_format($r['tf_resi'],0,',','.') : '' }}</td>
                        <td class="text-right">{{ $r['tf_value'] > 0 ? number_format($r['tf_value'],0,',','.') : '' }}</td>
                        <td class="text-right">{{ $r['cod_resi'] > 0 ? number_format($r['cod_resi'],0,',','.') : '' }}</td>
                        <td class="text-right">{{ $r['cod_value'] > 0 ? number_format($r['cod_value'],0,',','.') : '' }}</td>
                        <td class="text-right" style="font-weight:700;">{{ number_format($r['total_resi'],0,',','.') }}</td>
                        <td class="text-right" style="font-weight:700;">{{ number_format($r['total_value'],0,',','.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;color:#9ca3af;">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc;font-weight:800;border-top:2px solid #e5e7eb;">
                        <td style="font-size:.7rem;">TOTAL {{ $recap['dashboard'] }}</td>
                        <td class="text-right">{{ number_format($recap['tf_resi'],0,',','.') }}</td>
                        <td class="text-right">{{ number_format($recap['tf_value'],0,',','.') }}</td>
                        <td class="text-right">{{ number_format($recap['cod_resi'],0,',','.') }}</td>
                        <td class="text-right">{{ number_format($recap['cod_value'],0,',','.') }}</td>
                        <td class="text-right" style="color:var(--color-primary,#FF6B6B);">{{ number_format($recap['total_resi'],0,',','.') }}</td>
                        <td class="text-right" style="color:var(--color-primary,#FF6B6B);">{{ number_format($recap['total_value'],0,',','.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endforeach

    @if(!$selectedDashboard)
    <div style="border-top:2px solid #e5e7eb;padding-top:12px;display:flex;justify-content:flex-end;gap:24px;font-weight:800;font-size:.85rem;">
        <span>GRAND TOTAL RESI: {{ number_format($grandTotalResi,0,',','.') }}</span>
        <span style="color:var(--color-primary,#FF6B6B);">GRAND TOTAL VALUE: {{ number_format($grandTotalValue,0,',','.') }}</span>
    </div>
    @endif
</div>

<script>
var openDbs = new Set();
function togDb(id) {
    var el = document.getElementById(id);
    var chev = el.parentElement.querySelector('.db-chevron');
    if (!el) return;
    if (openDbs.has(id)) {
        el.style.display = 'none';
        if (chev) chev.style.transform = 'rotate(0deg)';
        openDbs.delete(id);
    } else {
        el.style.display = 'block';
        if (chev) chev.style.transform = 'rotate(90deg)';
        openDbs.add(id);
    }
}
</script>
@endif

<style>
.hidden { display:none; }
.field-label { display:block;font-size:.75rem;font-weight:600;margin-bottom:4px;color:#374151; }
.text-right { text-align:right; }
.clay-table th, .clay-table td { white-space:nowrap; }
.badge { display:inline-block;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:700; }
.badge-danger { background:#fee2e2;color:#991b1b; }
.badge-info { background:#dbeafe;color:#1e40af; }
.badge-dashboard { background:#e0e7ff;color:#3730a3; }
.clay-btn-xs { padding:2px 8px !important;font-size:.65rem !important;min-height:0 !important; }
</style>
@endsection
