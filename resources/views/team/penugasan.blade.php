@extends('layouts.app')
@section('title','Penugasan CS')
@section('page-title','🎯 Penugasan CS')
@section('page-subtitle','Susun CS ke advertiser untuk satu bulan — rotasi bulanan')

@push('styles')
<style>
/* ── Board drag & drop penugasan ─────────────────────────── */
.penugasan-zone {
    border: 2px dashed #d1d5db;
    border-radius: 14px;
    background: #fafbfc;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: border-color .15s, background .15s, box-shadow .15s;
}
.penugasan-zone.drag-over {
    border-color: #4472C4;
    background: #eef4ff;
    box-shadow: 0 0 0 3px rgba(68,114,196,.18);
}
.penugasan-zone .zone-head {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    padding: 8px 12px; font-size: .78rem; font-weight: 700;
}
.penugasan-zone .zone-count {
    background: rgba(0,0,0,.08);
    border-radius: 20px;
    padding: 1px 8px;
    font-size: .68rem;
    font-weight: 700;
    white-space: nowrap;
}
.penugasan-zone .zone-body {
    display: flex; flex-wrap: wrap; gap: 8px; padding: 12px;
    min-height: 90px; align-content: flex-start;
}
.cs-chip {
    display: flex; align-items: center; gap: 8px;
    background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
    padding: 6px 10px; cursor: grab; user-select: none;
    box-shadow: 0 1px 2px rgba(0,0,0,.05);
    transition: transform .12s, box-shadow .12s, border-color .12s, opacity .12s;
}
.cs-chip:hover { box-shadow: 0 4px 12px rgba(0,0,0,.1); transform: translateY(-1px); }
.cs-chip.dragging { opacity: .4; transform: scale(.96); }
.cs-chip.selected { border-color: #4472C4; box-shadow: 0 0 0 2px rgba(68,114,196,.25); }
.zone-placeholder {
    color: #b6bdc9; font-size: .72rem; text-align: center;
    padding: 22px 8px; width: 100%;
}
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:14px;">

    {{-- Pemilih bulan --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <form method="GET" action="{{ route('team.penugasan') }}" id="form-bulan"
              style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;margin-bottom:4px;color:#6b7280;">📅 Bulan Penugasan</label>
                <input type="month" name="bulan" value="{{ $bulan }}" class="clay-input" style="width:auto;">
            </div>
            <button type="submit" class="clay-btn clay-btn-secondary">Lihat Bulan</button>
            <a href="{{ route('team.admin-index') }}" class="clay-btn clay-btn-outline" data-page-link>← Kembali ke Mapping</a>
            @if($bulan === now()->format('Y-m'))
            <span style="align-self:center;background:#d1fae5;color:#065f46;font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:20px;">✓ Bulan Berjalan</span>
            @endif
        </form>
    </div>

    {{-- Info peringatan --}}
    @if($existing->isNotEmpty())
    <div style="display:flex;gap:10px;align-items:flex-start;background:#FEF3C7;border:1.5px solid rgba(245,158,11,.35);color:#92400e;border-radius:12px;padding:12px 16px;font-size:.82rem;" data-reveal>
        <span>ℹ️</span>
        <div style="flex:1;">
            Bulan <strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $bulan)->translatedFormat('F Y') }}</strong> sudah punya
            <strong>{{ $existing->count() }} penugasan</strong>. Menyimpan akan <strong>mengganti</strong> seluruh penempatan bulan ini.
        </div>
    </div>
    @endif

    @if($bulan !== now()->format('Y-m'))
    <div style="display:flex;gap:10px;align-items:flex-start;background:#EFF6FF;border:1.5px solid rgba(59,130,246,.3);color:#1e40af;border-radius:12px;padding:12px 16px;font-size:.82rem;" data-reveal>
        <span>ℹ️</span>
        <div style="flex:1;">Bulan ini bukan bulan berjalan — penyimpanan hanya mengubah <strong>riwayat penempatan</strong>, tidak mengubah penempatan CS saat ini.</div>
    </div>
    @endif

    {{-- Board drag & drop --}}
    <form method="POST" action="{{ route('team.penugasan.store') }}" id="form-penugasan">
        @csrf
        <input type="hidden" name="bulan" value="{{ $bulan }}">
        <input type="hidden" name="assignments" id="assignments-input" value="">

        <div class="clay-card" style="padding:16px;" data-reveal>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                <span style="font-weight:800;font-size:.9rem;color:#1e1b2e;">🖱️ Susun CS</span>
                <span style="background:#dbeafe;color:#1e40af;font-size:.62rem;font-weight:700;padding:3px 10px;border-radius:20px;">Seret chip CS ke kolom advertiser</span>
                <span style="font-size:.72rem;color:#9ca3af;">Atau klik chip, lalu klik kolom tujuan.</span>
            </div>

            <div id="board-penugasan" style="display:flex;flex-direction:column;gap:14px;">
                {{-- Kolam CS (belum ditugaskan) --}}
                <div class="penugasan-zone" data-zone="pool">
                    <div class="zone-head" style="background:#f1f5f9;color:#475569;">
                        <span>🧺 Belum Ditugaskan</span>
                        <span class="zone-count">0</span>
                    </div>
                    <div class="zone-body">
                        @foreach($csList as $cs)
                            @if(empty($existing[$cs->id]))
                            @include('team.partials.penugasan-chip', ['cs' => $cs])
                            @endif
                        @endforeach
                        <div class="zone-placeholder">Tidak ada CS di sini</div>
                    </div>
                </div>

                {{-- Zona per advertiser --}}
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px;">
                    @foreach($advertisers as $adv)
                    <div class="penugasan-zone" data-zone="{{ $adv->id }}">
                        <div class="zone-head" style="background:#4472C4;color:#fff;">
                            <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                                <img src="{{ $adv->avatar_url }}" alt=""
                                     style="width:20px;height:20px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                                <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $adv->display_name }}</span>
                            </div>
                            <span class="zone-count" style="background:rgba(255,255,255,.22);">0</span>
                        </div>
                        <div class="zone-body">
                            @foreach($csList as $cs)
                                @if(($existing[$cs->id] ?? null) == $adv->id)
                                @include('team.partials.penugasan-chip', ['cs' => $cs])
                                @endif
                            @endforeach
                            <div class="zone-placeholder">Seret CS ke sini</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid rgba(0,0,0,.06);align-items:center;">
                <button type="submit" class="clay-btn clay-btn-primary">💾 Simpan Penugasan</button>
                <span style="font-size:.72rem;color:#9ca3af;" id="simpan-info"></span>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var board = document.getElementById('board-penugasan');
    if (!board) return;
    var form = document.getElementById('form-penugasan');
    var state = {};

    function updateCounts() {
        board.querySelectorAll('[data-zone]').forEach(function (zone) {
            var n = zone.querySelectorAll('.cs-chip').length;
            var count = zone.querySelector('.zone-count');
            if (count) count.textContent = n;
            var ph = zone.querySelector('.zone-placeholder');
            if (ph) ph.style.display = n ? 'none' : '';
        });
        var assigned = Object.keys(state).filter(function (k) { return state[k] && state[k] !== 'pool'; }).length;
        var info = document.getElementById('simpan-info');
        if (info) info.textContent = assigned + ' CS ditugaskan · ' + Object.keys(state).length + ' CS total';
    }

    // Inisialisasi state dari posisi chip saat ini
    board.querySelectorAll('.cs-chip').forEach(function (chip) {
        var zone = chip.closest('[data-zone]');
        state[chip.dataset.cs] = zone ? zone.dataset.zone : 'pool';
    });
    updateCounts();

    // ── Drag & drop ──
    document.addEventListener('dragstart', function (e) {
        var chip = e.target.closest('.cs-chip');
        if (!chip || !board.contains(chip)) return;
        chip.classList.add('dragging');
        e.dataTransfer.setData('text/plain', chip.dataset.cs);
        e.dataTransfer.effectAllowed = 'move';
    });
    document.addEventListener('dragend', function (e) {
        var chip = e.target.closest('.cs-chip');
        if (chip) chip.classList.remove('dragging');
        board.querySelectorAll('.drag-over').forEach(function (z) { z.classList.remove('drag-over'); });
    });
    document.addEventListener('dragover', function (e) {
        var zone = e.target.closest('[data-zone]');
        if (zone && board.contains(zone)) {
            e.preventDefault();
            zone.classList.add('drag-over');
        }
    });
    document.addEventListener('dragleave', function (e) {
        var zone = e.target.closest('[data-zone]');
        if (zone && board.contains(zone)) zone.classList.remove('drag-over');
    });
    document.addEventListener('drop', function (e) {
        var zone = e.target.closest('[data-zone]');
        if (!zone || !board.contains(zone)) return;
        e.preventDefault();
        zone.classList.remove('drag-over');
        var csId = e.dataTransfer.getData('text/plain');
        if (!csId) return;
        var chip = board.querySelector('.cs-chip[data-cs="' + csId + '"]');
        if (!chip) return;
        var body = zone.querySelector('.zone-body');
        if (!body) return;
        body.appendChild(chip);
        state[csId] = zone.dataset.zone;
        updateCounts();
    });

    // ── Fallback klik: pilih chip → klik zona tujuan ──
    var selected = null;
    document.addEventListener('click', function (e) {
        var chip = e.target.closest('.cs-chip');
        if (chip && board.contains(chip)) {
            if (selected === chip.dataset.cs) {
                chip.classList.remove('selected');
                selected = null;
            } else {
                board.querySelectorAll('.cs-chip.selected').forEach(function (c) { c.classList.remove('selected'); });
                chip.classList.add('selected');
                selected = chip.dataset.cs;
            }
            return;
        }
        var zone = e.target.closest('[data-zone]');
        if (zone && board.contains(zone) && selected) {
            var c = board.querySelector('.cs-chip[data-cs="' + selected + '"]');
            if (c) {
                zone.querySelector('.zone-body').appendChild(c);
                state[selected] = zone.dataset.zone;
                c.classList.remove('selected');
                selected = null;
                updateCounts();
            }
            return;
        }
        // Klik di luar board → batal pilih
        board.querySelectorAll('.cs-chip.selected').forEach(function (c) { c.classList.remove('selected'); });
        selected = null;
    });

    // ── Simpan: kumpulkan state jadi JSON ──
    form.addEventListener('submit', function () {
        var out = {};
        Object.keys(state).forEach(function (csId) {
            if (state[csId] && state[csId] !== 'pool') out[csId] = state[csId];
        });
        document.getElementById('assignments-input').value = JSON.stringify(out);
    });
})();
</script>
@endpush
@endsection
