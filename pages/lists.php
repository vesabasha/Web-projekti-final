<?php
ob_start();
require_once __DIR__ . '/../config.php';

session_start();

$loggedInId = $_SESSION['user_id'] ?? null;
$listId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (isset($_GET['action'])) {
    ob_end_clean();
    header('Content-Type: application/json');

    if (!$listId) {
        echo json_encode(['success' => false, 'error' => 'Invalid list ID']);
        exit;
    }

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

ob_end_clean();

if (!$listId) {
    header("Location: landing");
    exit();
}

$stmt = $pdo->prepare("SELECT id, name, user_id FROM lists WHERE id = ?");
$stmt->execute([$listId]);
$list = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$list) {
    header("Location: landing");
    exit();
}

$isOwnList = ($loggedInId && intval($loggedInId) === intval($list['user_id']));

$stmt = $pdo->prepare("
    SELECT g.id, g.title, g.main_image_url, g.description, g.release_date, GROUP_CONCAT(ge.name SEPARATOR ', ') as genres
    FROM games g
    INNER JOIN list_games lg ON g.id = lg.game_id
    LEFT JOIN game_genres gg ON g.id = gg.game_id
    LEFT JOIN genres ge ON gg.genre_id = ge.id
    WHERE lg.list_id = ?
    GROUP BY g.id
");
$stmt->execute([$listId]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, title, main_image_url FROM games ORDER BY title LIMIT 50");
$stmt->execute();
$allGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

$gameIds = array_map(fn($g) => $g['id'], $games);

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

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
        $stmt = $pdo->prepare("UPDATE lists SET name = ? WHERE id = ?");
        $stmt->execute([$newName, $listId]);
        echo json_encode(['success' => true, 'name' => $newName]);
        exit;
    }

    if ($_GET['action'] === 'remove_game') {
        if (!$isOwnList) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        $gameId = intval($_POST['game_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM list_games WHERE list_id = ? AND game_id = ?");
        $stmt->execute([$listId, $gameId]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($_GET['action'] === 'add_game') {
        if (!$isOwnList) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        $gameId = intval($_POST['game_id'] ?? 0);
        
        // Check if game already in list
        $stmt = $pdo->prepare("SELECT id FROM list_games WHERE list_id = ? AND game_id = ?");
        $stmt->execute([$listId, $gameId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Game already in list']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO list_games (list_id, game_id) VALUES (?, ?)");
        $stmt->execute([$listId, $gameId]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($_GET['action'] === 'search_games') {
        $q = trim($_GET['q'] ?? '');
        if (!$q) { 
            echo json_encode([]); 
            exit; 
        }
        $stmt = $pdo->prepare("SELECT id, title, main_image_url FROM games WHERE title LIKE ? LIMIT 15");
        $stmt->execute(["%$q%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
}

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
    <link rel="stylesheet" href="../list.css">
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
        <button class="button-3" id="shareListBtn"><img class="icon-btn" src="../images/shared.png" alt="Share"></button>
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
            <div class="game-card" data-game-id="<?= $game['id'] ?>" data-game-title="<?= htmlspecialchars($game['title']) ?>">
                <a class="card-link" href="/details?game=<?= urlencode($game['title']) ?>" style="display:flex; gap:12px; color:inherit; text-decoration:none; width:100%;">
                    <img src="../<?= htmlspecialchars($game['main_image_url']) ?>" alt="<?= htmlspecialchars($game['title']) ?>" onerror="this.src='../images/games/placeholder.png';">
                    <div class="game-card-info">
                        <h3><?= htmlspecialchars($game['title']) ?></h3>
                        <p class="secondary-text"><?= htmlspecialchars(substr($game['description'] ?? 'No description', 0, 1000)) ?></p>
                        <?php if ($game['release_date']): ?>
                            <p class="release-date"><?= htmlspecialchars($game['release_date']) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php if ($isOwnList): ?>
                        <button class="remove-game-btn" title="Remove from list" onclick="removeGame(<?= $game['id'] ?>)">✕</button>
                    <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
    
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

<div id="lists-table-container" style="margin-left: 230px; ">
    <div class="table-container" style="min-width: 1400px;">
        <div class="table-header">
            <h2><?= htmlspecialchars($list['name']) ?></h2>
            <span class="badge" id="gameCount"><?= count($games) ?> games</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Genres</th>
                    <th>Description</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $g): ?>
                    <tr data-game-id="<?= $g['id'] ?>">
                        <td><?= htmlspecialchars($g['title']) ?></td>
                        <td><?= htmlspecialchars($g['genres'] ?? '') ?></td>
                        <td><?= htmlspecialchars(substr($g['description'] ?? 'No description', 0, 500)) ?></td>
                        <td><?= $g['release_date'] ? date('Y', strtotime($g['release_date'])) : '' ?></td>
                        <td>
                            <button class="button-3" onclick="window.location.href='details?game=<?= urlencode($g['title']) ?>'"><img class="icon-btn" src="../images/view.png" alt="View"></button>
                            <?php if ($isOwnList): ?>
                                <button class="button-2" onclick="removeGame(<?= $g['id'] ?>)"><img class="icon-btn" src="../images/delete.png" alt="Remove"></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<div class="toast-notification" id="toast-notification"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const isOwnList = <?= json_encode($isOwnList) ?>;
    const listId = <?= json_encode($listId) ?>;
    const gameContainer = document.getElementById('game-container');
    const listsTable = document.getElementById('lists-table-container');
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

    const currentGameIds = new Set(Array.from(document.querySelectorAll('.game-card')).map(card => parseInt(card.dataset.gameId)));

    function updateViewVisibility() {
        if (currentView === 'lists-view') {
            gameContainer.style.display = 'none';
            listsTable.style.display = 'block';
        } else {
            gameContainer.style.display = '';
            listsTable.style.display = 'none';
            gameContainer.className = `game-container ${currentView}`;
        }
    }

    viewToggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            viewToggleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentView = btn.dataset.view;
            localStorage.setItem('listViewMode', currentView);
            updateViewVisibility();
            renderGames();
        });
    });

    const savedView = localStorage.getItem('listViewMode');
    if (savedView) {
        viewToggleBtns.forEach(btn => btn.classList.remove('active'));
        viewToggleBtns.forEach(btn => { if (btn.dataset.view === savedView) btn.classList.add('active'); });
        currentView = savedView;
        updateViewVisibility();
    }

    shareListBtn.addEventListener('click', () => {
        const url = window.location.href;
        navigator.clipboard.writeText(url);
        toastNotification.textContent = "Link copied to clipboard!";
        toastNotification.style.display = 'block';
        setTimeout(() => { toastNotification.style.display = 'none'; }, 2000);
    });

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
                                <img src="../${game.main_image_url}" alt="${game.title}" style="width: 50px; height: 70px; object-fit: cover; border-radius: 3px;">
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
                                <img src="../${game.main_image_url}" alt="${game.title}" style="width: 100%; height: 100%; object-fit: cover;">
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
                        <img src="../${game.main_image_url}" alt="${game.title}" style="width: 50px; height: 70px; object-fit: cover; border-radius: 3px;">
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

    function renderGames() {
        if (currentView === 'compact-view') {
        }
    }
    
    gameContainer.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') return;
            const gameCard = e.target.closest('.game-card');
        if (gameCard) {
            const gameTitle = gameCard.dataset.gameTitle;
            window.location.href = '/details?game=' + encodeURIComponent(gameTitle);
        }
    });
});
</script>

<script src="../script.js"></script>
</body>
</html>
