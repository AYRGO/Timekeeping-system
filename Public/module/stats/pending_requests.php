<?php
require_once '../config/db.php';

$pending_total = 0;
$pending_breakdown = [];

// ✅ LEAVE REQUESTS FOR CURRENT USER
$leaveStmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM leave_requests WHERE employee_id = ? GROUP BY status");
$leaveStmt->execute([$employee_id]);

while ($row = $leaveStmt->fetch(PDO::FETCH_ASSOC)) {
    $status = strtolower($row['status']);
    $count = $row['count'];

    if ($status === 'pending') {
        $pending_total += $count;
    }

    $pending_breakdown[] = "$count leave " . $status . ($count > 1 ? "s" : "");
}

// ✅ SCHEDULE CHANGE REQUESTS FOR CURRENT USER
$schedStmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM schedule_change_requests WHERE employee_id = ? GROUP BY status");
$schedStmt->execute([$employee_id]);

while ($row = $schedStmt->fetch(PDO::FETCH_ASSOC)) {
    $status = strtolower($row['status']);
    $count = $row['count'];

    if ($status === 'pending') {
        $pending_total += $count;
    }

    $pending_breakdown[] = "$count schedule " . $status . ($count > 1 ? "s" : "");
}

// ✅ Combine breakdown into a readable string
$pending_breakdown = implode(', ', $pending_breakdown);
?>


<!-- HTML Card Display -->
<div class="card bg-white rounded-lg p-6 shadow-sm">
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
        <p class="text-sm text-gray-600"><?= htmlspecialchars($pending_breakdown ?: 'No requests') ?></p>
    </div>
</div>
