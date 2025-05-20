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

    return "<a href=\"?sort=$column&order=$newOrder&page=$page\" class=\"hover:underline\">$label$arrow</a>";
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

    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
      <h1 class="text-2xl sm:text-3xl font-bold text-[#2F9C95]">Employees</h1>
      <div class="flex flex-col sm:flex-row gap-2 mt-4 sm:mt-0">
        <a href="employee-create.php" class="bg-[#0fe0fc] hover:bg-[#65adad] text-white font-semibold py-2 px-4 rounded-xl transition duration-200 text-sm sm:text-base">
          + Add New Employee
        </a>
        <a href="../controller/generate_attendance_report.php" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-xl transition duration-200 text-sm sm:text-base">
          Generate Attendance Report
        </a>
        <a href="../admin/logout.php" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-xl transition duration-200 text-sm sm:text-base">
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
