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

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$userId = $_SESSION['employee']['id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

try {
    // Get all unread announcements
    $stmt = $pdo->prepare("
        SELECT a.announcement_id 
        FROM announcements a
        LEFT JOIN user_read_announcements ura ON a.announcement_id = ura.announcement_id AND ura.user_id = ?
        WHERE a.deleted = 0 AND ura.announcement_id IS NULL
    ");
    $stmt->execute([$userId]);
    $unreadAnnouncements = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Mark all as read
    foreach ($unreadAnnouncements as $announcementId) {
        $insertStmt = $pdo->prepare("
            INSERT IGNORE INTO user_read_announcements (user_id, announcement_id, read_at)
            VALUES (?, ?, NOW())
        ");
        $insertStmt->execute([$userId, $announcementId]);
    }
    
    echo json_encode(['success' => true, 'marked_count' => count($unreadAnnouncements)]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
