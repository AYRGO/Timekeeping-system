<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Function to format duration in hours and minutes
function formatDurationPHP($hours) {
    $totalHours = floatval($hours ?: 0);
    $wholeHours = floor($totalHours);
    $minutes = round(($totalHours - $wholeHours) * 60);
    
    if ($wholeHours == 0 && $minutes == 0) {
        return '0 min';
    } else if ($wholeHours == 0) {
        return $minutes . ' min';
    } else if ($minutes == 0) {
        return $wholeHours . ($wholeHours > 1 ? ' hrs' : ' hr');
    } else {
        return $wholeHours . ($wholeHours > 1 ? ' hrs ' : ' hr ') . $minutes . ' min';
    }
}

// Get filter parameters
$employee_id = $_GET['employee_id'] ?? '';
$date_filter = $_GET['date_filter'] ?? ''; // Default to show all dates

// Pagination settings
$recordsPerPage = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $recordsPerPage;

// Build query conditions
$where_conditions = ["LOWER(TRIM(e.company)) = 'bugardi'"];
$params = [];

if ($employee_id) {
    $where_conditions[] = "e.id = ?";
    $params[] = $employee_id;
}

if ($date_filter) {
    $where_conditions[] = "DATE(tl.log_date) = ?";
    $params[] = $date_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as total_count
    FROM time_logs tl
    JOIN employees e ON tl.employee_id = e.id
    WHERE $where_clause
");
$count_stmt->execute($params);
$totalCountResult = $count_stmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalCountResult['total_count'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Fetch time logs with pagination
$query = "
    SELECT 
        tl.*,
        e.fname, e.lname, e.position, e.company
    FROM time_logs tl
    JOIN employees e ON tl.employee_id = e.id
    WHERE $where_clause
    ORDER BY tl.log_date DESC, tl.time_in DESC
    LIMIT $recordsPerPage OFFSET $offset
";

if (empty($params)) {
    $stmt = $pdo->query($query);
    $time_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $time_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get list of Bugardi employees for filter dropdown
$emp_stmt = $pdo->query("
    SELECT id, fname, lname, position 
    FROM employees 
    WHERE LOWER(TRIM(company)) = 'bugardi' 
    ORDER BY fname, lname
");
$employees = $emp_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../src/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <title>Bugardi - Time Logs</title>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        <?php include('sidebar.php'); ?>

        <div class="flex-1 flex flex-col">
            <?php include('header.php'); ?>

            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Page Header -->
                <div class="mb-6">
                    <div class="card-bugardi mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-white">Bugardi Time Logs</h1>
                                <p class="text-white mt-1 opacity-90">Bugardi employee time tracking records</p>
                            </div>
                            <div class="bg-gray-500 bg-opacity-30 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="flex items-center text-white">
                                    <i class="fas fa-clock mr-2"></i>
                                     <span class="font-semibold text-white"><?= $totalRecords ?> Total Records</span>
                                </div>
                                </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Filter Time Logs</h3>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                            <select name="employee_id" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="">All Bugardi Employees</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= ($employee_id == $emp['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) ?> - <?= htmlspecialchars($emp['position']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date (Optional)</label>
                            <input type="date" name="date_filter" value="<?= htmlspecialchars($date_filter) ?>" 
                                   placeholder="Select date to filter by..."
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <p class="text-xs text-gray-500 mt-1">Leave empty to show all dates</p>
                        </div>
                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
                            <div class="flex space-x-2">
                                <button type="submit" class="flex-1 bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transition-colors text-sm">
                                    <i class="fas fa-search mr-2"></i>Filter
                                </button>
                                <a href="time_logs.php" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors text-center text-sm">
                                    <i class="fas fa-times mr-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Time Logs Table -->
                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time In</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time Out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours Worked</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($time_logs)): ?>
                                <?php foreach ($time_logs as $log): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-orange-600 font-medium text-sm">
                                                        <?= strtoupper(substr($log['fname'], 0, 1) . substr($log['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium"><?= htmlspecialchars($log['fname'] . ' ' . $log['lname']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($log['position']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium"><?= date('M d, Y', strtotime($log['log_date'])) ?></span>
                                                <span class="text-xs text-gray-500"><?= date('l', strtotime($log['log_date'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php if ($log['time_in']): ?>
                                                <span class="font-medium text-green-600">
                                                    <?= date('g:i A', strtotime($log['time_in'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">Not recorded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php if ($log['time_out']): ?>
                                                <span class="font-medium text-red-600">
                                                    <?= date('g:i A', strtotime($log['time_out'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">Not recorded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php 
                                            if ($log['time_in'] && $log['time_out']) {
                                                $time_in = new DateTime($log['time_in']);
                                                $time_out = new DateTime($log['time_out']);
                                                $interval = $time_in->diff($time_out);
                                                $hours_worked = $interval->h + ($interval->i / 60);
                                                ?>
                                                <div class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium text-center">
                                                    <?= formatDurationPHP($hours_worked) ?>
                                                </div>
                                                <?php
                                            } else {
                                                ?>
                                                <span class="text-gray-400 italic">N/A</span>
                                                <?php
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php
                                            $status = strtolower($log['status'] ?? 'present');
                                            $status_class = match($status) {
                                                'present' => 'bg-green-100 text-green-800',
                                                'absent' => 'bg-red-100 text-red-800',
                                                'late' => 'bg-yellow-100 text-yellow-800',
                                                'partial' => 'bg-orange-100 text-orange-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $status_class ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-sm py-8 text-gray-500">
                                        <i class="fas fa-clock text-4xl text-gray-300 mb-2"></i>
                                        <div>No time logs found for the selected criteria.</div>
                                        <div class="text-xs text-gray-400 mt-1">Try adjusting your filters</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-700">
                        Showing <?= min($offset + 1, $totalRecords) ?> to <?= min($offset + $recordsPerPage, $totalRecords) ?> of <?= $totalRecords ?> time logs
                    </div>
                    
                    <div class="flex space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?employee_id=<?= urlencode($employee_id) ?>&date_filter=<?= urlencode($date_filter) ?>&page=<?= $page - 1 ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?employee_id=<?= urlencode($employee_id) ?>&date_filter=<?= urlencode($date_filter) ?>&page=<?= $i ?>" 
                               class="px-3 py-2 border rounded-md text-sm <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?employee_id=<?= urlencode($employee_id) ?>&date_filter=<?= urlencode($date_filter) ?>&page=<?= $page + 1 ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-500 hover:bg-gray-50">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

</body>
</html>
