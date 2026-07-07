<?php
/**
 * Seed literary authors + full readable content for canon works.
 * Run: php seed_literary_canon.php
 */
require_once __DIR__ . '/../../config/db.php';

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
$extendSql = file_get_contents(__DIR__ . '/../../database/literary_canon_extend.sql');
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
            echo "literary_work missing â€” run apply_literary_canon.php first\n";
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
    ['Nguyá»…n Du', 'nguyen-du', 'vietnam', 'Vietnam', '1765', '1820', 'Thá»i LÃª â€“ Nguyá»…n',
        'Äáº¡i thi hÃ o dÃ¢n tá»™c, tÃ¡c giáº£ Truyá»‡n Kiá»u â€” kiá»‡t tÃ¡c thÆ¡ NÃ´m báº¥t há»§. Ã”ng káº¿t há»£p tinh hoa vÄƒn há»c Trung Hoa vá»›i Ä‘á»i sá»‘ng vÃ  tÃ¢m há»“n ngÆ°á»i Viá»‡t, táº¡o nÃªn tÃ¡c pháº©m cÃ³ giÃ¡ trá»‹ nhÃ¢n vÄƒn sÃ¢u sáº¯c vá» sá»‘ pháº­n con ngÆ°á»i.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Portrait_of_Nguy%E1%BB%85n_Du.jpg/440px-Portrait_of_Nguy%E1%BB%85n_Du.jpg',
        'https://vi.wikipedia.org/wiki/Nguyá»…n_Du'],
    ['Nam Cao', 'nam-cao', 'vietnam', 'Vietnam', '1915', '1951', 'Hiá»‡n thá»±c phÃª phÃ¡n',
        'NhÃ  vÄƒn hiá»‡n thá»±c phÃª phÃ¡n tiÃªu biá»ƒu. TÃ¡c pháº©m nhÆ° LÃ£o Háº¡c, ChÃ­ PhÃ¨o, DÃ¬ Háº£o bá»™c lá»™ sá»‘ pháº­n ngÆ°á»i nÃ´ng dÃ¢n nghÃ¨o Ä‘Ã³i, lÃ²ng tá»± trá»ng vÃ  pháº©m giÃ¡ con ngÆ°á»i dÆ°á»›i Ã¡ch bÃ³c lá»™t phong kiáº¿n.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8e/Nam_Cao.jpg/440px-Nam_Cao.jpg',
        'https://vi.wikipedia.org/wiki/Nam_Cao'],
    ['Kim LÃ¢n', 'kim-lan', 'vietnam', 'Vietnam', '1920', '1987', 'Hiá»‡n thá»±c phÃª phÃ¡n',
        'NhÃ  vÄƒn ná»•i tiáº¿ng vá»›i cÃ¡c truyá»‡n ngáº¯n vá» ngÆ°á»i lao Ä‘á»™ng nghÃ¨o. Vá»£ nháº·t lÃ  kiá»‡t tÃ¡c vá» náº¡n Ä‘Ã³i 1945, ca ngá»£i tÃ¬nh yÃªu vÃ  nhÃ¢n pháº©m giá»¯a cáº£nh khá»‘n cÃ¹ng.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9f/Kim_Lan.jpg/440px-Kim_Lan.jpg',
        'https://vi.wikipedia.org/wiki/Kim_LÃ¢n'],
    ['VÅ© Trá»ng Phá»¥ng', 'vu-trong-phung', 'vietnam', 'Vietnam', '1912', '1939', 'Hiá»‡n thá»±c phÃª phÃ¡n',
        'NhÃ  vÄƒn hiá»‡n thá»±c phÃª phÃ¡n xuáº¥t sáº¯c, chÃ¢m biáº¿m sáº¯c bÃ©n Ä‘á»i sá»‘ng Ä‘Ã´ thá»‹ vÃ  táº§ng lá»›p trung lÆ°u HÃ  Ná»™i thá»i thuá»™c Ä‘á»‹a. Sá»‘ Ä‘á» lÃ  tiá»ƒu thuyáº¿t tiÃªu biá»ƒu.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Vu_Trong_Phung.jpg/440px-Vu_Trong_Phung.jpg',
        'https://vi.wikipedia.org/wiki/VÅ©_Trá»ng_Phá»¥ng'],
    ['TÃ´ HoÃ i', 'to-hoai', 'vietnam', 'Vietnam', '1920', '2014', 'VÄƒn há»c thiáº¿u nhi',
        'NhÃ  vÄƒn ná»•i tiáº¿ng vá»›i vÄƒn há»c thiáº¿u nhi vÃ  miá»n nÃºi TÃ¢y Báº¯c. Dáº¿ MÃ¨n phiÃªu lÆ°u kÃ½ lÃ  tÃ¡c pháº©m kinh Ä‘iá»ƒn giÃ¡o dá»¥c lÃ²ng dÅ©ng cáº£m, trung thá»±c cho nhiá»u tháº¿ há»‡.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/To_Hoai_2008.jpg/440px-To_Hoai_2008.jpg',
        'https://vi.wikipedia.org/wiki/TÃ´_HoÃ i'],
    ['Nguyá»…n BÃ­nh', 'nguyen-binh', 'vietnam', 'Vietnam', '1918', '1966', 'ThÆ¡ má»›i',
        'Thi sÄ© ná»•i tiáº¿ng vá»›i thÆ¡ tÃ¬nh vÃ  ná»—i nhá»› quÃª hÆ°Æ¡ng. ChÃ¢n quÃª, Lá»¡ bÆ°á»›c sang ngang lÃ  nhá»¯ng bÃ i thÆ¡ Ä‘Æ°á»£c yÃªu thÃ­ch trong chÆ°Æ¡ng trÃ¬nh phá»• thÃ´ng.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0c/Nguyen_Binh.jpg/440px-Nguyen_Binh.jpg',
        'https://vi.wikipedia.org/wiki/Nguyá»…n_BÃ­nh'],
    ['Pháº¡m Tiáº¿n Duáº­t', 'pham-tien-duat', 'vietnam', 'Vietnam', '1941', '2007', 'ThÆ¡ khÃ¡ng chiáº¿n',
        'NhÃ  thÆ¡ khÃ¡ng chiáº¿n, tÃ¡c giáº£ BÃ i thÆ¡ vá» tiá»ƒu Ä‘á»™i xe khÃ´ng kÃ­nh â€” biá»ƒu tÆ°á»£ng tinh tháº§n láº¡c quan, kiÃªn cÆ°á»ng cá»§a ngÆ°á»i lÃ­nh trÃªn Ä‘Æ°á»ng TrÆ°á»ng SÆ¡n.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Pham_Tien_Duat.jpg/440px-Pham_Tien_Duat.jpg',
        'https://vi.wikipedia.org/wiki/Pháº¡m_Tiáº¿n_Duáº­t'],
    ['HÃ n Máº·c Tá»­', 'han-mac-tu', 'vietnam', 'Vietnam', '1912', '1940', 'ThÆ¡ ÄÆ°á»ng',
        'Thi sÄ© trÆ°á»ng phÃ¡i Má»›i, ná»•i tiáº¿ng vá»›i thÆ¡ siÃªu thá»±c, Ä‘au khá»• vÃ  tÃ¬m kiáº¿m cÃ¡i Ä‘áº¹p. Äau thÆ°Æ¡ng, ÄÃ¢y thÃ´n VÄ© Dáº¡nh lÃ  nhá»¯ng bÃ i thÆ¡ tiÃªu biá»ƒu.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/Han_Mac_Tu.jpg/440px-Han_Mac_Tu.jpg',
        'https://vi.wikipedia.org/wiki/HÃ n_Máº·c_Tá»­'],
    ['Quang DÅ©ng', 'quang-dung', 'vietnam', 'Vietnam', '1921', '1988', 'ThÆ¡ khÃ¡ng chiáº¿n',
        'NhÃ  thÆ¡, tÆ°á»›ng quÃ¢n Ä‘á»™i. TÃ¢y Tiáº¿n lÃ  bÃ i thÆ¡ ná»•i tiáº¿ng vá» chiáº¿n tranh vÃ  ná»—i nhá»›, vá»›i ngÃ´n ngá»¯ giÃ u hÃ¬nh áº£nh Ä‘á»™c Ä‘Ã¡o.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Quang_Dung.jpg/440px-Quang_Dung.jpg',
        'https://vi.wikipedia.org/wiki/Quang_DÅ©ng_(nhÃ _thÆ¡)'],
    ['ChÃ­nh Há»¯u', 'chinh-huu', 'vietnam', 'Vietnam', '1926', '2007', 'ThÆ¡ khÃ¡ng chiáº¿n',
        'NhÃ  thÆ¡ khÃ¡ng chiáº¿n, tÃ¡c giáº£ Äá»“ng chÃ­ â€” biá»ƒu tÆ°á»£ng tÃ¬nh Ä‘á»“ng Ä‘á»™i, tÃ¬nh yÃªu thÆ°Æ¡ng giá»¯a nhá»¯ng ngÆ°á»i cÃ¹ng chung má»™t chÃ­ hÆ°á»›ng.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4a/Chinh_Huu.jpg/440px-Chinh_Huu.jpg',
        'https://vi.wikipedia.org/wiki/ChÃ­nh_Há»¯u'],
    ['LÃª Minh KhuÃª', 'le-minh-khue', 'vietnam', 'Vietnam', '1949', NULL, 'VÄƒn há»c chiáº¿n tranh',
        'NhÃ  vÄƒn ná»¯ ná»•i tiáº¿ng vá»›i cÃ¡c tÃ¡c pháº©m vá» chiáº¿n tranh vÃ  phá»¥ ná»¯. Nhá»¯ng ngÃ´i sao xa xÃ´i lÃ  truyá»‡n ngáº¯n Ä‘iá»ƒn hÃ¬nh vÄƒn há»c Ä‘á»•i má»›i.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9e/Vietnam_War_protestors.jpg/440px-Vietnam_War_protestors.jpg',
        'https://vi.wikipedia.org/wiki/LÃª_Minh_KhuÃª'],
    ['Nguyá»…n Minh ChÃ¢u', 'nguyen-minh-chau', 'vietnam', 'Vietnam', '1931', '1989', 'VÄƒn há»c Ä‘á»•i má»›i',
        'NhÃ  vÄƒn vÄƒn há»c Ä‘á»•i má»›i, tÃ¡c giáº£ Chiáº¿c thuyá»n ngoÃ i xa â€” má»Ÿ ra khÃ´ng gian tÃ¢m lÃ½ vÃ  xung Ä‘á»™t Ä‘áº¡o Ä‘á»©c má»›i trong vÄƒn há»c Viá»‡t.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/Fishing_boats_Vietnam.jpg/440px-Fishing_boats_Vietnam.jpg',
        'https://vi.wikipedia.org/wiki/Nguyá»…n_Minh_ChÃ¢u'],
    ['NgÃ´ Táº¥t Tá»‘', 'ngo-tat-to', 'vietnam', 'Vietnam', '1894', '1954', 'Hiá»‡n thá»±c phÃª phÃ¡n',
        'NhÃ  vÄƒn hiá»‡n thá»±c phÃª phÃ¡n, tÃ¡c giáº£ Táº¯t Ä‘Ã¨n â€” tiá»ƒu thuyáº¿t tiÃªu biá»ƒu vá» ngÆ°á»i phá»¥ ná»¯ nÃ´ng dÃ¢n dÆ°á»›i Ã¡ch Ä‘Ã´ há»™ phong kiáº¿n.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Ngo_Tat_To.jpg/440px-Ngo_Tat_To.jpg',
        'https://vi.wikipedia.org/wiki/NgÃ´_Táº¥t_Tá»‘'],
    ['Nguyá»…n Huy Thiá»‡p', 'nguyen-huy-thiep', 'vietnam', 'Vietnam', '1950', NULL, 'VÄƒn há»c Ä‘á»•i má»›i',
        'NhÃ  vÄƒn gÃ¢y tranh luáº­n vá»›i cÃ¡c tÃ¡c pháº©m vá» nÃ´ng thÃ´n vÃ  Ä‘á»•i má»›i. Báº¿n quÃª lÃ  truyá»‡n ngáº¯n ná»•i tiáº¿ng má»Ÿ ra cÃ¡ch nhÃ¬n má»›i vá» lÃ ng quÃª Viá»‡t.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Vietnamese_village.jpg/440px-Vietnamese_village.jpg',
        'https://vi.wikipedia.org/wiki/Nguyá»…n_Huy_Thiá»‡p'],
    ['Huy Cáº­n', 'huy-can', 'vietnam', 'Vietnam', '1919', '2002', 'ThÆ¡ má»›i',
        'NhÃ  thÆ¡ thuá»™c tháº¿ há»‡ ThÆ¡ má»›i, ná»•i tiáº¿ng vá»›i Thu báº¥t chá»£t, Nhá»› rá»«ng â€” thÆ¡ buá»“n man mÃ¡c, gáº¯n vá»›i thiÃªn nhiÃªn vÃ  kÃ½ á»©c.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a7/Autumn_leaves.jpg/440px-Autumn_leaves.jpg',
        'https://vi.wikipedia.org/wiki/Huy_Cáº­n'],
    ['William Shakespeare', 'william-shakespeare', 'international', 'England', '1564', '1616', 'Renaissance',
        'Playwright and poet, greatest writer in the English language. His tragedies (Hamlet, Romeo and Juliet) and comedies shaped world drama and explored human nature with unmatched depth.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Romeo_and_juliet_brown.jpg/440px-Romeo_and_juliet_brown.jpg',
        'https://en.wikipedia.org/wiki/William_Shakespeare'],
    ['Jane Austen', 'jane-austen', 'international', 'England', '1775', '1817', 'Regency',
        'Novelist known for wit, social observation, and romance. Pride and Prejudice remains one of the most beloved novels in English literature.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/PrideAndPrejudiceTitlePage.jpg/440px-PrideAndPrejudiceTitlePage.jpg',
        'https://en.wikipedia.org/wiki/Jane_Austen'],
    ['George Orwell', 'george-orwell', 'international', 'England', '1903', '1950', '20th century',
        'Writer and essayist, author of 1984 and Animal Farm â€” powerful critiques of totalitarianism, propaganda, and the abuse of political power.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c3/1984_first_edition_cover.jpg/440px-1984_first_edition_cover.jpg',
        'https://en.wikipedia.org/wiki/George_Orwell'],
    ['F. Scott Fitzgerald', 'f-scott-fitzgerald', 'international', 'USA', '1896', '1940', 'Jazz Age',
        'American novelist of the Jazz Age. The Great Gatsby captures ambition, wealth, and disillusionment in 1920s America.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/The_Great_Gatsby_Cover_1925_Retouched.jpg/440px-The_Great_Gatsby_Cover_1925_Retouched.jpg',
        'https://en.wikipedia.org/wiki/F._Scott_Fitzgerald'],
    ['Gabriel GarcÃ­a MÃ¡rquez', 'gabriel-garcia-marquez', 'international', 'Colombia', '1927', '2014', 'Magical realism',
        'Nobel laureate, master of magical realism. One Hundred Years of Solitude chronicles the BuendÃ­a family in the fictional town of Macondo.',
        'https://upload.wikimedia.org/wikipedia/en/thumb/b/b5/One_Hundred_Years_of_Solitude_%28first_edition_in_Spanish%29.jpg/440px-One_Hundred_Years_of_Solitude_%28first_edition_in_Spanish%29.jpg',
        'https://en.wikipedia.org/wiki/Gabriel_Garc%C3%ADa_M%C3%A1rquez'],
    ['Homer', 'homer', 'international', 'Greece', '8th c. BCE', NULL, 'Ancient',
        'Legendary ancient Greek poet, traditionally credited with the Iliad and the Odyssey â€” foundational epics of Western literature.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/5/59/Odysseus_Sculpture.jpg/440px-Odysseus_Sculpture.jpg',
        'https://en.wikipedia.org/wiki/Homer'],
    ['Dante Alighieri', 'dante-alighieri', 'international', 'Italy', '1265', '1321', 'Medieval',
        'Italian poet, author of The Divine Comedy â€” an allegorical journey through Hell, Purgatory, and Paradise that defined Italian literature.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Dante_Alighieri.jpg/440px-Dante_Alighieri.jpg',
        'https://en.wikipedia.org/wiki/Dante_Alighieri'],
    ['Victor Hugo', 'victor-hugo', 'international', 'France', '1802', '1885', 'Romanticism',
        'French Romantic writer and humanist. Les MisÃ©rables is an epic of justice, redemption, and revolution in 19th-century France.',
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
        'Russian writer, author of War and Peace and Anna Karenina â€” panoramic novels of society, history, and the human soul.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c6/L.N.Tolstoy_Prokudin-Gorsky.jpg/440px-L.N.Tolstoy_Prokudin-Gorsky.jpg',
        'https://en.wikipedia.org/wiki/Leo_Tolstoy'],
    ['Franz Kafka', 'franz-kafka', 'international', 'Austria', '1883', '1924', 'Modernism',
        'German-language writer of alienation and absurdity. The Metamorphosis opens with Gregor Samsa\'s transformation into an insect.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4c/Kafka1906.jpg/440px-Kafka1906.jpg',
        'https://en.wikipedia.org/wiki/Franz_Kafka'],
    ['Miguel de Cervantes', 'miguel-de-cervantes', 'international', 'Spain', '1547', '1616', 'Golden Age',
        'Spanish writer, author of Don Quixote â€” often called the first modern novel, blending chivalry, illusion, and reality.',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/5/57/Don_Quijote_y_Sancho_Panza.jpg/440px-Don_Quijote_y_Sancho_Panza.jpg',
        'https://en.wikipedia.org/wiki/Miguel_de_Cervantes'],
    ['Antoine de Saint-ExupÃ©ry', 'saint-exupery', 'international', 'France', '1900', '1944', '20th century',
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
        'American novelist and Nobel laureate. The Old Man and the Sea tells of Santiago\'s struggle with a giant marlin â€” dignity against nature.',
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
$contentFile = __DIR__ . '/../../database/literary_content_data.php';
if (!is_readable($contentFile)) {
    echo "Warning: literary_content_data.php not found â€” skipping content.\n";
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
    $html = '<h2>Giá»›i thiá»‡u</h2><p>' . htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
    if (!empty($row['excerpt'])) {
        $html .= '<h2>TrÃ­ch Ä‘oáº¡n</h2><p class="canon-verse"><em>' . htmlspecialchars($row['excerpt'], ENT_QUOTES, 'UTF-8') . '</em></p>';
    }
    $html .= '<p><em>â€” ' . htmlspecialchars($row['author'] ?? '', ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars($row['genre_label'] ?? '', ENT_QUOTES, 'UTF-8') . '</em></p>';
    $fallbackUpd->execute([':content' => $html, ':title' => $row['title']]);
    $updated++;
}

echo "{$updated} works received full content.\n";
echo "Done.\n";

