<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quest Landing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/../components/nav.php'; ?>


<section class="hero">
    <div class="hero-content">
        <h1>Find the Next World<br>Worth Entering.</h1>
        <p>Log your gaming journey and find new games perfect for you.</p>

        <div class="hero-buttons">
            <a href="games"><button>Start Browsing</button></a>
            <button id="heroSignupBtn" class="button-2">Create an account</button>
        </div>
    </div>
</section>

<!-- info-->
<section class="info-section">

    <!-- 1 -->
    <div class="info-row">
        <div class="info-text">
            <h3>What is quest?</h3>
            <p class="secondary-text">
                Quest is a website developed by gamers, for gamers. With an overwhelming amount
                of great games to play, Quest offers a place to keep track of your gaming
                history as well as find recommendations catered to you.
            </p>
        </div>
        <div class="info-image-wrapper">
            <img src="images/img1.png" class="info-image" alt="">
        </div>
    </div>

    <!-- 2 -->
    <div class="info-row info-row-reverse">
        <div class="info-image-wrapper">
            <img src="images/img2.png" class="info-image" alt="">
        </div>
        <div class="info-text">
            <h3>Track your games</h3>
            <p class="secondary-text">
                Quest offers a way for you to track your gaming journey. Create lists of games
                in your radar or maybe your favorites and share them with your friends.
            </p>
        </div>
    </div>

    <!-- 3 -->
    <div class="info-row">
        <div class="info-text">
            <h3>Find games for you</h3>
            <p class="secondary-text">
                Browse games based on genre, rating, year of release or use our specialized
                tools to help you find your next perfect game.
            </p>
        </div>
        <div class="info-image-wrapper">
            <img src="images/img3.png" class="info-image" alt="">
        </div>
    </div>

</section>
<section id="browse-games">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
    <h2 style="color:#A24465; font-size:32px;">Start Browsing</h2>
    <a href="games" class="secondary-a">See More</a>
  </div>

  <div id="browse-grid" style="display:grid; grid-template-columns:repeat(2,1fr); gap:25px; padding:0 20px;"></div>
</section>

<?php include __DIR__ . '/../components/authModal.php'; ?>


<?php include __DIR__ . '/../components/footer.php'; ?>

    <script src="script.js"></script>
    <script src="browsegames.js"></script>

</body>
</html>