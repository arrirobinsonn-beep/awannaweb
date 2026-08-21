@extends('layouts.app')

@section('title', 'Transfer Antar Akun')
@section('page-title', '🔁 Transfer Antar Akun')
@section('page-subtitle', 'Operan saldo antar akun — dari agregator ke rekening, rekening ke cash, dst.')

@push('styles')
<style>
    .tr-grid { display: grid; grid-template-columns: 380px minmax(0, 1fr); gap: 16px; align-items: start; }
    @media (max-width: 1023px) { .tr-grid { grid-template-columns: 1fr; } }

    .tr-form label { display: block; font-size: .72rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
    .tr-form .tr-field { margin-bottom: 12px; }
    .tr-form .clay-input, .tr-form select { width: 100%; font-size: .8rem; }

    .tr-flow { display: flex; align-items: center; gap: 8px; font-size: .82rem; font-weight: 700; color: #1e1b2e; }
    .tr-arrow { color: var(--color-primary, #FF6B6B); font-size: 1rem; }
    .tr-del-form { display: inline; }
    .tr-del-btn { background: none; border: none; color: #dc2626; font-weight: 700; font-size: .76rem; cursor: pointer; padding: 2px 6px; }
    .tr-del-btn:hover { text-decoration: underline; }
    @media (max-width: 479px) {
        .tr-table-wrap { overflow-x: auto; }
        .tr-table-wrap .clay-table { min-width: 640px; }
    }
</style>
@endpush

@section('content')

<div class="tr-grid">

    {{-- ── Form Transfer ───────────────────────────────────────── --}}
    <div class="clay-card" style="padding:16px;" data-reveal>
        <h2 style="margin:0 0 4px;font-size:1rem;font-weight:800;">🔄 Transfer Saldo</h2>
        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:14px;">
            Dari akun ke akun — saldo kedua akun otomatis berubah.
        </div>

        <form method="POST" action="{{ route('finance.transfers.store') }}" class="tr-form">
            @csrf

            <div class="tr-field">
                <label>Dari Akun *</label>
                <select name="from_account_id" class="clay-input" required>
                    <option value="">— pilih akun —</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('from_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->name }} (Rp {{ number_format((float) $account->current_balance, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="tr-field">
                <label>Ke Akun *</label>
                <select name="to_account_id" class="clay-input" required>
                    <option value="">— pilih akun —</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('to_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="tr-field">
                <label>Jumlah (Rp) *</label>
                <input type="number" name="amount" class="clay-input" step="0.01" min="0.01"
                       placeholder="mis. 1000000" value="{{ old('amount') }}" required>
                <div style="font-size:.66rem;color:#9ca3af;margin-top:3px;">Saldo akun asal harus cukup.</div>
            </div>

            <div class="tr-field">
                <label>Tanggal Transfer *</label>
                <input type="date" name="transfer_date" class="clay-input" value="{{ old('transfer_date', now()->format('Y-m-d')) }}" required>
            </div>

            <div class="tr-field">
                <label>Keterangan</label>
                <textarea name="description" class="clay-input" rows="2" maxlength="500"
                          placeholder="mis. pindah saldo aggregator ke BCA">{{ old('description') }}</textarea>
            </div>

            <button type="submit" class="clay-btn clay-btn-primary" style="width:100%;">↔ Transfer Sekarang</button>
        </form>
    </div>

    {{-- ── Riwayat Transfer ────────────────────────────────────── --}}
    <div class="clay-card" style="overflow:hidden;" data-reveal>
        <div style="padding:12px 18px;font-weight:800;font-size:.9rem;border-bottom:1px solid rgba(0,0,0,.06);
                    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span>🗂 Riwayat Transfer</span>
        </div>

        <div class="tr-table-wrap">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Alur</th>
                        <th style="text-align:right;">Jumlah</th>
                        <th>Keterangan</th>
                        <th>Dibuat oleh</th>
                        <th style="width:80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                    <tr>
                        <td style="white-space:nowrap;">{{ $transfer->transfer_date->format('d M Y H:i') }}</td>
                        <td>
                            <div class="tr-flow">
                                <span>{{ $transfer->fromAccount?->name ?? '—' }}</span>
                                <span class="tr-arrow">→</span>
                                <span>{{ $transfer->toAccount?->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td style="text-align:right;font-weight:700;white-space:nowrap;">
                            Rp {{ number_format((float) $transfer->amount, 0, ',', '.') }}
                        </td>
                        <td style="max-width:220px;">
                            <div style="font-size:.75rem;color:#4b5563;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $transfer->description }}">
                                {{ $transfer->description ?: '—' }}
                            </div>
                        </td>
                        <td style="font-size:.75rem;color:#6b7280;">{{ $transfer->creator?->display_name ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('finance.transfers.destroy', $transfer) }}" class="tr-del-form"
                                  data-confirm="Hapus transfer Rp {{ number_format((float) $transfer->amount, 0, ',', '.') }}? Saldo kedua akun akan dikembalikan.">
                                @csrf @method('DELETE')
                                <button type="submit" class="tr-del-btn">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:48px;color:#9ca3af;">
                            Belum ada transfer antar akun.
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

@endsection

@push('scripts')
<script>
document.querySelectorAll('.tr-del-form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
        if (!window.confirm(f.dataset.confirm)) e.preventDefault();
    });
});
</script>
@endpush