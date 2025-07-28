<?php
session_start();
include('../config/db.php');

header('Content-Type: application/json');

// Debug session data
error_log("Session data: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$announcement_id = (int)($input['announcement_id'] ?? 0);
$reaction_type = $input['reaction_type'] ?? '';

// Try multiple ways to get user ID
$employee_id = null;

// Check all possible session variables
if (isset($_SESSION['id'])) {
    $employee_id = $_SESSION['id'];
} elseif (isset($_SESSION['employee']['id'])) {
    $employee_id = $_SESSION['employee']['id'];
} elseif (isset($_SESSION['user_id'])) {
    $employee_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $employee_id = $_SESSION['admin_id'];
} elseif (isset($_SESSION['employee_id'])) {
    $employee_id = $_SESSION['employee_id'];
}

// Debug log
error_log("Announcement ID: $announcement_id");
error_log("Reaction type: $reaction_type");
error_log("Employee ID: $employee_id");

if (!$announcement_id || !$reaction_type || !$employee_id) {
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required data',
        'debug' => [
            'announcement_id' => $announcement_id,
            'reaction_type' => $reaction_type,
            'employee_id' => $employee_id,
            'all_session' => $_SESSION,
            'input_data' => $input
        ]
    ]);
    exit;
}

// Map emoji to reaction type
$reaction_map = [
    '👍' => 'like',
    '❤️' => 'love', 
    '😂' => 'laugh',
    '😮' => 'wow',
    '😢' => 'sad',
    '😡' => 'angry'
];

$type_to_emoji = array_flip($reaction_map);
$db_reaction_type = $reaction_map[$reaction_type] ?? null;

if (!$db_reaction_type) {
    echo json_encode(['success' => false, 'error' => 'Invalid reaction type: ' . $reaction_type]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if user already reacted to this post
    $check_stmt = $pdo->prepare("SELECT reaction_type FROM post_reactions WHERE announcement_id = ? AND employee_id = ?");
    $check_stmt->execute([$announcement_id, $employee_id]);
    $existing_reaction = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_reaction) {
        if ($existing_reaction['reaction_type'] === $db_reaction_type) {
            // Same reaction - remove it (toggle off)
            $delete_stmt = $pdo->prepare("DELETE FROM post_reactions WHERE announcement_id = ? AND employee_id = ?");
            $delete_stmt->execute([$announcement_id, $employee_id]);
            $user_reaction = null;
        } else {
            // Different reaction - update it
            $update_stmt = $pdo->prepare("UPDATE post_reactions SET reaction_type = ?, updated_at = CURRENT_TIMESTAMP WHERE announcement_id = ? AND employee_id = ?");
            $update_stmt->execute([$db_reaction_type, $announcement_id, $employee_id]);
            $user_reaction = $reaction_type;
        }
    } else {
        // No existing reaction - insert new one
        $insert_stmt = $pdo->prepare("INSERT INTO post_reactions (announcement_id, employee_id, reaction_type) VALUES (?, ?, ?)");
        $insert_stmt->execute([$announcement_id, $employee_id, $db_reaction_type]);
        $user_reaction = $reaction_type;
    }

    // Get updated reaction counts
    $count_stmt = $pdo->prepare("
        SELECT reaction_type, COUNT(*) as count 
        FROM post_reactions 
        WHERE announcement_id = ? 
        GROUP BY reaction_type
    ");
    $count_stmt->execute([$announcement_id]);
    $reaction_counts = $count_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert to emoji format for frontend
    $reactions = [];
    foreach ($reaction_counts as $row) {
        $emoji = $type_to_emoji[$row['reaction_type']] ?? '👍';
        $reactions[$emoji] = (int)$row['count'];
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'user_reaction' => $user_reaction,
        'reactions' => $reactions
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>