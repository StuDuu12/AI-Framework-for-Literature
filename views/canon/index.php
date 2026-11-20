<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <link href="<?= URL_ROOT ?>/assets/css/literary-canon.css" rel="stylesheet">
    <title data-lang-key="canon_title">Literary Canon — Knowledge Base</title>
</head>

<body class="canon-page">
    <?php
    require_once __DIR__ . '/../../config/helper_workspace.php';
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'student') {
        header('Location: ' . URL_ROOT . '/dashboard');
        exit;
    }

    $filters = getCanonListFiltersFromRequest();
    $works = searchCanonForLibrary($filters);
    $totalCount = count(getLiteraryCanonWorks());

    $baseUrl = URL_ROOT . '/canon';
    $activeRegion = $filters['region'] ?? '';
    $activeGenre = $filters['genre'] ?? '';
    $searchQ = $filters['q'] ?? '';

    function canonFilterUrl($base, $region, $genre, $q = '')
    {
        $params = [];
        if ($region) {
            $params['region'] = $region;
        }
        if ($genre) {
            $params['genre'] = $genre;
        }
        if ($q !== '') {
            $params['q'] = $q;
        }
        return $base . ($params ? '?' . http_build_query($params) : '');
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="canon_title">Literary Canon</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/workspace" data-lang-key="menu_workspace">Thư viện tác phẩm</a></li>
                    <li class="breadcrumb-item active" data-lang-key="canon_breadcrumb">Kho tri thức</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <p class="text-muted mb-3" data-lang-key="canon_subtitle">
                Explore <?= $totalCount ?> masterpieces from Vietnam and world literature — read full texts and learn about each author.
            </p>

            <div class="canon-toolbar">
                <form class="canon-toolbar__search" method="get" action="<?= $baseUrl ?>">
                    <i class="bi bi-search"></i>
                    <input aria-label="Tìm tên tác phẩm, tác giả" type="search" name="q" value="<?= htmlspecialchars($searchQ) ?>"
                        placeholder="Tìm tên tác phẩm, tác giả…"
                        data-lang-key="canon_search_placeholder">
                    <?php if ($activeRegion): ?><input type="hidden" name="region" value="<?= htmlspecialchars($activeRegion) ?>"><?php endif; ?>
                    <?php if ($activeGenre): ?><input type="hidden" name="genre" value="<?= htmlspecialchars($activeGenre) ?>"><?php endif; ?>
                </form>
                <div class="canon-filters">
                    <a href="<?= canonFilterUrl($baseUrl, '', '', $searchQ) ?>"
                        class="canon-filter-chip <?= !$activeRegion && !$activeGenre ? 'is-active' : '' ?>"
                        data-lang-key="workspace_filter_all">All</a>
                    <a href="<?= canonFilterUrl($baseUrl, 'vietnam', '', $searchQ) ?>"
                        class="canon-filter-chip <?= $activeRegion === 'vietnam' ? 'is-active' : '' ?>"
                        data-lang-key="workspace_filter_vn">Việt Nam</a>
                    <a href="<?= canonFilterUrl($baseUrl, 'international', '', $searchQ) ?>"
                        class="canon-filter-chip <?= $activeRegion === 'international' ? 'is-active' : '' ?>"
                        data-lang-key="workspace_filter_intl">Thế giới</a>
                    <a href="<?= canonFilterUrl($baseUrl, $activeRegion, 'poem', $searchQ) ?>"
                        class="canon-filter-chip <?= $activeGenre === 'poem' ? 'is-active' : '' ?>"
                        data-lang-key="workspace_filter_poem">Thơ</a>
                    <a href="<?= canonFilterUrl($baseUrl, $activeRegion, 'story', $searchQ) ?>"
                        class="canon-filter-chip <?= $activeGenre === 'story' ? 'is-active' : '' ?>"
                        data-lang-key="workspace_filter_story">Truyện</a>
                    <a href="<?= canonFilterUrl($baseUrl, $activeRegion, 'novel', $searchQ) ?>"
                        class="canon-filter-chip <?= $activeGenre === 'novel' ? 'is-active' : '' ?>"
                        data-lang-key="workspace_filter_novel">Tiểu thuyết</a>
                </div>
            </div>

            <?php if (empty($works)): ?>
                <div class="canon-empty">
                    <i class="bi bi-book display-4 d-block mb-2 text-muted"></i>
                    <p data-lang-key="canon_no_results">Không có tác phẩm phù hợp. Thử từ khoá khác.</p>
                </div>
            <?php else: ?>
                <div class="canon-grid">
                    <?php foreach ($works as $work): ?>
                        <a href="<?= URL_ROOT ?>/canon/view?id=<?= (int) $work['id'] ?>" class="canon-card">
                            <div class="canon-card__cover">
                                <?php if (!empty($work['cover_image'])): ?>
                                    <img src="<?= htmlspecialchars($work['cover_image']) ?>" alt="<?= htmlspecialchars($work['title']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:<?= htmlspecialchars($work['gradient']) ?>"></div>
                                <?php endif; ?>
                                <span class="canon-card__badge <?= ($work['region'] ?? '') === 'vietnam' ? 'canon-card__badge--vn' : 'canon-card__badge--intl' ?>">
                                    <?= ($work['region'] ?? '') === 'vietnam' ? 'VN' : 'World' ?>
                                </span>
                            </div>
                            <div class="canon-card__body">
                                <p class="canon-card__title"><?= htmlspecialchars($work['title']) ?></p>
                                <p class="canon-card__author"><?= htmlspecialchars($work['author'] ?? '') ?></p>
                                <p class="canon-card__meta"><?= htmlspecialchars($work['genre_label'] ?? '') ?><?= !empty($work['era']) ? ' · ' . htmlspecialchars($work['era']) : '' ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>
</body>

</html>
