<?php
session_start();
include('../config/db.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$leaveType = $_POST['leaveType'] ?? '';
$leaveDates = $_POST['leaveDates'] ?? '';
$reason = $_POST['reason'] ?? '';

$leaveTables = [
    "sick" => "sick_leaves",
    "vacation" => "vacation_leaves",
    "paternity" => "paternity_leaves",
    "maternity" => "maternity_leaves",
    "solo_parent" => "solo_parent_leaves",
    "halfday" => "halfday_leaves",
    "halfday_sick" => "halfday_sick_leaves"
];

$leaveNames = [
    "sick" => "Sick Leave",
    "vacation" => "Vacation Leave",
    "paternity" => "Paternity Leave",
    "maternity" => "Maternity Leave",
    "solo_parent" => "Solo Parent Leave",
    "halfday" => "Halfday Leave",
    "halfday_sick" => "Halfday Sick Leave"
];

if (!array_key_exists($leaveType, $leaveTables)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid leave type']);
    exit;
}

$dates = explode(" to ", $leaveDates);
if (count($dates) !== 2) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid date range']);
    exit;
}

$start_date = trim($dates[0]);
$end_date = trim($dates[1]);
$today = date("Y-m-d");
$table = $leaveTables[$leaveType];
$leaveName = $leaveNames[$leaveType];

try {
    // Check for existing leave request today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE employee_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$employee_id, $today]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo json_encode(['status' => 'error', 'message' => "You have already submitted a $leaveName request today."]);
        exit;
    }

    // Insert new leave request
    $stmt = $pdo->prepare("INSERT INTO $table (employee_id, date_start, date_end, reason) VALUES (?, ?, ?, ?)");
    $stmt->execute([$employee_id, $start_date, $end_date, $reason]);

    echo json_encode(['status' => 'success', 'message' => "$leaveName submitted successfully."]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
