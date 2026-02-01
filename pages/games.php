<?php
session_start();
require_once __DIR__ . '/../config.php';

$limit = 10; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$genreStmt = $pdo->query("SELECT name FROM genres ORDER BY name");
$allGenres = $genreStmt->fetchAll(PDO::FETCH_COLUMN);


$genre = $_GET['genre'] ?? null;
$yearFrom = $_GET['year_from'] ?? null;
$yearTo   = $_GET['year_to'] ?? null;
$search = $_GET['search'] ?? null;

$sql = "
SELECT
    g.id,
    g.title AS name,
    g.description,
    g.main_image_url,
    g.release_date,
    GROUP_CONCAT(DISTINCT ge.name) AS genres
FROM games g
LEFT JOIN game_genres gg ON g.id = gg.game_id
LEFT JOIN genres ge ON gg.genre_id = ge.id
WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND g.title LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($yearFrom) && !empty($yearTo)) {
    $sql .= " AND YEAR(g.release_date) BETWEEN :yearFrom AND :yearTo";
    $params[':yearFrom'] = $yearFrom;
    $params[':yearTo'] = $yearTo;

} elseif (!empty($yearFrom)) {
    $sql .= " AND YEAR(g.release_date) >= :yearFrom";
    $params[':yearFrom'] = $yearFrom;

} elseif (!empty($yearTo)) {
    $sql .= " AND YEAR(g.release_date) <= :yearTo";
    $params[':yearTo'] = $yearTo;
}

$selectedGenres = [];
if (!empty($_GET['genres']) && is_array($_GET['genres'])) {
    $selectedGenres = $_GET['genres'];
}

if (!empty($selectedGenres)) {
    $placeholders = [];
    foreach ($selectedGenres as $i => $g) {
        $placeholders[] = ":genre$i";
        $params[":genre$i"] = $g;
    }

    $sql .= "
    AND g.id IN (
        SELECT gg2.game_id
        FROM game_genres gg2
        JOIN genres ge2 ON gg2.genre_id = ge2.id
        WHERE ge2.name IN (" . implode(',', $placeholders) . ")
        GROUP BY gg2.game_id
        HAVING COUNT(DISTINCT ge2.name) = " . count($selectedGenres) . "
    )";
}
$countSql = "SELECT COUNT(*) FROM ( " . $sql . " GROUP BY g.id ) as total_count";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$sql .= " GROUP BY g.id ORDER BY g.release_date DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($games as &$game) {
    if (isset($game['genres']) && $game['genres'] !== null && $game['genres'] !== '') {
        $game['genres'] = explode(',', $game['genres']);
    } else {
        $game['genres'] = [];
    }
}
unset($game);

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$username = $is_logged_in ? htmlspecialchars($_SESSION['username'] ?? 'User') : '';
$currentParams = $_GET;
unset($currentParams['page']);
$queryString = http_build_query($currentParams);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Games - Quest</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../responsive.css">
    <style>
        .game-card-info h3 {
            color: #FF669C;
            font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
            font-weight: 600;
            font-size: 18px;
            margin: 0 0 8px;
        }

        .game-card-info p.secondary-text {
            color: #ccc;
        }
    </style>
</head>
<body>

<!--testing per commit, erind balaj-->
    <!--navigation bar, e boni copy paste qeta ncdo faqe ever -->
    <?php include __DIR__ . '/../components/nav.php'; ?>
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <?php include __DIR__ . '/../components/authModal.php'; ?>

<!--header -->

<div style="margin: 40px auto; width: 75%; display: flex; align-items: center;">
    <h1 class="h1-title" style="flex-grow: 0.5; margin: 0;">
        Find the perfect game for you
    </h1>

    <form method="GET" style="width:42%; margin-left: 5%;">
        <input
            style="height:45px; width:100%;"
            type="search"
            name="search"
            value="<?= htmlspecialchars($search ?? '') ?>"
            placeholder="Search..."
        >
    </form>
</div>

<!-- qetu i qesim filters advanced search -->
<div style="width: 75%; margin: 0 auto;">
    <button id="advancedBtn" style="height: auto; width: 100%; cursor: pointer; border: none; border-radius: 8px 8px 0 0; background-color: #010B14; color: white; text-align: left;">
        <p style="padding-left: 2%; font-size: 20px;" class="secondary-text">Advanced Search</p>
    </button>

    <div id="advancedBox" style="display: none; width: 100%; background-color: #010B14; color: white; border-top: 1px solid #000; border-radius: 0 0 8px 8px;">
    <form method="GET" class="advanced-form">

    <div class="advanced-grid">

    <div class="genre-filter">
        <p class="secondary-text">Genres</p>

        <select id="genreDropdown">
            <option value="">-- Select a genre --</option>
            <?php foreach ($allGenres as $g): ?>
                <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
            <?php endforeach; ?>
        </select>

        <div id="selectedGenres" class="selected-genres"></div>
    </div>

    <div class="year-filter">
        <label class="secondary-text">Release year</label>

        <div class="year-range">
            <input type="number" name="year_from" placeholder="From" min="1980" max="2030" value="<?= htmlspecialchars($yearFrom ?? '') ?>">
            <span class="year-separator">–</span>
            <input type="number" name="year_to" placeholder="To" min="1980" max="2030" value="<?= htmlspecialchars($yearTo ?? '') ?>">
        </div>
    </div>

    <div class="apply-filter">
        <button type="submit" style="margin-top:33px;">Apply filters</button>
    </div>

</div>
</form>
</div>
</div>

<!-- games  -->
<div style="margin-top: 2%;" class="game-container" id="game-container">
    <?php foreach ($games as $game): ?>
        <div class="game-card" onclick="window.location.href='/details?game=<?php echo urlencode($game['name']); ?>';" style="cursor: pointer;">
            <?php
            $imagePath = $game['main_image_url'];
            if (empty($imagePath) || $imagePath === null) {
                $imagePath = '../images/games/' . urlencode($game['name']) . '.png';
            }
            ?>
            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" onerror="this.src='../images/games/placeholder.png';">
            <div class="game-card-info">
                <h3><?php echo htmlspecialchars($game['name']); ?></h3>
                <p style="display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;" class="secondary-text"><?php echo htmlspecialchars($game['description']); ?></p>
                <div class="genre-container">
                    <?php foreach ($game['genres'] as $genre): ?>
                        <p class="genre-badge"><?php echo htmlspecialchars($genre); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="pagination" style="display: flex; justify-content: center; gap: 15px; margin: 40px 0;">
    <?php if ($page > 1): ?>
        <a href="?<?= $queryString ?>&page=<?= $page - 1 ?>" style="color: #fff; text-decoration: none;">« Prev</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?= $queryString ?>&page=<?= $i ?>" 
           style="color: <?= ($i == $page) ? '#00fbff' : '#fff' ?>; font-weight: <?= ($i == $page) ? 'bold' : 'normal' ?>; text-decoration: none;">
           <?= $i ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?<?= $queryString ?>&page=<?= $page + 1 ?>" style="color: #fff; text-decoration: none;">Next »</a>
    <?php endif; ?>
</div>


  <?php include __DIR__ . '/../components/footer.php'; ?>   
    <script src="../script.js"></script>
</body>

    <script>
const btn = document.getElementById('advancedBtn');
const box = document.getElementById('advancedBox');

const savedState = localStorage.getItem('advancedBoxOpen');
if (savedState) {
    box.style.display = savedState;
} else {
    box.style.display = 'none';
}

btn.addEventListener('click', () => {
    const isOpen = box.style.display === 'block';
    box.style.display = isOpen ? 'none' : 'block';
    localStorage.setItem('advancedBoxOpen', box.style.display);
});

const dropdown = document.getElementById('genreDropdown');
const selectedContainer = document.getElementById('selectedGenres');

let selectedGenres = <?= json_encode($selectedGenres ?? []) ?>;


dropdown.addEventListener('change', () => {
    const value = dropdown.value;

    if (value && !selectedGenres.includes(value)) {
        selectedGenres.push(value);
        updateBadges();
    }

    dropdown.value = '';
});

function updateBadges() {
    selectedContainer.innerHTML = '';

    selectedGenres.forEach((genre, index) => {
        const badge = document.createElement('span');
        badge.className = 'genre-badge';

        const text = document.createElement('span');
        text.textContent = genre;

        const remove = document.createElement('span');
        remove.className = 'remove';
        remove.textContent = '✕';

        remove.addEventListener('click', (e) => {
            e.stopPropagation();
            selectedGenres.splice(index, 1);
            updateBadges();
        });

        badge.appendChild(text);
        badge.appendChild(remove);

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'genres[]';
        input.value = genre;

        selectedContainer.appendChild(badge);
        selectedContainer.appendChild(input);
    });
}
updateBadges();

</script>
</html>