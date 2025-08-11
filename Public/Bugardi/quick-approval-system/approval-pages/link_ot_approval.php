<?php
/**
 * Special OT Approval Page for Quick - Enhanced Version
 * Supports both permanent and temporary access tokens
 */

require_once '../config/db.php';

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
        
        $stmt = $pdo->prepare("UPDATE overtime_requests SET status = ?, created_at = NOW() WHERE id = ? AND status = 'Pending'");
        $success = $stmt->execute([$newStatus, $requestId]);
        
        if ($success && $stmt->rowCount() > 0) {
            $message = "Request #$requestId has been $newStatus successfully.";
            $messageType = 'success';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bugardi OT Approval - Quick</title>
    <link href="../../../../src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 p-4">
        <div class="container mx-auto">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-clock text-blue-600 mr-2"></i>
                        Bugardi Overtime Approval
                    </h1>
                    <div class="flex items-center space-x-3 mt-1">
                        <p class="text-gray-600">Manager Dashboard - Quick</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $tokenType === 'permanent' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                            <i class="fas <?= $tokenType === 'permanent' ? 'fa-infinity' : 'fa-clock' ?> mr-1"></i>
                            <?= $accessType ?>
                        </span>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    Last updated: <?= date('M d, Y - H:i A') ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Success/Error Message -->
    <?php if (isset($message)): ?>
    <div class="container mx-auto mt-4">
        <div class="alert <?= $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> border px-4 py-3 rounded-md">
            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="container mx-auto mt-6 px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-list-ul"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $totalRequests ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $pendingCount ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Approved</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $approvedCount ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Rejected</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $rejectedCount ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-filter mr-2"></i>Filters & Search
            </h3>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($tokenType) ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Employee/Reason</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-200">
                        <i class="fas fa-search mr-1"></i>Filter
                    </button>
                    <a href="?token=<?= htmlspecialchars($token) ?>&type=<?= htmlspecialchars($tokenType) ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition duration-200">
                        <i class="fas fa-times mr-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- OT Requests Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-list mr-2"></i>Overtime Requests
                </h3>
            </div>
            
            <?php if (!empty($overtime_requests)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OT Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($overtime_requests as $request): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($request['employee_name']) ?></div>
                                <div class="text-sm text-gray-500">ID: <?= $request['id'] ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($request['position']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= date('M d, Y', strtotime($request['date'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= $request['start_time'] ?? 'N/A' ?> - <?= $request['end_time'] ?? 'N/A' ?>
                                <?php if ($request['time_in'] && $request['time_out']): ?>
                                <div class="text-xs text-gray-500">Actual: <?= $request['time_in'] ?> - <?= $request['time_out'] ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= $request['duration_hours'] ? $request['duration_hours'] . ' hrs' : 'N/A' ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                <div class="truncate" title="<?= htmlspecialchars($request['reason']) ?>">
                                    <?= htmlspecialchars(substr($request['reason'], 0, 50)) ?><?= strlen($request['reason']) > 50 ? '...' : '' ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusClasses = [
                                    'Pending' => 'bg-yellow-100 text-yellow-800',
                                    'Approved' => 'bg-green-100 text-green-800',
                                    'Rejected' => 'bg-red-100 text-red-800'
                                ];
                                $statusClass = $statusClasses[$request['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                    <?= htmlspecialchars($request['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($request['status'] === 'Pending'): ?>
                                <div class="flex space-x-2">
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to APPROVE this overtime request?')">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded-md transition duration-200">
                                            <i class="fas fa-check mr-1"></i>Approve
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to REJECT this overtime request?')">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded-md transition duration-200">
                                            <i class="fas fa-times mr-1"></i>Reject
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span class="text-sm text-gray-500">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="px-6 py-12 text-center">
                <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No overtime requests found</h3>
                <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-12 bg-white border-t border-gray-200 py-6">
        <div class="container mx-auto px-4 text-center text-gray-600">
            <p>&copy; <?= date('Y') ?> Resource Staff Solutions. All rights reserved.</p>
            <p class="text-sm mt-2">Secure Access • Updated in Real-time • <?= $accessType ?></p>
        </div>
    </footer>

</body>
</html>
