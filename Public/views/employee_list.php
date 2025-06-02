<?php
include('../config/db.php');
session_start();

// Prevent browser from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
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

// Count total employees
$totalStmt = $pdo->query("SELECT COUNT(*) FROM employees");
$totalEmployees = $totalStmt->fetchColumn();
$totalPages = ceil($totalEmployees / $limit);

// Get employees with sorting and pagination
if ($sort === 'fname') {
    $stmt = $pdo->prepare("SELECT * FROM employees ORDER BY fname $order, lname $order LIMIT :limit OFFSET :offset");
} else {
    $stmt = $pdo->prepare("SELECT * FROM employees ORDER BY $sort $order LIMIT :limit OFFSET :offset");
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sorting helper
function sort_link($column, $label) {
    $currentSort = $_GET['sort'] ?? 'id';
    $currentOrder = $_GET['order'] ?? 'asc';
    $page = $_GET['page'] ?? 1;

    $newOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    $arrow = ($currentSort === $column) ? ($currentOrder === 'asc' ? ' ▲' : ' ▼') : '';

    return "<a href=\"?sort=$column&order=$newOrder&page=$page\" class=\"hover:underline inline-block whitespace-nowrap\">$label$arrow</a>";
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <title>Employee List</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-10 px-4">

  <div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-lg p-8 flex flex-col gap-8">

   <!-- Admin + Approval Actions -->
   <div class="flex flex-col gap-4">

    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Employees</h1>
    </div>

    <!-- Approval Buttons -->
    <div class="flex flex-wrap gap-3">
      <a href="../module/employee_create.php"
        class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
        Add Employee
      </a>

      <!-- <a href="../module/employee_assign_schedule.php"
        class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
        Assign Schedule
      </a>

      <a href="../controller/generate_overtime_report.php"
        class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
        Overtime Report
      </a> -->

      <a href="../controller/generate_attendance_report.php"
        class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
        Attendance Report
      </a>

      <!-- <a href="../module/overtime_request_approval.php"
        class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
        Overtime Approvals
      </a>

      <a href="../module/leave_request_approval.php"
        class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
        Leave Approvals
      </a>

      <a href="../module/rest_day_overtime_approval.php"
        class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
        Rest Day OT Approvals
      </a>

      <a href="../module/schedule_change_request_approval.php"
        class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
        Schedule Change Approvals
      </a>

      <a href="../module/schedule_exception_request_approval.php"
        class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
        Schedule Exception Approvals
      </a> -->
      
      <a href="../admin/logout.php"
        class="px-5 py-2 rounded-lg border border-red-600 text-red-600 font-semibold hover:bg-red-50 transition focus:outline-none focus:ring-2 focus:ring-red-400">
        Logout
      </a>
    </div>
   </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse border border-gray-300 text-left text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="border border-gray-300 px-4 py-3"><?= sort_link('id', 'ID') ?></th>
            <th class="border border-gray-300 px-4 py-3"><?= sort_link('fname', 'Name') ?></th>
            <th class="border border-gray-300 px-4 py-3"><?= sort_link('email', 'Email') ?></th>
            <th class="border border-gray-300 px-4 py-3"><?= sort_link('contact', 'Contact') ?></th>
            <th class="border border-gray-300 px-4 py-3"><?= sort_link('position', 'Position') ?></th>
            <th class="border border-gray-300 px-4 py-3"><?= sort_link('status', 'Status') ?></th>
            <th class="border border-gray-300 px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employees as $emp): ?>
            <tr class="even:bg-gray-50 hover:bg-blue-50">
              <td class="border border-gray-300 px-4 py-2"><?= $emp['id'] ?></td>
              <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($emp['fname']) ?> <?= htmlspecialchars($emp['lname']) ?></td>
              <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($emp['email']) ?></td>
              <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($emp['contact']) ?></td>
              <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($emp['position']) ?></td>
              <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($emp['status']) ?></td>
              <td class="border border-gray-300 px-4 py-2 flex gap-2">
                <a href="employee-edit.php?id=<?= $emp['id'] ?>"
                   class="text-blue-600 font-semibold hover:underline focus:outline-none focus:ring-1 focus:ring-blue-300 rounded px-2 py-1">
                  Edit
                </a>
                <a href="employee-delete.php?id=<?= $emp['id'] ?>"
                   class="text-red-600 font-semibold hover:underline focus:outline-none focus:ring-1 focus:ring-red-300 rounded px-2 py-1">
                  Delete
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pt-6 border-t border-gray-200 flex justify-center space-x-2">
      <nav aria-label="Pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>"
             class="px-3 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-blue-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 <?= $i === $page ? 'bg-blue-600 text-white border-blue-600' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </nav>
    </div>

  </div>

</body>
</html>
