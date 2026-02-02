<?php
  session_start();
  require 'config.php';

  if (!isset($_SESSION['user_id'])) {
    header("Location: landing");
    exit;
  }

  $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();

  if (!$user['is_admin']) {
      header("Location: /landing");
      exit;
  }


  if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (isset($_POST['delete_user_id'])) {
      $userId = $_POST['delete_user_id'];
      $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
      $stmt->execute([$userId]);
      header("Location: dashboard");
      exit;
    }
    if (isset($_POST['role_user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
    $stmt->execute([$_POST['is_admin'], $_POST['role_user_id']]);
    header("Location: dashboard");
    exit;
  }
  }

  $users = $pdo -> query ("SELECT * FROM users")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="styledashboard.css">
  <link rel="stylesheet" href="../list.css">
</head>
<body>

  <div class="sidebar">
    <h1 style="padding-left: 3vh;">Admin Panel</h1>
    <a href="dashboard">Users</a>
    <a href="manage_games">Games</a>
  </div>

  <div class="main-content">

  <div class="header">
    <h2>User Management</h2> 
    <button class="button-3" onclick="window.location.href='landing'">Back to Quest</button>
</div>
<div class="table-container">
        <div class="table-header">
          <h2>All Users</h2>
          <span class="badge" id="userCount"><?= count($users) ?> users</span>
        </div>
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>User Email</th>
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:15px;">
                    <img src="<?= $user['pfp_url'] ?>" alt="Profile" class="profile-avatar" style="width:40px;height:40px;border-radius:50%;">
                    <div class="username"><?= htmlspecialchars($user['username']) ?></div>
                  </div>
                </td>
                <td>
                  <div class="email"><?= htmlspecialchars($user['email']) ?></div>
                </td>    
                <td>
                  <form method="POST">
                    <input type="hidden" name="role_user_id" value="<?= $user['id'] ?>">
                    <select name="is_admin" onchange="this.form.submit()">
                      <option value="0" <?= !$user['is_admin'] ? 'selected' : '' ?>>User</option>
                      <option value="1" <?= $user['is_admin'] ? 'selected' : '' ?>>Admin</option>
                    </select>
                  </form>
                </td>
                <td>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                      <button type="submit" class="button-2">
                        <img src="images/delete.png" alt="Delete" class="icon-btn">
                      </button>
                    </form>
                    <button onclick="window.location.href='profile?id=<?= $user['id'] ?>'"><img src="images/view.png" alt="View" class="icon-btn"> </button>
                </td>        
            </tr>
            <?php endforeach; ?>
            </tbody>   
        </table>
      </div>
    </div>
  </div>
</body>
</html>