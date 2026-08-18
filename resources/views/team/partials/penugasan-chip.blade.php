{{-- ── Chip CS draggable — dipakai di board penugasan (team/penugasan.blade.php) ── --}}
<div class="cs-chip" draggable="true" data-cs="{{ $cs->id }}">
    <img src="{{ $cs->avatar_url }}" alt="avatar"
         style="width:24px;height:24px;border-radius:7px;object-fit:cover;flex-shrink:0;">
    <div style="min-width:0;line-height:1.15;">
        <div style="font-size:.78rem;font-weight:700;color:#1e1b2e;white-space:nowrap;">{{ $cs->display_name }}</div>
        @if(!$cs->is_active)
        <span class="clay-badge clay-badge-red" style="font-size:.5rem;padding:1px 5px;">Nonaktif</span>
        @endif
    </div>
</div>
