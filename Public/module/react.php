<?php
session_start();
include('../config/db.php');

$input = json_decode(file_get_contents('php://input'), true);
$announcement_id = $input['announcement_id'] ?? null;
$emoji = $input['emoji'] ?? null;
$employee_id = $_SESSION['employee']['id'] ?? null;

$response = ['success' => false];

if ($announcement_id && $emoji && $employee_id) {
    // Check if user already reacted
    $stmt = $pdo->prepare("SELECT emoji FROM reactions WHERE announcement_id = ? AND employee_id = ?");
    $stmt->execute([$announcement_id, $employee_id]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        if ($existing === $emoji) {
            // Unreact
            $stmt = $pdo->prepare("DELETE FROM reactions WHERE announcement_id = ? AND employee_id = ?");
            $stmt->execute([$announcement_id, $employee_id]);
            $user_reaction = null;
        } else {
            // Update to different emoji
            $stmt = $pdo->prepare("UPDATE reactions SET emoji = ? WHERE announcement_id = ? AND employee_id = ?");
            $stmt->execute([$emoji, $announcement_id, $employee_id]);
            $user_reaction = $emoji;
        }
    } else {
        // Insert new reaction
        $stmt = $pdo->prepare("INSERT INTO reactions (announcement_id, employee_id, emoji) VALUES (?, ?, ?)");
        $stmt->execute([$announcement_id, $employee_id, $emoji]);
        $user_reaction = $emoji;
    }

    // Get updated reactions
    $stmt = $pdo->prepare("SELECT emoji, COUNT(*) as count FROM reactions WHERE announcement_id = ? GROUP BY emoji");
    $stmt->execute([$announcement_id]);
    $reactions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $emoji_labels = ['👍' => 'Like', '❤️' => 'Love'];

    $response = [
        'success' => true,
        'reactions' => $reactions,
        'user_reaction' => $user_reaction,
        'label' => $user_reaction ? ($emoji_labels[$user_reaction] ?? '') : ''
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
