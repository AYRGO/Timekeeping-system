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

// Pagination settings
$recordsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $recordsPerPage;

// Get total count for pagination
$countStmt = $pdo->query("
    SELECT COUNT(*) as total_count
    FROM employees e
    WHERE LOWER(TRIM(e.company)) = 'bugardi'
");
$totalCountResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalCountResult['total_count'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Fetch Bugardi employees with pagination
$stmt = $pdo->query("
    SELECT 
        e.id, e.fname, e.lname, e.email, e.contact, e.position, e.company,
        e.created_at,
        COUNT(DISTINCT tl.id) as total_logs,
        COUNT(DISTINCT DATE(tl.log_date)) as days_worked,
        MAX(tl.log_date) as last_attendance
    FROM employees e
    LEFT JOIN time_logs tl ON e.id = tl.employee_id
    WHERE LOWER(TRIM(e.company)) = 'bugardi'
    GROUP BY e.id, e.fname, e.lname, e.email, e.contact, e.position, e.company, e.created_at
    ORDER BY e.fname, e.lname
    LIMIT $recordsPerPage OFFSET $offset
");

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug information - check if there are any employees
error_log("Total records: " . $totalRecords);
error_log("Current page: " . $page);
error_log("Records per page: " . $recordsPerPage);
error_log("Offset: " . $offset);
error_log("Employee count fetched: " . count($employees));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../src/output.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <title>Bugardi - Employee List</title>
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
                                <h1 class="text-2xl font-bold">Bugardi Employees</h1>
                                <p class="text-white mt-1">Manage and view all Bugardi company employees</p>
                            </div>
                            <div class="bg-gray-500 bg-opacity-30 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="flex items-center text-white">
                                    <i class="fas fa-users mr-2"></i>
                                    <span class="font-semibold text-white"><?= count($employees) ?> Employees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <?php
                    $total_employees = count($employees);
                    $active_employees = 0;
                    $total_days_worked = 0;
                    $recent_activity = 0;
                    
                    foreach ($employees as $emp) {
                        if ($emp['total_logs'] > 0) $active_employees++;
                        $total_days_worked += $emp['days_worked'];
                        if ($emp['last_attendance'] && strtotime($emp['last_attendance']) > strtotime('-7 days')) {
                            $recent_activity++;
                        }
                    }
                    ?>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-bugardi-100 rounded-lg">
                                <i class="fas fa-users text-bugardi-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Employees</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $total_employees ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-user-check text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Active Employees</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $active_employees ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-calendar-check text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Days Worked</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $total_days_worked ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-clock text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Recent Activity (7 days)</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $recent_activity ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Table -->
                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attendance Summary</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Activity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($employees)): ?>
                                <?php foreach ($employees as $emp): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-bugardi-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-bugardi-600 font-medium text-sm">
                                                        <?= strtoupper(substr($emp['fname'], 0, 1) . substr($emp['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900">
                                                        <?= htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">ID: <?= $emp['id'] ?></div>
                                                    <div class="text-xs text-bugardi-600 font-medium">
                                                        <?= htmlspecialchars($emp['company']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="font-medium"><?= htmlspecialchars($emp['position']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <div class="flex items-center text-xs text-gray-600 mb-1">
                                                    <i class="fas fa-envelope mr-1"></i>
                                                    <?= htmlspecialchars($emp['email']) ?>
                                                </div>
                                                <div class="flex items-center text-xs text-gray-600">
                                                    <i class="fas fa-phone mr-1"></i>
                                                    <?= htmlspecialchars($emp['contact']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="grid grid-cols-2 gap-2">
                                                <div class="bg-blue-50 px-2 py-1 rounded text-xs">
                                                    <div class="text-blue-600 font-medium">Total Logs</div>
                                                    <div class="text-blue-800 font-bold"><?= $emp['total_logs'] ?></div>
                                                </div>
                                                <div class="bg-green-50 px-2 py-1 rounded text-xs">
                                                    <div class="text-green-600 font-medium">Days Worked</div>
                                                    <div class="text-green-800 font-bold"><?= $emp['days_worked'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if ($emp['last_attendance']): ?>
                                                <div class="flex flex-col">
                                                    <span class="font-medium">
                                                        <?= date('M d, Y', strtotime($emp['last_attendance'])) ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500">
                                                        <?= date('l', strtotime($emp['last_attendance'])) ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">No activity</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php
                                            $is_recent = $emp['last_attendance'] && strtotime($emp['last_attendance']) > strtotime('-7 days');
                                            $has_activity = $emp['total_logs'] > 0;
                                            ?>
                                            
                                            <?php if ($is_recent): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check-circle mr-1"></i>Active
                                                </span>
                                            <?php elseif ($has_activity): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-clock mr-1"></i>Inactive
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <i class="fas fa-user-plus mr-1"></i>New
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex space-x-2">
                                                <a href="time_logs.php?employee_id=<?= $emp['id'] ?>" 
                                                   class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition-colors">
                                                    <i class="fas fa-clock mr-1"></i>Time Logs
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-sm py-8 text-gray-500">
                                        <i class="fas fa-users text-4xl text-gray-300 mb-2"></i>
                                        <div>No Bugardi employees found.</div>
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
                        Showing <?= min($offset + 1, $totalRecords) ?> to <?= min($offset + $recordsPerPage, $totalRecords) ?> of <?= $totalRecords ?> employees
                    </div>
                    
                    <div class="flex space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?>" 
                               class="px-3 py-2 border rounded-md text-sm <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" 
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
