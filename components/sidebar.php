<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';
$profilePic = $_SESSION['pfp_url'] ?? 'images/placeholder.jpg';

if ($userId) {
    $stmt = $pdo->prepare("SELECT pfp_url FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $dbPfp = $stmt->fetchColumn();
    if (!empty($dbPfp)) {
        $profilePic = $dbPfp;
    }
}
?>

<div class="mobile-header">
    <button type="button" class="mobile-burger" id="mobileBurgerBtn">☰</button>
    <img src="images/logo.jpg" alt="Logo" class="mobile-logo" onclick="window.location.href='landing'">
    <span id="mobile-title" onclick="window.location.href='landing'">QUEST</span>

    <div class="mobile-user" style="position:absolute; top:10px; right:10px;">
        <?php if ($userId): ?>
            <img src="<?= $profilePic ?>" alt="Profile" class="profile-avatar" style="width:35px;height:35px;border-radius:50%;cursor:pointer;" onclick="window.location.href='profile'">

        <?php endif; ?>
    </div>
</div>


<div class="mobile-nav-wrapper">
    <nav class="mobile-nav">
        <button type="button" class="burger" id="sidebarCloseBtn">✕</button>

        <div class="brand">
            <img onclick="window.location.href='landing'" src="images/logo.jpg" alt="Logo">
            <span id="mobile-title" onclick="window.location.href='landing'">QUEST</span>
        </div>

        <hr class="divider">

        <ul>
            <?php if (!$userId): ?>
                <li><button class="nav-btn mobile-login-btn" id="mobileLoginBtn">Log in</button></li>
                <li><button class="nav-btn button-2 mobile-signup-btn" id="mobileSignupBtn">Sign Up</button></li>
            <?php endif; ?>

            <li><a class="navitem" href="games">Browse Games</a></li>
            <li><a class="navitem" href="privacy">Our Policy</a></li>
        </ul>
    </nav>
    
    <div class="mobile-overlay" id="mobileOverlay"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const mobileBurger = document.getElementById('mobileBurgerBtn');
    const sidebarClose = document.getElementById('sidebarCloseBtn');
    const sidebar = document.querySelector('.mobile-nav');
    const overlay = document.getElementById('mobileOverlay');
    const navWrapper = document.querySelector('.mobile-nav-wrapper');

    function openSidebar() {
        body.classList.add('mobile-nav-open');
    }

    function closeSidebar() {
        body.classList.remove('mobile-nav-open');
    }

    if (mobileBurger) {
        mobileBurger.addEventListener('click', (e) => {
            e.stopPropagation();
            openSidebar();
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', (e) => {
            e.stopPropagation();
            closeSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeSidebar);
        });

        const mobileLoginBtn = document.getElementById('mobileLoginBtn');
        const mobileSignupBtn = document.getElementById('mobileSignupBtn');
        
        if (mobileLoginBtn) {
            mobileLoginBtn.addEventListener('click', () => {
                closeSidebar();
                setTimeout(() => {
                    const mainLoginBtn = document.getElementById('loginBtn');
                    if (mainLoginBtn) mainLoginBtn.click();
                }, 300);
            });
        }

        if (mobileSignupBtn) {
            mobileSignupBtn.addEventListener('click', () => {
                closeSidebar();
                setTimeout(() => {
                    const mainSignupBtn = document.getElementById('signupBtn');
                    if (mainSignupBtn) mainSignupBtn.click();
                }, 300);
            });
        }
    }
});
</script>
