/**
 * date-range-picker.js — Date Range Picker terpusat webAwanna
 * Fitur: dual calendar, preset shortcuts, highlight range, claymorphism style
 */

const DRP = (function () {

    // ── State per picker instance ──────────────────────────────
    const state = {};

    const MONTHS_ID = [
        'Januari','Februari','Maret','April','Mei','Juni',
        'Juli','Agustus','September','Oktober','November','Desember'
    ];
    const DAYS_ID = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];

    // ── CSS styles diinjeksikan sekali ─────────────────────────
    function injectStyles() {
        if (document.getElementById('drp-styles')) return;
        const s = document.createElement('style');
        s.id = 'drp-styles';
        s.textContent = `
            .drp-day {
                width: 30px; height: 30px;
                display: flex; align-items: center; justify-content: center;
                border-radius: 8px; font-size: .8rem; cursor: pointer;
                transition: background .12s, color .12s;
                user-select: none;
            }
            .drp-day:hover:not(.drp-empty):not(.drp-disabled) {
                background: rgba(255,107,107,.15);
                color: var(--color-primary, #FF6B6B);
            }
            .drp-day.drp-in-range {
                background: rgba(255,107,107,.1);
                border-radius: 0;
            }
            .drp-day.drp-start, .drp-day.drp-end {
                background: var(--color-primary, #FF6B6B) !important;
                color: #fff !important;
                font-weight: 700;
                border-radius: 8px !important;
                box-shadow: 0 3px 0 #e05555;
            }
            .drp-day.drp-today:not(.drp-start):not(.drp-end) {
                font-weight: 700;
                color: var(--color-primary, #FF6B6B);
            }
            .drp-day.drp-disabled {
                opacity: .3; cursor: default;
            }
            .drp-day.drp-sunday:not(.drp-start):not(.drp-end) {
                color: #ef4444;
            }
            .drp-day.drp-weekend-sat:not(.drp-start):not(.drp-end) {
                color: #3b82f6;
            }
            .drp-preset-btn:hover {
                background: rgba(255,107,107,.08) !important;
                color: var(--color-primary, #FF6B6B) !important;
            }
            .drp-preset-btn.drp-preset-active {
                background: rgba(255,107,107,.12) !important;
                color: var(--color-primary, #FF6B6B) !important;
                font-weight: 700 !important;
            }
        `;
        document.head.appendChild(s);
    }

    // ── Helpers ────────────────────────────────────────────────
    function parseDate(str) {
        if (!str) return null;
        const [y, m, d] = str.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    function formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function formatLabel(str) {
        if (!str) return '';
        const d = parseDate(str);
        return `${d.getDate()} ${MONTHS_ID[d.getMonth()].substring(0,3)} ${d.getFullYear()}`;
    }

    function sameDay(a, b) {
        return a && b &&
            a.getFullYear() === b.getFullYear() &&
            a.getMonth()    === b.getMonth()    &&
            a.getDate()     === b.getDate();
    }

    function startOfMonth(y, m) { return new Date(y, m, 1); }

    // ── Render satu bulan ──────────────────────────────────────
    function renderMonth(pickerId, year, month, side) {
        const st = state[pickerId];
        const el = document.getElementById(`${pickerId}-cal-${side}`);
        if (!el) return;

        const startDate = parseDate(st.tempStart || st.start);
        const endDate   = parseDate(st.tempEnd   || st.end);
        const today     = new Date(); today.setHours(0,0,0,0);

        // First day of month (0=Mon..6=Sun in ISO) — Monday first
        const firstDay = new Date(year, month, 1);
        let dow = firstDay.getDay(); // 0=Sun
        dow = dow === 0 ? 6 : dow - 1; // Convert to Mon=0

        const daysInMonth = new Date(year, month + 1, 0).getDate();

        let html = `<div style="display:grid;grid-template-columns:repeat(7,30px);gap:2px;justify-content:center;">`;

        // Header hari
        DAYS_ID.forEach((d, i) => {
            const color = i === 6 ? '#ef4444' : (i === 5 ? '#3b82f6' : '#9ca3af');
            html += `<div style="width:30px;text-align:center;font-size:.68rem;font-weight:700;
                                 color:${color};margin-bottom:4px;">${d}</div>`;
        });

        // Blank cells
        for (let i = 0; i < dow; i++) {
            html += `<div class="drp-day drp-empty"></div>`;
        }

        // Day cells
        for (let d = 1; d <= daysInMonth; d++) {
            const date = new Date(year, month, d);
            date.setHours(0,0,0,0);
            const dateStr = formatDate(date);

            let cls = 'drp-day';
            const dayOfWeek = date.getDay();
            if (dayOfWeek === 0) cls += ' drp-sunday';
            if (dayOfWeek === 6) cls += ' drp-weekend-sat';
            if (sameDay(date, today)) cls += ' drp-today';
            if (startDate && sameDay(date, startDate)) cls += ' drp-start';
            if (endDate   && sameDay(date, endDate))   cls += ' drp-end';
            if (startDate && endDate &&
                date > startDate && date < endDate)     cls += ' drp-in-range';

            html += `<div class="${cls}" data-date="${dateStr}"
                         onclick="DRP._clickDay('${pickerId}','${dateStr}')"
                         onmouseenter="DRP._hoverDay('${pickerId}','${dateStr}')">${d}</div>`;
        }

        html += `</div>`;
        el.innerHTML = html;
    }

    // ── Update headers bulan ────────────────────────────────────
    function updateHeaders(pickerId) {
        const st = state[pickerId];
        const lbl = (y, m) => `${MONTHS_ID[m]} ${y}`;
        const lEl = document.getElementById(`${pickerId}-month-l`);
        const rEl = document.getElementById(`${pickerId}-month-r`);
        if (lEl) lEl.textContent = lbl(st.viewYearL, st.viewMonthL);
        if (rEl) rEl.textContent = lbl(st.viewYearR, st.viewMonthR);
    }

    // ── Render kedua bulan ──────────────────────────────────────
    function renderBoth(pickerId) {
        const st = state[pickerId];
        renderMonth(pickerId, st.viewYearL, st.viewMonthL, 'l');
        renderMonth(pickerId, st.viewYearR, st.viewMonthR, 'r');
        updateHeaders(pickerId);
        updatePresetHighlight(pickerId);
        updateLabel(pickerId);
    }

    // ── Update label tombol trigger ────────────────────────────
    function updateLabel(pickerId) {
        const st  = state[pickerId];
        const lEl = document.getElementById(`${pickerId}-label`);
        if (!lEl) return;
        const s = st.tempStart || st.start;
        const e = st.tempEnd   || st.end;
        if (s && e) {
            lEl.textContent = `${formatLabel(s)} — ${formatLabel(e)}`;
        } else if (s) {
            lEl.textContent = `${formatLabel(s)} — ...`;
        }
    }

    // ── Update highlight preset aktif ──────────────────────────
    function updatePresetHighlight(pickerId) {
        const st = state[pickerId];
        document.querySelectorAll(`.drp-preset-btn[data-picker="${pickerId}"]`).forEach(btn => {
            btn.classList.toggle('drp-preset-active',
                btn.getAttribute('data-key') === st.activePreset);
        });
    }

    // ── Klik hari ──────────────────────────────────────────────
    function _clickDay(pickerId, dateStr) {
        const st = state[pickerId];
        st.activePreset = null;

        if (!st.selecting || (st.tempStart && st.tempEnd)) {
            // Mulai seleksi baru
            st.selecting  = true;
            st.tempStart  = dateStr;
            st.tempEnd    = null;
        } else {
            // Selesai seleksi
            const s = parseDate(st.tempStart);
            const e = parseDate(dateStr);
            if (e < s) {
                st.tempStart = dateStr;
                st.tempEnd   = formatDate(s);
            } else {
                st.tempEnd   = dateStr;
            }
            st.selecting = false;
        }
        renderBoth(pickerId);
    }

    // ── Hover hari (preview range) ─────────────────────────────
    function _hoverDay(pickerId, dateStr) {
        const st = state[pickerId];
        if (!st.selecting || !st.tempStart) return;
        const s = parseDate(st.tempStart);
        const h = parseDate(dateStr);
        if (h < s) {
            st.tempEnd = null;
        } else {
            st.tempEnd = dateStr;
        }
        renderBoth(pickerId);
    }

    // ── Presets ────────────────────────────────────────────────
    function applyPreset(pickerId, key) {
        const now = new Date();
        let s, e;
        const fmt = formatDate;

        const startOf = (y, m) => new Date(y, m, 1);
        const endOf   = (y, m) => new Date(y, m + 1, 0);

        switch (key) {
            case 'kemarin': {
                const y = new Date(now); y.setDate(now.getDate() - 1);
                s = e = fmt(y); break;
            }
            case 'hari_ini':
                s = e = fmt(now); break;
            case 'bulan_ini':
                s = fmt(startOf(now.getFullYear(), now.getMonth()));
                e = fmt(now); break;
            case 'bulan_lalu': {
                const lm = now.getMonth() === 0 ? 11 : now.getMonth() - 1;
                const ly = now.getMonth() === 0 ? now.getFullYear() - 1 : now.getFullYear();
                s = fmt(startOf(ly, lm));
                e = fmt(endOf(ly, lm)); break;
            }
            case '7hari': {
                const d = new Date(now); d.setDate(now.getDate() - 6);
                s = fmt(d); e = fmt(now); break;
            }
            case '30hari': {
                const d = new Date(now); d.setDate(now.getDate() - 29);
                s = fmt(d); e = fmt(now); break;
            }
            case '90hari': {
                const d = new Date(now); d.setDate(now.getDate() - 89);
                s = fmt(d); e = fmt(now); break;
            }
            default: return;
        }

        const st = state[pickerId];
        st.tempStart    = s;
        st.tempEnd      = e;
        st.selecting    = false;
        st.activePreset = key;

        // Navigasi kalender ke bulan mulai
        const sd = parseDate(s);
        st.viewYearL  = sd.getFullYear();
        st.viewMonthL = sd.getMonth();
        const rd = new Date(sd.getFullYear(), sd.getMonth() + 1, 1);
        st.viewYearR  = rd.getFullYear();
        st.viewMonthR = rd.getMonth();

        renderBoth(pickerId);
    }

    // ── Navigasi bulan ──────────────────────────────────────────
    function prevMonth(pickerId) {
        const st = state[pickerId];
        const d = new Date(st.viewYearL, st.viewMonthL - 1, 1);
        st.viewYearL  = d.getFullYear();
        st.viewMonthL = d.getMonth();
        const r = new Date(d.getFullYear(), d.getMonth() + 1, 1);
        st.viewYearR  = r.getFullYear();
        st.viewMonthR = r.getMonth();
        renderBoth(pickerId);
    }

    function nextMonth(pickerId) {
        const st = state[pickerId];
        const d = new Date(st.viewYearL, st.viewMonthL + 1, 1);
        st.viewYearL  = d.getFullYear();
        st.viewMonthL = d.getMonth();
        const r = new Date(d.getFullYear(), d.getMonth() + 1, 1);
        st.viewYearR  = r.getFullYear();
        st.viewMonthR = r.getMonth();
        renderBoth(pickerId);
    }

    // ── Buka popup ──────────────────────────────────────────────
    function open(pickerId) {
        injectStyles();

        const dariEl   = document.getElementById(`${pickerId}-dari`);
        const sampaiEl = document.getElementById(`${pickerId}-sampai`);

        const s = dariEl?.value   || formatDate(new Date());
        const e = sampaiEl?.value || formatDate(new Date());
        const sd = parseDate(s);

        state[pickerId] = {
            start:      s,
            end:        e,
            tempStart:  s,
            tempEnd:    e,
            selecting:  false,
            activePreset: null,
            viewYearL:  sd.getFullYear(),
            viewMonthL: sd.getMonth(),
            viewYearR:  sd.getMonth() === 11 ? sd.getFullYear() + 1 : sd.getFullYear(),
            viewMonthR: sd.getMonth() === 11 ? 0 : sd.getMonth() + 1,
        };

        const popup = document.getElementById(`${pickerId}-popup`);
        if (popup) { popup.style.display = 'flex'; }

        renderBoth(pickerId);
    }

    // ── Tutup popup ─────────────────────────────────────────────
    function close(pickerId) {
        const popup = document.getElementById(`${pickerId}-popup`);
        if (popup) popup.style.display = 'none';
        // Reset temp ke nilai sebelum dibuka
        if (state[pickerId]) {
            state[pickerId].tempStart = state[pickerId].start;
            state[pickerId].tempEnd   = state[pickerId].end;
            state[pickerId].selecting = false;
        }
    }

    // ── Terapkan & submit form ──────────────────────────────────
    function applyAndSubmit(pickerId, formId) {
        const st = state[pickerId];
        if (!st || !st.tempStart || !st.tempEnd) return;

        // Simpan ke state
        st.start = st.tempStart;
        st.end   = st.tempEnd;

        // Tulis ke hidden input
        const dariEl   = document.getElementById(`${pickerId}-dari`);
        const sampaiEl = document.getElementById(`${pickerId}-sampai`);
        if (dariEl)   dariEl.value   = st.start;
        if (sampaiEl) sampaiEl.value = st.end;

        // Update label
        const lEl = document.getElementById(`${pickerId}-label`);
        if (lEl) lEl.textContent = `${formatLabel(st.start)} — ${formatLabel(st.end)}`;

        // Tutup popup
        close(pickerId);

        // Submit form
        const form = document.getElementById(formId);
        if (form) form.submit();
    }

    // ── Tutup saat klik backdrop ────────────────────────────────
    document.addEventListener('click', function (e) {
        if (e.target.matches('[id$="-popup"]')) {
            const pickerId = e.target.id.replace('-popup', '');
            close(pickerId);
        }
    });

    // ── Public API ──────────────────────────────────────────────
    return { open, close, prevMonth, nextMonth, applyPreset, applyAndSubmit, _clickDay, _hoverDay };
})();

export default DRP;
window.DRP = DRP; // expose globally
