<?php
ob_start();
require_once __DIR__ . '/../config.php';

session_start();

$loggedInId = $_SESSION['user_id'] ?? null;
$listId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Handle AJAX actions first (before any HTML output)
if (isset($_GET['action'])) {
    ob_end_clean();
    header('Content-Type: application/json');

    if (!$listId) {
        echo json_encode(['success' => false, 'error' => 'Invalid list ID']);
        exit;
    }

    // Fetch list to check ownership
    $stmt = $pdo->prepare("SELECT user_id FROM lists WHERE id = ?");
    $stmt->execute([$listId]);
    $list = $stmt->fetch(PDO::FETCH_ASSOC);
    $isOwnList = ($loggedInId && intval($loggedInId) === intval($list['user_id'] ?? 0));

    if ($_GET['action'] === 'rename_list') {
        if (!$isOwnList) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        $newName = trim($_POST['name'] ?? '');
        if (!$newName) {
            echo json_encode(['success' => false, 'error' => 'Name cannot be empty']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE lists SET name = ? WHERE id = ?");
            $stmt->execute([$newName, $listId]);
            echo json_encode(['success' => true, 'name' => $newName]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['action'] === 'remove_game') {
        if (!$isOwnList) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        $gameId = intval($_POST['game_id'] ?? 0);
        if (!$gameId) {
            echo json_encode(['success' => false, 'error' => 'Invalid game ID']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM list_games WHERE list_id = ? AND game_id = ?");
            $stmt->execute([$listId, $gameId]);
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['action'] === 'add_game') {
        if (!$isOwnList) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        $gameId = intval($_POST['game_id'] ?? 0);
        if (!$gameId) {
            echo json_encode(['success' => false, 'error' => 'Invalid game ID']);
            exit;
        }
        
        // Check if game already in list
        $stmt = $pdo->prepare("SELECT 1 FROM list_games WHERE list_id = ? AND game_id = ?");
        $stmt->execute([$listId, $gameId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Game already in list']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO list_games (list_id, game_id) VALUES (?, ?)");
            $stmt->execute([$listId, $gameId]);
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['action'] === 'search_games') {
        $q = trim($_GET['q'] ?? '');
        if (!$q) { 
            echo json_encode([]); 
            exit; 
        }
        $stmt = $pdo->prepare("SELECT id, title, main_image2_url FROM games WHERE title LIKE ? LIMIT 15");
        $stmt->execute(["%$q%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
}

// Now load page data (only if not an AJAX request)
ob_end_clean();

if (!$listId) {
    header("Location: landing");
    exit();
}

// Fetch the list details
$stmt = $pdo->prepare("SELECT id, name, user_id FROM lists WHERE id = ?");
$stmt->execute([$listId]);
$list = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$list) {
    header("Location: landing");
    exit();
}

$isOwnList = ($loggedInId && intval($loggedInId) === intval($list['user_id']));

// Fetch games in this list
$stmt = $pdo->prepare("
    SELECT g.id, g.title, g.main_image2_url, g.description, g.release_date
    FROM games g
    INNER JOIN list_games lg ON g.id = lg.game_id
    WHERE lg.list_id = ?
");
$stmt->execute([$listId]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all games for the add games modal
$stmt = $pdo->prepare("SELECT id, title, main_image2_url FROM games ORDER BY title LIMIT 50");
$stmt->execute();
$allGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of game IDs already in this list
$gameIds = array_map(fn($g) => $g['id'], $games);

// Handle form submission for list rename
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action']) && $isOwnList) {
    $newName = trim($_POST['list_name'] ?? '');
    if (!empty($newName)) {
        $stmt = $pdo->prepare("UPDATE lists SET name = ? WHERE id = ?");
        $stmt->execute([$newName, $listId]);
        header("Location: lists?id=" . $listId);
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($list['name']) ?> - Quest</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../responsive.css">
    <style>
        .list-header {
            margin: 40px auto;
            width: 75%;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .list-header h1 {
            margin: 0;
            flex-grow: 1;
            color: #FF669C;
        }

        .list-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .view-toggle {
            display: flex;
            gap: 8px;
            background: transparent;
            padding: 0;
        }

        .view-toggle button {
            padding: 6px 12px;
            background: #555;
            border: none;
            color: #ccc;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 12px;
            font-weight: 500;
        }

        .view-toggle button.active {
            background: #44A1A0;
            color: white;
            padding: 8px 16px;
            font-size: 13px;
            transform: scale(1.1);
        }

        .list-action-btn {
            padding: 8px 15px;
            background: #44A1A0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .list-action-btn:hover {
            background: #368c8b;
        }

        .list-action-btn.secondary {
            background: transparent;
            border: 2px solid #44A1A0;
            color: #44A1A0;
        }

        .list-action-btn.secondary:hover {
            background: rgba(68, 161, 160, 0.1);
        }

        .game-container {
            margin: 2% auto;
            width: 75%;
        }

        .game-container.grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }

        .game-container.detailed-view {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .game-container.lists-view {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .game-card {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }

        .game-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        /* Detailed View - Text Only */
        .detailed-view .game-card {
            padding: 15px;
            background: #2a2a2a;
            border-left: 4px solid #44A1A0;
            cursor: pointer;
        }

        .detailed-view .game-card:hover {
            background: #333;
            transform: translateX(5px);
        }

        .detailed-view .game-card-info {
            flex-grow: 1;
        }

        .detailed-view .game-card-info h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #FF669C;
        }

        .detailed-view .game-card-info .secondary-text {
            color: #aaa;
            font-size: 13px;
            margin: 6px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .detailed-view .genres-container {
            display: flex;
            gap: 6px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .detailed-view .genre-badge {
            background: #44A1A0;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
        }

        /* Lists View - Image + Text */
        .lists-view .game-card {
            display: flex;
            gap: 15px;
            padding: 12px;
            background: #2a2a2a;
            align-items: center;
            position: relative;
        }

        .lists-view .game-card img {
            width: 100px;
            height: 140px;
            object-fit: cover;
            border-radius: 5px;
            flex-shrink: 0;
        }

        .lists-view .game-card-info {
            flex-grow: 1;
        }

        .lists-view .game-card-info h3 {
            margin: 0 0 6px 0;
            font-size: 16px;
            color: #FF669C;
        }

        .lists-view .game-card-info .secondary-text {
            color: #aaa;
            font-size: 13px;
            margin: 6px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Grid View - Images */
        .grid-view .game-card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }

        .grid-view .game-card-info {
            padding: 12px;
            background: #2a2a2a;
            display: none;
        }

        .grid-view .game-card .remove-game-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255, 102, 156, 0.9);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.3s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .grid-view .game-card:hover .remove-game-btn {
            opacity: 1;
        }

        .grid-view .remove-game-btn:hover {
            background: rgba(255, 102, 156, 1);
        }

        .lists-view .remove-game-btn {
            position: relative;
            opacity: 1;
            margin-left: auto;
        }

        .detailed-view .remove-game-btn {
            display: none;
        }

        .edit-list-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }

        .edit-list-modal.show {
            display: flex;
        }

        .edit-list-modal .modal-content {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .edit-list-modal .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            margin: -10px -10px 0 0;
        }

        .edit-list-modal .close:hover {
            color: #FF669C;
        }

        .edit-list-modal h2 {
            color: #FF669C;
            margin-top: 0;
            margin-bottom: 20px;
            clear: both;
        }

        .edit-list-modal input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: none;
            border-radius: 5px;
            background: #2a2a2a;
            color: #fff;
            box-sizing: border-box;
        }

        .edit-list-modal button {
            padding: 10px 20px;
            background: #44A1A0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            transition: background 0.3s ease;
        }

        .edit-list-modal button:hover {
            background: #368c8b;
        }

        .edit-list-modal .cancel-btn {
            background: #666;
        }

        .edit-list-modal .cancel-btn:hover {
            background: #555;
        }

        .no-games {
            text-align: center;
            color: #aaa;
            padding: 40px;
            font-size: 16px;
        }

        .toast-notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #44A1A0;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            display: none;
            z-index: 2000;
            max-width: 90%;
            text-align: center;
        }

        @media (max-width: 768px) {
            .list-header {
                width: 90%;
                flex-direction: column;
            }

            .game-container {
                width: 90%;
            }

            .game-container.grid-view {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 10px;
            }

            .detailed-view .game-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .detailed-view .game-card img {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../components/nav.php'; ?>
<?php include __DIR__ . '/../components/sidebar.php'; ?>
<?php include __DIR__ . '/../components/authModal.php'; ?>

<div class="list-header">
    <h1 id="list-title"><?= htmlspecialchars($list['name']) ?></h1>
    <div class="list-controls">
        <div class="view-toggle">
            <button class="view-btn active" data-view="grid-view">Grid</button>
            <button class="view-btn" data-view="detailed-view">Detailed</button>
            <?php if ($isOwnList): ?>
                <button class="view-btn" data-view="lists-view">Lists</button>
            <?php endif; ?>
        </div>
        <button class="list-action-btn" id="shareListBtn">Share</button>
        <?php if ($isOwnList): ?>
            <button class="list-action-btn" id="addGamesBtn">+ Add Games</button>
            <button class="list-action-btn" id="editListBtn">Edit</button>
        <?php endif; ?>
    </div>
</div>

<div class="game-container grid-view" id="game-container">
    <?php if (empty($games)): ?>
        <div class="no-games">No games in this list yet.</div>
    <?php else: ?>
        <?php foreach ($games as $game): ?>
            <div class="game-card" data-game-id="<?= $game['id'] ?>">
                <img src="../<?= htmlspecialchars($game['main_image2_url']) ?>" alt="<?= htmlspecialchars($game['title']) ?>" onerror="this.src='../images/games/placeholder.png';">
                <div class="game-card-info">
                    <h3><?= htmlspecialchars($game['title']) ?></h3>
                    <p class="secondary-text"><?= htmlspecialchars(substr($game['description'] ?? 'No description', 0, 100)) ?></p>
                    <?php if ($game['release_date']): ?>
                        <p class="release-date"><?= htmlspecialchars($game['release_date']) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($isOwnList): ?>
                    <button class="remove-game-btn" title="Remove from list" onclick="removeGame(<?= $game['id'] ?>)">✕</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Edit List Modal -->
<?php if ($isOwnList): ?>
<div id="editListModal" class="edit-list-modal">
    <div class="modal-content">
        <button class="close" id="closeEditModal">&times;</button>
        <h2>Edit List</h2>
        <form id="editListForm">
            <label for="listNameInput">List Name:</label>
            <input type="text" id="listNameInput" name="list_name" value="<?= htmlspecialchars($list['name']) ?>" required>
            <button type="submit">Save</button>
            <button type="button" class="cancel-btn" id="cancelEditBtn">Cancel</button>
        </form>
    </div>
</div>

<!-- Add Games Modal -->
<div id="addGamesModal" class="edit-list-modal">
    <div class="modal-content">
        <button class="close" id="closeAddGamesModal">&times;</button>
        <h2>Add Games to List</h2>
        <input type="text" id="searchGameInput" placeholder="Search games..." autocomplete="off" style="width: 100%; padding: 10px; margin-bottom: 15px; border: none; border-radius: 5px; background: #2a2a2a; color: #fff; box-sizing: border-box;">
        <div id="gameSearchResults" style="max-height: 400px; overflow-y: auto; margin-bottom: 15px;"></div>
        <div id="selectedGamesList" style="margin-top: 15px;"></div>
        <button type="button" id="confirmAddGamesBtn" style="padding: 10px 20px; background: #44A1A0; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">Add Selected</button>
        <button type="button" class="cancel-btn" id="cancelAddGamesBtn" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../components/footer.php'; ?>

<div class="toast-notification" id="toast-notification"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const isOwnList = <?= json_encode($isOwnList) ?>;
    const listId = <?= json_encode($listId) ?>;
    const gameContainer = document.getElementById('game-container');
    const viewToggleBtns = document.querySelectorAll('.view-btn');
    const shareListBtn = document.getElementById('shareListBtn');
    const editListBtn = document.getElementById('editListBtn');
    const addGamesBtn = document.getElementById('addGamesBtn');
    const editListModal = document.getElementById('editListModal');
    const addGamesModal = document.getElementById('addGamesModal');
    const closeEditModal = document.getElementById('closeEditModal');
    const closeAddGamesModal = document.getElementById('closeAddGamesModal');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const cancelAddGamesBtn = document.getElementById('cancelAddGamesBtn');
    const editListForm = document.getElementById('editListForm');
    const toastNotification = document.getElementById('toast-notification');
    let currentView = 'grid-view';
    let selectedGamesToAdd = new Set();

    // Get current list of game IDs
    const currentGameIds = new Set(Array.from(document.querySelectorAll('.game-card')).map(card => parseInt(card.dataset.gameId)));

    // View toggle
    viewToggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            viewToggleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentView = btn.dataset.view;
            gameContainer.className = `game-container ${currentView}`;
            localStorage.setItem('listViewMode', currentView);
            renderGames();
        });
    });

    // Load saved view preference
    const savedView = localStorage.getItem('listViewMode');
    if (savedView && savedView !== 'grid-view') {
        gameContainer.className = `game-container ${savedView}`;
        viewToggleBtns.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.view === savedView) {
                btn.classList.add('active');
            }
        });
        currentView = savedView;
    }

    // Share list
    shareListBtn.addEventListener('click', () => {
        const url = window.location.href;
        navigator.clipboard.writeText(url);
        toastNotification.textContent = "Link copied to clipboard!";
        toastNotification.style.display = 'block';
        setTimeout(() => { toastNotification.style.display = 'none'; }, 2000);
    });

    // Edit list
    if (isOwnList) {
        editListBtn.addEventListener('click', () => {
            editListModal.classList.add('show');
        });

        closeEditModal.addEventListener('click', () => {
            editListModal.classList.remove('show');
        });

        cancelEditBtn.addEventListener('click', () => {
            editListModal.classList.remove('show');
        });

        window.addEventListener('click', (e) => {
            if (e.target === editListModal) {
                editListModal.classList.remove('show');
            }
            if (e.target === addGamesModal) {
                addGamesModal.classList.remove('show');
            }
        });

        editListForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const newName = document.getElementById('listNameInput').value.trim();
            if (!newName) {
                toastNotification.textContent = 'List name cannot be empty!';
                toastNotification.style.display = 'block';
                toastNotification.style.background = '#FF6B6B';
                setTimeout(() => { toastNotification.style.display = 'none'; }, 2000);
                return;
            }

            try {
                const formData = new FormData();
                formData.append('name', newName);
                const res = await fetch(`?id=${listId}&action=rename_list`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('list-title').textContent = newName;
                    editListModal.classList.remove('show');
                    toastNotification.textContent = "List renamed successfully!";
                    toastNotification.style.display = 'block';
                    toastNotification.style.background = '#44A1A0';
                    setTimeout(() => { toastNotification.style.display = 'none'; }, 2000);
                } else {
                    console.error('Rename error:', data);
                    toastNotification.textContent = 'Error: ' + (data.error || 'Failed to rename list');
                    toastNotification.style.display = 'block';
                    toastNotification.style.background = '#FF6B6B';
                    setTimeout(() => { toastNotification.style.display = 'none'; }, 3000);
                }
            } catch (e) {
                console.error('Error renaming list:', e);
                toastNotification.textContent = 'Error renaming list!';
                toastNotification.style.display = 'block';
                toastNotification.style.background = '#FF6B6B';
                setTimeout(() => { toastNotification.style.display = 'none'; }, 3000);
            }
        });

        // Add Games Modal
        addGamesBtn.addEventListener('click', () => {
            selectedGamesToAdd.clear();
            document.getElementById('gameSearchResults').innerHTML = '';
            document.getElementById('selectedGamesList').innerHTML = '';
            document.getElementById('searchGameInput').value = '';
            addGamesModal.classList.add('show');
        });

        closeAddGamesModal.addEventListener('click', () => {
            addGamesModal.classList.remove('show');
        });

        cancelAddGamesBtn.addEventListener('click', () => {
            addGamesModal.classList.remove('show');
        });

        // Search games
        let searchTimeout;
        document.getElementById('searchGameInput').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            if (!query) {
                document.getElementById('gameSearchResults').innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const res = await fetch(`?id=${listId}&action=search_games&q=${encodeURIComponent(query)}`);
                    const games = await res.json();
                    const resultsDiv = document.getElementById('gameSearchResults');
                    
                    if (games.length === 0) {
                        resultsDiv.innerHTML = '<p style="color: #aaa; text-align: center;">No games found</p>';
                        return;
                    }

                    resultsDiv.innerHTML = games.map(game => `
                        <div class="game-search-result" data-game-id="${game.id}" style="padding: 10px; background: #2a2a2a; margin-bottom: 8px; border-radius: 4px; cursor: pointer; border: 2px solid transparent; transition: all 0.3s ease;">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <img src="../${game.main_image2_url}" alt="${game.title}" style="width: 50px; height: 70px; object-fit: cover; border-radius: 3px;">
                                <div>
                                    <p style="margin: 0; color: #FF669C; font-weight: bold;">${game.title}</p>
                                    <p style="margin: 4px 0 0 0; color: #aaa; font-size: 12px;">${currentGameIds.has(game.id) ? '✓ Already in list' : 'Click to add'}</p>
                                </div>
                            </div>
                        </div>
                    `).join('');

                    document.querySelectorAll('.game-search-result').forEach(el => {
                        const gameId = parseInt(el.dataset.gameId);
                        if (currentGameIds.has(gameId)) {
                            el.style.opacity = '0.5';
                            el.style.pointerEvents = 'none';
                        } else {
                            el.addEventListener('click', () => {
                                const game = games.find(g => g.id === gameId);
                                selectedGamesToAdd.add(gameId);
                                currentGameIds.add(gameId);
                                renderSelectedGames();
                                renderSearchResults(games);
                            });
                        }
                    });
                } catch (e) {
                    console.error(e);
                }
            }, 300);
        });

        function renderSelectedGames() {
            const selectedDiv = document.getElementById('selectedGamesList');
            if (selectedGamesToAdd.size === 0) {
                selectedDiv.innerHTML = '';
                return;
            }

            const selectedGamesData = Array.from(selectedGamesToAdd).map(id => {
                const allGamesArray = <?= json_encode($allGames) ?>;
                return allGamesArray.find(g => g.id === id);
            }).filter(g => g);

            selectedDiv.innerHTML = `
                <div style="background: #2a2a2a; padding: 12px; border-radius: 5px; border-top: 2px solid #44A1A0;">
                    <p style="margin: 0 0 10px 0; color: #44A1A0; font-size: 14px; font-weight: bold;">Selected (${selectedGamesToAdd.size})</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        ${selectedGamesData.map(game => `
                            <div style="position: relative; width: 70px; height: 100px; border-radius: 4px; overflow: hidden;">
                                <img src="../${game.main_image2_url}" alt="${game.title}" style="width: 100%; height: 100%; object-fit: cover;">
                                <button type="button" onclick="removeSelectedGame(${game.id})" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 102, 156, 0.9); border: none; color: white; width: 24px; height: 24px; border-radius: 3px; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">✕</button>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        function renderSearchResults(games) {
            const resultsDiv = document.getElementById('gameSearchResults');
            if (document.getElementById('searchGameInput').value.trim() === '') {
                resultsDiv.innerHTML = '';
                return;
            }

            resultsDiv.innerHTML = games.map(game => `
                <div class="game-search-result" data-game-id="${game.id}" style="padding: 10px; background: #2a2a2a; margin-bottom: 8px; border-radius: 4px; cursor: pointer; border: 2px solid transparent; transition: all 0.3s ease; opacity: ${currentGameIds.has(game.id) ? '0.5' : '1'}; pointer-events: ${currentGameIds.has(game.id) ? 'none' : 'auto'};">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <img src="../${game.main_image2_url}" alt="${game.title}" style="width: 50px; height: 70px; object-fit: cover; border-radius: 3px;">
                        <div>
                            <p style="margin: 0; color: #FF669C; font-weight: bold;">${game.title}</p>
                            <p style="margin: 4px 0 0 0; color: #aaa; font-size: 12px;">${currentGameIds.has(game.id) ? '✓ Already in list' : 'Click to add'}</p>
                        </div>
                    </div>
                </div>
            `).join('');

            document.querySelectorAll('.game-search-result').forEach(el => {
                const gameId = parseInt(el.dataset.gameId);
                if (!currentGameIds.has(gameId)) {
                    el.addEventListener('click', () => {
                        selectedGamesToAdd.add(gameId);
                        currentGameIds.add(gameId);
                        renderSelectedGames();
                        renderSearchResults(games);
                    });
                }
            });
        }

        window.removeSelectedGame = (gameId) => {
            selectedGamesToAdd.delete(gameId);
            currentGameIds.delete(gameId);
            renderSelectedGames();
            const searchInput = document.getElementById('searchGameInput');
            if (searchInput.value.trim()) {
                searchInput.dispatchEvent(new Event('input'));
            }
        };

        document.getElementById('confirmAddGamesBtn').addEventListener('click', async () => {
            if (selectedGamesToAdd.size === 0) {
                toastNotification.textContent = 'No games selected!';
                toastNotification.style.display = 'block';
                toastNotification.style.background = '#FF6B6B';
                setTimeout(() => { toastNotification.style.display = 'none'; }, 2000);
                return;
            }

            try {
                let allSuccess = true;
                for (const gameId of selectedGamesToAdd) {
                    const formData = new FormData();
                    formData.append('game_id', gameId);
                    const res = await fetch(`?id=${listId}&action=add_game`, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (!data.success) {
                        console.error(`Failed to add game ${gameId}:`, data.error);
                        allSuccess = false;
                    }
                }
                
                if (allSuccess) {
                    toastNotification.textContent = 'Games added successfully!';
                    toastNotification.style.display = 'block';
                    toastNotification.style.background = '#44A1A0';
                    setTimeout(() => { 
                        toastNotification.style.display = 'none';
                        location.reload();
                    }, 1500);
                } else {
                    toastNotification.textContent = 'Some games failed to add. Check console.';
                    toastNotification.style.display = 'block';
                    toastNotification.style.background = '#FF6B6B';
                    setTimeout(() => { toastNotification.style.display = 'none'; }, 3000);
                }
            } catch (e) {
                console.error('Error adding games:', e);
                toastNotification.textContent = 'Error adding games!';
                toastNotification.style.display = 'block';
                toastNotification.style.background = '#FF6B6B';
                setTimeout(() => { toastNotification.style.display = 'none'; }, 3000);
            }
        });
    }

    // Remove game from list
    window.removeGame = async (gameId) => {
        if (!confirm('Remove this game from the list?')) return;

        try {
            const formData = new FormData();
            formData.append('game_id', gameId);

            const res = await fetch(`?id=${listId}&action=remove_game`, {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (data.success) {
                const gameCard = document.querySelector(`.game-card[data-game-id="${gameId}"]`);
                if (gameCard) {
                    gameCard.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => {
                        gameCard.remove();
                        // Check if list is now empty
                        const remainingCards = document.querySelectorAll('.game-card').length;
                        if (remainingCards === 0) {
                            gameContainer.innerHTML = '<div class="no-games">No games in this list yet.</div>';
                        }
                    }, 300);
                    toastNotification.textContent = 'Game removed successfully!';
                    toastNotification.style.display = 'block';
                    toastNotification.style.background = '#44A1A0';
                    setTimeout(() => { toastNotification.style.display = 'none'; }, 2000);
                }
            } else {
                console.error('Remove game error:', data);
                toastNotification.textContent = 'Error: ' + (data.error || 'Failed to remove game');
                toastNotification.style.display = 'block';
                toastNotification.style.background = '#FF6B6B';
                setTimeout(() => { toastNotification.style.display = 'none'; }, 3000);
            }
        } catch (e) {
            console.error('Error removing game:', e);
            toastNotification.textContent = 'Error removing game!';
            toastNotification.style.display = 'block';
            toastNotification.style.background = '#FF6B6B';
            setTimeout(() => { toastNotification.style.display = 'none'; }, 3000);
        }
    };

    // Render games with genre info
    function renderGames() {
        if (currentView === 'compact-view') {
            // For compact view, we need to fetch genre info
            // For now, we'll keep the basic rendering
        }
    }

    // Click on game card to go to details page
    gameContainer.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') return;
        const gameCard = e.target.closest('.game-card');
        if (gameCard) {
            const gameId = gameCard.dataset.gameId;
            window.location.href = `details?id=${gameId}`;
        }
    });
});
</script>

<script src="../script.js"></script>
</body>
</html>
