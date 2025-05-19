<?php
include('../config/db.php');
session_start();

//Prevent browser from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['admin'])) {
    // If not logged in, redirect to login
    header("Location: ../admin/login.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM employees ORDER BY id ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
      <a href="employee-create.php" class="mt-4 sm:mt-0 bg-[#0fe0fc] hover:bg-[#65adad] text-white font-semibold py-2 px-4 rounded-xl transition duration-200 text-sm sm:text-base">
        + Add New Employee
      </a>
           <a href="../admin/logout.php" class="mt-4 sm:mt-0 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-xl transition duration-200 text-sm sm:text-base">
      Logout
    </a>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm sm:text-base">
        <thead class="bg-[#0fe0fc] text-white">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">ID</th>
            <th class="px-4 py-3 text-left font-semibold">Name</th>
            <th class="px-4 py-3 text-left font-semibold">Email</th>
            <th class="px-4 py-3 text-left font-semibold">Contact</th>
            <th class="px-4 py-3 text-left font-semibold">Position</th>
            <th class="px-4 py-3 text-left font-semibold">Status</th>
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

  </div>

</body>
</html>
