@props([
    'dari'        => now()->startOfMonth()->format('Y-m-d'),
    'sampai'      => now()->format('Y-m-d'),
    'formId'      => 'drp-form',
    'inputDari'   => 'dari',
    'inputSampai' => 'sampai',
    'extraInputs' => '',
])

@php
    $pid = 'drp-' . substr(md5(uniqid()), 0, 8);
    $presets = [
        ['label'=>'Kemarin',          'key'=>'kemarin'],
        ['label'=>'Hari ini',         'key'=>'today'],
        ['label'=>'Bulan ini',        'key'=>'month'],
        ['label'=>'Bulan lalu',       'key'=>'lmonth'],
        ['label'=>'7 Hari Terakhir',  'key'=>'7d'],
        ['label'=>'30 Hari Terakhir', 'key'=>'30d'],
        ['label'=>'90 Hari Terakhir', 'key'=>'90d'],
    ];
@endphp

{{-- Hidden inputs (di dalam form) --}}
<input type="hidden" name="{{ $inputDari }}"   id="{{ $pid }}-dari"   value="{{ $dari }}">
<input type="hidden" name="{{ $inputSampai }}" id="{{ $pid }}-sampai" value="{{ $sampai }}">
{!! $extraInputs !!}

{{-- Trigger button (di dalam form) — min-width di CSS .drp-trigger agar bisa menyusut di layar kecil --}}
<button type="button" onclick="DRP.open('{{ $pid }}')"
        class="clay-btn clay-btn-outline drp-trigger"
        style="gap:8px;white-space:nowrap;justify-content:flex-start;">
    <span>📅</span>
    <span id="{{ $pid }}-label" class="drp-label" style="font-size:.83rem;font-weight:600;color:#374151;">
        {{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}
    </span>
    <span style="margin-left:auto;font-size:.7rem;color:#9ca3af;">▼</span>
</button>

{{--
    POPUP — di-push ke stack 'body-end' agar dirender tepat sebelum </body>
    sehingga TIDAK berada di dalam elemen apapun yang punya transform/overflow.
    Ini menyelesaikan masalah popup terpotong oleh sidebar yang punya transition:transform.
--}}
@push('body-end')
<div id="{{ $pid }}-popup" class="drp-popup"
     style="display:none;position:fixed;z-index:99999;
            top:0;left:0;width:100vw;height:100vh;
            background:rgba(0,0,0,.42);"
     onclick="if(event.target===this)DRP.close('{{ $pid }}')">

    <div class="drp-popup-panel" style="background:#fff;border-radius:20px;
                box-shadow:12px 12px 0 rgba(0,0,0,.1);
                border:2px solid rgba(0,0,0,.07);
                font-family:inherit;overflow:hidden;"
         onclick="event.stopPropagation()">
        <div class="drp-panel-inner" style="display:flex;">

            {{-- Preset shortcuts --}}
            <div class="drp-presets" style="width:155px;flex-shrink:0;
                        border-right:1.5px solid rgba(0,0,0,.07);
                        padding:12px 0;background:#fafafa;overflow-y:auto;">
                @foreach($presets as $ps)
                <button type="button"
                        class="drp-pre"
                        data-drp="{{ $pid }}"
                        data-key="{{ $ps['key'] }}"
                        onclick="DRP.applyPreset('{{ $pid }}','{{ $ps['key'] }}')"
                        style="display:block;width:100%;text-align:left;
                               padding:10px 18px;border:none;background:transparent;
                               cursor:pointer;font-size:.82rem;font-weight:500;
                               color:#6b7280;font-family:inherit;transition:all .12s;">
                    {{ $ps['label'] }}
                </button>
                @endforeach
            </div>

            {{-- Calendar area --}}
            <div class="drp-calarea" style="flex:1;padding:18px 20px 0;min-width:0;">

                {{-- Month nav --}}
                <div style="display:flex;align-items:center;
                            justify-content:space-between;margin-bottom:14px;">
                    <button type="button" onclick="DRP.prevMonth('{{ $pid }}')"
                            style="background:none;border:none;cursor:pointer;
                                   font-size:1.1rem;color:#6b7280;
                                   padding:4px 10px;border-radius:8px;line-height:1;"
                            onmouseenter="this.style.background='#f3f4f6'"
                            onmouseleave="this.style.background='transparent'">←</button>

                    <div style="display:flex;gap:32px;flex:1;justify-content:center;">
                        <span id="{{ $pid }}-ml"
                              style="font-weight:700;font-size:.88rem;color:#1e1b2e;
                                     min-width:110px;text-align:center;"></span>
                        <span id="{{ $pid }}-mr"
                              style="font-weight:700;font-size:.88rem;color:#1e1b2e;
                                     min-width:110px;text-align:center;"></span>
                    </div>

                    <button type="button" onclick="DRP.nextMonth('{{ $pid }}')"
                            style="background:none;border:none;cursor:pointer;
                                   font-size:1.1rem;color:#6b7280;
                                   padding:4px 10px;border-radius:8px;line-height:1;"
                            onmouseenter="this.style.background='#f3f4f6'"
                            onmouseleave="this.style.background='transparent'">→</button>
                </div>

                {{-- Dual calendar --}}
                <div class="drp-cals" style="display:flex;gap:16px;overflow-x:auto;">
                    <div id="{{ $pid }}-cal-l" style="flex:1;min-width:232px;"></div>
                    <div id="{{ $pid }}-cal-r" style="flex:1;min-width:232px;"></div>
                </div>

                {{-- Footer --}}
                <div class="drp-footer" style="display:flex;justify-content:flex-end;gap:8px;
                            padding:14px 0 16px;margin-top:12px;
                            border-top:1px solid rgba(0,0,0,.06);">
                    <button type="button" onclick="DRP.close('{{ $pid }}')"
                            class="clay-btn clay-btn-outline"
                            style="padding:7px 18px;font-size:.82rem;">Batal</button>
                    <button type="button"
                            onclick="DRP.applyAndSubmit('{{ $pid }}','{{ $formId }}')"
                            class="clay-btn clay-btn-primary"
                            style="padding:7px 18px;font-size:.82rem;">Terapkan</button>
                </div>
            </div>

        </div>
    </div>
</div>
@endpush

@push('styles')
<style>
    /* ── Responsive Date Range Picker ─────────────────────────────── */
    .drp-trigger { min-width: 220px; }
    /* Label trigger: boleh menyusut & terpotong (…) saat ruang sempit */
    .drp-trigger .drp-label {
        flex: 1; min-width: 0;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .drp-popup { display: none; align-items: center; justify-content: center; }
    .drp-popup-panel { width: min(700px, 96vw); max-height: 90vh; }

    @media (max-width: 640px) {
        /* Trigger menyusut (dipakai .filter-bar untuk tetap 1 jajar) */
        .drp-trigger { min-width: 0; }
        .drp-trigger .drp-label { font-size: .74rem !important; }

        /* Popup jadi bottom-sheet: preset di atas (chips), kalender di bawah */
        .drp-popup { align-items: flex-end; }
        .drp-popup-panel {
            width: 100vw !important;
            border-radius: 18px 18px 0 0 !important;
            overflow-y: auto !important;
        }
        .drp-panel-inner { flex-direction: column; }
        .drp-presets {
            width: 100% !important;
            display: flex;
            flex-direction: row; overflow-x: auto;
            border-right: none !important;
            border-bottom: 1.5px solid rgba(0,0,0,.07);
            padding: 8px 0 !important;
            flex-shrink: 0;
            -webkit-overflow-scrolling: touch;
        }
        .drp-presets button { width: auto !important; white-space: nowrap; padding: 8px 14px !important; }
        .drp-calarea { padding: 14px 14px 0 !important; display: flex; flex-direction: column; }
        .drp-cals { flex-direction: column; }
        .drp-cals > div { min-width: 0 !important; }
        /* Footer tetap terlihat di bawah sheet saat kalender panjang */
        .drp-footer {
            position: sticky; bottom: 0;
            background: #fff;
            margin-top: auto;
        }
    }
</style>
@endpush
