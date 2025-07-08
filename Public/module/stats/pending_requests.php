<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo "Employee not logged in.";
    exit;
}

$pending_total = 0;
$pending_breakdown = [];

// Helper function
function add_request_count($count, $label) {
    return "$count $label";
}

// ✅ Leave Requests
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM leave_requests WHERE employee_id = ? GROUP BY status");
$stmt->execute([$employee_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (strtolower($row['status']) === 'pending') {
        $pending_total += $row['count'];
        $pending_breakdown[] = add_request_count($row['count'], "leave");
    }
}

// ✅ Schedule Change Requests
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM schedule_change_requests WHERE employee_id = ? GROUP BY status");
$stmt->execute([$employee_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (strtolower($row['status']) === 'pending') {
        $pending_total += $row['count'];
        $pending_breakdown[] = add_request_count($row['count'], "schedule change");
    }
}

// ✅ Overtime Requests
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM overtime_requests WHERE employee_id = ? GROUP BY status");
$stmt->execute([$employee_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (strtolower($row['status']) === 'pending') {
        $pending_total += $row['count'];
        $pending_breakdown[] = add_request_count($row['count'], "OT");
    }
}

// ✅ Time Adjustment Requests
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM time_adjustment_requests WHERE employee_id = ? GROUP BY status");
$stmt->execute([$employee_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (strtolower($row['status']) === 'pending') {
        $pending_total += $row['count'];
        $pending_breakdown[] = add_request_count($row['count'], "time adjustment");
    }
}

$pending_breakdown = implode(', ', $pending_breakdown);
?>

<!-- ✅ Summary UI Card -->
<div 
    class="card bg-white rounded-lg p-6 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-lg cursor-pointer mt-8">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">All Requests Summary</p>
            <h3 class="text-2xl font-bold mt-1"><?= htmlspecialchars($pending_total) ?></h3>
        </div>
        <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
            <i class="fas fa-clock text-yellow-700 text-xl"></i>
        </div>
    </div>
    <div class="mt-4">
        <p class="text-sm text-gray-600"><?= htmlspecialchars($pending_breakdown ?: 'No pending requests') ?></p>
    </div>
</div>
