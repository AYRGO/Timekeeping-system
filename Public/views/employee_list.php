<?php

$pageTitle = "Management";
// Secure session cookie params
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// Check authorization - must be internal employee with admin view
if (
    !isset($_SESSION['employee']['id']) ||
    $_SESSION['employee']['role'] !== 'internal' ||
    $_SESSION['view_mode'] !== 'admin'
) {
    // Unauthorized, redirect to employee view
    header("Location: ../module/time_log_create.php");
    exit;
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include('../config/db.php');

// Search functionality
$search = $_GET['search'] ?? '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = "WHERE CONCAT(fname, ' ', lname) LIKE :search OR email LIKE :search OR position LIKE :search OR contact LIKE :search";
    $searchParams[':search'] = '%' . $search . '%';
}

// Sorting
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'asc';
$allowedSorts = ['id', 'fname', 'lname', 'email', 'contact', 'position', 'status'];
$allowedOrders = ['asc', 'desc'];

if (!in_array($sort, $allowedSorts)) $sort = 'id';
if (!in_array($order, $allowedOrders)) $order = 'asc';

// Pagination
$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total count with search
$totalQuery = "SELECT COUNT(*) FROM employees $searchCondition";
$totalStmt = $pdo->prepare($totalQuery);
foreach ($searchParams as $key => $value) {
    $totalStmt->bindValue($key, $value);
}
$totalStmt->execute();
$totalEmployees = $totalStmt->fetchColumn();
$totalPages = ceil($totalEmployees / $limit);

// Employee list with search - updated to include profile_picture column
$orderClause = ($sort === 'fname') ? "ORDER BY fname $order, lname $order" : "ORDER BY $sort $order";
$query = "SELECT id, fname, lname, email, contact, position, status, profile_picture FROM employees $searchCondition $orderClause LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($searchParams as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sorting link helper
function sort_link($column, $label) {
    $currentSort = $_GET['sort'] ?? 'id';
    $currentOrder = $_GET['order'] ?? 'asc';
    $page = $_GET['page'] ?? 1;
    $search = $_GET['search'] ?? '';

    $newOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    $arrow = ($currentSort === $column) ? ($currentOrder === 'asc' ? ' ▲' : ' ▼') : '';

    return "<a href=\"?sort=$column&order=$newOrder&page=$page&search=" . urlencode($search) . "\" class=\"hover:underline inline-block whitespace-nowrap\">$label$arrow</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <title>Employee Directory</title>
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
        .profile-img {
            transition: all 0.2s ease;
        }
        .table-row-hover:hover .profile-img {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-50">

    <div x-data="{ open: false }" class="flex h-screen">
        <?php include('sidebar.php'); ?>

        <div class="flex-1 flex flex-col">
            <?php 
            $pageTitle = "Employee Directory";
            include('header.php'); 
            ?>
            
            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Main Container -->
                <div class="w-full">
                    
                    <!-- Display Success Message -->
                    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400 text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    Employee created successfully!
                                </p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-green-400 hover:text-green-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Header Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <!-- Title and Stats -->
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 mb-2">Lists of Employees</h1>
                                <p class="text-gray-600">
                                    <i class="fas fa-users text-blue-500 mr-2"></i>
                                    Manage your organization's workforce
                                    <span class="ml-4 text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                                        <?= $totalEmployees ?> Total Employees
                                    </span>
                                </p>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a href="../module/employee_create.php" 
                                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add Employee
                                </a>
                                <button onclick="document.getElementById('dateModal').classList.remove('hidden')" 
                                        class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 transition-all duration-200 shadow-sm hover:shadow-md">
                                    <i class="fas fa-file-export mr-2"></i>
                                    Attendance Report
                                </button>
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
                                        placeholder="Search employees by name, email, position, or contact..." 
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
                                    <a href="?" 
                                       class="inline-flex items-center px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all duration-200">
                                        <i class="fas fa-times mr-2"></i>
                                        Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Hidden inputs to preserve sorting -->
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                            <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                        </form>
                    </div>

                    <!-- Employee Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Table Header -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-table text-blue-500 mr-2"></i>
                                    Employee List
                                </h3>
                                <?php if (!empty($search)): ?>
                                    <span class="text-sm text-gray-600">
                                        <i class="fas fa-filter text-orange-500 mr-1"></i>
                                        Showing results for "<?= htmlspecialchars($search) ?>"
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <?php if (!empty($employees)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <?= sort_link('fname', 'Employee') ?>
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <?= sort_link('email', 'Contact Info') ?>
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <?= sort_link('position', 'Position') ?>
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <?= sort_link('status', 'Status') ?>
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($employees as $emp): ?>
                                            <tr class="table-row-hover" onclick="window.location.href='../views/employee-edit.php?id=<?= $emp['id'] ?>'">
                                                <!-- Employee Info with Profile Picture -->
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-12 w-12">
                                                            <?php if (!empty($emp['profile_picture']) && file_exists('../uploads/profile_pics/' . $emp['profile_picture'])): ?>
                                                                <img class="profile-img h-12 w-12 rounded-full object-cover border-2 border-gray-200" 
                                                                     src="../uploads/profile_pics/<?= htmlspecialchars($emp['profile_picture']) ?>" 
                                                                     alt="Profile picture">
                                                            <?php else: ?>
                                                                <div class="profile-img h-12 w-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center border-2 border-gray-200">
                                                                    <span class="text-white font-medium text-lg">
                                                                        <?= strtoupper(substr($emp['fname'], 0, 1)) ?><?= strtoupper(substr($emp['lname'], 0, 1)) ?>
                                                                    </span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                <?= htmlspecialchars($emp['fname']) ?> <?= htmlspecialchars($emp['lname']) ?>
                                                            </div>
                                                            <div class="text-sm text-gray-500">
                                                                Employee #<?= $emp['id'] ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                
                                                <!-- Contact Info -->
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                                        <?= htmlspecialchars($emp['email']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <i class="fas fa-phone text-gray-400 mr-2"></i>
                                                        <?= htmlspecialchars($emp['contact']) ?>
                                                    </div>
                                                </td>
                                                
                                                <!-- Position -->
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <i class="fas fa-briefcase mr-1"></i>
                                                        <?= htmlspecialchars($emp['position']) ?>
                                                    </span>
                                                </td>
                                                
                                                <!-- Status -->
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $emp['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                        <i class="fas fa-circle mr-1 text-xs"></i>
                                                        <?= htmlspecialchars(ucfirst($emp['status'])) ?>
                                                    </span>
                                                </td>
                                                
                                                <!-- Actions -->
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" onclick="event.stopPropagation()">
                                                    <div class="flex items-center space-x-3">
                                                        <a href="../views/employee-edit.php?id=<?= $emp['id'] ?>" 
                                                           class="text-blue-600 hover:text-blue-900 transition-colors">
                                                            <i class="fas fa-edit mr-1"></i>
                                                            Edit
                                                        </a>
                                                        <a href="../views/time_log_list.php?employee_id=<?= $emp['id'] ?>" 
                                                           class="text-green-600 hover:text-green-900 transition-colors">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            Time Logs
                                                        </a>
                                                        <form method="POST" action="employee-delete.php" 
                                                              onsubmit="return confirm('Are you sure you want to delete this employee?');" 
                                                              class="inline">
                                                            <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                            <button type="submit" 
                                                                    class="text-red-600 hover:text-red-900 transition-colors">
                                                                <i class="fas fa-trash mr-1"></i>
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
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
                                            (<?= $totalEmployees ?> total employees)
                                        </div>

                                        <!-- Pagination Links -->
                                        <nav class="flex gap-1" aria-label="Pagination">
                                            <?php if ($page > 1): ?>
                                                <a href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>" 
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
                                                <a href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>" 
                                                   class="px-3 py-2 text-sm font-medium <?= $i === $page ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300' ?> border hover:bg-gray-50 transition-colors">
                                                    <?= $i ?>
                                                </a>
                                            <?php endfor; ?>

                                            <?php if ($page < $totalPages): ?>
                                                <a href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>" 
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
                                    <i class="fas fa-search text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No employees found</h3>
                                <p class="text-gray-500 mb-6">
                                    <?php if (!empty($search)): ?>
                                        No employees match your search criteria "<?= htmlspecialchars($search) ?>"
                                    <?php else: ?>
                                        No employees have been added yet.
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($search)): ?>
                                    <a href="?" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-times mr-2"></i>
                                        Clear Search
                                    </a>
                                <?php else: ?>
                                    <a href="../module/employee_create.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-plus mr-2"></i>
                                        Add First Employee
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Date Modal -->
    <div id="dateModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                    Generate Attendance Report
                </h2>
                <button onclick="document.getElementById('dateModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <form action="../controller/generate_attendance_report.php" method="get" class="space-y-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-plus text-green-500 mr-1"></i>
                        Start Date
                    </label>
                    <input type="date" name="start_date" id="start_date" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-minus text-red-500 mr-1"></i>
                        End Date
                    </label>
                    <input type="date" name="end_date" id="end_date" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div class="flex justify-end gap-3 pt-6">
                    <button type="button" onclick="document.getElementById('dateModal').classList.add('hidden')" 
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-times mr-1"></i>
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-download mr-1"></i>
                        Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>