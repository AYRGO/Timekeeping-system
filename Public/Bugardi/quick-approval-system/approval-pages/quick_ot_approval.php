<?php
/**
 * Special OT Approval Page for Quick - Enhanced Version
 * Supports both permanent and temporary access tokens
 * Enhanced with Real-time Schedule Tracker Integration
 */

// Set timezone to Manila time (UTC+8)
date_default_timezone_set('Asia/Manila');

require_once '../../../config/db.php';
require_once '../whatsapp-notifications/CallMeBotWhatsApp.php';

// Enhanced Schedule Tracker Integration - Real-time Schedule Status Detection
function getCurrentScheduleInfo($employee_id, $pdo) {
    // Set Manila timezone for consistency
    date_default_timezone_set('Asia/Manila');
    
    // Hardcoded schedule times (identical to schedule_tracker.php)
    $schedule_times = [
        3 => ['in' => '07:30:00', 'out' => '16:30:00'],
        4 => ['in' => '07:00:00', 'out' => '16:00:00'],
        5 => ['in' => '08:00:00', 'out' => '17:00:00'],
        6 => ['in' => '09:00:00', 'out' => '18:00:00'],
        7 => ['in' => '10:00:00', 'out' => '19:00:00'],
        8 => ['in' => '06:00:00', 'out' => '15:00:00'],
        9 => ['in' => '08:00:00', 'out' => '16:30:00'],
        10 => ['in' => '07:40:00', 'out' => '16:40:00'],
        11 => ['in' => '06:30:00', 'out' => '15:00:00'],
    ];

    // Fetch employee's official schedule from employee table
    $stmt = $pdo->prepare("SELECT official_sched, fname, lname FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fallback to schedule ID 4 if not set
    $default_schedule_id = $employee['official_sched'] ?? 4;
    $employee_name = ($employee['fname'] ?? '') . ' ' . ($employee['lname'] ?? '');
    
    $today = date('Y-m-d');
    
    // Step 1: Get current active approved schedule from post_schedule_change_requests
    $stmt = $pdo->prepare("
        SELECT work_schedule_id, status, start_date, end_date, created_at
        FROM post_schedule_change_requests 
        WHERE employee_id = ? AND status = 'Approved' 
        AND ? BETWEEN start_date AND end_date 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $today]);
    $currentActiveSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Step 2: Get latest schedule request from schedule_change_requests (for pending status)
    $stmt = $pdo->prepare("
        SELECT work_schedule_id, status, start_date, end_date, created_at, reason
        FROM schedule_change_requests 
        WHERE employee_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$employee_id]);
    $latestRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Step 3: Determine what schedule to use based on real-time current situation
    if ($currentActiveSchedule) {
        // There's an active approved schedule change - use it
        $current_real_schedule_id = $currentActiveSchedule['work_schedule_id'] ?? $default_schedule_id;
        $schedule_id_to_use = $current_real_schedule_id;
        
        // Check if there's a newer pending request while current approved is still active
        if ($latestRequest && strtolower(trim($latestRequest['status'])) === 'pending') {
            $schedule_status = "pending_with_active";
            $status_text = "New Schedule Pending (Current: Active Change)";
            $schedule_type = "active_with_pending";
        } else {
            $schedule_status = "approved";
            $status_text = "Active Schedule Change (" . date('M d', strtotime($currentActiveSchedule['start_date'])) . " - " . date('M d', strtotime($currentActiveSchedule['end_date'])) . ")";
            $schedule_type = "active_approved";
        }
    } else {
        // No currently active approved schedule - using baseline
        $current_real_schedule_id = $default_schedule_id;
        $schedule_id_to_use = $default_schedule_id;
        
        // Check latest request status
        if ($latestRequest) {
            $status = strtolower(trim($latestRequest['status']));
            
            if ($status === 'pending') {
                $schedule_status = "pending";
                $status_text = "Schedule Change Pending (Current: Official)";
                $schedule_type = "baseline_with_pending";
            } elseif (in_array($status, ['declined', 'rejected'])) {
                $schedule_status = "declined";
                $status_text = "Request Declined (Using Official)";
                $schedule_type = "baseline_declined";
            } else {
                $schedule_status = "baseline";
                $status_text = "Official Schedule (ID: {$default_schedule_id})";
                $schedule_type = "baseline_official";
            }
        } else {
            $schedule_status = "baseline";
            $status_text = "Official Schedule";
            $schedule_type = "baseline_official";
        }
    }
    
    // Step 4: Convert final schedule to display format
    $sched_time_in_24h = $schedule_times[$schedule_id_to_use]['in'] ?? '07:00:00';
    $sched_time_out_24h = $schedule_times[$schedule_id_to_use]['out'] ?? '16:00:00';
    $sched_time_in  = date('h:i A', strtotime($sched_time_in_24h));
    $sched_time_out = date('h:i A', strtotime($sched_time_out_24h));
    
    // Step 5: Determine UI badge color based on schedule status
    $color = match ($schedule_status) {
        'approved' => 'green',
        'pending', 'pending_with_active' => 'yellow',
        'declined' => 'red',
        default    => 'blue',
    };
    
    // Step 6: Generate detailed status information
    $is_changed_schedule = ($schedule_status === 'approved' || $schedule_status === 'pending_with_active');
    $is_official_schedule = ($schedule_status === 'baseline');
    
    return [
        'schedule_id' => $schedule_id_to_use,
        'current_real_schedule_id' => $current_real_schedule_id,
        'baseline_schedule_id' => $default_schedule_id,
        'time_in' => $sched_time_in,
        'time_out' => $sched_time_out,
        'time_in_24h' => $sched_time_in_24h,
        'time_out_24h' => $sched_time_out_24h,
        'status' => $schedule_status,
        'status_text' => $status_text,
        'schedule_type' => $schedule_type,
        'color' => $color,
        'is_changed_schedule' => $is_changed_schedule,
        'is_official_schedule' => $is_official_schedule,
        'has_active_approved' => $currentActiveSchedule ? true : false,
        'has_pending_request' => ($latestRequest && strtolower(trim($latestRequest['status'])) === 'pending') ? true : false,
        'employee_name' => trim($employee_name),
        'active_schedule_info' => $currentActiveSchedule,
        'latest_request_info' => $latestRequest,
        'real_time_check' => date('Y-m-d H:i:s') // Timestamp of when this check was performed
    ];
}

// Function to get current real-time schedule ID (for compatibility with other systems)
function getCurrentRealScheduleId($employee_id, $pdo) {
    $scheduleInfo = getCurrentScheduleInfo($employee_id, $pdo);
    return $scheduleInfo['current_real_schedule_id'];
}

// Token verification functions
function verifyTemporaryToken($token, $date = null) {
    $secret = 'quick-ot-approval-bugardi-2025';
    $date = $date ?: date('Y-m-d');
    $expectedToken = hash('sha256', 'quick' . $date . $secret);
    $yesterdayToken = hash('sha256', 'quick' . date('Y-m-d', strtotime('-1 day')) . $secret);
    
    return hash_equals($expectedToken, $token) || hash_equals($yesterdayToken, $token);
}

function verifyPermanentToken($token) {
    $secret = 'quick-ot-approval-bugardi-permanent-2025';
    $expectedToken = hash('sha256', 'quick-permanent' . $secret);
    
    return hash_equals($expectedToken, $token);
}

// Check if valid token is provided
$token = $_GET['token'] ?? '';
$tokenType = $_GET['type'] ?? 'temporary';
$isValidToken = false;

if ($tokenType === 'permanent') {
    $isValidToken = verifyPermanentToken($token);
    $accessType = 'Permanent Access';
} else {
    $isValidToken = verifyTemporaryToken($token);
    $accessType = 'Temporary Access (24 hours)';
}

if (!$isValidToken) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Access Denied</title>
        <link href="../../../../src/output.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="bg-red-50 min-h-screen flex items-center justify-center">
        <div class="max-w-md mx-auto text-center p-8 bg-white rounded-lg shadow-lg">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-lock text-red-600 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Access Denied</h1>
            <p class="text-gray-600 mb-4">Invalid or expired access token.</p>
            <p class="text-sm text-gray-500">Please contact your administrator for a new access link.</p>
        </div>
    </body>
    </html>
    ');
}

// Handle approval/rejection actions
if ($_POST['action'] ?? false) {
    $action = $_POST['action'];
    $requestId = $_POST['request_id'] ?? 0;
    
    if (in_array($action, ['approve', 'reject']) && $requestId > 0) {
        $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
        
        // Get employee name for WhatsApp notification
        $employeeStmt = $pdo->prepare("
            SELECT CONCAT(emp.fname, ' ', emp.lname) as employee_name 
            FROM post_ot_requests ot 
            JOIN employees emp ON ot.employee_id = emp.id 
            WHERE ot.id = ?
        ");
        $employeeStmt->execute([$requestId]);
        $employeeData = $employeeStmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("UPDATE post_ot_requests SET status = ?, created_at = NOW() WHERE id = ? AND status = 'Pending'");
        $success = $stmt->execute([$newStatus, $requestId]);
        
        if ($success && $stmt->rowCount() > 0) {
            $message = "Request #$requestId has been $newStatus successfully.";
            $messageType = 'success';
            
            // Send WhatsApp notification about the approval/rejection
            try {
                $whatsapp = new CallMeBotWhatsApp();
                
                $notificationMessage = "📋 *OT Request Update*\n\n";
                $notificationMessage .= "👤 Employee: " . ($employeeData['employee_name'] ?? 'Unknown') . "\n";
                $notificationMessage .= "🆔 Request ID: #$requestId\n";
                $notificationMessage .= "📅 Status: " . ($action === 'approve' ? '✅ APPROVED' : '❌ REJECTED') . "\n";
                $notificationMessage .= "👨‍💼 Approved by: Quick\n";
                $notificationMessage .= "⏰ Time: " . date('M d, Y H:i A') . "\n\n";
                
                if ($action === 'approve') {
                    $notificationMessage .= "The overtime request has been approved and will be processed.";
                } else {
                    $notificationMessage .= "The overtime request has been rejected.";
                }
                
                // Send to your WhatsApp for confirmation
                $apiKey = '2833078';
                $phone = '639762477146';
                $encodedMessage = urlencode($notificationMessage);
                $apiUrl = "https://api.callmebot.com/whatsapp.php?phone={$phone}&text={$encodedMessage}&apikey={$apiKey}";
                
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 10,
                        'header' => 'User-Agent: Bugardi-Approval/1.0'
                    ]
                ]);
                
                $response = @file_get_contents($apiUrl, false, $context);
                
                if ($response !== false) {
                    $message .= " 📱 WhatsApp confirmation sent.";
                    error_log("✅ WhatsApp confirmation sent for OT request #$requestId action: $action");
                } else {
                    $message .= " (WhatsApp notification failed)";
                    error_log("❌ WhatsApp confirmation failed for OT request #$requestId");
                }
                
            } catch (Exception $e) {
                error_log("WhatsApp notification error: " . $e->getMessage());
                $message .= " (WhatsApp notification error)";
            }
        } else {
            $message = "Failed to update request or request already processed.";
            $messageType = 'error';
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$status = $_GET['status'] ?? '';

// Build the query
$whereConditions = ["LOWER(emp.company) = 'bugardi'"];
$params = [];

if ($search) {
    $whereConditions[] = "(CONCAT(emp.fname, ' ', emp.lname) LIKE ? OR ot.reason LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($dateFrom) {
    $whereConditions[] = "ot.date >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = "ot.date <= ?";
    $params[] = $dateTo;
}

if ($status) {
    $whereConditions[] = "ot.status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $whereConditions);

// Fetch overtime requests for Bugardi employees
$sql = "
    SELECT 
        ot.id,
        ot.employee_id,
        CONCAT(emp.fname, ' ', emp.lname) as employee_name,
        emp.position,
        DATE(ot.time_in) as date,
        TIME(ot.time_in) as start_time,
        TIME(ot.time_out) as end_time,
        ot.ot_duration as duration_hours,
        ot.reason,
        ot.status,
        ot.created_at,
        ot.time_in,
        ot.time_out
    FROM post_ot_requests ot
    JOIN employees emp ON ot.employee_id = emp.id
    WHERE $whereClause
    ORDER BY 
        CASE WHEN LOWER(ot.status) = 'pending' THEN 1 ELSE 2 END,
        ot.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$overtime_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count statistics
$totalRequests = count($overtime_requests);
$pendingCount = count(array_filter($overtime_requests, fn($req) => $req['status'] === 'Pending'));
$approvedCount = count(array_filter($overtime_requests, fn($req) => $req['status'] === 'Approved'));
$rejectedCount = count(array_filter($overtime_requests, fn($req) => $req['status'] === 'Rejected'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../../../src/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Bugardi OT Approval - Quick</title>
    <style>
        /* iPhone-specific optimizations */
        body {
            overflow-x: hidden; /* Prevent horizontal scroll */
        }
        
        @media (max-width: 768px) {
            .touch-button {
                min-height: 48px;
                padding: 14px 16px;
                font-size: 16px;
                font-weight: 600;
                touch-action: manipulation;
                border-radius: 8px;
            }
            .card-mobile {
                margin: 8px;
                padding: 16px;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                max-width: 100vw;
                box-sizing: border-box;
            }
            .text-mobile-lg {
                font-size: 18px;
                line-height: 1.4;
            }
            .text-mobile-sm {
                font-size: 14px;
            }
            /* Ensure buttons don't overflow */
            .action-buttons {
                width: 100%;
                max-width: 100%;
                gap: 8px;
            }
            .action-button {
                flex: 1;
                min-width: 0; /* Allow flex shrink */
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            /* Prevent any element from causing horizontal scroll */
            * {
                max-width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    
    <!-- Mobile-First Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="px-4 py-3">
            <div class="flex flex-col space-y-2 md:flex-row md:justify-between md:items-center md:space-y-0">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                        <i class="fas fa-clock text-blue-600 mr-2"></i>
                        <span class="hidden sm:inline">Bugardi </span>OT Approval
                    </h1>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 mt-1">
                        <p class="text-gray-600 text-sm">Manager: Quick</p>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-1 sm:mt-0 w-fit <?= $tokenType === 'permanent' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                            <i class="fas <?= $tokenType === 'permanent' ? 'fa-infinity' : 'fa-clock' ?> mr-1"></i>
                            <?= $tokenType === 'permanent' ? 'Permanent' : '24hr Access' ?>
                        </span>
                    </div>
                </div>
                <div class="text-xs text-gray-500 md:text-sm">
                    <?php
                    // Set timezone to Manila
                    date_default_timezone_set('Asia/Manila');
                    echo date('M d, H:i A');
                    ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile-Optimized Notification Banner -->
    <?php if ($pendingCount > 0): ?>
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-400">
        <div class="px-4 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-exclamation text-yellow-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-yellow-800 text-mobile-lg">
                            <?= $pendingCount ?> Request<?= $pendingCount > 1 ? 's' : '' ?> Pending
                        </p>
                        <p class="text-yellow-700 text-sm">Tap requests below to approve/reject</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-1"></i>
                        Action Required
                    </span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <!-- Success/Error Message -->
    <?php if (isset($message)): ?>
    <div class="px-4 mt-4">
        <div class="alert <?= $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> border px-4 py-3 rounded-md">
            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mobile-Optimized Statistics Cards -->
    <div class="px-4 mt-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <!-- Total Requests Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 card-mobile">
                <div class="flex flex-col items-center text-center">
                    <div class="p-2 rounded-full bg-blue-100 text-blue-600 mb-2">
                        <i class="fas fa-list-ul text-lg"></i>
                    </div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Total</p>
                    <p class="text-xl font-bold text-gray-900"><?= $totalRequests ?></p>
                </div>
            </div>
            
            <!-- Pending Requests Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 card-mobile <?= $pendingCount > 0 ? 'ring-2 ring-yellow-200' : '' ?>">
                <div class="flex flex-col items-center text-center">
                    <div class="p-2 rounded-full bg-yellow-100 text-yellow-600 mb-2 <?= $pendingCount > 0 ? 'animate-pulse' : '' ?>">
                        <i class="fas fa-clock text-lg"></i>
                    </div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Pending</p>
                    <p class="text-xl font-bold <?= $pendingCount > 0 ? 'text-yellow-600' : 'text-gray-900' ?>"><?= $pendingCount ?></p>
                </div>
            </div>
            
            <!-- Approved Requests Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 card-mobile">
                <div class="flex flex-col items-center text-center">
                    <div class="p-2 rounded-full bg-green-100 text-green-600 mb-2">
                        <i class="fas fa-check-circle text-lg"></i>
                    </div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Approved</p>
                    <p class="text-xl font-bold text-gray-900"><?= $approvedCount ?></p>
                </div>
            </div>
            
            <!-- Rejected Requests Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 card-mobile">
                <div class="flex flex-col items-center text-center">
                    <div class="p-2 rounded-full bg-red-100 text-red-600 mb-2">
                        <i class="fas fa-times-circle text-lg"></i>
                    </div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Rejected</p>
                    <p class="text-xl font-bold text-gray-900"><?= $rejectedCount ?></p>
                </div>
            </div>
        </div>

        <!-- Schedule Status Overview -->
        <?php
        // Get schedule status overview for all Bugardi employees with OT requests
        $scheduleOverview = [];
        $employeeIds = array_unique(array_column($overtime_requests, 'employee_id'));
        
        foreach ($employeeIds as $empId) {
            $empSchedule = getCurrentScheduleInfo($empId, $pdo);
            $scheduleType = $empSchedule['is_changed_schedule'] ? 'changed' : 'official';
            $scheduleOverview[$scheduleType] = ($scheduleOverview[$scheduleType] ?? 0) + 1;
        }
        
        $totalEmployees = count($employeeIds);
        $changedSchedules = $scheduleOverview['changed'] ?? 0;
        $officialSchedules = $scheduleOverview['official'] ?? 0;
        ?>
        
        <?php if ($totalEmployees > 0): ?>
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4 card-mobile">
            <h3 class="text-lg font-semibold mb-3">
                <i class="fas fa-chart-pie mr-2"></i>Schedule Status Overview
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <!-- Changed Schedules -->
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-exchange-alt text-blue-600 text-lg mr-2"></i>
                        <span class="font-semibold text-blue-800">Changed</span>
                    </div>
                    <p class="text-2xl font-bold text-blue-600"><?= $changedSchedules ?></p>
                    <p class="text-xs text-blue-700">employees with schedule changes</p>
                </div>
                
                <!-- Official Schedules -->
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-calendar-day text-gray-600 text-lg mr-2"></i>
                        <span class="font-semibold text-gray-800">Official</span>
                    </div>
                    <p class="text-2xl font-bold text-gray-600"><?= $officialSchedules ?></p>
                    <p class="text-xs text-gray-700">employees using official schedule</p>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500 text-center">
                <i class="fas fa-info-circle mr-1"></i>
                Real-time schedule tracking for <?= $totalEmployees ?> employees with OT requests
            </div>
        </div>
        <?php endif; ?>

        <!-- Mobile-Optimized Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4 card-mobile">
            <h3 class="text-lg font-semibold mb-3">
                <i class="fas fa-filter mr-2"></i>Filters
            </h3>
            
            <form method="GET" class="space-y-3">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($tokenType) ?>">
                
                <!-- Search Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Employee/Reason</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search..." 
                           class="w-full px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base">
                </div>
                
                <!-- Date Range (Side by Side on Mobile) -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" 
                               class="w-full px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" 
                               class="w-full px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base">
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex action-buttons pt-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-3 rounded-md transition duration-200 touch-button action-button mr-2">
                        <i class="fas fa-search mr-1"></i>Filter
                    </button>
                    <a href="?token=<?= htmlspecialchars($token) ?>&type=<?= htmlspecialchars($tokenType) ?>" 
                       class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-3 rounded-md transition duration-200 text-center touch-button action-button">
                        <i class="fas fa-times mr-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Mobile-Optimized OT Requests -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold px-1">
                <i class="fas fa-list mr-2"></i>Overtime Requests (<?= count($overtime_requests) ?>)
            </h3>
            
            <?php if (!empty($overtime_requests)): ?>
                <?php foreach ($overtime_requests as $request): ?>
                <?php 
                    // Get schedule information for this employee
                    $scheduleInfo = getCurrentScheduleInfo($request['employee_id'], $pdo);
                ?>
                <div class="bg-white rounded-lg shadow-sm card-mobile border-l-4 <?= $request['status'] === 'Pending' ? 'border-yellow-400' : ($request['status'] === 'Approved' ? 'border-green-400' : 'border-red-400') ?>">
                    <!-- Employee Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <span class="text-blue-600 font-semibold text-sm">
                                    <?= strtoupper(substr(explode(' ', $request['employee_name'])[0], 0, 1) . substr(explode(' ', $request['employee_name'])[1] ?? '', 0, 1)) ?>
                                </span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 text-mobile-lg"><?= htmlspecialchars($request['employee_name']) ?></h4>
                                <p class="text-gray-600 text-sm"><?= htmlspecialchars($request['position']) ?></p>
                                <!-- Enhanced Real-time Schedule Info -->
                                <div class="flex flex-col mt-1 space-y-1">
                                    <!-- Current Active Schedule -->
                                    <div class="flex items-center text-xs">
                                        <span class="bg-<?= $scheduleInfo['color'] ?>-100 text-<?= $scheduleInfo['color'] ?>-800 px-2 py-1 rounded-full mr-2 flex items-center">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?= htmlspecialchars($scheduleInfo['time_in']) ?> - <?= htmlspecialchars($scheduleInfo['time_out']) ?>
                                        </span>
                                        <!-- Schedule Type Indicator -->
                                        <?php if ($scheduleInfo['is_changed_schedule']): ?>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                                <i class="fas fa-exchange-alt mr-1"></i>Changed Schedule
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">
                                                <i class="fas fa-calendar-day mr-1"></i>Official Schedule
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Status Details -->
                                    <div class="text-xs text-gray-500">
                                        <?= htmlspecialchars($scheduleInfo['status_text']) ?>
                                        <?php if ($scheduleInfo['has_pending_request'] && $scheduleInfo['has_active_approved']): ?>
                                            <span class="ml-2 text-yellow-600 font-medium">• New Request Pending</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Real-time Check Timestamp -->
                                    <div class="text-xs text-gray-400">
                                        <i class="fas fa-sync-alt mr-1"></i>Checked: <?= date('M d, H:i A', strtotime($scheduleInfo['real_time_check'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <?php
                            $statusClasses = [
                                'Pending' => 'bg-yellow-100 text-yellow-800',
                                'Approved' => 'bg-green-100 text-green-800',
                                'Rejected' => 'bg-red-100 text-red-800'
                            ];
                            ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $statusClasses[$request['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= htmlspecialchars($request['status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Request Details -->
                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div>
                            <p class="text-gray-600 font-medium mb-1">
                                <i class="fas fa-calendar-alt mr-1"></i>Date
                            </p>
                            <p class="text-gray-900"><?= date('M d, Y', strtotime($request['date'])) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 font-medium mb-1">
                                <i class="fas fa-clock mr-1"></i>Duration
                            </p>
                            <p class="text-gray-900"><?= $request['duration_hours'] ? $request['duration_hours'] . ' hrs' : 'N/A' ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-gray-600 font-medium mb-1">
                                <i class="fas fa-time mr-1"></i>OT Time
                            </p>
                            <p class="text-gray-900">
                                <?= $request['start_time'] ? date('g:i A', strtotime($request['start_time'])) : 'N/A' ?> - 
                                <?= $request['end_time'] ? date('g:i A', strtotime($request['end_time'])) : 'N/A' ?>
                            </p>
                        </div>
                    </div>

                    <!-- Enhanced Schedule Analysis Section -->
                    <div class="mb-4 bg-gray-50 p-3 rounded-lg border hidden">
                        <p class="text-gray-700 font-medium mb-2 text-sm">
                            <i class="fas fa-chart-line mr-1"></i>Schedule Analysis
                        </p>
                        <div class="grid grid-cols-1 gap-2 text-xs">
                            <!-- Current Schedule Status -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Current Schedule:</span>
                                <div class="flex items-center">
                                    <span class="bg-<?= $scheduleInfo['color'] ?>-100 text-<?= $scheduleInfo['color'] ?>-800 px-2 py-1 rounded text-xs font-medium mr-2">
                                        <?= htmlspecialchars($scheduleInfo['time_in']) ?> - <?= htmlspecialchars($scheduleInfo['time_out']) ?>
                                    </span>
                                    <?php if ($scheduleInfo['is_changed_schedule']): ?>
                                        <i class="fas fa-exchange-alt text-blue-600" title="Using Changed Schedule"></i>
                                    <?php else: ?>
                                        <i class="fas fa-calendar-day text-gray-600" title="Using Official Schedule"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Schedule IDs for Reference -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Schedule ID:</span>
                                <span class="font-mono text-gray-800">
                                    Current: #<?= $scheduleInfo['schedule_id'] ?>
                                    <?php if ($scheduleInfo['schedule_id'] !== $scheduleInfo['baseline_schedule_id']): ?>
                                        (Official: #<?= $scheduleInfo['baseline_schedule_id'] ?>)
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <!-- Schedule Status -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Status:</span>
                                <span class="text-<?= $scheduleInfo['color'] ?>-800 font-medium">
                                    <?php
                                    $statusDisplay = match($scheduleInfo['status']) {
                                        'approved' => 'Active Schedule Change',
                                        'pending', 'pending_with_active' => 'Schedule Change Pending',
                                        'declined' => 'Request Declined',
                                        default => 'Official Schedule'
                                    };
                                    echo $statusDisplay;
                                    ?>
                                </span>
                            </div>
                            
                            <!-- Additional Info for Complex Cases -->
                            <?php if ($scheduleInfo['has_pending_request'] && $scheduleInfo['has_active_approved']): ?>
                            <div class="flex justify-between items-center border-t pt-2 mt-2">
                                <span class="text-gray-600">Note:</span>
                                <span class="text-yellow-700 text-xs">New schedule change pending while current change is active</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="mb-4">
                        <p class="text-gray-600 font-medium mb-1 text-sm">
                            <i class="fas fa-comment mr-1"></i>Reason
                        </p>
                        <p class="text-gray-900 text-sm bg-gray-50 p-2 rounded"><?= htmlspecialchars($request['reason']) ?></p>
                    </div>

                    <!-- Action Buttons for Pending Requests -->
                    <?php if (strtolower($request['status']) === 'pending'): ?>
                    <div class="flex action-buttons pt-3 border-t border-gray-100">
                        <form method="POST" class="flex-1">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="type" value="<?= htmlspecialchars($tokenType) ?>">
                            <button type="submit" 
                                    onclick="return confirm('✅ Approve OT for <?= addslashes($request['employee_name']) ?>?')"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-3 rounded-md transition-colors touch-button action-button">
                                <i class="fas fa-check mr-1"></i>Approve
                            </button>
                        </form>
                        <form method="POST" class="flex-1 ml-2">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="type" value="<?= htmlspecialchars($tokenType) ?>">
                            <button type="submit" 
                                    onclick="return confirm('❌ Reject OT for <?= addslashes($request['employee_name']) ?>?')"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-3 rounded-md transition-colors touch-button action-button">
                                <i class="fas fa-times mr-1"></i>Reject
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="pt-3 border-t border-gray-100">
                        <p class="text-center text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Request has been <?= strtolower($request['status']) ?> on <?= date('M d, Y', strtotime($request['created_at'])) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-sm p-8 text-center card-mobile">
                    <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No overtime requests found</h3>
                    <p class="text-gray-500 text-sm">Try adjusting your search or filter criteria.</p>
                </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile-Optimized Footer -->
    <footer class="mt-8 bg-white border-t border-gray-200 py-4">
        <div class="px-4 text-center">
            <p class="text-sm text-gray-600">
                <i class="fas fa-shield-alt mr-1"></i>
                Secure OT Approval System | Bugardi Company
            </p>
            <p class="text-xs text-gray-500 mt-1">
                Last synced: <?= date('M d, Y H:i A') ?>
            </p>
        </div>
    </footer>

    <!-- Mobile-friendly JavaScript -->
    <script>
        // Prevent zoom on double tap for iOS
        document.addEventListener('touchend', function (event) {
            var now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        var lastTouchEnd = 0;

        // Auto-refresh pending count every 30 seconds (only if pending requests exist)
        // Enhanced with real-time schedule status tracking
        <?php if ($pendingCount > 0): ?>
        setInterval(function() {
            // Show a subtle notification that data is being refreshed
            console.log('Auto-checking for new requests and schedule changes...');
        }, 30000);
        <?php endif; ?>

        // Add touch feedback for buttons
        document.querySelectorAll('.touch-button').forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.opacity = '0.8';
            });
            button.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });
    </script>
        <div class="container mx-auto px-4 text-center text-gray-600">
            <p>&copy; <?= date('Y') ?> Resource Staff Solutions. All rights reserved.</p>
            <p class="text-sm mt-2">Secure Access • Updated in Real-time • <?= $accessType ?></p>
        </div>
    </footer>

    <script>
        function scrollToPendingRequests() {
            const table = document.querySelector('table');
            if (table) {
                table.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Highlight pending rows briefly
                const pendingRows = document.querySelectorAll('tr');
                pendingRows.forEach(row => {
                    if (row.innerHTML.includes('bg-yellow-100')) {
                        row.style.backgroundColor = '#fef3c7';
                        setTimeout(() => {
                            row.style.backgroundColor = '';
                        }, 3000);
                    }
                });
            }
        }
        
        function confirmApproval(employeeName, date, duration) {
            return confirm(`🟢 APPROVE OVERTIME REQUEST\n\nEmployee: ${employeeName}\nDate: ${date}\nDuration: ${duration}\n\nAre you sure you want to approve this request?`);
        }
        
        function confirmRejection(employeeName, date) {
            return confirm(`🔴 REJECT OVERTIME REQUEST\n\nEmployee: ${employeeName}\nDate: ${date}\n\nAre you sure you want to reject this request?\n\nNote: The employee will be notified of the rejection.`);
        }
        
        // Auto-refresh every 5 minutes to show new requests
        setInterval(function() {
            // Only refresh if user is not actively interacting
            if (document.hidden === false) {
                const lastActivity = localStorage.getItem('lastActivity') || 0;
                const now = Date.now();
                if (now - lastActivity > 300000) { // 5 minutes
                    window.location.reload();
                }
            }
        }, 300000);
        
        // Track user activity
        document.addEventListener('click', function() {
            localStorage.setItem('lastActivity', Date.now());
        });
        
        document.addEventListener('scroll', function() {
            localStorage.setItem('lastActivity', Date.now());
        });
    </script>

</body>
</body>
</html>
