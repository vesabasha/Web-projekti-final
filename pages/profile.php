<?php
require_once __DIR__ . '/../config.php';

session_start();

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';
$profilePic = $_SESSION['pfp_url'] ?? 'images/placeholder.jpg';

if (!$userId) {
    header("Location: landing");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newUsername = trim($_POST['username'] ?? '');

    if (!empty($_FILES['pfp']['name'])) {
        $ext = pathinfo($_FILES['pfp']['name'], PATHINFO_EXTENSION);
        $newFileName = 'user_' . $userId . '.' . $ext;
        $uploadPath = __DIR__ . '/../images/user_profiles/' . $newFileName;

        if (move_uploaded_file($_FILES['pfp']['tmp_name'], $uploadPath)) {
            $pfpUrl = 'images/user_profiles/' . $newFileName;

            $stmt = $pdo->prepare("UPDATE users SET pfp_url = ? WHERE id = ?");
            $stmt->execute([$pfpUrl, $userId]);

            $_SESSION['pfp_url'] = $pfpUrl;
        }
    }

    if (!empty($newUsername)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->execute([$newUsername, $userId]);
        $_SESSION['username'] = $newUsername;
    }

    header("Location: /profile");
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/../components/nav.php'; ?>

<?php include __DIR__ . '/../components/authModal.php'; ?>

<section class="profile-container">
        <div class="profile-header">
            <div class="profile-top">
                <div class="profile-pic-container">
                    <img src="<?= $profilePic ?>" class="profile-avatar" id="profilePicPreview">
                    <div class="pfp-overlay"><p>Choose Picture</p></div>
                </div>

                <div class="profile-user-info">
                    <h2 id="usernameDisplay" style="color:#FF669C ;"><?= htmlspecialchars($username) ?></h2>
                    <button id="editProfileBtn">Edit Profile</button>
                </div>

                <form method="post" enctype="multipart/form-data" id="editProfileForm" style="display:none;">
                <input type="text" name="username" value="<?= htmlspecialchars($username) ?>">
                <input type="file" name="pfp" id="pfpInput" style="display:none;" accept="image/*">
                <button class="button-3" type="submit">Apply</button>
                <button class="button-3" type="button" id="cancelEdit">Cancel</button>
                </form>

                <button class="button-2" onclick="window.location.href='logout.php'"><img src="images/logout.png" alt="Lo2gout" class="icon-btn"></button>

            </div>




            <div class="profile-stats">
                <p><span>103</span>Liked Games</p>
                <p><span>5</span>Lists</p>
                <p><span>63</span>Games Saved</p>
            </div>
        </div>

        <div class="lists-header">
            <h2>My Lists</h2>
            <button class="create-list-btn"> Create a List</button>
        </div>

    <div id="lists"></div>
</section>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    </div>
    
<script>  


async function loadLists() {
  const res = await fetch('games.json')
  const games = await res.json()
  const container = document.getElementById('lists')

const titles = ["Favorites", "Haven't Finished", "Want to Play"]

  for (let i = 0; i < 3; i++) {
    const start = i * 6
    const end = start + 6
    const picks = games.slice(start, end)

    const block = document.createElement('div')
    block.className = 'list-block'


    const titleRow = document.createElement('div')
    titleRow.className = 'list-title-row'

const copyBtn = document.createElement('button')
copyBtn.textContent = 'Copy Link'
copyBtn.className = 'copy-link-btn'
copyBtn.onclick = () => {
    navigator.clipboard.writeText(window.location.href)
    alert('Link copied!')
}
    titleRow.appendChild(copyBtn)

    titleRow.innerHTML = `<h3>${titles[i]}</h3> <a href="lists.html?list=${i}" class="secondary-a">See Full List</a>`


    const row = document.createElement('div')
    row.className = 'list-row'

    for (const g of picks) {
      const img = document.createElement('img')
      img.src = g.main_image_url
      img.onclick = () => {
        location.href = `details.html?game=${encodeURIComponent(g.name)}`
      }
      row.appendChild(img)
    }

    block.appendChild(titleRow)
    block.appendChild(row)
    container.appendChild(block)
  }
}

loadLists()
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editBtn = document.getElementById('editProfileBtn');
    const editForm = document.getElementById('editProfileForm');
    const usernameDisplay = document.getElementById('usernameDisplay');
    const cancelBtn = document.getElementById('cancelEdit');
    const profilePicContainer = document.querySelector('.profile-pic-container');
    const profilePic = document.getElementById('profilePicPreview');
    const pfpInput = document.getElementById('pfpInput');

    if (editBtn && editForm) {
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

        profilePicContainer.onclick = () => {
            if (editForm.style.display === 'block') {
                pfpInput.click();
            }
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
});
</script>


<script src="../script.js"></script>

</body>
</html>
