<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game_id'])) {
    $gameId = $_POST['delete_game_id'];
    $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    header("Location: manage_games");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_game_id'])) {
    $gameId = $_POST['edit_game_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $release_date = $_POST['release_date'];

    $uploadDir = 'images/games/details/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    function uploadFileEdit($file, $dir) {
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $filename = time() . '_' . basename($file['name']);
            $target = $dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) return $target;
        }
        return null;
    }

    $main_image_url = uploadFileEdit($_FILES['main_image_url'], $uploadDir);
    $main_image2_url = uploadFileEdit($_FILES['main_image2_url'], $uploadDir);

    $stmt = $pdo->prepare("UPDATE games SET title=?, description=?, release_date=? " .
        ($main_image_url ? ", main_image_url=?" : "") .
        ($main_image2_url ? ", main_image2_url=?" : "") .
        " WHERE id=?");

    $params = [$title, $description, $release_date];
    if ($main_image_url) $params[] = $main_image_url;
    if ($main_image2_url) $params[] = $main_image2_url;
    $params[] = $gameId;

    $stmt->execute($params);

    if (!empty($_POST['genres'])) {
        $pdo->prepare("DELETE FROM game_genres WHERE game_id=?")->execute([$gameId]);
        foreach ($_POST['genres'] as $genreId) {
            $pdo->prepare("INSERT INTO game_genres (game_id, genre_id) VALUES (?, ?)")->execute([$gameId, $genreId]);
        }
    }

    header("Location: manage_games");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && !isset($_POST['edit_game_id'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $release_date = $_POST['release_date'];

    $uploadDir = 'images/games/details/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    function uploadFile($file, $dir) {
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $filename = time() . '_' . basename($file['name']);
            $target = $dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) return $target;
        }
        return null;
    }

    $main_image_url = uploadFile($_FILES['main_image_url'], $uploadDir);
    $main_image2_url = uploadFile($_FILES['main_image2_url'], $uploadDir);

    $stmt = $pdo->prepare("INSERT INTO games (title, description, release_date, main_image_url, main_image2_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $release_date, $main_image_url, $main_image2_url]);

    $gameId = $pdo->lastInsertId();

    if (!empty($_FILES['content_images']['name'][0])) {
        foreach ($_FILES['content_images']['tmp_name'] as $index => $tmpName) {
            $file = [
                'name' => $_FILES['content_images']['name'][$index],
                'tmp_name' => $tmpName,
                'error' => $_FILES['content_images']['error'][$index]
            ];
            $path = uploadFile($file, $uploadDir);
            if ($path) {
                $col = 'image' . ($index + 1);
                $pdo->prepare("UPDATE games SET $col = ? WHERE id = ?")->execute([$path, $gameId]);
            }
        }
    }

    if (!empty($_POST['genres'])) {
        foreach ($_POST['genres'] as $genreId) {
            $pdo->prepare("INSERT INTO game_genres (game_id, genre_id) VALUES (?, ?)")->execute([$gameId, $genreId]);
        }
    }

    header("Location: manage_games");
    exit;
}

$stmt = $pdo->query("
    SELECT g.id, g.title, g.description, g.release_date, GROUP_CONCAT(ge.name SEPARATOR ', ') as genres
    FROM games g
    LEFT JOIN game_genres gg ON g.id = gg.game_id
    LEFT JOIN genres ge ON gg.genre_id = ge.id
    GROUP BY g.id
    ORDER BY g.title
");
$games = $stmt->fetchAll();
$genres = $pdo->query("SELECT * FROM genres")->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="styledashboard.css">
</head>
<body>

  <div class="sidebar">
    <h1 style="padding-left: 3vh;">Admin Panel</h1>
    <a href="dashboard">Users</a>
    <a href="manage_games">Games</a>
  </div>

  <div class="main-content">
    <div class="header">
      <h2>Game Management</h2>
      <button style="margin-left:auto;" class="button-3" onclick="window.location.href='landing'">Back to Quest</button>

      <button  id="openModalBtn">Add Game</button>

    </div>

<div id="add-game-modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Add New Game</h2>
    <form method="POST" enctype="multipart/form-data">
      
      <label>Title:</label>
      <input type="text" name="title" placeholder="Enter game title" required>
      
      <label>Description:</label>
      <textarea name="description" placeholder="Enter game description" required></textarea>
      
      <label>Release Date:</label>
      <input type="date" name="release_date" required>
      
      <label>Genres:</label>
      <select name="genres[]" multiple required>
        <?php
        $genres = $pdo->query("SELECT * FROM genres")->fetchAll();
        foreach ($genres as $g) {
            echo "<option value='{$g['id']}'>{$g['name']}</option>";
        }
        ?>
      </select>

      <label>Cover Image 1:</label>
      <input type="file" name="main_image_url" accept="image/*" required>

      <label>Cover Image 2:</label>
      <input type="file" name="main_image2_url" accept="image/*">

      <label>Content Images:</label>
      <input type="file" name="content_images[]" accept="image/*" multiple>

      <button type="submit">Add Game</button>
    </form>
  </div>
</div>

<div id="edit-game-modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Edit Game</h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="edit_game_id" id="editGameId">
      
      <label>Title:</label>
      <input type="text" name="title" id="editTitle" required>
      
      <label>Description:</label>
      <textarea name="description" id="editDescription" required></textarea>
      
      <label>Release Date:</label>
      <input type="date" name="release_date" id="editReleaseDate" required>
      
      <label>Genres:</label>
      <select name="genres[]" id="editGenres" multiple required>
        <?php
        foreach ($genres as $g) {
            echo "<option value='{$g['id']}'>{$g['name']}</option>";
        }
        ?>
      </select>

      <label>Cover Image 1:</label>
      <input type="file" name="main_image_url">

      <label>Cover Image 2:</label>
      <input type="file" name="main_image2_url">

      <label>Content Images:</label>
      <input type="file" name="content_images[]" multiple>

      <button type="submit">Save Changes</button>
    </form>
  </div>
</div>



    <div class="table-container">
      <div class="table-header">
        <h2>All Games</h2>
        <span class="badge" id="userCount"><?= count($games) ?> games</span>
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
        <tbody id="usersTableBody">
          <?php foreach($games as $game): ?>
          <tr data-id="<?= $game['id'] ?>">
  <td><?= htmlspecialchars($game['title']) ?></td>
  <td><?= htmlspecialchars($game['genres']) ?></td>
  <td><?= htmlspecialchars($game['description']) ?></td>
  <td><?= date('Y', strtotime($game['release_date'])) ?></td>
  <td>
    <button class="edit-btn button-3"><img src="images/edit.png" alt="Edit" class="icon-btn"></button>
      
    <button><img src="images/view.png" alt="View" class="icon-btn"></button>
    <form method="POST" style="display:inline;">
        <input type="hidden" name="delete_game_id" value="<?= $game['id'] ?>">
        <button type="submit" class="button-2">
          <img src="images/delete.png" alt="Delete" class="icon-btn">
        </button>
      </form>
  </td>
</tr>



          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>

<script>
  const modal = document.getElementById('add-game-modal');
const btn = document.getElementById('openModalBtn');
const close = modal.querySelector('.close');

btn.onclick = () => modal.style.display = 'block';
close.onclick = () => modal.style.display = 'none';
window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; }

const editModal = document.getElementById('edit-game-modal');
const editClose = editModal.querySelector('.close');

document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const row = btn.closest('tr');
    const id = row.dataset.id;
    const title = row.querySelector('td:nth-child(1)').textContent;
    const genres = row.querySelector('td:nth-child(2)').textContent.split(', ').map(g => g.trim());
    const desc = row.querySelector('td:nth-child(3)').textContent;
    const date = row.querySelector('td:nth-child(4)').textContent;

    document.getElementById('editGameId').value = id;
    document.getElementById('editTitle').value = title;
    document.getElementById('editDescription').value = desc;
    document.getElementById('editReleaseDate').value = new Date(date).toISOString().split('T')[0];

    const options = document.getElementById('editGenres').options;
    for (let i = 0; i < options.length; i++) {
      options[i].selected = genres.includes(options[i].text);
    }

    editModal.style.display = 'block';
  });
});

editClose.onclick = () => editModal.style.display = 'none';
window.addEventListener('click', e => { 
  if (e.target == editModal) editModal.style.display = 'none';
});

</script>



</html>
