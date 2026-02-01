<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}
try {
    $stmt = $pdo->prepare("SELECT title, main_image2_url FROM games WHERE title LIKE ? ORDER BY title LIMIT 8");
    $stmt->execute(["%$q%"]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (Exception $e) {
    echo json_encode([]);
}
