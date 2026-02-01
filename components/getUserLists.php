<?php
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get user's lists
$stmt = $pdo->prepare("SELECT id, name FROM lists WHERE user_id = ? ORDER BY name");
$stmt->execute([$userId]);
$lists = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'lists' => $lists
]);
