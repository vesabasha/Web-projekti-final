<?php
session_start();
require_once __DIR__ . '/../config.php'; // this should define $pdo as your PDO connection

// Fetch games with genres
$stmt = $pdo->query("
    SELECT g.id, g.title AS name, g.description, g.main_image_url,
           GROUP_CONCAT(ge.name) AS genres
    FROM games g
    LEFT JOIN game_genres gg ON g.id = gg.game_id
    LEFT JOIN genres ge ON gg.genre_id = ge.id
    GROUP BY g.id
");
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert genres from a comma-separated string into an array
foreach ($games as &$game) {
    if (isset($game['genres']) && $game['genres'] !== null && $game['genres'] !== '') {
        $game['genres'] = explode(',', $game['genres']);
    } else {
        $game['genres'] = [];
    }
}
unset($game);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$username = $is_logged_in ? htmlspecialchars($_SESSION['username'] ?? 'User') : '';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Games - Quest</title>
    <link rel="stylesheet" href="../style.css">
    


</head>
<body>

<!--testing per commit, erind balaj-->
    <!--navigation bar, e boni copy paste qeta ncdo faqe ever -->
    <?php include __DIR__ . '/../components/nav.php'; ?>

    <?php include __DIR__ . '/../components/authModal.php'; ?>

<!--header -->

<div style="margin: 40px auto; width: 75%; display: flex; flex-direction: row; margin-bottom: 50px; align-items: center;">
    <h1 class="h1-title" style="flex-grow: 0.5; margin: 0;">Find the perfect game for you</h1>
    <input style="height:45px; width:42%; margin-left: 5%;" type="search" placeholder=" Search...">
</div>

<!-- qetu i qesim filters advanced search -->
<div style="width: 75%; margin: 0 auto;">
    <button id="advancedBtn" style="height: auto; width: 100%; cursor: pointer; border: none; border-radius: 8px 8px 0 0; background-color: #010B14; color: white; text-align: left;">
        <p style="padding-left: 2%; font-size: 20px;" class="secondary-text">Advanced Search</p>
    </button>

    <div id="advancedBox" style="display: none; width: 100%; background-color: #010B14; color: white; border-top: 1px solid #000; border-radius: 0 0 8px 8px;">
        <p class="secondary-text" style="padding-left: 2%;">Seems like you're early...</p>
        <p class="secondary-text" style="padding-left:  2%; padding-bottom:2%;">Come back in phase two of the project to be able to filter by genre, date etc. from more games, as well as use our AI feature.</p>
    </div>
</div>

<!-- games  -->
<div style="margin-top: 2%;" class="game-container" id="game-container">
    <?php foreach ($games as $game): ?>
        <div class="game-card" onclick="window.location.href='/details?game=<?php echo urlencode($game['name']); ?>';" style="cursor: pointer;">
            <?php
            // Try to use main_image_url from database, otherwise construct from game name
            $imagePath = $game['main_image_url'];
            if (empty($imagePath) || $imagePath === null) {
                // Construct path from game name
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


  <?php include __DIR__ . '/../components/footer.php'; ?>   
    <script src="../script.js"></script>
</body>

    <script>
    const btn = document.getElementById('advancedBtn');
    const box = document.getElementById('advancedBox');

    btn.addEventListener('click', () => {
        // toggle display
        if (box.style.display === 'none') {
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    });
    </script>
    
</html>