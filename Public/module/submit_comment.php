<?php

session_start();
include('../config/db.php');
require_once __DIR__ . '/../config/demo_guard.php';
demo_block_mutation('Announcement comments are disabled in demo mode.');
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Get employee ID
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

// Validate input
$announcement_id = intval($_POST['announcement_id'] ?? 0);
$comment_content = trim($_POST['comment'] ?? '');

if (!$announcement_id || empty($comment_content)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Validate announcement exists
$stmt = $pdo->prepare("SELECT 1 FROM announcements WHERE announcement_id = ?");
$stmt->execute([$announcement_id]);
if (!$stmt->fetchColumn()) {
    echo json_encode(['success' => false, 'error' => 'Announcement not found']);
    exit;
}

try {
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO comments (announcement_id, employee_id, content, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$announcement_id, $employee_id, $comment_content]);
    
    // Get user info for response
    $stmt = $pdo->prepare("SELECT fname, lname FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $user = $stmt->fetch();
    
    $response = [
        'success' => true,
        'comment' => [
            'content' => htmlspecialchars($comment_content),
            'user_name' => htmlspecialchars($user['fname'] . ' ' . $user['lname']),
            'user_initial' => strtoupper(substr($user['fname'], 0, 1)),
            'time' => date('M j \a\t g:i A')
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
