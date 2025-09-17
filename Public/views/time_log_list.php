<?php
include('../config/db.php');

// Check if filtering by specific employee
$employee_id = $_GET['employee_id'] ?? null;
$employee_name = '';

// Search functionality
$search = $_GET['search'] ?? '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = "AND (e.fname LIKE :search OR e.lname LIKE :search OR t.log_date LIKE :search)";
    $searchParams[':search'] = '%' . $search . '%';
}

// Pagination
$limit = (int)10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = (int)(($page - 1) * $limit);

if ($employee_id) {
    // Get employee name for the title
    $empStmt = $pdo->prepare("SELECT fname, lname FROM employees WHERE id = ?");
    $empStmt->execute([$employee_id]);
    $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
    $employee_name = $employee ? $employee['fname'] . ' ' . $employee['lname'] : '';
    
    // Get total count for specific employee
    if (!empty($search)) {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM time_logs t
            JOIN employees e ON t.employee_id = e.id
            WHERE t.employee_id = :employee_id AND (e.fname LIKE :search1 OR e.lname LIKE :search2 OR t.log_date LIKE :search3)
        ");
        $countStmt->bindValue(':employee_id', $employee_id, PDO::PARAM_INT);
        $countStmt->bindValue(':search1', '%' . $search . '%', PDO::PARAM_STR);
        $countStmt->bindValue(':search2', '%' . $search . '%', PDO::PARAM_STR);
        $countStmt->bindValue(':search3', '%' . $search . '%', PDO::PARAM_STR);
        $countStmt->execute();
    } else {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM time_logs t
            JOIN employees e ON t.employee_id = e.id
            WHERE t.employee_id = :employee_id
        ");
        $countStmt->bindValue(':employee_id', $employee_id, PDO::PARAM_INT);
        $countStmt->execute();
    }
    $totalLogs = $countStmt->fetchColumn();
    
    // Fetch time logs for specific employee with pagination
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT t.id, t.log_date, t.time_in, t.time_out, e.fname, e.lname 
            FROM time_logs t
            JOIN employees e ON t.employee_id = e.id
            WHERE t.employee_id = :employee_id AND (e.fname LIKE :search1 OR e.lname LIKE :search2 OR t.log_date LIKE :search3)
            ORDER BY t.log_date DESC, t.time_in ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt->bindValue(':search1', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':search2', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':search3', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT t.id, t.log_date, t.time_in, t.time_out, e.fname, e.lname 
            FROM time_logs t
            JOIN employees e ON t.employee_id = e.id
            WHERE t.employee_id = :employee_id
            ORDER BY t.log_date DESC, t.time_in ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
} else {
    // Get total count for all logs
    if (!empty($search)) {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM time_logs t
            JOIN employees e ON t.employee_id = e.id
            WHERE (e.fname LIKE :search1 OR e.lname LIKE :search2 OR t.log_date LIKE :search3)
        ");
        $countStmt->bindValue(':search1', '%' . $search . '%', PDO::PARAM_STR);
        $countStmt->bindValue(':search2', '%' . $search . '%', PDO::PARAM_STR);
        $countStmt->bindValue(':search3', '%' . $search . '%', PDO::PARAM_STR);
        $countStmt->execute();
    } else {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM time_logs t
            JOIN employees e ON t.employee_id = e.id
        ");
        $countStmt->execute();
    }
    $totalLogs = $countStmt->fetchColumn();
    
    // Fetch all time logs with pagination
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT t.id, t.log_date, t.time_in, t.time_out, e.fname, e.lname 
            FROM time_logs t
            JOIN employees e ON t.employee_id = e.id
            WHERE (e.fname LIKE :search1 OR e.lname LIKE :search2 OR t.log_date LIKE :search3)
            ORDER BY t.log_date DESC, t.time_in ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':search1', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':search2', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':search3', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT t.id, t.log_date, t.time_in, t.time_out, e.fname, e.lname 
            FROM time_logs t
            JOIN employees e ON t.employee_id = e.id
            ORDER BY t.log_date DESC, t.time_in ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
}

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = ceil($totalLogs / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <title>Time Logs<?= $employee_name ? ' - ' . htmlspecialchars($employee_name) : '' ?></title>
    <style>
        .table-row-hover {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .table-row-hover:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            transform: translateX(2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="flex h-screen">
    <?php include('sidebar.php'); ?>

    <div class="flex-1 flex flex-col">
        <?php 
        $pageTitle = "Time Logs" . ($employee_name ? ' - ' . htmlspecialchars($employee_name) : '');
        include('header.php'); 
        ?>

            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Main Container -->
                <div class="w-full">
                    
                    <!-- Header Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <!-- Title and Stats -->
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                                    <?php if ($employee_name): ?>
                                        Time Logs - <?= htmlspecialchars($employee_name) ?>
                                    <?php else: ?>
                                        Time Logs
                                    <?php endif; ?>
                                </h1>
                                <p class="text-gray-600">
                                    <i class="fas fa-clock text-blue-500 mr-2"></i>
                                    <?php if ($employee_name): ?>
                                        Viewing attendance records for <?= htmlspecialchars($employee_name) ?>
                                    <?php else: ?>
                                        View all employee attendance records
                                    <?php endif; ?>
                                                                         <span class="ml-4 text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                                         <?= $totalLogs ?> Total Records
                                     </span>
                                </p>
                            </div>
                            
                                                         <!-- Action Buttons -->
                             <div class="flex flex-col sm:flex-row gap-3">
                                 <a href="employee_list.php" 
                                    class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
                                     <i class="fas fa-users mr-2"></i>
                                     Back to Employees
                                 </a>
                             </div>
                        </div>
                                         </div>

                     <!-- Search and Filters -->
                     <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                         <form method="GET" class="flex flex-col md:flex-row gap-4">
                             <!-- Search Bar -->
                             <div class="flex-1">
                                 <div class="relative">
                                     <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                         <i class="fas fa-search text-gray-400"></i>
                                     </div>
                                     <input 
                                         type="text" 
                                         name="search" 
                                         value="<?= htmlspecialchars($search) ?>"
                                         placeholder="Search by employee name or date..." 
                                         class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                     >
                                 </div>
                             </div>
                             
                             <!-- Search Actions -->
                             <div class="flex gap-2">
                                 <button type="submit" 
                                         class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-all duration-200">
                                     <i class="fas fa-search mr-2"></i>
                                     Search
                                 </button>
                                 <?php if (!empty($search)): ?>
                                     <a href="?<?= $employee_id ? 'employee_id=' . $employee_id : '' ?>" 
                                        class="inline-flex items-center px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all duration-200">
                                         <i class="fas fa-times mr-2"></i>
                                         Clear
                                     </a>
                                 <?php endif; ?>
                             </div>
                             
                             <!-- Hidden inputs to preserve employee_id and pagination -->
                             <?php if ($employee_id): ?>
                                 <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
                             <?php endif; ?>
                         </form>
                     </div>

                     <!-- Time Logs Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Table Header -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-table text-blue-500 mr-2"></i>
                                    Attendance Records
                                </h3>
                                <?php if ($employee_name): ?>
                                    <span class="text-sm text-gray-600">
                                        <i class="fas fa-filter text-green-500 mr-1"></i>
                                        Filtered for <?= htmlspecialchars($employee_name) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <?php if (!empty($logs)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-hashtag text-gray-400 mr-1"></i>ID
                                            </th>
                                            <?php if (!$employee_name): ?>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-user text-gray-400 mr-1"></i>Employee
                                            </th>
                                            <?php endif; ?>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-calendar text-gray-400 mr-1"></i>Date
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-sign-in-alt text-green-500 mr-1"></i>Time In
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-sign-out-alt text-red-500 mr-1"></i>Time Out
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-clock text-blue-500 mr-1"></i>Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($logs as $log): ?>
                                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                                                        <?= $log['id'] ?>
                                                    </span>
                                                </td>
                                                <?php if (!$employee_name): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <div class="flex items-center">
                                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xs font-medium mr-3">
                                                            <?= strtoupper(substr($log['fname'], 0, 1) . substr($log['lname'], 0, 1)) ?>
                                                        </div>
                                                        <span class="font-medium"><?= htmlspecialchars($log['fname'] . ' ' . $log['lname']) ?></span>
                                                    </div>
                                                </td>
                                                <?php endif; ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <div class="flex flex-col">
                                                        <span class="font-semibold"><?= date('M d, Y', strtotime($log['log_date'])) ?></span>
                                                        <span class="text-xs text-gray-500"><?= date('l', strtotime($log['log_date'])) ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                    <?php if ($log['time_in']): ?>
                                                        <div class="flex items-center space-x-2">
                                                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                                            <span class="font-mono font-medium"><?= date('h:i A', strtotime($log['time_in'])) ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 italic">No record</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                    <?php if ($log['time_out']): ?>
                                                        <div class="flex items-center space-x-2">
                                                            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                                            <span class="font-mono font-medium"><?= date('h:i A', strtotime($log['time_out'])) ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 italic">No record</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php if ($log['time_in'] && $log['time_out']): ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                                            <i class="fas fa-check-circle mr-1.5"></i>Complete
                                                        </span>
                                                    <?php elseif ($log['time_in'] && !$log['time_out']): ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                                            <i class="fas fa-clock mr-1.5"></i>Incomplete
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200">
                                                            <i class="fas fa-times-circle mr-1.5"></i>No Log
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                                         </div>

                             <!-- Pagination -->
                             <?php if ($totalPages > 1): ?>
                                 <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                                     <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                                         <!-- Pagination Info -->
                                         <div class="text-sm text-gray-700">
                                             <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                                             Showing page <?= $page ?> of <?= $totalPages ?> 
                                             (<?= $totalLogs ?> total records)
                                         </div>

                                         <!-- Pagination Links -->
                                         <nav class="flex gap-1" aria-label="Pagination">
                                             <?php if ($page > 1): ?>
                                                 <a href="?<?= $employee_id ? 'employee_id=' . $employee_id . '&' : '' ?>page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" 
                                                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-50 transition-colors">
                                                     <i class="fas fa-chevron-left mr-1"></i>
                                                     Previous
                                                 </a>
                                             <?php endif; ?>

                                             <?php 
                                             $start = max(1, $page - 2);
                                             $end = min($totalPages, $page + 2);
                                             
                                             for ($i = $start; $i <= $end; $i++): 
                                             ?>
                                                 <a href="?<?= $employee_id ? 'employee_id=' . $employee_id . '&' : '' ?>page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                                                    class="px-3 py-2 text-sm font-medium <?= $i === $page ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300' ?> border hover:bg-gray-50 transition-colors">
                                                     <?= $i ?>
                                                 </a>
                                             <?php endfor; ?>

                                             <?php if ($page < $totalPages): ?>
                                                 <a href="?<?= $employee_id ? 'employee_id=' . $employee_id . '&' : '' ?>page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" 
                                                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-50 transition-colors">
                                                     Next
                                                     <i class="fas fa-chevron-right ml-1"></i>
                                                 </a>
                                             <?php endif; ?>
                                         </nav>
                                     </div>
                                 </div>
                             <?php endif; ?>
                         <?php else: ?>
                             <!-- Empty State -->
                             <div class="text-center py-12">
                                 <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                     <i class="fas fa-clock text-gray-400 text-2xl"></i>
                                 </div>
                                 <h3 class="text-lg font-medium text-gray-900 mb-2">No time logs found</h3>
                                 <p class="text-gray-500 mb-6">
                                     <?php if ($employee_name): ?>
                                         <?= htmlspecialchars($employee_name) ?> has no attendance records yet.
                                     <?php else: ?>
                                         No time logs have been recorded yet.
                                     <?php endif; ?>
                                 </p>
                             </div>
                         <?php endif; ?>
                     </div>

                    <?php if ($employee_id && $employee_name): ?>
                        <!-- Summary for specific employee -->
                        <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-blue-800 mb-2">
                                        <i class="fas fa-user-circle text-blue-600 mr-2"></i>
                                        Employee Summary
                                    </h3>
                                    <p class="text-blue-700 font-medium text-lg"><?= htmlspecialchars($employee_name) ?></p>
                                                                         <p class="text-sm text-blue-600">Total Time Logs: <?= $totalLogs ?></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold text-blue-600"><?= count($logs) ?></div>
                                    <div class="text-sm text-blue-500">Records</div>
                                </div>
                            </div>
                        </div>
                                         <?php endif; ?>
                 </div>
             </main>
         </div>
     </div>
 
 <?php include('footer.php'); ?>
 
 </body>
 </html>
