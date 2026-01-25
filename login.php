<?php
//logins
session_start();
require_once 'UserRepository.php';      

$userRepo = new UserRepository($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$emailOrUsername = trim($_POST['email'] );
$password = $_POST['password'];
$user = $userRepo->getUserByEmailOrUsername($emailOrUsername);

if ($user && password_verify($password, $user['password'])) {
//i wanna try doing this myself ever
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
header("Location: profile.html");

exit();
} else {
    echo "Invalid credetials. Please try again.";

}
}
?>
