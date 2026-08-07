<?php
session_start();
include('../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$announcementId = $input['announcement_id'] ?? null;

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$userId = $_SESSION['employee']['id'] ?? null;
if (!$userId || !$announcementId) {
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO user_read_announcements (user_id, announcement_id, read_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$userId, $announcementId]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
