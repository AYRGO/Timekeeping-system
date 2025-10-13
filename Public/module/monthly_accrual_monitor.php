<?php
// File: monthly_accrual_monitor.php
// Simple monitoring page for monthly leave accrual
include('../config/db.php');

// Get current user (if logged in)
$current_user_id = $_SESSION['employee']['id'] ?? null;

// Calculate next accrual date
$currentDate = new DateTime();
$lastDayOfMonth = new DateTime($currentDate->format('Y-m-t'));
$daysUntilAccrual = $currentDate->diff($lastDayOfMonth)->days;
$nextAccrualDate = $lastDayOfMonth->format('F j, Y');

// Get recent accrual statistics
$accrualStats = [];
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT employee_id) as total_employees,
            COUNT(*) as total_records,
            MAX(updated_at) as last_processing
        FROM leave_credits 
        WHERE year = " . date('Y') . " 
        AND last_processed_month = " . date('n')
    );
    $accrualStats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $accrualStats = ['total_employees' => 0, 'total_records' => 0, 'last_processing' => null];
}

// Get user's leave credits if logged in
$userCredits = [];
if ($current_user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM leave_credits 
            WHERE employee_id = ? AND year = ? 
            ORDER BY leave_type
        ");
        $stmt->execute([$current_user_id, date('Y')]);
        $userCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $userCredits = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Leave Accrual Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- 1. Header with Countdown -->
    <div class="bg-white shadow-lg rounded-xl mb-8 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 flex items-center">
                    <i class="fas fa-calendar-check text-blue-600 text-xl mr-3"></i>
                    Monthly Leave Accrual Monitor
                </h1>
                <p class="text-slate-600 mt-1">Production system • Processes at end of each month</p>
            </div>
            <div class="bg-slate-800 text-white rounded-xl px-6 py-4 text-center">
                <p class="text-sm font-medium">Days Until Next Accrual</p>
                <p class="text-3xl font-bold"><?= $daysUntilAccrual ?></p>
                <p class="text-xs opacity-80"><?= $nextAccrualDate ?></p>
            </div>
        </div>
    </div>

    <!-- 2. Production Mode Status -->
    <div class="bg-gradient-to-r from-blue-600 to-slate-700 text-white rounded-xl p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-semibold text-lg">Production Mode Active</p>
                <p class="text-sm opacity-90">Sick Leave: +0.42 days • Vacation Leave: +1.25 days per month</p>
            </div>
            <div class="text-right">
                <p class="text-sm opacity-90">Current Month</p>
                <p class="font-semibold text-lg"><?= date('F Y') ?></p>
            </div>
        </div>
    </div>

    <!-- 3. Monthly Accrual Schedule -->
    <div class="bg-white shadow-lg rounded-xl p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center">
            <i class="fas fa-calendar-week text-blue-600 mr-3"></i>
            Monthly Accrual Schedule
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php
            for ($i = 0; $i < 6; $i++) {
                $month = date('n') + $i;
                $year = date('Y');
                
                if ($month > 12) {
                    $month -= 12;
                    $year++;
                }
                
                $lastDay = date('t', mktime(0, 0, 0, $month, 1, $year));
                $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
                $accrualDate = date('M j, Y', mktime(0, 0, 0, $month, $lastDay, $year));
                
                $isCurrent = ($month == date('n') && $year == date('Y'));
                $cardClass = $isCurrent ? 'bg-blue-50 border-blue-200' : 'bg-slate-50 border-slate-200';
            ?>
            <div class="<?= $cardClass ?> border rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-slate-800"><?= $monthName ?> <?= $year ?></h4>
                        <p class="text-sm text-slate-600"><?= $accrualDate ?></p>
                    </div>
                    <?php if ($isCurrent): ?>
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                            Current
                        </span>
                    <?php else: ?>
                        <i class="fas fa-calendar text-slate-400"></i>
                    <?php endif; ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<script>
// Auto-refresh page every 30 minutes
setTimeout(function() {
    location.reload();
}, 1800000); // 30 minutes
</script>

</body>
</html>