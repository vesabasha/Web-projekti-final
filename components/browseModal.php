<?php
require_once __DIR__ . '/../config.php'; 

$stmt = $pdo->query("
    SELECT g.title, g.description, g.main_image_url, GROUP_CONCAT(ge.name SEPARATOR ',') AS genres
    FROM games g
    LEFT JOIN game_genres gg ON g.id = gg.game_id
    LEFT JOIN genres ge ON gg.genre_id = ge.id
    GROUP BY g.id
    ORDER BY RAND()
    LIMIT 4
");
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section id="browse-games">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h2 style="color:#A24465; font-size:32px;">Start Browsing</h2>
        <a href="games" class="secondary-a">See More</a>
    </div>

    <div id="browse-grid" style="display:grid; grid-template-columns:repeat(2,1fr); gap:25px; padding:0 20px;">
        <?php foreach ($games as $g): ?>
            <?php $genres = $g['genres'] ? explode(',', $g['genres']) : []; ?>
            <div class="game-card" data-name="<?= htmlspecialchars($g['title']) ?>">
                <img src="<?= htmlspecialchars($g['main_image_url'] ?: 'images/games/placeholder.png') ?>" alt="" 
                     onerror="this.src='images/games/placeholder.png';">
                <div class="game-card-info">
                    <h3><?= htmlspecialchars($g['title']) ?></h3>
                    <p class="secondary-text" style="
                        display: -webkit-box;
                        -webkit-line-clamp: 3;
                        -webkit-box-orient: vertical;
                        overflow: hidden;
                    "><?= htmlspecialchars($g['description']) ?></p>
                    <div class="genre-container">
                        <?php foreach ($genres as $x): ?>
                            <p class="genre-badge"><?= htmlspecialchars($x) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
document.querySelectorAll("#browse-grid .game-card").forEach(card => {
    const name = card.getAttribute("data-name");
    card.addEventListener("click", () => {
        window.location.href = `/details?game=${encodeURIComponent(title)}`;
    });
});
</script>
