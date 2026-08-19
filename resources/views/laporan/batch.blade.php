@extends('layouts.app')
@section('title', 'Detail Pengirim — '.($batch->sender ?: 'Tanpa Pengirim'))
@section('page-title', '🧾 Detail Pengirim')
@section('page-subtitle', 'Barang & varian terjual — batch #'.$batch->id)

@section('content')

@php
    $periodeLabel = $isToday ? 'Hari Ini' : 'Periode Terpilih';
@endphp

{{-- ── Header batch ─────────────────────────────────────────── --}}
<div class="clay-card" style="padding:18px 22px;margin-bottom:16px;" data-reveal>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <div style="font-size:1.05rem;font-weight:800;color:#1e1b2e;">{{ $batch->sender ?: '(tanpa pengirim)' }}</div>
            <div style="font-size:.75rem;color:#9ca3af;margin-top:3px;">
                🗂 {{ $batch->original_filename }} • Batch #{{ $batch->id }} • diimport {{ $batch->created_at?->format('d/m/Y H:i') }}
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="clay-btn clay-btn-primary" id="btn-copy-report">📋 Copy ke WhatsApp</button>
            <a href="{{ route('operational-report.index', ['dari' => $dari, 'sampai' => $sampai]) }}" class="clay-btn" data-page-link>← Kembali ke Laporan</a>
        </div>
    </div>
</div>

{{-- ── Kartu ringkasan batch ────────────────────────────────── --}}
<div class="grid-stats" style="margin-bottom:20px;">
    <div class="stat-card stat-card-1" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Order {{ $periodeLabel }}</div>
        <div style="font-size:1.7rem;font-weight:900;">{{ number_format($summary->total_order,0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">📦 Resi {{ number_format($summary->resi,0,',','.') }}</div>
    </div>
    <div class="stat-card stat-card-2" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Qty Terjual</div>
        <div style="font-size:1.7rem;font-weight:900;">{{ number_format($summary->qty,0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">🎁 pcs / unit produk</div>
    </div>
    <div class="stat-card stat-card-3" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Uang Masuk</div>
        <div style="font-size:1.5rem;font-weight:900;">Rp {{ number_format((float)$summary->uang_masuk,0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">💰 gross revenue order</div>
    </div>
    <div class="stat-card stat-card-4" data-reveal>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.75;margin-bottom:8px;">Margin (Uang − HPP)</div>
        @php $margin = (float)$summary->uang_masuk - (float)$summary->hpp; $persen = (float)$summary->uang_masuk > 0 ? round($margin / (float)$summary->uang_masuk * 100, 1) : 0; @endphp
        <div style="font-size:1.5rem;font-weight:900;">Rp {{ number_format($margin,0,',','.') }}</div>
        <div style="font-size:.72rem;opacity:.8;margin-top:4px;">📉 HPP Rp {{ number_format((float)$summary->hpp,0,',','.') }} ({{ $persen }}%)</div>
    </div>
</div>

{{-- ── Filter rentang tanggal ───────────────────────────────── --}}
<div class="clay-card" style="padding:0;margin-bottom:20px;" data-reveal>
    <form method="GET" action="{{ route('operational-report.batch', $batch->id) }}" id="drp-form"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:16px;">
        <x-date-range-picker :dari="$dari" :sampai="$sampai" form-id="drp-form" />
        <button class="clay-btn clay-btn-primary" type="submit">🔍 Terapkan</button>
        <a href="{{ route('operational-report.batch', $batch->id) }}" class="clay-btn">Hari Ini</a>
        <span style="font-size:.75rem;color:#9ca3af;">Periode: {{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</span>
    </form>
</div>

{{-- ── Barang terjual & rincian varian ──────────────────────── --}}
@php
    $grouped = $rows->groupBy('kode_master');

    // ── Teks siap-copy (format WhatsApp) — dibangun dari $rows yang SAMA ──
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $powerTxt = function ($r) {
        if ((float) $r->power <= 0) return '';
        return ' (+'.rtrim(rtrim(number_format((float) $r->power, 2, '.', ''), '0'), '.').')';
    };
    $copyLines = [];
    $copyLines[] = '🧾 LAPORAN PENGIRIM — '.($batch->sender ?: '(tanpa pengirim)');
    $copyLines[] = '📅 '.\Carbon\Carbon::parse($dari)->translatedFormat('d M Y').' — '.\Carbon\Carbon::parse($sampai)->translatedFormat('d M Y');
    $copyLines[] = '🗂 '.$batch->original_filename.' • Batch #'.$batch->id;
    $copyLines[] = '';
    $copyLines[] = '📦 Order: '.$fmt($summary->total_order).' | Resi: '.$fmt($summary->resi);
    $copyLines[] = '🎁 Qty Terjual: '.$fmt($summary->qty).' pcs';
    $copyLines[] = '💰 Uang Masuk: Rp '.$fmt($summary->uang_masuk);
    $copyLines[] = '📉 HPP: Rp '.$fmt($summary->hpp);
    $copyMargin = (float) $summary->uang_masuk - (float) $summary->hpp;
    $copyPersen = (float) $summary->uang_masuk > 0 ? round($copyMargin / (float) $summary->uang_masuk * 100, 1) : 0;
    $copyLines[] = '📈 Margin: Rp '.$fmt($copyMargin).' ('.$copyPersen.'%)';
    $copyLines[] = '';
    $copyLines[] = '━━━ BARANG TERJUAL ━━━';
    foreach ($grouped as $kodeMaster => $group) {
        $first = $group->first();
        $copyLines[] = '📦 '.($first->nama_master ?? '(produk tak dikenal)').' ('.$kodeMaster.')'
            .' — '.$fmt($group->sum('qty')).' pcs — Rp '.$fmt($group->sum('uang_masuk'));
        foreach ($group as $r) {
            $copyLines[] = '  • '.($r->kode_varian ?: '-').$powerTxt($r)
                .' | '.($r->nama_terjual ?? '-')
                .' | '.$fmt($r->qty_per_order).' pcs/order ×'.$fmt($r->total_order)
                .' = '.$fmt($r->qty).' pcs — Rp '.$fmt($r->uang_masuk)
                .' (HPP Rp '.$fmt($r->hpp).')';
        }
    }
    $copyLines[] = '';
    $copyLines[] = '━━━ TOTAL ━━━';
    $copyLines[] = '🧮 '.$fmt($summary->qty).' pcs — Rp '.$fmt($summary->uang_masuk)
        .' — HPP Rp '.$fmt($summary->hpp);
    $copyText = implode("\n", $copyLines);
@endphp
<div class="clay-card" style="padding:0;overflow:hidden;" data-reveal>
    <div style="padding:16px 20px;border-bottom:1px solid rgba(0,0,0,.06);">
        <div style="font-weight:800;font-size:.95rem;color:#1e1b2e;">🛍 Barang Terjual & Rincian Varian</div>
        <div style="font-size:.72rem;color:#9ca3af;">
            Per produk + varian (power). Kacamata promo "Dapat N" tampil terpisah — varian sama dengan isi 2 pcs dan 4 pcs dihitung sebagai baris berbeda.
        </div>
    </div>
    <div class="table-scroll">
        <table class="clay-table" style="min-width:900px;">
            <thead>
                <tr>
                    <th>Produk (Varian)</th>
                    <th>Nama Terjual</th>
                    <th style="text-align:center;">Qty / Order</th>
                    <th style="text-align:right;">Jumlah Order</th>
                    <th style="text-align:right;">Qty Terjual</th>
                    <th style="text-align:right;">Uang Masuk</th>
                    <th style="text-align:right;">HPP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grouped as $kodeMaster => $group)
                    @php
                        $first = $group->first();
                        $gQty = (int) $group->sum('qty');
                        $gOrder = (int) $group->sum('total_order');
                        $gUang = (float) $group->sum('uang_masuk');
                        $gHpp = (float) $group->sum('hpp');
                    @endphp
                    <tr style="background:#FEF6F6;">
                        <td colspan="7" style="font-weight:800;color:#1e1b2e;padding:10px 20px;">
                            📦 {{ $first->nama_master ?? '(produk tak dikenal)' }}
                            <span style="color:#9ca3af;font-weight:600;font-size:.75rem;">({{ $kodeMaster }})</span>
                            <span style="float:right;font-weight:700;color:var(--color-primary,#FF6B6B);">
                                {{ number_format($gQty,0,',','.') }} pcs · Rp {{ number_format($gUang,0,',','.') }}
                            </span>
                        </td>
                    </tr>
                    @foreach($group as $r)
                        <tr>
                            <td style="font-weight:700;">
                                {{ $r->kode_varian ?: '-' }}
                                @if((float) $r->power > 0)
                                    <span class="clay-badge clay-badge-blue">+{{ rtrim(rtrim(number_format((float)$r->power, 2, '.', ''), '0'), '.') }}</span>
                                @endif
                            </td>
                            <td style="color:#4b5563;">{{ $r->nama_terjual ?? '-' }}</td>
                            <td style="text-align:center;">{{ number_format($r->qty_per_order,0,',','.') }}</td>
                            <td style="text-align:right;">{{ number_format($r->total_order,0,',','.') }}</td>
                            <td style="text-align:right;font-weight:700;">{{ number_format($r->qty,0,',','.') }}</td>
                            <td style="text-align:right;">Rp {{ number_format((float)$r->uang_masuk,0,',','.') }}</td>
                            <td style="text-align:right;color:#6b7280;">Rp {{ number_format((float)$r->hpp,0,',','.') }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:48px;color:#9ca3af;">Tidak ada order pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot style="background:#FFF5F5;font-weight:800;">
                <tr>
                    <td colspan="4">TOTAL</td>
                    <td style="text-align:right;">{{ number_format($summary->qty,0,',','.') }}</td>
                    <td style="text-align:right;">Rp {{ number_format((float)$summary->uang_masuk,0,',','.') }}</td>
                    <td style="text-align:right;color:#6b7280;">Rp {{ number_format((float)$summary->hpp,0,',','.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div style="font-size:.7rem;color:#9ca3af;margin-top:12px;">
    ⚠️ Qty / Order = jumlah pcs dalam satu order (mis. promo "Dapat 2" → 2). Qty Terjual = total pcs semua order baris itu.
    HPP dihitung dari <code>products.purchase_price × qty</code>.
</div>

{{-- ── Teks siap-copy (dibaca JS) + fallback tampil manual bila clipboard gagal ── --}}
<textarea id="copy-report-text" readonly aria-hidden="true"
          style="position:fixed;left:-9999px;top:0;width:2px;height:2px;opacity:0;">{{ $copyText }}</textarea>
<pre id="copy-report-fallback" style="display:none;margin-top:14px;padding:14px;background:#f8f8fc;border:1px dashed #d1d5db;border-radius:10px;font-size:.78rem;white-space:pre-wrap;word-break:break-word;">{{ $copyText }}</pre>

<script>
(function () {
    var btn = document.getElementById('btn-copy-report');
    var ta = document.getElementById('copy-report-text');
    var fallback = document.getElementById('copy-report-fallback');
    if (!btn || !ta) { return; }

    function fallbackCopy() {
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        ta.focus();
        ta.select();
        ta.setSelectionRange(0, ta.value.length);
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        return ok;
    }

    btn.addEventListener('click', function () {
        var old = btn.textContent;
        function done(ok) {
            btn.textContent = ok ? '✅ Tersalin — paste di WhatsApp!' : '❌ Gagal — blok teks di bawah & salin manual';
            if (!ok && fallback) { fallback.style.display = 'block'; }
            setTimeout(function () {
                btn.textContent = old;
                if (fallback) { fallback.style.display = 'none'; }
            }, 3000);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(ta.value)
                .then(function () { done(true); })
                .catch(function () { done(fallbackCopy()); });
        } else {
            done(fallbackCopy());
        }
    });
})();
</script>

@endsection
