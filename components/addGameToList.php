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
$listId = $_POST['list_id'] ?? null;
$gameName = $_POST['game_name'] ?? '';

if (!$listId || !$gameName) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Verify the list belongs to the user
$stmt = $pdo->prepare("SELECT id FROM lists WHERE id = ? AND user_id = ?");
$stmt->execute([$listId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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

// Check if game is already in the list
$stmt = $pdo->prepare("SELECT 1 FROM list_games WHERE list_id = ? AND game_id = ?");
$stmt->execute([$listId, $gameId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Game is already in this list']);
    exit;
}

// Add game to list
try {
    $stmt = $pdo->prepare("INSERT INTO list_games (list_id, game_id) VALUES (?, ?)");
    $stmt->execute([$listId, $gameId]);
    echo json_encode(['success' => true, 'message' => 'Game added to list']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error adding game to list']);
}
