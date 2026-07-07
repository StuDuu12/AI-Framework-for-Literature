/**
 * Header notification bell — task assignments, evaluations, alerts
 * Clean rebuild: no JS animations, no stagger, no transform hacks.
 * Bootstrap 5 handles dropdown show/hide natively.
 */
(function () {
    const meta = document.querySelector('meta[name="url-root"]');
    const base = meta?.content || '';
    const badge = document.getElementById('notif-badge');
    const dropdown = document.getElementById('notif-dropdown');
    const bellToggle = dropdown?.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
    if (!dropdown) return;

    let cachedData = null;
    let lastFetchTime = 0;
    const CACHE_TTL = 30000;

    function currentLang() {
        return localStorage.getItem('app_language') || 'vi';
    }

    function t(key, fallback) {
        if (typeof window.translate === 'function') {
            const v = window.translate(key);
            if (v && v !== key) return v;
        }
        return fallback;
    }

    function typeIcon(type) {
        const map = {
            assignment: 'bi-journal-plus',
            task:       'bi-clipboard-check',
            evaluate:   'bi-star',
            eval:       'bi-star',
            deadline:   'bi-alarm',
            warning:    'bi-exclamation-triangle',
            success:    'bi-check-circle',
            info:       'bi-bell'
        };
        return map[type] || 'bi-bell';
    }

    function typeClass(type) {
        const map = {
            assignment: 'notif-icon--task',
            task:       'notif-icon--task',
            evaluate:   'notif-icon--eval',
            eval:       'notif-icon--eval',
            deadline:   'notif-icon--deadline',
            warning:    'notif-icon--deadline',
            success:    'notif-icon--eval',
            info:       'notif-icon--info'
        };
        return map[type] || 'notif-icon--info';
    }

    function timeAgo(ts, lang) {
        if (!ts) return '';
        try {
            const diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
            const isVi = lang !== 'en';
            if (diff < 60)    return isVi ? 'vừa xong' : 'just now';
            if (diff < 3600)  { const m = Math.floor(diff / 60);   return isVi ? `${m} phút trước`  : `${m}m ago`; }
            if (diff < 86400) { const h = Math.floor(diff / 3600);  return isVi ? `${h} giờ trước`   : `${h}h ago`; }
            const d = Math.floor(diff / 86400);
            return isVi ? `${d} ngày trước` : `${d}d ago`;
        } catch (e) { return ts; }
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function escapeAttr(text) {
        return String(text || '#')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function buildNotificationHTML(n, lang) {
        const isUnread = n.is_read == 0 || n.is_read === '0';
        const icon = typeIcon(n.type);
        const iconCls = typeClass(n.type);
        return `
            <a class="notif-item ${isUnread ? 'unread' : ''} text-decoration-none"
               href="${escapeAttr(n.link || '#')}"
               data-notif-id="${n.dynamic ? '' : (n.id || '')}"
               data-notif-dynamic="${n.dynamic ? '1' : '0'}">
                <div class="notif-icon ${iconCls}"><i class="bi ${icon}"></i></div>
                <div class="notif-body">
                    <div class="notif-title">${escapeHtml(n.title)}</div>
                    ${n.message ? `<div class="notif-time">${escapeHtml(n.message)}</div>` : ''}
                    <div class="notif-time">${timeAgo(n.created_at, lang)}</div>
                </div>
            </a>`;
    }

    function renderNotifications(data) {
        const lang = currentLang();

        if (badge) {
            if (data.unread_count > 0) {
                badge.textContent = data.unread_count > 9 ? '9+' : String(data.unread_count);
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }

        dropdown.querySelectorAll('[data-notif-item]').forEach(el => el.remove());

        if (!data.notifications.length) {
            const empty = document.createElement('li');
            empty.className = 'dropdown-item-text text-muted small py-4 text-center';
            empty.setAttribute('data-notif-item', '1');
            empty.innerHTML = `
                <i class="bi bi-bell-slash d-block mb-2" style="font-size:1.5rem;opacity:.4"></i>
                ${escapeHtml(t('no_notifications', lang === 'en' ? 'No notifications' : 'Không có thông báo'))}`;
            dropdown.appendChild(empty);
            return;
        }

        const fragment = document.createDocumentFragment();
        data.notifications.forEach(n => {
            const li = document.createElement('li');
            li.setAttribute('data-notif-item', '1');
            li.innerHTML = buildNotificationHTML(n, lang);
            fragment.appendChild(li);
        });
        dropdown.appendChild(fragment);

        dropdown.querySelectorAll('[data-notif-id]').forEach(el => {
            const id = el.getAttribute('data-notif-id');
            if (!id) return;
            el.addEventListener('click', () => {
                fetch(base + '/api/notifications.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_read', id: parseInt(id, 10) })
                }).then(() => {
                    cachedData = null;
                    lastFetchTime = 0;
                    loadNotifications(true);
                });
            });
        });
    }

    async function loadNotifications(force) {
        const now = Date.now();
        if (!force && cachedData && (now - lastFetchTime) < CACHE_TTL) {
            renderNotifications(cachedData);
            return;
        }
        try {
            const lang = currentLang();
            const res = await fetch(base + '/api/notifications.php?lang=' + encodeURIComponent(lang), {
                credentials: 'same-origin'
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;
            cachedData = data;
            lastFetchTime = now;
            renderNotifications(data);
        } catch (e) {
            console.warn('Notifications load failed', e);
        }
    }

    document.getElementById('mark-all-read-btn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        await fetch(base + '/api/notifications.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_all_read' })
        });
        cachedData = null;
        lastFetchTime = 0;
        loadNotifications(true);
    });

    bellToggle?.addEventListener('show.bs.dropdown', () => loadNotifications(true));
    window.addEventListener('languageChanged', () => loadNotifications(true));

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadNotifications);
    } else {
        loadNotifications();
    }
    setInterval(() => loadNotifications(true), 60000);
})();
