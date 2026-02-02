<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

        <?php if ($is_admin): ?>
            <li><a class="navitem" href="dashboard">Dashboard</a></li>
        <?php endif; ?>

        <li style="position:relative;">
            <form action="games" method="GET" class="global-search-form" style="display:inline-block;">
                <input autocomplete="off" type="search" id="globalSearchInput" name="search" placeholder=" Search..." style="height:34px; padding:6px 10px; border-radius:4px; border:1px solid #333;">
            </form>
            <div id="globalSearchDropdown" style="position:absolute; right:0; top:42px; background:#121212; border:1px solid #222; width:320px; max-height:320px; overflow:auto; display:none; border-radius:6px; box-shadow:0 6px 18px rgba(0,0,0,0.6); z-index:9999;"></div>
        </li>

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

<script>
(function(){
    try {
        const input = document.getElementById('globalSearchInput');
        const dropdown = document.getElementById('globalSearchDropdown');
        if (!input || !dropdown) return;

        const params = new URLSearchParams(window.location.search);
        const q = params.get('search') || '';
        input.value = q;

        input.addEventListener('keydown', function(e){
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
            if (e.key === 'ArrowDown') {
                const first = dropdown.querySelector('.search-item');
                if (first) first.focus();
            }
        });

        let timer = null;
        input.addEventListener('input', function(){
            clearTimeout(timer);
            const val = this.value.trim();
            if (!val) { dropdown.style.display = 'none'; dropdown.innerHTML = ''; return; }
            timer = setTimeout(async () => {
                try {
                    const url = `${window.location.origin}/pages/search.php?q=` + encodeURIComponent(val);
                    const res = await fetch(url);
                    const items = await res.json();
                    if (!Array.isArray(items) || items.length === 0) {
                        dropdown.innerHTML = '<div style="padding:10px;color:#aaa;">No results</div>';
                        dropdown.style.display = 'block';
                        return;
                    }
                    dropdown.innerHTML = items.map(it => `
                        <div class="search-item" tabindex="0" style="display:flex;gap:8px;align-items:center;padding:8px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.03);">
                            <img src="../${it.main_image2_url || 'images/games/placeholder.png'}" style="width:48px;height:64px;object-fit:cover;border-radius:4px;" onerror="this.src='../images/games/placeholder.png'" />
                                    <div style="flex:1;color:#FF669C;font-weight:600;font-family:'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, Arial;">${(it.title||'').replace(/</g,'&lt;')}</div>
                        </div>
                    `).join('');
                    dropdown.querySelectorAll('.search-item').forEach(el => {
                        el.addEventListener('click', () => {
                            const title = el.textContent.trim();
                            window.location.href = `/details?game=` + encodeURIComponent(title);
                        });
                        el.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                el.click();
                            }
                        });
                    });
                    dropdown.style.display = 'block';
                } catch (e) {
                    console.error('Search dropdown error', e);
                }
            }, 250);
        });

        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && e.target !== input) {
                dropdown.style.display = 'none';
            }
        });

    } catch (e) { console.error(e); }
})();
</script>
