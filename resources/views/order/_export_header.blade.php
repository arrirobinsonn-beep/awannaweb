@if($selectedBatch && !$isCs)
<div style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;border-bottom:1px solid rgba(0,0,0,.06);">
  <div>
    <h2 style="margin:0;font-size:1rem;font-weight:800;">🚚 {{ $selectedBatch->original_filename }}</h2>
    <div style="font-size:.72rem;color:#9ca3af;">
      {{ $selectedBatch->created_at?->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}{{ $selectedBatch->sender ? ' ('.$selectedBatch->sender.')' : '' }}
      · Total {{ $selectedBatch->total_rows }} · Sukses {{ $selectedBatch->success_rows }}
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    @foreach($exportTemplates as $et)
      {{-- Daftar courier template = kolom `export_templates.couriers` (array, bisa diedit
           admin di halaman /export-mapping/{id}/edit) — TIDAK hardcode. --}}
      @php
          $etCouriers = array_values(array_unique(array_filter(
              $et->couriers ?? [],
              fn ($c) => trim((string) $c) !== ''
          )));
      @endphp
      @if(count($etCouriers) > 1)
        <details style="position:relative;" data-couriers="{{ json_encode($etCouriers) }}">
          <summary class="clay-btn" style="padding:6px 12px;font-size:.78rem;cursor:pointer;">📗 Export {{ $et->name }} ▾</summary>
          <div style="position:absolute;right:0;top:100%;margin-top:4px;background:#fff;border:1px solid #eee;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,.12);min-width:200px;z-index:50;padding:6px;">
            @foreach($etCouriers as $fc)
              @php $fcCount = (int) ($courierCounts[$fc] ?? 0); @endphp
              <a href="{{ route('orders.export', [$selectedBatch->id, $et->key, $fc]) }}"
                 style="display:block;padding:7px 10px;font-size:.76rem;color:#374151;text-decoration:none;border-radius:6px;{{ $fcCount === 0 ? 'opacity:.55;' : '' }}">
                {{ $et->name }} — {{ $fc }} <span style="color:#9ca3af;">({{ $fcCount }})</span>
              </a>
            @endforeach
          </div>
        </details>
      @else
        @php $etIcon = $et->key === 'sicepat' ? '📘' : ($et->key === 'spx' ? '📙' : '📦'); @endphp
        <a href="{{ route('orders.export', [$selectedBatch->id, $et->key]) }}" class="clay-btn" style="padding:6px 12px;font-size:.78rem;">{{ $etIcon }} Export {{ $et->name }}</a>
      @endif
    @endforeach
  </div>
</div>
@endif
