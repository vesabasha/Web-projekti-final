<?php
require_once __DIR__ . '/../config.php';

$gameName = $_GET['game'] ?? '';
$gameName = urldecode($gameName);

$stmt = $pdo->prepare("
    SELECT g.*, GROUP_CONCAT(ge.name) AS genres
    FROM games g
    LEFT JOIN game_genres gg ON g.id = gg.game_id
    LEFT JOIN genres ge ON gg.genre_id = ge.id
    WHERE g.title = ?
    GROUP BY g.id
");
$stmt->execute([$gameName]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    http_response_code(404);
    echo 'Game not found';
    exit;
}

$images = array_filter([
    $game['image1'] ?? null,
    $game['image2'] ?? null,
    $game['image3'] ?? null,
    $game['image4'] ?? null
]);

$genres = $game['genres'] ? explode(',', $game['genres']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($game['title']) ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php include __DIR__ . '/../components/nav.php'; ?>
<?php include __DIR__ . '/../components/authModal.php'; ?>

<button onclick="history.back()" style="margin-left:9%;padding:0 20px;font-size:16px;cursor:pointer;">
    &lt; Back to Games
</button>

<h1 style="margin-left:10%;font-size:42px;">
    <?= htmlspecialchars($game['title']) ?>
</h1>

<div style="width:75%;margin:auto;display:flex;gap:20px;">
    <div style="flex:3;">
        <img id="big-image" src="<?= $images[0] ?>" style="width:94%;height:83%;object-fit:cover;margin-bottom:10px;">
        <div id="thumbnails" style="margin-left:3%;display:flex;gap:10px;height:80px;">
            <?php foreach ($images as $i => $img): ?>
                <img
                    src="<?= $img ?>"
                    class="game-detail-image"
                    style="opacity:<?= $i === 0 ? '1' : '0.5' ?>"
                >
            <?php endforeach; ?>
        </div>
    </div>

    <div style="flex:2;">
        <img src="<?= $game['main_image2_url'] ?>" style="width:100%;margin-bottom:5%;">
        <p class="secondary-text"><?= htmlspecialchars($game['description']) ?></p>
        <div class="genre-container" style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:3%;">
            <?php foreach ($genres as $g): ?>
                <p class="genre-badge"><?= htmlspecialchars($g) ?></p>
            <?php endforeach; ?>
        </div>
        <button style="width:100%;" class="button-2">Add to List</button>
    </div>
</div>

<?php include __DIR__ . '/../components/browseModal.php'; ?>

<?php include __DIR__ . '/../components/footer.php'; ?>

<script>
const thumbs = document.querySelectorAll('.game-detail-image');
const big = document.getElementById('big-image');

thumbs.forEach(img => {
    img.onclick = () => {
        big.src = img.src;
        thumbs.forEach(i => i.style.opacity = '0.5');
        img.style.opacity = '1';
    };
});
</script>

</body>
</html>
