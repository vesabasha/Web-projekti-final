<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/UserRepository.php';

header('Content-Type: application/json');

$userRepo = new UserRepository($pdo);
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $emailOrUsername = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = $userRepo->getUserByEmailOrUsername($emailOrUsername);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? false;
            $response['success'] = true;
            $response['username'] = $user['username'];
            $response['is_admin'] = $user['is_admin'] ?? false;
        } else {
            $response['message'] = 'Invalid credentials. Please try again.';
        }
    } elseif ($action === 'signup') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $response['message'] = 'All fields are required.';
        } elseif ($password !== $confirm_password) {
            $response['message'] = 'Passwords do not match.';
        } elseif ($userRepo->getUserByEmailOrUsername($email)) {
            $response['message'] = 'Email already taken.';
        } elseif ($userRepo->getUserByEmailOrUsername($username)) {
            $response['message'] = 'Username already taken.';
        } elseif ($userRepo->createUser($username, $email, $password)) {
            $newUser = $userRepo->getUserByEmailOrUsername($username);
            $_SESSION['user_id'] = $newUser['id'];
            $_SESSION['username'] = $username;  
            $_SESSION['is_admin'] = $newUser['is_admin'] ?? false;
            $response['success'] = true;
            $response['username'] = $username;
            $response['is_admin'] = $newUser['is_admin'];
        } else {
            $response['message'] = 'Signup failed. Please try again.';
        }
    }
}

echo json_encode($response);
