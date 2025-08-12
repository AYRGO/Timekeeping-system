<?php
/**
 * Special OT Approval Page for Quick - Enhanced Version
 * Supports both permanent and temporary access tokens
 */

require_once '../../../config/db.php';
require_once '../whatsapp-notifications/SimpleWhatsApp.php';

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
            FROM overtime_requests ot 
            JOIN employees emp ON ot.employee_id = emp.id 
            WHERE ot.id = ?
        ");
        $employeeStmt->execute([$requestId]);
        $employeeData = $employeeStmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("UPDATE overtime_requests SET status = ?, created_at = NOW() WHERE id = ? AND status = 'Pending'");
        $success = $stmt->execute([$newStatus, $requestId]);
        
        if ($success && $stmt->rowCount() > 0) {
            $message = "Request #$requestId has been $newStatus successfully.";
            $messageType = 'success';
            
            // Send notification (PowerShell automation)
            try {
                require_once '../whatsapp-notifications/PowerShellNotifier.php';
                $powershell = new PowerShellNotifier('scott@bugardi.com'); // Scott's email
                
                if ($action === 'approve') {
                    $notificationSent = $powershell->sendOTApprovalConfirmation($employeeData['employee_name'], $requestId);
                    $notificationMethod = 'Teams/Email';
                } else {
                    $notificationSent = $powershell->sendOTRejectionConfirmation($employeeData['employee_name'], $requestId);
                    $notificationMethod = 'Teams/Email';
                }
                
                if ($notificationSent) {
                    $message .= " � {$notificationMethod} notification sent to Scott.";
                } else {
                    $message .= " (PowerShell notification failed - check logs)";
                }
                
            } catch (Exception $e) {
                error_log("PowerShell notification failed: " . $e->getMessage());
                $message .= " (PowerShell notification error)";
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
        CONCAT(emp.fname, ' ', emp.lname) as employee_name,
        emp.position,
        ot.date,
        ot.start_time,
        ot.end_time,
        ot.duration_hours,
        ot.reason,
        ot.status,
        ot.created_at,
        tl.time_in,
        tl.time_out
    FROM overtime_requests ot
    JOIN employees emp ON ot.employee_id = emp.id
    LEFT JOIN time_logs tl ON ot.employee_id = tl.employee_id AND ot.date = tl.log_date
    WHERE $whereClause
    ORDER BY 
        CASE WHEN ot.status = 'Pending' THEN 1 ELSE 2 END,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Bugardi OT Approval - Quick</title>
    <link href="../../../../src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <?= date('M d, H:i A') ?>
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
                            <p class="text-xs text-gray-500 mt-1">ID: <?= $request['id'] ?></p>
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
                            <p class="text-gray-900"><?= $request['start_time'] ?? 'N/A' ?> - <?= $request['end_time'] ?? 'N/A' ?></p>
                            <?php if ($request['time_in'] && $request['time_out']): ?>
                                <p class="text-xs text-gray-500 mt-1">Actual: <?= $request['time_in'] ?> - <?= $request['time_out'] ?></p>
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
                    <?php if ($request['status'] === 'Pending'): ?>
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
        <?php if ($pendingCount > 0): ?>
        setInterval(function() {
            // Show a subtle notification that data is being refreshed
            console.log('Auto-checking for new requests...');
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
</html>
