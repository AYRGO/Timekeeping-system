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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee List</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0fe0fc] min-h-screen px-4 py-8 sm:py-12 flex flex-col items-center">
    
  <div class="w-full max-w-6xl bg-white rounded-2xl shadow-xl border-t-8 border-[#00ffff] p-6 sm:p-8 space-y-6">

   <!-- ✅ Unified Admin + Approval Actions with Consistent Responsive Design -->
<div class="flex flex-col gap-4 w-full">

  <!-- Admin Actions -->
  <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#2F9C95]">Employees</h1>

    <!-- <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
    </div> -->
  </div>

  <!-- Approval Buttons -->
  <div class="flex flex-col sm:flex-row flex-wrap gap-2">
    <!-- Add Employee -->
      <a href="../module/employee_create.php" class="flex items-center gap-2 w-full sm:w-auto bg-[#0fe0fc] hover:bg-[#65adad] text-white font-semibold py-3 px-4 rounded-xl transition duration-200 text-base">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Employee
      </a>

      <!-- Overtime Report -->
      <a href="../controller/generate_overtime_report.php" class="flex items-center gap-2 w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 text-base">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m2 4H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5a1 1 0 01.7.3l5.5 5.5a1 1 0 01.3.7V19a2 2 0 01-2 2z" />
        </svg>
        Overtime Report
      </a>

      <!-- Attendance Report -->
      <a href="../controller/generate_attendance_report.php" class="flex items-center gap-2 w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 text-base">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8M8 8h8M5 20h14a2 2 0 002-2V4a2 2 0 00-2-2H5a2 2 0 00-2 2v16a2 2 0 002 2z" />
        </svg>
        Attendance Report
      </a>
    <a href="../module/overtime_request_approval.php" class="flex items-center gap-2 w-full sm:w-auto bg-cyan-500 hover:bg-cyan-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 text-base">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m4-2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Overtime Approvals
    </a>

    <a href="../module/leave_request_approval.php" class="flex items-center gap-2 w-full sm:w-auto bg-sky-500 hover:bg-sky-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 text-base">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      Leave Approvals
    </a>

    <a href="../module/rest_day_overtime_approval.php" class="flex items-center gap-2 w-full sm:w-auto bg-teal-500 hover:bg-teal-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 text-base">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582M20 20v-5h-.581M20 4a9 9 0 00-16 0M4 20a9 9 0 0016 0" />
      </svg>
      Rest Day OT Approvals
    </a>

    <a href="../module/schedule_change_request_approval.php" class="flex items-center gap-2 w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 text-base">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m2 0a2 2 0 002-2V6a2 2 0 00-2-2h-2.5a2 2 0 00-2 2v4a2 2 0 002 2zM9 16H7a2 2 0 01-2-2v-1m0-4V6a2 2 0 012-2h2.5a2 2 0 012 2v4a2 2 0 01-2 2z" />
      </svg>
      Schedule Change Approvals
    </a>

    <a href="../module/schedule_exception_request_approval.php" class="flex items-center gap-2 w-full sm:w-auto bg-rose-500 hover:bg-rose-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 text-base">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 4.93a10 10 0 0114.14 0 10 10 0 010 14.14 10 10 0 01-14.14 0 10 10 0 010-14.14z" />
      </svg>
      Schedule Exception Approvals
    </a>
    
       <!-- Logout -->
      <a href="../admin/logout.php" class="flex items-center gap-2 w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 text-base">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6-4v1m0 6v1m-6-5h.01" />
        </svg>
        Logout
      </a>
  </div>
</div>



    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm sm:text-base">
        <thead class="bg-[#0fe0fc] text-white">
          <tr>
            <th class="px-4 py-3 text-left font-semibold"><?= sort_link('id', 'ID') ?></th>
            <th class="px-4 py-3 text-left font-semibold"><?= sort_link('fname', 'Name') ?></th>
            <th class="px-4 py-3 text-left font-semibold"><?= sort_link('email', 'Email') ?></th>
            <th class="px-4 py-3 text-left font-semibold"><?= sort_link('contact', 'Contact') ?></th>
            <th class="px-4 py-3 text-left font-semibold"><?= sort_link('position', 'Position') ?></th>
            <th class="px-4 py-3 text-left font-semibold"><?= sort_link('status', 'Status') ?></th>
            <th class="px-4 py-3 text-left font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($employees as $emp): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3"><?= $emp['id'] ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($emp['fname']) ?> <?= htmlspecialchars($emp['lname']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($emp['email']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($emp['contact']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($emp['position']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($emp['status']) ?></td>
              <td class="px-4 py-3 space-x-2">
                <a href="employee-edit.php?id=<?= $emp['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                <a href="employee-delete.php?id=<?= $emp['id'] ?>" class="text-red-600 hover:underline">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center pt-6">
      <nav class="inline-flex space-x-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>" 
             class="px-3 py-1 rounded-md text-sm font-medium 
                    <?= $i == $page ? 'bg-[#0fe0fc] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </nav>
    </div>

  </div>

</body>
</html>

