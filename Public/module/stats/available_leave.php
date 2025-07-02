<?php
$current_user_id = $_SESSION['employee']['id'] ?? null;
$today = date('Y-m-d');

// Default values
$leave_message = "No leave request submitted";
$leave_status = "none";
$leave_type = null;

// Fetch latest leave request
$leave_stmt = $pdo->prepare("
    SELECT start_date, end_date, status, leave_type 
    FROM leave_requests 
    WHERE employee_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$leave_stmt->execute([$current_user_id]);
$leave = $leave_stmt->fetch(PDO::FETCH_ASSOC);

if ($leave) {
    $leave_start = $leave['start_date'];
    $leave_end = $leave['end_date'];
    $leave_type = $leave['leave_type'];

    if ($leave['status'] === 'approved' && $leave_start <= $today && $today <= $leave_end) {
        $leave_message = "You're on leave today";
        $leave_status = "approved";
    } elseif ($leave['status'] === 'pending') {
        $leave_message = "Leave request pending approval";
        $leave_status = "pending";
    } else {
        $leave_message = "No active leave today";
        $leave_status = "default";
    }
}

// Color settings to match status
$icon_color = match ($leave_status) {
    'approved' => 'green',
    'pending' => 'yellow',
    'none', 'default' => 'gray',
    default => 'gray',
};
?>

<!-- Leave Status Card with Date Range and Leave Type -->
<div class="card bg-white rounded-lg p-6 shadow-sm">
    <div class="flex justify-between items-start">
        <div class="flex-1 pr-4">
            <p class="text-sm text-gray-500">Leave Status</p>
            <p class="text-2xl font-bold mt-1 text-green-800">
                <?= htmlspecialchars($leave_message) ?>
            </p>

            <?php if ($leave): ?>
                <p class="text-sm text-gray-500 mt-1">
                    <?= date('F j, Y', strtotime($leave['start_date'])) ?> 
                    to 
                    <?= date('F j, Y', strtotime($leave['end_date'])) ?>
                </p>

                <?php if ($leave_status === 'approved' && $leave_type): ?>
                    <p class="text-sm text-gray-600 italic mt-1">
                        <?= htmlspecialchars($leave_type) ?>  Leave
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
                    
       <div class="w-12 h-12 rounded-full bg-<?= $icon_color ?>-100 flex items-center justify-center">
            <i class="fas fa-umbrella-beach text-<?= $icon_color ?>-700 text-2xl"></i>
        </div>
    </div>
</div>
