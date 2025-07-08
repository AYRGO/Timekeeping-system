<?php
session_start();
require '../config/db.php'; // or wherever your PDO $pdo is

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Read JSON payload
$input = json_decode(file_get_contents('php://input'), true);
$field = $input['field'] ?? null;
$timestamp = $input['timestamp'] ?? null;
$reason = trim($input['reason'] ?? 'Auto OT entry');

// Validation
if (!$field || !$timestamp || !in_array($field, ['start_ot', 'end_ot'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$date = date('Y-m-d', $timestamp);
$time = date('H:i:s', $timestamp);

// Fetch existing request or create new
$stmt = $pdo->prepare("SELECT * FROM overtime_requests WHERE employee_id = ? AND date = ?");
$stmt->execute([$employee_id, $date]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // Update only the relevant field
    $updateStmt = $pdo->prepare("UPDATE overtime_requests SET {$field} = ? WHERE id = ?");
    $updateStmt->execute([$time, $existing['id']]);
} else {
    // Insert new
    $insertStmt = $pdo->prepare("INSERT INTO overtime_requests 
        (employee_id, date, $field, reason, status, created_at) 
        VALUES (?, ?, ?, ?, 'Pending', NOW())");
    $insertStmt->execute([$employee_id, $date, $time, $reason]);
}

echo json_encode(['success' => true]);
