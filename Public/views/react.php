<?php
session_start();
include('../config/db.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$announcement_id = $input['announcement_id'] ?? null;
$emoji = $input['emoji'] ?? null;
$employee_id = $_SESSION['employee']['id'] ?? null;

// Validate inputs
if (!$announcement_id || !$emoji || !$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    // Start a transaction for safety
    $pdo->beginTransaction();

    // Check for existing reaction
    $stmt = $pdo->prepare("SELECT emoji FROM reactions WHERE announcement_id = ? AND employee_id = ?");
    $stmt->execute([$announcement_id, $employee_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['emoji'] === $emoji) {
            // Remove reaction if clicking the same one
            $pdo->prepare("DELETE FROM reactions WHERE announcement_id = ? AND employee_id = ?")
                ->execute([$announcement_id, $employee_id]);
        } else {
            // Update to new emoji
            $pdo->prepare("UPDATE reactions SET emoji = ?, reacted_at = NOW() WHERE announcement_id = ? AND employee_id = ?")
                ->execute([$emoji, $announcement_id, $employee_id]);
        }
    } else {
        // Add new reaction
        $pdo->prepare("INSERT INTO reactions (announcement_id, employee_id, emoji) VALUES (?, ?, ?)")
            ->execute([$announcement_id, $employee_id, $emoji]);
    }

    // Commit changes
    $pdo->commit();

    // Fetch updated reaction counts
    $reaction_stmt = $pdo->prepare("
        SELECT emoji, COUNT(*) as count
        FROM reactions
        WHERE announcement_id = ?
        GROUP BY emoji
    ");
    $reaction_stmt->execute([$announcement_id]);
    $updated_reactions = $reaction_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode(['success' => true, 'reactions' => $updated_reactions]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
