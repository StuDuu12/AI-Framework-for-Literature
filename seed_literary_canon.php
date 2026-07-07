<?php
/**
 * Seed literary authors + full readable content for canon works.
 * Run: php seed_literary_canon.php
 */
require_once __DIR__ . '/config/db.php';

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
    $stmt->execute([':col' => $column]);
    return (bool) $stmt->fetch();
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
    return $stmt && $stmt->rowCount() > 0;
}

// --- Schema extend ---
$extendSql = file_get_contents(__DIR__ . '/database/literary_canon_extend.sql');
$extendSql = preg_replace('/ADD COLUMN IF NOT EXISTS/', 'ADD COLUMN', $extendSql);
$extendSql = preg_replace('/ADD KEY IF NOT EXISTS/', 'ADD KEY', $extendSql);

foreach (array_filter(array_map('trim', explode(';', $extendSql))) as $stmt) {
    if ($stmt === '' || stripos($stmt, 'SET NAMES') === 0) {
        continue;
    }
    if (stripos($stmt, 'CREATE TABLE') !== false && stripos($stmt, 'literary_author') !== false) {
        $pdo->exec($stmt);
        continue;
    }
    if (stripos($stmt, 'ALTER TABLE') !== false) {
        if (!tableExists($pdo, 'literary_work')) {
            echo "literary_work missing — run apply_literary_canon.php first\n";
            exit(1);
        }
        foreach (['author_id', 'full_content', 'content_html'] as $col) {
            if (!columnExists($pdo, 'literary_work', $col)) {
                if ($col === 'author_id') {
                    $pdo->exec('ALTER TABLE literary_work ADD COLUMN author_id int DEFAULT NULL AFTER author');
                } elseif ($col === 'full_content') {
                    $pdo->exec('ALTER TABLE literary_work ADD COLUMN full_content longtext AFTER description');
                } elseif ($col === 'content_html') {
                    $pdo->exec('ALTER TABLE literary_work ADD COLUMN content_html tinyint(1) NOT NULL DEFAULT 0 AFTER full_content');
                }
            }
        }
        if (!columnExists($pdo, 'literary_work', 'author_id')) {
            continue;
        }
        try {
            $pdo->exec('ALTER TABLE literary_work ADD KEY idx_lw_author (author_id)');
        } catch (PDOException $e) {
            // index may exist
        }
        continue;
    }
    try {
        $pdo->exec($stmt);
    } catch (PDOException $e) {
        // ignore duplicate
    }
}

echo "Schema extended.\n";

// --- Authors ---
$authors = [
    ['Nguyễn Du', 'nguyen-du', 'vietnam', 'Vietnam', '1765', '1820', 'Thời Lê – Nguyễn',
        'Đại thi hào dân tộc, tác giả Truyện Kiều — kiệt tác thơ Nôm bất hủ. Ông kết hợp tinh hoa văn học Trung Hoa với đời sống và tâm hồn người Việt, tạo nên tác phẩm có giá trị nhân văn sâu sắc về số phận con người.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Portrait_of_Nguy%E1%BB%85n_Du.jpg/440px-Portrait_of_Nguy%E1%BB%85n_Du.jpg',
        'https://vi.wikipedia.org/wiki/Nguyễn_Du'],
    ['Nam Cao', 'nam-cao', 'vietnam', 'Vietnam', '1915', '1951', 'Hiện thực phê phán',
        'Nhà văn hiện thực phê phán tiêu biểu. Tác phẩm như Lão Hạc, Chí Phèo, Dì Hảo bộc lộ số phận người nông dân nghèo đói, lòng tự trọng và phẩm giá con người dưới ách bóc lột phong kiến.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8e/Nam_Cao.jpg/440px-Nam_Cao.jpg',
        'https://vi.wikipedia.org/wiki/Nam_Cao'],
    ['Kim Lân', 'kim-lan', 'vietnam', 'Vietnam', '1920', '1987', 'Hiện thực phê phán',
        'Nhà văn nổi tiếng với các truyện ngắn về người lao động nghèo. Vợ nhặt là kiệt tác về nạn đói 1945, ca ngợi tình yêu và nhân phẩm giữa cảnh khốn cùng.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9f/Kim_Lan.jpg/440px-Kim_Lan.jpg',
        'https://vi.wikipedia.org/wiki/Kim_Lân'],
    ['Vũ Trọng Phụng', 'vu-trong-phung', 'vietnam', 'Vietnam', '1912', '1939', 'Hiện thực phê phán',
        'Nhà văn hiện thực phê phán xuất sắc, châm biếm sắc bén đời sống đô thị và tầng lớp trung lưu Hà Nội thời thuộc địa. Số đỏ là tiểu thuyết tiêu biểu.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Vu_Trong_Phung.jpg/440px-Vu_Trong_Phung.jpg',
        'https://vi.wikipedia.org/wiki/Vũ_Trọng_Phụng'],
    ['Tô Hoài', 'to-hoai', 'vietnam', 'Vietnam', '1920', '2014', 'Văn học thiếu nhi',
        'Nhà văn nổi tiếng với văn học thiếu nhi và miền núi Tây Bắc. Dế Mèn phiêu lưu ký là tác phẩm kinh điển giáo dục lòng dũng cảm, trung thực cho nhiều thế hệ.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/To_Hoai_2008.jpg/440px-To_Hoai_2008.jpg',
        'https://vi.wikipedia.org/wiki/Tô_Hoài'],
    ['Nguyễn Bính', 'nguyen-binh', 'vietnam', 'Vietnam', '1918', '1966', 'Thơ mới',
        'Thi sĩ nổi tiếng với thơ tình và nỗi nhớ quê hương. Chân quê, Lỡ bước sang ngang là những bài thơ được yêu thích trong chương trình phổ thông.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0c/Nguyen_Binh.jpg/440px-Nguyen_Binh.jpg',
        'https://vi.wikipedia.org/wiki/Nguyễn_Bính'],
    ['Phạm Tiến Duật', 'pham-tien-duat', 'vietnam', 'Vietnam', '1941', '2007', 'Thơ kháng chiến',
        'Nhà thơ kháng chiến, tác giả Bài thơ về tiểu đội xe không kính — biểu tượng tinh thần lạc quan, kiên cường của người lính trên đường Trường Sơn.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Pham_Tien_Duat.jpg/440px-Pham_Tien_Duat.jpg',
        'https://vi.wikipedia.org/wiki/Phạm_Tiến_Duật'],
    ['Hàn Mặc Tử', 'han-mac-tu', 'vietnam', 'Vietnam', '1912', '1940', 'Thơ Đường',
        'Thi sĩ trường phái Mới, nổi tiếng với thơ siêu thực, đau khổ và tìm kiếm cái đẹp. Đau thương, Đây thôn Vĩ Dạnh là những bài thơ tiêu biểu.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/Han_Mac_Tu.jpg/440px-Han_Mac_Tu.jpg',
        'https://vi.wikipedia.org/wiki/Hàn_Mặc_Tử'],
    ['Quang Dũng', 'quang-dung', 'vietnam', 'Vietnam', '1921', '1988', 'Thơ kháng chiến',
        'Nhà thơ, tướng quân đội. Tây Tiến là bài thơ nổi tiếng về chiến tranh và nỗi nhớ, với ngôn ngữ giàu hình ảnh độc đáo.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Quang_Dung.jpg/440px-Quang_Dung.jpg',
        'https://vi.wikipedia.org/wiki/Quang_Dũng_(nhà_thơ)'],
    ['Chính Hữu', 'chinh-huu', 'vietnam', 'Vietnam', '1926', '2007', 'Thơ kháng chiến',
        'Nhà thơ kháng chiến, tác giả Đồng chí — biểu tượng tình đồng đội, tình yêu thương giữa những người cùng chung một chí hướng.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4a/Chinh_Huu.jpg/440px-Chinh_Huu.jpg',
        'https://vi.wikipedia.org/wiki/Chính_Hữu'],
    ['Lê Minh Khuê', 'le-minh-khue', 'vietnam', 'Vietnam', '1949', NULL, 'Văn học chiến tranh',
        'Nhà văn nữ nổi tiếng với các tác phẩm về chiến tranh và phụ nữ. Những ngôi sao xa xôi là truyện ngắn điển hình văn học đổi mới.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9e/Vietnam_War_protestors.jpg/440px-Vietnam_War_protestors.jpg',
        'https://vi.wikipedia.org/wiki/Lê_Minh_Khuê'],
    ['Nguyễn Minh Châu', 'nguyen-minh-chau', 'vietnam', 'Vietnam', '1931', '1989', 'Văn học đổi mới',
        'Nhà văn văn học đổi mới, tác giả Chiếc thuyền ngoài xa — mở ra không gian tâm lý và xung đột đạo đức mới trong văn học Việt.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/Fishing_boats_Vietnam.jpg/440px-Fishing_boats_Vietnam.jpg',
        'https://vi.wikipedia.org/wiki/Nguyễn_Minh_Châu'],
    ['Ngô Tất Tố', 'ngo-tat-to', 'vietnam', 'Vietnam', '1894', '1954', 'Hiện thực phê phán',
        'Nhà văn hiện thực phê phán, tác giả Tắt đèn — tiểu thuyết tiêu biểu về người phụ nữ nông dân dưới ách đô hộ phong kiến.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Ngo_Tat_To.jpg/440px-Ngo_Tat_To.jpg',
        'https://vi.wikipedia.org/wiki/Ngô_Tất_Tố'],
    ['Nguyễn Huy Thiệp', 'nguyen-huy-thiep', 'vietnam', 'Vietnam', '1950', NULL, 'Văn học đổi mới',
        'Nhà văn gây tranh luận với các tác phẩm về nông thôn và đổi mới. Bến quê là truyện ngắn nổi tiếng mở ra cách nhìn mới về làng quê Việt.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Vietnamese_village.jpg/440px-Vietnamese_village.jpg',
        'https://vi.wikipedia.org/wiki/Nguyễn_Huy_Thiệp'],
    ['Huy Cận', 'huy-can', 'vietnam', 'Vietnam', '1919', '2002', 'Thơ mới',
        'Nhà thơ thuộc thế hệ Thơ mới, nổi tiếng với Thu bất chợt, Nhớ rừng — thơ buồn man mác, gắn với thiên nhiên và ký ức.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a7/Autumn_leaves.jpg/440px-Autumn_leaves.jpg',
        'https://vi.wikipedia.org/wiki/Huy_Cận'],
    ['William Shakespeare', 'william-shakespeare', 'international', 'England', '1564', '1616', 'Renaissance',
        'Playwright and poet, greatest writer in the English language. His tragedies (Hamlet, Romeo and Juliet) and comedies shaped world drama and explored human nature with unmatched depth.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Romeo_and_juliet_brown.jpg/440px-Romeo_and_juliet_brown.jpg',
        'https://en.wikipedia.org/wiki/William_Shakespeare'],
    ['Jane Austen', 'jane-austen', 'international', 'England', '1775', '1817', 'Regency',
        'Novelist known for wit, social observation, and romance. Pride and Prejudice remains one of the most beloved novels in English literature.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/PrideAndPrejudiceTitlePage.jpg/440px-PrideAndPrejudiceTitlePage.jpg',
        'https://en.wikipedia.org/wiki/Jane_Austen'],
    ['George Orwell', 'george-orwell', 'international', 'England', '1903', '1950', '20th century',
        'Writer and essayist, author of 1984 and Animal Farm — powerful critiques of totalitarianism, propaganda, and the abuse of political power.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c3/1984_first_edition_cover.jpg/440px-1984_first_edition_cover.jpg',
        'https://en.wikipedia.org/wiki/George_Orwell'],
    ['F. Scott Fitzgerald', 'f-scott-fitzgerald', 'international', 'USA', '1896', '1940', 'Jazz Age',
        'American novelist of the Jazz Age. The Great Gatsby captures ambition, wealth, and disillusionment in 1920s America.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/The_Great_Gatsby_Cover_1925_Retouched.jpg/440px-The_Great_Gatsby_Cover_1925_Retouched.jpg',
        'https://en.wikipedia.org/wiki/F._Scott_Fitzgerald'],
    ['Gabriel García Márquez', 'gabriel-garcia-marquez', 'international', 'Colombia', '1927', '2014', 'Magical realism',
        'Nobel laureate, master of magical realism. One Hundred Years of Solitude chronicles the Buendía family in the fictional town of Macondo.',
        'https://upload.wikimedia.org/wikipedia/en/thumb/b/b5/One_Hundred_Years_of_Solitude_%28first_edition_in_Spanish%29.jpg/440px-One_Hundred_Years_of_Solitude_%28first_edition_in_Spanish%29.jpg',
        'https://en.wikipedia.org/wiki/Gabriel_Garc%C3%ADa_M%C3%A1rquez'],
    ['Homer', 'homer', 'international', 'Greece', '8th c. BCE', NULL, 'Ancient',
        'Legendary ancient Greek poet, traditionally credited with the Iliad and the Odyssey — foundational epics of Western literature.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/5/59/Odysseus_Sculpture.jpg/440px-Odysseus_Sculpture.jpg',
        'https://en.wikipedia.org/wiki/Homer'],
    ['Dante Alighieri', 'dante-alighieri', 'international', 'Italy', '1265', '1321', 'Medieval',
        'Italian poet, author of The Divine Comedy — an allegorical journey through Hell, Purgatory, and Paradise that defined Italian literature.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Dante_Alighieri.jpg/440px-Dante_Alighieri.jpg',
        'https://en.wikipedia.org/wiki/Dante_Alighieri'],
    ['Victor Hugo', 'victor-hugo', 'international', 'France', '1802', '1885', 'Romanticism',
        'French Romantic writer and humanist. Les Misérables is an epic of justice, redemption, and revolution in 19th-century France.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5f/Victor_Hugo_by_Nadar_1876_full.jpg/440px-Victor_Hugo_by_Nadar_1876_full.jpg',
        'https://en.wikipedia.org/wiki/Victor_Hugo'],
    ['Gustave Flaubert', 'gustave-flaubert', 'international', 'France', '1821', '1880', 'Realism',
        'French novelist, pioneer of literary realism. Madame Bovary portrays desire, boredom, and bourgeois life with meticulous style.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Gustave_Flaubert.jpg/440px-Gustave_Flaubert.jpg',
        'https://en.wikipedia.org/wiki/Gustave_Flaubert'],
    ['Fyodor Dostoevsky', 'fyodor-dostoevsky', 'international', 'Russia', '1821', '1881', '19th century',
        'Russian novelist exploring guilt, faith, and psychology. Crime and Punishment follows Raskolnikov\'s moral crisis in St. Petersburg.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Dostoevsky_1872.jpg/440px-Dostoevsky_1872.jpg',
        'https://en.wikipedia.org/wiki/Fyodor_Dostoevsky'],
    ['Leo Tolstoy', 'leo-tolstoy', 'international', 'Russia', '1828', '1910', '19th century',
        'Russian writer, author of War and Peace and Anna Karenina — panoramic novels of society, history, and the human soul.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c6/L.N.Tolstoy_Prokudin-Gorsky.jpg/440px-L.N.Tolstoy_Prokudin-Gorsky.jpg',
        'https://en.wikipedia.org/wiki/Leo_Tolstoy'],
    ['Franz Kafka', 'franz-kafka', 'international', 'Austria', '1883', '1924', 'Modernism',
        'German-language writer of alienation and absurdity. The Metamorphosis opens with Gregor Samsa\'s transformation into an insect.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4c/Kafka1906.jpg/440px-Kafka1906.jpg',
        'https://en.wikipedia.org/wiki/Franz_Kafka'],
    ['Miguel de Cervantes', 'miguel-de-cervantes', 'international', 'Spain', '1547', '1616', 'Golden Age',
        'Spanish writer, author of Don Quixote — often called the first modern novel, blending chivalry, illusion, and reality.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/5/57/Don_Quijote_y_Sancho_Panza.jpg/440px-Don_Quijote_y_Sancho_Panza.jpg',
        'https://en.wikipedia.org/wiki/Miguel_de_Cervantes'],
    ['Antoine de Saint-Exupéry', 'saint-exupery', 'international', 'France', '1900', '1944', '20th century',
        'French writer and aviator. The Little Prince is a philosophical fable about love, loss, and seeing with the heart.',
        'https://upload.wikimedia.org/wikipedia/en/thumb/0/05/Littleprince.JPG/440px-Littleprince.JPG',
        'https://en.wikipedia.org/wiki/Antoine_de_Saint-Exup%C3%A9ry'],
    ['Johann Wolfgang von Goethe', 'johann-goethe', 'international', 'Germany', '1749', '1832', 'Romanticism',
        'German writer, scientist, and statesman. Faust dramatizes the scholar\'s pact with Mephistopheles and the search for meaning.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2b/Goethe_%281828%29_by_Joseph_Karl_Stieler.jpg/440px-Goethe_%281828%29_by_Joseph_Karl_Stieler.jpg',
        'https://en.wikipedia.org/wiki/Johann_Wolfgang_von_Goethe'],
    ['Albert Camus', 'albert-camus', 'international', 'France', '1913', '1960', 'Existentialism',
        'French-Algerian philosopher and novelist. The Stranger explores absurdity, detachment, and moral indifference through Meursault.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0f/Albert_Camus%2C_gouvernement.jpg/440px-Albert_Camus%2C_gouvernement.jpg',
        'https://en.wikipedia.org/wiki/Albert_Camus'],
    ['Harper Lee', 'harper-lee', 'international', 'USA', '1926', '2016', '20th century',
        'American novelist. To Kill a Mockingbird addresses racial injustice and moral courage in the American South through Scout Finch\'s eyes.',
        'https://upload.wikimedia.org/wikipedia/en/thumb/7/79/To_Kill_a_Mockingbird_%28first_edition_cover%29.jpg/440px-To_Kill_a_Mockingbird_%28first_edition_cover%29.jpg',
        'https://en.wikipedia.org/wiki/Harper_Lee'],
    ['Ernest Hemingway', 'ernest-hemingway', 'international', 'USA', '1899', '1961', '20th century',
        'American novelist and Nobel laureate. The Old Man and the Sea tells of Santiago\'s struggle with a giant marlin — dignity against nature.',
        'https://upload.wikimedia.org/wikipedia/en/thumb/7/73/Oldmansea.jpg/440px-Oldmansea.jpg',
        'https://en.wikipedia.org/wiki/Ernest_Hemingway'],
    ['Lewis Carroll', 'lewis-carroll', 'international', 'England', '1832', '1898', 'Victorian',
        'English writer and mathematician. Alice\'s Adventures in Wonderland is a fantasy journey through logic, identity, and childhood imagination.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/Alice_in_Wonderland_by_George_Dunlop_Leslie.jpg/440px-Alice_in_Wonderland_by_George_Dunlop_Leslie.jpg',
        'https://en.wikipedia.org/wiki/Lewis_Carroll'],
];

$insAuthor = $pdo->prepare("
    INSERT INTO literary_author (name, slug, region, country, birth_year, death_year, era, biography, portrait_image, wikipedia_url)
    VALUES (:name, :slug, :region, :country, :birth, :death, :era, :bio, :img, :wiki)
    ON DUPLICATE KEY UPDATE
        biography = VALUES(biography),
        portrait_image = VALUES(portrait_image),
        wikipedia_url = VALUES(wikipedia_url)
");

$authorIds = [];
foreach ($authors as $a) {
    $insAuthor->execute([
        ':name' => $a[0], ':slug' => $a[1], ':region' => $a[2], ':country' => $a[3],
        ':birth' => $a[4], ':death' => $a[5], ':era' => $a[6], ':bio' => $a[7],
        ':img' => $a[8], ':wiki' => $a[9],
    ]);
    $stmt = $pdo->prepare('SELECT id FROM literary_author WHERE slug = :slug');
    $stmt->execute([':slug' => $a[1]]);
    $authorIds[$a[0]] = (int) $stmt->fetchColumn();
}

echo count($authorIds) . " authors seeded.\n";

$updWork = $pdo->prepare('UPDATE literary_work SET author_id = :aid WHERE author = :name');
foreach ($authorIds as $name => $aid) {
    $updWork->execute([':aid' => $aid, ':name' => $name]);
}

// --- Full content by title ---
$contentFile = __DIR__ . '/database/literary_content_data.php';
if (!is_readable($contentFile)) {
    echo "Warning: literary_content_data.php not found — skipping content.\n";
    exit(0);
}

$contents = require $contentFile;
$updContent = $pdo->prepare('UPDATE literary_work SET full_content = :content, content_html = :html WHERE title = :title');
$updated = 0;
foreach ($contents as $title => $data) {
    $updContent->execute([
        ':content' => $data['content'],
        ':html' => !empty($data['html']) ? 1 : 0,
        ':title' => $title,
    ]);
    if ($updContent->rowCount() > 0) {
        $updated++;
    }
}

// Fallback: works without dedicated content get description + excerpt
$stmt = $pdo->query("SELECT title, description, excerpt, genre_label, author FROM literary_work WHERE full_content IS NULL OR full_content = ''");
$fallbackUpd = $pdo->prepare('UPDATE literary_work SET full_content = :content, content_html = 1 WHERE title = :title');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $html = '<h2>Giới thiệu</h2><p>' . htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
    if (!empty($row['excerpt'])) {
        $html .= '<h2>Trích đoạn</h2><p class="canon-verse"><em>' . htmlspecialchars($row['excerpt'], ENT_QUOTES, 'UTF-8') . '</em></p>';
    }
    $html .= '<p><em>— ' . htmlspecialchars($row['author'] ?? '', ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars($row['genre_label'] ?? '', ENT_QUOTES, 'UTF-8') . '</em></p>';
    $fallbackUpd->execute([':content' => $html, ':title' => $row['title']]);
    $updated++;
}

echo "{$updated} works received full content.\n";
echo "Done.\n";
