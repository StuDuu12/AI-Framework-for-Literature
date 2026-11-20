<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <link href="<?= URL_ROOT ?>/assets/css/student-workspace.css" rel="stylesheet">
    <title data-lang-key="workspace_title">Learning Workspace - Literature System</title>
</head>

<body class="workspace-page">
    <?php
    require_once __DIR__ . '/../../config/helper_workspace.php';
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'student') {
        header('Location: ' . URL_ROOT . '/dashboard');
        exit;
    }

    $userId = (int) $_SESSION['user']['user_id'];
    $userName = $_SESSION['user']['fullname'] ?? $_SESSION['user']['username'] ?? 'Student';
    $stats = getStudentWorkspaceStats($userId);
    $catalog = getLiteraryCanonWorks();
    $suggestionChips = getLiterarySuggestionChips(12);

    $authorCards = [];
    $seenAuthors = [];
    foreach ($catalog as $work) {
        $a = $work['author'] ?? '';
        if ($a && !isset($seenAuthors[$a]) && !empty($work['cover_image'])) {
            $seenAuthors[$a] = true;
            $authorCards[] = ['name' => $a, 'cover' => $work['cover_image'], 'query' => $a];
            if (count($authorCards) >= 8) {
                break;
            }
        }
    }

    $featuredWorks = array_slice(array_filter($catalog, fn($w) => !empty($w['featured'])), 0, 6);
    if (count($featuredWorks) < 4) {
        $featuredWorks = array_slice($catalog, 0, 6);
    }
    $quoteWork = $featuredWorks[(int) date('z') % max(1, count($featuredWorks))] ?? ($catalog[0] ?? null);
    $taskPct = $stats['tasks'] > 0 ? (int) round(($stats['completed'] / $stats['tasks']) * 100) : 0;
    ?>

    <main id="main" class="main">
        <section class="section pt-2">
            <div class="workspace-topbar">
                <div>
                    <h1 class="workspace-topbar__greeting">
                        <span data-lang-key="workspace_greeting">Chào mừng bạn trở lại</span>,
                        <?= htmlspecialchars($userName) ?>
                    </h1>
                    <p class="workspace-topbar__sub" data-lang-key="workspace_subtitle">
                        Explore world literature — search online, browse the canon gallery, then enter your classes.
                    </p>
                </div>
                <div class="workspace-stats">
                    <span class="workspace-stat-pill">
                        <i class="bi bi-book"></i>
                        <?= $stats['canon_count'] ?: count($catalog) ?>
                        <span data-lang-key="workspace_stat_works">works</span>
                    </span>
                    <span class="workspace-stat-pill">
                        <i class="bi bi-collection"></i>
                        <?= $stats['classes'] ?> <span data-lang-key="workspace_stat_classes">classes</span>
                    </span>
                    <span class="workspace-stat-pill">
                        <i class="bi bi-list-task"></i>
                        <?= $stats['tasks'] ?> <span data-lang-key="workspace_stat_tasks">tasks</span>
                    </span>
                </div>
            </div>

            <div class="workspace-layout">
                <div class="workspace-left-col">
                <div class="lit-search-panel">
                    <div class="lit-search-brand">
                        <div class="lit-search-brand__logo"><i class="bi bi-search"></i></div>
                        <h2 class="lit-search-brand__title" data-lang-key="workspace_search_title">LitSearch</h2>
                        <p class="lit-search-brand__tagline" data-lang-key="workspace_search_tagline">
                            Literature search — local canon + Wikipedia suggestions, like Google for books &amp; authors
                        </p>
                    </div>

                    <div class="lit-search-wrap">
                        <form class="lit-search-form" id="litSearchForm" role="search" autocomplete="off">
                            <input type="search" id="litSearchInput"
                                placeholder="Tìm tác giả, tác phẩm, bài thơ, tiểu thuyết…"
                                data-lang-key="workspace_search_placeholder"
                                aria-label="Tìm tác phẩm"
                                aria-autocomplete="list"
                                aria-controls="litAutocomplete"
                                aria-expanded="false">
                            <button type="submit" data-lang-key="workspace_search_btn">Tìm kiếm</button>
                        </form>
                        <div class="lit-autocomplete" id="litAutocomplete" role="listbox"></div>
                    </div>

                    <div class="lit-search-filters" id="litSearchFilters">
                        <button type="button" class="lit-filter-chip active" data-region="all" data-lang-key="workspace_filter_all">All</button>
                        <button type="button" class="lit-filter-chip" data-region="vietnam" data-lang-key="workspace_filter_vn">Việt Nam</button>
                        <button type="button" class="lit-filter-chip" data-region="international" data-lang-key="workspace_filter_intl">Thế giới</button>
                        <button type="button" class="lit-filter-chip" data-genre="poem" data-lang-key="workspace_filter_poem">Thơ</button>
                        <button type="button" class="lit-filter-chip" data-genre="story" data-lang-key="workspace_filter_story">Truyện</button>
                        <button type="button" class="lit-filter-chip" data-genre="novel" data-lang-key="workspace_filter_novel">Tiểu thuyết</button>
                    </div>

                    <?php if (!empty($suggestionChips)): ?>
                        <div class="lit-suggestion-chips" id="litSuggestionChips">
                            <?php foreach ($suggestionChips as $chip): ?>
                                <button type="button" class="lit-suggestion-chip" data-query="<?= htmlspecialchars($chip['query']) ?>">
                                    <?= htmlspecialchars($chip['label']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="lit-search-results" id="litSearchResults">
                        <div class="lit-search-results__empty" data-lang-key="workspace_search_hint">
                            Gõ để xem gợi ý từ kho tác phẩm và Wikipedia
                        </div>
                    </div>

                    <?php if (!empty($authorCards)): ?>
                        <div class="workspace-featured-authors">
                            <div class="workspace-featured-authors__title" data-lang-key="workspace_featured_authors">Tác giả tiêu biểu</div>
                            <div class="workspace-authors-scroll">
                                <?php foreach ($authorCards as $ac): ?>
                                    <button type="button" class="workspace-author-card" data-query="<?= htmlspecialchars($ac['query']) ?>">
                                        <img src="<?= htmlspecialchars($ac['cover']) ?>" alt="" class="workspace-author-card__avatar" loading="lazy">
                                        <span class="workspace-author-card__name"><?= htmlspecialchars($ac['name']) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="lit-quick-links">
                        <a href="<?= URL_ROOT ?>/canon" class="btn btn-sm btn-primary">
                            <i class="bi bi-book"></i> <span data-lang-key="menu_canon">Knowledge Base</span>
                        </a>
                        <a href="<?= URL_ROOT ?>/dashboard" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-grid"></i> <span data-lang-key="menu_classes">Class List</span>
                        </a>
                        <a href="<?= URL_ROOT ?>/class/my_tasks" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-list-task"></i> <span data-lang-key="menu_my_tasks">My Tasks</span>
                        </a>
                    </div>
                </div>

                <div class="workspace-discover">
                    <?php if ($quoteWork && !empty($quoteWork['excerpt'])): ?>
                        <blockquote class="workspace-quote">
                            <span class="workspace-quote__mark" aria-hidden="true">“</span>
                            <p><?= htmlspecialchars($quoteWork['excerpt']) ?></p>
                            <footer>
                                <a href="<?= URL_ROOT ?>/canon/view?id=<?= (int) $quoteWork['id'] ?>">
                                    <?= htmlspecialchars($quoteWork['title']) ?>
                                </a>
                                <?php if (!empty($quoteWork['author'])): ?>
                                    <span>— <?= htmlspecialchars($quoteWork['author']) ?></span>
                                <?php endif; ?>
                            </footer>
                        </blockquote>
                    <?php endif; ?>

                    <div class="workspace-discover__grid">
                        <div class="workspace-discover__card workspace-discover__card--progress">
                            <div class="workspace-discover__card-head">
                                <i class="bi bi-graph-up-arrow"></i>
                                <span data-lang-key="workspace_discover_progress">Tiến độ của bạn</span>
                            </div>
                            <div class="workspace-progress-ring" style="--pct: <?= $taskPct ?>">
                                <span class="workspace-progress-ring__value"><?= $taskPct ?>%</span>
                            </div>
                            <p class="workspace-discover__stat-line">
                                <strong><?= $stats['completed'] ?></strong>/<span><?= $stats['tasks'] ?></span>
                                <span data-lang-key="workspace_discover_submitted">nhiệm vụ đã nộp</span>
                            </p>
                            <?php if ($stats['pending_eval'] > 0): ?>
                                <a href="<?= URL_ROOT ?>/class/my_tasks" class="workspace-discover__pill">
                                    <i class="bi bi-star"></i>
                                    <?= $stats['pending_eval'] ?>
                                    <span data-lang-key="workspace_discover_eval_pending">bài chờ đánh giá</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="workspace-discover__card">
                            <div class="workspace-discover__card-head">
                                <i class="bi bi-compass"></i>
                                <span data-lang-key="workspace_discover_paths">Lối tắt</span>
                            </div>
                            <div class="workspace-path-tiles">
                                <a href="<?= URL_ROOT ?>/canon?region=vietnam" class="workspace-path-tile workspace-path-tile--vn">
                                    <span>🇻🇳</span>
                                    <strong data-lang-key="workspace_path_vn">Kinh điển Việt Nam</strong>
                                </a>
                                <a href="<?= URL_ROOT ?>/canon?region=international" class="workspace-path-tile workspace-path-tile--world">
                                    <span>🌍</span>
                                    <strong data-lang-key="workspace_path_world">Văn học thế giới</strong>
                                </a>
                                <a href="<?= URL_ROOT ?>/canon?genre=poem" class="workspace-path-tile workspace-path-tile--poem">
                                    <span>✒️</span>
                                    <strong data-lang-key="workspace_path_poem">Góc thơ</strong>
                                </a>
                                <button type="button" class="workspace-path-tile workspace-path-tile--search" data-query="Truyện Kiều">
                                    <span>📖</span>
                                    <strong data-lang-key="workspace_path_search">Gợi ý ngẫu nhiên</strong>
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($featuredWorks)): ?>
                        <div class="workspace-picks">
                            <div class="workspace-picks__head">
                                <span data-lang-key="workspace_discover_picks">Editor's picks</span>
                                <a href="<?= URL_ROOT ?>/canon" data-lang-key="workspace_discover_view_all">Xem tất cả</a>
                            </div>
                            <div class="workspace-picks__scroll">
                                <?php foreach (array_slice($featuredWorks, 0, 5) as $pick): ?>
                                    <a href="<?= URL_ROOT ?>/canon/view?id=<?= (int) $pick['id'] ?>" class="workspace-pick-card">
                                        <?php if (!empty($pick['cover_image'])): ?>
                                            <img src="<?= htmlspecialchars($pick['cover_image']) ?>" alt="" loading="lazy">
                                        <?php else: ?>
                                            <div class="workspace-pick-card__fallback" style="background:<?= htmlspecialchars($pick['gradient'] ?? '#4154f1') ?>"></div>
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($pick['title']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                </div>

                <aside class="poster-gallery-panel">
                    <div class="poster-gallery-panel__head">
                        <div>
                            <h3 class="poster-gallery-panel__title" data-lang-key="workspace_gallery_title">Literary Canon</h3>
                            <p class="poster-gallery-panel__sub" data-lang-key="workspace_gallery_sub">Vietnam &amp; world masterpieces</p>
                        </div>
                        <span class="badge bg-primary"><?= count($catalog) ?></span>
                    </div>
                    <div class="poster-grid" id="posterGallery">
                        <?php foreach ($catalog as $work): ?>
                            <a href="<?= URL_ROOT ?>/canon/view?id=<?= (int) $work['id'] ?>"
                                class="poster-card"
                                data-genre="<?= htmlspecialchars($work['genre'] ?? 'story') ?>"
                                data-region="<?= htmlspecialchars($work['region'] ?? 'vietnam') ?>"
                                data-title="<?= htmlspecialchars(mb_strtolower($work['title'])) ?>"
                                data-author="<?= htmlspecialchars(mb_strtolower($work['author'] ?? '')) ?>">
                                <?php if (!empty($work['cover_image'])): ?>
                                    <img src="<?= htmlspecialchars($work['cover_image']) ?>"
                                        alt="<?= htmlspecialchars($work['title']) ?>"
                                        class="poster-card__img"
                                        loading="lazy">
                                <?php else: ?>
                                    <div class="poster-card__bg-fallback" style="background:<?= htmlspecialchars($work['gradient']) ?>"></div>
                                <?php endif; ?>
                                <span class="poster-card__genre"><?= htmlspecialchars($work['genre_label'] ?? '') ?></span>
                                <span class="poster-card__region poster-card__region--<?= ($work['region'] ?? '') === 'vietnam' ? 'vn' : 'intl' ?>">
                                    <?= ($work['region'] ?? '') === 'vietnam' ? 'VN' : 'World' ?>
                                </span>
                                <div class="poster-card__overlay">
                                    <p class="poster-card__title"><?= htmlspecialchars($work['title']) ?></p>
                                    <p class="poster-card__author"><?= htmlspecialchars($work['author'] ?? '') ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
    (function() {
        const urlRoot = document.querySelector('meta[name="url-root"]')?.content || '';
        const apiUrl = urlRoot + '/api/literary_search.php';
        let activeRegion = 'all';
        let activeGenre = '';
        let suggestTimer = null;
        let activeSuggestIdx = -1;

        const input = document.getElementById('litSearchInput');
        const autocomplete = document.getElementById('litAutocomplete');
        const resultsEl = document.getElementById('litSearchResults');

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function filterPosters(q) {
            const query = (q || '').trim().toLowerCase();
            document.querySelectorAll('#posterGallery .poster-card').forEach(card => {
                const region = card.dataset.region || '';
                const genre = card.dataset.genre || '';
                const title = card.dataset.title || '';
                const author = card.dataset.author || '';
                let show = true;
                if (activeRegion !== 'all' && region !== activeRegion) show = false;
                if (activeGenre && genre !== activeGenre) show = false;
                if (query && !title.includes(query) && !author.includes(query)) show = false;
                card.style.display = show ? '' : 'none';
            });
        }

        function sourceBadge(item) {
            const label = item.source_label || 'Web';
            const color = item.source_color || '#475569';
            return `<span class="lit-result-badge lit-result-badge--source" style="border-color:${escapeHtml(color)};color:${escapeHtml(color)}">${escapeHtml(label)}</span>`;
        }

        function renderLocalResults(local, online, query) {
            if (!local.length && !online.length) {
                resultsEl.innerHTML = '<div class="lit-search-results__empty">No results for &ldquo;' + escapeHtml(query) + '&rdquo;. Try another author or title.</div>';
                return;
            }

            let html = '';
            if (local.length) {
                html += '<div class="lit-results-section-title">Kho kiến thức</div>';
                html += local.map(item => `
                    <a href="${urlRoot}/canon/view?id=${item.id || 0}" class="lit-result-item">
                        ${item.cover_image ? `<img src="${escapeHtml(item.cover_image)}" class="lit-result-item__cover" alt="">` : `<div class="lit-result-item__icon" style="background:${escapeHtml(item.gradient || '#4154f1')}"><i class="bi bi-book"></i></div>`}
                        <div>
                            <p class="lit-result-item__title">${escapeHtml(item.title)} <span class="lit-result-badge">${escapeHtml(item.region === 'vietnam' ? 'VN' : 'Canon')}</span></p>
                            <p class="lit-result-item__meta">${escapeHtml(item.author)}${item.country ? ' · ' + escapeHtml(item.country) : ''} · ${escapeHtml(item.genre_label || '')}</p>
                            ${item.excerpt ? `<p class="lit-result-item__snippet">${escapeHtml(item.excerpt)}</p>` : ''}
                        </div>
                    </a>`).join('');
            }
            if (online.length) {
                html += '<div class="lit-results-section-title">Online — multiple sources</div>';
                html += '<p class="lit-results-sources-hint">Open Library · Project Gutenberg · Internet Archive · Wikidata · Wikipedia</p>';
                html += online.map(item => `
                    <a href="${escapeHtml(item.url)}" class="lit-result-item" target="_blank" rel="noopener">
                        ${item.cover ? `<img src="${escapeHtml(item.cover)}" class="lit-result-item__cover" alt="">` : `<div class="lit-result-item__icon" style="background:${escapeHtml(item.source_color || '#475569')}"><i class="bi ${escapeHtml(item.source_icon || 'bi-globe2')}"></i></div>`}
                        <div>
                            <p class="lit-result-item__title">${escapeHtml(item.title)} ${sourceBadge(item)}</p>
                            <p class="lit-result-item__meta">${escapeHtml(item.source_label || 'Web')}${item.author ? ' · ' + escapeHtml(item.author) : ''}</p>
                            ${item.snippet ? `<p class="lit-result-item__snippet">${escapeHtml(item.snippet)}</p>` : ''}
                        </div>
                    </a>`).join('');
            }
            resultsEl.innerHTML = html;
        }

        async function runSearch(query) {
            query = (query || '').trim();
            if (!query) return;
            closeAutocomplete();
            resultsEl.innerHTML = '<div class="lit-search-results__empty"><span class="spinner-border spinner-border-sm"></span> Searching knowledge base &amp; online sources...</div>';
            filterPosters(query);
            try {
                const res = await fetch(apiUrl + '?action=search&q=' + encodeURIComponent(query));
                const data = await res.json();
                if (data.success) {
                    renderLocalResults(data.local || [], data.online || [], query);
                } else {
                    resultsEl.innerHTML = '<div class="lit-search-results__empty">Không tìm được. Vui lòng thử lại.</div>';
                }
            } catch (e) {
                resultsEl.innerHTML = '<div class="lit-search-results__empty">Lỗi mạng. Kiểm tra kết nối rồi thử lại.</div>';
            }
        }

        function renderAutocomplete(combined) {
            if (!combined.length) {
                closeAutocomplete();
                return;
            }
            let html = '<div class="lit-autocomplete__section">';
            combined.forEach((item, idx) => {
                const thumb = item.cover
                    ? `<img src="${escapeHtml(item.cover)}" class="lit-suggest-item__thumb" alt="">`
                    : `<div class="lit-suggest-item__thumb lit-suggest-item__thumb--icon" style="background:${escapeHtml(item.source_color || (item.type === 'online' ? '#475569' : '#4154f1'))}"><i class="bi ${item.type === 'online' ? escapeHtml(item.source_icon || 'bi-globe2') : 'bi-book'}"></i></div>`;
                html += `<div class="lit-suggest-item" role="option" data-idx="${idx}" data-query="${escapeHtml(item.title)}" data-url="${escapeHtml(item.url || '')}">
                    ${thumb}
                    <div>
                        <p class="lit-suggest-item__title">${escapeHtml(item.title)}</p>
                        <p class="lit-suggest-item__meta">${escapeHtml(item.subtitle || '')}</p>
                    </div>
                </div>`;
            });
            html += '</div>';
            autocomplete.innerHTML = html;
            autocomplete.classList.add('is-open');
            input.setAttribute('aria-expanded', 'true');
            activeSuggestIdx = -1;

            autocomplete.querySelectorAll('.lit-suggest-item').forEach(el => {
                el.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    const q = this.dataset.query;
                    const url = this.dataset.url;
                    input.value = q;
                    closeAutocomplete();
                    if (url && item.type === 'online') {
                        window.open(url, '_blank');
                    }
                    runSearch(q);
                });
            });
        }

        function closeAutocomplete() {
            autocomplete.classList.remove('is-open');
            autocomplete.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
            activeSuggestIdx = -1;
        }

        async function fetchSuggestions(q) {
            if (q.length < 1) {
                closeAutocomplete();
                return;
            }
            try {
                const res = await fetch(apiUrl + '?action=suggest&q=' + encodeURIComponent(q));
                const data = await res.json();
                if (data.success && data.combined) {
                    renderAutocomplete(data.combined);
                }
            } catch (e) { /* silent */ }
        }

        input.addEventListener('input', function() {
            const q = this.value;
            filterPosters(q);
            clearTimeout(suggestTimer);
            if (q.length < 1) {
                closeAutocomplete();
                return;
            }
            suggestTimer = setTimeout(() => fetchSuggestions(q), 280);
        });

        input.addEventListener('keydown', function(e) {
            const items = autocomplete.querySelectorAll('.lit-suggest-item');
            if (!items.length || !autocomplete.classList.contains('is-open')) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeSuggestIdx = Math.min(activeSuggestIdx + 1, items.length - 1);
                items.forEach((it, i) => it.classList.toggle('is-active', i === activeSuggestIdx));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeSuggestIdx = Math.max(activeSuggestIdx - 1, 0);
                items.forEach((it, i) => it.classList.toggle('is-active', i === activeSuggestIdx));
            } else if (e.key === 'Enter' && activeSuggestIdx >= 0) {
                e.preventDefault();
                items[activeSuggestIdx].dispatchEvent(new MouseEvent('mousedown'));
            } else if (e.key === 'Escape') {
                closeAutocomplete();
            }
        });

        document.getElementById('litSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            runSearch(input.value);
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.lit-search-wrap')) closeAutocomplete();
        });

        document.querySelectorAll('.lit-filter-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                const isRegion = this.dataset.region !== undefined;
                document.querySelectorAll('.lit-filter-chip').forEach(c => {
                    if (isRegion && c.dataset.region !== undefined) c.classList.remove('active');
                    if (!isRegion && c.dataset.genre !== undefined) c.classList.remove('active');
                });
                this.classList.add('active');
                if (isRegion) {
                    activeRegion = this.dataset.region || 'all';
                } else {
                    activeGenre = this.dataset.genre || '';
                    if (!activeGenre) activeGenre = '';
                }
                filterPosters(input.value);
            });
        });

        document.querySelectorAll('.lit-suggestion-chip, .workspace-author-card').forEach(btn => {
            btn.addEventListener('click', function() {
                const q = this.dataset.query || '';
                input.value = q;
                runSearch(q);
            });
        });

        document.querySelectorAll('.workspace-path-tile--search').forEach(btn => {
            btn.addEventListener('click', function() {
                const picks = ['Truyện Kiều', 'Lão Hạc', 'Chí Phèo', 'Romeo and Juliet', '1984', 'The Great Gatsby'];
                const q = this.dataset.query || picks[Math.floor(Math.random() * picks.length)];
                input.value = q;
                runSearch(q);
            });
        });
    })();
    </script>
</body>

</html>
