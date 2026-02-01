<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';
$profilePic = $_SESSION['pfp_url'] ?? 'images/placeholder.jpg';
?>

    <div class="mobile-header">
    <img src="images/logo.jpg" alt="Logo" class="mobile-logo">
    <span id="mobile-title">QUEST</span>
    
</div>

<div class="mobile-nav-wrapper">

    <nav class="mobile-nav">
    <button type="button" class="burger" onclick="document.body.classList.toggle('mobile-nav-open')">☰</button>


        <div class="brand">
            <img src="images/logo.jpg" alt="Logo">
            <span id="mobile-title">QUEST</span>
        </div>

        <hr class="divider">

        <div class="search">
            <input type="search" placeholder="Search...">
        </div>

        <ul>
            <?php if (!$userId): ?>
                <li><button class="nav-btn" id="loginBtn">Log in</button></li>
                <li><button class="nav-btn button-2" id="signupBtn">Sign Up</button></li>
            <?php endif; ?>

            <li><a class="navitem" href="games">Browse Games</a></li>
            <li><a class="navitem" href="privacy">Our Policy</a></li>
        </ul>
    </nav>
</div>
