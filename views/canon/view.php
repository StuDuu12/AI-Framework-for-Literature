<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <link href="<?= URL_ROOT ?>/assets/css/literary-canon.css" rel="stylesheet">
    <title><?= htmlspecialchars($pageTitle ?? 'Work') ?> — Literary Canon</title>
</head>

<body class="canon-page canon-detail-page">
    <?php
    require_once __DIR__ . '/../../config/helper_workspace.php';
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'student') {
        header('Location: ' . URL_ROOT . '/dashboard');
        exit;
    }

    $workId = (int) ($_GET['id'] ?? 0);
    $work = getLiteraryWorkById($workId);

    if (!$work) {
        echo '<main id="main" class="main"><div class="alert alert-warning mt-3">Work not found.</div></main>';
        require_once __DIR__ . '/../templates/footer.php';
        require_once __DIR__ . '/../templates/vendors.php';
        exit;
    }

    $author = getLiteraryAuthorForWork($work);
    $related = [];
    if ($author && !empty($author['id'])) {
        $related = getRelatedWorksByAuthor($author['id'], $work['id'], 8);
    }

    $pageTitle = $work['title'];
    $readContent = $work['full_content'] ?? '';
    if ($readContent === '' && !empty($work['description'])) {
        $readContent = '<p>' . htmlspecialchars($work['description'], ENT_QUOTES, 'UTF-8') . '</p>';
        if (!empty($work['excerpt'])) {
            $readContent .= '<p class="canon-verse"><em>' . htmlspecialchars($work['excerpt'], ENT_QUOTES, 'UTF-8') . '</em></p>';
        }
        $work['content_html'] = true;
    }

    $genrePeers = [];
    if (!empty($work['genre'])) {
        $genrePeers = array_values(array_filter(
            getLiteraryCanonWorks(['genre' => $work['genre'], 'limit' => 6]),
            fn($w) => (int) ($w['id'] ?? 0) !== (int) $work['id']
        ));
        $genrePeers = array_slice($genrePeers, 0, 3);
    }

    $regionFeatured = array_values(array_filter(
        getLiteraryCanonWorks(['region' => $work['region'] ?? '', 'featured' => true, 'limit' => 8]),
        fn($w) => (int) ($w['id'] ?? 0) !== (int) $work['id']
    ));
    $dailyPick = $regionFeatured[(int) $work['id'] % max(1, count($regionFeatured))] ?? ($regionFeatured[0] ?? null);

    $canonTotal = literaryCanonTableExists()
        ? (int) $pdo->query('SELECT COUNT(*) FROM literary_work')->fetchColumn()
        : 0;
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?= htmlspecialchars($work['title']) ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/workspace" data-lang-key="menu_workspace">Workspace</a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/canon" data-lang-key="canon_breadcrumb">Knowledge Base</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($work['title']) ?></li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="canon-detail-hero">
                <div class="canon-detail-hero__cover">
                    <?php if (!empty($work['cover_image'])): ?>
                        <img src="<?= htmlspecialchars($work['cover_image']) ?>" alt="<?= htmlspecialchars($work['title']) ?>">
                    <?php else: ?>
                        <div style="width:100%;height:100%;background:<?= htmlspecialchars($work['gradient']) ?>"></div>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="canon-detail-hero__title"><?= htmlspecialchars($work['title']) ?></h2>
                    <?php if ($author): ?>
                        <a href="#canon-author" class="canon-detail-hero__author-link"><?= htmlspecialchars($author['name']) ?></a>
                    <?php else: ?>
                        <span class="canon-detail-hero__author-link"><?= htmlspecialchars($work['author'] ?? '') ?></span>
                    <?php endif; ?>
                    <div class="canon-detail-hero__meta">
                        <span class="canon-meta-pill">
                            <i class="bi bi-tag"></i>
                            <?= htmlspecialchars($work['genre_label'] ?? '') ?>
                        </span>
                        <?php if (!empty($work['era'])): ?>
                            <span class="canon-meta-pill"><i class="bi bi-clock"></i> <?= htmlspecialchars($work['era']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($work['country'])): ?>
                            <span class="canon-meta-pill"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($work['country']) ?></span>
                        <?php endif; ?>
                        <span class="canon-meta-pill">
                            <?= ($work['region'] ?? '') === 'vietnam' ? '🇻🇳 Vietnam' : '🌍 World' ?>
                        </span>
                    </div>
                    <?php if (!empty($work['description'])): ?>
                        <p class="text-muted mb-0"><?= htmlspecialchars($work['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($work['wikipedia_url'])): ?>
                        <a href="<?= htmlspecialchars($work['wikipedia_url']) ?>" class="btn btn-sm btn-outline-primary mt-3" target="_blank" rel="noopener">
                            <i class="bi bi-wikipedia"></i> <span data-lang-key="canon_wikipedia">Wikipedia</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($author): ?>
                <div class="canon-author-panel" id="canon-author">
                    <?php if (!empty($author['portrait_image'])): ?>
                        <div class="canon-author-panel__portrait">
                            <img src="<?= htmlspecialchars($author['portrait_image']) ?>" alt="<?= htmlspecialchars($author['name']) ?>">
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-uppercase text-muted small mb-1" data-lang-key="canon_about_author">About the author</p>
                        <h3 class="canon-author-panel__name"><?= htmlspecialchars($author['name']) ?></h3>
                        <p class="canon-author-panel__lifespan">
                            <?php if (!empty($author['lifespan'])): ?>
                                <?= htmlspecialchars($author['lifespan']) ?>
                            <?php endif; ?>
                            <?php if (!empty($author['country'])): ?>
                                · <?= htmlspecialchars($author['country']) ?>
                            <?php endif; ?>
                            <?php if (!empty($author['era'])): ?>
                                · <?= htmlspecialchars($author['era']) ?>
                            <?php endif; ?>
                        </p>
                        <p class="canon-author-panel__bio"><?= nl2br(htmlspecialchars($author['biography'] ?? '')) ?></p>
                        <?php if (!empty($author['wikipedia_url'])): ?>
                            <a href="<?= htmlspecialchars($author['wikipedia_url']) ?>" class="btn btn-sm btn-link px-0" target="_blank" rel="noopener">
                                <i class="bi bi-box-arrow-up-right"></i> <span data-lang-key="canon_author_wiki">Read more on Wikipedia</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="canon-read-panel">
                <h3 class="canon-read-panel__title" data-lang-key="canon_read_content">Read the work</h3>
                <div class="canon-read-body">
                    <?php if (!empty($work['content_html'])): ?>
                        <?= $readContent ?>
                    <?php else: ?>
                        <?= nl2br(htmlspecialchars($readContent)) ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($related)): ?>
                <div class="canon-related">
                    <div class="canon-related__head">
                        <h3 class="canon-related__title">
                            <span data-lang-key="canon_related">Related works</span>
                            — <?= htmlspecialchars($author['name'] ?? $work['author'] ?? '') ?>
                        </h3>
                        <span class="canon-related__hint" data-lang-key="canon_drag_hint">
                            <i class="bi bi-arrows-move"></i> Drag to slide
                        </span>
                    </div>
                    <div class="canon-related-carousel">
                        <button type="button" class="canon-carousel-btn canon-carousel-btn--prev" id="canonCarouselPrev" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <div class="canon-related-scroll" id="canonRelatedTrack">
                            <?php foreach ($related as $rel): ?>
                                <a href="<?= URL_ROOT ?>/canon/view?id=<?= (int) $rel['id'] ?>" class="canon-related-card" draggable="false">
                                    <?php if (!empty($rel['cover_image'])): ?>
                                        <img src="<?= htmlspecialchars($rel['cover_image']) ?>" alt="<?= htmlspecialchars($rel['title']) ?>" class="canon-related-card__img" loading="lazy" draggable="false">
                                    <?php else: ?>
                                        <div class="canon-related-card__img" style="background:<?= htmlspecialchars($rel['gradient']) ?>"></div>
                                    <?php endif; ?>
                                    <p class="canon-related-card__title"><?= htmlspecialchars($rel['title']) ?></p>
                                    <p class="canon-related-card__author"><?= htmlspecialchars($rel['genre_label'] ?? '') ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="canon-carousel-btn canon-carousel-btn--next" id="canonCarouselNext" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="canon-tail">
                <div class="canon-tail__wave" aria-hidden="true">
                    <svg viewBox="0 0 1440 80" preserveAspectRatio="none">
                        <path d="M0,48 C240,80 480,0 720,32 C960,64 1200,16 1440,48 L1440,80 L0,80 Z" fill="currentColor"></path>
                    </svg>
                </div>

                <div class="canon-tail__body">
                    <?php if (!empty($work['excerpt'])): ?>
                        <blockquote class="canon-tail__quote">
                            <span class="canon-tail__quote-mark" aria-hidden="true">“</span>
                            <p><?= htmlspecialchars($work['excerpt']) ?></p>
                            <footer>
                                — <?= htmlspecialchars($work['title']) ?>
                                <?php if (!empty($work['author'])): ?>
                                    <span class="canon-tail__quote-author"><?= htmlspecialchars($work['author']) ?></span>
                                <?php endif; ?>
                            </footer>
                        </blockquote>
                    <?php endif; ?>

                    <div class="canon-tail__grid">
                        <?php if (!empty($genrePeers)): ?>
                            <div class="canon-tail__panel">
                                <div class="canon-tail__panel-head">
                                    <span class="canon-tail__panel-icon"><i class="bi bi-collection"></i></span>
                                    <div>
                                        <h4 data-lang-key="canon_tail_genre">Same genre</h4>
                                        <p><?= htmlspecialchars($work['genre_label'] ?? '') ?></p>
                                    </div>
                                </div>
                                <ul class="canon-tail__mini-list">
                                    <?php foreach ($genrePeers as $peer): ?>
                                        <li>
                                            <a href="<?= URL_ROOT ?>/canon/view?id=<?= (int) $peer['id'] ?>">
                                                <?php if (!empty($peer['cover_image'])): ?>
                                                    <img src="<?= htmlspecialchars($peer['cover_image']) ?>" alt="" loading="lazy">
                                                <?php else: ?>
                                                    <span class="canon-tail__mini-fallback" style="background:<?= htmlspecialchars($peer['gradient'] ?? '#4154f1') ?>"></span>
                                                <?php endif; ?>
                                                <span>
                                                    <strong><?= htmlspecialchars($peer['title']) ?></strong>
                                                    <small><?= htmlspecialchars($peer['author'] ?? '') ?></small>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="canon-tail__panel canon-tail__panel--spotlight">
                            <div class="canon-tail__panel-head">
                                <span class="canon-tail__panel-icon"><i class="bi bi-stars"></i></span>
                                <div>
                                    <h4 data-lang-key="canon_tail_spotlight">Spotlight pick</h4>
                                    <p data-lang-key="canon_tail_spotlight_sub">A work you might enjoy next</p>
                                </div>
                            </div>
                            <?php if ($dailyPick): ?>
                                <a href="<?= URL_ROOT ?>/canon/view?id=<?= (int) $dailyPick['id'] ?>" class="canon-tail__spotlight">
                                    <?php if (!empty($dailyPick['cover_image'])): ?>
                                        <img src="<?= htmlspecialchars($dailyPick['cover_image']) ?>" alt="<?= htmlspecialchars($dailyPick['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="canon-tail__spotlight-fallback" style="background:<?= htmlspecialchars($dailyPick['gradient'] ?? '#7c3aed') ?>"></div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="canon-tail__spotlight-label" data-lang-key="canon_tail_try">Try reading</p>
                                        <h5><?= htmlspecialchars($dailyPick['title']) ?></h5>
                                        <p><?= htmlspecialchars($dailyPick['author'] ?? '') ?></p>
                                    </div>
                                    <i class="bi bi-arrow-right-circle"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="canon-tail__panel canon-tail__panel--actions">
                            <div class="canon-tail__panel-head">
                                <span class="canon-tail__panel-icon"><i class="bi bi-compass"></i></span>
                                <div>
                                    <h4 data-lang-key="canon_tail_explore">Keep exploring</h4>
                                    <p data-lang-key="canon_tail_explore_sub">Jump back into the library</p>
                                </div>
                            </div>
                            <div class="canon-tail__actions">
                                <a href="<?= URL_ROOT ?>/canon" class="canon-tail__action">
                                    <i class="bi bi-bookshelf"></i>
                                    <span data-lang-key="canon_tail_library">Browse all works</span>
                                </a>
                                <a href="<?= URL_ROOT ?>/canon?region=<?= urlencode($work['region'] ?? '') ?>" class="canon-tail__action">
                                    <i class="bi bi-globe-asia-australia"></i>
                                    <span>
                                        <?= ($work['region'] ?? '') === 'vietnam' ? '🇻🇳 ' : '🌍 ' ?>
                                        <span data-lang-key="canon_tail_region">Works from this region</span>
                                    </span>
                                </a>
                                <a href="<?= URL_ROOT ?>/workspace" class="canon-tail__action canon-tail__action--primary">
                                    <i class="bi bi-search"></i>
                                    <span data-lang-key="canon_tail_workspace">Search in workspace</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="canon-tail__ribbon">
                        <div class="canon-tail__ribbon-item">
                            <i class="bi bi-journal-richtext"></i>
                            <span><?= $canonTotal ?> <span data-lang-key="canon_tail_works">literary works</span></span>
                        </div>
                        <?php if (!empty($work['genre_label'])): ?>
                            <div class="canon-tail__ribbon-item">
                                <i class="bi bi-tag"></i>
                                <span><?= htmlspecialchars($work['genre_label']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($work['era'])): ?>
                            <div class="canon-tail__ribbon-item">
                                <i class="bi bi-hourglass-split"></i>
                                <span><?= htmlspecialchars($work['era']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="canon-tail__ribbon-item canon-tail__ribbon-item--muted">
                            <i class="bi bi-bookmark-heart"></i>
                            <span data-lang-key="canon_tail_saved">Saved in your knowledge base</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>
    <?php if (!empty($related)): ?>
    <script>
    (function() {
        const track = document.getElementById('canonRelatedTrack');
        const prevBtn = document.getElementById('canonCarouselPrev');
        const nextBtn = document.getElementById('canonCarouselNext');
        if (!track) return;

        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;
        let dragged = false;

        function updateNav() {
            if (!prevBtn || !nextBtn) return;
            const max = track.scrollWidth - track.clientWidth - 2;
            prevBtn.disabled = track.scrollLeft <= 2;
            nextBtn.disabled = track.scrollLeft >= max;
        }

        function slideBy(direction) {
            const card = track.querySelector('.canon-related-card');
            const step = card ? card.offsetWidth + 16 : 166;
            track.scrollBy({ left: direction * step, behavior: 'smooth' });
        }

        track.addEventListener('mousedown', function(e) {
            if (e.button !== 0) return;
            isDown = true;
            dragged = false;
            track.classList.add('is-dragging');
            startX = e.pageX;
            scrollLeft = track.scrollLeft;
        });

        window.addEventListener('mouseup', function() {
            if (!isDown) return;
            isDown = false;
            track.classList.remove('is-dragging');
            setTimeout(function() { dragged = false; }, 50);
        });

        track.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const walk = e.pageX - startX;
            if (Math.abs(walk) > 4) dragged = true;
            track.scrollLeft = scrollLeft - walk;
            updateNav();
        });

        track.addEventListener('click', function(e) {
            const link = e.target.closest('a.canon-related-card');
            if (link && dragged) {
                e.preventDefault();
            }
        }, true);

        track.addEventListener('wheel', function(e) {
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                e.preventDefault();
                track.scrollLeft += e.deltaY;
                updateNav();
            }
        }, { passive: false });

        track.addEventListener('scroll', updateNav, { passive: true });

        if (prevBtn) prevBtn.addEventListener('click', function() { slideBy(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function() { slideBy(1); });

        updateNav();
        window.addEventListener('resize', updateNav);
    })();
    </script>
    <?php endif; ?>
</body>

</html>
