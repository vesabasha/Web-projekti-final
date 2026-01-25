<?php
//signups
session_start();
require_once 'UserRepository.php';
$userRepo = new UserRepository($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation 
    if (empty($username) || empty($email) || empty($password)) {
        echo "All fields are required.";
        exit();
    }
    if (empty($username) || empty($email) || $password !== $_POST['confirmPassword']) {
        echo "invalid input or passwords arent the same";
        exit;
    }

    // Check if user already exists

    $existingUser = $userRepo->getUserByEmailOrUsername($email);
    if ($existingUser) {
        echo "Email already taken. Please choose another.";
        exit;
    }

    $existingUser = $userRepo->getUserByEmailOrUsername($username);
    if ($existingUser) {
        echo "Username already taken. Please choose another.";
        exit;
    }

    // Create user
    if ($userRepo->createUser($username, $email, $password)) {
        header("Location: profile.html");
        exit;
    } else {
        echo "Singup failed";
    }



?>