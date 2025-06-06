<?php
// Secure session cookie params (set before session_start)
session_set_cookie_params([
    'lifetime' => 0,            // Session cookie lasts until browser closes
    'path' => '/',
    'domain' => '',             // Change this if needed
    'secure' => isset($_SERVER['HTTPS']),  // true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'         // Helps protect against CSRF
]);

session_start();

// Session inactivity timeout (30 minutes)
$timeout = 1800; // seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../admin/login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Check if admin logged in
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include('../config/db.php');

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

// Sorting helper function
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

     <!-- Trigger Button -->
<button onclick="document.getElementById('dateModal').classList.remove('hidden')"
  class="px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
  Attendance Report
</button>

<!-- Modal -->
<div id="dateModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
    <h2 class="text-lg font-semibold mb-4 text-gray-700">Select Date Range</h2>
    <form action="../controller/generate_attendance_report.php" method="get" class="space-y-4">
      <div>
        <label for="start_date" class="block text-sm font-medium text-gray-600">Start Date</label>
        <input type="date" name="start_date" id="start_date" required
               class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200" />
      </div>
      <div>
        <label for="end_date" class="block text-sm font-medium text-gray-600">End Date</label>
        <input type="date" name="end_date" id="end_date" required
               class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200" />
      </div>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" onclick="document.getElementById('dateModal').classList.add('hidden')"
          class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
        <button type="submit"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Generate</button>
      </div>
    </form>
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
              <td class="border border-gray-300 px-4 py-2 flex gap-2 items-center">

                <a href="employee-edit.php?id=<?= $emp['id'] ?>"
                   class="text-blue-600 font-semibold hover:underline focus:outline-none focus:ring-1 focus:ring-blue-300 rounded px-2 py-1">
                  Edit
                </a>

                <!-- Secure Delete form -->
                <form method="POST" action="employee-delete.php" onsubmit="return confirm('Are you sure you want to delete this employee?');" class="inline">
                  <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <button type="submit" class="text-red-600 font-semibold hover:underline focus:outline-none focus:ring-1 focus:ring-red-300 rounded px-2 py-1 bg-transparent border-0 cursor-pointer">
                    Delete
                  </button>
                </form>

              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

 <!-- Pagination + Logout -->
<div class="pt-6 border-t border-gray-200 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
  
  <!-- Pagination -->
  <nav class="flex flex-wrap gap-2" aria-label="Pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>"
         class="px-3 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-blue-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 <?= $i === $page ? 'bg-blue-600 text-white border-blue-600' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </nav>

  <!-- Logout Button -->
  <a href="../admin/logout.php"
     class="px-5 py-2 rounded-lg border border-red-600 text-red-600 font-semibold hover:bg-red-50 transition focus:outline-none focus:ring-2 focus:ring-red-400 self-start sm:self-auto">
    Logout
  </a>

</div>

  </div>

</body>
</html>
