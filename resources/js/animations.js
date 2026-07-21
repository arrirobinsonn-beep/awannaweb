/**
 * animations.js — Otak terpusat transisi halaman & animasi elemen
 * webAwanna Management System
 *
 * Fitur:
 *  1. Page transition  — fade+slide saat navigasi antar halaman
 *  2. Stagger entrance — elemen muncul berurutan saat halaman dimuat
 *  3. Counter animation — angka stat card naik dari 0
 *  4. Flash auto-dismiss — notifikasi hilang otomatis
 *  5. Micro-interaction — ripple effect pada tombol
 *  6. Scroll reveal     — elemen muncul saat masuk viewport
 *  7. Chart pulse       — animasi bar/donut chart entry
 */

/* ─── 1. PAGE TRANSITION ───────────────────────────────────────────────── */

const PageTransition = {
    duration: 300, // ms

    /** Panggil saat halaman pertama kali dimuat — hanya area konten */
    enter() {
        const content = document.getElementById('main-content');
        if (!content) return;

        content.style.opacity   = '0';
        content.style.transform = 'translateY(14px)';
        content.style.transition = `opacity ${this.duration}ms ease, transform ${this.duration}ms ease`;

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                content.style.opacity   = '1';
                content.style.transform = 'translateY(0)';
            });
        });
    },

    /** Panggil sebelum navigasi keluar — hanya area konten */
    leave(callback) {
        const content = document.getElementById('main-content');
        if (!content) { callback(); return; }

        content.style.transition = `opacity ${this.duration / 2}ms ease, transform ${this.duration / 2}ms ease`;
        content.style.opacity   = '0';
        content.style.transform = 'translateY(-8px)';

        setTimeout(callback, this.duration / 2);
    },
};

/* ─── 2. STAGGER ENTRANCE ──────────────────────────────────────────────── */

const StaggerEntrance = {
    /**
     * Animasikan semua elemen dengan atribut [data-stagger]
     * Urutan berdasarkan nilai atributnya: data-stagger="1", "2", dst.
     */
    run() {
        const elements = document.querySelectorAll('[data-stagger]');
        elements.forEach(el => {
            const order = parseInt(el.getAttribute('data-stagger')) || 1;
            const delay  = (order - 1) * 80; // 80ms antar elemen

            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = `opacity 0.4s ease ${delay}ms, transform 0.4s ease ${delay}ms`;

            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 50);
        });
    },

    /**
     * Animasikan semua elemen [data-animate-in] sebagai satu grup
     */
    runGroup() {
        const groups = document.querySelectorAll('[data-animate-in]');
        groups.forEach((group, groupIdx) => {
            const children = group.querySelectorAll('[data-stagger]');
            if (children.length === 0) {
                // Animasikan groupnya langsung
                group.style.opacity = '0';
                group.style.transform = 'translateY(24px)';
                group.style.transition = `opacity 0.45s ease ${groupIdx * 60}ms, transform 0.45s ease ${groupIdx * 60}ms`;
                setTimeout(() => {
                    group.style.opacity = '1';
                    group.style.transform = 'translateY(0)';
                }, 50);
            }
            children.forEach(el => {
                const order = parseInt(el.getAttribute('data-stagger')) || 1;
                const delay = groupIdx * 60 + (order - 1) * 80;
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = `opacity 0.4s ease ${delay}ms, transform 0.4s ease ${delay}ms`;
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 50);
            });
        });
    },
};

/* ─── 3. COUNTER ANIMATION ─────────────────────────────────────────────── */

const CounterAnimation = {
    /**
     * Animasikan semua elemen [data-counter]
     * <span data-counter="12500" data-prefix="Rp " data-suffix=" rb">0</span>
     */
    run() {
        const counters = document.querySelectorAll('[data-counter]');
        counters.forEach(el => {
            const target   = parseFloat(el.getAttribute('data-counter')) || 0;
            const prefix   = el.getAttribute('data-prefix') || '';
            const suffix   = el.getAttribute('data-suffix') || '';
            const duration = parseInt(el.getAttribute('data-duration')) || 1200;
            const decimals = (target % 1 !== 0) ? 2 : 0;

            let start    = null;
            const startVal = 0;

            function step(timestamp) {
                if (!start) start = timestamp;
                const progress = Math.min((timestamp - start) / duration, 1);
                // Ease out quad
                const eased = 1 - (1 - progress) * (1 - progress);
                const current = startVal + (target - startVal) * eased;
                el.textContent = prefix + current.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + suffix;
                if (progress < 1) requestAnimationFrame(step);
            }

            requestAnimationFrame(step);
        });
    },
};

/* ─── 4. FLASH AUTO-DISMISS ────────────────────────────────────────────── */

const FlashDismiss = {
    delay: 4500, // ms sebelum hilang otomatis

    run() {
        const flashes = document.querySelectorAll('[data-flash]');
        flashes.forEach(el => {
            setTimeout(() => {
                el.style.transition = 'opacity 0.4s ease, transform 0.4s ease, max-height 0.4s ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-8px)';
                el.style.maxHeight = '0';
                el.style.overflow = 'hidden';
                setTimeout(() => el.remove(), 400);
            }, this.delay);
        });
    },
};

/* ─── 5. RIPPLE EFFECT ─────────────────────────────────────────────────── */

const RippleEffect = {
    run() {
        document.addEventListener('click', e => {
            const btn = e.target.closest('.clay-btn');
            if (!btn) return;

            const rect   = btn.getBoundingClientRect();
            const size   = Math.max(rect.width, rect.height);
            const x      = e.clientX - rect.left - size / 2;
            const y      = e.clientY - rect.top - size / 2;

            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255,255,255,0.35);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple-anim 0.5s ease-out forwards;
                pointer-events: none;
            `;

            btn.style.position = 'relative';
            btn.style.overflow = 'hidden';
            btn.appendChild(ripple);
            setTimeout(() => ripple.remove(), 500);
        });

        // Inject keyframe sekali
        if (!document.getElementById('ripple-style')) {
            const style = document.createElement('style');
            style.id = 'ripple-style';
            style.textContent = `
                @keyframes ripple-anim {
                    to { transform: scale(2.5); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
    },
};

/* ─── 6. SCROLL REVEAL ─────────────────────────────────────────────────── */

const ScrollReveal = {
    threshold: 0.1,

    run() {
        const elements = document.querySelectorAll('[data-reveal]');
        if (!elements.length) return;

        // Set initial state
        elements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        });

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const delay = entry.target.getAttribute('data-reveal-delay') || 0;
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, parseInt(delay));
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: this.threshold });

        elements.forEach(el => observer.observe(el));
    },
};

/* ─── 7. CHART ENTRY ANIMATION ─────────────────────────────────────────── */

const ChartEntrance = {
    /**
     * Animasikan bar chart: tiap bar tumbuh dari bawah
     * Tambahkan class .chart-bar ke setiap elemen bar
     */
    runBars() {
        const bars = document.querySelectorAll('.chart-bar');
        bars.forEach((bar, i) => {
            const height = bar.style.height || bar.getAttribute('data-height') || '0%';
            bar.style.height = '0%';
            bar.style.transition = `height 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) ${i * 40}ms`;
            setTimeout(() => {
                bar.style.height = height;
            }, 50);
        });
    },
};

/* ─── 8. PAGE LINK INTERCEPTION ─────────────────────────────────────────── */

const PageLinkHandler = {
    run() {
        document.addEventListener('click', e => {
            const link = e.target.closest('a[data-page-link]');
            if (!link) return;
            if (link.getAttribute('href') === window.location.pathname) return;
            if (link.getAttribute('target') === '_blank') return;

            e.preventDefault();
            const href = link.getAttribute('href');

            PageTransition.leave(() => {
                window.location.href = href;
            });
        });
    },
};

/* ─── 9. TOOLTIP SIMPLE ────────────────────────────────────────────────── */

const Tooltip = {
    run() {
        const tips = document.querySelectorAll('[data-tip]');
        tips.forEach(el => {
            el.style.position = 'relative';

            el.addEventListener('mouseenter', () => {
                const tip = document.createElement('div');
                tip.className = '_tip';
                tip.textContent = el.getAttribute('data-tip');
                tip.style.cssText = `
                    position: absolute;
                    bottom: calc(100% + 6px);
                    left: 50%;
                    transform: translateX(-50%) translateY(4px);
                    background: #1e1b2e;
                    color: #fff;
                    font-size: 0.7rem;
                    padding: 4px 10px;
                    border-radius: 8px;
                    white-space: nowrap;
                    z-index: 9999;
                    opacity: 0;
                    transition: opacity 0.2s, transform 0.2s;
                    pointer-events: none;
                `;
                el.appendChild(tip);
                requestAnimationFrame(() => {
                    tip.style.opacity = '1';
                    tip.style.transform = 'translateX(-50%) translateY(0)';
                });
            });

            el.addEventListener('mouseleave', () => {
                el.querySelectorAll('._tip').forEach(t => t.remove());
            });
        });
    },
};

/* ─── INIT — jalankan semua saat DOM siap ──────────────────────────────── */

function initAnimations() {
    PageTransition.enter();
    StaggerEntrance.run();
    StaggerEntrance.runGroup();
    CounterAnimation.run();
    FlashDismiss.run();
    RippleEffect.run();
    ScrollReveal.run();
    ChartEntrance.runBars();
    PageLinkHandler.run();
    Tooltip.run();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAnimations);
} else {
    initAnimations();
}

/* ─── EXPORT untuk dipakai blade/component lain jika perlu ─────────────── */
export {
    PageTransition,
    StaggerEntrance,
    CounterAnimation,
    FlashDismiss,
    RippleEffect,
    ScrollReveal,
    ChartEntrance,
    Tooltip,
};
