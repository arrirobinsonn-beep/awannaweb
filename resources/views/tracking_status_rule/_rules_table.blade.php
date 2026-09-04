<table class="clay-table">
    <thead>
        <tr>
            <th style="width:64px;text-align:center;">Urutan</th>
            <th>Status Mentah</th>
            <th>Status Sistem</th>
            <th>Masalah</th>
            <th style="text-align:center;">Status</th>
            <th style="width:160px;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rules as $rule)
        <tr style="{{ $rule->is_active ? '' : 'opacity:.55;' }}">
            <td style="text-align:center;font-weight:700;color:#6b7280;">{{ $rule->sort_order }}</td>
            <td>
                <span class="clay-badge ts-badge-raw">{{ $rule->raw_status }}</span>
                @if($rule->match_type === 'contains')
                    <span class="clay-badge ts-badge-type" title="cocok bila status memuat teks ini">~</span>
                @endif
            </td>
            <td><span class="clay-badge ts-badge-st {{ $rule->status }}">{{ $rule->status }}</span></td>
            <td>
                @if($rule->problem_mode === 'required')
                    @php
                        $mtype = $rule->problem_match_type ?? 'contains';
                        $cara = $mtype === 'starts_with' ? 'diawali' : 'mengandung';
                    @endphp
                    <span class="clay-badge ts-badge-required" title="kolom masalah {{ $rule->problem_keyword ? $cara.' "'.$rule->problem_keyword.'"' : 'harus terisi' }}">
                        ⚠ {{ $rule->problem_keyword ? $cara.' '.$rule->problem_keyword : 'terisi' }}
                    </span>
                @else
                    <span class="clay-badge ts-badge-none">—</span>
                @endif
            </td>
            <td style="text-align:center;">
                <button type="button" class="ts-toggle {{ $rule->is_active ? 'on' : 'off' }} ts-toggle-btn"
                        data-id="{{ $rule->id }}"
                        title="Klik untuk {{ $rule->is_active ? 'menonaktifkan' : 'mengaktifkan' }}">
                    {{ $rule->is_active ? '● Aktif' : '○ Nonaktif' }}
                </button>
            </td>
            <td>
                <div class="ts-aksi">
                    <button type="button" class="ts-move ts-move-btn" data-id="{{ $rule->id }}" data-dir="up"
                            title="Naikkan prioritas" {{ $loop->first ? 'disabled' : '' }}>↑</button>
                    <button type="button" class="ts-move ts-move-btn" data-id="{{ $rule->id }}" data-dir="down"
                            title="Turunkan prioritas" {{ $loop->last ? 'disabled' : '' }}>↓</button>

                    <button type="button" class="ts-edit-btn" id="ts-edit-{{ $rule->id }}"
                            onclick="openTsEdit({{ $rule->id }})"
                            data-sort="{{ $rule->sort_order }}"
                            data-raw="{{ $rule->raw_status }}"
                            data-match="{{ $rule->match_type }}"
                            data-status="{{ $rule->status }}"
                            data-problem="{{ $rule->problem_mode }}"
                            data-keyword="{{ $rule->problem_keyword ?? '' }}"
                            data-mtype="{{ $rule->problem_match_type ?? 'contains' }}"
                            data-active="{{ $rule->is_active ? '1' : '' }}">✏️ Edit</button>

                    <button type="button" class="ts-del-btn ts-del-btn-ajax"
                            data-id="{{ $rule->id }}"
                            data-confirm="Hapus aturan {{ $rule->raw_status }} → {{ $rule->status }}?">🗑 Hapus</button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;">
                Belum ada aturan status untuk {{ strtoupper($source) }}. Tambahkan di form bawah.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
