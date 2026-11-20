/**
 * ============================================================
 * animations.js — Unified Animation Controller
 * LiteratureVer2 — Modern Glassmorphism Design System
 * ============================================================
 * Requires (optional): GSAP 3.12+ + ScrollTrigger
 * CSS fallback: animations.css + IntersectionObserver
 * ============================================================
 */

(function () {
    'use strict';

    document.documentElement.classList.add('lit-js');

    var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var MOBILE = window.innerWidth < 768;
    var HAS_GSAP = typeof gsap !== 'undefined';
    var HAS_ST = HAS_GSAP && typeof ScrollTrigger !== 'undefined';

    function skipPage() {
        var body = document.body;
        return body.classList.contains('landing-page')
            || body.classList.contains('error-page')
            || document.querySelector('[data-lit-no-animate]');
    }

    function revealNodes(selector) {
        document.querySelectorAll(selector).forEach(function (el) {
            el.style.opacity = '1';
            el.style.transform = 'none';
        });
    }

    function isInViewport(el, threshold) {
        if (!el) return true;
        var rect = el.getBoundingClientRect();
        var limit = window.innerHeight * (threshold || 0.92);
        return rect.top < limit && rect.bottom > 0;
    }

    function finishStagger(container) {
        if (!container) return;
        container.classList.add('lit-stagger-done');
        Array.prototype.forEach.call(container.children, function (child) {
            child.style.opacity = '1';
            child.style.transform = 'none';
        });
    }

    function finishAllStaggers() {
        document.querySelectorAll('[data-stagger]:not(.lit-stagger-done)').forEach(finishStagger);
    }

    function markAnimateDone(el) {
        if (!el) return;
        el.classList.add('lit-animate-done');
        el.style.opacity = '1';
        el.style.transform = 'none';
    }

    function markReady() {
        document.body.classList.add('lit-anim-ready');
        var main = document.getElementById('main');
        if (main) {
            main.classList.add('page-enter');
        }
    }

    function throttle(fn, wait) {
        var last = 0;
        return function () {
            var now = Date.now();
            if (now - last >= wait) {
                last = now;
                fn.apply(this, arguments);
            }
        };
    }

    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: REDUCED ? 'auto' : 'smooth' });
    }

    function gsapFrom(target, vars, scrollTrigger) {
        if (!HAS_GSAP || REDUCED) return;
        var opts = Object.assign({}, vars);
        if (scrollTrigger && HAS_ST) {
            opts.scrollTrigger = scrollTrigger;
        }
        gsap.from(target, opts);
    }

    /* ---- CSS-only reveal (works without GSAP) ---- */
    function initRevealObserver() {
        var nodes = document.querySelectorAll('.reveal, .reveal-stagger');
        if (!nodes.length) return;

        if (!('IntersectionObserver' in window) || REDUCED) {
            nodes.forEach(function (el) { el.classList.add('revealed'); });
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('revealed');
                io.unobserve(entry.target);
            });
        }, { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        nodes.forEach(function (el) { io.observe(el); });
    }

    /* ---- Shell: header / sidebar / main (CSS via .lit-anim-ready) ---- */
    function initShell() {
        markReady();
    }

    /* ---- Page content: pagetitle + cards inside #main only ---- */
    function initPageContent() {
        var main = document.getElementById('main');
        if (!main || !HAS_GSAP || REDUCED) return;

        var tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
        var pageTitle = main.querySelector('.pagetitle');
        if (pageTitle) {
            tl.from(pageTitle, { y: -12, opacity: 0, duration: MOBILE ? 0.3 : 0.38 });
        }

        var cards = main.querySelectorAll('.section > .card, .section .row > [class*="col"] > .card');
        cards = Array.prototype.filter.call(cards, function (card) {
            return !card.closest('[data-stagger]');
        });
        if (cards.length && cards.length <= 24) {
            tl.from(cards, {
                y: 22,
                opacity: 0,
                duration: MOBILE ? 0.32 : 0.48,
                stagger: MOBILE ? 0.04 : 0.07
            }, pageTitle ? '-=0.12' : 0);
        }
    }

    /* ---- [data-animate] scroll reveals ---- */
    function initDataAnimate() {
        if (REDUCED) {
            document.querySelectorAll('[data-animate]').forEach(markAnimateDone);
            return;
        }

        document.querySelectorAll('[data-animate]').forEach(function (el) {
            if (el.closest('#header, #sidebar, .header-nav, .dropdown-menu')) return;

            var anim = el.getAttribute('data-animate') || 'fade-up';
            var from = { opacity: 0 };
            switch (anim) {
                case 'fade-down': from.y = -24; break;
                case 'fade-left': from.x = -24; break;
                case 'fade-right': from.x = 24; break;
                case 'scale': from.scale = 0.92; break;
                case 'rotate': from.rotation = -4; break;
                default: from.y = 24; break;
            }

            if (HAS_GSAP) {
                var inView = isInViewport(el);
                var opts = Object.assign(from, {
                    duration: MOBILE ? 0.45 : 0.6,
                    ease: 'power3.out',
                    clearProps: 'transform,opacity',
                    onComplete: function () { markAnimateDone(el); }
                });
                if (HAS_ST && !inView) {
                    opts.scrollTrigger = {
                        trigger: el,
                        start: 'top 92%',
                        toggleActions: 'play none none none',
                        once: true
                    };
                }
                gsap.from(el, opts);
            } else {
                el.classList.add('animate-fade-in-up', 'lit-animate-done');
            }
        });
    }

    /* ---- [data-stagger] children ---- */
    function initStagger() {
        if (REDUCED) {
            finishAllStaggers();
            return;
        }

        document.querySelectorAll('[data-stagger]').forEach(function (container) {
            var children = container.children;
            if (!children.length) {
                finishStagger(container);
                return;
            }

            if (HAS_GSAP) {
                var inView = isInViewport(container);
                var opts = {
                    opacity: 0,
                    y: 18,
                    duration: MOBILE ? 0.32 : 0.42,
                    stagger: MOBILE ? 0.04 : 0.06,
                    ease: 'power3.out',
                    clearProps: 'transform,opacity',
                    onComplete: function () { finishStagger(container); }
                };
                if (HAS_ST && !inView) {
                    opts.scrollTrigger = {
                        trigger: container,
                        start: 'top 92%',
                        toggleActions: 'play none none none',
                        once: true
                    };
                }
                gsap.from(children, opts);
            } else {
                Array.prototype.forEach.call(children, function (child, i) {
                    child.classList.add('animate-fade-in-up');
                    child.style.animationDelay = (i * 0.06) + 's';
                });
                finishStagger(container);
            }
        });
    }

    /* ---- Sidebar nav item entrance ---- */
    function initSidebarNav() {
        var nav = document.getElementById('sidebar-nav');
        if (!nav || !HAS_GSAP || REDUCED || MOBILE) return;

        var items = nav.querySelectorAll(':scope > .nav-item');
        if (!items.length) return;

        gsap.from(items, {
            x: -10,
            opacity: 0,
            duration: 0.34,
            stagger: 0.035,
            delay: 0.15,
            ease: 'power2.out',
            clearProps: 'transform,opacity'
        });
    }

    /* ---- Number count-up ---- */
    function initCountUp() {
        if (!HAS_GSAP || REDUCED) return;

        document.querySelectorAll('.count-up, [data-count], .stat-value, .stat-number').forEach(function (el) {
            if (el.dataset.litCounted === '1') return;

            var raw = el.getAttribute('data-count') || el.textContent;
            var target = parseInt(String(raw).replace(/[^\d]/g, ''), 10);
            if (isNaN(target) || target <= 0) return;

            el.dataset.litCounted = '1';
            var original = el.textContent;
            var obj = { val: 0 };
            var inView = isInViewport(el);
            var opts = {
                val: target,
                duration: MOBILE ? 1 : 1.4,
                ease: 'power2.out',
                onUpdate: function () {
                    el.textContent = Math.round(obj.val).toLocaleString();
                },
                onComplete: function () {
                    el.textContent = original.indexOf('.') >= 0 ? String(target) : Math.round(obj.val).toLocaleString();
                }
            };
            if (HAS_ST && !inView) {
                opts.scrollTrigger = {
                    trigger: el,
                    start: 'top 92%',
                    toggleActions: 'play none none none',
                    once: true
                };
            }
            gsap.to(obj, opts);
        });
    }

    /* ---- Header dropdown polish (CSS primary; class hook) ---- */
    function initDropdowns() {
        document.querySelectorAll('.header-nav .dropdown-menu').forEach(function (menu) {
            menu.classList.add('lit-dropdown');
        });
    }

    /* ---- Modal entrance (CSS primary) ---- */
    function initModals() {
        document.querySelectorAll('.modal').forEach(function (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                var dialog = modal.querySelector('.modal-dialog');
                if (!dialog || !HAS_GSAP || REDUCED) return;
                gsap.fromTo(dialog,
                    { opacity: 0, y: 16, scale: 0.97 },
                    { opacity: 1, y: 0, scale: 1, duration: 0.28, ease: 'power2.out', clearProps: 'transform,opacity' }
                );
            });
        });
    }

    /* ---- Table row stagger ---- */
    function initTables() {
        if (REDUCED) return;

        document.querySelectorAll('#main table.table tbody').forEach(function (tbody) {
            var rows = tbody.querySelectorAll('tr');
            if (!rows.length || rows.length > 40) return;

            if (HAS_GSAP && HAS_ST) {
                gsap.from(rows, {
                    opacity: 0,
                    x: -8,
                    duration: 0.28,
                    stagger: 0.025,
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: tbody,
                        start: 'top 92%',
                        toggleActions: 'play none none none'
                    }
                });
            } else {
                Array.prototype.forEach.call(rows, function (row, i) {
                    row.classList.add('lit-table-row-enter');
                    row.style.animationDelay = Math.min(i * 0.025, 0.5) + 's';
                });
            }
        });
    }

    /* ---- Alerts in main content ---- */
    function initAlerts() {
        document.querySelectorAll('#main .alert:not(.lit-alert-enter)').forEach(function (alert) {
            alert.classList.add('lit-alert-enter');
        });
    }

    /* ---- Button ripple CSS vars ---- */
    function initButtonRipple() {
        document.querySelectorAll('.btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                var rect = btn.getBoundingClientRect();
                btn.style.setProperty('--ripple-x', (e.clientX - rect.left) + 'px');
                btn.style.setProperty('--ripple-y', (e.clientY - rect.top) + 'px');
            });
        });
    }

    /* ---- Notification bell ---- */
    function initNotificationBell() {
        var bell = document.querySelector('.header-nav .bi-bell');
        if (!bell) return;

        var badge = document.getElementById('notif-badge');
        if (!badge || badge.classList.contains('d-none')) return;
        if (parseInt(badge.textContent, 10) <= 0) return;

        if (HAS_GSAP && !REDUCED) {
            gsap.fromTo(bell,
                { rotation: 0 },
                { rotation: 12, duration: 0.08, yoyo: true, repeat: 5, ease: 'power1.inOut', delay: 0.7 }
            );
        } else {
            bell.classList.add('lit-ring');
        }
    }

    /* ---- Theme toggle icon spin ---- */
    function initThemeToggle() {
        var toggle = document.querySelector('.theme-toggle');
        if (!toggle || !HAS_GSAP || REDUCED) return;

        toggle.addEventListener('click', function () {
            var icon = toggle.querySelector('i');
            if (!icon) return;
            gsap.fromTo(icon,
                { scale: 1, rotation: 0 },
                {
                    scale: 0,
                    rotation: 180,
                    duration: 0.18,
                    ease: 'power2.in',
                    onComplete: function () {
                        gsap.to(icon, { scale: 1, rotation: 360, duration: 0.28, ease: 'back.out(2)' });
                    }
                }
            );
        });
    }

    /* ---- Back to top ---- */
    function initScrollToTop() {
        var btn = document.querySelector('.back-to-top');
        if (!btn) return;

        var toggle = throttle(function () {
            if (window.scrollY > 280) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }, 80);

        window.addEventListener('scroll', toggle, { passive: true });
        toggle();

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            scrollToTop();
        });
    }

    /* ---- Toast observer ---- */
    function initToastObserver() {
        var attach = function (container) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    m.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1 || !node.classList.contains('toast')) return;
                        node.classList.add('lit-toast-enter');
                        if (HAS_GSAP && !REDUCED) {
                            gsap.from(node, { x: 36, opacity: 0, duration: 0.34, ease: 'power2.out' });
                        }
                    });
                });
            }).observe(container, { childList: true });
        };

        var existing = document.getElementById('toast-container');
        if (existing) {
            attach(existing);
            return;
        }

        var observer = new MutationObserver(function () {
            var container = document.getElementById('toast-container');
            if (!container) return;
            attach(container);
            observer.disconnect();
        });
        observer.observe(document.body, { childList: true, subtree: false });
    }

    /* ---- Login page subtle entrance ---- */
    function initLoginPage() {
        if (!document.body.classList.contains('login-page')) return;
        document.body.classList.add('lit-anim-ready');
    }

    /* ---- Bootstrap tooltips fade-in ---- */
    function initTooltips() {
        if (!window.bootstrap || !bootstrap.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (bootstrap.Tooltip.getInstance(el)) return;
            new bootstrap.Tooltip(el);
        });
    }

    function init() {
        if (skipPage()) {
            finishAllStaggers();
            document.querySelectorAll('[data-animate]').forEach(markAnimateDone);
            return;
        }

        initLoginPage();
        initRevealObserver();
        initDropdowns();
        initButtonRipple();
        initScrollToTop();
        initToastObserver();
        initAlerts();
        initTooltips();

        if (REDUCED) {
            finishAllStaggers();
            document.querySelectorAll('[data-animate]').forEach(markAnimateDone);
            markReady();
            return;
        }

        if (!HAS_GSAP) {
            markReady();
            initDataAnimate();
            initStagger();
            initTables();
            return;
        }

        if (HAS_ST) {
            gsap.registerPlugin(ScrollTrigger);
        }

        initShell();
        initPageContent();
        initDataAnimate();
        initStagger();
        initSidebarNav();
        initCountUp();
        initModals();

        if (!MOBILE) {
            initTables();
        }

        initNotificationBell();
        initThemeToggle();

        /* Safety net: never leave stagger/content hidden if GSAP fails */
        setTimeout(finishAllStaggers, 2000);
        setTimeout(function () {
            document.querySelectorAll('[data-animate]:not(.lit-animate-done)').forEach(markAnimateDone);
        }, 2000);
    }

    window.LitAnimations = {
        refresh: function () {
            if (HAS_ST) ScrollTrigger.refresh();
        },
        revealAll: function () {
            finishAllStaggers();
            document.querySelectorAll('[data-animate]').forEach(markAnimateDone);
            revealNodes('.reveal, .reveal-stagger > *');
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.addEventListener('load', function () {
        if (window.LitAnimations && typeof window.LitAnimations.refresh === 'function') {
            window.LitAnimations.refresh();
        }
    });
})();
