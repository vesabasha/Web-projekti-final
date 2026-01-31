<?php
session_start();

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';
$profilePic = $_SESSION['pfp_url'] ?? 'images/placeholder.jpg';
$is_admin = $_SESSION['is_admin'] ?? false;
?>
<nav>
    <ul>
        <li><img style="width:50px;height:50px;cursor:pointer;" src="images/logo.jpg" alt="Logo"></li>
        <li><a style="font-size:30px" id="title" href="landing">QUEST</a></li>

        
        
        <li style="margin-left:auto;"><a class="navitem" href="games">Browse Games</a></li>
        <li><a class="navitem" href="privacy">Our Policy</a></li>

        <?php if ($is_admin):?> 
        <li><a class="navitem" href="dashboard">Dashboard</a></li>
        <?php endif;?>


        <li><input type="search" placeholder=" Search..."></li>

        <?php if ($userId): ?>
            <li style="display:flex;align-items:center;gap:8px;">
            <span style="color:white; font-weight:bold; cursor:pointer;" onclick="window.location.href='profile'"><?= htmlspecialchars($username) ?></span>

                <img src="<?= $profilePic ?>" alt="Profile" class="profile-avatar" onclick="window.location.href='profile'" style="cursor:pointer; width:40px;height:40px;border-radius:50%;">
            </li>
        <?php else: ?>
            <li><button id="loginBtn">Log in</button></li>
            <li><button id="signupBtn" class="button-2">Sign Up</button></li>
        <?php endif; ?>
    </ul>
</nav>
