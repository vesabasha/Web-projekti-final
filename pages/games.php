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
    <nav>
        <ul>
            <li><img href="#" style="width: 50px; height: 50px; cursor: pointer;" src="../images/logo.jpg" alt="Logo"></li>
            <li><a style="font-size:30px" id="title" href="/">QUEST</a></li>

            <li style="margin-left: auto;"><a  class="navitem" href="/games">Browse Games</a></li>
            <li><a class="navitem" href="/privacy">Our Policy</a></li>

            <li><input type="search" placeholder=" Search..."></li>

            <?php if (!$is_logged_in): ?>
                <li><button id="loginBtn">Log in</button></li>
                <li><button id="signupBtn" class="button-2">Sign Up</button></li>
            <?php else: ?>
                <li><a href="/profile"><?php echo htmlspecialchars($username); ?></a></li>
                <li><a href="../logout.php" style="color: #ff6b6b;">Log out</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <!-- login and signup -->
     <!-- login -->
    <div id="loginModal" class="modal hidden">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h2>Log in</h2>

            <button class="google-btn">
                <img src="../images/googleicon.png" alt="Google Icon">
                Continue with Google
            </button>

            <p class="divider-text">or</p>

            <form class="modal-form" action="../login.php" method="POST">
                <label>
                    Email
                    <input type="email" name="email" placeholder="Enter your email or username" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" placeholder="Enter your password" required>
                </label>

                <div style="margin-bottom: 12px;"></div>

                <button type="submit">Log in</button>
            </form>

                <p style="color:white; margin-top:15px; text-align:center; font-size:14px;">
                <button id="reset" style="
                    background:none;
                    border:none;
                    color:#44A1A0;
                    cursor:pointer;
                    font-size:14px;
                    text-decoration:underline;
                ">Reset password</button>

                <p style="color:white; margin-top:15px; text-align:center; font-size:14px;">
                No account?
                <button id="swapToSignup" style="
                    background:none;
                    border:none;
                    color:#44A1A0;
                    cursor:pointer;
                    font-size:14px;
                    text-decoration:underline;
                ">Create one</button>
            </p>
        </div>
    </div>

    <!-- signup -->
    <div id="signupModal" class="modal hidden">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h2>Sign Up</h2>

            <button class="google-btn">
                <img src="../images/googleicon.png" alt="Google Icon">
                Continue with Google
            </button>

            <p class="divider-text">or</p>

            <form class="modal-form" action="../signup.php" method="POST">
                <label>
                    Username
                    <input type="text" name="username" placeholder="Choose a username" required>
                </label>

                <label>
                    Email
                    <input type="email" name="email" placeholder="Enter your email" required>
                </label>

                <label>
                    Password
                    <input type="password" name="password" placeholder="Create a password" required>
                </label>

                <label>
                    Confirm Password
                    <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                </label>

                <div style="margin-bottom: 12px;"></div>

                <button type="submit" class="button-2">Create Account</button>
            </form>

            <p style="color:white; margin-top:15px; text-align:center; font-size:14px;">
                Already a member?
                <button id="swapToLogin" style="
                    background:none;
                    border:none;
                    color:#44A1A0;
                    cursor:pointer;
                    font-size:14px;
                    text-decoration:underline;
                ">Log in!</button>
            </p>
        </div>
    </div>

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


   <footer>
        <div class="footer-columns">
            <div class="col">
            <h2>QUEST</h2>
            </div>
            <div class="col">
            <a href="#">About Us</a><br>
            <a href="#">Contact Us</a>
            </div>
            <div class="col">
            <a href="#">Terms of Service</a><br>
            <a href="#">Privacy Policy</a>
            </div>
        </div>
        <br>
        <div class="footer-bottom">
            © 2025 Quest. All rights reserved. Game data and artwork belong to their respective owners.
        </div>
    </footer>
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