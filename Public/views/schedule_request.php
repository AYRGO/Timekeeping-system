<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Check which view to display (current requests, monthly, history, or switch)
$view = isset($_GET['view']) ? $_GET['view'] : 'current';
$isHistoryView = ($view === 'history');
$isMonthlyView = ($view === 'monthly');
$isSwitchView = ($view === 'switch');

$pageTitle = $isMonthlyView ? 'Monthly Schedule Requests' : ($isSwitchView ? 'Schedule Switch Requests' : ($isHistoryView ? 'Schedule Changes & Day Off History' : 'Schedule Change & Day Off Requests'));

// Fetch schedule change requests with employee names, attachments, and current schedule
if ($isMonthlyView) {
    // Fetch from month_weekly_schedule table (monthly requests)
    $stmt = $pdo->query("
        SELECT mws.id, mws.reason, mws.status, mws.year, mws.month, mws.created_at,
               mws.sunday_schedule_id, mws.sunday_is_rest_day,
               mws.monday_schedule_id, mws.monday_is_rest_day,
               mws.tuesday_schedule_id, mws.tuesday_is_rest_day,
               mws.wednesday_schedule_id, mws.wednesday_is_rest_day,
               mws.thursday_schedule_id, mws.thursday_is_rest_day,
               mws.friday_schedule_id, mws.friday_is_rest_day,
               mws.saturday_schedule_id, mws.saturday_is_rest_day,
               mws.attachment_path, mws.processed_at, mws.processed_by, mws.admin_notes,
               mws.employee_id,
               e.fname, e.lname
        FROM month_weekly_schedule mws
        JOIN employees e ON mws.employee_id = e.id
        ORDER BY mws.created_at DESC
    ");
    $monthly_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else if ($isSwitchView) {
    // Fetch from schedule_switch_requests table (switch requests)
    $stmt = $pdo->query("
        SELECT ssr.id, ssr.employee_id, ssr.source_date, ssr.target_date, 
               ssr.reason, ssr.attachment_path, ssr.status, ssr.created_at,
               ssr.processed_at, ssr.processed_by, ssr.admin_notes,
               e.fname, e.lname
        FROM schedule_switch_requests ssr
        JOIN employees e ON ssr.employee_id = e.id
        ORDER BY ssr.created_at DESC
    ");
    $switch_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else if ($isHistoryView) {
    // Fetch from post_schedule_change_requests table (history)
    $stmt = $pdo->query("
        SELECT psr.id, psr.reason, psr.status, psr.start_date, psr.end_date, psr.created_at,
               psr.work_schedule_id, psr.current_work_schedule_id, psr.attachment_scr, psr.explanation,
               psr.created_at as approved_at, psr.employee_id, psr.is_rest_day,
               e.fname, e.lname,
               ws.time_in, ws.time_out
        FROM post_schedule_change_requests psr
        JOIN employees e ON psr.employee_id = e.id
        LEFT JOIN work_schedules ws ON psr.work_schedule_id = ws.id
        ORDER BY psr.created_at DESC
    ");
} else {
    // Fetch from schedule_change_requests table (current requests)
    $stmt = $pdo->query("
        SELECT sr.id, sr.reason, sr.status, sr.start_date, sr.end_date, sr.created_at,
               sr.work_schedule_id, sr.current_work_schedule_id, sr.attachment_scr, sr.explanation,
               sr.employee_id, sr.is_rest_day,
               e.fname, e.lname,
               ws.time_in, ws.time_out
        FROM schedule_change_requests sr
        JOIN employees e ON sr.employee_id = e.id
        LEFT JOIN work_schedules ws ON sr.work_schedule_id = ws.id
        WHERE sr.status NOT IN ('Declined', 'Rejected', 'Approved')
        ORDER BY sr.created_at DESC
    ");
}
$schedule_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending counts for each tab
$pendingCurrentCount = $pdo->query("
    SELECT COUNT(*) FROM schedule_change_requests 
    WHERE status NOT IN ('Declined', 'Rejected', 'Approved')
")->fetchColumn();

$pendingMonthlyCount = $pdo->query("
    SELECT COUNT(*) FROM month_weekly_schedule 
    WHERE LOWER(status) = 'pending'
")->fetchColumn();

$pendingSwitchCount = $pdo->query("
    SELECT COUNT(*) FROM schedule_switch_requests 
    WHERE LOWER(status) = 'pending'
")->fetchColumn();

// Function to get schedule time display
function getScheduleTime($schedule_id) {
    switch ((int)$schedule_id) {
        case 1: return '6:30 AM – 3:30 PM';
        case 2: return '8:00 AM – 7:00 PM';
        case 3: return '7:30 AM – 4:30 PM';
        case 4: return '7:00 AM – 4:00 PM';
        case 5: return '8:00 AM – 5:00 PM';
        case 6: return '9:00 AM – 6:00 PM';
        case 7: return '10:00 AM – 7:00 PM';
        case 8: return '6:00 AM – 3:00 PM';
        case 9: return '8:00 AM – 4:30 PM';
        case 10: return '7:40 AM – 4:40 PM';
        case 11: return '6:30 AM – 3:30 PM';
        case 12: return '6:30 AM – 5:30 PM';
        case 13: return '7:00 AM – 6:00 PM';
        case 14: return '6:00 AM – 5:00 PM';
        case 15: return '6:00 AM – 4:00 PM';
        case 16: return '8:30 AM – 4:30 PM';
        case 17: return '6:00 AM – 12:00 PM';
        case 18: return '6:00 AM – 2:30 PM';
        default: return 'N/A';
    }
}

// Helper: Get current schedule for an employee from the cache (what calendar displays)
function getCurrentScheduleForEmployee($employee_id, $pdo, $date = null) {
    if (!$date) $date = date('Y-m-d');
    
    // PRIORITY 1: Check employee_daily_schedule_cache (what the calendar displays)
    $stmt = $pdo->prepare("
        SELECT work_schedule_id, is_rest_day, schedule_name, time_in, time_out
        FROM employee_daily_schedule_cache 
        WHERE employee_id = ? AND schedule_date = ? 
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $date]);
    $cachedSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cachedSchedule) {
        if ($cachedSchedule['is_rest_day']) {
            return [
                'id' => null,
                'display' => 'OFF'
            ];
        }
        if ($cachedSchedule['time_in'] && $cachedSchedule['time_out']) {
            return [
                'id' => $cachedSchedule['work_schedule_id'],
                'display' => date('g:i A', strtotime($cachedSchedule['time_in'])) . ' – ' . date('g:i A', strtotime($cachedSchedule['time_out']))
            ];
        }
    }
    
    // FALLBACK: Check employee_default_schedules (weekly default)
    $dayOfWeek = date('w', strtotime($date));
    $stmt = $pdo->prepare("
        SELECT eds.work_schedule_id, eds.is_rest_day, ws.time_in, ws.time_out
        FROM employee_default_schedules eds
        LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
        WHERE eds.employee_id = ? AND eds.day_of_week = ?
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $dayOfWeek]);
    $weeklySchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($weeklySchedule) {
        if ($weeklySchedule['is_rest_day']) {
            return [
                'id' => null,
                'display' => 'OFF'
            ];
        }
        if ($weeklySchedule['time_in'] && $weeklySchedule['time_out']) {
            return [
                'id' => $weeklySchedule['work_schedule_id'],
                'display' => date('g:i A', strtotime($weeklySchedule['time_in'])) . ' – ' . date('g:i A', strtotime($weeklySchedule['time_out']))
            ];
        }
    }
    
    // LAST RESORT: Fall back to employee's official schedule
    $empStmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
    $empStmt->execute([$employee_id]);
    $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
    $officialSchedId = $emp['official_sched'] ?? 4;
    
    $schedStmt = $pdo->prepare("SELECT time_in, time_out FROM work_schedules WHERE id = ?");
    $schedStmt->execute([$officialSchedId]);
    $sched = $schedStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sched) {
        return [
            'id' => $officialSchedId,
            'display' => date('g:i A', strtotime($sched['time_in'])) . ' – ' . date('g:i A', strtotime($sched['time_out']))
        ];
    }
    
    // Ultimate fallback
    return [
        'id' => 4,
        'display' => '7:00 AM – 4:00 PM'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title><?= $pageTitle ?></title>
</head>
<body class="bg-gray-100">

 <div class="flex h-screen">
        <?php include('sidebar.php'); ?>

<div class="flex-1 flex flex-col">
            <?php include('header.php'); ?>

            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Display messages -->
                <?php if (isset($_GET['message'])): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars(urldecode($_GET['message'])) ?></span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none'"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars(urldecode($_GET['error'])) ?></span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none'"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>

                        </div>
                        <div class="flex space-x-2">
                            <a href="?view=current" 
                               class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= !$isHistoryView && !$isMonthlyView && !$isSwitchView ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                <i class="fas fa-clock mr-2"></i>Current Requests
                                <?php if ($pendingCurrentCount > 0): ?>
                                    <span class="ml-2 px-2 py-0.5 text-xs font-bold rounded-full <?= !$isHistoryView && !$isMonthlyView && !$isSwitchView ? 'bg-white text-blue-600' : 'bg-blue-600 text-white' ?>">
                                        <?= $pendingCurrentCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <a href="?view=monthly" 
                               class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $isMonthlyView ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                <i class="fas fa-calendar-alt mr-2"></i>Monthly Schedule
                                <?php if ($pendingMonthlyCount > 0): ?>
                                    <span class="ml-2 px-2 py-0.5 text-xs font-bold rounded-full <?= $isMonthlyView ? 'bg-white text-purple-600' : 'bg-purple-600 text-white' ?>">
                                        <?= $pendingMonthlyCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <a href="?view=switch" 
                               class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $isSwitchView ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                <i class="fas fa-exchange-alt mr-2"></i>Switch Requests
                                <?php if ($pendingSwitchCount > 0): ?>
                                    <span class="ml-2 px-2 py-0.5 text-xs font-bold rounded-full <?= $isSwitchView ? 'bg-white text-purple-600' : 'bg-purple-600 text-white' ?>">
                                        <?= $pendingSwitchCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <a href="?view=history" 
                               class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $isHistoryView ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                <i class="fas fa-history mr-2"></i>History
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($isMonthlyView): ?>
                <!-- Monthly Schedule Requests Table -->
                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month/Year</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weekly Schedule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Effective Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($monthly_requests)): ?>
                                <?php foreach ($monthly_requests as $mr): ?>
                                    <?php
                                    // Calculate effective date range (from today or start of month to end of month)
                                    $today = date('Y-m-d');
                                    $firstDayOfMonth = date('Y-m-d', strtotime("{$mr['year']}-{$mr['month']}-01"));
                                    $lastDayOfMonth = date('Y-m-t', strtotime("{$mr['year']}-{$mr['month']}-01"));
                                    
                                    if (strtotime($firstDayOfMonth) > strtotime($today)) {
                                        $effectiveStart = $firstDayOfMonth;
                                    } else if (strtotime($lastDayOfMonth) < strtotime($today)) {
                                        $effectiveStart = 'Past month';
                                        $effectiveEnd = '';
                                    } else {
                                        $effectiveStart = $today;
                                    }
                                    $effectiveEnd = $lastDayOfMonth;
                                    
                                    // Get schedule names helper function
                                    if (!function_exists('getScheduleName')) {
                                        function getScheduleName($schedId, $isRestDay, $pdo) {
                                            if ($isRestDay) {
                                                return '<span class="text-red-600 font-medium"><i class="fas fa-bed mr-1"></i>Rest Day</span>';
                                            }
                                            if (!$schedId) {
                                                return '<span class="text-gray-400 italic">Not set</span>';
                                            }
                                            $stmt = $pdo->prepare("SELECT time_in, time_out FROM work_schedules WHERE id = ?");
                                            $stmt->execute([$schedId]);
                                            $sched = $stmt->fetch(PDO::FETCH_ASSOC);
                                            if ($sched) {
                                                return '<span class="text-green-600 font-medium">' . 
                                                       date('g:i A', strtotime($sched['time_in'])) . ' - ' . 
                                                       date('g:i A', strtotime($sched['time_out'])) . '</span>';
                                            }
                                            return '<span class="text-gray-500">Schedule ' . $schedId . '</span>';
                                        }
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-purple-600 font-medium text-sm">
                                                        <?= strtoupper(substr($mr['fname'], 0, 1) . substr($mr['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($mr['fname'] . ' ' . $mr['lname']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-semibold text-lg text-purple-600">
                                                    <?= date('F Y', strtotime("{$mr['year']}-{$mr['month']}-01")) ?>
                                                </span>
                                                <span class="text-xs text-gray-500"><?= $mr['year'] ?>-<?= str_pad($mr['month'], 2, '0', STR_PAD_LEFT) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <div class="grid grid-cols-2 gap-x-6">
                                                <!-- Left Column: Sun, Mon, Tue -->
                                                <div class="space-y-2">
                                                    <!-- Sunday -->
                                                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                                                        <i class="fas fa-sun text-yellow-500 w-4"></i>
                                                        <span class="font-medium text-gray-600 w-12">Sun:</span>
                                                        <span class="flex-1"><?= getScheduleName($mr['sunday_schedule_id'], $mr['sunday_is_rest_day'], $pdo) ?></span>
                                                    </div>
                                                    <!-- Monday -->
                                                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                                                        <i class="fas fa-briefcase text-blue-500 w-4"></i>
                                                        <span class="font-medium text-gray-600 w-12">Mon:</span>
                                                        <span class="flex-1"><?= getScheduleName($mr['monday_schedule_id'], $mr['monday_is_rest_day'], $pdo) ?></span>
                                                    </div>
                                                    <!-- Tuesday -->
                                                    <div class="flex items-center gap-2">
                                                        <i class="fas fa-briefcase text-blue-500 w-4"></i>
                                                        <span class="font-medium text-gray-600 w-12">Tue:</span>
                                                        <span class="flex-1"><?= getScheduleName($mr['tuesday_schedule_id'], $mr['tuesday_is_rest_day'], $pdo) ?></span>
                                                    </div>
                                                </div>
                                                <!-- Right Column: Wed, Thu, Fri, Sat -->
                                                <div class="space-y-2">
                                                    <!-- Wednesday -->
                                                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                                                        <i class="fas fa-briefcase text-blue-500 w-4"></i>
                                                        <span class="font-medium text-gray-600 w-12">Wed:</span>
                                                        <span class="flex-1"><?= getScheduleName($mr['wednesday_schedule_id'], $mr['wednesday_is_rest_day'], $pdo) ?></span>
                                                    </div>
                                                    <!-- Thursday -->
                                                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                                                        <i class="fas fa-briefcase text-blue-500 w-4"></i>
                                                        <span class="font-medium text-gray-600 w-12">Thu:</span>
                                                        <span class="flex-1"><?= getScheduleName($mr['thursday_schedule_id'], $mr['thursday_is_rest_day'], $pdo) ?></span>
                                                    </div>
                                                    <!-- Friday -->
                                                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                                                        <i class="fas fa-briefcase text-blue-500 w-4"></i>
                                                        <span class="font-medium text-gray-600 w-12">Fri:</span>
                                                        <span class="flex-1"><?= getScheduleName($mr['friday_schedule_id'], $mr['friday_is_rest_day'], $pdo) ?></span>
                                                    </div>
                                                    <!-- Saturday -->
                                                    <div class="flex items-center gap-2">
                                                        <i class="fas fa-moon text-indigo-500 w-4"></i>
                                                        <span class="font-medium text-gray-600 w-12">Sat:</span>
                                                        <span class="flex-1"><?= getScheduleName($mr['saturday_schedule_id'], $mr['saturday_is_rest_day'], $pdo) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if ($effectiveStart === 'Past month'): ?>
                                                <span class="text-gray-500 italic">Past month</span>
                                            <?php else: ?>
                                                <div class="flex flex-col">
                                                    <span class="font-medium">From: <?= date('M d, Y', strtotime($effectiveStart)) ?></span>
                                                    <span class="font-medium">To: <?= date('M d, Y', strtotime($effectiveEnd)) ?></span>
                                                    <span class="text-xs text-gray-500 mt-1">
                                                        <?php
                                                        $start = new DateTime($effectiveStart);
                                                        $end = new DateTime($effectiveEnd);
                                                        $days = $start->diff($end)->days + 1;
                                                        echo "($days day" . ($days > 1 ? 's' : '') . ")";
                                                        ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs text-sm text-gray-900">
                                            <div class="truncate hover:whitespace-normal cursor-help" title="<?= htmlspecialchars($mr['reason']) ?>">
                                                <?= htmlspecialchars($mr['reason']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php if (!empty($mr['attachment_path'])): ?>
                                                <a href="<?= htmlspecialchars($mr['attachment_path']) ?>" target="_blank" 
                                                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php
                                            $status = strtolower($mr['status']);
                                            $statusClass = match($status) {
                                                'approved' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'rejected', 'cancelled' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $statusClass ?>">
                                                <?= htmlspecialchars(ucfirst($mr['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div class="flex flex-col">
                                                <span><?= date('M d, Y', strtotime($mr['created_at'])) ?></span>
                                                <span class="text-xs"><?= date('g:i A', strtotime($mr['created_at'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if (strtolower($mr['status']) === 'pending'): ?>
                                                <div class="flex space-x-2">
                                                    <form method="post" action="process_monthly_schedule_action.php" class="inline-block">
                                                        <input type="hidden" name="request_id" value="<?= $mr['id'] ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" 
                                                                onclick="return confirm('Are you sure you want to approve this monthly schedule request? This will apply from present day to end of month.')"
                                                                class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm transition-colors">
                                                            <i class="fas fa-check mr-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            onclick="openDeclineMonthlyModal(<?= $mr['id'] ?>)"
                                                            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm transition-colors">
                                                        <i class="fas fa-times mr-1"></i>Decline
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-500 italic">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    <?= $mr['processed_at'] ? 'Processed ' . date('M d', strtotime($mr['processed_at'])) : 'Done' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-sm py-8 text-gray-500">
                                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-2"></i>
                                        <div>No monthly schedule requests found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Monthly Summary Cards -->
                <?php if (!empty($monthly_requests)): ?>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php
                    $monthlySummary = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
                    foreach ($monthly_requests as $req) {
                        $status = strtolower($req['status']);
                        if (isset($monthlySummary[$status])) {
                            $monthlySummary[$status]++;
                        }
                    }
                    ?>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-list text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Monthly</p>
                                <p class="text-lg font-semibold text-gray-900"><?= count($monthly_requests) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $monthlySummary['pending'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $monthlySummary['approved'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <i class="fas fa-times text-red-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Rejected</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $monthlySummary['rejected'] + $monthlySummary['cancelled'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php elseif ($isSwitchView): ?>
                <!-- Schedule Switch Requests Table -->
                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date A (Source)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule A</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date B (Target)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule B</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($switch_requests)): ?>
                                <?php foreach ($switch_requests as $swr): ?>
                                    <?php
                                    // Get schedules for both dates
                                    $scheduleA = getCurrentScheduleForEmployee($swr['employee_id'], $pdo, $swr['source_date']);
                                    $scheduleB = getCurrentScheduleForEmployee($swr['employee_id'], $pdo, $swr['target_date']);
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-purple-600 font-medium text-sm">
                                                        <?= strtoupper(substr($swr['fname'], 0, 1) . substr($swr['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($swr['fname'] . ' ' . $swr['lname']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-purple-600"><?= date('M d, Y', strtotime($swr['source_date'])) ?></span>
                                                <span class="text-xs text-gray-500"><?= date('l', strtotime($swr['source_date'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="bg-purple-50 px-3 py-2 rounded-md">
                                                <span class="font-medium text-purple-700"><?= $scheduleA['display'] ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-indigo-600"><?= date('M d, Y', strtotime($swr['target_date'])) ?></span>
                                                <span class="text-xs text-gray-500"><?= date('l', strtotime($swr['target_date'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="bg-indigo-50 px-3 py-2 rounded-md">
                                                <span class="font-medium text-indigo-700"><?= $scheduleB['display'] ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs text-sm text-gray-900">
                                            <div class="truncate hover:whitespace-normal cursor-help" title="<?= htmlspecialchars($swr['reason']) ?>">
                                                <?= htmlspecialchars($swr['reason']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php if (!empty($swr['attachment_path'])): ?>
                                                <a href="<?= htmlspecialchars($swr['attachment_path']) ?>" target="_blank" 
                                                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php
                                            $status = strtolower($swr['status']);
                                            $statusClass = match($status) {
                                                'approved' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'rejected', 'cancelled' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $statusClass ?>">
                                                <?= htmlspecialchars(ucfirst($swr['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div class="flex flex-col">
                                                <span><?= date('M d, Y', strtotime($swr['created_at'])) ?></span>
                                                <span class="text-xs"><?= date('g:i A', strtotime($swr['created_at'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if (strtolower($swr['status']) === 'pending'): ?>
                                                <div class="flex space-x-2">
                                                    <form method="post" action="process_switch_action.php" class="inline-block">
                                                        <input type="hidden" name="request_id" value="<?= $swr['id'] ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" 
                                                                onclick="return confirm('Are you sure you want to approve this schedule switch? This will swap the schedules for both dates.')"
                                                                class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm transition-colors">
                                                            <i class="fas fa-check mr-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            onclick="openDeclineSwitchModal(<?= $swr['id'] ?>)"
                                                            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm transition-colors">
                                                        <i class="fas fa-times mr-1"></i>Decline
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-500 italic">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    <?= $swr['processed_at'] ? 'Processed ' . date('M d', strtotime($swr['processed_at'])) : 'Done' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-sm py-8 text-gray-500">
                                        <i class="fas fa-exchange-alt text-4xl text-gray-300 mb-2"></i>
                                        <div>No schedule switch requests found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Switch Summary Cards -->
                <?php if (!empty($switch_requests)): ?>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php
                    $switchSummary = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
                    foreach ($switch_requests as $req) {
                        $status = strtolower($req['status']);
                        if (isset($switchSummary[$status])) {
                            $switchSummary[$status]++;
                        }
                    }
                    ?>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-list text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Switch</p>
                                <p class="text-lg font-semibold text-gray-900"><?= count($switch_requests) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $switchSummary['pending'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $switchSummary['approved'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <i class="fas fa-times text-red-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Rejected</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $switchSummary['rejected'] + $switchSummary['cancelled'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- Regular/History Schedule Requests Table -->
                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Schedule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested Schedule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?= $isHistoryView ? 'Processed' : 'Submitted' ?>
                                </th>
                                <?php if (!$isHistoryView): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($schedule_requests)): ?>
                                <?php foreach ($schedule_requests as $sr): ?>
                                    <?php
                                    // Check if this is a rest day request
                                    $isRestDay = (empty($sr['work_schedule_id']) || ($sr['is_rest_day'] ?? 0) == 1);
                                    
                                    // Get requested schedule time
                                    if ($isRestDay) {
                                        $requested_shift = 'Day Off';
                                    } else {
                                        $requested_shift = getScheduleTime($sr['work_schedule_id']);
                                        if ($requested_shift === 'N/A' && $sr['time_in'] && $sr['time_out']) {
                                            $requested_shift = date('g:i A', strtotime($sr['time_in'])) . ' – ' . date('g:i A', strtotime($sr['time_out']));
                                        }
                                    }
                                    
                                    // Get current schedule from cache for the start date of the request
                                    $current_sched = getCurrentScheduleForEmployee($sr['employee_id'], $pdo, $sr['start_date']);
                                    // Calculate period duration
                                    $start = new DateTime($sr['start_date']);
                                    $end = new DateTime($sr['end_date']);
                                    $days = $start->diff($end)->days + 1;
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-blue-600 font-medium text-sm">
                                                        <?= strtoupper(substr($sr['fname'], 0, 1) . substr($sr['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($sr['fname'] . ' ' . $sr['lname']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-red-600">Schedule <?= $current_sched['id'] ?>:</span>
                                                <span class="text-sm"><?= $current_sched['display'] ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if ($isRestDay): ?>
                                                <div class="flex flex-col">
                                                    <div class="flex items-center gap-2">
                                                        <i class="fas fa-bed text-red-600"></i>
                                                        <span class="font-medium text-red-600">Day Off</span>
                                                    </div>
                                                    <span class="text-xs text-gray-500 mt-1">Rest day request</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-green-600">Schedule <?= $sr['work_schedule_id'] ?>:</span>
                                                    <span class="text-sm"><?= $requested_shift ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium">Start: <?= date('M d, Y', strtotime($sr['start_date'])) ?></span>
                                                <span class="font-medium">End: <?= date('M d, Y', strtotime($sr['end_date'])) ?></span>
                                                <span class="text-xs text-gray-500 mt-1">(<?= $days ?> day<?= $days > 1 ? 's' : '' ?>)</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs text-sm text-gray-900">
                                            <div class="truncate hover:whitespace-normal cursor-help" title="<?= htmlspecialchars($sr['reason']) ?>">
                                                <?= htmlspecialchars($sr['reason']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php if (!empty($sr['attachment_scr'])): ?>
                                                <a href="../uploads/schedule_attachments/<?= htmlspecialchars($sr['attachment_scr']) ?>" target="_blank" 
                                                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php
                                            $status = strtolower($sr['status']);
                                            $statusClass = match($status) {
                                                'approved' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'rejected', 'declined' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $statusClass ?>">
                                                <?= htmlspecialchars(ucfirst($sr['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div class="flex flex-col">
                                                <?php if ($isHistoryView && isset($sr['approved_at'])): ?>
                                                    <span><?= date('M d, Y', strtotime($sr['approved_at'])) ?></span>
                                                    <span class="text-xs"><?= date('g:i A', strtotime($sr['approved_at'])) ?></span>
                                                <?php else: ?>
                                                    <span><?= date('M d, Y', strtotime($sr['created_at'])) ?></span>
                                                    <span class="text-xs"><?= date('g:i A', strtotime($sr['created_at'])) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <?php if (!$isHistoryView): ?>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if (strtolower($sr['status']) === 'pending'): ?>
                                                <div class="flex space-x-2">
                                                    <form method="post" action="process_schedule_action.php" class="inline-block">
                                                        <input type="hidden" name="request_id" value="<?= $sr['id'] ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" 
                                                                onclick="return confirm('Are you sure you want to approve this schedule change request?')"
                                                                class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm transition-colors">
                                                            <i class="fas fa-check mr-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            onclick="openDeclineModal(<?= $sr['id'] ?>)"
                                                            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm transition-colors">
                                                        <i class="fas fa-times mr-1"></i>Decline
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-500 italic">
                                                    <i class="fas fa-check-circle mr-1"></i>Done
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $isHistoryView ? '9' : '10' ?>" class="text-center text-sm py-8 text-gray-500">
                                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-2"></i>
                                        <div><?= $isHistoryView ? 'No processed schedule changes or day off requests found.' : 'No schedule change or day off requests found.' ?></div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Cards -->
                <?php if (!empty($schedule_requests)): ?>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php
                    $summary = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'declined' => 0];
                    foreach ($schedule_requests as $req) {
                        $status = strtolower($req['status']);
                        if (isset($summary[$status])) {
                            $summary[$status]++;
                        }
                    }
                    ?>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-list text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Requests</p>
                                <p class="text-lg font-semibold text-gray-900"><?= count($schedule_requests) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $summary['pending'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $summary['approved'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <i class="fas fa-times text-red-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Rejected</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $summary['rejected'] + $summary['declined'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php endif; // End of isMonthlyView check ?>

            </main>
        </div>
    </div>

    <!-- Decline Modal (for regular requests) -->
    <div id="declineModal" class="fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <i class="fas fa-times-circle text-red-600 mr-2"></i>Decline Schedule Request
            </h2>
            <form method="POST" action="process_schedule_action.php">
                <input type="hidden" name="request_id" id="modalRequestId">
                <input type="hidden" name="action" value="decline">

                <label for="explanation" class="block text-sm font-medium text-gray-700 mb-1">Explanation:</label>
                <textarea name="explanation" id="explanation" rows="4"
                          class="w-full border rounded-md px-3 py-2 text-sm focus:ring focus:ring-red-200"
                          placeholder="Provide explanation for declining..." required></textarea>

                <div class="mt-4 flex justify-end space-x-2">
                    <button type="button" onclick="closeDeclineModal()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-times mr-1"></i>Submit
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <!-- Decline Monthly Modal -->
    <div id="declineMonthlyModal" class="fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <i class="fas fa-times-circle text-red-600 mr-2"></i>Decline Monthly Schedule Request
            </h2>
            <form method="POST" action="process_monthly_schedule_action.php">
                <input type="hidden" name="request_id" id="modalMonthlyRequestId">
                <input type="hidden" name="action" value="decline">

                <label for="monthly_explanation" class="block text-sm font-medium text-gray-700 mb-1">Explanation:</label>
                <textarea name="explanation" id="monthly_explanation" rows="4"
                          class="w-full border rounded-md px-3 py-2 text-sm focus:ring focus:ring-red-200"
                          placeholder="Provide explanation for declining..." required></textarea>

                <div class="mt-4 flex justify-end space-x-2">
                    <button type="button" onclick="closeDeclineMonthlyModal()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-times mr-1"></i>Submit
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <!-- Decline Switch Modal -->
    <div id="declineSwitchModal" class="fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <i class="fas fa-times-circle text-red-600 mr-2"></i>Decline Schedule Switch Request
            </h2>
            <form method="POST" action="process_switch_action.php">
                <input type="hidden" name="request_id" id="modalSwitchRequestId">
                <input type="hidden" name="action" value="decline">

                <label for="switch_explanation" class="block text-sm font-medium text-gray-700 mb-1">Explanation:</label>
                <textarea name="explanation" id="switch_explanation" rows="4"
                          class="w-full border rounded-md px-3 py-2 text-sm focus:ring focus:ring-red-200"
                          placeholder="Provide explanation for declining..." required></textarea>

                <div class="mt-4 flex justify-end space-x-2">
                    <button type="button" onclick="closeDeclineSwitchModal()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-times mr-1"></i>Submit
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <script>
        function openDeclineModal(requestId) {
            document.getElementById('modalRequestId').value = requestId;
            document.getElementById('explanation').value = '';
            document.getElementById('declineModal').style.display = 'block';
            console.log('Decline modal opened for requestId:', requestId);
        }

        function closeDeclineModal() {
            document.getElementById('declineModal').style.display = 'none';
        }

        function openDeclineMonthlyModal(requestId) {
            document.getElementById('modalMonthlyRequestId').value = requestId;
            document.getElementById('monthly_explanation').value = '';
            document.getElementById('declineMonthlyModal').style.display = 'block';
            console.log('Decline monthly modal opened for requestId:', requestId);
        }

        function closeDeclineMonthlyModal() {
            document.getElementById('declineMonthlyModal').style.display = 'none';
        }

        function openDeclineSwitchModal(requestId) {
            document.getElementById('modalSwitchRequestId').value = requestId;
            document.getElementById('switch_explanation').value = '';
            document.getElementById('declineSwitchModal').style.display = 'block';
            console.log('Decline switch modal opened for requestId:', requestId);
        }

        function closeDeclineSwitchModal() {
            document.getElementById('declineSwitchModal').style.display = 'none';
        }

        // Optional: Prevent form submit if request_id is missing
        document.querySelector('#declineModal form').addEventListener('submit', function(e) {
            var reqId = document.getElementById('modalRequestId').value;
            if (!reqId) {
                alert('Request ID missing. Please try again.');
                e.preventDefault();
            }
        });
    </script>

</body>
</html>