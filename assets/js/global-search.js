/**
 * Global search in header — debounce, dropdown with icons, keyboard nav
 */
(function () {
    const form = document.querySelector('.search-form');
    const input = form?.querySelector('input[name="query"]');
    if (!form || !input) return;

    const meta = document.querySelector('meta[name="url-root"]');
    const base = meta?.content || '';

    let box = form.querySelector('.global-search-results');
    if (!box) {
        box = document.createElement('div');
        box.className = 'global-search-results';
        form.style.position = 'relative';
        form.appendChild(box);
    }

    const TYPE_ICONS = {
        class: 'bi-building',
        task:  'bi-clipboard-check',
        user:  'bi-person',
        topic: 'bi-journal-text',
    };

    const TYPE_LABELS_VI = { class: 'Lớp', task: 'Nhiệm vụ', user: 'Người dùng', topic: 'Chủ đề' };
    const TYPE_LABELS_EN = { class: 'Class', task: 'Task', user: 'User', topic: 'Topic' };

    let timer = null;
    let activeIdx = -1;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const links = box.querySelectorAll('a[data-search-idx]');
        if (activeIdx >= 0 && links[activeIdx]) {
            window.location = links[activeIdx].href;
        } else {
            runSearch(input.value.trim());
        }
    });

    input.addEventListener('input', function () {
        clearTimeout(timer);
        activeIdx = -1;
        timer = setTimeout(() => runSearch(input.value.trim()), 300);
    });

    input.addEventListener('keydown', function (e) {
        const links = box.querySelectorAll('a[data-search-idx]');
        if (!links.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, links.length - 1);
            updateActive(links);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, -1);
            updateActive(links);
        } else if (e.key === 'Escape') {
            box.classList.remove('show');
            activeIdx = -1;
        } else if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            window.location = links[activeIdx].href;
        }
    });

    document.addEventListener('click', function (e) {
        if (!form.contains(e.target)) {
            box.classList.remove('show');
            activeIdx = -1;
        }
    });

    function updateActive(links) {
        links.forEach((a, i) => {
            a.style.background = (i === activeIdx) ? 'var(--app-primary-soft, #f6f7ff)' : '';
        });
    }

    function currentLang() {
        return localStorage.getItem('app_language') || 'vi';
    }

    async function runSearch(q) {
        if (q.length < 2) {
            box.classList.remove('show');
            box.innerHTML = '';
            return;
        }
        box.innerHTML = '<div class="p-2 text-muted small text-center"><i class="bi bi-search me-1"></i>...</div>';
        box.classList.add('show');

        try {
            const res = await fetch(`${base}/api/search.php?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            const lang = currentLang();
            const typeLabels = lang === 'en' ? TYPE_LABELS_EN : TYPE_LABELS_VI;

            if (!data.results?.length) {
                box.innerHTML = '<div class="p-3 text-muted small text-center"><i class="bi bi-search me-1"></i>' +
                    (lang === 'en' ? 'No results found' : 'Không tìm thấy kết quả') + '</div>';
            } else {
                box.innerHTML = data.results.slice(0, 8).map((r, i) => {
                    const icon = TYPE_ICONS[r.type] || 'bi-search';
                    const label = typeLabels[r.type] || r.type;
                    return `<a href="${escapeAttr(r.url)}" data-search-idx="${i}">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi ${icon} text-muted" style="font-size:.9rem;width:16px"></i>
                            <span>${escapeHtml(r.label)}</span>
                        </span>
                        <span class="search-type">${escapeHtml(label)}</span>
                    </a>`;
                }).join('');
            }
            box.classList.add('show');
            activeIdx = -1;
        } catch (err) {
            box.classList.remove('show');
        }
    }

    function escapeHtml(t) {
        const d = document.createElement('div');
        d.textContent = t || '';
        return d.innerHTML;
    }

    function escapeAttr(t) {
        return String(t || '#').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
})();
