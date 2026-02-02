<?php
require_once __DIR__ . '/../config.php';

session_start();

$loggedInId = $_SESSION['user_id'] ?? null;
$viewingId = isset($_GET['id']) ? intval($_GET['id']) : $loggedInId;

if (!$viewingId) {
    header("Location: landing");
    exit();
}

$isOwnProfile = ($loggedInId && intval($loggedInId) === intval($viewingId));

$stmt = $pdo->prepare("SELECT username, pfp_url FROM users WHERE id = ?");
$stmt->execute([$viewingId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: landing"); 
    exit();
}

$viewedUsername = $user['username'];
$viewedProfilePic = $user['pfp_url'] ? '../' . $user['pfp_url'] : '../images/placeholder.jpg';

$listCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM lists WHERE user_id = ?");
$listCountStmt->execute([$viewingId]);
$listCount = $listCountStmt->fetch(PDO::FETCH_ASSOC)['count'];

$gamesSavedStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT lg.game_id) as count 
    FROM list_games lg
    INNER JOIN lists l ON lg.list_id = l.id
    WHERE l.user_id = ?
");
$gamesSavedStmt->execute([$viewingId]);
$gamesSaved = $gamesSavedStmt->fetch(PDO::FETCH_ASSOC)['count'];

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'search_games') {
        $q = trim($_GET['q'] ?? '');
        if (!$q) { echo json_encode([]); exit; }
        $stmt = $pdo->prepare("SELECT id, title, main_image_url, main_image_url FROM games WHERE title LIKE ? LIMIT 10");
        $stmt->execute(["%$q%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($_GET['action'] === 'get_lists') {
        $stmt = $pdo->prepare("SELECT id, name FROM lists WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$viewingId]);
        $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $listsWithGames = [];
        foreach ($lists as $list) {
            $gameStmt = $pdo->prepare("
                SELECT g.id, g.title, g.main_image_url FROM games g
                INNER JOIN list_games lg ON g.id = lg.game_id
                WHERE lg.list_id = ?
            ");
            $gameStmt->execute([$list['id']]);
            $list['games'] = $gameStmt->fetchAll(PDO::FETCH_ASSOC);
            $listsWithGames[] = $list;
        }
        echo json_encode(['lists' => $listsWithGames]);
        exit;
    }

    if (!$isOwnProfile) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    if ($_GET['action'] === 'delete_list') {
        $listId = intval($_POST['list_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM lists WHERE id = ? AND user_id = ?");
        $stmt->execute([$listId, $loggedInId]);
        echo json_encode(['success' => true, 'message' => 'List deleted successfully']);
        exit;
    }

    if ($_GET['action'] === 'create_list') {
        $name = trim($_POST['name'] ?? '');
        $games = isset($_POST['games']) && is_array($_POST['games']) ? $_POST['games'] : [];

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO lists (name, user_id) VALUES (?, ?)");
            $stmt->execute([$name, $loggedInId]);
            $listId = $pdo->lastInsertId();

            if (!empty($games)) {
                $stmt = $pdo->prepare("INSERT INTO list_games (list_id, game_id) VALUES (?, ?)");
                foreach ($games as $gameId) {
                    $stmt->execute([$listId, intval($gameId)]);
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'List created successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action']) && $isOwnProfile) {
    $newUsername = trim($_POST['username'] ?? '');

    if (!empty($_FILES['pfp']['name'])) {
        $ext = pathinfo($_FILES['pfp']['name'], PATHINFO_EXTENSION);
        $newFileName = 'user_' . $loggedInId . '.' . $ext;
        $uploadPath = __DIR__ . '/../images/user_profiles/' . $newFileName;

        if (move_uploaded_file($_FILES['pfp']['tmp_name'], $uploadPath)) {
            $pfpUrl = 'images/user_profiles/' . $newFileName;
            $stmt = $pdo->prepare("UPDATE users SET pfp_url = ? WHERE id = ?");
            $stmt->execute([$pfpUrl, $loggedInId]);
            $_SESSION['pfp_url'] = $pfpUrl;
        }
    }

    if (!empty($newUsername)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->execute([$newUsername, $loggedInId]);
        $_SESSION['username'] = $newUsername;
    }

    header("Location: profile?id=" . $loggedInId);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($viewedUsername) ?>'s Profile</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../list.css">
    <link rel="stylesheet" href="../responsive.css">
</head>
<body>

<?php include __DIR__ . '/../components/nav.php'; ?>
<?php include __DIR__ . '/../components/sidebar.php'; ?>
<?php include __DIR__ . '/../components/authModal.php'; ?>

<section class="profile-container">
    <div class="profile-header">
        <div class="profile-top">
            <div class="profile-pic-container">
                <img src="<?= $viewedProfilePic ?>" class="profile-avatar" id="profilePicPreview">
                <?php if ($isOwnProfile): ?>
                    <div class="pfp-overlay"><p>Choose Picture</p></div>
                <?php endif; ?>
            </div>
            <div class="profile-user-info">
                <h2 id="usernameDisplay" style="color:#FF669C;"><?= htmlspecialchars($viewedUsername) ?></h2>
                <?php if ($isOwnProfile): ?>
                    <button id="editProfileBtn">Edit Profile</button>
                    <form method="post" enctype="multipart/form-data" id="editProfileForm" style="display:none;">
                        <input type="text" name="username" value="<?= htmlspecialchars($viewedUsername) ?>">
                        <input type="file" name="pfp" id="pfpInput" style="display:none;" accept="image/*">
                        <button class="button-3" type="submit">Apply</button>
                        <button class="button-3" type="button" id="cancelEdit">Cancel</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if ($isOwnProfile): ?>
                <button class="button-2" onclick="window.location.href='../logout.php'">
                    <img src="../images/logout.png" alt="Logout" class="icon-btn">
                </button>
                <button id="shareProfileBtn" data-id="<?= $viewingId ?>" class="button-3"><img src="../images/shared.png" alt="Share" class="icon-btn"></button>
            <?php endif; ?>
        </div>

        <div class="profile-stats">
            <p><span><?= $listCount ?></span>Lists</p>
            <p><span><?= $gamesSaved ?></span>Games Saved</p>
        </div>
    </div>

    <div class="lists-header">
        <h2><?= $isOwnProfile ? "My Lists" : htmlspecialchars($viewedUsername) . "'s Lists" ?></h2>
        <?php if ($isOwnProfile): ?>
            <button class="create-list-btn">Create a List</button>
        <?php endif; ?>
    </div>

    <div id="create-list-modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Create New List</h2>
            <form id="createListForm">
                <label>List Name:</label>
                <input type="text" id="listNameInput" name="name" placeholder="Enter list name" required>
                
                <label>Search Games to Add (Optional):</label>
                <input type="text" id="listGameSearch" placeholder="Type to search games..." autocomplete="off">
                <div id="listGameResults"></div>
                
                <div id="selectedGamesContainer"></div>
                
                <div id="listFeedback"></div>
                <button type="submit">Create List</button>
            </form>
        </div>
    </div>

    <div id="lists"></div>
</section>

<?php include __DIR__ . '/../components/footer.php'; ?>

<script>


document.addEventListener('DOMContentLoaded', () => {
    const isOwnProfile = <?= json_encode($isOwnProfile) ?>;

    if (isOwnProfile) {
        const editBtn = document.getElementById('editProfileBtn');
        const editForm = document.getElementById('editProfileForm');
        const usernameDisplay = document.getElementById('usernameDisplay');
        const cancelBtn = document.getElementById('cancelEdit');
        const profilePicContainer = document.querySelector('.profile-pic-container');
        const profilePic = document.getElementById('profilePicPreview');
        const pfpInput = document.getElementById('pfpInput');

        editBtn.onclick = () => {
            usernameDisplay.style.display = 'none';
            editBtn.style.display = 'none';
            editForm.style.display = 'block';
            profilePicContainer.classList.add('edit-mode');
        };

        cancelBtn.onclick = () => {
            editForm.style.display = 'none';
            usernameDisplay.style.display = 'block';
            editBtn.style.display = 'inline-block';
            profilePicContainer.classList.remove('edit-mode');
        };

        document.getElementById('shareProfileBtn').onclick = (e) => {
    let url = window.location.href;
    const userId = e.currentTarget.getAttribute('data-id');

    if (!url.includes('id=')) {
        url += (url.includes('?') ? '&' : '?') + 'id=' + userId;
    }

    navigator.clipboard.writeText(url);
    
    const toast = document.getElementById('toast-notification');
    toast.innerText = "Link copied: " + url;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 2000);
};

        profilePicContainer.onclick = () => {
            if (editForm.style.display === 'block') pfpInput.click();
        };

        pfpInput.onchange = () => {
            const file = pfpInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => profilePic.src = e.target.result;
                reader.readAsDataURL(file);
            }
        };
    }

    const listModal = document.getElementById('create-list-modal');
    const listClose = listModal.querySelector('.close');
    const listGameSearch = document.getElementById('listGameSearch');
    const listGameResults = document.getElementById('listGameResults');
    const selectedGamesContainer = document.getElementById('selectedGamesContainer');
    const listFeedback = document.getElementById('listFeedback');
    const listNameInput = document.getElementById('listNameInput');
    const createListForm = document.getElementById('createListForm');

    let selectedGames = [];
    let searchTimeout = null;

    function openListModal() {
        selectedGames = [];
        listModal.style.display = 'flex';
        listNameInput.value = '';
        listGameSearch.value = '';
        listGameResults.innerHTML = '';
        selectedGamesContainer.innerHTML = '';
        listFeedback.style.display = 'none';
    }

    function closeListModal() {
        listModal.style.display = 'none';
    }

    function showFeedback(message, isError = false) {
        listFeedback.textContent = message;
        listFeedback.className = isError ? 'error' : 'success';
        listFeedback.style.display = 'block';
        setTimeout(() => { listFeedback.style.display = 'none'; }, 3000);
    }

    function renderSelectedGames() {
        if (selectedGames.length === 0) {
            selectedGamesContainer.innerHTML = '';
            return;
        }

        const gamesHtml = selectedGames.map(game => `
            <div class="selected-game-item">
                <img src="../${game.main_image_url}" alt="${game.title}">
                <span>${game.title}</span>
                <button type="button" class="remove-btn" onclick="removeGame(${game.id})">✕</button>
            </div>
        `).join('');

        selectedGamesContainer.innerHTML = `
            <label>Selected Games (${selectedGames.length}):</label>
            <div class="selected-games-list">${gamesHtml}</div>
        `;
    }

    window.removeGame = (gameId) => {
        selectedGames = selectedGames.filter(g => g.id !== gameId);
        renderSelectedGames();
    };

    if (isOwnProfile) {
        document.querySelector('.create-list-btn').onclick = openListModal;
    }
    
    listClose.onclick = closeListModal;
    window.onclick = e => { if (e.target === listModal) closeListModal(); };

    listGameSearch.oninput = () => {
        clearTimeout(searchTimeout);
        const term = listGameSearch.value.trim();
        if (!term) { listGameResults.innerHTML = ''; return; }

        searchTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`?action=search_games&q=${encodeURIComponent(term)}`);
                const games = await res.json();
                if (games.length === 0) {
                    listGameResults.innerHTML = '<p class="no-results">No games found</p>';
                    return;
                }
                listGameResults.innerHTML = games.slice(0, 5).map(game => `
                    <div class="game-search-result" data-id="${game.id}" data-title="${game.title}" data-img="${game.main_image_url}">
                        <img src="../${game.main_image_url}" alt="${game.title}">
                        <span>${game.title}</span>
                    </div>
                `).join('');

                document.querySelectorAll('.game-search-result').forEach(el => {
                    el.onclick = () => {
                        const gameId = parseInt(el.dataset.id);

                        if (selectedGames.some(g => g.id === gameId)) {
                            showFeedback('Game already added!', true);
                            return;
                        }
                        selectedGames.push({ id: gameId, title: el.dataset.title, main_image_url: el.dataset.img });
                        renderSelectedGames();
                        listGameSearch.value = '';
                        listGameResults.innerHTML = '';
                    };
                });
            } catch (e) { console.error(e); }
        }, 300);
    };

    createListForm.onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('name', listNameInput.value.trim());
        selectedGames.forEach(game => formData.append('games[]', game.id));

        const res = await fetch('?action=create_list', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            closeListModal();
            loadUserLists();
        } else {
            showFeedback(data.error, true);
        }
    };

    async function loadUserLists() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const profileId = urlParams.get('id') || ''; 
            const fetchUrl = `?action=get_lists${profileId ? '&id=' + profileId : ''}`;

            const res = await fetch(fetchUrl);
            const data = await res.json();
            const listsContainer = document.getElementById('lists');

            if (data.lists.length === 0) {
                listsContainer.innerHTML = `<p class="no-lists">${isOwnProfile ? "You haven't created any lists." : "This user hasn't created any lists."}</p>`;
                return;
            }

            listsContainer.innerHTML = data.lists.map(list => `
                <div class="list-block">
                    <div class="list-title-row">
                        <h3>${escapeHtml(list.name)}</h3>
                        <div class="list-actions">
                            <a href="lists?id=${list.id}" class="see-more-btn">See More</a>
                            ${isOwnProfile ? `<button class="delete-list-btn" onclick="deleteList(${list.id})"><img src="../images/delete.png" alt="Delete" class="icon-btn"></button>` : ''}
                        </div>
                    </div>
                    <div class="list-games">
                        ${list.games.length === 0 ? '<p class="no-games">No games in this list</p>' : ''}
                        ${list.games.map(game => `
                            <div class="list-game-item">
                                <img src="../${game.main_image_url}" alt="${escapeHtml(game.title)}" title="${escapeHtml(game.title)}">
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error('Error loading lists:', error);
        }
    }

    window.deleteList = async (listId) => {
        if (!confirm('Are you sure?')) return;
        const formData = new FormData();
        formData.append('list_id', listId);
        await fetch('?action=delete_list', { method: 'POST', body: formData });
        loadUserLists();
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    loadUserLists();
});
</script>

<script src="../script.js"></script>
</body>
</html>