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
$listName = $_POST['list_name'] ?? '';
$gameName = $_POST['game_name'] ?? '';

if (!$listName || !$gameName) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$listName = trim($listName);

if (strlen($listName) === 0) {
    echo json_encode(['success' => false, 'message' => 'List name cannot be empty']);
    exit;
}

if (strlen($listName) > 100) {
    echo json_encode(['success' => false, 'message' => 'List name is too long']);
    exit;
}

// Get game ID by name
$stmt = $pdo->prepare("SELECT id FROM games WHERE title = ?");
$stmt->execute([$gameName]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    echo json_encode(['success' => false, 'message' => 'Game not found']);
    exit;
}

$gameId = $game['id'];

try {
    // Create the list
    $stmt = $pdo->prepare("INSERT INTO lists (name, user_id) VALUES (?, ?)");
    $stmt->execute([$listName, $userId]);
    $listId = $pdo->lastInsertId();

    // Add game to list
    $stmt = $pdo->prepare("INSERT INTO list_games (list_id, game_id) VALUES (?, ?)");
    $stmt->execute([$listId, $gameId]);

    echo json_encode(['success' => true, 'message' => 'List created and game added']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating list']);
}
