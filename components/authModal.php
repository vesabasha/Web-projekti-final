<?php
session_start();
require_once __DIR__ . '/../UserRepository.php';
$userRepo = new UserRepository($pdo);

$mode = $_GET['mode'] ?? 'login';
$loginError = '';
$signupError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $emailOrUsername = trim($_POST['email']);
        $password = $_POST['password'];
        $user = $userRepo->getUserByEmailOrUsername($emailOrUsername);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: /profile");
            exit();
        } else {
            $loginError = "Invalid credentials. Please try again.";
            $mode = 'login';
        }
    }

    if (isset($_POST['signup'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($username) || empty($email) || empty($password) || $password !== $confirm_password) {
            $signupError = "Invalid input or passwords do not match.";
            $mode = 'signup';
        } else {
            if ($userRepo->getUserByEmailOrUsername($email)) {
                $signupError = "Email already taken.";
                $mode = 'signup';
            } elseif ($userRepo->getUserByEmailOrUsername($username)) {
                $signupError = "Username already taken.";
                $mode = 'signup';
            } elseif ($userRepo->createUser($username, $email, $password)) {
                $_SESSION['username'] = $username;
                header("Location: /profile");
                exit();
            } else {
                $signupError = "Signup failed. Please try again.";
                $mode = 'signup';
            }
        }
    }
}
?>

<div id="authModal" class="modal hidden">
    <div class="modal-content">
        <button class="modal-close">&times;</button>

        <!-- Login Form -->
        <div id="loginFormWrapper" style="display: <?= $mode === 'login' ? 'block' : 'none' ?>;">
            <h2>Log in</h2>
            <button class="google-btn">
                <img src="images/googleicon.png" alt="Google Icon"> Continue with Google
            </button>
            <p class="divider-text">or</p>
            <form class="modal-form" method="post" action="">
                <label>Email<input type="email" name="email" placeholder="Enter your email or username" required></label>
                <label>Password<input type="password" name="password" placeholder="Enter your password" required></label>
                <button type="submit" name="login">Log in</button>
            </form>
            <?php if ($loginError): ?><p style="color:red;text-align:center;"><?= $loginError ?></p><?php endif; ?>
            <p style="text-align:center;">
                No account? <button id="swapToSignup" style="background:none;border:none;color:#44A1A0;text-decoration:underline;cursor:pointer;">Create one</button>
            </p>
        </div>

        <!-- Signup Form -->
        <div id="signupFormWrapper" style="display: <?= $mode === 'signup' ? 'block' : 'none' ?>;">
            <h2>Sign Up</h2>
            <button class="google-btn">
                <img src="images/googleicon.png" alt="Google Icon"> Continue with Google
            </button>
            <p class="divider-text">or</p>
            <form class="modal-form" method="post" action="">
                <label>Username<input type="text" name="username" placeholder="Choose a username" required></label>
                <label>Email<input type="email" name="email" placeholder="Enter your email" required></label>
                <label>Password<input type="password" name="password" placeholder="Create a password" required></label>
                <label>Confirm Password<input type="password" name="confirm_password" placeholder="Confirm your password" required></label>
                <button type="submit" name="signup">Create Account</button>
            </form>
            <?php if ($signupError): ?><p style="color:red;text-align:center;"><?= $signupError ?></p><?php endif; ?>
            <p style="text-align:center;">
                Already a member? <button id="swapToLogin" style="background:none;border:none;color:#44A1A0;text-decoration:underline;cursor:pointer;">Log in!</button>
            </p>
        </div>
    </div>
</div>

<script>
const authModal = document.getElementById('authModal');
const loginWrapper = document.getElementById('loginFormWrapper');
const signupWrapper = document.getElementById('signupFormWrapper');

document.querySelectorAll('#swapToSignup').forEach(btn => btn.onclick = () => {
    loginWrapper.style.display = 'none';
    signupWrapper.style.display = 'block';
});
document.querySelectorAll('#swapToLogin').forEach(btn => btn.onclick = () => {
    signupWrapper.style.display = 'none';
    loginWrapper.style.display = 'block';
});
document.querySelector('.modal-close').onclick = () => authModal.classList.add('hidden');
</script>
